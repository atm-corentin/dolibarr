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
 * \file    class/SupplierPriceSync/ValueObject/SupplierProductRequest.php
 * \ingroup clichaumeil
 * \brief   Value object: one supplier product to query (price grid unit of work).
 */

declare(strict_types=1);

/**
 * Immutable request for a single supplier product (one distinct ref_fourn).
 *
 * The connector returns the full price grid for each request.
 */
final class SupplierProductRequest
{
	/**
	 * @param int    $productId   Dolibarr product id (fk_product).
	 * @param int    $supplierId  Supplier third party id (fk_soc).
	 * @param string $supplierRef Supplier reference (ref_fourn = ANTALIS productID).
	 * @param string $productRef  Dolibarr product reference (for reporting).
	 */
	public function __construct(
		public readonly int $productId,
		public readonly int $supplierId,
		public readonly string $supplierRef,
		public readonly string $productRef
	) {
	}
}
