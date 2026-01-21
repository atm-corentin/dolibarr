<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022       Ayoub Bayed             <ayoub@code42.fr>
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
 * 	\defgroup   lareponse     Module Lareponse
 *  \brief      Lareponse module descriptor.
 *
 *  \file       htdocs/lareponse/core/modules/modLareponse.class.php
 *  \ingroup    lareponse
 *  \brief      Description and activation file for module Lareponse
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
dol_include_once('/h2g2/class/thegalaxy.class.php');
dol_include_once('/h2g2/core/modules/modH2G2.class.php');

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
	// We declare the module only if TheGalaxy is included
	/**
	 *  Description and activation class for module Lareponse
	 */

class modLareponse extends TheGalaxy
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

		$this->defaultLangFile = 'lareponse@lareponse';
        $langs->load($this->defaultLangFile);
		parent::__construct();

		$this->versionList = array(
				'14.0.00',
				'14.1.00',
				'15.0.00',
				'16.0.00',
				'16.1.00',
				'16.2.00',
				'16.3.00',
				'18.0.00',
				'18.1.00',
				'19.0.00',
				'19.1.00',
				'19.1.01',
				'19.2.00',
				'19.2.01',
				'19.2.02',
				'19.2.03',
				'20.0.00',
				'20.0.01',
				'20.1.00',
				'20.1.01',
				'20.2.00',
				'20.3.00'
			);

		$this->migrationPath = '/lareponse/migrations';
		$this->numero = 448075;
		$this->rights_class = 'lareponse';
		$this->family = "Code 42";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans('ModuleLareponseDesc');
		$this->descriptionlong = $langs->trans('ModuleLareponseDesc');
		$this->editor_name = 'Code 42';
		$this->editor_url = 'https://www.code42.fr';
		$this->version = '20.3.00';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'lareponse_main@lareponse';
		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 1,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'theme' => 0,
			'js' => array(),
			'css' => array(
				'/lareponse/css/lareponse.css.php',
			),
			'hooks' => array(
				   'data' => array(
					   'main',
					   'h2g2',
					   'articlelist',
                       'userihm'
				   ),
			),
			'moduleforexternal' => 0,
		);
		$this->dirs = array("/lareponse/temp");
		$this->config_page_url = array("setup.php@lareponse");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("lareponse@lareponse");
		$this->phpmin = array(7, 3);
		$this->need_dolibarr_version = array(13);
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		if (!isset($conf->lareponse) || !isset($conf->lareponse->enabled)) {
			$conf->lareponse = new stdClass();
			$conf->lareponse->enabled = 0;
		}
		$langs->load('lareponse@lareponse');
		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$frequency = (getDolGlobalInt("LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK") > 5 ? getDolGlobalInt("LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK") : 60);
		$this->cronjobs = array(
			0 => array('label' => 'LaReponseCronNotificationArticle', 'jobtype' => 'method', 'class' => 'lareponse/class/article_cron.class.php', 'objectname' => 'ArticleCron', 'method' => 'notifySubscribedUsers', 'parameters' => '', 'comment' => 'Any', 'frequency' => $frequency, 'unitfrequency' => 60, 'status' => 1, 'priority' => 50, "test" => '$conf->lareponse->enabled')
		);
		$this->rights = array();
		$r = 1100;
		$this->addRight($langs->trans('LaReponseReadObjects'), 'article', 'read');
		$this->addRight($langs->trans('LaReponseCreUpdArticles'), 'article', 'write');
		$this->addRight($langs->trans('LaReponseClosedArticles'), 'article', 'close');
		$this->addRight($langs->trans('LaReponseOpenedArticles'), 'article', 'open');
		$this->addRight($langs->trans('LaReponseUpdateOtherArticles'), 'article', 'update_other'); // #278
		$this->addRight($langs->trans('LaReponseDeleteArticles'), 'article', 'delete');
		$this->addRight($langs->trans('LaReponseConfigModule'), 'configure', '');
		$this->addRight($langs->trans('LaReponseCreUpdTags'), 'tag', 'write');
		$this->addRight($langs->trans('LaReponseDeleteTags'), 'tag', 'delete');
		$this->addRight($langs->trans('LaReponseExpImpArticles'), 'article', 'export');
		$this->addRight($langs->trans('LaReponsepublishArticles'), 'article', 'publish');

		$this->addTopMenu('lareponse', $langs->trans('Lareponse'), '/lareponse/lareponseindex.php', '', '', '$user->rights->lareponse->article->read', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_list', $langs->trans('Lareponse') . ' - v.' . $this->version, '/lareponse/lareponseindex.php', '', '', '$user->rights->lareponse->article->read', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_article', $langs->trans('ListArticle'), '/lareponse/article_list.php', '', $r++, '$user->rights->lareponse->article->read', '$conf->lareponse->enabled', '', 2);
		$this->addLeftSubMenu('lareponse', 'lareponse_article', 'lareponse_new_article', $langs->trans('NewArticle'), '/lareponse/article_card.php?action=create', '', $r++, '$user->rights->lareponse->article->write', '$conf->lareponse->enabled', '', 2);
		$this->addLeftSubMenu('lareponse', 'lareponse_article', 'lareponse_import', $langs->trans('NewArticleImport'), '/lareponse/article_import.php', '', $r++, '$user->rights->lareponse->article->export', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_tags', $langs->trans('ListTag'), '/lareponse/tagstree.php', '', $r++, '$user->rights->lareponse->article->read', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_setup', $langs->trans('ConfigurationMenu'), '/lareponse/admin/setup.php', '', $r++, '$user->rights->lareponse->configure', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_doc', $langs->trans('DocumentationMenu'), '/lareponse/user_doc.php', '', $r++, '$conf->lareponse->enabled', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_news', $langs->trans('NewsMenu'), '/lareponse/changelog.php', '', $r++, '$conf->lareponse->enabled', '$conf->lareponse->enabled', '', 2);
		$this->addLeftMenu('lareponse', 'lareponse_contactus', 'contactUs', '/h2g2/admin/contactus_page.php?module=modLareponse&modulePath=/lareponse/core/modules/modLareponse.class.php', '', $r++, '1', '', 'blank');

		// Constants
		$this->addConstant('LAREPONSE_WIZARD_ACTIVE', 'integer', 0);
		$this->addConstant('LAREPONSE_WIZARD_INDEX', 'chaine', '');
		$this->addConstant('LAREPONSE_WIZARD_ARTICLE_LIST', 'chaine', '');
		$this->addConstant('LAREPONSE_WIZARD_ARTICLE_ASSISTANT', 'chaine', '');
		$this->addConstant('LAREPONSE_TAG_GESTIONPARC_ACTIVE', 'integer', 0);
		$this->addConstant('LAREPONSE_TAG_CATEGORIES_ACTIVE', 'integer', 0);
		if (!isset($conf->global->LAREPONSE_IS_TAGS_MIGRATION_DONE))
			$this->addConstant('LAREPONSE_IS_TAGS_MIGRATION_DONE', 'integer', 0);
		if (!isset($conf->global->LAREPONSE_PREVIEW_TOOLTIP))
			$this->addConstant('LAREPONSE_PREVIEW_TOOLTIP', 'integer', 250);
		if (!isset($conf->global->LAREPONSE_WYSIWYG_MODE))
			$this->addConstant('LAREPONSE_WYSIWYG_MODE', 'chaine', 'dolibarr_notes'); // #233
		if (!isset($conf->global->LAREPONSE_PUBLIC_BANNER_COLOR))
			$this->addConstant('LAREPONSE_PUBLIC_BANNER_COLOR', 'chaine', 'EA7600');
        $this->addConstant('LAREPONSE_TOC_FOLD_DEFAULT', 'integer', 0);
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

		$langs->load("lareponse@lareponse");
		// [#181] If dummy is true, we print "Missing H2G2" error
		if ($this->dummy > 0) {
			setEventMessage($langs->trans("LaReponseModuleH2G2Missing", $this->name), 'errors');
			return 0;
		}

		// Module h2g2 must be in version 15.0.11 minimum
		$error = true;
		$minVersion = '15.0.11';
		if (class_exists('modH2G2')) {
			$h2g2 = new modH2G2($this->db);
			if (version_compare($h2g2->version, $minVersion, '>='))
				$error = false;
		}

		if ($error) {
			$message = $langs->trans('H2G2MinimumVersionRequired', $minVersion);
			setEventMessage($message, 'errors');
			return 0;
		}

		// Permissions
		$this->remove($options);

		// [#244] If bad version number is insert in DB, we delete it
		$sql = "SELECT module_version FROM " . MAIN_DB_PREFIX . "c42migration WHERE module_name = 'lareponse' AND entity = " . $conf->entity;
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			while ($row = $this->db->fetch_object($resql)) {
				if (!empty($row->module_version) && !in_array($row->module_version, $this->versionList)) $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "c42migration WHERE module_name = 'lareponse' AND module_version = '" . $row->module_version . "'");
			}
		}

		$sql = array();

		// Check if tags migration is needed
		if (!$conf->global->LAREPONSE_IS_TAGS_MIGRATION_DONE) {
			$res = $this->doTagsMigration();
			switch ($res) {
				case 0:
					setEventMessage('Migration successfully done. Table llx_lareponse_tag removed', 'mesgs');
					break;
				case -1:
					setEventMessage('Migration couldn\'t be done', 'errors');
					break;
				case 2:
					setEventMessage('Table Insertion Failed', 'errors');
					break;
				case 3:
					setEventMessage('Table lareponse_article_tag update failed', 'errors');
					break;
				case 4:
					setEventMessage('Table lareponse_tag failed to drop', 'errors');
					break;
			}
		}

		// Enable categorie module
		if (!$conf->categorie->enabled) activateModule('modCategorie', 1);

		$params = array(
			'lareponse' => array(
				'sharingelements' => array(
					'tag' => array(
						'type' => 'object',
						'icon' => 'fa-solid fa-tags',
					),
					'article' => array(
						'type' => 'object',
						'icon' => 'fa-solid fa-file',
					),
				),
			),
		);

		$externalmodule = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
		if ($externalmodule) {
			// if not null, array merge
			$externalmodule = array_merge($externalmodule, $params);
		} else {
			$externalmodule = $params;
		}
		$jsonformat = json_encode($externalmodule);

		// set const with json_encode
		dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", $jsonformat, 'chaine', 0, '', $conf->entity);
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
		$oldid = array(448125, 448400);
		// Delete llx_rights_def old ids from table
		$sql[] = 'DELETE FROM ' . MAIN_DB_PREFIX . 'rights_def WHERE id >= ' . $oldid[0] . ' AND id <= ' . ($oldid[0] + count($this->rights) - 1) . ' AND module = \'lareponse\'';
		$sql[] = 'DELETE FROM ' . MAIN_DB_PREFIX . 'rights_def WHERE id >= ' . $oldid[1] . ' AND id <= ' . ($oldid[1] + count($this->rights) - 1) . ' AND module = \'lareponse\'';
		return $this->_remove($sql, $options);
	}

	/**
	 * To do the lareponse tags migration. They need to be inserted from llx_lareponse_tag to llx_categorie table
	 *
	 * @return    integer        0 if success, -1 if llx_categorie not found, 1 if no llx_lareponse_tag table found, 2 if insertion failed, 3 if lareponse_article_tag update failed, 4 if lareponse_tag drop failed
	 */
	private function doTagsMigration()
	{
		global $db;

		// Get max rowid size from llx_categorie's table
		$resql = $db->query('SELECT MAX(rowid) FROM ' . MAIN_DB_PREFIX . 'categorie');
		if (!$resql) return -1;
		$result = ($db->fetch_array($resql))[0];
		// Set the rowid to 1 if result is null
		if (!$result) $rowidsize = 1;
		else $rowidsize = $result;
		// Check if llx_lareponse_tag table exists
		if (!($resql = $db->query('SHOW TABLES LIKE \'' . MAIN_DB_PREFIX . 'lareponse_tag\';'))) return -1;
		$result = ($db->fetch_array($resql))[0];
		if (!$result) {
			dolibarr_set_const($db, 'LAREPONSE_IS_TAGS_MIGRATION_DONE', 1);
			return 1;
		}
		// Insert llx_lareponse_tag table elements into llx_categorie table
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (rowid, entity, fk_parent, label, type, description, color, visible) ';
		$sql .= 'SELECT t.rowid+' . $rowidsize . ', 1, 0, t.label, 43, NULL, t.color, 0 FROM ' . MAIN_DB_PREFIX . 'lareponse_tag AS t';
		if (!$db->query($sql)) return 2;
		// Update the linker article-tags in consequence as the tags rowids may have been modified
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'lareponse_article_tag ';
		$sql .= 'SET fk_tag = fk_tag+' . $rowidsize;
		if (!$db->query($sql)) return 3;
		// Delete llx_lareponse_tag table
		$sql = 'DROP TABLE ' . MAIN_DB_PREFIX . 'lareponse_tag';
		if (!$db->query($sql)) return 4;
		// do not forget to set migration const to->done
		dolibarr_set_const($db, 'LAREPONSE_IS_TAGS_MIGRATION_DONE', 1);
		return 0;
	}
}
