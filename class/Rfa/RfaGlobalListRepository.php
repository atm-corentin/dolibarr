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

/**
 * Repository dedicated to the supplier RFA global list dataset.
 */
class RfaGlobalListRepository
{
	/**
	 * @var int
	 */
	private const SUPPLIER_FLAG = 1;

	/**
	 * @var int
	 */
	private const STATUS_CLOSED_SUPPLIER_INVOICE = FactureFournisseur::STATUS_CLOSED;

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
	private const TABLE_RFA = 'clichaumeil_chaumeilrfa';

	/**
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Fetch suppliers having a positive purchase turnover for a given year.
	 *
	 * @param int $year Target year.
	 * @return array<int,array<string,mixed>> Rows indexed by supplier id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchSupplierTurnoversForYear(int $year): array
	{
		$rows = array();
		$sql = 'SELECT s.rowid AS supplier_id, s.nom AS supplier_name, s.parent AS parent_id, SUM(ff.total_ht) AS own_turnover';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s';
		$sql .= ' INNER JOIN '.$this->db->prefix().self::TABLE_SUPPLIER_INVOICE.' AS ff ON ff.fk_soc = s.rowid';
		$sql .= ' WHERE s.fournisseur = '.self::SUPPLIER_FLAG;
		$sql .= ' AND ff.fk_statut = '.self::STATUS_CLOSED_SUPPLIER_INVOICE;
		$sql .= ' AND YEAR(ff.datef) = '.((int) $year);
		$sql .= ' GROUP BY s.rowid, s.nom, s.parent';
		$sql .= ' HAVING SUM(ff.total_ht) > 0';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch supplier turnovers: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$supplierId = (int) $obj->supplier_id;
			$rows[$supplierId] = array(
				'supplier_id' => $supplierId,
				'supplier_name' => (string) $obj->supplier_name,
				'parent_id' => (int) $obj->parent_id,
				'fournisseur' => self::SUPPLIER_FLAG,
				'own_turnover' => (float) $obj->own_turnover,
			);
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Fetch third parties by ids.
	 *
	 * @param array<int,int> $ids Third party ids.
	 * @return array<int,array<string,mixed>> Rows indexed by supplier id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchThirdPartiesByIds(array $ids): array
	{
		$sanitizedIds = $this->sanitizeIds($ids);
		if (empty($sanitizedIds)) {
			return array();
		}

		$rows = array();
		$sql = 'SELECT s.rowid AS supplier_id, s.nom AS supplier_name, s.parent AS parent_id, s.fournisseur';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_THIRDPARTY.' AS s';
		$sql .= ' WHERE s.rowid IN ('.implode(',', $sanitizedIds).')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch third parties by ids: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$supplierId = (int) $obj->supplier_id;
			$rows[$supplierId] = array(
				'supplier_id' => $supplierId,
				'supplier_name' => (string) $obj->supplier_name,
				'parent_id' => (int) $obj->parent_id,
				'fournisseur' => (int) $obj->fournisseur,
				'own_turnover' => 0.0,
			);
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Fetch supplier ids having at least one active RFA on a given year.
	 *
	 * @param int $year Target year.
	 * @return array<int,bool> Supplier id set.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchActiveRfaSuppliersForYear(int $year): array
	{
		$rows = array();
		$sql = 'SELECT DISTINCT r.fk_soc';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_RFA.' AS r';
		$sql .= ' WHERE '.((int) $year).' BETWEEN YEAR(r.datestart) AND YEAR(r.dateend)';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to fetch active RFA suppliers: '.$this->db->lasterror());
		}

		while (($obj = $this->db->fetch_object($resql)) !== null) {
			$rows[(int) $obj->fk_soc] = true;
		}

		$this->db->free($resql);

		return $rows;
	}

	/**
	 * Fetch all active RFA rows for the provided suppliers and year.
	 *
	 * @param array<int,int> $supplierIds Supplier ids.
	 * @param int $year Target year.
	 * @return array<int,array<int,array<string,mixed>>> RFA rows grouped by supplier id.
	 * @throws RuntimeException When the SQL query fails.
	 */
	public function fetchActiveRfaRowsForSuppliersAndYear(array $supplierIds, int $year): array
	{
		$sanitizedIds = $this->sanitizeIds($supplierIds);
		if (empty($sanitizedIds)) {
			return array();
		}

		$rows = array();
		$sql = 'SELECT r.rowid, r.fk_soc, r.palier, r.raterfa, r.status, r.datestart, r.dateend';
		$sql .= ' FROM '.$this->db->prefix().self::TABLE_RFA.' AS r';
		$sql .= ' WHERE r.fk_soc IN ('.implode(',', $sanitizedIds).')';
		$sql .= ' AND '.((int) $year).' BETWEEN YEAR(r.datestart) AND YEAR(r.dateend)';
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
	 * Sanitize ids for SQL IN clauses.
	 *
	 * @param array<int,int> $ids Raw ids.
	 * @return array<int,int> Sanitized unique ids.
	 */
	private function sanitizeIds(array $ids): array
	{
		$sanitizedIds = array();

		foreach ($ids as $id) {
			$sanitizedId = (int) $id;
			if ($sanitizedId > 0) {
				$sanitizedIds[$sanitizedId] = $sanitizedId;
			}
		}

		return array_values($sanitizedIds);
	}
}
