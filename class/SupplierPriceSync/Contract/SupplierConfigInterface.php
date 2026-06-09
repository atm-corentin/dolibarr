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
 * \file    class/SupplierPriceSync/Contract/SupplierConfigInterface.php
 * \ingroup clichaumeil
 * \brief   Generic supplier configuration contract (no connector-specific dependency).
 */

declare(strict_types=1);

/**
 * Minimal generic supplier configuration exposed to the sync service.
 */
interface SupplierConfigInterface
{
	/**
	 * Return the supplier code (e.g. "ANTALIS").
	 *
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * Return the human-readable supplier label.
	 *
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * Return the Dolibarr third party id whose prices must be synchronised.
	 *
	 * @return int
	 */
	public function getSupplierThirdpartyId(): int;
}
