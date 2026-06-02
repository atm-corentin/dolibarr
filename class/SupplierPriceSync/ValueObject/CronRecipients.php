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
 * \file    class/SupplierPriceSync/ValueObject/CronRecipients.php
 * \ingroup clichaumeil
 * \brief   Value object parsing and validating the cron error-report recipients.
 */

declare(strict_types=1);

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';

/**
 * Immutable list of validated recipient email addresses for the cron report.
 *
 * An empty list is a valid state (no mail will be sent). A non-empty list with
 * at least one invalid address is a configuration error.
 */
final class CronRecipients
{
	/** @var string[] Validated, de-duplicated email addresses. */
	private array $emails;

	/**
	 * @param string[] $emails Already validated email addresses.
	 */
	private function __construct(array $emails)
	{
		$this->emails = $emails;
	}

	/**
	 * Build the recipients list from the raw cron parameter.
	 *
	 * @param string $raw Raw parameter (addresses separated by ';').
	 * @return self
	 * @throws InvalidArgumentException When a non-empty address is not a valid email.
	 */
	public static function fromRaw(string $raw): self
	{
		if (trim($raw) === '') {
			return new self(array());
		}

		$parts = array_filter(
			array_map('trim', explode(SupplierPriceSyncConstants::MAIL_SEPARATOR, $raw)),
			static function (string $email): bool {
				return $email !== '';
			}
		);
		$parts = array_values(array_unique($parts));

		foreach ($parts as $email) {
			if (!isValidEmail($email)) {
				throw new InvalidArgumentException('Invalid cron recipient: ' . $email);
			}
		}

		return new self($parts);
	}

	/**
	 * Return the validated email addresses.
	 *
	 * @return string[]
	 */
	public function all(): array
	{
		return $this->emails;
	}

	/**
	 * Tell whether no recipient is configured.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->emails === array();
	}
}
