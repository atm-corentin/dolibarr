<?php
/* Copyright (C) 2026  ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';

/**
 * Business guard detecting proposal/order lines whose current margin (or mark)
 * rate is below the minimum rate configured through the Discountrules module.
 *
 * The check is UI-only: it never blocks validation, it only tells the caller
 * whether a non-blocking warning should be displayed in the validation modal.
 */
class CliChaumeilMinimumMarginWarningGuard
{
	/**
	 * @var string[] Business object elements supported by this guard.
	 */
	private const SUPPORTED_ELEMENTS = array('propal', 'commande');

	/**
	 * @var string Rate type identifier for the mark rate (taux de marque).
	 */
	private const RATE_TYPE_MARK = 'MarkRate';

	/**
	 * @var string Rate type identifier for the margin rate (taux de marge).
	 */
	private const RATE_TYPE_MARGIN = 'MarginRate';

	/**
	 * @var string Translation key of the displayed warning message.
	 */
	private const WARNING_TRANSLATION_KEY = 'CliChaumeil_MinimumMarginValidationWarning';

	/**
	 * @var string Discountrules global configuration enabling the rate control.
	 */
	private const DISCOUNTRULES_USE_MARKUP_MARGIN_RATE = 'DISCOUNTRULES_USE_MARKUP_MARGIN_RATE';

	/**
	 * @var string Discountrules global configuration selecting the rate type (0=mark, 1=margin).
	 */
	private const DISCOUNTRULES_MARKUP_MARGIN_RATE = 'DISCOUNTRULES_MARKUP_MARGIN_RATE';

	/**
	 * @var string Discountrules global minimum rate configuration.
	 */
	private const DISCOUNTRULES_MINIMUM_RATE = 'DISCOUNTRULES_MINIMUM_RATE';

	/**
	 * @var string Discountrules extrafield holding the per-thirdparty/per-product minimum rate.
	 */
	private const MINIMUM_RATE_EXTRAFIELD = 'options_discountrules_min_markup_margin_percent';

	/**
	 * @var int Subtotal module special_code used to flag subtotal/title lines.
	 */
	private const SUBTOTAL_SPECIAL_CODE = 104777;

	/**
	 * @var int Product type used by the subtotal module for title/subtotal lines.
	 */
	private const SUBTOTAL_PRODUCT_TYPE = 9;

	/**
	 * @var float Fallback numeric value for empty amounts.
	 */
	private const ZERO_AMOUNT = 0.0;

	/**
	 * @var DoliDB Database handler.
	 */
	private DoliDB $db;

