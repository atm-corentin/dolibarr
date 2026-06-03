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
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceGridFetchResult.php
 * \ingroup clichaumeil
 * \brief   Value object encapsulating the outcome of a connector grid fetch.
 */

declare(strict_types=1);

/**
 * Immutable outcome of a connector fetch for one chunk of product requests.
 *
 * A per-product functional error must NOT set fatalError: only an unreachable
 * API or a back-end-down response does.
 */
final class SupplierPriceGridFetchResult
{
	/**
	 * @param SupplierProductPriceGrid[] $grids      Normalised product grids.
	 * @param SupplierPriceSyncIssue[]   $issues     Issues raised during the fetch.
	 * @param bool                       $fatalError True if the whole run must stop.
	 */
	public function __construct(
		public readonly array $grids,
		public readonly array $issues,
		public readonly bool $fatalError
	) {
	}
}
