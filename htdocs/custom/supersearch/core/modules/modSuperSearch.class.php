<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		Thomas BACHELEY			<thomas@code42.fr>
 * Copyright (C) 2024		Arthur Croix			<arthur@code42.fr>
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
 * 	\defgroup   supersearch     Module SuperSearch
 *  \brief      SuperSearch module descriptor.
 *
 *  \file       htdocs/supersearch/core/modules/modSuperSearch.class.php
 *  \ingroup    supersearch
 *  \brief      Description and activation file for module SuperSearch
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
dol_include_once('/supersearch/lib/supersearch.lib.php');
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
		 * @param int $objectType dummy
		 * @param int $tabId dummy
		 * @param int $title dummy
		 * @param int $right dummy
		 * @param int $url dummy
		 * @return void
		 */
		public function addTab($objectType, $tabId, $title, $right, $url): void
		{
		}

		/**
		 * Dummy addRight function
		 *
		 * @param int $label dummy
		 * @param int $level1 dummy
		 * @param int $level2 dummy
		 * @param int $type dummy
		 * @param int $enabledByDefault dummy
		 * @return void
		 */
		public function addRight($label, $level1, $level2, $type = '', $enabledByDefault = 0): void
		{
		}

		/**
		 * Dummy addMenu function
		 *
		 * @param int $type dummy
		 * @param int $fkMenu dummy
		 * @param int $mainMenu dummy
		 * @param int $leftMenu dummy
		 * @param int $title dummy
		 * @param int $url dummy
		 * @param int $position dummy
		 * @param int $perms dummy
		 * @param int $enabled dummy
		 * @param int $target dummy
		 * @param int $user dummy
		 * @param int $icon dummy
		 * @return void
		 */
		public function addMenu($type, $fkMenu, $mainMenu, $leftMenu, $title, $url, $position, $perms, $enabled, $target, $user, $icon): void
		{
		}

		/**
		 * Dummy addTopMenu function
		 *
		 * @param int $mainMenu dummy
		 * @param int $title dummy
		 * @param int $url dummy
		 * @param int $icon dummy
		 * @param int $position dummy
		 * @param int $perms dummy
		 * @param int $enabled dummy
		 * @param int $target dummy
		 * @param int $user dummy
		 * @return void
		 */
		public function addTopMenu($mainMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addLeftMenu function
		 *
		 * @param int $mainMenu dummy
		 * @param int $leftMenu dummy
		 * @param int $title dummy
		 * @param int $url dummy
		 * @param int $icon dummy
		 * @param int $position dummy
		 * @param int $perms dummy
		 * @param int $enabled dummy
		 * @param int $target dummy
		 * @param int $user dummy
		 * @return void
		 */
		public function addLeftMenu($mainMenu, $leftMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addLeftSubMenu function
		 *
		 * @param int $mainMenu dummy
		 * @param int $leftMenu dummy
		 * @param int $subMenuName dummy
		 * @param int $title dummy
		 * @param int $url dummy
		 * @param int $icon dummy
		 * @param int $position dummy
		 * @param int $perms dummy
		 * @param int $enabled dummy
		 * @param int $target dummy
		 * @param int $user dummy
		 * @return void
		 */
		public function addLeftSubMenu($mainMenu, $leftMenu, $subMenuName, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2): void
		{
		}

		/**
		 * Dummy addConstant function
		 *
		 * @param int $name dummy
		 * @param int $type dummy
		 * @param int $value dummy
		 * @param int $desc dummy
		 * @param int $visible dummy
		 * @param int $entity dummy
		 * @param int $deleteonunactive dummy
		 * @return void
		 */
		public function addConstant($name, $type, $value, $desc = '', $visible = 0, $entity = 'current', $deleteonunactive = 0): void
		{
		}

		/**
		 * Dummy addWidget function
		 *
		 * @param int $file dummy
		 * @param int $note dummy
		 * @param int $enabledbydefaulton dummy
		 * @return void
		 */
		public function addWidget($file, $note = '', $enabledbydefaulton = 'Home'): void
		{
		}
	}
}

