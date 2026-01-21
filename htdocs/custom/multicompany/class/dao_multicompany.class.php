<?php
/* Copyright (C) 2009-2024	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot		<herve.prot@symeos.com>
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
 *		\file       /multicompany/dao_multicompany.class.php
 *		\ingroup    multicompany
 *		\brief      File Class multicompany
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

/**
 *		\class      DaoMulticompany
 *		\brief      Class of the module multicompany
 */
#[\AllowDynamicProperties]
class DaoMulticompany extends CommonObject
{
    public $element = 'entity'; // !< Id that identify managed objects
    public $table_element = 'entity'; // !< Name of table without prefix where object is stored

	public $id;
	public $label;
	public $description;
	public $url;

	public $address;
	public $zip;
	public $town;

	public $currency_code;
	public $language_code;

	public $options=array();
	public $options_json;

	public $entity=array();
	public $entities=array();

	public $fk_tables=array();

	public $visible;
	public $active;
	public $currency;
	public $language;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->fk_tables = array(
			'societe' => array(
				'key' => 'fk_soc',
				'childs' => array(
					'societe_address',
					'societe_commerciaux',
					'societe_log',
					'societe_prices',
					'societe_remise',
					'societe_remise_except',
					'societe_rib',
					'socpeople'
				)
			),
			'product' => array(
				'key' => 'fk_product',
				'childs' => array(
					'product_ca',
					'product_lang',
					'product_price',
					'product_stock',
					'product_fournisseur_price' => array(
						'key' => 'fk_product_fournisseur',
						'childs' => array('product_fournisseur_price_log')
					),
				)
			),
			'projet' => array(
				'key' => 'fk_projet',
				'childs' => array(
					'projet_task' => array(
						'key' => 'fk_task',
						'childs' => array('projet_task_time')
					)
				)
			)
		);
	}

	/**
	 * Fetch entity
	 *
	 * @param int $id
	 * @return int
	 */
	public function fetch($id = null, $url = null)
	{
		global $user;

		//clearCache('mc_entity_' . $id); // for debug only!
		if ($cache = getCache('mc_entity_' . $id)) {
			foreach ($cache as $key => $value) {
				$this->$key = $value;
			}
		} else {
			$sql = "SELECT rowid, label, description, url, options, visible, active";
			$sql.= " FROM ".MAIN_DB_PREFIX."entity";
			if (!empty($url)) {
				$sql.= " WHERE url = '".$this->db->escape($url)."'";
			} else {
				$sql.= " WHERE rowid = ".((int) $id);
			}

			$resql = $this->db->query($sql);
			if (!empty($resql)) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);

					$this->id			= $obj->rowid;
					$this->label		= $obj->label;
					$this->description 	= $obj->description;
					$this->url			= $obj->url;
					$this->options		= (!empty($obj->options) ? json_decode($obj->options, true) : array());
					$this->visible 		= $obj->visible;
					$this->active		= $obj->active;

					$this->fetch_optionals();

					$cache = array(
						'id'			=> $this->id,
						'label'			=> $this->label,
						'description'	=> $this->description,
						'url'			=> $this->url,
						'options'		=> $this->options,
						'visible'		=> $this->visible,
						'active'		=> $this->active,
						'array_options'	=> $this->array_options
					);

					setCache('mc_entity_' . $this->id, $cache);
				} else {
					$this->error .= $this->db->lasterror();
					return -2;
				}
				$this->db->free($resql);
			} else {
				$this->error .= $this->db->lasterror();
				return -3;
			}
		}

		if (!empty($user->login)) {
			$this->getConstants();
		}

		return 1;
	}

	/**
	 * Create entity
	 *
	 * @param  User    $user           Object of user that ask creation
	 * @param  int     $call_trigger   false = no, true = yes
	 * @return int                     >= 0 if OK, < 0 if KO
	 */
	public function create(User $user, $call_trigger = true)
	{
		global $conf;

		$error=0;

		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->url			= trim($this->url);
		$this->options_json = json_encode($this->options);

		dol_syslog(get_class($this)."::create ".$this->label);

		$this->db->begin();

		$now=dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entity (";
		$sql.= "label";
		$sql.= ", description";
		$sql.= ", url";
		$sql.= ", datec";
		$sql.= ", fk_user_creat";
		$sql.= ", options";
		$sql.= ", visible";
		$sql.= ", active";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->description)."'";
		$sql.= ", ".(!empty($this->url) ? "'".$this->db->escape($this->url)."'" : "null");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", '".$this->db->escape($this->options_json)."'";
		$sql.= ", ".(!empty($this->visible) ? $this->db->escape($this->visible) : 0);
		$sql.= ", ".(!empty($this->active) ? $this->db->escape($this->active) : 0);
		$sql.= ")";

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."entity");

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			}

			dol_syslog(get_class($this)."::Create success id=".$this->id);
		} else {
			$error++;
		}

		if (empty($error) && $call_trigger) {
		    // Call trigger
		    $result = $this->call_trigger('MULTICOMPANY_CREATE', $user);
		    if ($result < 0) $error++;
		    // End call triggers
		}

		if (empty($error)) {
			$this->db->commit();
            return $this->id;
		} else {
			$this->error .= $this->db->lasterror();
			dol_syslog(get_class($this)."::Create echec ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Update entity
	 *
	 * @param  int     $id             Id of entity (deprecated, use 0 here and call update on an object loaded by a fetch)
	 * @param  User    $user           User who requests the update
	 * @param  int     $call_trigger   false = no, true = yes
	 * @return int                     <0 if KO, >=0 if OK
	 */
	public function update($id, User $user, $call_trigger = true)
	{
		global $conf;

		$error=0;

		if (empty($id)) {
			$id = $this->id;
		}

		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->url			= trim($this->url);
		$this->options_json = json_encode($this->options);

		dol_syslog(get_class($this)."::update id=".$id." label=".$this->label);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."entity SET";
		$sql.= " label = '" . $this->db->escape($this->label) ."'";
		$sql.= ", description = '" . $this->db->escape($this->description) ."'";
		$sql.= ", url = ".(!empty($this->url) ? "'".$this->db->escape($this->url)."'" : "null");
		$sql.= ", options = '" . $this->db->escape($this->options_json) ."'";
		$sql.= " WHERE rowid = " . $id;

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			dol_syslog(get_class($this)."::Update success id=".$id);
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			}
		} else {
			$error++;
		}

		if (empty($error) && $call_trigger) {
		    // Call trigger
		    $result = $this->call_trigger('MULTICOMPANY_MODIFY', $user);
		    if ($result < 0) $error++;
		    // End call triggers
		}

		if (empty($error)) {
			$this->db->commit();
			clearCache('mc_entity_' . $id);
			clearCache('mc_constants_' . $id);
            return 1;
		} else {
			$this->error .= $this->db->lasterror();
			dol_syslog(get_class($this)."::Update echec ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Delete entity
	 *
	 * @param  int     $id             Id of entity to delete
	 * @param  int     $call_trigger   false = no, true = yes
	 * @return int                     <0 if KO, >0 if OK
	 */
	public function delete($id, $call_trigger = true)
	{
	    global $user;

		$error=0;

		$this->db->begin();

		if (empty($error) && !empty($call_trigger)) {
		    // Call trigger
		    $result = $this->call_trigger('MULTICOMPANY_DELETE', $user);
		    if ($result < 0) $error++;
		    // End call triggers
		}

		if (empty($error)) {
		    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		    $sql.= " WHERE entity = " . ((int) $id);
		    dol_syslog(get_class($this)."::Delete sql=".$sql, LOG_DEBUG);
		    if ($this->db->query($sql)) {
		    	$this->deleteEntityRecords(((int) $id));
		    } else {
		        $error++;
		        $this->error .= $this->db->lasterror();
		        dol_syslog(get_class($this)."::Delete erreur -1 ".$this->error, LOG_ERR);
		    }
		}

		// Removed extrafields
		if (empty($error)) {
			$this->id = ((int) $id);
		    $result = $this->deleteExtraFields();
		    if ($result < 0) {
		        $error++;
		        dol_syslog(get_class($this)."::delete error -2 ".$this->error, LOG_ERR);
		    }
		}

		if (empty($error)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."entity";
			$sql.= " WHERE rowid = " . ((int) $id);
			dol_syslog(get_class($this)."::Delete sql=".$sql, LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$error++;
				$this->error .= $this->db->lasterror();
				dol_syslog(get_class($this)."::Delete erreur -1 ".$this->error, LOG_ERR);
			}
		}

		if (empty($error)) {
			dol_syslog(get_class($this)."::Delete success id=".$id);
			$this->db->commit();
			clearCache('mc_entity_' . $id);
			clearCache('mc_constants_' . $id);
            return 1;
		} else {
			dol_syslog(get_class($this)."::Delete echec ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *
	 */
	public function getConstants()
	{
		// Unset when use multifetching
		unset($this->country_id);
		unset($this->country_code);
		unset($this->currency_code);
		unset($this->language_code);
		unset($this->name);
		unset($this->zip);
		unset($this->address);
		unset($this->town);
		unset($this->state_id);

		$cachekey = 'mc_constants_' . $this->id;
		//clearCache($cachekey); // for debug only!

		if ($cache = getCache($cachekey)) {
			foreach ($cache as $key => $value) {
				$this->$key = $value;
			}
		} else {
			$cache=array();

			$sql = "SELECT ";
			$sql.= $this->db->decrypt('name')." as name";
			$sql.= ", ".$this->db->decrypt('value')." as value";
			$sql.= " FROM ".MAIN_DB_PREFIX."const";
			$sql.= " WHERE entity = ".$this->id;
			$sql.= " AND ".$this->db->decrypt('name')." LIKE 'MAIN_%'";

			$resql = $this->db->query($sql);
			if (!empty($resql)) {
				$num=$this->db->num_rows($resql);
				$i=0;

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					if ($obj->name === 'MAIN_INFO_SOCIETE_COUNTRY') {
						$tmp = explode(':', $obj->value);
						$this->country_id	= $tmp[0];
						$cache['country_id'] = $this->country_id;
						$this->country_code	= $tmp[1];
						$cache['country_code'] = $this->country_code;
					} elseif ($obj->name === 'MAIN_MONNAIE') {
						$this->currency_code = $obj->value;
						$cache['currency_code'] = $this->currency_code;
					} elseif ($obj->name === 'MAIN_LANG_DEFAULT') {
						$this->language_code = $obj->value;
						$cache['language_code'] = $this->language_code;
					} elseif ($obj->name === 'MAIN_INFO_SOCIETE_NOM') {
						$this->name	= $obj->value;
						$cache['name'] = $this->name;
					} elseif ($obj->name === 'MAIN_INFO_SOCIETE_ZIP') {
						$this->zip = $obj->value;
						$cache['zip'] = $this->zip;
					} elseif ($obj->name === 'MAIN_INFO_SOCIETE_ADDRESS') {
						$this->address = $obj->value;
						$cache['address'] = $this->address;
					} elseif ($obj->name === 'MAIN_INFO_SOCIETE_TOWN') {
						$this->town = $obj->value;
						$cache['town'] = $this->town;
					} elseif ($obj->name === 'MAIN_INFO_SOCIETE_STATE') {
						$this->state_id	= $obj->value;
						$cache['state_id'] = $this->state_id;
					}

					$constname = $obj->name;
					$this->$constname = $obj->value;
					$cache[$constname] = $this->$constname;

					$i++;
				}

				setCache($cachekey, $cache);
				$this->db->free($resql);

			} else {
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Get list of all dictionaries of the entity
	 *
	 * @param int $active		Get only active values of each dictionary (-1: ALL  0: only inactive  1: only active)
	 * @return 	array 	Array of dictionaries with their rows for the current entity
	 */
	public function getDictionaries($active = -1)
	{
		$dictionaries = [];

		// We cannot use the $this->db->DDLListTables() then $this->db->DDLInfoTable() because the regex used to select "table like" remove the "_" escape
		// So we are getting all tables beginning with $this->db->prefix()."c", instead of only dictionaries with $this->db->prefix()."c_"
		$sql = "SELECT distinct t.table_name as t_name, c.column_name as c_name";
		$sql .= " FROM information_schema.tables t";
		$sql .= " 	JOIN information_schema.columns c ON t.table_name = c.table_name";
		$sql .= " WHERE t.table_schema = '".$this->db->database_name."'";
		$sql .= " 	AND c.table_schema = '".$this->db->database_name."'";
		$sql .= " 	AND t.table_name LIKE '".$this->db->prefix()."c\_%'";
		$sql .= " ORDER BY t.table_name ASC, c.column_name ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			for ($i=0 ; $i<$this->db->num_rows($resql); $i++) {
				$obj = $this->db->fetch_object($resql);
				if ($obj->c_name !== "rowid" && $obj->c_name !== "id" && $obj->c_name !== "entity") {
					$dictionaries[$obj->t_name]['columnsName'][] = $obj->c_name;
				}
			}

			foreach ($dictionaries as $dictionary => $value) {
				$sql = "SELECT ";

				foreach ($value['columnsName'] as $index => $column) {
					$sql .= " ".$column.($index+1 === count($value['columnsName']) ? '' : ',');
				}

				$sql .= " FROM ".$this->db->escape($dictionary). " t";
				$sql .= " WHERE t.entity = ".$this->id;
				$sql .= ($active === 1) ? " AND t.active = 1" : (($active === 0) ? " AND t.active = 0" : "");

				$resql = $this->db->query($sql);
				if ($resql) {
					for ($i=0 ; $i<$this->db->num_rows($resql); $i++) {
						$obj = $this->db->fetch_object($resql);
						$dictionaries[$dictionary]['rows'][] = $obj;
					}
				}
			}
		}

		return $dictionaries;
	}

	/**
	 * @param string $dictionary	The dictionary table name
	 * @param array $columnsName	Array of columns name of the dictionary
	 * @param array $rows			Array of row to insert in the dictionary
	 * @return int                  <0 if KO, >=0 if OK
	 */
	public function addDictionary($dictionary, $columnsName, $rows) {
		$sql = "REPLACE INTO ".$this->db->escape($dictionary)." (entity";

		foreach ($columnsName as $column) {
			$sql .= ", ".$this->db->escape($column);
		}
		$sql .= ") VALUES ";
		foreach ($rows as $index => $row) {
			$sql .= "(".$this->db->escape($this->id);
			foreach ($columnsName as $column) {
				$sql .= ", '".$this->db->escape($row->{$column})."'";
			}
			$sql .= ")".($index+1 === count($rows) ? '' : ',');
		}
		$sql .= ";";

		$resql = $this->db->query($sql);

		if (!$resql) {
			return -1;
		}

		return 0;
	}

	/**
	 * Get list of all document models of the entity
	 *
	 * @return array Array of document models
	 */
	public function getDocumentModels() {
		$documentModels = [];

		$sql = "SELECT nom, type, libelle, description";
		$sql .= " FROM ".$this->db->prefix()."document_model";
		$sql .= " WHERE entity = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql) {
			for ($i=0 ; $i<$this->db->num_rows($resql); $i++) {
				$obj = $this->db->fetch_object($resql);
				$documentModels[] = $obj;
			}
		}

		return $documentModels;
	}

	/**
	 * @param array $documentModels Array of document models
	 * @return int 					<0 if KO, >=0 if ok
	 */
	public function addDocumentModels($documentModels) {
		// Use REPLACE instead of INSERT to prevent sql error on default document models already inserted
		$sql = "REPLACE INTO ".$this->db->prefix()."document_model (entity, nom, type, libelle, description) VALUES ";
		foreach ($documentModels as $index => $documentModel) {
			$sql .= "(".$this->id;
			foreach ($documentModel as $field) {
				$sql .= ", '".$this->db->escape($field)."'";
			}
			$sql .= ")".($index+1 === count($documentModels) ? '' : ',');
		}
		$resql = $this->db->query($sql);

		if (!$resql) {
			return -1;
		}

		return 0;
	}

	/**
	 *	Remove all records of an entity
	 *
	 *	@param	int		$id		Entity id
	 *	@return	int
	 */
	private function deleteEntityRecords($id)
	{
		$error = 0;
		$debug = '';

		$this->db->begin();

		$tables = $this->db->DDLListTables($this->db->database_name);
		if (is_array($tables) && !empty($tables)) {
			foreach($tables as $table) {
				$tablewithoutprefix = str_replace(MAIN_DB_PREFIX, '', $table);

				// llx_user_param have not "rowid" or "id"
				if ($tablewithoutprefix === 'user_param') {
					continue;
				}

				$fields = $this->db->DDLInfoTable($table);
				foreach ($fields as $field) {
					if (is_array($field) && in_array('entity', $field)) {
						$rowIdColumnName = in_array('rowid', $fields[0]) ? 'rowid' : 'id';
						$objIds = $this->getIdByForeignKey($tablewithoutprefix, $id, 'entity', $rowIdColumnName);

						if ($objIds === -1) {
							$error++;
							$this->error .= $this->db->lasterror();
							dol_syslog(get_class($this)."::deleteEntityRecords error -1 " . $this->error, LOG_ERR);
							continue;
						}
						if (!empty($objIds)) {
							if (array_key_exists($tablewithoutprefix, $this->fk_tables)) {
								// Level 0
								$foreignKey = $this->fk_tables[$tablewithoutprefix]['key'];
								foreach($this->fk_tables[$tablewithoutprefix]['childs'] as $childTable => $child) {
									// Level 1
									if (!is_int($childTable) && is_array($child)) {
										echo 'childTableLevel1='.$childTable.'<br>';
										$objLevel1Ids = array();
										foreach($objIds as $rowid) {
											$ret = $this->getIdByForeignKey($childTable, $rowid, $foreignKey);
											if (!empty($ret)){
												$objLevel1Ids = array_merge($objLevel1Ids, $ret);
											}
										}

										sort($objLevel1Ids);
										//var_dump($objLevel1Ids);

										// Level 2
										foreach($child['childs'] as $childLevel2) {
											echo 'childTableLevel2='.$childLevel2.'<br>';
											foreach($objLevel1Ids as $rowid) {
												$sql = "DELETE FROM " . MAIN_DB_PREFIX . $childLevel2;
												$sql.= " WHERE " . $child['key'] . " = " . $rowid;
												//echo $sql.'<br>';
												//dol_syslog(get_class($this)."::deleteEntityRecords sql=" . $sql, LOG_DEBUG);
												/*if (!$this->db->query($sql)) {
												 $error++;
												$this->error .= $this->db->lasterror();
												dol_syslog(get_class($this)."::deleteEntityRecords error -1 " . $this->error, LOG_ERR);
												}*/
											}
										}

										foreach($objIds as $rowid) {
											$sql = "DELETE FROM " . MAIN_DB_PREFIX . $childTable;
											$sql.= " WHERE " . $foreignKey . " = " . $rowid;
											//echo $sql.'<br>';
											//dol_syslog(get_class($this)."::deleteEntityRecords sql=" . $sql, LOG_DEBUG);
											/*if (!$this->db->query($sql)) {
											 $error++;
											$this->error .= $this->db->lasterror();
											dol_syslog(get_class($this)."::deleteEntityRecords error -1 " . $this->error, LOG_ERR);
											}*/
										}
									} else {
										foreach($objIds as $rowid) {
											$sql = "DELETE FROM " . MAIN_DB_PREFIX . $child;
											$sql.= " WHERE " . $foreignKey . " = " . $rowid;
											//echo $sql.'<br>';
											//dol_syslog(get_class($this)."::deleteEntityRecords sql=" . $sql, LOG_DEBUG);
											/*if (!$this->db->query($sql)) {
											 $error++;
											$this->error .= $this->db->lasterror();
											dol_syslog(get_class($this)."::deleteEntityRecords error -1 " . $this->error, LOG_ERR);
											}*/
										}
									}
								}
								$debug.= 'with childs = '.$table."\n";
							} else {
								$sql = "DELETE FROM " . MAIN_DB_PREFIX . $tablewithoutprefix;
								$sql .= " WHERE ". $rowIdColumnName ." IN (";
								$sql .= join(", ", $objIds);
								$sql .= ");";
								dol_syslog(get_class($this)."::deleteEntityRecords sql=" . $sql, LOG_DEBUG);

								if (!$this->db->query($sql)) {
									$error++;
									$this->error .= $this->db->lasterror();
									dol_syslog(get_class($this)."::deleteEntityRecords error -1 " . $this->error, LOG_ERR);
								}

								$debug.= 'without childs = '.$table."\n";
							}
						}
					}
				}
			}

			dol_syslog(get_class($this)."::deleteEntityRecords debug=" . $debug, LOG_DEBUG);

			if (empty($error)) {
				dol_syslog(get_class($this)."::deleteEntityRecords success entity=".$id);
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::deleteEntityRecords echec ".$this->error);
				$this->db->rollback();
				return -1;
			}
		}
		return 0;
	}

	/**
	 * Get all rowid from a table by couple foreign key / id
	 *
	 * @param string $table
	 * @param int $id
	 * @param string $foreignkey
	 * @param string $fieldname
	 * @return int[]
	 */
	private function getIdByForeignKey($table, $id, $foreignkey = 'entity', $fieldname = 'rowid')
	{
		$objIds=array();
		$foreignkey = (!empty($foreignkey) ? $foreignkey : 'entity');
		$fieldname = (!empty($fieldname) ? $fieldname : 'rowid');

		$sql = "SELECT " . $fieldname . " FROM " . MAIN_DB_PREFIX . $table;
		$sql.= " WHERE " . $foreignkey . " = " . $id;
		//echo $sql.'<br>';
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$objIds[] = $obj->$fieldname;
				$i++;
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $objIds;
	}

	/**
	 *    Set status of an entity
	 *
	 * @param    int $id			Id of entity
	 * @param    string $type	Type of status (visible or active)
	 * @param    string $value	Value of status (0: disable, 1: enable)
	 * @return int
	 */
	public function setEntity($id, $type='active', $value)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."entity";
		$sql.= " SET " . $this->db->escape($type) . " = " . (int) $value;
		$sql.= " WHERE rowid = " . (int) $id;

		dol_syslog(get_class($this)."::setEntity sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!empty($resql))	{
			$this->db->commit();
			clearCache($id);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	List of entities
	 *
	 *	@param		int		$login			If use in login page or not
	 *	@param		array	$exclude		Entity ids to exclude
	 *	@param		bool	$onlyactive		sort only active entities
	 *	@param		bool	$onlyvisible	sort only visible entities
	 *	@param		int		$onlyids		Return only ids of objects (consume less memory)
	 *	@return		void
	 */
	public function getEntities($login = false, $exclude = false, $onlyactive = false, $onlyvisible = false, $onlyids = false)
	{
		global $conf, $user;

		$this->entities=array();

		if (!empty($login) || empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) || (!empty($user->admin) && empty($user->entity))) {
			$sql = "SELECT DISTINCT(rowid), rang"; // Distinct parce que si user dans plusieurs groupes d'une entité, la liste d'entités de la petite terre affiche plusieurs fois la même entité
			$sql.= " FROM ".MAIN_DB_PREFIX."entity";
			if (!empty($user->admin) && empty($user->entity) && is_array($exclude) && !empty($exclude)) {
				$exclude = implode(",", $exclude);
				$sql.= " WHERE rowid NOT IN (" . $exclude .")";
				if (!empty($onlyactive)) {
					$sql.= " AND active = 1";
				}
			} elseif (!empty($onlyactive)) {
				$sql.= " WHERE active = 1";
			}
			if (!empty($onlyvisible)) {
				$sql.= " AND visible = 1";
			}
			if (empty($login)) {
				$sql.= " ORDER BY rowid";
			} else {
				$sql.= " ORDER BY rang ASC, rowid ASC";
			}
		} else {
			$sql = "SELECT DISTINCT(entity) as rowid"; // Distinct parce que si user dans plusieurs groupes d'une entité, la liste d'entités de la petite terre affiche plusieurs fois la même entité
			$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_user";
			$sql.= " WHERE fk_user = ".$user->id;
			$sql.= " ORDER BY entity";
		}

		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if (empty($onlyids)) {
					$objectstatic = new self($this->db);
					$ret = $objectstatic->fetch($obj->rowid);
					$this->entities[$i] = $objectstatic;
				} else {
					$this->entities[$i] = $obj->rowid;
				}

				$i++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Check user $userid belongs to at least one group created into entity $id
	 *
	 * @param int $entity
	 * @param int $userid
	 * @return int
	 */
	public function verifyRight($entity, $userid)
	{
		global $conf;

		$tmpuser=new User($this->db);
		$tmpuser->fetch($userid);
		//$tmpuser->fetch($userid, '', '',0, $entity); // TODO check compatibility with DAV authentication

		if (!empty($tmpuser->id)) {
			if (empty($tmpuser->entity)) {
				return 1;	// superadmin always allowed
			}
			if ($tmpuser->entity == $entity && $tmpuser->admin) {
				return 1;	// entity admin allowed
			}
			if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				if  ($tmpuser->entity == $entity) {
					return 1;	// user allowed if belong to entity
				}
			} else {
				$sql = "SELECT count(rowid) as nb";
				$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_user";
				$sql.= " WHERE fk_user=".$userid;
				$sql.= " AND entity=".$entity;
				//echo $sql;

				dol_syslog(get_class($this)."::verifyRight sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!empty($resql)) {
					$obj = $this->db->fetch_object($resql);
					$this->db->free($resql);
					return $obj->nb;	// user allowed if at least in one group
				} else {
					$this->error = $this->db->lasterror();
					return -1;
				}
			}
		}

		return 0;
	}

	/**
	 * 	Get constants values of an entity
	 *
	 * 	@param	int		$entity		Entity id
	 *  @param	string	$constname	Specific contant
	 * 	@return array				Array of constants
	 */
	public function getEntityConfig($entity, $constname=null)
	{
		$const=array();

		$sql = "SELECT ".$this->db->decrypt('value')." as value";
		$sql.= ", ".$this->db->decrypt('name')." as name";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE entity = " . $entity;
		if (!empty($constname)) {
			if (preg_match('/\_\*$/', $constname)) {
				$constname = str_replace('*', '', $constname);
				$sql.= " AND ".$this->db->decrypt('name')." LIKE '" . $this->db->escape($constname) ."%'";
			} else {
				$sql.= " AND ".$this->db->decrypt('name')." = '" . $this->db->escape($constname) ."'";
			}
		}

		dol_syslog(get_class($this)."::getEntityConfig sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$const[$obj->name] = $obj->value;
				$i++;
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $const;
	}

	/**
	 * Get group rights by entity
	 *
	 * @param int $group
	 * @param int $entity
	 * @return array[]
	 */
	public function getGroupRightsByEntity($group, $entity)
	{
		$permsgroupbyentity = array();

		$sql = "SELECT DISTINCT r.id, r.libelle, r.module, gr.entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
		$sql.= " ".MAIN_DB_PREFIX."usergroup_rights as gr";
		$sql.= " WHERE gr.fk_id = r.id";
		$sql.= " AND gr.entity = " . (int) $entity;
		$sql.= " AND gr.fk_usergroup = " . (int) $group;

		dol_syslog(get_class($this)."::getGroupRightsByEntity sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				array_push($permsgroupbyentity, $obj->id);
				$i++;
			}

			$this->db->free($resql);
			return $permsgroupbyentity;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Get list of groups
	 *
	 * @return array
	 */
	public function getListOfGroups()
	{
		$ret=array();

		$sql = "SELECT g.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		$sql.= " GROUP BY g.rowid";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$ret[] = $obj->rowid;
			}

			$this->db->free($resql);
			return $ret;
		} else {
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *
	 * @param unknown $groupid
	 * @param unknown $template
	 * @return User[]|number
	 */
	public function getListOfUsersInGroupByTemplate($groupid, $template)
	{
		$ret=array();

		$sql = "SELECT u.rowid, ug.entity as usergroup_entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE ug.fk_user = u.rowid";
		$sql.= " AND ug.fk_usergroup = " . (int) $groupid;
		$sql.= " AND ug.entity = " . (int) $template;

		dol_syslog(get_class($this)."::getListOfUsersInGroupByTemplate groupid=".$groupid." template=".$template, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					$newuser=new User($this->db);
					$newuser->fetch($obj->rowid);
					$ret[$obj->rowid]=$newuser;
				}
			}

			$this->db->free($resql);
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *
	 *  @param	string	$element			Name of element (thirdparty, product, ...)
	 *  @param	int		$element_id			Id of current element for check sharing granularity (if empty, all element sharing records are deleted for once entity)
	 *  @param	mixed	$entity				Array of sharing entities (create/update/delete) or integer of entity (delete entity)
	 *  @return	bool
	 *
	 */
	public function setSharingsByElement($element, $element_id = null, $entity = null)
	{
		$error = 0;

		// For avoid to delete all elements
		if (empty($element_id) && !is_int($entity)) {
			dol_syslog(get_class($this)."::setSharingsByElement erreur -2 element_id are empty and entity is not an integer", LOG_ERR);
			return -2;
		}

		$this->db->begin();

		// Delete before create/update elements
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."entity_element_sharing";
		$sql.= " WHERE element = '" . $this->db->sanitize($element) . "'";
		if (!empty($element_id)) {
			$sql.= " AND fk_element = " . (int) $element_id;
		} elseif (is_int($entity) && $entity > 0) {
			$sql.= " AND entity = " . (int) $entity;
		}

		dol_syslog(get_class($this)."::setSharingsByElement delete sql=".$sql, LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$error++;
		}

		if (empty($error) && !empty($element_id) && is_array($entity) && !empty($entity)) {
			foreach($entity as $sharedwithentity) {
				dol_syslog(get_class($this)."::setSharingsByElement create element=".$element." fk_element=".$element_id." sharedwithentity=".$sharedwithentity);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."entity_element_sharing (";
				$sql.= "entity";
				$sql.= ", element";
				$sql.= ", fk_element";
				$sql.= ") VALUES (";
				$sql.= (int) $sharedwithentity;
				$sql.= ", '".$this->db->escape($element)."'";
				$sql.= ", ". (int) $element_id;
				$sql.= ")";

				dol_syslog(get_class($this)."::setSharingsByElement create sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!empty($resql)) {
					dol_syslog(get_class($this)."::setSharingsByElement create success id=" . $this->db->last_insert_id(MAIN_DB_PREFIX."entity_element_sharing"));
				} else {
					$error++;
					break;
				}
			}
		}

		if (empty($error)) {
			dol_syslog(get_class($this)."::setSharingsByElement success");
			$this->db->commit();
			return 1;
		} else {
			$this->error.= $this->db->lasterror();
			dol_syslog(get_class($this)."::setSharingsByElement echec ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *  @param	string	$element			Name of element (thirdparty, product, ...)
	 *  @param	int		$element_id			Id of current element for check sharing granularity
	 *  @param	int		$entity				Entity to search
	 *
	 */
	public function getListOfSharingsByElement($element, $element_id = null, $entity = null, $checkaccess = false)
	{
		global $conf;

		$ret = array();
		$searchentity = (!empty($entity) ? (int) $entity : null);

		dol_syslog(get_class($this)."::getListOfSharingsByElement element=".$element." fk_element=".$element_id." entity=".$searchentity, LOG_DEBUG);

		if (isSharingAllByDefault($element) && !empty($element_id)) {

			$notshared = array();

			$sql = "SELECT entity";
			$sql.= " FROM ".MAIN_DB_PREFIX."entity_element_sharing";
			$sql.= " WHERE element = '" . $this->db->sanitize($element) . "'";
			$sql.= " AND fk_element = " . (int) $element_id;

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$notshared[] = $obj->entity;
				}

				$this->db->free($resql);

				$newelement = ($element == 'contact' ? 'thirdparty' : $element);

				if ($checkaccess === true) {
					$onlysharingentities = explode(",", getEntity($newelement));
				} else {
					$allentities = new self($this->db);
					$allentities->getEntities(true, array($conf->entity), true);

					$onlysharingentities = array();
					foreach ($allentities->entities as $otherentity) {
						if (isset($otherentity->options['sharings'][$newelement]) && in_array($conf->entity, $otherentity->options['sharings'][$newelement])) {
							$onlysharingentities[] = $otherentity->id;
						}
					}

					sort($onlysharingentities);
				}

				if (is_array($onlysharingentities) && !empty($onlysharingentities)) {
					if (!empty($notshared)) {
						$onlysharingentities = array_diff($onlysharingentities, $notshared);
					}
					foreach ($onlysharingentities as $fk_entity) {
						$ret[$element_id][] = $fk_entity;
					}
				}

				return $ret;

			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}

		} else {

			$sql = "SELECT rowid, entity, element, fk_element";
			$sql.= " FROM ".MAIN_DB_PREFIX."entity_element_sharing";
			$sql.= " WHERE element = '" . $this->db->sanitize($element) . "'";
			if (!empty($searchentity)) {
				$sql.= " AND entity = " . (int) $searchentity;
			}
			if (!empty($element_id)) {
				$sql.= " AND fk_element = " . (int) $element_id;
			}
		}

		dol_syslog(get_class($this)."::getListOfSharingsByElement sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$ret[$obj->fk_element][] = $obj->entity;
				/*$ret[$obj->fk_element]['element'] = $element;
				$ret[$obj->fk_element]['fk_element'] = $obj->fk_element;
				$ret[$obj->fk_element]['entity'][] = $obj->entity;*/

			}

			$this->db->free($resql);

			if (empty($ret) && empty($element_id) && !empty($searchentity)) {
				$ret[] = 0;
			}

			return $ret;

		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

}
