<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * Resolve the minimum markup/margin rate configured by the discountrules module for one line.
 *
 * Order matches the discountrules trigger behavior: thirdparty override > product override > global.
 * Product override is skipped for lines without a product.
 */
class CliChaumeilSubcontractingMinimumRateResolver
{
	public const TYPE_MARK   = 'MarkRate';
	public const TYPE_MARGIN = 'MarginRate';

	public const SOURCE_GLOBAL     = 'global';
	public const SOURCE_THIRDPARTY = 'thirdparty';
	public const SOURCE_PRODUCT    = 'product';

	private const OVERRIDE_FIELD = 'options_discountrules_min_markup_margin_percent';

	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

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
	 * Resolve the minimum rate applicable to one parent line.
	 *
	 * @param CommonObject $parent Parent commercial document (Propal or Commande).
	 * @param object       $line   Parent line (PropaleLigne or OrderLine) — expects fk_product property.
	 * @return array{enabled:bool,rate:float,type:string,source:string}
	 */
	public function resolve(CommonObject $parent, object $line): array
	{
		if (!getDolGlobalInt('DISCOUNTRULES_USE_MARKUP_MARGIN_RATE')) {
			return $this->disabled();
		}

		$type = $this->resolveType();
		if ($type === '') {
			return $this->disabled();
		}

		$globalRate = (float) getDolGlobalString('DISCOUNTRULES_MINIMUM_RATE');
		$rate       = $globalRate;
		$source     = self::SOURCE_GLOBAL;

		$thirdpartyRate = $this->resolveThirdpartyOverride($parent);
		if ($thirdpartyRate !== null) {
			$rate   = $thirdpartyRate;
			$source = self::SOURCE_THIRDPARTY;
		} elseif (!empty($line->fk_product)) {
			$productRate = $this->resolveProductOverride((int) $line->fk_product);
			if ($productRate !== null) {
				$rate   = $productRate;
				$source = self::SOURCE_PRODUCT;
			}
		}

		return array(
			'enabled' => true,
			'rate'    => $rate,
			'type'    => $type,
			'source'  => $source,
		);
	}

	/**
	 * Read the configured markup/margin rate type.
	 *
	 * Caller must have validated DISCOUNTRULES_USE_MARKUP_MARGIN_RATE first — when the global
	 * is missing, getDolGlobalInt() returns 0 which collides with the legitimate MarkRate value.
	 *
	 * @return string TYPE_MARK or TYPE_MARGIN or empty when unknown.
	 */
	private function resolveType(): string
	{
		$rawType = getDolGlobalInt('DISCOUNTRULES_MARKUP_MARGIN_RATE');
		if ($rawType === 0) {
			return self::TYPE_MARK;
		}
		if ($rawType === 1) {
			return self::TYPE_MARGIN;
		}
		return '';
	}

	/**
	 * Read the thirdparty override rate for the parent document.
	 *
	 * @param CommonObject $parent Parent commercial document.
	 * @return float|null Rate when defined, null otherwise.
	 */
	private function resolveThirdpartyOverride(CommonObject $parent): ?float
	{
		if (!isset($parent->thirdparty) || !($parent->thirdparty instanceof Societe)) {
			$socId = (int) ($parent->socid ?? 0);
			if ($socId <= 0) {
				return null;
			}
			$thirdparty  = new Societe($this->db);
			$fetchResult = $thirdparty->fetch($socId);
			if ($fetchResult < 0) {
				dol_syslog(__METHOD__.' thirdparty fetch failed for socid='.$socId.' '.$thirdparty->error, LOG_ERR);
				throw new RuntimeException('Unable to load thirdparty #'.$socId.' for discountrules rate resolution.');
			}
			if ($fetchResult === 0) {
				return null;
			}
			$parent->thirdparty = $thirdparty;
		}

		$thirdparty = $parent->thirdparty;
		if (!is_array($thirdparty->array_options) || empty($thirdparty->array_options)) {
			$thirdparty->fetch_optionals();
		}

		$value = $thirdparty->array_options[self::OVERRIDE_FIELD] ?? null;
		if ($value === null || $value === '') {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Read the product override rate.
	 *
	 * @param int $productId Product id.
	 * @return float|null Rate when defined, null otherwise.
	 */
	private function resolveProductOverride(int $productId): ?float
	{
		if ($productId <= 0) {
			return null;
		}

		$product     = new Product($this->db);
		$fetchResult = $product->fetch($productId);
		if ($fetchResult < 0) {
			dol_syslog(__METHOD__.' product fetch failed for id='.$productId.' '.$product->error, LOG_ERR);
			throw new RuntimeException('Unable to load product #'.$productId.' for discountrules rate resolution.');
		}
		if ($fetchResult === 0) {
			return null;
		}
		$product->fetch_optionals();

		$value = $product->array_options[self::OVERRIDE_FIELD] ?? null;
		if ($value === null || $value === '') {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Compute the selling price from a purchase price using the resolved rate and type.
	 *
	 * @param float  $buyPrice Purchase price (pa_ht).
	 * @param float  $rate     Markup or margin rate in percent.
	 * @param string $type     TYPE_MARK or TYPE_MARGIN.
	 * @return float Selling price rounded with price2num('MT'). Returns 0.0 for invalid input.
	 */
	public function computeSellingPrice(float $buyPrice, float $rate, string $type): float
	{
		if ($type === self::TYPE_MARK) {
			$denominator = 1 - $rate / 100;
			if ($denominator <= 0) {
				return 0.0;
			}
			return (float) price2num($buyPrice / $denominator, 'MT');
		}
		if ($type === self::TYPE_MARGIN) {
			return (float) price2num($buyPrice * (1 + $rate / 100), 'MT');
		}
		return 0.0;
	}

	/**
	 * Build a disabled resolution payload.
	 *
	 * @return array{enabled:bool,rate:float,type:string,source:string}
	 */
	private function disabled(): array
	{
		return array(
			'enabled' => false,
			'rate'    => 0.0,
			'type'    => '',
			'source'  => self::SOURCE_GLOBAL,
		);
	}
}
