<?php
declare(strict_types=1);
/* Copyright (C) 2026       ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__.'/RfaGlobalListRepository.php';

/**
 * Service dedicated to the supplier RFA global list business rules.
 */
class RfaGlobalListService
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
	 * @var string
	 */
	private const DISPLAY_MODE_VISIBLE_RFA_SUPPLIER = 'visible_rfa_supplier';

	/**
	 * @var RfaGlobalListRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param RfaGlobalListRepository $repository Data repository.
	 */
	public function __construct(RfaGlobalListRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Build the final paginated list rows for the global RFA list.
	 *
	 * @param int $year Target year.
	 * @param array<string,string> $filters Search filters.
	 * @param string $sortField Requested sort field.
	 * @param string $sortOrder Requested sort order.
	 * @param int $offset Requested offset.
	 * @param int $limit Requested limit.
	 * @return array<string,mixed> List result with total_count and rows keys.
	 * @throws RuntimeException When data loading fails.
	 */
	public function buildList(int $year, array $filters, string $sortField, string $sortOrder, int $offset, int $limit): array
	{
		$supplierTurnovers = $this->repository->fetchSupplierTurnoversForYear($year);
		if (empty($supplierTurnovers)) {
			return array(
				'total_count' => 0,
				'rows' => array(),
			);
		}

		$thirdParties = $this->loadThirdPartiesWithAncestors($supplierTurnovers);
		$activeRfaSuppliers = $this->repository->fetchActiveRfaSuppliersForYear($year);

		$aggregatedTurnoverBySupplier = array();
		$contributorsBySupplier = array();

		foreach ($supplierTurnovers as $supplierTurnover) {
			$supplierId = (int) $supplierTurnover['supplier_id'];
			$ancestorIds = $this->getAncestorIds($supplierId, $thirdParties);

			foreach ($ancestorIds as $ancestorId) {
				if (empty($activeRfaSuppliers[$ancestorId])) {
					continue;
				}

				if (!isset($aggregatedTurnoverBySupplier[$ancestorId])) {
					$aggregatedTurnoverBySupplier[$ancestorId] = 0.0;
					$contributorsBySupplier[$ancestorId] = array();
				}

				$aggregatedTurnoverBySupplier[$ancestorId] += (float) $supplierTurnover['own_turnover'];
				$contributorsBySupplier[$ancestorId][$supplierId] = $supplierId;
			}
		}

		$rfaSupplierIds = array_keys($aggregatedTurnoverBySupplier);
		$rfaRowsBySupplier = $this->repository->fetchActiveRfaRowsForSuppliersAndYear($rfaSupplierIds, $year);
		$displayRows = array();

		foreach ($aggregatedTurnoverBySupplier as $supplierId => $aggregatedTurnover) {
			if (isset($thirdParties[(int) $supplierId]['fournisseur']) && (int) $thirdParties[(int) $supplierId]['fournisseur'] !== 1) {
				continue;
			}

			if (empty($activeRfaSuppliers[(int) $supplierId])) {
				continue;
			}

			$bestReachedRfa = $this->findBestReachedRfa((int) $supplierId, (float) $aggregatedTurnover, $rfaRowsBySupplier);
			if ($bestReachedRfa === null) {
				continue;
			}

			if (empty($thirdParties[(int) $supplierId])) {
				continue;
			}

			$displayRows[] = $this->buildDisplayRow(
				(int) $supplierId,
				$thirdParties[(int) $supplierId],
				(float) $aggregatedTurnover,
				$bestReachedRfa,
				array_values($contributorsBySupplier[(int) $supplierId]),
				$this->resolveRootParentId((int) $supplierId, $thirdParties)
			);
		}

		$filteredRows = $this->applyFilters($displayRows, $filters);
		$sortedRows = $this->applySort($filteredRows, $sortField, $sortOrder);
		$totalCount = count($sortedRows);
		$pagedRows = $limit > 0 ? array_slice($sortedRows, max(0, $offset), $limit) : $sortedRows;

		return array(
			'total_count' => $totalCount,
			'rows' => array_values($pagedRows),
		);
	}

	/**
	 * Load suppliers plus every required ancestor to resolve root parents.
	 *
	 * @param array<int,array<string,mixed>> $supplierTurnovers Supplier turnover rows.
	 * @return array<int,array<string,mixed>> Third parties indexed by id.
	 * @throws RuntimeException When data loading fails.
	 */
	private function loadThirdPartiesWithAncestors(array $supplierTurnovers): array
	{
		$thirdParties = $supplierTurnovers;
		$missingParentIds = $this->collectMissingParentIds($thirdParties);

		while (!empty($missingParentIds)) {
			$loadedParents = $this->repository->fetchThirdPartiesByIds($missingParentIds);
			if (empty($loadedParents)) {
				break;
			}

			foreach ($loadedParents as $loadedParentId => $loadedParentRow) {
				$thirdParties[(int) $loadedParentId] = $loadedParentRow;
			}

			$missingParentIds = $this->collectMissingParentIds($thirdParties);
		}

		return $thirdParties;
	}

	/**
	 * Collect missing parent ids from the provided third party map.
	 *
	 * @param array<int,array<string,mixed>> $thirdParties Third parties indexed by id.
	 * @return array<int,int> Missing parent ids.
	 */
	private function collectMissingParentIds(array $thirdParties): array
	{
		$missingParentIds = array();

		foreach ($thirdParties as $thirdParty) {
			$parentId = isset($thirdParty['parent_id']) ? (int) $thirdParty['parent_id'] : self::NO_PARENT_ID;
			if ($parentId > self::NO_PARENT_ID && empty($thirdParties[$parentId])) {
				$missingParentIds[$parentId] = $parentId;
			}
		}

		return array_values($missingParentIds);
	}

	/**
	 * Return ancestor ids for one supplier, including itself and the root parent.
	 *
	 * @param int $supplierId Supplier id.
	 * @param array<int,array<string,mixed>> $thirdParties Third parties indexed by id.
	 * @return array<int,int> Ordered ancestor ids from self to root.
	 */
	private function getAncestorIds(int $supplierId, array $thirdParties): array
	{
		$ancestorIds = array();
		$currentId = $supplierId;
		$visited = array();
		$depth = 0;

		while (!empty($thirdParties[$currentId])) {
			if (!empty($visited[$currentId]) || $depth >= self::MAX_DEPTH_GUARD) {
				if (empty($ancestorIds)) {
					$ancestorIds[] = $supplierId;
				}
				break;
			}

			$visited[$currentId] = true;
			$ancestorIds[] = $currentId;

			$parentId = isset($thirdParties[$currentId]['parent_id']) ? (int) $thirdParties[$currentId]['parent_id'] : self::NO_PARENT_ID;
			if ($parentId <= self::NO_PARENT_ID || empty($thirdParties[$parentId])) {
				break;
			}

			$currentId = $parentId;
			$depth++;
		}

		return array_values(array_unique(array_map('intval', $ancestorIds)));
	}

	/**
	 * Resolve the root parent id for a supplier.
	 *
	 * @param int $supplierId Supplier id.
	 * @param array<int,array<string,mixed>> $thirdParties Third parties indexed by id.
	 * @return int Root parent id.
	 */
	private function resolveRootParentId(int $supplierId, array $thirdParties): int
	{
		$currentId = $supplierId;
		$visited = array();
		$depth = 0;

		while (!empty($thirdParties[$currentId])) {
			if (!empty($visited[$currentId]) || $depth >= self::MAX_DEPTH_GUARD) {
				return $supplierId;
			}

			$visited[$currentId] = true;
			$parentId = isset($thirdParties[$currentId]['parent_id']) ? (int) $thirdParties[$currentId]['parent_id'] : self::NO_PARENT_ID;

			if ($parentId <= self::NO_PARENT_ID || empty($thirdParties[$parentId])) {
				return $currentId;
			}

			$currentId = $parentId;
			$depth++;
		}

		return $supplierId;
	}

	/**
	 * Find the highest reached RFA row for a root supplier.
	 *
	 * @param int $rootId Root supplier id.
	 * @param float $aggregatedTurnover Aggregated turnover.
	 * @param array<int,array<int,array<string,mixed>>> $rfaRowsBySupplier RFA rows grouped by supplier.
	 * @return array<string,mixed>|null Best reached RFA row or null.
	 */
	private function findBestReachedRfa(int $rootId, float $aggregatedTurnover, array $rfaRowsBySupplier): ?array
	{
		if (empty($rfaRowsBySupplier[$rootId])) {
			return null;
		}

		foreach ($rfaRowsBySupplier[$rootId] as $rfaRow) {
			if ((float) $rfaRow['palier'] <= $aggregatedTurnover) {
				return $rfaRow;
			}
		}

		return null;
	}

	/**
	 * Build one display row.
	 *
	 * @param int $supplierId Supplier id displayed in the row.
	 * @param array<string,mixed> $rootThirdParty Root third party row.
	 * @param float $aggregatedTurnover Aggregated turnover.
	 * @param array<string,mixed> $bestReachedRfa Best reached RFA row.
	 * @param array<int,int> $contributorIds Contributor ids.
	 * @param int $rootSupplierId Root supplier id.
	 * @return array<string,mixed> Display row.
	 */
	private function buildDisplayRow(int $supplierId, array $rootThirdParty, float $aggregatedTurnover, array $bestReachedRfa, array $contributorIds, int $rootSupplierId): array
	{
		$uniqueContributorIds = array_values(array_unique(array_map('intval', $contributorIds)));
		$isAggregated = count($uniqueContributorIds) > 1 || (count($uniqueContributorIds) === 1 && (int) $uniqueContributorIds[0] !== $supplierId);

		return array(
			'fk_soc' => $supplierId,
			'soc_name' => isset($rootThirdParty['supplier_name']) ? (string) $rootThirdParty['supplier_name'] : '',
			'display_mode' => self::DISPLAY_MODE_VISIBLE_RFA_SUPPLIER,
			'root_soc_id' => $rootSupplierId,
			'ca_achats' => $aggregatedTurnover,
			'taux_rfa' => (float) $bestReachedRfa['raterfa'],
			'discount_amount_rfa' => $aggregatedTurnover * ((float) $bestReachedRfa['raterfa'] / 100),
			'status' => (int) $bestReachedRfa['status'],
			'datestart' => (string) $bestReachedRfa['datestart'],
			'is_aggregated' => $isAggregated,
			'contributor_count' => count($uniqueContributorIds),
			'contributor_ids' => $uniqueContributorIds,
		);
	}

	/**
	 * Apply filters on final rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Final rows.
	 * @param array<string,string> $filters Filter map.
	 * @return array<int,array<string,mixed>> Filtered rows.
	 */
	private function applyFilters(array $rows, array $filters): array
	{
		if (empty($filters)) {
			return $rows;
		}

		$filteredRows = array();

		foreach ($rows as $row) {
			if (!$this->matchesFilter($row, 'fk_soc', $filters)) {
				continue;
			}
			if (!$this->matchesFilter($row, 'ca_achats', $filters)) {
				continue;
			}
			if (!$this->matchesFilter($row, 'taux_rfa', $filters)) {
				continue;
			}
			if (!$this->matchesFilter($row, 'discount_amount_rfa', $filters)) {
				continue;
			}
			if (!$this->matchesFilter($row, 'status', $filters)) {
				continue;
			}

			$filteredRows[] = $row;
		}

		return $filteredRows;
	}

	/**
	 * Check if one row matches a given filter.
	 *
	 * @param array<string,mixed> $row Display row.
	 * @param string $fieldName Field name.
	 * @param array<string,string> $filters Filter map.
	 * @return bool True when the row matches the filter.
	 */
	private function matchesFilter(array $row, string $fieldName, array $filters): bool
	{
		if (!isset($filters[$fieldName]) || $filters[$fieldName] === '') {
			return true;
		}

		$filterValue = trim((string) $filters[$fieldName]);
		if ($fieldName === 'fk_soc' || $fieldName === 'status') {
			return (string) ((int) $row[$fieldName]) === (string) ((int) $filterValue);
		}

		$numericFilterValue = (float) $this->normalizeDecimalString($filterValue);
		$numericRowValue = (float) $row[$fieldName];

		return abs($numericRowValue - $numericFilterValue) < 0.00001;
	}

	/**
	 * Apply sorting on final rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Final rows.
	 * @param string $sortField Requested sort field.
	 * @param string $sortOrder Requested sort order.
	 * @return array<int,array<string,mixed>> Sorted rows.
	 */
	private function applySort(array $rows, string $sortField, string $sortOrder): array
	{
		$normalizedSortField = $this->normalizeSortField($sortField);
		$normalizedSortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

		usort(
			$rows,
			function (array $leftRow, array $rightRow) use ($normalizedSortField, $normalizedSortOrder): int {
				$comparison = 0;

				if ($normalizedSortField === 'soc_name') {
					$comparison = strcasecmp((string) $leftRow['soc_name'], (string) $rightRow['soc_name']);
				} elseif (in_array($normalizedSortField, array('ca_achats', 'taux_rfa', 'discount_amount_rfa'), true)) {
					$comparison = ((float) $leftRow[$normalizedSortField] <=> (float) $rightRow[$normalizedSortField]);
				} elseif ($normalizedSortField === 'status') {
					$comparison = ((int) $leftRow['status'] <=> (int) $rightRow['status']);
				}

				if ($comparison === 0) {
					$comparison = strcasecmp((string) $leftRow['soc_name'], (string) $rightRow['soc_name']);
				}

				return $normalizedSortOrder === 'DESC' ? -$comparison : $comparison;
			}
		);

		return $rows;
	}

	/**
	 * Normalize the requested sort field.
	 *
	 * @param string $sortField Requested sort field.
	 * @return string Normalized sort field.
	 */
	private function normalizeSortField(string $sortField): string
	{
		$allowedFields = array(
			'fk_soc' => 'soc_name',
			'ca_achats' => 'ca_achats',
			'taux_rfa' => 'taux_rfa',
			'discount_amount_rfa' => 'discount_amount_rfa',
			'status' => 'status',
		);

		return isset($allowedFields[$sortField]) ? $allowedFields[$sortField] : 'soc_name';
	}

	/**
	 * Normalize decimal strings for numeric filtering.
	 *
	 * @param string $value Raw string.
	 * @return string Normalized string.
	 */
	private function normalizeDecimalString(string $value): string
	{
		$normalizedValue = str_replace(',', '.', trim($value));

		return preg_replace('/\s+/', '', $normalizedValue) ?? '';
	}
}
