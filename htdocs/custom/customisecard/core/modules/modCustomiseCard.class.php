<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021 SuperAdmin <root@roor.fr>
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
 * 	\defgroup   customisecard     Module CustomiseCard
 *  \brief      CustomiseCard module descriptor.
 *
 *  \file       htdocs/customisecard/core/modules/modCustomiseCard.class.php
 *  \ingroup    customisecard
 *  \brief      Description and activation file for module CustomiseCard
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
dol_include_once('/h2g2/core/modules/modH2G2.class.php');
dol_include_once('/h2g2/class/thegalaxy.class.php');

// Verify if the class exists to instantiate the class
if (!class_exists('TheGalaxy')) {
	/**
	 *  Creation of dummy TheGalaxy module to avoid errors
	 */
	class TheGalaxy extends DolibarrModules
	{

		/**
		 * Variable used to prompt "missing H2G2' error
		 */
		public $dummy;

		/**
		 * TheGalaxy constructor
		 */
		public function __construct()
		{
			$this->dummy = 1;
		}

		/**
		 * Dummy addTable function
		 *
		 * @param 	int 		$objectType			dummy
		 * @param 	int			$tabId				dummy
		 * @param 	int			$title				dummy
		 * @param 	int			$right				dummy
		 * @param 	int			$url				dummy
		 * @return void
		 */
		public function addTab($objectType, $tabId, $title, $right, $url): void
		{
		}

		/**
		 * Dummy addRight function
		 *
		 * @param 	int 		$label				dummy
		 * @param 	int			$level1				dummy
		 * @param 	int			$level2				dummy
		 * @param 	int			$type				dummy
		 * @param 	int			$enabledByDefault	dummy
		 * @return void
		 */
		public function addRight($label, $level1, $level2, $type = '', $enabledByDefault = 0): void
		{
		}

		/**
		 * Dummy addMenu function
		 *
		 * @param 	int			$type				dummy
		 * @param 	int			$fkMenu				dummy
		 * @param 	int			$mainMenu			dummy
		 * @param 	int			$leftMenu			dummy
		 * @param 	int			$title				dummy
		 * @param 	int			$url				dummy
		 * @param 	int			$position			dummy
		 * @param 	int			$perms				dummy
		 * @param 	int			$enabled			dummy
		 * @param 	int			$target				dummy
		 * @param 	int			$user				dummy
		 * @param 	int			$icon				dummy
		 * @return void
		 */
		public function addMenu($type, $fkMenu, $mainMenu, $leftMenu, $title, $url, $position, $perms, $enabled, $target, $user, $icon): void
		{
		}

		/**
		 * Dummy addTopMenu function
		 *
		 * @param 	int			$mainMenu			dummy
		 * @param 	int			$title				dummy
		 * @param 	int			$url				dummy
		 * @param 	int			$icon				dummy
		 * @param 	int			$position			dummy
		 * @param 	int			$perms				dummy
		 * @param 	int			$enabled			dummy
		 * @param 	int			$target				dummy
		 * @param 	int			$user				dummy
		 * @return void
		 */
		public function addTopMenu($mainMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addLeftMenu function
		 *
		 * @param 	int			$mainMenu			dummy
		 * @param 	int			$leftMenu			dummy
		 * @param 	int			$title				dummy
		 * @param 	int			$url				dummy
		 * @param 	int			$icon				dummy
		 * @param 	int			$position			dummy
		 * @param 	int			$perms				dummy
		 * @param 	int			$enabled			dummy
		 * @param 	int			$target				dummy
		 * @param 	int			$user				dummy
		 * @return void
		 */
		public function addLeftMenu($mainMenu, $leftMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addLeftSubMenu function
		 *
		 * @param 	int			$mainMenu			dummy
		 * @param 	int			$leftMenu			dummy
		 * @param 	int			$subMenuName		dummy
		 * @param 	int			$title				dummy
		 * @param 	int			$url				dummy
		 * @param 	int			$icon				dummy
		 * @param 	int			$position			dummy
		 * @param 	int			$perms				dummy
		 * @param 	int			$enabled			dummy
		 * @param 	int			$target				dummy
		 * @param 	int			$user				dummy
		 * @return void
		 */
		public function addLeftSubMenu($mainMenu, $leftMenu, $subMenuName, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addConstant function
		 *
		 * @param 	int			$name				dummy
		 * @param 	int			$type				dummy
		 * @param 	int			$value				dummy
		 * @param 	int			$desc				dummy
		 * @param 	int			$visible			dummy
		 * @param 	int			$entity				dummy
		 * @param 	int			$deleteonunactive	dummy
		 * @return void
		 */
		public function addConstant($name, $type, $value, $desc = '', $visible = 0, $entity = 'current', $deleteonunactive = 0): void
		{
		}

		/**
		 * Dummy addWidget function
		 *
		 * @param 	int			$file				dummy
		 * @param 	int			$note				dummy
		 * @param 	int			$enabledbydefaulton	dummy
		 * @return void
		 */
		public function addWidget($file, $note = '', $enabledbydefaulton = 'Home'): void
		{
		}
	}
}

/**
 *  Description and activation class for module CustomiseCard
 */
class modCustomiseCard extends TheGalaxy
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $db, $langs, $conf;
		$this->db = $db;
		parent::__construct($db);

		$this->numero = 448220;
		$this->rights_class = 'customisecard';
		$this->family = "Code 42";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "CustomiseCardDescription";
		$this->descriptionlong = "CustomiseCard description (Long)";
		$this->editor_name = 'Code 42';
		$this->editor_url = 'https://www.code42.fr';
		$this->version = '20.0.00';
		$this->versionList = array(
			'16.2.00',
			'16.2.01',
			'18.0.00',
			'20.0.00'
		);
		$this->migrationPath = '/customisecard/migrations';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'customisecard@customisecard';
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'theme' => 0,
			'css' => array(
				'/customisecard/css/customisecard.css.php',
			),
			'js' => array(),
			'hooks' => array(
				'globalcard',
				'h2g2'
			),
			'moduleforexternal' => 0,
		);
		$this->dirs = array("/customisecard/temp");
		$this->config_page_url = array("setup.php@customisecard");
		$this->hidden = false;
		$this->depends = array("modH2G2");
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("customisecard@customisecard");
		$this->phpmin = array(5, 5); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->const = array();

		if (!isset($conf->customisecard) || !isset($conf->customisecard->enabled)) {
			$conf->customisecard = new stdClass();
			$conf->customisecard->enabled = 0;
		}

		// Module rights
		$this->addRight('CustomiseCardPermToCustom', 'customcard', 'create');
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$langs->load("customisecard@customisecard");
		// [#90] If dummy is true, we print "Missing H2G2" error
		if (isset($this->dummy) && $this->dummy > 0) {
			setEventMessage($langs->trans("CustomiseCardModuleH2G2Missing", $this->name), 'errors');
			return 0;
		}

		// Module h2g2 must be in version 16.0.0 minimum
		$error = true;
		$minVersion = '16.0.00';
		if (class_exists('modH2G2')) {
			$h2g2 = new modH2G2($this->db);
			if (version_compare($h2g2->version, $minVersion, '>=')) {
				$error = false;
			}
		}

		if ($error) {
			$message = $langs->trans('H2G2MinimumVersionRequired', $minVersion);
			setEventMessage($message, 'errors');
			return 0;
		}

		$result = $this->_load_tables('/customisecard/sql/');
		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		// Global to activate/desactivate custom card multientity
		if (!isset($conf->global->CUSTOMISECARD_MULTIENTITY) && $conf->entity == 1)
			dolibarr_set_const($this->db, 'CUSTOMISECARD_MULTIENTITY', 0, 'integer', 1, $langs->trans('CustomcardMultientity'), 1);

		// Add translation idprof for every country code in third party to have a constant number of tab lines
		$sql = "SELECT code FROM " . MAIN_DB_PREFIX . "c_country WHERE rowid > 0";
		$resql = $this->db->query($sql);

		$sql = array();
		if ($resql) {
			$langs->load("companies");
			while ($obj = $this->db->fetch_object($resql)) {
				$i = 0;
				while ($i <= 6) {
					$idprof = $langs->transcountry('ProfId' . $i, $obj->code);
					if ($idprof == '-') $this->overwriteTranslation($this->db->escape($langs->defaultlang), $this->db->escape('ProfId' . $i . $obj->code), $this->db->escape('Id. prof. ' . $i), -1);
					$i += 1;
				}
			}
		} else {
			dol_syslog("Error sql init module customisecard : " . $sql, 'ERROR');
		}

		// Permissions
		$this->remove($options);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
