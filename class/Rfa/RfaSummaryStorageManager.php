<?php
declare(strict_types=1);
/* Copyright (C) 2026       ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Storage manager used to inspect the RFA summary table.
 */
class RfaSummaryStorageManager
{
	/**
	 * @var string
	 */
	public const TABLE_SUMMARY = 'clichaumeil_rfa_summary';

	/**
	 * @var string
	 */
	public const ERROR_SUMMARY_STORAGE_MISSING = 'RFA summary storage is missing. Run the module SQL upgrade before rebuilding the summary.';

	/**
	 * @var DoliDB
	 */
	private DoliDB $db;

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
	 * Ensure the summary storage is ready.
	 *
	 * @return void
	 * @throws RuntimeException When the summary table is missing.
	 */
	public function assertStorageReady(): void
	{
		if (!$this->tableExists()) {
			throw new RuntimeException(self::ERROR_SUMMARY_STORAGE_MISSING);
		}
	}

	/**
	 * Check whether the summary table exists.
	 *
	 * @return bool
	 * @throws RuntimeException When the inspection query fails.
	 */
	public function tableExists(): bool
	{
		global $conf;

		$tables = $this->db->DDLListTables($conf->db->name, $this->db->prefix().self::TABLE_SUMMARY);
		if (!is_array($tables)) {
			throw new RuntimeException('Unable to inspect summary table existence.');
		}

		$expectedTable = $this->db->prefix().self::TABLE_SUMMARY;
		foreach ($tables as $tableName) {
			if ((string) $tableName === $expectedTable) {
				return true;
			}
		}

		return false;
	}
}
