<?php
/* Copyright (C) 2025  Grégory Maza  <gregory.maza@atm-consulting.fr>
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

/**
 * Helpers shared by hooks/triggers for CliChaumeil product cost calculations.
 */

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * Small helper dedicated to CliChaumeil cost breakdown.
 */
class CliChaumeilProductCostCalculator
{
	/**
	 * @var string Prefix used by Dolibarr to store extrafields values.
	 */
	private const EXTRA_PREFIX = 'options_';

	/**
	 * @var string[] Extrafields that compose the manual cost inputs.
	 */
	private const COST_FIELDS = array(
		'pa_support',
		'pa_sav',
		'pa_machine',
		'pa_encre',
		'pa_mo',
	);

	/**
	 * @var string Extrafield storing the overhead percentage.
	 */
	private const FG_PERCENT_FIELD = 'fg_percent';

	/**
	 * @var string Extrafield storing the calculated overhead amount.
	 */
	private const FG_AMOUNT_FIELD = 'pa_fg';

	/**
	 * Run normalization / calculations then persist values.
	 *
	 * @param Product $product Product to update
	 * @param User    $user    Acting user
	 * @return int             >0 when data updated, 0 when nothing changed, <0 on failure
	 */
	public static function synchronize(Product $product, User $user): int
	{
		if (!self::isSupportedProduct($product) || empty($product->id)) {
			return 0;
		}

		self::ensureExtrafieldsLoaded($product);

		$data = self::buildData($product);
		$changes = 0;

		// Update FG percent if required (default + rounding)
		if (self::valueDiffers(self::getExtrafieldValue($product, self::FG_PERCENT_FIELD), $data['fg_percent'])) {
			$product->array_options[self::EXTRA_PREFIX.self::FG_PERCENT_FIELD] = $data['fg_percent'];
			$result = $product->updateExtraField(self::FG_PERCENT_FIELD, 'CLICHAUMEIL_PRODUCT_COST', $user);
			if ($result < 0) {
				return -1;
			}
			$changes++;
		}

		// Update PA FG
		if (self::valueDiffers(self::getExtrafieldValue($product, self::FG_AMOUNT_FIELD), $data['pa_fg'])) {
			$product->array_options[self::EXTRA_PREFIX.self::FG_AMOUNT_FIELD] = $data['pa_fg'];
			$result = $product->updateExtraField(self::FG_AMOUNT_FIELD, 'CLICHAUMEIL_PRODUCT_COST', $user);
			if ($result < 0) {
				return -1;
			}
			$changes++;
		}

		$result = self::updateProductPriceField($product, 'cost_price', $data['cost_price'], $user);
		if ($result < 0) {
			return -1;
		}
		$changes += $result;

		return $changes;
	}

	/**
	 * Support only simple (type=0) products.
	 *
	 * @param Product $product
	 * @return bool
	 */
	public static function isSupportedProduct(Product $product): bool
	{
		return ($product instanceof Product) && ((int) $product->type === Product::TYPE_PRODUCT);
	}

	/**
	 * Load extrafields only once on demand.
	 *
	 * @param Product $product
	 * @return void
	 */
	private static function ensureExtrafieldsLoaded(Product $product): void
	{
		if (empty($product->array_options) || !array_key_exists(self::EXTRA_PREFIX.self::FG_PERCENT_FIELD, $product->array_options)) {
			if (!empty($product->id)) {
				$product->fetch_optionals($product->id);
			}
			if (empty($product->array_options)) {
				$product->array_options = array();
			}
		}
	}

	/**
	 * Build normalized numbers used by synchronisation.
	 *
	 * @param Product $product
	 * @return array{fg_percent:string,pa_fg:string,cost_price:string}
	 */
	private static function buildData(Product $product): array
	{
		$base = 0.0;
		foreach (self::COST_FIELDS as $field) {
			$current = self::normalizeDecimal(self::getExtrafieldValue($product, $field));
			$base += (float) $current;
		}

		$rawFgPercent = self::getExtrafieldValue($product, self::FG_PERCENT_FIELD);
		$fgPercent = self::normalizeDecimal($rawFgPercent);

		$paFg = self::normalizeDecimal($base * ((float) $fgPercent) / 100);
		$costPrice = self::normalizeDecimal($base + (float) $paFg);

		return array(
			'fg_percent' => $fgPercent,
			'pa_fg' => $paFg,
			'cost_price' => $costPrice,
		);
	}

	/**
	 * Fetch raw extrafield value from product.
	 *
	 * @param Product $product
	 * @param string  $field
	 * @return string|null
	 */
	private static function getExtrafieldValue(Product $product, string $field): ?string
	{
		$key = self::EXTRA_PREFIX.$field;
		return ($product->array_options[$key] ?? null);
	}

	/**
	 * Normalize numeric input to a 4-decimal string.
	 *
	 * @param string|float|int|null $value
	 * @return string
	 */
	public static function normalizeDecimal($value): string
	{
		if ($value === '' || $value === null) {
			$value = 0;
		}
		$sanitized = price2num((string) $value, 4);

		if ($sanitized === '' || $sanitized === null) {
			$sanitized = '0';
		}

		return number_format((float) $sanitized, 4, '.', '');
	}

	/**
	 * Compare two decimal values with 4 decimal precision.
	 *
	 * @param string|null $current
	 * @param string      $target
	 * @return bool
	 */
	private static function valueDiffers(?string $current, string $target): bool
	{
		if ($current === null) {
			return true;
		}

		return self::normalizeDecimal($current) !== self::normalizeDecimal($target);
	}

	/**
	 * Update a product numeric field if needed.
	 *
	 * @param Product $product
	 * @param string  $field
	 * @param string  $value
	 * @param User    $user
	 * @return int    1 if updated, 0 if unchanged, -1 on error
	 */
	private static function updateProductPriceField(Product $product, string $field, string $value, User $user): int
	{
		$current = property_exists($product, $field) ? $product->{$field} : null;
		if (!self::valueDiffers(self::normalizeDecimal($current), $value)) {
			return 0;
		}

		$result = $product->setValueFrom($field, $value, 'product', $product->id, 'text', 'rowid', $user, '');
		if ($result < 0) {
			return -1;
		}

		$product->{$field} = (float) $value;
		return 1;
	}
}