	/**
	 * @var array<int,?float> Per-product minimum rate cache (null = no threshold defined).
	 */
	private array $productMinimumRateCache = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Tell whether at least one non-excluded line has a margin/mark rate below
	 * the applicable minimum rate.
	 *
	 * @param CommonObject $object Proposal or order to inspect.
	 * @return bool True as soon as one line is below the minimum rate.
	 */
	public function hasLineBelowMinimumRate(CommonObject $object): bool
	{
		if (!isModEnabled('discountrules')) {
			return false;
		}

		if (!getDolGlobalInt(self::DISCOUNTRULES_USE_MARKUP_MARGIN_RATE)) {
			return false;
		}

		if (!$this->isSupportedObject($object)) {
			return false;
		}

		if (!$this->ensureLinesLoaded($object)) {
			return false;
		}

		$rateType = $this->getConfiguredRateType();

		foreach ($object->lines as $line) {
			if ($this->isExcludedLine($line)) {
				continue;
			}

			$costPrice = $this->normalizeAmount($line->pa_ht ?? null);
			if ($costPrice <= self::ZERO_AMOUNT) {
				continue;
			}

			$minimumRate = $this->getMinimumRateForLine($object, $line);
			if ($minimumRate === null) {
				continue;
			}

			$rate = $this->getLineRate($line, $rateType);
			if ($rate === null) {
				continue;
			}

			if ($rate < $minimumRate) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the red bold warning fragment injected into the validation modal.
	 *
	 * @param Translate $langs Translation handler.
	 * @return string HTML fragment.
	 */
	public function getWarningHtml(Translate $langs): string
	{
		$message = $langs->trans(self::WARNING_TRANSLATION_KEY);

		return '<div class="confirmmessage center"><strong class="error">' . dol_escape_htmltag($message) . '</strong></div>';
	}

	/**
	 * Tell whether the object element is supported by this guard.
	 *
	 * @param CommonObject $object Object to inspect.
	 * @return bool
	 */
	private function isSupportedObject(CommonObject $object): bool
	{
		return !empty($object->element) && in_array($object->element, self::SUPPORTED_ELEMENTS, true);
	}

	/**
	 * Load object lines when they are not already available.
	 *
	 * @param CommonObject $object Object to hydrate.
	 * @return bool True when lines are available, false on failure.
	 */
	private function ensureLinesLoaded(CommonObject $object): bool
	{
		if (is_array($object->lines) && !empty($object->lines)) {
			return true;
		}

		$result = $object->fetch_lines();
		if ($result < 0) {
			dol_syslog(
				__METHOD__ . ' failed to load lines for ' . $object->element . ' id=' . ((int) $object->id),
				LOG_ERR
			);
			return false;
		}

		return is_array($object->lines) && !empty($object->lines);
	}

	/**
	 * Tell whether a line must be excluded from the margin control.
	 *
	 * Excludes subtotal/title lines and lines linked to an absolute discount.
	 * Free-text, title and lines without a cost price are filtered later by the
	 * cost-price guard in {@see hasLineBelowMinimumRate()}.
	 *
	 * @param CommonObjectLine $line Line to inspect.
	 * @return bool
	 */
	private function isExcludedLine(CommonObjectLine $line): bool
	{
		if ($this->isSubtotalLine($line)) {
			return true;
		}

		if (!empty($line->fk_remise_except) && (int) $line->fk_remise_except > 0) {
			return true;
		}

		return false;
	}

	/**
	 * Tell whether a line is a subtotal/title line from the subtotal module.
	 *
	 * @param CommonObjectLine $line Line to inspect.
	 * @return bool
	 */
	private function isSubtotalLine(CommonObjectLine $line): bool
	{
		if (isModEnabled('subtotal')) {
			dol_include_once('/subtotal/class/subtotal.class.php');
			if (class_exists('TSubtotal')) {
				$checkedLine = $line;
				return (bool) TSubtotal::isModSubtotalLine($checkedLine);
			}
		}

		return !empty($line->special_code)
			&& (int) $line->special_code === self::SUBTOTAL_SPECIAL_CODE
			&& (int) $line->product_type === self::SUBTOTAL_PRODUCT_TYPE;
	}

	/**
	 * Return the configured rate type (mark or margin).
	 *
	 * @return string One of self::RATE_TYPE_MARK or self::RATE_TYPE_MARGIN.
	 */
	private function getConfiguredRateType(): string
	{
		return getDolGlobalInt(self::DISCOUNTRULES_MARKUP_MARGIN_RATE) === 1
			? self::RATE_TYPE_MARGIN
			: self::RATE_TYPE_MARK;
	}

	/**
	 * Resolve the applicable minimum rate for a line.
	 *
	 * Priority: thirdparty threshold, then product threshold, then global threshold.
	 *
	 * @param CommonObject     $object Owning object (proposal or order).
	 * @param CommonObjectLine $line   Line to inspect.
	 * @return float|null Minimum rate, or null when none is configured.
	 */
	private function getMinimumRateForLine(CommonObject $object, CommonObjectLine $line): ?float
	{
		$thirdpartyRate = $this->getThirdpartyMinimumRate($object);
		if ($thirdpartyRate !== null) {
			return $thirdpartyRate;
		}

		$productRate = $this->getProductMinimumRate($line);
		if ($productRate !== null) {
			return $productRate;
		}

		return $this->getGlobalMinimumRate();
	}

	/**
	 * Read the thirdparty minimum rate from its Discountrules extrafield.
	 *
	 * @param CommonObject $object Owning object.
	 * @return float|null Rate, or null when unset/empty.
	 */
	private function getThirdpartyMinimumRate(CommonObject $object): ?float
	{
		if ((empty($object->thirdparty) || !is_object($object->thirdparty)) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}

		if (empty($object->thirdparty) || !is_object($object->thirdparty)) {
			return null;
		}

		if ((int) $object->thirdparty->id <= 0) {
			return null;
		}

		if (!isset($object->thirdparty->array_options) || !is_array($object->thirdparty->array_options)) {
			$object->thirdparty->fetch_optionals();
		}

		$value = $object->thirdparty->array_options[self::MINIMUM_RATE_EXTRAFIELD] ?? null;
		if (empty($value)) {
			return null;
		}

		return (float) price2num((string) $value);
	}

	/**
	 * Read the product minimum rate from its Discountrules extrafield.
	 *
	 * Only loaded when no thirdparty threshold applies; results are cached per product.
	 *
	 * @param CommonObjectLine $line Line to inspect.
	 * @return float|null Rate, or null when unset/empty or no product is linked.
	 */
	private function getProductMinimumRate(CommonObjectLine $line): ?float
	{
		$fkProduct = isset($line->fk_product) ? (int) $line->fk_product : 0;
		if ($fkProduct <= 0) {
			return null;
		}

		if (array_key_exists($fkProduct, $this->productMinimumRateCache)) {
			return $this->productMinimumRateCache[$fkProduct];
		}

		$rate = null;
		$product = new Product($this->db);
		if ($product->fetch($fkProduct) > 0) {
			$product->fetch_optionals();
			$value = $product->array_options[self::MINIMUM_RATE_EXTRAFIELD] ?? null;
			if (!empty($value)) {
				$rate = (float) price2num((string) $value);
			}
		}

		$this->productMinimumRateCache[$fkProduct] = $rate;

		return $rate;
	}

	/**
	 * Read the global Discountrules minimum rate.
	 *
	 * @return float|null Rate, or null when the configuration is empty.
	 */
	private function getGlobalMinimumRate(): ?float
	{
		$raw = getDolGlobalString(self::DISCOUNTRULES_MINIMUM_RATE);
		if ($raw === '') {
			return null;
		}

		return (float) price2num($raw);
	}

	/**
	 * Compute the current margin/mark rate of a line.
	 *
	 * @param CommonObjectLine $line     Line to inspect.
	 * @param string           $rateType Rate type (mark or margin).
	 * @return float|null Rate, or null when it cannot be computed.
	 */
	private function getLineRate(CommonObjectLine $line, string $rateType): ?float
	{
		$netUnitSalePrice = $this->getNetUnitSalePrice($line);

		$marginInfos = getMarginInfos(
			$netUnitSalePrice,
			0,
			(float) ($line->tva_tx ?? 0),
			(float) ($line->localtax1_tx ?? 0),
			(float) ($line->localtax2_tx ?? 0),
			isset($line->fk_fournprice) ? (int) $line->fk_fournprice : 0,
			$this->normalizeAmount($line->pa_ht ?? null)
		);

		$index = ($rateType === self::RATE_TYPE_MARGIN) ? 1 : 2;
		if (!isset($marginInfos[$index]) || !is_numeric($marginInfos[$index])) {
			return null;
		}

		return (float) $marginInfos[$index];
	}

	/**
	 * Compute the net unit sale price used for the margin comparison.
	 *
	 * The line total already includes the commercial discount, so it is the most
	 * reliable source when a quantity is available; otherwise the unit price is
	 * discounted by the line rate.
	 *
	 * @param CommonObjectLine $line Line to inspect.
	 * @return float
	 */
	private function getNetUnitSalePrice(CommonObjectLine $line): float
	{
		$quantity = $this->normalizeAmount($line->qty ?? null);
		$totalHt = $this->normalizeAmount($line->total_ht ?? null);

		if (abs($quantity) > 0.0) {
			return $totalHt / $quantity;
		}

		$unitPrice = $this->normalizeAmount($line->subprice ?? null);
		$discountPercent = $this->normalizeAmount($line->remise_percent ?? null);

		if ($discountPercent <= 0.0) {
			return $unitPrice;
		}

		return $unitPrice * (1 - ($discountPercent / 100));
	}

	/**
	 * Normalize an amount to a float compatible with Dolibarr price helpers.
	 *
	 * @param mixed $value Raw amount value.
	 * @return float
	 */
	private function normalizeAmount($value): float
	{
		if ($value === null || $value === '') {
			return self::ZERO_AMOUNT;
		}

		return (float) price2num((string) $value);
	}
}
