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
require_once __DIR__ . '/../ValueObject/SupplierProductRequest.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceTier.php';
require_once __DIR__ . '/../ValueObject/SupplierProductPriceGrid.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncIssue.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncReport.php';

/**
 * Orchestrates the full synchronisation run for one supplier.
 *
 * For each supplier product, the connector returns the full price grid (all
 * thresholds). The service reconciles each grid against the existing Dolibarr
 * lines: update changed prices, create missing tiers, close tiers that vanished.
 * Writes go through ProductFournisseur::update_buyprice() (preserves the price
 * log); the activation status is toggled with a direct SQL update via the
 * repository. Tier creation and closure-on-absence only happen when the
 * connector advertises authoritative grids (supportsTierDiscovery() === true).
 */
final class SupplierPriceSyncService
{
	/** @var DoliDB Database handler. */
	private DoliDB $db;

	/** @var SupplierPriceRepository Repository. */
	private SupplierPriceRepository $repository;

	/** @var bool When true, the run computes counters but performs no write. */
	private bool $dryRun = false;

	/** @var int Maximum number of lines this run is allowed to close (closure guard). */
	private int $maxClosures = PHP_INT_MAX;

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
	 * @param SupplierConfigInterface         $config    Supplier configuration.
	 * @param SupplierPriceConnectorInterface $connector Supplier connector.
	 * @param User                            $user      Execution user.
	 * @param bool                            $dryRun    When true, compute counters but write nothing.
	 * @return SupplierPriceSyncReport
	 * @throws Exception When products or lines cannot be loaded.
	 */
	public function run(
		SupplierConfigInterface $config,
		SupplierPriceConnectorInterface $connector,
		User $user,
		bool $dryRun = false
	): SupplierPriceSyncReport {
		$report = new SupplierPriceSyncReport($config->getCode());
		$thirdpartyId = $config->getSupplierThirdpartyId();
		$this->dryRun = $dryRun;
		$startedAt = dol_now();

		$existingLines = $this->repository->fetchCandidatesForSupplier($thirdpartyId);
		$report->scanned = count($existingLines);
		$this->maxClosures = $this->computeMaxClosures($report->scanned);

		dol_syslog(
			'SupplierPriceSyncService::run START supplier=' . $config->getCode()
			. ' dryRun=' . ($dryRun ? '1' : '0') . ' scanned=' . $report->scanned
			. ' maxClosures=' . $this->maxClosures . ' at=' . dol_print_date($startedAt, 'standard'),
			LOG_INFO
		);

		$linesByRef = array();
		foreach ($existingLines as $line) {
			$linesByRef[$line->supplierRef][] = $line;
		}

		$products = $this->repository->fetchProductsForSupplier($thirdpartyId);
		$discovery = $connector->supportsTierDiscovery();
		$batchSize = max(1, $connector->getRecommendedBatchSize());

		foreach (array_chunk($products, $batchSize) as $chunk) {
			$productByRef = array();
			foreach ($chunk as $product) {
				$productByRef[$product->supplierRef] = $product;
			}

			$fetch = $connector->fetchPriceGrids($chunk);

			foreach ($fetch->issues as $issue) {
				$report->addIssue($issue);
				if (!$issue->isError()) {
					$report->incrementSkipped();
				}
			}

			if ($fetch->fatalError) {
				dol_syslog('SupplierPriceSyncService::run stopped on fatal API error', LOG_ERR);

				return $report;
			}

			foreach ($fetch->grids as $grid) {
				$product = $productByRef[$grid->supplierRef] ?? null;
				if ($product === null) {
					continue;
				}
				$existingForRef = $linesByRef[$grid->supplierRef] ?? array();
				$this->reconcileGrid($grid, $product, $existingForRef, $discovery, $user, $report);
			}
		}

		dol_syslog(
			'SupplierPriceSyncService::run END ' . $report->summaryLine()
			. ' durationsec=' . (dol_now() - $startedAt),
			LOG_INFO
		);

		return $report;
	}

	/**
	 * Compute the maximum number of lines this run may close (closure guard).
	 *
	 * @param int $scanned Number of scanned lines.
	 * @return int Cap (>= ratio 100 disables the guard).
	 */
	private function computeMaxClosures(int $scanned): int
	{
		$ratio = getDolGlobalInt(
			SupplierPriceSyncConstants::CONST_MAX_CLOSURE_RATIO,
			SupplierPriceSyncConstants::DEFAULT_MAX_CLOSURE_RATIO
		);
		if ($ratio <= 0 || $ratio >= 100) {
			return $scanned;
		}

		return (int) ceil($scanned * $ratio / 100);
	}

