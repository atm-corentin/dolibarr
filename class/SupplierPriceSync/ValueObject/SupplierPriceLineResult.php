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
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceLineResult.php
 * \ingroup clichaumeil
 * \brief   Value object representing a normalised API line result.
 */

declare(strict_types=1);

/**
 * Immutable normalised result for one requested line.
 *
 * The state drives the service behaviour: success -> update/keep, close -> deactivate,
 * error -> no mutation (issue already reported by the connector).
 */
final class SupplierPriceLineResult
{
	/** @var string Price found, mutation may apply. */
	public const STATE_SUCCESS = 'success';

	/** @var string Product no longer sold/available for this line -> close. */
	public const STATE_CLOSE = 'close';

	/** @var string Functional error on the line -> no mutation. */
	public const STATE_ERROR = 'error';

	/**
	 * @param string $supplierRef         Supplier reference.
	 * @param float  $quantity            Dolibarr targeted quantity (not SOAP priceQty).
	 * @param string $state               One of the STATE_* constants.
	 * @param float  $normalizedUnitPrice Normalised HT unit price (materialPrice / quantity).
	 */
	private function __construct(
		public readonly string $supplierRef,
		public readonly float $quantity,
		public readonly string $state,
		public readonly float $normalizedUnitPrice = 0.0
	) {
	}

	/**
	 * Build a success result.
	 *
	 * @param string $supplierRef         Supplier reference.
	 * @param float  $quantity            Targeted quantity.
	 * @param float  $normalizedUnitPrice Normalised HT unit price.
	 * @return self
	 */
	public static function success(string $supplierRef, float $quantity, float $normalizedUnitPrice): self
	{
		return new self($supplierRef, $quantity, self::STATE_SUCCESS, $normalizedUnitPrice);
	}

	/**
	 * Build a close result.
	 *
	 * @param string $supplierRef Supplier reference.
	 * @param float  $quantity    Targeted quantity.
	 * @return self
	 */
	public static function close(string $supplierRef, float $quantity): self
	{
		return new self($supplierRef, $quantity, self::STATE_CLOSE);
	}

	/**
	 * Build an error result.
	 *
	 * @param string $supplierRef Supplier reference.
	 * @param float  $quantity    Targeted quantity.
	 * @return self
	 */
	public static function error(string $supplierRef, float $quantity): self
	{
		return new self($supplierRef, $quantity, self::STATE_ERROR);
	}

	/**
	 * Business key used to match this result with a candidate.
	 *
	 * @return string
	 */
	public function key(): string
	{
		return $this->supplierRef . '|' . $this->quantity;
	}
}
