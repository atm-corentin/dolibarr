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
 * \file    class/SupplierPriceSync/Service/SupplierPriceSyncService.php
 * \ingroup clichaumeil
 * \brief   Orchestrates a supplier price synchronisation run.
 */

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../Contract/SupplierConfigInterface.php';
require_once __DIR__ . '/../Contract/SupplierPriceConnectorInterface.php';
require_once __DIR__ . '/../Repository/SupplierPriceRepository.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceCandidate.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceLineResult.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncIssue.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncReport.php';

/**
 * Orchestrates the full synchronisation run for one supplier.
 *
 * Scope: only known Dolibarr lines are synchronised (no tier creation). Writes go
 * through ProductFournisseur::update_buyprice() to preserve the price log; the
 * activation status is toggled with a direct SQL update via the repository.
 */
final class SupplierPriceSyncService
{
	/** @var DoliDB Database handler. */
	private DoliDB $db;

	/** @var SupplierPriceRepository Repository. */
	private SupplierPriceRepository $repository;

	/**
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
		$this->repository = new SupplierPriceRepository($db);
	}

	/**
	 * Run the synchronisation for the given supplier and connector.
	 *
	 * @param SupplierConfigInterface          $config    Supplier configuration.
	 * @param SupplierPriceConnectorInterface $connector Supplier connector.
	 * @param User                             $user      Execution user.
	 * @return SupplierPriceSyncReport
	 * @throws Exception When candidates cannot be loaded.
	 */
	public function run(
		SupplierConfigInterface $config,
		SupplierPriceConnectorInterface $connector,
		User $user
	): SupplierPriceSyncReport {
		$report = new SupplierPriceSyncReport($config->getCode());

		$candidates = $this->repository->fetchCandidatesForSupplier($config->getSupplierThirdpartyId());
		$report->scanned = count($candidates);

		$indexed = array();
		foreach ($candidates as $candidate) {
			$indexed[$candidate->key()] = $candidate;
		}

		$batchSize = max(1, $connector->getRecommendedBatchSize());
		$chunks = array_chunk($candidates, $batchSize);

		foreach ($chunks as $chunk) {
			$fetch = $connector->fetchPrices($chunk);

			foreach ($fetch->issues as $issue) {
				$report->addIssue($issue);
				if ($issue->code === SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT
					&& $issue->severity === SupplierPriceSyncIssue::SEVERITY_WARNING) {
					$report->incrementSkipped();
				}
			}

			if ($fetch->fatalError) {
				dol_syslog('SupplierPriceSyncService::run stopped on fatal API error', LOG_ERR);

				return $report;
			}

			foreach ($fetch->results as $result) {
				$report->incrementRequested();
				$candidate = $indexed[$result->key()] ?? null;
				if ($candidate === null) {
					continue;
				}
				$this->applyResult($result, $candidate, $config, $user, $report);
			}
		}

		return $report;
	}

	/**
	 * Apply one normalised line result to the matching candidate.
	 *
	 * @param SupplierPriceLineResult $result    Normalised line result.
	 * @param SupplierPriceCandidate  $candidate Matching Dolibarr candidate.
	 * @param SupplierConfigInterface $config    Supplier configuration.
	 * @param User                    $user      Execution user.
	 * @param SupplierPriceSyncReport $report    Run report.
	 * @return void
	 */
	private function applyResult(
		SupplierPriceLineResult $result,
		SupplierPriceCandidate $candidate,
		SupplierConfigInterface $config,
		User $user,
		SupplierPriceSyncReport $report
	): void {
		if ($result->state === SupplierPriceLineResult::STATE_CLOSE) {
			if ($candidate->currentStatus === SupplierPriceSyncConstants::STATUS_ACTIVE) {
				if ($this->repository->deactivate($candidate->supplierPriceId)) {
					$report->incrementClosed();
				} else {
					$report->addIssue($this->updateFailedIssue($candidate));
				}
			}

			return;
		}

		if ($result->state !== SupplierPriceLineResult::STATE_SUCCESS) {
			// Error lines: issue already reported by the connector, no mutation.
			return;
		}

		if ($this->priceDiffers($candidate->currentUnitPrice, $result->normalizedUnitPrice)) {
			if (!$this->updateBuyPrice($candidate, $result->normalizedUnitPrice, $user)) {
				$report->addIssue($this->updateFailedIssue($candidate));

				return;
			}
			$report->incrementUpdated();
		} else {
			$report->incrementUnchanged();
		}

		if ($candidate->currentStatus !== SupplierPriceSyncConstants::STATUS_ACTIVE) {
			if ($this->repository->activate($candidate->supplierPriceId)) {
				$report->incrementReactivated();
			} else {
				$report->addIssue($this->updateFailedIssue($candidate));
			}
		}
	}

	/**
	 * Update the buy price of an existing line, preserving its VAT and discounts.
	 *
	 * @param SupplierPriceCandidate $candidate       Candidate to update.
	 * @param float                  $normalizedPrice Normalised HT unit price.
	 * @param User                   $user            Execution user.
	 * @return bool True on success.
	 */
	private function updateBuyPrice(SupplierPriceCandidate $candidate, float $normalizedPrice, User $user): bool
	{
		$productFournisseur = new ProductFournisseur($this->db);
		$fetch = $productFournisseur->fetch_product_fournisseur_price($candidate->supplierPriceId);
		if ($fetch == 0) {
			dol_syslog('SupplierPriceSyncService::updateBuyPrice line not found (0) id=' . $candidate->supplierPriceId, LOG_WARNING);

			return false;
		}
		if ($fetch < 0) {
			dol_syslog('SupplierPriceSyncService::updateBuyPrice SQL error (-1) fetching line id=' . $candidate->supplierPriceId . ' ' . $productFournisseur->error, LOG_ERR);

			return false;
		}

		$productFournisseur->id = $candidate->productId;
		$totalHtForQuantity = $normalizedPrice * $candidate->quantity;

		$result = $productFournisseur->update_buyprice(
			$candidate->quantity,
			$totalHtForQuantity,
			$user,
			'HT',
			$candidate->supplierId,
			0,
			$candidate->supplierRef,
			(float) $productFournisseur->fourn_tva_tx,
			(float) $productFournisseur->fourn_charges,
			(float) $productFournisseur->fourn_remise_percent,
			(float) $productFournisseur->fourn_remise
		);

		if ($result <= 0) {
			dol_syslog('SupplierPriceSyncService::updateBuyPrice update_buyprice failed for line ' . $candidate->supplierPriceId, LOG_ERR);

			return false;
		}

		return true;
	}

	/**
	 * Tell whether two unit prices differ beyond the tolerance.
	 *
	 * @param float $current   Current unit price.
	 * @param float $candidate New unit price.
	 * @return bool
	 */
	private function priceDiffers(float $current, float $candidate): bool
	{
		return abs($current - $candidate) > SupplierPriceSyncConstants::PRICE_EPSILON;
	}

	/**
	 * Build a Dolibarr update failure issue for a candidate.
	 *
	 * @param SupplierPriceCandidate $candidate Candidate concerned.
	 * @return SupplierPriceSyncIssue
	 */
	private function updateFailedIssue(SupplierPriceCandidate $candidate): SupplierPriceSyncIssue
	{
		return new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_DOLIBARR_UPDATE_FAILED,
			'',
			$candidate->supplierRef,
			$candidate->productRef,
			$candidate->quantity
		);
	}
}
