<?php
/* Copyright (C) 2022  Ayoub Bayed <ayoub@code42.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/article.class.php
 * \ingroup     lareponse
 * \brief       This file is a CRUD class file for article (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for article
 */
class Article extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'article';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'lareponse_article';

	/**
	 * @var int  Does article support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for article. Must be the part after the 'object_' into object_article.png
	 */
	public $picto = 'lareponse@lareponse';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => -1, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => 2),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => 2),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2,),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
		'title' => array('type' => 'varchar(255)', 'label' => 'Title', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1,),
		'content' => array('type' => 'text', 'label' => 'Content', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 3,),
		'private' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 1, 'default' => 0, 'arrayofkeyval' => array(0 => 'Public', 1 => 'Private', 2 => 'ArticleClosed')),
		'publish_token' => array('type' => 'varchar(35)', 'label' => 'Published', 'enabled' => 1, 'visible' => 2, 'notnull' => 0, 'position' => 70),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => 1, 'notnull' => 1, 'index' => 1, 'position' => 20),
		'type' => array('type' => 'integer', 'label' => 'Type', 'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 1, 'default' => 0, 'arrayofkeyval' => array(0 => 'Article', 1 => 'LaReponseIframeUrl')),
	);
	public $rowid;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $title;
	public $content;
	public $private;
	public $publish_token;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'lareponse_articleline';

	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_article';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'articleline';

	/**
	 * @var array    List of child tables. To test if we can delete object.
	 */
	//protected $childtables=array();

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 */
	//protected $childtablesoncascade=array('lareponse_articledet');

	/**
	 * @var articleLine[]     Array of subtable lines
	 */
	//public $lines = array();


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->lareponse->article->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @return    mixed                New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) $object->fetchLines();

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);


		// Clear fields
		//$object->ref = empty($this->fields['ref']['default']) ? "copy_of_".$object->ref : $this->fields['ref']['default'];
		$object->title = empty($this->fields['title']['default']) ? $langs->trans("CopyOf") . " " . $object->title : $this->fields['title']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';

		//reset creation date
		$object->date_creation = dol_now();
		//reset modification date
		$object->tms = dol_now();

		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (property_exists($this, 'socid') && $this->socid == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0)
					$error++;
			}
		}

		// This function is not needed for now but may be later
		/* We need to associate the tags linked to the original article to the new one
		if (!$error)
		{
			$associatedtags = array();

			$sql = 'SELECT at.fk_tag FROM '.MAIN_DB_PREFIX.'lareponse_article_tag AS at ';
			$sql .= 'WHERE at.fk_article = '.$fromid;
			$resql = $this->db->query($sql);
			if ($resql->num_rows != 0) {
				for ($i = 0; $i < $resql->num_rows; $i++)
				{
					$associatedtags[] = $this->db->fetch_row($resql);
				}
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lareponse_article_tag (fk_article, fk_tag) ';
				$sql .= 'VALUES ';
				for ($i = 0; $i < $resql->num_rows; $i++) {
					$sql .= '(';
					$sql .= $object->id.', ';
					$sql .= $associatedtags[$i]['0'].')';
					if ($i + 1 < $resql->num_rows) $sql .= ',';
				}
				$this->db->query($sql);
			}
		}*/

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			setEventMessage($this->title . ' ' . $langs->trans('SuccessfullyCloned'));
			return $object;
		} else {
			$this->db->rollback();
			setEventMessage($this->title . ' ' . $langs->trans('FailedCloned'), 'errors');
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @param string $ref Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntityLareponse($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < min($limit, $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);
			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user User that modifies
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$this->fk_user_modif = $user->id;
		$this->tms = dol_now();
		return $this->updateCommon($user, $notrigger);
	}


	/**
	 *  Delete favorite elements into lareponse_favorites table of current article
	 *
	 * @return  integer                         The function successes => 1, The function fails => -1
	 */
	private function deleteFavorites()
	{
		global $db;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "lareponse_favorites WHERE fk_article = " . $this->id;
		if (!$resql = $db->query($sql)) {
			dol_print_error($db);
			return -1;
		}
		return 1;
	}


	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		if ($this->element == 'article')
			if (Article::deleteFavorites() == -1)
				return -1;
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 * @param User $user User that delete
	 * @param int $idline Id of line to delete
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int                >0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *    Validate object
	 *
	 * @param User $user User making status change
	 * @param int $notrigger 1=Does not execute triggers, 0= execute triggers
	 * @return    int                        <=0 if OK, 0=Nothing done, >0 if KO
	 */
	/*public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED)
		{
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->article->create))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->article->article_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*//*

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->title) || empty($this->title))) // empty should not happened, but when it occurs, the test save life
		{
			$num = $this->getNextNumRef();
		}
		else
		{
			$num = $this->title;
		}
		$this->newref = $num;

		if (! empty($num)) {
			// Validate
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " SET ref = '" . $this->db->escape($num) . "',";
			$sql .= " status = " . self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) $sql .= ", date_validation = '" . $this->db->idate($now) . "',";
			if (!empty($this->fields['fk_user_valid'])) $sql .= ", fk_user_valid = " . $user->id;
			$sql .= " WHERE rowid = " . $this->id;

			dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('MYOBJECT_VALIDATE', $user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}

		if (!$error)
		{
			$this->oldref = $this->title;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->title))
			{
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->title) + 1).")), filepath = 'article/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->title)."%' AND filepath = 'article/".$this->db->escape($this->title)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) { $error++; $this->error = $this->db->lasterror(); }

				// We rename directory ($this->title = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->title);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->lareponse->dir_output.'/article/'.$oldref;
				$dirdest = $conf->lareponse->dir_output.'/article/'.$newref;
				if (!$error && file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->lareponse->dir_output.'/article/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry)
						{
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error)
		{
			$this->title = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}*/


	/**
	 *    Set draft status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'ARTICLE_UNVALIDATE');
	}

	/**
	 *    Set cancel status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'ARTICLE_CLOSE');
	}

	/**
	 *    Set back to validated status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'ARTICLE_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 * @param int $withpicto Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param string $option On what the link point to ('nolink', ...)
	 * @param int $notooltip 1=Disable tooltip
	 * @param string $morecss Add more css on link
	 * @param int $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return    string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager, $db;

		dol_include_once('/lareponse/class/tag.class.php');

		$tag = new Tag($db);

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';
		$label = '';

		$tagArticleList = $tag->getArticleTag($this->id);
		if (count($tagArticleList) > 0) {
			$tags = '<div id="tags-section">';
			foreach ($tagArticleList as $tagVal) {
				$tagId = intval($tagVal['id']);
				$tag->fetch($tagId);
				$typeObject = getObjectNameByType($tagVal);
				$ways = $tag->print_all_ways(' &gt;&gt; <span class="fa fa-tag"></span> ', dol_buildpath('/lareponse/article_list.php', 1));
				// Some colors added by categories module may be empty or missing a '#'. We're here changing the string so that it matches a #XXXXXX pattern or becomes black if empty
				mb_substr($tagVal['color'], 0, 1) == '#' ? $currcolor = $tagVal['color'] : $currcolor = '#'.$tagVal['color'];
				if (strlen($currcolor) == 1) $currcolor .= '000000';
				$tags .= '<div><a style="background-color: ' . $currcolor . '; color:' . (colorIsLight($currcolor) ? 'black' : 'white') . ' !important;" class="lareponse_tag" href="' . dol_buildpath('/lareponse/card_tag.php', 1) . '?id=' . $tagVal['id'] . '"><span class="fa fa-tag"></span> ' . $ways[0] . ' (' . $typeObject . ')</a></div>';
			}
			$label .= $tags . '</div>';
		}

		$authorUser = new User($db);
		$authorUser->fetch($this->fk_user_creat);
		$label .= '<br /><b>' . $langs->trans('CreatedBy') . '</b> ' . activeContributorUrl($authorUser, -1, '', -1, 1) . '<br />';

		$displayImagesInTooltip = getDolGlobalInt('LAREPONSE_DISPLAY_IMAGES_IN_TOOLTIP') ?? 0;
		// strip_tags -> remove html and php tag (here we allow <img>)
		if (strip_tags($this->content, '<img>') != '' && $displayImagesInTooltip) {
			$articleContent = str_replace(["\n", "\r", "\t", "\v", "\0"], '', $this->content); // sometine in DB \r\n.. can appear, we replace them by '';
			$label .= '<div style="overflow: hidden !important;">' . dol_trunc(htmlspecialchars($articleContent), isset($conf->global->LAREPONSE_PREVIEW_TOOLTIP) ? $conf->global->LAREPONSE_PREVIEW_TOOLTIP : 250) . '</div>';
		} elseif (strip_tags($this->content, '<img>') != '' && !$displayImagesInTooltip) {
			$articleContent = preg_replace_callback(
				'/<img[^>]*>/i',
				function ($matches) {
					if (preg_match('/alt=(["\'])(.*?)\1/i', $matches[0], $altMatches)) return '[' . $altMatches[2] . ']'; // replace img by alt
					else return '[Image]'; // if no alt -> replace image by [Image]
				},
				$this->content
			);

			$label .= '<div style="overflow: hidden !important;">' . dol_trunc(htmlspecialchars($articleContent), isset($conf->global->LAREPONSE_PREVIEW_TOOLTIP) ? $conf->global->LAREPONSE_PREVIEW_TOOLTIP : 250) . '</div>';
		} else $label .= $langs->trans('LaReponseNoContent');

		$url = dol_buildpath('/lareponse/article_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("Showarticle");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= '<span class="fa fa-newspaper-o" style="color:black;"></span> ';
		if ($withpicto != 2) $result .= $this->title;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('articledao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return    string                   Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Return the status
	 *
	 * @param int $status Id status
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string                   Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("lareponse");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status' . $status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) $statusType = 'status6';

		if ((float) DOL_VERSION >= 11)
			if (isset($this->labelStatus[$status]) && isset($this->labelStatusShort[$status])) return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
		else return '';
	}

	/**
	 *    Load the info information in the object
	 *
	 * @param int $id Id of object
	 * @return    void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.rowid = ' . $id;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 *    Create an array of lines
	 *
	 * @return array|int        array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new articleLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_article = ' . $this->id));

		if (is_numeric($result)) {
			$this->error = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 * @return string            Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("lareponse@article");

		if (empty($conf->global->LAREPONSE_ARTICLE_ADDON)) {
			$conf->global->LAREPONSE_ARTICLE_ADDON = 'mod_mymobject_standard';
		}

		if (!empty($conf->global->LAREPONSE_ARTICLE_ADDON)) {
			$mybool = false;

			$file = $conf->global->LAREPONSE_ARTICLE_ADDON . ".php";
			$classname = $conf->global->LAREPONSE_ARTICLE_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/lareponse/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != "") {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error") . " " . $langs->trans("ClassNotFound");
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * @param string $modele Force template to use ('' to not force)
	 * @param Translate $outputlangs objet lang a utiliser pour traduction
	 * @param int $hidedetails Hide details of lines
	 * @param int $hidedesc Hide description
	 * @param int $hideref Hide ref
	 * @param null|array $moreparams Array to provide more information
	 * @return     int                        0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("lareponse@lareponse");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->ARTICLE_ADDON_PDF)) {
				$modele = $conf->global->ARTICLE_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/lareponse/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return    int            0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * Generates a token for the article and loads it in datatable
	 *
	 * @return  boolean     false if fail, true if success
	 */
	public function generateArticleToken()
	{
		$this->publish_token = openssl_random_pseudo_bytes(16);
		$this->publish_token = bin2hex($this->publish_token);
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'lareponse_article SET publish_token=\'' . $this->publish_token . '\' WHERE rowid=' . $this->id;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->publish_token = null;
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Removes the article publication token from object and in database
	 *
	 * @return  boolean     false if fail, true if success
	 */
	public function removeArticleToken()
	{
		$this->publish_token = null;
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'lareponse_article SET publish_token=NULL WHERE rowid=' . $this->id;
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * List of articles that not share id tag
	 * @param int   $id         tag id
	 * @return string
	 */
	public function selectArticles($id)
	{
		$articleId = 0;
		$articlesId = $this->getArticlesList($id);

		// if there is data
		if ($articlesId) {
			$articleId = implode(',', $articlesId);
		}

		$sql = "SELECT DISTINCT la.rowid, la.title from ".MAIN_DB_PREFIX."lareponse_article as la";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."lareponse_article_tag as at ON la.rowid = at.fk_article";
		$sql .= " WHERE (at.fk_tag <> ".$id." OR at.fk_tag is null)";
		$sql .= " AND la.rowid NOT IN($articleId)";

		$resql = $this->db->query($sql);

		if ($resql) {
			$res = print '<select name="idarticle" id="searcharticles">';
			$res .= print '<option value=""></option>';

			while ($obj = $this->db->fetch_object($resql)) {
				$res .= print '<option value="' . $obj->rowid . '" >' . $obj->title . '</option>';
			}

			$res .= print '</select>';

			return $res;
		}
	}


	/**
	 * List of articles that have tag linked
	 * @param int   $id         tag id
	 * @return array    $objs   List of id articles
	 */
	public function getArticlesList($id)
	{
		$objs = array();

		$sql = "SELECT fk_article FROM ".MAIN_DB_PREFIX."lareponse_article_tag WHERE fk_tag =".$id;
		$result = $this->db->query($sql);

		while ($obj = $this->db->fetch_object($result)) {
			$objs[] = $obj->fk_article;
		}
		return $objs;
	}

	/**
	 * Return list of articles having this tag
	 * @param int   $id     id of tag
	 * @param int   $idToExclude     id of the article to exclude
	 * @return int      -1 if ko
	 *         array    $objs if ok
	 */
	public function getArticles($id, $idToExclude = null)
	{
		global $user;

		$objs = array();
		global $user;
		$sql = "SELECT DISTINCT la.title, la.rowid as id from ".MAIN_DB_PREFIX."lareponse_article as la";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."lareponse_article_tag as at ON la.rowid = at.fk_article";
		$sql .= " AND ((la.private = 1 AND la.fk_user_creat = ".$user->id.") OR (la.private = 0))";
		if ($idToExclude) {
			$sql .= " AND la.rowid != " . $idToExclude;
		}
		$sql .= " WHERE at.fk_tag = ".$id;
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$objs[] = $obj;
			}
			return $objs;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 * Link an article to the category
	 * @param int       $idTag      id of tag
	 * @param int       $idArticle   id of article
	 * @return int      -1 if ko, 1 if ok
	 */
	public function linkArticle($idTag, $idArticle)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."lareponse_article_tag";
		$sql .= " (fk_article, fk_tag)";
		$sql .= " VALUES (".$idArticle.", ".$idTag.")";
		$resql = $this->db->query($sql);

		if ($resql) {
			return 1;
		}
		return -1;
	}

	/**
	 * Delete article from category
	 * @param int       $idTag      id of tag
	 * @param int       $idArticle   id of article
	 * @return int      -1 if ko, 1 if ok
	 */
	public function delLinkArticle($idTag, $idArticle)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."lareponse_article_tag";
		$sql .= " WHERE fk_article = ". $idArticle." AND fk_tag =".$idTag;
		$resql = $this->db->query($sql);

		return $resql ? 1 : -1;
	}
}

/**
 * Class articleLine. You can also remove this and generate a CRUD class for lines objects.
 */
class articleLine
{
	// To complete with content of an object articleLine
	// We should have a field rowid, fk_article and position
}
