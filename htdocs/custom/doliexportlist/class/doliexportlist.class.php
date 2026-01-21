<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/myclass.class.php
 * \ingroup mymodule
 * \brief   Example CRUD (Create/Read/Update/Delete) class.
 *
 * Put detailed description here.
 */

/** Includes */
//require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";

/**
 * Put your class' description here
 */
class DoliExportList// extends CommonObject
{

    /** @var DoliDb Database handler */
	public $db;
    /** @var string Error code or message */
	public $error;
    /** @var array Several error codes or messages */
	public $errors = array();
    /** @var string Id to identify managed object */
	public $element='list';
    /** @var string Name of table without prefix where object is stored */
	public $table_element='doliexportlist_format';
    /** @var string Id to identify managed object */
	public $picto='doliexportlist@doliexportlist';
    /** @var int An example ID */
	public $formats = array();
        

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		return 1;
	}
        
        /**
	 * Load object in memory from database
	 *
	 * @param int $id Id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function getFormats($type='', $only_active=1)
	{
		$sql = "SELECT t.rowid, t.format, t.description, t.type, t.title, t.warning, t.picto, t.position, t.active";
		$sql.= " FROM " . MAIN_DB_PREFIX . "doliexportlist_format as t";
                if (! empty($type)) $sql.= " WHERE t.type = '" . $type . "'";
		if ($only_active) $sql.= (empty($type) ? " WHERE" : " AND")." t.active = 1";
                $sql.= " ORDER BY t.position ASC";

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
                        $i = 0;
                        $num = $this->db->num_rows($resql);
                        $this->formats = array();
                        
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
                                
                                $this->formats[$obj->rowid] = $obj;
                                
				$i++;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id Id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function isActiveFormat($type, $format)
	{
		$sql = "SELECT count(*) as is_active";
		$sql.= " FROM " . MAIN_DB_PREFIX . "doliexportlist_format as t";
		$sql.= " WHERE t.type = '" . $type . "'";
		$sql.= " AND t.format = '" . $format . "'";
		$sql.= " AND t.active = 1";

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			return $obj->is_active;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function enable($id, $active=1)
	{
		$error = 0;
                
                // Update request
                $sql = "UPDATE " . MAIN_DB_PREFIX . "doliexportlist_format SET";
                $sql.= " active = ".$active;
                $sql.= " WHERE rowid = " . $id;

                $this->db->begin();

                dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (! $resql) {
                        $error ++;
                        $this->errors[] = "Error " . $this->db->lasterror();
                }

                // Commit or rollback
                if ($error) {
                        foreach ($this->errors as $errmsg) {
                                dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
                                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
                        }
                        $this->db->rollback();

                        return -1 * $error;
                } else {
                        $this->db->commit();

                        return 1;
                }
	}
        
        /**
	 * Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function disable($id)
	{
                $idarray = explode(',', $id);
                
                foreach ($idarray as $format_id) {
                    self::enable($format_id, 0);
                }
	}
        
        /**
	 * Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function setPosition($from, $to, $id=0, $not=0)
	{
		$error = 0;
                
                // Update request
                $sql = "UPDATE " . MAIN_DB_PREFIX . "doliexportlist_format SET";
                $sql.= " position = " . $to;
                $sql.= " WHERE position = " . $from;
                if ($id > 0) $sql.= " AND rowid" . ($not ? " != " : " = ") . $id;

                $this->db->begin();

                dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (! $resql) {
                        $error ++;
                        $this->errors[] = "Error " . $this->db->lasterror();
                }

                // Commit or rollback
                if ($error) {
                        foreach ($this->errors as $errmsg) {
                                dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
                                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
                        }
                        $this->db->rollback();

                        return -1 * $error;
                } else {
                        $this->db->commit();

                        return 1;
                }
	}
        
        /**
	 * Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function up($id)
	{
            if (count($this->formats) == 0) {
                $this->getFormats('', 0);
            }
            
            $pos = $this->formats[$id]->position;
            $newpos = $pos - 1;
            
            if (self::setPosition($newpos, $pos)) { // swap formats on new position to our position
                self::setPosition($pos, $newpos, $id); // move to the new position! (only selected format)
            }
	}
        
        /**
	 * Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function down($id)
	{
		if (count($this->formats) == 0) {
                    $this->getFormats('', 0);
                }

                $pos = $this->formats[$id]->position;
                $newpos = $pos + 1;

                if (self::setPosition($newpos, $pos)) { // swap formats on new position to our position
                    self::setPosition($pos, $newpos, $id); // move to the new position! (only selected format)
                }
	}
}
