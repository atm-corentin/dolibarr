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
require_once __DIR__.'/RfaSummaryStorageManager.php';
require_once __DIR__.'/../chaumeilrfa.class.php';

/**
 * Persister used to rebuild the yearly RFA summary table.
 */
class RfaSummaryPersister
{
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
	 * @var RfaSummaryBuilder
	 */
	private RfaSummaryBuilder $builder;

	/**
	 * @var RfaSummaryStorageManager
	 */
	private RfaSummaryStorageManager $storageManager;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 * @param RfaSummaryBuilder $builder Summary builder.
	 */
	public function __construct(DoliDB $db, RfaSummaryBuilder $builder)
	{
		global $conf;

		$this->db = $db;
		$this->builder = $builder;
		$this->entity = (int) $conf->entity;
		$this->storageManager = new RfaSummaryStorageManager($db);
	}

	/**
	 * Rebuild the summary for one year.
	 *
	 * @param int $year    Target year.
	 * @param int $rfaType RFA type (ChaumeilRfa::TYPE_SUPPLIER or TYPE_CLIENT).
	 * @return int Number of persisted rows.
	 * @throws RuntimeException When the rebuild fails.
	 */
	public function rebuildYear(int $year, int $rfaType = ChaumeilRfa::TYPE_SUPPLIER): int
	{
		if ($year < 2000 || $year > 2100) {
			throw new RuntimeException('Invalid summary rebuild year: '.$year);
		}

		$this->assertSummaryStorageReady();
		$summaryRows = $this->builder->buildYearSummary($year);
		$this->db->begin();

		try {
			$this->deleteYearRows($year, $rfaType);

			foreach ($summaryRows as $summaryRow) {
				$this->insertSummaryRow($summaryRow);
			}

			$this->db->commit();

			return count($summaryRows);
		} catch (Throwable $exception) {
			$this->db->rollback();
			throw new RuntimeException('Unable to rebuild RFA summary for year '.$year.': '.$exception->getMessage(), 0, $exception);
		}
	}

	/**
	 * Delete all summary rows for one year.
	 *
	 * @param int $year    Target year.
	 * @param int $rfaType RFA type (ChaumeilRfa::TYPE_SUPPLIER or TYPE_CLIENT).
	 * @return void
	 * @throws RuntimeException When the delete fails.
	 */
	private function deleteYearRows(int $year, int $rfaType): void
	{
		$sql = 'DELETE FROM '.$this->db->prefix().self::TABLE_SUMMARY;
		$sql .= ' WHERE entity = '.$this->entity;
		$sql .= ' AND year = '.((int) $year);
		$sql .= ' AND rfa_type = '.((int) $rfaType);
		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to delete yearly summary rows: '.$this->db->lasterror());
		}
	}

	/**
	 * Insert one summary row.
	 *
	 * @param array<string,mixed> $summaryRow Row to persist.
	 * @return void
	 * @throws RuntimeException When the insert fails.
	 */
	private function insertSummaryRow(array $summaryRow): void
	{
		$sql = 'INSERT INTO '.$this->db->prefix().self::TABLE_SUMMARY.' (';
		$sql .= 'entity, year, rfa_type, fk_soc, fk_root_soc, fk_chaumeilrfa, is_aggregated, contributor_count, ca_achats, taux_rfa, discount_amount_rfa, rfa_status, date_calculated';
		$sql .= ') VALUES (';
		$sql .= $this->entity;
		$sql .= ', '.(int) $summaryRow['year'];
		$sql .= ', '.(int) ($summaryRow['rfa_type'] ?? 0);
		$sql .= ', '.(int) $summaryRow['fk_soc'];
		$sql .= ', '.(int) $summaryRow['fk_root_soc'];
		$sql .= ', '.(int) $summaryRow['fk_chaumeilrfa'];
		$sql .= ', '.(int) $summaryRow['is_aggregated'];
		$sql .= ', '.(int) $summaryRow['contributor_count'];
		$sql .= ', '.((float) $summaryRow['ca_achats']);
		$sql .= ', '.((float) $summaryRow['taux_rfa']);
		$sql .= ', '.((float) $summaryRow['discount_amount_rfa']);
		$sql .= ', '.(int) $summaryRow['rfa_status'];
		$sql .= ", '".$this->db->idate((int) $summaryRow['date_calculated'])."'";
		$sql .= ')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('Unable to insert summary row for thirdparty #'.((int) $summaryRow['fk_soc']).': '.$this->db->lasterror());
		}
	}

	/**
	 * Ensure the summary storage exists before writing rows.
	 *
	 * @return void
	 * @throws RuntimeException When the summary table is missing.
	 */
	private function assertSummaryStorageReady(): void
	{
		$this->storageManager->assertStorageReady();
	}
}