	/**
	 * Reconcile one product grid against its existing Dolibarr lines.
	 *
	 * @param SupplierProductPriceGrid $grid           Product price grid.
	 * @param SupplierProductRequest   $product        Product request.
	 * @param SupplierPriceCandidate[] $existingForRef Existing lines for this ref.
	 * @param bool                     $discovery      Whether grids are authoritative.
	 * @param User                     $user           Execution user.
	 * @param SupplierPriceSyncReport  $report         Run report.
	 * @return void
	 */
	private function reconcileGrid(
		SupplierProductPriceGrid $grid,
		SupplierProductRequest $product,
		array $existingForRef,
		bool $discovery,
		User $user,
		SupplierPriceSyncReport $report
	): void {
		if ($grid->state === SupplierProductPriceGrid::STATE_ERROR) {
			// Functional error already reported as an issue: no mutation.
			return;
		}

		if ($grid->state === SupplierProductPriceGrid::STATE_ABSENT) {
			$this->closeLines($existingForRef, $report);

			return;
		}

		$matched = array();
		foreach ($grid->tiers as $tier) {
			$report->incrementRequested();
			$candidate = $this->matchByQuantity($existingForRef, $tier->quantity);
			if ($candidate !== null) {
				$matched[$candidate->supplierPriceId] = true;
				$this->applyTier($candidate, $tier, $user, $report);
				continue;
			}
			if ($discovery) {
				$this->createTier($product, $tier, $user, $report);
			}
		}

		if ($discovery) {
			$absentLines = array();
			foreach ($existingForRef as $line) {
				if (!isset($matched[$line->supplierPriceId])) {
					$absentLines[] = $line;
				}
			}
			$this->closeLines($absentLines, $report);
		}
	}

	/**
	 * Apply a tier to an existing matching line (update price, reactivate).
	 *
	 * @param SupplierPriceCandidate $candidate Existing line.
	 * @param SupplierPriceTier      $tier      Matching API tier.
	 * @param User                   $user      Execution user.
	 * @param SupplierPriceSyncReport $report   Run report.
	 * @return void
	 */
	private function applyTier(
		SupplierPriceCandidate $candidate,
		SupplierPriceTier $tier,
		User $user,
		SupplierPriceSyncReport $report
	): void {
		$this->warnIfUnitDiverges($candidate, $tier, $report);

		if ($this->priceDiffers($candidate->currentUnitPrice, $tier->normalizedUnitPrice)) {
			if (!$this->dryRun && !$this->updateBuyPrice($candidate, $tier->normalizedUnitPrice, $user)) {
				$report->addIssue($this->updateFailedIssue($candidate->supplierRef, $candidate->productRef, $candidate->quantity));

				return;
			}
			$report->incrementUpdated();
		} else {
			$report->incrementUnchanged();
		}

		if ($candidate->currentStatus !== SupplierPriceSyncConstants::STATUS_ACTIVE) {
			if ($this->dryRun || $this->repository->activate($candidate->supplierPriceId)) {
				$report->incrementReactivated();
			} else {
				$report->addIssue($this->updateFailedIssue($candidate->supplierRef, $candidate->productRef, $candidate->quantity));
			}
		}
	}

