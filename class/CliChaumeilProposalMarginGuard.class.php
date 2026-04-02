<?php
/* Copyright (C) 2026  ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

/**
 * Business guard dedicated to proposal margin validation.
 */
class CliChaumeilProposalMarginGuard
{
	/**
	 * @var string Supported business object element.
	 */
	private const OBJECT_ELEMENT = 'propal';

	/**
	 * @var string Translation key used on proposal card.
	 */
	private const UI_BLOCK_MESSAGE_KEY = 'CliChaumeil_PropalMarginValidationBlocked';

	/**
	 * @var string Translation key used for mass validation.
	 */
	private const MASS_BLOCK_MESSAGE_KEY = 'CliChaumeil_PropalMassMarginValidationBlocked';

	/**
	 * @var float Fallback numeric value for empty amounts.
	 */
	private const ZERO_AMOUNT = 0.0;

	/**
	 * Detect whether the proposal contains at least one blocking line.
	 *
	 * @param Propal $proposal Proposal to inspect.
	 * @return bool True when at least one line has a cost price greater than the sale price.
	 * @throws RuntimeException When proposal lines cannot be loaded.
	 */
	public function hasBlockingNegativeMargin(Propal $proposal): bool
	{
		return !empty($this->getBlockingLineIds($proposal));
	}

	/**
	 * Return identifiers of lines blocking the proposal validation.
	 *
	 * @param Propal $proposal Proposal to inspect.
	 * @return int[] List of blocking line identifiers.
	 * @throws RuntimeException When proposal lines cannot be loaded.
	 */
	public function getBlockingLineIds(Propal $proposal): array
	{
		$this->assertSupportedProposal($proposal);
		$this->ensureProposalLinesLoaded($proposal);

		$blockingLineIds = array();

		foreach ($proposal->lines as $line) {
			$lineId = isset($line->id) ? (int) $line->id : 0;
			$salePrice = $this->getNetUnitSalePrice($line);
			$costPrice = $this->normalizeAmount($line->pa_ht ?? null);

			if ($costPrice > $salePrice && $lineId > 0) {
				$blockingLineIds[] = $lineId;
			}
		}

		return $blockingLineIds;
	}

	/**
	 * Compute the net unit sale price used for margin comparison.
	 *
	 * The line total already includes the commercial discount, so it is the
	 * most reliable source when a quantity is available. Fallback to the unit
	 * price discounted by the line rate when the total cannot be used.
	 *
	 * @param CommonObjectLine $line Proposal line to inspect.
	 * @return float
	 */
	private function getNetUnitSalePrice(CommonObjectLine $line): float
	{
		$quantity = $this->normalizeAmount($line->qty ?? null);
		$totalHt = $this->normalizeAmount($line->total_ht ?? null);

		if (abs($quantity) > 0.0) {
			return $totalHt / $quantity;
		}

		$unitPrice = $this->normalizeAmount($line->subprice ?? null);
		$discountPercent = $this->normalizeAmount($line->remise_percent ?? null);

		if ($discountPercent <= 0.0) {
			return $unitPrice;
		}

		return $unitPrice * (1 - ($discountPercent / 100));
	}

	/**
	 * Return the translated blocking message for proposal card validation.
	 *
	 * @param Translate $langs Translation handler.
	 * @return string
	 */
	public function getCardBlockingMessage(Translate $langs): string
	{
		return $langs->transnoentities(self::UI_BLOCK_MESSAGE_KEY);
	}

	/**
	 * Return the translated blocking message for proposal mass validation.
	 *
	 * @param Translate $langs Translation handler.
	 * @return string
	 */
	public function getMassBlockingMessage(Translate $langs): string
	{
		return $langs->transnoentities(self::MASS_BLOCK_MESSAGE_KEY);
	}

	/**
	 * Ensure the proposal is supported by this guard.
	 *
	 * @param Propal $proposal Proposal to validate.
	 * @return void
	 * @throws RuntimeException When proposal is unsupported or incomplete.
	 */
	private function assertSupportedProposal(Propal $proposal): void
	{
		if (empty($proposal->element) || $proposal->element !== self::OBJECT_ELEMENT) {
			throw new RuntimeException('Unsupported proposal element for margin validation guard.');
		}

		if (empty($proposal->id)) {
			throw new RuntimeException('Proposal identifier is required for margin validation guard.');
		}
	}

	/**
	 * Load proposal lines when they are not already available.
	 *
	 * @param Propal $proposal Proposal to hydrate.
	 * @return void
	 * @throws RuntimeException When proposal lines cannot be loaded.
	 */
	private function ensureProposalLinesLoaded(Propal $proposal): void
	{
		if (!empty($proposal->lines)) {
			return;
		}

		$result = $proposal->fetch_lines();
		if ($result < 0) {
			$message = !empty($proposal->error) ? $proposal->error : 'Failed to load proposal lines for margin validation guard.';
			throw new RuntimeException($message);
		}
	}

	/**
	 * Normalize an amount to a float compatible with Dolibarr price helpers.
	 *
	 * @param mixed $value Raw amount value.
	 * @return float
	 */
	private function normalizeAmount(mixed $value): float
	{
		if ($value === null || $value === '') {
			return self::ZERO_AMOUNT;
		}

		return (float) price2num((string) $value);
	}
}
