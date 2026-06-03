<?php
/* Copyright (C) 2026 ATM Consulting
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceTier.php
 * \ingroup clichaumeil
 * \brief   Value object: one normalised price threshold of a supplier product.
 */

declare(strict_types=1);

/**
 * Immutable normalised price tier (one threshold returned by the supplier API).
 *
 * The unit price is already normalised (per single unit, HT) by the connector.
 */
final class SupplierPriceTier
{
	/**
	 * @param float  $quantity            Threshold quantity (Dolibarr line quantity).
	 * @param string $unitLabel           Dolibarr packaging unit label (best-effort, may be empty).
	 * @param float  $normalizedUnitPrice Normalised HT unit price (personalUnitPrice / personalPriceQty).
	 */
	public function __construct(
		public readonly float $quantity,
		public readonly string $unitLabel,
		public readonly float $normalizedUnitPrice
	) {
	}
}
