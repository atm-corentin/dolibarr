<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023 SuperAdmin
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
 * 	\defgroup   themequarantedeux     Module Quarantedeux
 *  \brief      ThemeQuarantedeux module descriptor.
 *
 *  \file       htdocs/themequarantedeux/core/modules/modThemeQuaranteDeux.class.php
 *  \ingroup    themequarantedeux
 *  \brief      Description and activation file for module ThemeQuarantedeux
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

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
 *  Description and activation class for module ThemeQuarantedeux
 */
class modThemeQuaranteDeux extends TheGalaxy
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
		$this->defaultLangFile = "themequarantedeux@themequarantedeux";
		$langs->load($this->defaultLangFile);

		// Call TheGalaxy constructor
		parent::__construct();

		$this->db = $db;

		$this->numero = 448551;
		$this->rights_class = 'themequarantedeux';
		$this->family = "Code 42";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "ModuleThemeQuarantedeuxDesc";
		$this->descriptionlong = "ModuleThemeQuarantedeuxDesc";
		$this->editor_name = 'Code 42';
		$this->editor_url = 'https://www.code42.fr';
		$this->version = '20.2.00';

		$this->versionList = array(
			'13.6.00',
			'13.6.01',
			'18.0.00',
			'18.0.02',
			'18.0.03',
			'18.0.04',
			'18.0.05',
			'18.0.06',
			'19.0.00',
			'19.0.01',
			'19.1.00',
			'19.2.00',
			'19.2.10',
			'19.2.11',
			'20.0.00',
			'20.0.01',
			'20.0.02',
			'20.0.03',
			'20.1.00',
			'20.1.01',
			'20.1.02',
			'20.1.03',
			'20.1.04',
            '20.1.05',
            '20.1.06',
            '20.1.07',
            '20.2.00'
		);

		$this->migrationPath = 'themequarantedeux/migrations';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto = 'themes.png@themequarantedeux';

		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(
				'/themequarantedeux/js/macro.js.php',
				'/themequarantedeux/js/Sortable/Sortable.js'
			),
			'hooks' => array(
				'data' => array(
					'main',
					'h2g2',
					'searchform',
					'userihm'
				)
			),
			'moduleforexternal' => 0,
		);

		$this->dirs = array();
		$this->config_page_url = array("ihm.php@themequarantedeux");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		$this->depends = array('modH2G2');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("themequarantedeux@themequarantedeux");

		$this->phpmin = array(7, 3);
		$this->need_dolibarr_version = array(11, -3);
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		if (!isset($conf->quarantedeux) || !isset($conf->quarantedeux->enabled)) {
			$conf->quarantedeux = new stdClass();
			$conf->quarantedeux->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();

		$this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();

		$r=0;
		$this->rights[$r][0] = 161199; // id de la permission
		$this->rights[$r][1] = "Configurer HideTopMenu"; // libelle de la permission
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'configure';

		if (!isset($conf->global->THEME_SORTABLE_ACTIVATED)) $this->addConstant('THEME_SORTABLE_ACTIVATED', 'integer', 0);

		$resql = $this->db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "c42migration WHERE module_name = 'themequarantedeux' AND date_creation < '" . $this->db->idate(strtotime('-1min', dol_now())) . "'");

		// 1 => Classic / 2 => Modern
		if ($resql) dolibarr_set_const($this->db, "TC42_POSITION_MENU", ($db->num_rows($resql) > 0 ? "2" : "1"), 'chaine', 0, '', 0);
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 *                                  -1 if H2G2 Minimum Version Required
	 *                                  -2 if Error when deleted ThemeQuarantedeux's theme directory
	 *                                  -3 if Error when copy ThemeQuarantedeux's theme directory
	 */
	public function init($options = '')
	{
		global $langs, $conf;

		$langs->load('themequarantedeux@themequarantedeux');

		// If dummy is true, we print "Missing H2G2" error
		if ($this->dummy > 0) {
			setEventMessage($langs->trans("ThemeModuleH2G2Missing", $this->name), 'errors');
			return 0;
		}

		// Module h2g2 must be in version 15.0.05 minimum
		$minVersion = '15.0.05';
		if (class_exists('modH2G2')) {
			$h2g2 = new modH2G2($this->db);
			if (version_compare($h2g2->version, $minVersion, '<=')) {
				$message = $langs->trans('H2G2MinimumVersionRequired', $minVersion);
				setEventMessage($message, 'errors');
				return -1;
			} elseif ($conf->h2g2->enabled) {
				$srcDir = dol_buildpath('/custom/themequarantedeux/quarantedeux');
				$destDir = DOL_DOCUMENT_ROOT . '/theme/quarantedeux';

				if (dol_is_dir($destDir)) {
					if (dol_delete_dir_recursive($destDir) < 0) {
						setEventMessage($langs->trans('ThemeQuaranteDeuxErrorDelete'), 'errors');
						return -2;
					}
				}

				$error = dolCopyDir($srcDir, $destDir, 644, 1);

				if ($error <= 0) {
					switch ($error) {
						case 0:
							setEventMessage($langs->trans('TC42ErrorCopyNothingDone'), 'errors');
							break;
						case -1:
							setEventMessage($langs->trans('TC42ErrorCopyEmptyFile'), 'errors');
							break;
						case -2:
							setEventMessage($langs->trans('TC42ErrorCopySourceDirectoryNonExistent'), 'errors');
							break;
						case -3:
							setEventMessage($langs->trans('TC42ErrorCopyVirusOrFailed'), 'errors');
							break;
						default:
							setEventMessage($langs->trans('TC42ErrorCopy'), 'errors');
							break;
					}
					dol_syslog('TC42::CopyTheme::error::code=' . $error, LOG_ERR);
					return -3;
				} else {
					dolibarr_set_const($this->db, 'MAIN_THEME', 'quarantedeux', 'chaine', 0, '', $conf->entity);
				}
			}
		}

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
		global $conf;

		// set theme eldy
		dolibarr_set_const($this->db, 'MAIN_THEME', 'eldy', 'chaine', 0, '', $conf->entity);

		$sql = array();
		return $this->_remove($sql, $options);
	}
}
