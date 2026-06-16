<?php
declare(strict_types=1);
/* Copyright (C) 2026       ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__.'/RfaSummarySourceRepository.php';
require_once __DIR__.'/../chaumeilrfa.class.php';

/**
 * Builder used to compute yearly RFA summary rows.
 */
class RfaSummaryBuilder
{
	/**
	 * @var int
	 */
	private const NO_PARENT_ID = 0;

	/**
	 * @var int
	 */
	private const MAX_DEPTH_GUARD = 50;

	/**
	 * @var RfaSummarySourceRepository
	 */
	protected RfaSummarySourceRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param RfaSummarySourceRepository $repository Source repository.
	 */
	public function __construct(RfaSummarySourceRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Build all summary rows for a given year.
	 *
	 * @param int $year Target year.
	 * @return array<int,array<string,mixed>>
	 * @throws RuntimeException When source data cannot be loaded or hierarchy is invalid.
	 */
	public function buildYearSummary(int $year): array
	{
		$thirdParties = $this->repository->fetchThirdParties();
		$ownTurnoverBySupplier = $this->repository->fetchOwnTurnoverBySupplierYear($year);
		$activeRfaRowsBySupplier = $this->repository->fetchActiveRfaRowsForYear($year);

		if (empty($activeRfaRowsBySupplier)) {
			return array();
		}

		$childrenByParent = $this->buildChildrenByParent($thirdParties);
		$turnoverMemo = array();
		$contributorMemo = array();
		$summaryRows = array();
		$calculationTimestamp = dol_now();

		foreach ($activeRfaRowsBySupplier as $supplierId => $rfaRows) {
			if (empty($thirdParties[$supplierId])) {
				continue;
			}
			if ((int) $thirdParties[$supplierId]['fournisseur'] !== 1) {
				continue;
			}

			$subtreeData = $this->computeSubtreeData($supplierId, $childrenByParent, $ownTurnoverBySupplier, $turnoverMemo, $contributorMemo, array());
			$aggregatedTurnover = (float) $subtreeData['turnover'];
			$bestReachedRfa = $this->findBestReachedRfa($rfaRows, $aggregatedTurnover);
			if ($bestReachedRfa === null) {
				continue;
			}

			$contributorIds = array_values(array_unique(array_map('intval', $subtreeData['contributors'])));
			$isAggregated = count($contributorIds) > 1 || (count($contributorIds) === 1 && (int) $contributorIds[0] !== $supplierId);

			$summaryRows[] = array(
				'year' => $year,
				'rfa_type' => ChaumeilRfa::TYPE_SUPPLIER,
				'fk_soc' => $supplierId,
				'fk_root_soc' => $this->resolveRootSupplierId($supplierId, $thirdParties),
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

	/**
	 * Build the parent to children adjacency map.
	 *
	 * @param array<int,array<string,mixed>> $thirdParties Third parties indexed by id.
	 * @return array<int,array<int,int>>
	 */
	protected function buildChildrenByParent(array $thirdParties): array
	{
		$childrenByParent = array();

		foreach ($thirdParties as $thirdPartyId => $thirdParty) {
			$parentId = isset($thirdParty['parent']) ? (int) $thirdParty['parent'] : self::NO_PARENT_ID;
			if ($parentId <= self::NO_PARENT_ID) {
				continue;
			}

			if (!isset($childrenByParent[$parentId])) {
				$childrenByParent[$parentId] = array();
			}

			$childrenByParent[$parentId][] = (int) $thirdPartyId;
		}

		return $childrenByParent;
	}

	/**
	 * Compute subtree turnover and contributors for a supplier.
	 *
	 * @param int $supplierId Supplier id.
	 * @param array<int,array<int,int>> $childrenByParent Children map.
	 * @param array<int,float> $ownTurnoverBySupplier Own turnover indexed by supplier.
	 * @param array<int,float> $turnoverMemo Turnover memoization.
	 * @param array<int,array<int,int>> $contributorMemo Contributor memoization.
	 * @param array<int,bool> $currentStack Current DFS stack.
	 * @return array<string,mixed>
	 * @throws RuntimeException When a cycle is detected.
	 */
	protected function computeSubtreeData(int $supplierId, array $childrenByParent, array $ownTurnoverBySupplier, array &$turnoverMemo, array &$contributorMemo, array $currentStack): array
	{
		if (isset($turnoverMemo[$supplierId], $contributorMemo[$supplierId])) {
			return array(
				'turnover' => (float) $turnoverMemo[$supplierId],
				'contributors' => $contributorMemo[$supplierId],
			);
		}

		if (!empty($currentStack[$supplierId])) {
			throw new RuntimeException('Cycle detected in thirdparty hierarchy for supplier #'.$supplierId);
		}
		if (count($currentStack) >= self::MAX_DEPTH_GUARD) {
			throw new RuntimeException('Maximum hierarchy depth reached for supplier #'.$supplierId);
		}

		$currentStack[$supplierId] = true;
		$aggregatedTurnover = isset($ownTurnoverBySupplier[$supplierId]) ? (float) $ownTurnoverBySupplier[$supplierId] : 0.0;
		$contributorIds = array();

		if ($aggregatedTurnover > 0) {
			$contributorIds[$supplierId] = $supplierId;
		}

		if (!empty($childrenByParent[$supplierId])) {
			foreach ($childrenByParent[$supplierId] as $childSupplierId) {
				$childSubtreeData = $this->computeSubtreeData((int) $childSupplierId, $childrenByParent, $ownTurnoverBySupplier, $turnoverMemo, $contributorMemo, $currentStack);
				$aggregatedTurnover += (float) $childSubtreeData['turnover'];

				foreach ($childSubtreeData['contributors'] as $contributorId) {
					$contributorIds[(int) $contributorId] = (int) $contributorId;
				}
			}
		}

		$turnoverMemo[$supplierId] = $aggregatedTurnover;
		$contributorMemo[$supplierId] = array_values($contributorIds);

		return array(
			'turnover' => $aggregatedTurnover,
			'contributors' => $contributorMemo[$supplierId],
		);
	}

	/**
	 * Find the highest reached RFA row for one supplier.
	 *
	 * @param array<int,array<string,mixed>> $rfaRows Supplier RFA rows sorted by descending palier.
	 * @param float $aggregatedTurnover Aggregated turnover.
	 * @return array<string,mixed>|null
	 */
	protected function findBestReachedRfa(array $rfaRows, float $aggregatedTurnover): ?array
	{
		foreach ($rfaRows as $rfaRow) {
			if ((float) $rfaRow['palier'] <= $aggregatedTurnover) {
				return $rfaRow;
			}
		}

		return null;
	}

	/**
	 * Resolve the root supplier id for one third party.
	 *
	 * @param int $supplierId Supplier id.
	 * @param array<int,array<string,mixed>> $thirdParties Third parties indexed by id.
	 * @return int
	 * @throws RuntimeException When a cycle is detected.
	 */
	protected function resolveRootSupplierId(int $supplierId, array $thirdParties): int
	{
		$currentId = $supplierId;
		$visited = array();
		$depth = 0;

		while (!empty($thirdParties[$currentId])) {
			if (!empty($visited[$currentId])) {
				throw new RuntimeException('Cycle detected while resolving root supplier for supplier #'.$supplierId);
			}
			if ($depth >= self::MAX_DEPTH_GUARD) {
				throw new RuntimeException('Maximum hierarchy depth reached while resolving root supplier for supplier #'.$supplierId);
			}

			$visited[$currentId] = true;
			$parentId = isset($thirdParties[$currentId]['parent']) ? (int) $thirdParties[$currentId]['parent'] : self::NO_PARENT_ID;
			if ($parentId <= self::NO_PARENT_ID || empty($thirdParties[$parentId])) {
				return $currentId;
			}

			$currentId = $parentId;
			$depth++;
		}

		return $supplierId;
	}
}
