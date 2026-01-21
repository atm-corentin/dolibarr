<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020 SuperAdmin
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \defgroup   h2g2     Module H2G2
 *  \brief      H2G2 module descriptor.
 *
 *  \file       htdocs/h2g2/core/modules/modH2G2.class.php
 *  \ingroup    h2g2
 *  \brief      Description and activation file for module H2G2
 */
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
dol_include_once('/h2g2/class/thegalaxy.class.php');
dol_include_once('/h2g2/lib/h2g2.lib.php');

/**
 *  Description and activation class for module H2G2
 */
class modH2G2 extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$this->numero = 448300;
		$this->rights_class = 'h2g2';
		$this->family = "Code 42";
		$this->module_position = '80';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "H2G2Description";
		$this->descriptionlong = "H2G2 description (Long)";
		$this->editor_name = 'Code 42';
		$this->editor_url = 'https://www.code42.fr';
		$this->version = '20.3.00';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='h2g2@h2g2';

		$this->defaultLangFile = 'h2g2@h2g2';
		$langs->load($this->defaultLangFile);

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'theme' => 0,
			'css' => array(
				'/h2g2/css/h2g2.css.php',
				'/h2g2/css/topbar.css.php',
				'/h2g2/css/clpbrd42.css',
			),
			'js' => array(
				'/h2g2/js/h2g2.js.php',
				'/h2g2/js/sweetalert2.all.min.js',
				'/h2g2/js/clpbrd42.js.php',
				'/h2g2/js/ScrollToTop.js'
			),
			'hooks' => array(
				'data' => array(
					'adminmodules', // Hook to display wizard to activate not updated modules
					'main', // Hook to include the dev lib globally
					'upgrade' // Hook to reset user when there is an upgrade
				),
				'entity' => '0',
			),
			'moduleforexternal' => 0,
		);
		$this->dirs = array("/h2g2/temp");
		$this->config_page_url = array("setup.php@h2g2");
		$this->hidden = false;
		$this->depends = array('modAgenda');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("h2g2@h2g2");
		$this->phpmin = array(5, 5);
		$this->need_dolibarr_version = array(7, 0);
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// Constants
		$this->const = array(
			array('H2G2_INCLUDE_DEV_LIB', 'chaine', '0', 'Include the dev lib globally', 1, 'allentities', 0)
		);

		if (!isset($conf->h2g2) || !isset($conf->h2g2->enabled)) {
			$conf->h2g2 = new stdClass();
			$conf->h2g2->enabled = 0;
		}

		$this->tabs[] = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero;
		$this->rights[$r][1] = $langs->trans("H2G2AdminNewsPage");
		$this->rights[$r][4] = 'news';
		$this->rights[$r][5] = 'access';

		$this->menu = array();
		$menuUrl = '/h2g2/h2g2index.php';
		$pos = 'top';
		/* BEGIN MODULEBUILDER TOPMENU */
		$ret = isMenuExist($this->rights_class, $pos, $menuUrl, 'h2g2'); // We check by url, moduleName and 'type' in DB to see if the menu already exist or not
		if (!$ret) {
			$this->menu[$r++] = array(
				'fk_menu' => '',
				'type' => $pos,
				'titre' => 'H2G2',
				'mainmenu' => 'h2g2',
				'leftmenu' => '',
				'url' => $menuUrl,
				'langs' => 'h2g2@h2g2',
				'position' => 1000 + $r,
				'enabled' => '$conf->global->H2G2_INCLUDE_DEV_LIB',
				'perms' => '1',
				'target' => '',
				'user' => 2,
			);
		}

		$menuUrl = '/h2g2/admin/news.php';
		$pos = 'left';
		$ret = isMenuExist($this->rights_class, $pos, $menuUrl, 'tools'); // We check by url, moduleName and 'type' in DB to see if the menu already exist or not
		if (!$ret) {
			$this->menu[$r++] = array(
				'fk_menu' => 'fk_mainmenu=tools',
				'type' => $pos,
				'titre' => 'H2G2NewsTab',
				'mainmenu' => 'tools',
				'leftmenu' => '',
				'url' => $menuUrl,
				'langs' => 'h2g2@h2g2',
				'position' => 1000 + $r,
				'enabled' => '$user->admin || !empty($user->rights->h2g2->news->access)',
				'perms' => '1',
				'target' => '',
				'user' => 2,
			);
		}

		$menuUrl = '/h2g2/h2g2newsindex.php';
		$pos = 'top';
		$ret = isMenuExist($this->rights_class, $pos, $menuUrl, 'h2g2'); // We check by url, moduleName and 'type' in DB to see if the menu already exist or not
		if ($ret) {
			if (updateMenu($this->rights_class, $pos, $menuUrl, 'titre', 'H2G2NewsMenu') < 0) {
				setEventMessage($langs->trans("H2G2ErrorUpdateMenu", "H2G2NewsMenu"), "errors");
			}
		} else {
			// Button to redirect for news H2G2
			$this->menu[$r++] = array(
				'fk_menu' => '',
				'type' => $pos,
				'titre' => 'H2G2NewsMenu',
				'mainmenu' => 'h2g2',
				'leftmenu' => '',
				'url' => $menuUrl,
				'langs' => 'h2g2@h2g2',
				'position' => 1000 + $r,
				'enabled' => '$conf->h2g2->enabled',
				'perms' => '1',
				'target' => '',
				'user' => 2,
			);
		}
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		$menuUrl = '/h2g2/h2g2index.php';
		$pos = 'left';
		$ret = isMenuExist($this->rights_class, $pos, $menuUrl, 'h2g2', 'documentation'); // We check by url, moduleName and 'type' in DB to see if the menu already exist or not
		if ($ret) {
			if (updateMenu($this->rights_class, $pos, $menuUrl, 'titre', 'H2G2MenuDocumentation') < 0) {
				setEventMessage($langs->trans("H2G2ErrorUpdateMenu", "H2G2MenuDocumentation"), "errors");
			}
		} else {
			// Button to redirect for news H2G2
			$this->menu[$r++] = array(
				'fk_menu' => 'fk_mainmenu=h2g2',
				'type' => $pos,
				'titre' => 'H2G2MenuDocumentation',
				'mainmenu' => 'h2g2',
				'leftmenu' => 'documentation',
				'url' => $menuUrl,
				'langs' => 'h2g2@h2g2',
				'position' => 1000 + $r,
				'enabled' => '$conf->global->H2G2_INCLUDE_DEV_LIB',
				'perms' => '1',
				'target' => '',
				'user' => 2,
			);
		}

		$library = array(
			"multientrybtn" => "H2G2MenuMultientryButton",
			"setup" => "H2G2MenuSetup",
		);

		foreach ($library as $libKey => $libName) {
			$menuUrl = "/h2g2/dev/$libKey/index.php";
			$pos = 'left';
			$ret = isMenuExist($this->rights_class, $pos, $menuUrl, 'h2g2', "h2g2_$libKey"); // We check by url, moduleName and 'type' in DB to see if the menu already exist or not
			if ($ret) {
				if (updateMenu($this->rights_class, $pos, $menuUrl, 'titre', $libName) < 0) {
					setEventMessage($langs->trans("H2G2ErrorUpdateMenu", $libName), "errors");
				}
			} else {
				// Button to redirect for news H2G2
				$this->menu[$r++] = array(
					'fk_menu'  => 'fk_mainmenu=h2g2,fk_leftmenu=documentation',
					'type'     => $pos,
					'titre'    => $libName,
					'mainmenu' => 'h2g2',
					'leftmenu' => 'h2g2_' . $libKey,
					'url'      => $menuUrl,
					'langs'    => 'h2g2@h2g2',
					'position' => 1000 + $r,
					'enabled'  => '$conf->global->H2G2_INCLUDE_DEV_LIB',
					'perms'    => '1',
					'target'   => '',
					'user'     => 2,
				);
			}
		}
		/* END MODULEBUILDER LEFTMENU MYOBJECT */

		if (empty($conf->global->H2G2_DISABLE_CHECK_LAST_VERSION)) $this->url_last_version = "https://git.code42.io/pub/badge-dolibarr/-/raw/main/last-version/h2g2.txt?ref_type=heads";
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 * @param  string $options Options when enabling module ('', 'noboxes')
	 * @return int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user;

		$result = $this->_load_tables('/h2g2/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Set event constant
		dolibarr_set_const($this->db, 'AGENDA_USE_EVENT_TYPE', '1', 'chaine', 0, '', 0);

		$sql = array();

		// Add the massaction event to event types
		$query = "SELECT id FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE code = 'AC_MAS_ACT' AND module = 'h2g2'";
		$resql = $this->db->query($query);
		if ($resql && $this->db->num_rows($resql) == 0) {
			$sql[] = "INSERT INTO " . MAIN_DB_PREFIX . "c_actioncomm (id, code, type, libelle, module) SELECT MAX(id) + 1, 'AC_MAS_ACT', 'module', 'Action de masse', 'h2g2' FROM " . MAIN_DB_PREFIX . "c_actioncomm";
		}

		// [#41] News system - Enable an event value into agenda module
		dolibarr_set_const($this->db, 'AGENDA_USE_EVENT_TYPE', '1', 'chaine', 0, '', $conf->entity);

		// [#41] News system - Add extrafields for users to know if they already see news
		$galaxy = new TheGalaxy();
		$galaxy->db = $this->db;
		// [#41] Add extrafield for users to know if they already see news and set type with systemauto to avoid creating event with this type
		$galaxy->addUpdateExtrafields('news_viewed', 'Nouveautés vues', 'boolean', 100, 2, 'user', '', 0, '', '', 0, '', '0');
		// [#73] Add extrafield for actioncomm to know if user see the news
		$galaxy->addUpdateExtrafields('viewed_by_user', 'Info Bar Nouveautés vue par', 'text', 101, 100, 'actioncomm', '', 0, '', '', 0, '', '0');
		// [#41] List all users of the current entity and SuperAdmin
		$sql_list_users = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE entity = " . $conf->entity . " OR entity = 0";
		$resql_list_users = $this->db->query($sql_list_users);

		if ($resql_list_users && $this->db->num_rows($resql_list_users) > 0) {
			// [#41] News system - Add extrafield for users to know if they already see news (only one time per user)
			while ($obj = $this->db->fetch_object($resql_list_users)) {
				$sql_check_extra = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user_extrafields WHERE fk_object = " . $obj->rowid;
				$resql_check = $this->db->query($sql_check_extra);
				if ($resql_check && $this->db->num_rows($resql_check) == 0) {
					$sql_user = "INSERT INTO " . MAIN_DB_PREFIX . "user_extrafields (fk_object, tms, news_viewed) VALUES (" . $obj->rowid . ",'" . $this->db->escape($this->db->idate(dol_now())) . "', 0)";
					$resql_user = $this->db->query($sql_user);
					if (!$resql_user) {
						setEventMessage($langs->trans('H2H2NewsSetupExtrafieldsNotSaved', 'errors'));
					}
				}
			}
		}

		$sql_check_action = "SELECT id FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE id = '80'";
		$resql = $this->db->query($sql_check_action);

		if ($resql && $this->db->num_rows($resql) > 0) {
			// [#41] News system - update "news" type in event module with number of h2g2 module + 10
			$query = "UPDATE " . MAIN_DB_PREFIX . "c_actioncomm SET id = '448310' WHERE id = '80'";
			$resql = $this->db->query($query);
			if (!$resql) {
				setEventMessage($langs->trans('H2H2NewsSetupDicoNotSaved'), 'errors');
			}
		} else {
			$events = array(
				// code => id, type, libelle
				'C42_NEWS' => array(448310 , 'systemauto', 'Nouveautés H2G2'),
				'H2G2NewsSucc' => array(448312 , 'news@h2g2', $langs->trans('H2G2NewsSucc')),
				'H2G2NewsInfo' => array(448313 , 'news@h2g2', $langs->trans('H2G2NewsInfo')),
				'H2G2NewsWarn' => array(448314 , 'news@h2g2', $langs->trans('H2G2NewsWarn')),
				'H2G2NewsDang' => array(448315 , 'news@h2g2', $langs->trans('H2G2NewsDang')),
			);

			$pos = 80;
			foreach ($events as $code => $event) {
				$id = $event[0];
				$type = $event[1];
				$label = $event[2];

				$resql = $this->db->query('SELECT id FROM ' . MAIN_DB_PREFIX . 'c_actioncomm WHERE id = ' . $id);
				if ($resql && !$this->db->num_rows($resql)) {
					$resql = $this->db->query('INSERT INTO ' . MAIN_DB_PREFIX . 'c_actioncomm (id, code, type, libelle, module, active, position) VALUES (' . $id . ', "' . $code . '", "' . $type . '", "' . $label . '", "h2g2", 1, ' . $pos . ')');
					if (!$resql)setEventMessage($langs->trans('H2H2NewsSetupDicoNotSaved'), 'errors');
				}
				$pos++;
			}

			$galaxy->addRight($langs->trans('H2G2ReadNews'), 'news', 'read', '', 1); // Necessary to ensure the news type appears in the event type selector
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 * @param  string $options Options when enabling module ('', 'noboxes')
	 * @return int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		$galaxy = new TheGalaxy();
		return $galaxy->_remove($sql, $options, $this);
	}
}
