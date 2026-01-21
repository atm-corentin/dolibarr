<?php
/* Copyright (C) 2024	Regis Houssin	<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       /multicompany/class/import.class.php
 *		\ingroup    multicompany
 *		\brief      File Class import
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

/**
 *		\class      Import
 *		\brief      Class of the module multicompany
 */
class Import extends CommonObject
{
    public $table_element = 'entity_element_import'; // !< Name of table without prefix where object is stored

	public $olddb;

	public $tablename;
	public $oldid;
	public $newid;

	public $oldrows = array();
	public $rows = array();

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 *	@param	DoliDB	$olddb	Old Database handler
	 */
	public function __construct($db, $olddb)
	{
		$this->db = $db;
		$this->olddb = $olddb;
	}

	/**
	 * Create element import
	 *
	 * @param  int     $notrigger	false = no, true = yes
	 * @return int					>= 0 if OK, < 0 if KO
	 */
	public function create($notrigger = false)
	{
		global $user;

		$error = 0;

		// Clean parameters
		$this->tablename 	= trim($this->tablename);
		$this->oldid		= ((int) $this->oldid);
		$this->newid		= ((int) $this->newid);

		dol_syslog(__METHOD__.":: element=".$this->tablename." oldid=".$this->oldid." newid=".$this->newid);

		$this->db->begin();

		$sql = "INSERT INTO ".$this->db->prefix().$this->table_element." (";
		$sql.= "element";
		$sql.= ", oldid";
		$sql.= ", newid";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->tablename)."'";
		$sql.= ", ".$this->oldid;
		$sql.= ", ".$this->newid;
		$sql.= ")";

		dol_syslog(__METHOD__.":: sql=".$sql, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			dol_syslog(__METHOD__.":: success id=".$this->id);
		} else {
			$error++;
		}

