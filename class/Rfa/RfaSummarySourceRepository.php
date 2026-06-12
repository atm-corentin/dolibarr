<?php
declare(strict_types=1);
/* Copyright (C) 2026       ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once __DIR__.'/RfaSummaryStorageManager.php';
require_once __DIR__.'/../chaumeilrfa.class.php';

/**
 * Repository used to load RFA summary source data and summary list rows.
 */
class RfaSummarySourceRepository
{
	/**
	 * @var int
	 */
	private const SUPPLIER_FLAG = 1;

	/**
	 * @var int
	 */
	private const CLOSED_SUPPLIER_INVOICE_STATUS = FactureFournisseur::STATUS_CLOSED;

	/**
	 * @var string
	 */
	private const TABLE_THIRDPARTY = 'societe';

	/**
	 * @var string
	 */
	private const TABLE_SUPPLIER_INVOICE = 'facture_fourn';

	/**
	 * @var string
	 */
	private const TABLE_CUSTOMER_INVOICE = 'facture';

	/**
	 * @var int
	 */
	private const CUSTOMER_FLAG = 1;

	/**
	 * @var int
	 */
	private const CLOSED_CUSTOMER_INVOICE_STATUS = 2; // Facture::STATUS_CLOSED

	/**
	 * @var string
	 */
	private const TABLE_RFA = 'clichaumeil_chaumeilrfa';

	/**
	 * @var string
	 */
	private const TABLE_SUMMARY = 'clichaumeil_rfa_summary';

	/**
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * @var int
	 */
	private int $entity;

