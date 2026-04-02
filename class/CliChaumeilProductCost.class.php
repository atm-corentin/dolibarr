<?php
declare(strict_types=1);
/* Copyright (C) 2025  Grégory Maza  <gregory.maza@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once __DIR__ . '/CostBreakdownInput.class.php';
require_once __DIR__ . '/CostBreakdownResult.class.php';

/**
 * Deterministic calculator for CliChaumeil product cost breakdown.
 */
class CliChaumeilProductCostCalculator
{
	/**
	 * Prefix used by Dolibarr to store extrafields values.
	 *
	 * @var string
	 */
	public const EXTRA_PREFIX = 'options_';

	/**
	 * Default overhead rate used when configuration is empty.
	 *
	 * @var string
	 */
	public const DEFAULT_RATE_VALUE = '0.25';

	/**
	 * Persisted amount fields used to compute the base cost.
	 *
	 * @var string[]
	 */
	public const COST_FIELDS = array(
		'clichaumeil_pa_support',
		'clichaumeil_pa_sav',
		'clichaumeil_pa_machine',
		'clichaumeil_pa_encre',
		'clichaumeil_pa_mo',
	);

	/**
	 * Packaging percent extrafield.
	 *
	 * @var string
	 */
	public const PACKAGING_PERCENT_FIELD = 'clichaumeil_conditionnement_percent';

	/**
	 * Transport percent extrafield.
	 *
	 * @var string
	 */
	public const TRANSPORT_PERCENT_FIELD = 'clichaumeil_transport_percent';

	/**
	 * Overhead percent extrafield.
	 *
	 * @var string
	 */
	public const FG_PERCENT_FIELD = 'clichaumeil_fg_percent';

	/**
	 * Overhead amount extrafield.
	 *
	 * @var string
	 */
	public const FG_AMOUNT_FIELD = 'clichaumeil_pa_fg';

	/**
	 * Virtual field used only by UI rendering.
	 *
	 * @var string
	 */
	public const VIRTUAL_TOTAL_COSTS_FIELD = 'clichaumeil_total_costs';

	/**
	 * Supported product types.
	 *
	 * @var int[]
	 */
	public const SUPPORTED_PRODUCT_TYPES = array(Product::TYPE_PRODUCT, Product::TYPE_SERVICE);

	/**
	 * Amount precision.
	 *
	 * @var int
	 */
	public const PRECISION_AMOUNT = 4;

	/**
	 * Percent precision.
	 *
	 * @var int
	 */
	public const PRECISION_PERCENT = 4;

	/**
	 * Semaphore preventing recursion on product synchronization.
	 *
	 * @var array<int,bool>
	 */
	private static $processingProducts = array();

	/**
	 * Tell if a product type is supported by the breakdown feature.
	 *
	 * @param Product $product Product instance.
	 * @return bool
	 */
	public static function isSupportedProduct(Product $product): bool
	{
		return ($product instanceof Product) && in_array((int) $product->type, self::SUPPORTED_PRODUCT_TYPES, true);
	}

	/**
	 * Return the default configured overhead rate.
	 *
	 * @return string
	 */
	public static function getDefaultOverheadRate(): string
	{
		return (string) getDolGlobalString('CLICHAUMEIL_DEFAULT_OVERHEAD_RATE', self::DEFAULT_RATE_VALUE);
	}

	/**
	 * Compute and synchronize persisted fields from product extrafields.
	 *
	 * @param Product $product Product to synchronize.
	 * @param User    $user    Acting user.
	 * @return int
	 */
	public static function calculateAndUpdateProductCostPriceFromExtrafields(Product $product, User $user): int
	{
		if (!self::isSupportedProduct($product) || empty($product->id)) {
			return 0;
		}

		$productId = (int) $product->id;
		if (isset(self::$processingProducts[$productId])) {
			return 0;
		}

		self::$processingProducts[$productId] = true;

		try {
			$input = self::buildInput($product);
			$result = self::compute($input);

			return self::syncComputedFields($product, $result, $user);
		} catch (Exception $exception) {
			dol_syslog(
				__METHOD__ . ' failed for product #' . $productId . ': ' . $exception->getMessage(),
				LOG_ERR
			);

			return -1;
		} finally {
			unset(self::$processingProducts[$productId]);
		}
	}

