<?php
declare(strict_types=1);
/* Copyright (C) 2026       ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__.'/RfaSummaryBuilder.php';
require_once __DIR__.'/../chaumeilrfa.class.php';

/**
 * Builder used to compute yearly client RFA summary rows.
 * Uses closed customer invoices (STATUS_CLOSED) instead of supplier invoices.
 */
class RfaClientSummaryBuilder extends RfaSummaryBuilder
{
	/**
	 * Build all client RFA summary rows for a given year.
	 *
	 * @param int $year Target year.
	 * @return array<int,array<string,mixed>>
	 * @throws RuntimeException When source data cannot be loaded or hierarchy is invalid.
	 */
	public function buildYearSummary(int $year): array
	{
		$thirdParties = $this->repository->fetchThirdParties();
		$ownTurnoverByClient = $this->repository->fetchOwnTurnoverByClientYear($year);
		$activeRfaRowsByClient = $this->repository->fetchActiveRfaRowsForYear($year, ChaumeilRfa::TYPE_CLIENT);

		if (empty($activeRfaRowsByClient)) {
			return array();
		}

		$childrenByParent = $this->buildChildrenByParent($thirdParties);
		$turnoverMemo = array();
		$contributorMemo = array();
		$summaryRows = array();
		$calculationTimestamp = dol_now();

		foreach ($activeRfaRowsByClient as $clientId => $rfaRows) {
			if (empty($thirdParties[$clientId])) {
				continue;
			}
			if ((int) $thirdParties[$clientId]['client'] < 1) {
				continue;
			}

			$subtreeData = $this->computeSubtreeData($clientId, $childrenByParent, $ownTurnoverByClient, $turnoverMemo, $contributorMemo, array());
			$aggregatedTurnover = (float) $subtreeData['turnover'];
			$bestReachedRfa = $this->findBestReachedRfa($rfaRows, $aggregatedTurnover);
			if ($bestReachedRfa === null) {
				continue;
			}

			$contributorIds = array_values(array_unique(array_map('intval', $subtreeData['contributors'])));
			$isAggregated = count($contributorIds) > 1 || (count($contributorIds) === 1 && (int) $contributorIds[0] !== $clientId);

			$summaryRows[] = array(
				'year' => $year,
				'rfa_type' => ChaumeilRfa::TYPE_CLIENT,
				'fk_soc' => $clientId,
				'fk_root_soc' => $this->resolveRootSupplierId($clientId, $thirdParties), // method name is supplier-centric but works for any party hierarchy
				'fk_chaumeilrfa' => (int) $bestReachedRfa['rowid'],
				'is_aggregated' => $isAggregated ? 1 : 0,
				'contributor_count' => count($contributorIds),
				'ca_achats' => $aggregatedTurnover,
				'taux_rfa' => (float) $bestReachedRfa['raterfa'],
				'discount_amount_rfa' => $aggregatedTurnover * (((float) $bestReachedRfa['raterfa']) / 100),
				'rfa_status' => (int) $bestReachedRfa['status'],
				'date_calculated' => $calculationTimestamp,
			);
		}

		return $summaryRows;
	}
}
