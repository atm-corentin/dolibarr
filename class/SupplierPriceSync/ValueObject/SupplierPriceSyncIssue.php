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
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceSyncIssue.php
 * \ingroup clichaumeil
 * \brief   Value object describing a single synchronisation issue.
 */

declare(strict_types=1);

/**
 * Immutable description of one issue raised during a synchronisation run.
 */
final class SupplierPriceSyncIssue
{
	/** @var string Blocking issue (marks the run as failed). */
	public const SEVERITY_ERROR = 'error';

	/** @var string Non-blocking issue (line skipped, run still succeeds). */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * @param string $severity    One of the SEVERITY_* constants.
	 * @param string $code        Internal issue code (SupplierPriceSyncConstants::ISSUE_*).
	 * @param string $message     Human-readable, translated message.
	 * @param string $supplierRef Supplier reference concerned (optional).
	 * @param string $productRef  Dolibarr product reference concerned (optional).
	 * @param float  $quantity    Quantity concerned (optional).
	 */
	public function __construct(
		public readonly string $severity,
		public readonly string $code,
		public readonly string $message,
		public readonly string $supplierRef = '',
		public readonly string $productRef = '',
		public readonly float $quantity = 0.0
	) {
	}

	/**
	 * Tell whether the issue is blocking.
	 *
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->severity === self::SEVERITY_ERROR;
	}
}
