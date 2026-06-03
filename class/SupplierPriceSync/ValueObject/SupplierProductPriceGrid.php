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
 * \file    class/SupplierPriceSync/ValueObject/SupplierProductPriceGrid.php
 * \ingroup clichaumeil
 * \brief   Value object: the full price grid of one supplier product.
 */

declare(strict_types=1);

require_once __DIR__ . '/SupplierPriceTier.php';

/**
 * Immutable price grid returned for one supplier product.
 *
 * The state drives the service reconciliation:
 *  - FOUND  : tiers are authoritative (update/create/close-missing).
 *  - ABSENT : product no longer sold/available -> close every existing line.
 *  - ERROR  : functional error already reported -> no mutation.
 */
final class SupplierProductPriceGrid
{
	/** @var string Product found, tiers are authoritative. */
	public const STATE_FOUND = 'found';

	/** @var string Product unavailable/not sold -> close existing lines. */
	public const STATE_ABSENT = 'absent';

	/** @var string Functional error on the product -> no mutation. */
	public const STATE_ERROR = 'error';

	/**
	 * @param string             $supplierRef Supplier reference (ref_fourn).
	 * @param string             $state       One of the STATE_* constants.
	 * @param SupplierPriceTier[] $tiers       Normalised tiers (only for STATE_FOUND).
	 */
	private function __construct(
		public readonly string $supplierRef,
		public readonly string $state,
		public readonly array $tiers
	) {
	}

	/**
	 * Build a found grid carrying its tiers.
	 *
	 * @param string             $supplierRef Supplier reference.
	 * @param SupplierPriceTier[] $tiers       Normalised tiers.
	 * @return self
	 */
	public static function found(string $supplierRef, array $tiers): self
	{
		return new self($supplierRef, self::STATE_FOUND, $tiers);
	}

	/**
	 * Build an absent grid (product unavailable/not sold).
	 *
	 * @param string $supplierRef Supplier reference.
	 * @return self
	 */
	public static function absent(string $supplierRef): self
	{
		return new self($supplierRef, self::STATE_ABSENT, array());
	}

	/**
	 * Build an error grid (functional error already reported as an issue).
	 *
	 * @param string $supplierRef Supplier reference.
	 * @return self
	 */
	public static function error(string $supplierRef): self
	{
		return new self($supplierRef, self::STATE_ERROR, array());
	}
}
