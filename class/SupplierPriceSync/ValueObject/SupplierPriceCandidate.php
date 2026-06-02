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
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceCandidate.php
 * \ingroup clichaumeil
 * \brief   Value object representing a Dolibarr supplier price line to synchronise.
 */

declare(strict_types=1);

/**
 * Immutable source line (one product_fournisseur_price row) to synchronise.
 *
 * All decimals are already normalised at hydration time.
 */
final class SupplierPriceCandidate
{
	/**
	 * @param int    $supplierPriceId  product_fournisseur_price.rowid.
	 * @param int    $productId        Product id.
	 * @param string $productRef       Dolibarr product reference.
	 * @param int    $supplierId       Supplier third party id (fk_soc).
	 * @param string $supplierRef      Supplier reference (ref_fourn).
	 * @param float  $quantity         Quantity of the price line.
	 * @param float  $currentUnitPrice Current stored unit price (HT).
	 * @param int    $currentStatus    Current status (1 active, 0 inactive).
	 * @param string $orderUnitSource  Source order unit label (extrafield conditionnement_unite_de_prix).
	 */
	public function __construct(
		public readonly int $supplierPriceId,
		public readonly int $productId,
		public readonly string $productRef,
		public readonly int $supplierId,
		public readonly string $supplierRef,
		public readonly float $quantity,
		public readonly float $currentUnitPrice,
		public readonly int $currentStatus,
		public readonly string $orderUnitSource
	) {
	}

	/**
	 * Business key used to match an API line with this candidate.
	 *
	 * @return string
	 */
	public function key(): string
	{
		return $this->supplierRef . '|' . $this->quantity;
	}
}
