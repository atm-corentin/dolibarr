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
 * \file    class/SupplierPriceSync/Contract/SupplierPriceConnectorInterface.php
 * \ingroup clichaumeil
 * \brief   Generic supplier price connector contract (transport-agnostic).
 */

declare(strict_types=1);

require_once __DIR__ . '/../ValueObject/SupplierPriceGridFetchResult.php';

/**
 * Contract every supplier price connector must implement.
 *
 * The interface is transport-agnostic (SOAP, REST...). Each request targets one
 * supplier product and the connector returns its full price grid (all tiers).
 * supportsTierDiscovery() tells the service whether the returned grid is
 * authoritative (true => create missing tiers and close absent ones) or only
 * covers already-known lines (false => touch known lines only).
 */
interface SupplierPriceConnectorInterface
{
	/**
	 * Return the supplier code handled by this connector.
	 *
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * Return the recommended number of products per remote call.
	 *
	 * @return int
	 */
	public function getRecommendedBatchSize(): int;

	/**
	 * Tell whether the returned grids are authoritative (enable tier creation/closure).
	 *
	 * @return bool
	 */
	public function supportsTierDiscovery(): bool;

	/**
	 * Fetch the full price grids for the given product requests.
	 *
	 * @param SupplierProductRequest[] $products Product requests of a single chunk.
	 * @return SupplierPriceGridFetchResult
	 */
	public function fetchPriceGrids(array $products): SupplierPriceGridFetchResult;
}