	/**
	 * Return the computed breakdown without any persistence side effect.
	 *
	 * @param Product $product Product to analyze.
	 * @return CostBreakdownResult
	 */
	public static function getComputedBreakdown(Product $product): CostBreakdownResult
	{
		$input = self::buildInput($product);

		return self::compute($input);
	}

	/**
	 * Build deterministic input values from product extrafields.
	 *
	 * @param Product $product Product to analyze.
	 * @return CostBreakdownInput
	 */
	public static function buildInput(Product $product): CostBreakdownInput
	{
		self::ensureExtrafieldsLoaded($product);

		return new CostBreakdownInput(
			self::normalizeAmount(self::getExtrafieldValue($product, self::COST_FIELDS[0])),
			self::normalizeAmount(self::getExtrafieldValue($product, self::COST_FIELDS[1])),
			self::normalizeAmount(self::getExtrafieldValue($product, self::COST_FIELDS[2])),
			self::normalizeAmount(self::getExtrafieldValue($product, self::COST_FIELDS[3])),
			self::normalizeAmount(self::getExtrafieldValue($product, self::COST_FIELDS[4])),
			self::normalizePercent(self::getExtrafieldValue($product, self::PACKAGING_PERCENT_FIELD)) ?? 0.0,
			self::normalizePercent(self::getExtrafieldValue($product, self::TRANSPORT_PERCENT_FIELD)) ?? 0.0,
			self::normalizePercent(self::getExtrafieldValue($product, self::FG_PERCENT_FIELD))
		);
	}

	/**
	 * Compute the complete cost breakdown from normalized values.
	 *
	 * @param CostBreakdownInput $input Normalized input values.
	 * @return CostBreakdownResult
	 */
	public static function compute(CostBreakdownInput $input): CostBreakdownResult
	{
		$baseCost = self::roundAmount(
			$input->paSupport
			+ $input->paSav
			+ $input->paMachine
			+ $input->paEncre
			+ $input->paMo
		);

		$packagingAmount = self::roundAmount($baseCost * $input->packagingPercent / 100);
		$transportAmount = self::roundAmount($baseCost * $input->transportPercent / 100);
		$totalCosts = self::roundAmount($baseCost + $packagingAmount + $transportAmount);

		if ($input->fgPercent === null) {
			return new CostBreakdownResult(
				$baseCost,
				$packagingAmount,
				$transportAmount,
				$totalCosts,
				null,
				null,
				false,
				'missing_fg_percent'
			);
		}

		$paFg = self::roundAmount($totalCosts * $input->fgPercent / 100);
		$costPrice = self::roundAmount($totalCosts + $paFg);

		return new CostBreakdownResult(
			$baseCost,
			$packagingAmount,
			$transportAmount,
			$totalCosts,
			$paFg,
			$costPrice,
			true,
			null
		);
	}

	/**
	 * Ensure extrafields are available on the product instance.
	 *
	 * @param Product $product Product to load.
	 * @return void
	 */
	public static function ensureExtrafieldsLoaded(Product $product): void
	{
		$requiredKeys = array_merge(
			self::COST_FIELDS,
			array(
				self::PACKAGING_PERCENT_FIELD,
				self::TRANSPORT_PERCENT_FIELD,
				self::FG_PERCENT_FIELD,
				self::FG_AMOUNT_FIELD,
			)
		);

		$mustLoad = empty($product->array_options);
		if (!$mustLoad) {
			foreach ($requiredKeys as $field) {
				if (!array_key_exists(self::EXTRA_PREFIX . $field, $product->array_options)) {
					$mustLoad = true;
					break;
				}
			}
		}

		if ($mustLoad && !empty($product->id)) {
			$product->fetch_optionals($product->id);
		}

		if (empty($product->array_options)) {
			$product->array_options = array();
		}
	}

	/**
	 * Synchronize persisted computed fields when the final calculation is allowed.
	 *
	 * @param Product             $product Product to update.
	 * @param CostBreakdownResult $result  Computed values.
	 * @param User                $user    Acting user.
	 * @return int
	 * @throws Exception
	 */
	public static function syncComputedFields(Product $product, CostBreakdownResult $result, User $user): int
	{
		self::ensureExtrafieldsLoaded($product);

		if (!$result->isFinalComputable) {
			return 0;
		}

		$changes = 0;
		$changes += self::syncExtraFieldAmount($product, self::FG_AMOUNT_FIELD, $result->paFg, $user);
		$changes += self::syncProductFieldAmount($product, 'cost_price', $result->costPrice, $user);

		return $changes;
	}