		if (empty($error) && empty($notrigger)) {
			// Call trigger
			$result = $this->call_trigger('MULTICOMPANY_IMPORT_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (empty($error)) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->error .= $this->db->lasterror();
			dol_syslog(__METHOD__.":: error ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Delete element import
	 *
	 * @param  int	$id         Id of entity to delete
	 * @param  int	$notrigger	false = no, true = yes
	 * @return int				<0 if KO, >0 if OK
	 */
	public function delete($notrigger = false)
	{
		global $user;

		$error = 0;
		$uniquekey = md5($this->tablename);

		$this->db->begin();

		if (empty($error) && empty($notrigger)) {
			// Call trigger
			$result = $this->call_trigger('MULTICOMPANY_IMPORT_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (empty($error)) {
			$sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
			$sql.= " WHERE element = '".$this->db->escape($this->tablename)."'";

			dol_syslog(__METHOD__.":: sql=".$sql, LOG_DEBUG);

			if (!$this->db->query($sql)) {
				$error++;
			}
		}

		if (empty($error)) {
			dol_syslog(__METHOD__.":: success element=".$this->tablename);
			$this->db->commit();
			clearCache('mc_entity_import_getlistbyelement_'.$uniquekey);
			return 1;
		} else {
			$this->error .= $this->db->lasterror();
			dol_syslog(__METHOD__.":: error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Get list of element
	 *
	 * @return array
	 */
	public function getListByElement()
	{
		$cache = array();
		$uniquekey = md5($this->tablename);

		if ($cache = getCache('mc_entity_import_getlistbyelement_'.$uniquekey)) {
			foreach ($cache as $key => $values) {
				$this->rows[$key] = $values;
			}

			return 1;

		} else {
			$sql = "SELECT oldid, newid";
			$sql.= " FROM ".$this->db->prefix().$this->table_element;
			$sql.= " WHERE element = '".$this->db->escape($this->tablename)."'";

			dol_syslog(__METHOD__.":: sql=".$sql, LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$this->rows[$obj->oldid] = $obj->newid;
					$cache[$obj->oldid] = $obj->newid;
				}

				setCache('mc_entity_import_getlistbyelement_'.$uniquekey, $cache);

				$this->db->free($resql);
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}
	}

	public function fetchFromOldTable($selectfields, $onlyids = false)
	{
		$cache = array();
		$uniquekey = md5($this->olddb->database_name.$this->tablename.serialize($selectfields).serialize($onlyids));

		if ($cache = getCache('mc_entity_import_fetcholdtable_'.$uniquekey)) {
			foreach ($cache as $key => $values) {
				$this->oldrows[$key] = $values;
			}

			return 1;

		} else {
			$sql = "SELECT rowid, ";
			$sql.= implode(', ', $selectfields);
			$sql.= " FROM ".$this->olddb->prefix().$this->tablename;
			if (is_array($onlyids) && !empty($onlyids)) {
				$sql.= " WHERE rowid IN (".$this->olddb->sanitize(implode(',', $onlyids)).")";
			} elseif (!empty($onlyids)) {
				$sql.= " WHERE rowid = ".((int) $onlyids);
			}

			dol_syslog(__METHOD__.":: sql=".$sql, LOG_DEBUG);

			$resql = $this->olddb->query($sql);
			if ($resql) {
				if ($this->olddb->num_rows($resql)) {
					$i = 0;
					while ($obj = $this->olddb->fetch_object($resql)) {
						$this->oldrows[$i]['rowid'] = $obj->rowid;
						$cache[$i]['rowid'] = $obj->rowid;
						foreach($selectfields as $field) {
							$this->oldrows[$i][$field] = $obj->$field;
							$cache[$i][$field] = $obj->$field;
						}
						$i++;
					}

					setCache('mc_entity_import_fetcholdtable_'.$uniquekey, $cache);

					return 1;
				} else {
					return 0;
				}

				$this->olddb->free($resql);

			} else {
				$this->error .= $this->olddb->lasterror();
				return -1;
			}
		}
	}

	/**
	 *
	 * @param string $table	table name without prefix (eg. llx_)
	 */
	public function copyTable($newentity, $importkey = false, $onlyids = false, $notrigger = false)
	{
		global $user;

		$error = 0;

		$removefields = array('rowid', 'entity', 'import_key');

		$newfields = getFieldsFromTable($this->db, $this->tablename, false, $removefields);
		$oldfields = getFieldsFromTable($this->olddb, $this->tablename, false, $removefields);
		$selectfields = array_intersect($newfields, $oldfields);

		$insertfields = getFieldsFromTable($this->db, $this->tablename, true, $removefields);

		dol_syslog(__METHOD__.":: table=".$this->tablename." newentity=".$newentity);

		$ret = $this->fetchFromOldTable($selectfields, $onlyids);
		if ($ret > 0) {
			if (!empty($this->oldrows)) {
				$this->db->begin();

				foreach ($this->oldrows as $oldrows) {

					$sqlinsert = "INSERT INTO ".$this->db->prefix().$this->tablename." (";
					$sqlinsert.= "entity, ";
					if (!empty($importkey)) {
						$sqlinsert.= "import_key, ";
					}
					$sqlinsert.= implode(', ', $selectfields);
					$sqlinsert.= ") VALUES (";
					$sqlinsert.= ((int) $newentity).", ";
					if (!empty($importkey)) {
						$sqlinsert.= "'".$this->db->escape($importkey)."', ";
					}

					foreach ($insertfields as $fieldname => $fieldtype) {
						if (!in_array($fieldname, $selectfields)) {
							continue;
						}
						if (preg_match('/int/', $fieldtype)) {
							$sqlinsert.= (($oldrows[$fieldname] > 0 || $oldrows[$fieldname] != '') ? ((int) $oldrows[$fieldname]) : "null");
						} elseif (preg_match('/date/', $fieldtype)) {
							$sqlinsert.= (strval($oldrows[$fieldname]) != '' ? "'".$this->db->idate($this->db->jdate($oldrows[$fieldname]))."'" : "null");
						} else {
							$sqlinsert.= ($oldrows[$fieldname] != '' ? "'".$this->db->escape($oldrows[$fieldname])."'" : "null");
						}
						if (end($selectfields) != $fieldname) {
							$sqlinsert.= ", ";
						}
					}

					$sqlinsert.= ")";

					dol_syslog(__METHOD__."::insert sql=".$sqlinsert, LOG_DEBUG);

					$resqlinsert = $this->db->query($sqlinsert);
					if ($resqlinsert) {

						$this->rows[$oldrows['rowid']] = $this->db->last_insert_id($this->db->prefix().$this->tablename);

						dol_syslog(__METHOD__."::insert success oldid=".$oldrows['rowid']." newid=".$this->rows[$oldrows['rowid']]);

					} else {
						$error++;
						$this->error .= $this->db->lasterror();
						break;
					}
				}

				if (empty($error) && empty($notrigger)) {
					// Call trigger
					$result = $this->call_trigger('MULTICOMPANY_IMPORT_COPY_TABLE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error)) {
					$this->db->commit();
					return 1;
				} else {
					dol_syslog(__METHOD__.":: error ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			} else {
				return -2;
			}
		} else {
			return -3;
		}
	}
}