	/**
	 * @var RfaSummaryStorageManager
	 */
	private RfaSummaryStorageManager $storageManager;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->entity = (int) $conf->entity;
		$this->storageManager = new RfaSummaryStorageManager($db);
	}

	/**
	 * Load third parties used by the hierarchy builder.
	 *
	 * @return array<int,array<string,mixed>> Third parties indexed by id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchThirdParties(): array
	{
		$rows = array();
		$sql = 'SELECT s.rowid, s.nom, s.parent, s.fournisseur, s.client';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s';
		$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch third parties: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$rows[(int) $obj->rowid] = array(
				'rowid' => (int) $obj->rowid,
				'nom' => (string) $obj->nom,
				'parent' => (int) $obj->parent,
				'fournisseur' => (int) $obj->fournisseur,
				'client' => (int) $obj->client,
			);
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Load supplier own turnover for one year.
	 *
	 * @param int $year Target year.
	 * @return array<int,float> Turnover indexed by supplier id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchOwnTurnoverBySupplierYear(int $year): array
	{
		$rows = array();
		$sql = 'SELECT ff.fk_soc, SUM(ff.total_ht) AS own_turnover';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_SUPPLIER_INVOICE.' AS ff';
		$sql .= ' INNER JOIN '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s ON s.rowid = ff.fk_soc';
		$sql .= ' WHERE ff.entity IN ('.getEntity('facture_fourn').')';
		$sql .= ' AND s.entity IN ('.getEntity('societe').')';
		$sql .= ' AND s.fournisseur = '.self::SUPPLIER_FLAG;
		$sql .= ' AND ff.fk_statut = '.self::CLOSED_SUPPLIER_INVOICE_STATUS;
		$sql .= ' AND YEAR(ff.datef) = '.((int) $year);
		$sql .= ' GROUP BY ff.fk_soc';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch supplier turnover: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$rows[(int) $obj->fk_soc] = (float) $obj->own_turnover;
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Load customer own turnover for one year.
	 *
	 * @param int $year Target year.
	 * @return array<int,float> Turnover indexed by customer id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchOwnTurnoverByClientYear(int $year): array
	{
		$rows = array();
		$sql = 'SELECT f.fk_soc, SUM(f.total_ht) AS own_turnover';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_CUSTOMER_INVOICE.' AS f';
		$sql .= ' INNER JOIN '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s ON s.rowid = f.fk_soc';
		$sql .= ' WHERE f.entity IN ('.getEntity('facture').')';
		$sql .= ' AND s.entity IN ('.getEntity('societe').')';
		$sql .= ' AND s.client >= '.self::CUSTOMER_FLAG;
		$sql .= ' AND f.fk_statut = '.self::CLOSED_CUSTOMER_INVOICE_STATUS;
		$sql .= ' AND YEAR(f.datef) = '.((int) $year);
		$sql .= ' GROUP BY f.fk_soc';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch customer turnover: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$rows[(int) $obj->fk_soc] = (float) $obj->own_turnover;
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Load all RFA rows active for one year.
	 *
	 * @param int $year    Target year.
	 * @param int $rfaType RFA type (ChaumeilRfa::TYPE_SUPPLIER or TYPE_CLIENT).
	 * @return array<int,array<int,array<string,mixed>>> RFA rows grouped by supplier id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchActiveRfaRowsForYear(int $year, int $rfaType = ChaumeilRfa::TYPE_SUPPLIER): array
	{
		$rows = array();
		$sql = 'SELECT r.rowid, r.fk_soc, r.palier, r.raterfa, r.status, r.datestart, r.dateend';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_RFA.' AS r';
		$sql .= ' WHERE '.((int) $year).' BETWEEN YEAR(r.datestart) AND YEAR(r.dateend)';
		$sql .= ' AND r.rfa_type = '.((int) $rfaType);
		$sql .= ' ORDER BY r.fk_soc ASC, r.palier DESC, r.rowid DESC';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch active RFA rows: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$supplierId = (int) $obj->fk_soc;
			if (!isset($rows[$supplierId])) {
				$rows[$supplierId] = array();
			}

			$rows[$supplierId][] = array(
				'rowid' => (int) $obj->rowid,
				'fk_soc' => $supplierId,
				'palier' => (float) $obj->palier,
				'raterfa' => (float) $obj->raterfa,
				'status' => (int) $obj->status,
				'datestart' => (string) $obj->datestart,
				'dateend' => (string) $obj->dateend,
			);
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Load the list of years that should be rebuilt by the cron job.
	 *
	 * @return array<int,int> Sorted list of relevant years.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchRelevantSummaryYears(): array
	{
		$years = array();

		$sql = 'SELECT r.datestart, r.dateend';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_RFA.' AS r';
		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch RFA year ranges: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$startYear = (int) dol_print_date($this->db->jdate($obj->datestart), '%Y');
			$endYear = (int) dol_print_date($this->db->jdate($obj->dateend), '%Y');
			if ($startYear <= 0 || $endYear <= 0) {
				continue;
			}

			for ($year = $startYear; $year <= $endYear; $year++) {
				$years[$year] = $year;
			}
		}

		$this->db->free($resql);

		if ($this->summaryTableExists()) {
			$sql = 'SELECT DISTINCT rs.year';
			$sql .= ' FROM '.$this->db->prefix().self::TABLE_SUMMARY.' AS rs';
			$sql .= ' WHERE rs.entity = '.$this->entity;
			$sql .= ' ORDER BY rs.year ASC';
			$resql = $this->db->query($sql);
			if (!$resql) {
				throw new RuntimeException('Unable to fetch existing summary years: '.$this->db->lasterror());
			}

			while (($obj = $this->db->fetch_object($resql)) !== null) {
				$year = (int) $obj->year;
				if ($year > 0) {
					$years[$year] = $year;
				}
			}

			$this->db->free($resql);
		}

		ksort($years);

		return array_values($years);
	}

	/**
	 * Count summary rows for a given year and filters.
	 *
	 * @param int $year Target year.
	 * @param array<string,string> $filters Filters.
	 * @return int
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function countSummaryRowsForYear(int $year, array $filters): int
	{
		if (!$this->summaryTableExists()) {
			return 0;
		}

		$sql = 'SELECT COUNT(rs.rowid) AS nb';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_SUMMARY.' AS rs';
		$sql .= ' INNER JOIN '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s ON s.rowid = rs.fk_soc';
		$sql .= ' WHERE rs.entity = '.$this->entity;
		$sql .= ' AND rs.year = '.((int) $year);
		$sql .= $this->buildSummaryFilterSql($filters);

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to count summary rows: '.$this->db->lasterror());
		}

		$obj = $this->db->fetch_object($resql);
		$count = $obj ? (int) $obj->nb : 0;
		$this->db->free($resql);

		return $count;
	}

	/**
	 * Fetch paginated summary rows for a given year.
	 *
	 * @param int $year Target year.
	 * @param array<string,string> $filters Filters.
	 * @param string $sortField Requested sort field.
	 * @param string $sortOrder Requested sort order.
	 * @param int $offset Offset.
	 * @param int $limit Limit.
	 * @return array<int,array<string,mixed>>
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchSummaryRowsForYear(int $year, array $filters, string $sortField, string $sortOrder, int $offset, int $limit): array
	{
		$rows = array();
		if (!$this->summaryTableExists()) {
			return $rows;
		}

		$sql = 'SELECT rs.rowid, rs.fk_soc, rs.fk_root_soc, rs.is_aggregated, rs.contributor_count, rs.ca_achats, rs.taux_rfa, rs.discount_amount_rfa, rs.rfa_status, rs.date_calculated, s.nom AS soc_name';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_SUMMARY.' AS rs';
		$sql .= ' INNER JOIN '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s ON s.rowid = rs.fk_soc';
		$sql .= ' WHERE rs.entity = '.$this->entity;
		$sql .= ' AND rs.year = '.((int) $year);
		$sql .= $this->buildSummaryFilterSql($filters);
		$sql .= $this->buildSummaryOrderBySql($sortField, $sortOrder);
		if ($limit > 0) {
			$sql .= ' '.$this->db->plimit((int) $limit, (int) max(0, $offset));
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch summary rows: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$rows[] = array(
				'rowid' => (int) $obj->rowid,
				'fk_soc' => (int) $obj->fk_soc,
				'root_soc_id' => (int) $obj->fk_root_soc,
				'is_aggregated' => ((int) $obj->is_aggregated) === 1,
				'contributor_count' => (int) $obj->contributor_count,
				'ca_achats' => (float) $obj->ca_achats,
				'taux_rfa' => (float) $obj->taux_rfa,
				'discount_amount_rfa' => (float) $obj->discount_amount_rfa,
				'status' => (int) $obj->rfa_status,
				'date_calculated' => (string) $obj->date_calculated,
				'soc_name' => (string) $obj->soc_name,
			);
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Check whether a summary exists for a given year and type.
	 *
	 * @param int $year    Target year.
	 * @param int $rfaType RFA type (ChaumeilRfa::TYPE_SUPPLIER or TYPE_CLIENT).
	 * @return bool
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function hasSummaryForYear(int $year, int $rfaType = ChaumeilRfa::TYPE_SUPPLIER): bool
	{
		if (!$this->summaryTableExists()) {
			return false;
		}

		return $this->countSummaryRowsForYear($year, array('rfa_type' => (string) $rfaType)) > 0;
	}

	/**
	 * Return the latest calculation timestamp stored for one summary year and type.
	 *
	 * @param int $year    Target year.
	 * @param int $rfaType RFA type (ChaumeilRfa::TYPE_SUPPLIER or TYPE_CLIENT).
	 * @return int Unix timestamp, 0 when unavailable.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function getSummaryLastCalculatedTimestamp(int $year, int $rfaType = ChaumeilRfa::TYPE_SUPPLIER): int
	{
		if (!$this->summaryTableExists()) {
			return 0;
		}

		$sql = 'SELECT MAX(rs.date_calculated) AS last_calculated_at';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_SUMMARY.' AS rs';
		$sql .= ' WHERE rs.entity = '.$this->entity;
		$sql .= ' AND rs.year = '.((int) $year);
		$sql .= ' AND rs.rfa_type = '.((int) $rfaType);

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch summary latest calculation date: '.$this->db->lasterror());
		}

		$obj = $this->db->fetch_object($resql);
		$timestamp = 0;
		if ($obj && !empty($obj->last_calculated_at)) {
			$timestamp = (int) $this->db->jdate($obj->last_calculated_at);
		}
		$this->db->free($resql);

		return $timestamp;
	}

	/**
	 * Check whether the summary storage is available.
	 *
	 * @return bool
	 * @throws RuntimeException When the inspection query fails.
	 */
	public function isSummaryStorageReady(): bool
	{
		return $this->summaryTableExists();
	}

	/**
	 * Check whether the summary table exists.
	 *
	 * @return bool
	 * @throws RuntimeException When the SQL query fails.
	 */
	private function summaryTableExists(): bool
	{
		return $this->storageManager->tableExists();
	}

	/**
	 * Build SQL filters for summary queries.
	 *
	 * @param array<string,string> $filters Filters.
	 * @return string
	 */
	private function buildSummaryFilterSql(array $filters): string
	{
		$sql = '';

		if (isset($filters['fk_soc']) && $filters['fk_soc'] !== '') {
			$sql .= ' AND rs.fk_soc = '.((int) $filters['fk_soc']);
		}
		if (isset($filters['status']) && $filters['status'] !== '') {
			$sql .= ' AND rs.rfa_status = '.((int) $filters['status']);
		}
		if (isset($filters['ca_achats']) && $filters['ca_achats'] !== '') {
			$sql .= natural_search('rs.ca_achats', (string) $filters['ca_achats'], 1);
		}
		if (isset($filters['taux_rfa']) && $filters['taux_rfa'] !== '') {
			$sql .= natural_search('rs.taux_rfa', (string) $filters['taux_rfa'], 1);
		}
		if (isset($filters['discount_amount_rfa']) && $filters['discount_amount_rfa'] !== '') {
			$sql .= natural_search('rs.discount_amount_rfa', (string) $filters['discount_amount_rfa'], 1);
		}
		if (isset($filters['rfa_type']) && $filters['rfa_type'] !== '') {
			$sql .= ' AND rs.rfa_type = '.((int) $filters['rfa_type']);
		}

		return $sql;
	}

	/**
	 * Build a safe ORDER BY clause for summary rows.
	 *
	 * @param string $sortField Requested sort field.
	 * @param string $sortOrder Requested sort order.
	 * @return string
	 */
	private function buildSummaryOrderBySql(string $sortField, string $sortOrder): string
	{
		$fieldMap = array(
			'fk_soc' => 's.nom',
			'ca_achats' => 'rs.ca_achats',
			'taux_rfa' => 'rs.taux_rfa',
			'discount_amount_rfa' => 'rs.discount_amount_rfa',
			'status' => 'rs.rfa_status',
		);

		$normalizedSortField = isset($fieldMap[$sortField]) ? $fieldMap[$sortField] : 's.nom';
		$normalizedSortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

		return ' ORDER BY '.$normalizedSortField.' '.$normalizedSortOrder.', s.nom ASC, rs.rowid ASC';
	}
}