	/**
	 * Normalize an amount value according to the business contract.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	public static function normalizeAmount($value): float
	{
		if ($value === null || $value === '') {
			return 0.0;
		}

		return self::roundAmount((float) price2num((string) $value, self::PRECISION_AMOUNT));
	}

	/**
	 * Normalize a percent value according to the business contract.
	 *
	 * @param mixed $value Raw value.
	 * @return float|null
	 */
	public static function normalizePercent($value): ?float
	{
		if ($value === null || $value === '') {
			return null;
		}

		return self::roundPercent((float) price2num((string) $value, self::PRECISION_PERCENT));
	}

	/**
	 * Compare current and target values using normalized decimals.
	 *
	 * @param mixed $current Current value.
	 * @param mixed $target  Target value.
	 * @return bool
	 */
	public static function valueDiffers($current, $target): bool
	{
		if ($current === null && $target === null) {
			return false;
		}

		if ($current === null || $target === null) {
			return true;
		}

		return self::roundAmount((float) $current) !== self::roundAmount((float) $target);
	}

	/**
	 * Read an extrafield raw value from the product.
	 *
	 * @param Product $product Product instance.
	 * @param string  $field   Extrafield name without prefix.
	 * @return mixed
	 */
	public static function getExtrafieldValue(Product $product, string $field)
	{
		return $product->array_options[self::EXTRA_PREFIX . $field] ?? null;
	}

	/**
	 * Tell if a field must be rendered as a percentage.
	 *
	 * @param string $field Field name.
	 * @return bool
	 */
	public static function isPercentField(string $field): bool
	{
		return in_array(
			$field,
			array(self::PACKAGING_PERCENT_FIELD, self::TRANSPORT_PERCENT_FIELD, self::FG_PERCENT_FIELD),
			true
		);
	}

	/**
	 * Round an amount using the module precision.
	 *
	 * @param float $value Amount to round.
	 * @return float
	 */
	private static function roundAmount(float $value): float
	{
		return (float) price2num((string) $value, self::PRECISION_AMOUNT);
	}

	/**
	 * Round a percent using the module precision.
	 *
	 * @param float $value Percent to round.
	 * @return float
	 */
	private static function roundPercent(float $value): float
	{
		return (float) price2num((string) $value, self::PRECISION_PERCENT);
	}

	/**
	 * Persist an amount extrafield when needed.
	 *
	 * @param Product $product Product to update.
	 * @param string  $field   Extrafield name.
	 * @param float   $value   Target amount.
	 * @param User    $user    Acting user.
	 * @return int
	 * @throws Exception
	 */
	private static function syncExtraFieldAmount(Product $product, string $field, float $value, User $user): int
	{
		$current = self::getExtrafieldValue($product, $field);
		if (!self::valueDiffers($current, $value)) {
			return 0;
		}

		$product->array_options[self::EXTRA_PREFIX . $field] = self::roundAmount($value);
		$result = $product->updateExtraField($field, 'CLICHAUMEIL_PRODUCT_COST', $user);
		if ($result < 0) {
			throw new Exception('Failed to update extrafield ' . $field . ' on product #' . (int) $product->id);
		}

		return 1;
	}

	/**
	 * Persist a native product amount field when needed.
	 *
	 * @param Product $product Product to update.
	 * @param string  $field   Native field name.
	 * @param float   $value   Target amount.
	 * @param User    $user    Acting user.
	 * @return int
	 * @throws Exception
	 */
	private static function syncProductFieldAmount(Product $product, string $field, float $value, User $user): int
	{
		$current = property_exists($product, $field) ? $product->{$field} : null;
		if (!self::valueDiffers($current, $value)) {
			return 0;
		}

		$result = $product->setValueFrom($field, self::roundAmount($value), 'product', (int) $product->id, 'text', 'rowid', $user, '');
		if ($result < 0) {
			throw new Exception('Failed to update product field ' . $field . ' on product #' . (int) $product->id);
		}

		$product->{$field} = self::roundAmount($value);

		return 1;
	}
}