	/**
	 * Warn (without blocking) when the Dolibarr line packaging unit diverges from the
	 * API threshold unit. Both are Dolibarr labels, so the comparison stays generic.
	 *
	 * @param SupplierPriceCandidate $candidate Existing line.
	 * @param SupplierPriceTier      $tier      Matching API tier.
	 * @param SupplierPriceSyncReport $report   Run report.
	 * @return void
	 */
	private function warnIfUnitDiverges(
		SupplierPriceCandidate $candidate,
		SupplierPriceTier $tier,
		SupplierPriceSyncReport $report
	): void {
		if ($candidate->packagingUnit === '' || $tier->unitLabel === '') {
			return;
		}
		if ($this->normalizeUnitLabel($candidate->packagingUnit) === $this->normalizeUnitLabel($tier->unitLabel)) {
			return;
		}

		$report->addIssue(new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_WARNING,
			SupplierPriceSyncConstants::ISSUE_UNIT_MISMATCH,
			$candidate->packagingUnit . ' / ' . $tier->unitLabel,
			$candidate->supplierRef,
			$candidate->productRef,
			$candidate->quantity
		));
	}

	/**
	 * Normalise a packaging label for comparison (trim, lowercase, strip accents).
	 *
	 * @param string $value Raw label.
	 * @return string
	 */
	private function normalizeUnitLabel(string $value): string
	{
		$value = mb_strtolower(trim($value));

		return strtr(
			$value,
			array(
				'à' => 'a', 'â' => 'a', 'ä' => 'a',
				'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
				'î' => 'i', 'ï' => 'i',
				'ô' => 'o', 'ö' => 'o',
				'ù' => 'u', 'û' => 'u', 'ü' => 'u',
				'ç' => 'c',
			)
		);
	}

	/**
	 * Create a new supplier price line for a discovered tier.
	 *
	 * @param SupplierProductRequest  $product Product request.
	 * @param SupplierPriceTier       $tier    Discovered tier.
	 * @param User                    $user    Execution user.
	 * @param SupplierPriceSyncReport $report  Run report.
	 * @return void
	 */
	private function createTier(
		SupplierProductRequest $product,
		SupplierPriceTier $tier,
		User $user,
		SupplierPriceSyncReport $report
	): void {
		if ($this->dryRun) {
			$report->incrementCreated();

			return;
		}

		$productFournisseur = new ProductFournisseur($this->db);
		$productFournisseur->id = $product->productId;

		$this->db->begin();

		$created = $productFournisseur->add_fournisseur($user, $product->supplierId, $product->supplierRef, $tier->quantity);
		if ($created < 0) {
			$this->db->rollback();
			dol_syslog('SupplierPriceSyncService::createTier add_fournisseur failed (' . $created . ') ref=' . $product->supplierRef, LOG_ERR);
			$report->addIssue($this->updateFailedIssue($product->supplierRef, $product->productRef, $tier->quantity));

			return;
		}

		$newLineId = (int) $productFournisseur->product_fourn_price_id;
		$candidate = new SupplierPriceCandidate(
			$newLineId,
			$product->productId,
			$product->productRef,
			$product->supplierId,
			$product->supplierRef,
			$tier->quantity,
			0.0,
			SupplierPriceSyncConstants::STATUS_ACTIVE
		);

		if (!$this->updateBuyPrice($candidate, $tier->normalizedUnitPrice, $user)) {
			$this->db->rollback();
			$report->addIssue($this->updateFailedIssue($product->supplierRef, $product->productRef, $tier->quantity));

			return;
		}

		if ($tier->unitLabel !== '') {
			$this->repository->setPackagingUnit($newLineId, $tier->unitLabel);
		}

		$this->db->commit();

		// add_fournisseur returns 1 when it created the line, 0 when the (ref, qty)
		// row already existed: in the latter case it is an update, not a creation.
		if ($created === 1) {
			$report->incrementCreated();
		} else {
			$report->incrementUpdated();
		}
	}

	/**
	 * Close (deactivate) every active line of the given set.
	 *
	 * @param SupplierPriceCandidate[] $lines  Lines to close.
	 * @param SupplierPriceSyncReport  $report Run report.
	 * @return void
	 */
	private function closeLines(array $lines, SupplierPriceSyncReport $report): void
	{
		foreach ($lines as $line) {
			if ($line->currentStatus !== SupplierPriceSyncConstants::STATUS_ACTIVE) {
				continue;
			}

			// Closure guard: never close more than the allowed share of scanned lines
			// in a single run (protects against a partial/erroneous API response).
			if ($report->closed >= $this->maxClosures) {
				$report->addIssue(new SupplierPriceSyncIssue(
					SupplierPriceSyncIssue::SEVERITY_ERROR,
					SupplierPriceSyncConstants::ISSUE_CLOSURE_THRESHOLD,
					(string) $this->maxClosures,
					$line->supplierRef,
					$line->productRef,
					$line->quantity
				));
				continue;
			}

			if ($this->dryRun || $this->repository->deactivate($line->supplierPriceId)) {
				$report->incrementClosed();
			} else {
				$report->addIssue($this->updateFailedIssue($line->supplierRef, $line->productRef, $line->quantity));
			}
		}
	}

	/**
	 * Find an existing line matching a tier quantity.
	 *
	 * @param SupplierPriceCandidate[] $existingForRef Existing lines for the ref.
	 * @param float                    $quantity       Tier quantity.
	 * @return SupplierPriceCandidate|null
	 */
	private function matchByQuantity(array $existingForRef, float $quantity): ?SupplierPriceCandidate
	{
		foreach ($existingForRef as $line) {
			if (abs($line->quantity - $quantity) <= SupplierPriceSyncConstants::QUANTITY_EPSILON) {
				return $line;
			}
		}

		return null;
	}

	/**
	 * Update the buy price of a line, preserving its VAT and discounts.
	 *
	 * @param SupplierPriceCandidate $candidate       Line to update.
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
	 * Build a Dolibarr write-failure issue.
	 *
	 * @param string $supplierRef Supplier reference.
	 * @param string $productRef  Dolibarr product reference.
	 * @param float  $quantity    Quantity concerned.
	 * @return SupplierPriceSyncIssue
	 */
	private function updateFailedIssue(string $supplierRef, string $productRef, float $quantity): SupplierPriceSyncIssue
	{
		return new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_DOLIBARR_UPDATE_FAILED,
			'',
			$supplierRef,
			$productRef,
			$quantity
		);
	}
}