/**
 *  Description and activation class for module SuperSearch
 */
class modSuperSearch extends TheGalaxy
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		// Load correct lang before calling the parent constructor
		$this->defaultLangFile = "supersearch@supersearch";
		$langs->load($this->defaultLangFile);

		// Call TheGalaxy constructor
		parent::__construct();

		$this->db = $db;
		$this->numero = 448214;
		$this->rights_class = 'supersearch';
		$this->family = "Code 42";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "SuperSearchDescription";
		$this->descriptionlong = "SuperSearchDescription";
		$this->editor_name = 'Code42';
		$this->editor_url = 'https://www.code42.fr';

		$this->version = '18.5.00';
		$this->versionList = array(
			'18.0.00',
			'18.1.00',
			'18.2.00',
			'18.2.01',
			'18.3.00',
			'18.3.01',
			'18.3.02',
			'18.4.00',
			'18.4.01',
			'18.4.02',
			'18.5.00'
		);

		$this->migrationPath = 'supersearch/migrations';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'supersearch.png@supersearch';
		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(
			),
			'js' => array(
				'/supersearch/js/instant-meilisearch.umd.js',
				'/supersearch/js/instantsearch.js',
				'/supersearch/js/popup.js.php'
			),
			'hooks' => array(
				'data' => array(
					'h2g2'
				)
			),
			'moduleforexternal' => 0,
		);

		$this->dirs = array();
		$this->config_page_url = array("setup.php@supersearch");

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modH2G2');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("supersearch@supersearch");

		// Prerequisites
		$this->phpmin = array(7, 3);
		$this->need_dolibarr_version = array(11, -3);
		$this->need_javascript_ajax = 0;
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();
		$this->const = array();

		if (!isset($conf->supersearch) || !isset($conf->supersearch->enabled)) {
			$conf->supersearch = new stdClass();
			$conf->supersearch->enabled = 0;
		}
		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		$this->addRight('SuperSearchAccess', 'access', '');
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $langs, $db;

		$langs->load('supersearch@supersearch');

		// If dummy is true, we print "Missing H2G2" error
		if ($this->dummy > 0) {
			setEventMessage($langs->trans("SuperSearchModuleH2G2Missing", $this->name), 'errors');
			return 0;
		}

		// Module h2g2 must be in version 20.0.00 minimum
		$minVersion = '20.0.00';
		if (class_exists('modH2G2')) {
			$h2g2 = new modH2G2($this->db);
			if (version_compare($h2g2->version, $minVersion, '<')) {
				$message = $langs->trans('H2G2MinimumVersionRequired', $minVersion);
				setEventMessage($message, 'errors');
				return 0;
			}
		}

		$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
		$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

		if (empty($adminKey) || empty($urlMeilisearch)) {
			$pageSettingsLink = '<a href="' . dol_buildpath('/supersearch/admin/setup.php', 1) . '"> ' . $langs->trans('SuperSearchSetupPage') . ' </a>';
			$message = '';

			if (empty($adminKey)) $message = $langs->trans('SuperSearchAdminKeyMissing');

			if (empty($urlMeilisearch)) {
				if (empty($message)) $message = $langs->trans('SuperSearchURLMissing');
				else $message .= ', ' . $langs->trans('SuperSearchURLMissing');
			}

			setEventMessage($message . ' -> '. $pageSettingsLink, 'errors');
		} else {
			$date = $db->idate(strtotime('-30 min', dol_now()));
			$res = $db->query('SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c42migration WHERE module_name = "' . $this->rights_class .'" AND module_version >= "' . $this->version . '" AND date_creation >= "' . $date . '" ORDER BY date_creation DESC LIMIT 1');
			if ($res && $db->num_rows($res) > 0) {
				dol_include_once('/h2g2/lib/h2g2.lib.php');
				createTopBarInfo($this->name, $langs->trans('SuperSearchTopBarInitNewVersion', $this->version));
			}
		}

		// init index meilisearch
		initIndexes(); // setup indexes for this instance

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
