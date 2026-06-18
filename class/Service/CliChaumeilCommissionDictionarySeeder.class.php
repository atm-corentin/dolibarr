<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * Handles the atomic migration of commission coefficients from legacy constants
 * to dictionary rows, including seeding, validation and legacy cleanup.
 */
class CliChaumeilCommissionDictionarySeeder
{
	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Current entity id.
	 *
	 * @var int
	 */
	private int $entity;

	/**
	 * Last migration error message.
	 *
	 * @var string
	 */
	private string $error = '';

	/**
	 * @param DoliDB $db Database handler
	 * @param int    $entity Current entity id
	 */
	public function __construct(DoliDB $db, int $entity)
	{
		$this->db = $db;
		$this->entity = $entity;
	}

	/**
	 * Return the last migration error collected by the service.
	 *
	 * @return string
	 */
	public function getError(): string
	{
		return $this->error;
	}

	/**
	 * Migrate commission coefficients from constants to dictionary rows atomically.
	 *
	 * @return int<-1,1> 1 on success, -1 on failure.
	 */
	public function migrate(): int
	{
		global $langs;

		$langs->loadLangs(array('clichaumeil@clichaumeil'));

		// The constants->dictionary migration is a one-shot operation. Once the dictionary
		// holds at least one row for this entity, the migration is considered done: we must
		// not re-insert default rows on module re-activation. Doing so would collide with the
		// (entity, role_code, customer_tag) unique key, and would fight back any code or tag
		// the user has since customized through the editable dictionary.
		$alreadySeeded = $this->dictionaryHasRows();
		if ($alreadySeeded < 0) {
			return -1;
		}
		if ($alreadySeeded > 0) {
			return 1;
		}

		$this->db->begin();

		/** @var array{code:string,role_code:string,customer_tag:string,label_key:string,coefficient:float,legacy_const:string} $row */
		foreach (CliChaumeilCommissionConfig::getDefaultCoefficientDictionaryRows() as $row) {
			$sql = 'SELECT rowid';
			$sql .= ' FROM '.$this->db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE;
			$sql .= ' WHERE entity = '.((int) $this->entity);
			$sql .= " AND code = '".$this->db->escape($row['code'])."'";

			$resql = $this->db->query($sql);
			if ($resql) {
				$exists = ($this->db->num_rows($resql) > 0);
				$this->db->free($resql);
				if ($exists) {
					continue;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' unable to inspect commission dictionary row '.$row['code'].': '.$this->error, LOG_ERR);
				return -1;
			}

			$coefficient = (float) $row['coefficient'];
			if (!empty($row['legacy_const'])) {
				$legacyValue = getDolGlobalString($row['legacy_const']);
				if ($legacyValue !== '') {
					$coefficient = (float) price2num($legacyValue);
				}
			}
			$label = $langs->transnoentitiesnoconv($row['label_key']);

			$sql = 'INSERT INTO '.$this->db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE.' (';
			$sql .= 'entity, code, role_code, customer_tag, label, coefficient, active';
			$sql .= ') VALUES (';
			$sql .= ((int) $this->entity).", ";
			$sql .= "'".$this->db->escape($row['code'])."', ";
			$sql .= "'".$this->db->escape($row['role_code'])."', ";
			$sql .= "'".$this->db->escape($row['customer_tag'])."', ";
			$sql .= "'".$this->db->escape($label)."', ";
			$sql .= "'".$this->db->escape((string) $coefficient)."', ";
			$sql .= '1';
			$sql .= ')';

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' unable to insert commission dictionary row '.$row['code'].': '.$this->error, LOG_ERR);
				return -1;
			}
		}

		if (!$this->hasCompleteDictionary()) {
			$this->db->rollback();
			$this->error = 'Commission coefficient dictionary migration is incomplete.';
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		if ($this->cleanupLegacyConstants() < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Tell whether the commission dictionary already holds rows for the current entity.
	 *
	 * @return int<-1,1> 1 if at least one row exists, 0 if empty, -1 on SQL error.
	 */
	private function dictionaryHasRows(): int
	{
		$sql = 'SELECT rowid';
		$sql .= ' FROM '.$this->db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE;
		$sql .= ' WHERE entity = '.((int) $this->entity);
		$sql .= ' LIMIT 1';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' unable to inspect commission dictionary rows: '.$this->error, LOG_ERR);
			return -1;
		}

		$hasRows = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		return $hasRows ? 1 : 0;
	}

	/**
	 * Remove legacy commission coefficient constants now replaced by the dictionary.
	 *
	 * @return int<-1,1> 1 on success, -1 on failure.
	 */
	private function cleanupLegacyConstants(): int
	{
		/** @var string $legacyConst */
		foreach (CliChaumeilCommissionConfig::getLegacyCoefficientConstantMap() as $legacyConst) {
			if (dolibarr_del_const($this->db, $legacyConst, $this->entity) <= 0) {
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' unable to delete legacy commission constant '.$legacyConst.': '.$this->error, LOG_ERR);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Check that all default commission rows exist in the dictionary.
	 *
	 * @return bool
	 */
	private function hasCompleteDictionary(): bool
	{
		$expectedCodes = array();

		/** @var array{code:string,role_code:string,customer_tag:string,label_key:string,coefficient:float,legacy_const:string} $row */
		foreach (CliChaumeilCommissionConfig::getDefaultCoefficientDictionaryRows() as $row) {
			$expectedCodes[$row['code']] = true;
		}

		$sql = 'SELECT code';
		$sql .= ' FROM '.$this->db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE;
		$sql .= ' WHERE entity = '.((int) $this->entity);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' unable to inspect commission dictionary rows: '.$this->db->lasterror(), LOG_ERR);
			return false;
		}

		$foundCodes = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$foundCodes[(string) $obj->code] = true;
		}
		$this->db->free($resql);

		foreach (array_keys($expectedCodes) as $expectedCode) {
			if (empty($foundCodes[$expectedCode])) {
				return false;
			}
		}

		return true;
	}
}
