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
		$report->dryRun = $dryRun;
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

		// Test knob: cap the number of products processed so a dry-run does not take hours.
		// 0 = whole catalogue. Logged loudly so it is never silently left on in production.
		$productLimit = getDolGlobalInt(SupplierPriceSyncConstants::CONST_PRODUCT_LIMIT);
		if ($productLimit > 0 && count($products) > $productLimit) {
			$products = array_slice($products, 0, $productLimit);
			dol_syslog(
				'SupplierPriceSyncService::run PRODUCT LIMIT active (test mode): only '
				. $productLimit . ' products processed',
				LOG_WARNING
			);
		}

		$this->warnOnSharedReferences($products);
		$discovery = $connector->supportsTierDiscovery();
		$batchSize = max(1, $connector->getRecommendedBatchSize());

		$consecutiveFailedBatches = 0;
		$batches = array_chunk($products, $batchSize);
		$totalBatches = count($batches);
		$batchNr = 0;
		dol_syslog('SupplierPriceSyncService::run total batches=' . $totalBatches, LOG_INFO);

		foreach ($batches as $chunk) {
			$batchNr++;
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
				$consecutiveFailedBatches++;
				dol_syslog(
					'SupplierPriceSyncService::run batch failed, consecutive=' . $consecutiveFailedBatches,
					LOG_WARNING
				);
				if ($consecutiveFailedBatches >= SupplierPriceSyncConstants::BATCH_FAILURE_CIRCUIT_BREAKER) {
					dol_syslog(
						'SupplierPriceSyncService::run circuit breaker tripped after '
						. $consecutiveFailedBatches . ' consecutive failed batches, aborting run',
						LOG_ERR
					);

					return $report;
				}
				// Skip this batch; its products will be retried on the next run.
				continue;
			}
			$consecutiveFailedBatches = 0;

			if ($batchNr % SupplierPriceSyncConstants::HEARTBEAT_EVERY_BATCHES === 0) {
				dol_syslog(
					sprintf(
						'SupplierPriceSyncService::run heartbeat batch %d/%d updated=%d created=%d closed=%d warnings=%d',
						$batchNr,
						$totalBatches,
						$report->updated,
						$report->created,
						$report->closed,
						$report->countWarnings()
					),
					LOG_INFO
				);
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
	 * Log a warning for any supplier reference shared by several Dolibarr products.
	 *
	 * Such a reference is reconciled per product (see reconcileGrid scoping); this
	 * surfaces a data oddity that would otherwise sync only one of the products.
	 *
	 * @param SupplierProductRequest[] $products Product requests.
	 * @return void
	 */
	private function warnOnSharedReferences(array $products): void
	{
		$byRef = array();
		foreach ($products as $product) {
			$byRef[$product->supplierRef][$product->productId] = true;
		}
		foreach ($byRef as $ref => $productIds) {
			if (count($productIds) > 1) {
				dol_syslog(
					'SupplierPriceSyncService::run supplier ref shared by ' . count($productIds)
					. ' products, reconciled per product: ' . $ref,
					LOG_WARNING
				);
			}
		}
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
		// Defensive scoping: only this product's lines may be touched by its grid.
		// Guarantees that a ref_fourn shared by several Dolibarr products never lets
		// one product's grid update or close another product's lines.
		$existingForRef = array_values(array_filter(
			$existingForRef,
			static function (SupplierPriceCandidate $line) use ($product): bool {
				return $line->productId === $product->productId;
			}
		));

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
			$candidate = $this->matchLine($existingForRef, $tier);
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

			return;
		}

		// Update-only mode: surface a line whose unit was not returned by the API (no
		// matching price unit). Fail-safe: the line is left untouched rather than
		// rewritten with a price expressed in the wrong unit.
		foreach ($existingForRef as $line) {
			if (!isset($matched[$line->supplierPriceId]) && $line->packagingUnit !== '') {
				$report->addIssue(new SupplierPriceSyncIssue(
					SupplierPriceSyncIssue::SEVERITY_WARNING,
					SupplierPriceSyncConstants::ISSUE_UNIT_MISMATCH,
					$line->packagingUnit,
					$line->supplierRef,
					$line->productRef,
					$line->quantity
				));
			}
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
		$unit = $tier->unitLabel !== '' ? $tier->unitLabel : $candidate->packagingUnit;

		if ($this->priceDiffers($candidate->currentUnitPrice, $tier->normalizedUnitPrice)) {
			if (!$this->dryRun && !$this->updateBuyPrice($candidate, $tier->normalizedUnitPrice, $user)) {
				$report->addIssue($this->updateFailedIssue($candidate->supplierRef, $candidate->productRef, $candidate->quantity));

				return;
			}
			$report->incrementUpdated();
			$report->recordChange(
				SupplierPriceSyncConstants::CHANGE_UPDATE,
				$candidate->supplierRef,
				$candidate->productRef,
				$candidate->currentUnitPrice,
				$tier->normalizedUnitPrice,
				$unit,
				$candidate->productId
			);
		} else {
			$report->incrementUnchanged();
		}

		if ($candidate->currentStatus !== SupplierPriceSyncConstants::STATUS_ACTIVE) {
			if ($this->dryRun || $this->repository->activate($candidate->supplierPriceId)) {
				$report->incrementReactivated();
				$report->recordChange(
					SupplierPriceSyncConstants::CHANGE_REACTIVATE,
					$candidate->supplierRef,
					$candidate->productRef,
					null,
					null,
					$unit,
					$candidate->productId
				);
			} else {
				$report->addIssue($this->updateFailedIssue($candidate->supplierRef, $candidate->productRef, $candidate->quantity));
			}
		}
	}

	/**
	 * Match an existing line to a tier, by unit when the tier carries one.
	 *
	 * ANTALIS returns one threshold per price unit (per sheet, per ream…) with a
	 * thresholdQty that does not align with the stored line quantity, so the reliable
	 * join key is the unit, not the quantity. Quantity matching is kept as a fallback
	 * for unit-less connectors (and for unmapped tier units).
	 *
	 * @param SupplierPriceCandidate[] $existingForRef Existing lines for the product.
	 * @param SupplierPriceTier        $tier           API tier to match.
	 * @return SupplierPriceCandidate|null
	 */
	private function matchLine(array $existingForRef, SupplierPriceTier $tier): ?SupplierPriceCandidate
	{
		if ($tier->unitLabel !== '') {
			$tierUnit = $this->normalizeUnitLabel($tier->unitLabel);
			foreach ($existingForRef as $line) {
				if ($line->packagingUnit !== '' && $this->normalizeUnitLabel($line->packagingUnit) === $tierUnit) {
					return $line;
				}
			}

			return null;
		}

		return $this->matchByQuantity($existingForRef, $tier->quantity);
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
			$report->recordChange(
				SupplierPriceSyncConstants::CHANGE_CREATE,
				$product->supplierRef,
				$product->productRef,
				null,
				$tier->normalizedUnitPrice,
				$tier->unitLabel,
				$product->productId
			);

			return;
		}

		// A zero/negative quantity would create a broken supplier line and then make
		// update_buyprice() divide by zero: reject it as a write failure up front.
		if ($tier->quantity <= 0) {
			dol_syslog('SupplierPriceSyncService::createTier skipped, non-positive quantity ref=' . $product->supplierRef, LOG_WARNING);
			$report->addIssue($this->updateFailedIssue($product->supplierRef, $product->productRef, $tier->quantity));

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
			$report->recordChange(
				SupplierPriceSyncConstants::CHANGE_CREATE,
				$product->supplierRef,
				$product->productRef,
				null,
				$tier->normalizedUnitPrice,
				$tier->unitLabel,
				$product->productId
			);
		} else {
			$report->incrementUpdated();
			$report->recordChange(
				SupplierPriceSyncConstants::CHANGE_UPDATE,
				$product->supplierRef,
				$product->productRef,
				null,
				$tier->normalizedUnitPrice,
				$tier->unitLabel,
				$product->productId
			);
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
				$report->recordChange(
					SupplierPriceSyncConstants::CHANGE_CLOSE,
					$line->supplierRef,
					$line->productRef,
					null,
					null,
					$line->packagingUnit,
					$line->productId
				);
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
		// Guard against a corrupt zero/negative quantity: update_buyprice() divides the
		// total by the quantity (core fournisseur.product.class.php) and would raise a
		// fatal DivisionByZeroError. Treat it as a write failure instead.
		if ($candidate->quantity <= 0) {
			dol_syslog('SupplierPriceSyncService::updateBuyPrice skipped, non-positive quantity for line id=' . $candidate->supplierPriceId, LOG_WARNING);

			return false;
		}

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

		// NB: $fourn is passed as the int supplier id. update_buyprice() only
		// dereferences $fourn->id in its INSERT branch; here the line is always
		// pre-fetched above (product_fourn_price_id > 0) so it takes the UPDATE
		// branch and never touches $fourn. A future connector that relies on the
		// INSERT branch must pass a Societe object instead.
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
