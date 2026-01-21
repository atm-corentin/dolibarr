<?php
use h2g2\MigrationManager;

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
dol_include_once('/h2g2/class/migrationmanager.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/h2g2/lib/h2g2.lib.php');

/**
 *   .___________. __    __   _______      _______      ___       __          ___      ___   ___ ____    ____
 *  |           ||  |  |  | |   ____|    /  _____|    /   \     |  |        /   \     \  \ /  / \   \  /   /
 *  `---|  |----`|  |__|  | |  |__      |  |  __     /  ^  \    |  |       /  ^  \     \  V  /   \   \/   /
 *      |  |     |   __   | |   __|     |  | |_ |   /  /_\  \   |  |      /  /_\  \     >   <     \_    _/
 *      |  |     |  |  |  | |  |____    |  |__| |  /  _____  \  |  `----./  _____  \   /  .  \      |  |
 *      |__|     |__|  |__| |_______|    \______| /__/     \__\ |_______/__/     \__\ /__/ \__\     |__|
 *
 *                      o               .        ___---___                    .
 *                             .              .--\        --.     .     .         .
 *                                          ./.;_.\     __/~ \.
 *                                         /;  / `-'  __\    . \
 *                       .        .       / ,--'     / .   .;   \        |
 *                                       | .|       /       __   |      -O-       .
 *                                      |__/    __ |  . ;   \ | . |      |
 *                                      |      /  \\_    . ;| \___|
 *                         .    o       |      \  .~\\___,--'     |           .
 *                                       |     | . ; ~~~~\_    __|
 *                          |             \    \   .  .  ; \  /_/   .
 *                         -O-        .    \   /         . |  ~/                  .
 *                          |    .          ~\ \   .      /  /~          o
 *                        .                   ~--___ ; ___--~
 *                                       .          ---         .
 */
class TheGalaxy extends DolibarrModules
{

	/**
	 * @var string[]    List of module version
	 */
	public $versionList;

	/**
	 * @var string      Path of the migration folder for the module
	 */
	public $migrationPath;

	/**
	 * @var string      Default lang file used in module configuration
	 */
	public $defaultLangFile;

	/**
	 * @var int      	Set to 0 if H2G2 is imported, used to avoid error if not at module activating
	 */
	public $dummy;

	/**
	 * Url used when update is available for a module
	 */
	const DOLISTORE_URL = 'https://www.dolistore.com/fr/recherche?orderby=position&orderway=desc&search_query=code42';

	/**
	 * TheGalaxy constructor.
	 */
	public function __construct()
	{
		global $conf;

		// If not overwrited, default lang file will be the first one of the array of lang files
		if (!$this->defaultLangFile && $this->langfiles && count($this->langfiles) > 0) {
			$this->defaultLangFile = $this->langfiles[0];
		}

		$this->dummy = 0;

		$this->const = array();
		$this->tabs = array();
		$this->rights = array();
		$this->menu = array();
		$this->boxes = array();

		// Get module name and set up last version file url
		$moduleName = strtolower(preg_replace('/^mod/i', '', get_class($this)));
		if (empty($conf->global->H2G2_DISABLE_CHECK_LAST_VERSION)) $this->url_last_version = "https://git.code42.io/pub/badge-dolibarr/-/raw/main/last-version/$moduleName.txt?ref_type=heads";
	}

	/**
	 * Gives the translated module name if translation exists in admin.lang or into language files of module.
	 * Otherwise return the module key name.
	 *
	 * @param  int $forcePicto Include the picto for update / new
	 * @return string                          Translated module name
	 */
	public function getName($forcePicto = 1)
	{
		global $langs, $conf;
		$langs->load('h2g2@h2g2');

		$ret = parent::getName();

		// We only want to put the picto on the admin/modules.php page
		$withPicto = 0;
		if ($forcePicto) {
			if (strpos($_SERVER['REQUEST_URI'], 'admin/modules.php') !== false) {
				$withPicto = 1;
			}
		}

		// [#48] Configuration into H2G2 to enable/disable the update check
		if (property_exists($conf->global, 'H2G2_DISABLE_VERSION_MODULES') && !$conf->global->H2G2_DISABLE_VERSION_MODULES) {
			// Check last version installed
			if ($withPicto) {
				$lastVersionInstalled = MigrationManager::getLastVersionInstalled($this->rights_class);
				if (!$lastVersionInstalled || $lastVersionInstalled != $this->version) {
					// Last version installed is not the module version
					$picto = '&nbsp;&nbsp;<img src="' . dol_buildpath('h2g2/img/picto_new.png', 1) . '" class="valignmiddle" width="30" title="' . $langs->trans('H2G2NewModule') . '" alt="' . $langs->trans('H2G2NewModule') . '">';
				} else if ($this->isNewVersionAvailable()) {
					$picto = '<a href="' . self::DOLISTORE_URL . '" target="_blank">';
					$picto .= '&nbsp;&nbsp;<img src="' . dol_buildpath('h2g2/img/picto_update.png', 1) . '" class="valignmiddle" width="30" title="' . $langs->trans('H2G2UpdateModule') . '" alt="' . $langs->trans('H2G2UpdateModule') . '">';
					$picto .= '</a>';
				} else {
					$picto = '';
				}

				$ret .= $picto;
			}
		}

		return $ret;
	}

	/**
	 * Check if there is a new version of a module available
	 *
	 * @return boolean
	 */
	protected function isNewVersionAvailable()
	{
		global $conf;
		// TODO : find a new way to check for updates
		// For now, we connect to the following public doc and search in the content
		if (property_exists($conf->global, 'H2G2_VERSION_MODULES_URL') && $conf->global->H2G2_VERSION_MODULES_URL) {
			try {
				$docPath = $conf->global->H2G2_VERSION_MODULES_URL;
				$docContent = @file_get_contents($docPath);
				if ($docContent) {
					// Must be like <li>modulename : 13.0.0</li>
					$preg = "/<\s*li[^>]*>" . $this->rights_class . "\s*\:\s*(.*?)<\s*\/\s*li>/";
					preg_match($preg, $docContent, $matches);
					if ($matches && $matches[1]) {
						$version = $matches[1];
						if ($version > $this->version) {
							return true;
						}
					}
				}
			} catch (Exception $e) {
				dol_syslog('h2g2:TheGalaxy:isNewVersionAvailable ' . $e->getMessage(), LOG_ERR);
			}
		}

		return false;
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 * @param  array  $array_sql SQL requests to be executed when enabling module
	 * @param  string $options   Options when enabling module ('', 'noboxes')
	 * @return int                 1 if OK, 0 if KO
	 * @throws Exception
	 *
     * phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	 */
	public function _init($array_sql, $options = '')
	{
		global $conf, $langs, $db;
		$langs->load('h2g2@h2g2');

		// #108
		$changelogPath = '';
		if (file_exists(dol_buildpath('/' . $this->rights_class) . '/changelog.php')) {
			$changelogPath = dol_buildpath('/' . $this->rights_class, 1) . '/changelog.php';
		} else if (file_exists(dol_buildpath('/' . $this->rights_class) . '/admin/changelog.php')) {
			$changelogPath = dol_buildpath('/' . $this->rights_class, 1) . '/admin/changelog.php';
		}

		if (!empty($changelogPath)) {
			$date = $db->idate(strtotime('-30 min', dol_now()));
			$res = $db->query('SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c42migration WHERE module_name = "' . $this->rights_class .'" AND module_version >= "' . $this->version . '" AND date_creation >= "' . $date . '" ORDER BY date_creation DESC LIMIT 1');
			if ($res && $db->num_rows($res) > 0) createTopBarInfo($this->name, $langs->trans("H2G2TopBarNewVersion", $changelogPath), 448313);
		}


		if (!$this->migrationPath) {
			throw new Exception('Attribute \'migrationPath\' is not set in the module descriptor');
		}

		if (!$this->versionList) {
			throw new Exception('Attribute \'versionList\' is not set in the module descriptor');
		}

		// Check for h2g2 module activation which is required
		if (!$conf->h2g2->enabled) {
			setEventMessages($langs->trans('H2G2ModuleNotActivated'), null, 'errors');
			return -1;
		}

		// Check that module numero as been set
		if ($this->numero === 500000) {
			throw new Exception('You must define a module numero different than 500000');
		}

		// Manage sql migration
		$mm = new MigrationManager($this->db, get_class($this), $this->rights_class, $this->version, $this->versionList, $this->migrationPath);
		// [#63] Delete obsolete version form database
		$versionCorrected = $mm->correctInstalledVersions($this);
		if (!empty($versionCorrected)) {
			setEventMessages($langs->trans("H2G2CorrectedVersions", implode(",", $versionCorrected)), "", "warnings");
			$mm = new MigrationManager($this->db, get_class($this), $this->rights_class, $this->version, $this->versionList, $this->migrationPath);
		}
		$mm->launch();

		return parent::_init($array_sql, $options);
	}

	/**
	 * Disable function. Deletes the module constants and boxes from the database.
	 *
	 * @param string[] $array_sql SQL requests to be executed when module is disabled
	 * @param string   $options   Options when disabling module:
	 * @param object   $module    Optional module instance, if null -> defaults to $this
	 *
	 * @return int                     1 if OK, 0 if KO
	 */
	public function _remove($array_sql, $options = '', $module = '')
	{
        // phpcs:enable
		$err = 0;

		// Use $module if an external module instance is provided (like H2G2 which doesn't extend TheGalaxy).
		// Otherwise, use $this, which corresponds to a module that extends TheGalaxy.
		$module = !empty($module) ? $module : $this;
		$module->db->begin();
		// Remove activation module line (constant MAIN_MODULE_MYMODULE in llx_const)
		if (!$err) $err += $module->_unactive();

		// Remove activation of module's new tabs (MAIN_MODULE_MYMODULE_TABS_XXX in llx_const)
		if (!$err) $err += $module->delete_tabs();

		// Remove activation of module's parts (MAIN_MODULE_MYMODULE_XXX in llx_const)
		if (!$err) $err += $module->delete_module_parts();

		// Remove constants defined by modules
		if (!$err) $err += $module->delete_const();

		// Remove list of module's available boxes (entry in llx_boxes)
		// We don't have to delete if option ask to keep boxes safe or ask to add new box def only
		if (!$err && !preg_match('/(newboxdefonly|noboxes)/', $options)) $err += $module->delete_boxes();

		// Remove list of module's cron job entries (entry in llx_cronjobs)
		if (!$err) $err += $module->delete_cronjobs();

		// Remove module's permissions from list of available permissions (entries in llx_rights_def)
		if (!$err) $err += $module->delete_permissions();

		// Remove module's directories
		if (!$err) $err += $module->delete_dirs();

		// Run complementary sql requests
		$num = count((array) $array_sql);
		for ($i = 0; $i < $num; $i++) {
			if (!$err) {
				dol_syslog(get_class($module) . "::_remove", LOG_DEBUG);
				$result = $module->db->query($array_sql[$i]);
				if (!$result) {
					$module->error = $module->db->error();
					$err++;
				}
			}
		}

		// Return code
		if (!$err) {
			$module->db->commit();
			return 1;
		} else {
			$module->db->rollback();
			return 0;
		}
	}

	/**
	 * Add a right for the module.
	 *
	 * @param  string $label            Right label
	 * @param  string $level1           Level 1 of the right that will be checked ($user->rights->moduleRightClass->level1->level2)
	 * @param  string $level2           Level 2 of the right that will be checked ($user->rights->moduleRightClass->level1->level2)
	 * @param  string $type             Right type ('r', 'c', 'm' or 'd')
	 * @param  int    $enabledByDefault If the module is enabled by default for new users
	 * @return void
	 */
	public function addRight($label, $level1, $level2, $type = '', $enabledByDefault = 0)
	{
		if (!is_array($this->rights)) {
			$this->rights = array();
		}

		$id = $this->numero + count($this->rights);

		$this->rights[] = array(
			0 => $id,
			1 => $label,
			2 => $type,
			3 => $enabledByDefault,
			4 => $level1,
			5 => $level2
		);
	}

	/**
	 * Add a tab on the object header.
	 *
	 * @param  string $objectType Object element (ex: contact, intervention, project, ...)
	 * @param  string $tabId      Id of the tab to add
	 * @param  string $title      Title of the tab (It can be a translation key)
	 * @param  string $right      Right to check to add the tab (ex: '$user->rights->mymodule->level1->level2')
	 * @param  string $url        Url of the tab (ex: '/mymodule/mynewtab1.php?id=__ID__')
	 * @return void
	 */
	public function addTab($objectType, $tabId, $title, $right, $url)
	{
		if (!is_array($this->tabs)) {
			$this->tabs = array();
		}

		$this->tabs[] = array('data' => $objectType.':+'.$tabId.':'.$title.':'.$this->defaultLangFile.':'.$right.':'.$url);
	}

	/**
	 * Remove a tab on the object header
	 *
	 * @param  string $objectType Object element (ex: contact, intervention, project, ...)
	 * @param  string $tabId      Id of the tab to remove
	 * @param  string $condition  Condition to verify to remove the tab ('$user->admin != 1')
	 * @return void
	 */
	public function removeTab($objectType, $tabId, $condition = '')
	{
		if (!is_array($this->tabs)) {
			$this->tabs = array();
		}
		$this->tabs[] = array('data' => $objectType.':-'.$tabId.':NU:'.$condition);
	}

	/**
	 * Add a menu entry for the module, or update it if it's a 'menu version'.
	 *
	 * Example :
	 * When upgrading the module version from v1.0.0 to v2.0.0,
	 * the menu title will change from "Mod v1.0.0" to "Mod v2.0.0".
	 * Only the title of existing menus will be updated, if they have the same URL, mainmenu (can be empty), and leftmenu (can be empty), and their title contains a version pattern (digit.digit.digit).
	 *
	 * @param  string $type     Define if it's a top or a left menu ('left' or 'top')
	 * @param  string $fkMenu   Where to insert menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode of parent menu
	 * @param  string $mainMenu Main menu name
	 * @param  string $leftMenu Left menu name
	 * @param  string $title    Menu title
	 * @param  string $url      Target url for the link
	 * @param  int    $position Position of the menu entry
	 * @param  string $perms    Permission to see the menu (ex: '$user->rights->mymodule->level1->level2' or '1')
	 * @param  string $enabled  Condition to show / hide menu (ex: '$conf->mymodule->enabled')
	 * @param  string $target   Menu target, leave empty or use '_blank' to open in a new window / tab
	 * @param  int    $user     0 = Menu for internal users, 1 = external users, 2 = both
	 * @param  string $icon     Icon in front of title
	 * @return void
	 */
	public function addMenu($type, $fkMenu, $mainMenu, $leftMenu, $title, $url, $position, $perms, $enabled, $target, $user, $icon)
	{
		global $conf;
		if (!is_array($this->menu)) {
			$this->menu = array();
		}

		if (!empty($icon)) {
			$iconElem = '<i class="'.$icon.'"></i>&nbsp;';
		} else {
			$iconElem = '';
		}

		// #103 #104 update "version" menu
		if (preg_match('/\d+\.\d+\.\d+/', $title)) { // digit.digit.digit (20.1.01)
			$sql = "UPDATE " . MAIN_DB_PREFIX . "menu";
			$sql .= " SET titre = '" . $title . "'";
			$sql .= " WHERE module = '" . $this->rights_class . "'";
			$sql .= " AND type = '" . $type . "'";
			$sql .= " AND (mainmenu = '" . str_replace('fk_mainmenu=', '', $mainMenu) . "' OR mainmenu = '')"; // editing menu put mainmenu and leftmenu to '' -> L.75 admin/menus/edit.php
			if ($type == 'left') $sql .= " AND (leftmenu = '" . $leftMenu . "' OR leftmenu = '')";
			$sql .= " AND url LIKE '" . str_replace('?', '%', $url) . "'";
			$sql .= " AND titre REGEXP '[0-9]+\\.[0-9]+\\.[0-9]+'"; // update only menu who have digit.digit.digit
			$sql .= " AND entity = " . $conf->entity;

			$this->db->query($sql);
		}

		$ret = isMenuExist($this->rights_class, $type, $url, $mainMenu, $leftMenu); // We check by url, mainmenu, leftmenu, moduleName and 'type' in DB to see if the menu already exist or not
		if (!$ret) {
			$this->menu[] = array(
				'fk_menu' => $fkMenu,
				'type' => $type,
				'titre' => $iconElem.$title,
				'mainmenu' => $mainMenu,
				'leftmenu' => $leftMenu,
				'url' => $url,
				'langs' => $this->defaultLangFile,
				'position' => $position,
				'enabled' => $enabled,
				'perms' => $perms,
				'target' => $target,
				'user' => $user
			);
		}
	}

	/**
	 * Add a top menu entry for the module.
	 *
	 * @param  string $mainMenu Main menu name
	 * @param  string $title    Menu title
	 * @param  string $url      Target url for the link
	 * @param  string $icon     Icon in front of title
	 * @param  int    $position Position of the menu entry
	 * @param  string $perms    Permission to see the menu (ex: '$user->rights->mymodule->level1->level2' or '1')
	 * @param  string $enabled  Condition to show / hide menu (ex: '$conf->mymodule->enabled')
	 * @param  string $target   Menu target, leave empty or use '_blank' to open in a new window / tab
	 * @param  int    $user     0 = Menu for internal users, 1 = external users, 2 = both
	 * @return void
	 */
	public function addTopMenu($mainMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2)
	{
		if (empty($enabled)) {
			$enabled = '$conf->'.$this->rights_class.'->enabled';
		}

		if (!$position) {
			$position = 100 + count($this->menu);
		}

		$this->addMenu('top', '', $mainMenu, '', $title, $url, $position, $perms, $enabled, $target, $user, $icon);
	}

	/**
	 * Add a left menu entry for the module.
	 *
	 * @param  string $mainMenu Main menu name
	 * @param  string $leftMenu Left menu name
	 * @param  string $title    Menu title
	 * @param  string $url      Target url for the link
	 * @param  string $icon     Icon in front of title
	 * @param  int    $position Position of the menu entry
	 * @param  string $perms    Permission to see the menu (ex: '$user->rights->mymodule->level1->level2' or '1')
	 * @param  string $enabled  Condition to show / hide menu (ex: '$conf->mymodule->enabled')
	 * @param  string $target   Menu target, leave empty or use '_blank' to open in a new window / tab
	 * @param  int    $user     0 = Menu for internal users, 1 = external users, 2 = both
	 * @return void
	 */
	public function addLeftMenu($mainMenu, $leftMenu, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2)
	{
		if (empty($enabled)) {
			$enabled = '$conf->'.$this->rights_class.'->enabled';
		}

		if (!$position) {
			$position = 100 + count($this->menu);
		}

		$this->addMenu('left', 'fk_mainmenu='.$mainMenu, $mainMenu, $leftMenu, $title, $url, $position, $perms, $enabled, $target, $user, $icon);
	}

	/**
	 * Add a left menu entry for the module.
	 *
	 * @param  string $mainMenu    Main menu name
	 * @param  string $leftMenu    Left menu name
	 * @param  string $subMenuName Menu name
	 * @param  string $title       Menu title
	 * @param  string $url         Target url for the link
	 * @param  string $icon        Icon in front of title
	 * @param  int    $position    Position of the menu entry
	 * @param  string $perms       Permission to see the menu (ex: '$user->rights->mymodule->level1->level2' or '1')
	 * @param  string $enabled     Condition to show / hide menu (ex: '$conf->mymodule->enabled')
	 * @param  string $target      Menu target, leave empty or use '_blank' to open in a new window / tab
	 * @param  int    $user        0 = Menu for internal users, 1 = external users, 2 = both
	 * @return void
	 */
	public function addLeftSubMenu($mainMenu, $leftMenu, $subMenuName, $title, $url, $icon = '', $position = 0, $perms = '1', $enabled = '', $target = '', $user = 2)
	{
		if (empty($enabled)) {
			$enabled = '$conf->'.$this->rights_class.'->enabled';
		}

		if (!$position) {
			$position = 100 + count($this->menu);
		}

		$this->addMenu('left', 'fk_mainmenu='.$mainMenu.',fk_leftmenu='.$leftMenu, $mainMenu, $subMenuName, $title, $url, $position, $perms, $enabled, $target, $user, $icon);
	}

	/**
	 * Add all generic menus entry to the module
	 *
	 * @param  int $withTopMenu           Add the top menu entry or not
	 * @param  int $withMainMenu          Add the main menu entry or not
	 * @param  int $withSetupMenu         Add the setup menu entry or not
	 * @param  int $withDocumentationMenu Add the documentation entry or not
	 * @param  int $withNewsMenu          Add the news menu entry or not
	 * @param  int $withHelpMenu          Add the help menu entry or not
	 * @return void
	 */
	public function addGenericMenus($withTopMenu = 1, $withMainMenu = 1, $withSetupMenu = 1, $withDocumentationMenu = 1, $withNewsMenu = 1, $withHelpMenu = 1)
	{
		global $langs;
		$langs->load('h2g2@h2g2');

		$mainmenu = $this->rights_class;
		$classname = get_class($this);

		// Menu top
		if ($withTopMenu) {
			// Check if there is an index page
			$indexPage = '/'.$mainmenu.'/'.$mainmenu.'index.php';
			$indexPath = dol_buildpath($indexPage);
			if (!file_exists($indexPath)) {
				trigger_error('The file '.$indexPage.' doesn\'t exists. You should either create it or set the parameter \'$withTopMenu\' to 0', E_USER_ERROR);
			} else {
				$this->addTopMenu($mainmenu, $this->getName(0), $indexPage);
			}
		}

		// Main menu
		if ($withMainMenu) {
			// Check if there is an index page
			$indexPage = '/'.$mainmenu.'/'.$mainmenu.'index.php';
			$indexPath = dol_buildpath($indexPage);
			if (!file_exists($indexPath)) {
				trigger_error('The file '.$indexPage.' doesn\'t exists. You should either create it or set the parameter \'$withMainMenu\' to 0', E_USER_ERROR);
			} else {
				$this->addLeftMenu($mainmenu, $mainmenu . '_index', $this->getName(0) . ' - v.' . $this->version, $indexPage, '', 1);
			}
		}

		// Setup menu
		if ($withSetupMenu) {
			// Check if there is a setup page
			$indexPage = '/'.$mainmenu.'/admin/setup.php';
			$indexPath = dol_buildpath($indexPage);
			if (!file_exists($indexPath)) {
				trigger_error('The file '.$indexPage.' doesn\'t exists. You should either create it or set the parameter \'$withSetupMenu\' to 0', E_USER_ERROR);
			} else {
				$this->addLeftMenu($mainmenu, $mainmenu.'_setup', $langs->trans('H2G2SetupMenu'), $indexPage, 'fas fa-cog', 1000);
			}
		}

		// Documentation menu
		if ($withDocumentationMenu) {
			// Check if there is a documentation page
			$indexPage = '/'.$mainmenu.'/documentation.php';
			$indexPath = dol_buildpath($indexPage);
			if (!file_exists($indexPath)) {
				trigger_error('The file '.$indexPage.' doesn\'t exists. You should either create it or set the parameter \'$withDocumentationMenu\' to 0', E_USER_ERROR);
			} else {
				$this->addLeftMenu($mainmenu, $mainmenu.'_documentation', $langs->trans('H2G2DocumentationMenu'), $indexPage, 'fas fa-info', 1002);
			}
		}

		// News menu
		if ($withNewsMenu) {
			// Check if there is a news page
			$indexPage = '/'.$mainmenu.'/changelog.php';
			$indexPath = dol_buildpath($indexPage);
			if (!file_exists($indexPath)) {
				trigger_error('The file '.$indexPage.' doesn\'t exists. You should either create it or set the parameter \'$withNewsMenu\' to 0', E_USER_ERROR);
			} else {
				$this->addLeftMenu($mainmenu, $mainmenu.'_documentation', $langs->trans('H2G2NewsMenu'), $indexPage, 'fas fa-exclamation fa-beat', 1004);
			}
		}

		// Help menu
		if ($withHelpMenu) {
			$filePath = '/'.$mainmenu.'/core/modules/'.$classname.'.class.php';
			$this->addLeftMenu($mainmenu, $mainmenu.'_help', $langs->trans('H2G2HelpMenu'), '/h2g2/admin/information_page.php?module='.$classname.'&modulePath='.$filePath, 'fas fa-question-circle', 1006);
		}
	}

	/**
	 * Add a constant for the module.
	 *
	 * @param  string $name             Constant name (in full caps)
	 * @param  string $type             Constant type (ex: 'chaine')
	 * @param  string $value            Constant value
	 * @param  string $desc             Constant desc
	 * @param  int    $visible          Is constant visible in Setup->Other page (0 by default)
	 * @param  string $entity           Entity 'current' or 'allentities'
	 * @param  int    $deleteonunactive Delete on unactive
	 * @return void
	 */
	public function addConstant($name, $type, $value, $desc = '', $visible = 0, $entity = 'current', $deleteonunactive = 0)
	{
		if (!is_array($this->const)) {
			$this->const = array();
		}

		if (!isset($conf->global->$name)) {
			$this->const[] = array($name, $type, $value, $desc, $visible, $entity, $deleteonunactive);
		}
	}

	/**
	 * Add a widget to the module
	 *
	 * @param  string $file               Widget filename
	 * @param  string $note               Widget note
	 * @param  string $enabledbydefaulton Where to enable the widget by default
	 * @return void
	 */
	public function addWidget($file, $note = '', $enabledbydefaulton = 'Home')
	{
		if (!is_array($this->boxes)) {
			$this->boxes = array();
		}

		$this->boxes[] = array(
			'file' => $file.'@'.$this->rights_class,
			'note' => $note,
			'enabledbydefaulton' => $enabledbydefaulton
		);
	}

	/**
	 * Update or create extrafields if it doesn't exist
	 *
	 * @param  string $attrname       Code of attribute
	 * @param  string $label          Label of attribute
	 * @param  string $type           Type of attribute ('boolean','int','varchar','text','html','date','datehour','price','phone','mail','password','url','select','checkbox','separate',...)
	 * @param  int    $pos            Position of attribute
	 * @param  string $size           Size/length definition of attribute ('5', '24,8', ...). For float, it contains 2 numeric separated with a comma.
	 * @param  string $elementtype    Element type. Same value than object->table_element (Example 'member', 'product', 'thirdparty', ...)
	 * @param  int    $unique         Is field unique or not
	 * @param  int    $required       Is field required or not
	 * @param  string $default_value  Defaulted value (In database. use the default_value feature for default value on screen. Example: '', '0', 'null', 'avalue')
	 * @param  string $param          Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 * @param  int    $alwayseditable Is attribute always editable regardless of the document status
	 * @param  string $perms          Permission to check
	 * @param  string $list           Visibilty ('0'=never visible, '1'=visible on list+forms, '2'=list only, '3'=form only or 'eval string')
	 * @param  string $help           Text with help tooltip
	 * @param  string $computed       Computed value
	 * @param  string $entity         Entity of extrafields (for multicompany modules)
	 * @param  string $langfile       Language file
	 * @param  string $enabled        Condition to have the field enabled or not
	 * @param  int    $totalizable    Is a measure. Must show a total on lists
	 * @return int                             <= 0 on error, > 0 on success
	 */
	public function addUpdateExtrafields($attrname, $label, $type, $pos, $size, $elementtype, $unique = 0, $required = 0, $default_value = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0)
	{
		$extrafields = new Extrafields($this->db);
		$res = $extrafields->update($attrname, $label, $type, $size, $elementtype, $unique, $required, $pos, $param, $alwayseditable, $perms, $list, $help, $default_value, $computed, $entity, $langfile, $enabled, $totalizable);
		if ($res <= 0) {
			$res = $extrafields->addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $default_value, $param, $alwayseditable, $perms, $list, $help, $computed, $entity, $langfile, $enabled, $totalizable);
		}

		return $res;
	}

	/**
	 * Add translation contained in $this->overwrite_trans to llx_overwrite_trans table is it doesn't exist
	 *
	 * @param   $lang_table     string          Lang (en_US, fr_FR, es_ES ...)
	 * @param   $transkey       string          Translation key
	 * @param   $transvalue     string          Translation value
	 * @param   $entity         string|int      Entity or 'current' to select current entity
	 * @param   $mode           int             0 : Overwrite translation
	 *                          1 : Overwrite translation only if it doesn't exist
	 * @return                  int             0 if KO, 1 if OK
	 * @throws Exception
	 */
	public function overwriteTranslation($lang_table, $transkey, $transvalue, $entity = 'current', $mode = 0)
	{
		global $conf, $db;

		dol_syslog(get_class($this) . "::overwriteTranslation", LOG_DEBUG);

		$entity = (intval($entity) > 0 && $entity !== 'current') ? intval($entity) : $conf->entity;

		$sql = "SELECT rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "overwrite_trans";
		$sql .= " WHERE transkey = '" . $this->db->escape($transkey) . "'";
		$sql .= " AND lang = '" . $this->db->escape($lang_table) . "'";
		$sql .= " AND entity = " . $this->db->escape($entity);

		$result = $db->query($sql);

		if ($result) {
			// If translation not found, we add it in all case
			if ($this->db->num_rows($result) == 0) {
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "overwrite_trans (lang, transkey, transvalue, entity)";
				$sql .= " VALUES (";
				$sql .= "'" . $this->db->escape($lang_table) . "'";
				$sql .= ", '" . $this->db->escape($transkey) . "'";
				$sql .= ", '" . $this->db->escape($transvalue) . "'";
				$sql .= ", " . $this->db->escape($entity);
				$sql .= ")";

				if (!$this->db->query($sql)) {
					dol_syslog(get_class($this) . "::overwriteTranslation constant '" . $transkey . "' insertion failed", LOG_WARNING);
					return 0;
				}
				dol_syslog(get_class($this) . "::overwriteTranslation constant '" . $transkey . "' was inserted successfully", LOG_WARNING);
			} else if ($mode != 1) { // If row is found and mode = 0, we replace translation
				$sql = "UPDATE " . MAIN_DB_PREFIX . "overwrite_trans SET ";
				$sql .= "transvalue = '" . $this->db->escape($transvalue) . "' ";
				$sql .= "WHERE transkey = '" . $this->db->escape($transkey) . "' ";
				$sql .= "AND lang = '" . $this->db->escape($lang_table) . "' ";
				$sql .= "AND entity = " . $this->db->escape($entity);

				if (!$this->db->query($sql)) {
					dol_syslog(get_class($this) . "::overwriteTranslation constant '" . $transkey . "' update failed", LOG_WARNING);
					return 0;
				}
				dol_syslog(get_class($this) . "::overwriteTranslation constant '" . $transkey . "' was overwritten successfully", LOG_WARNING);
			}
		}

		return 1;
	}

	/**
	 * Remove translation from llx_overwrite_translation table
	 *
	 * @param   $lang_table     string          Lang (en_US, fr_FR, es_ES ...)
	 * @param   $transkey       string          Translation key
	 * @param   $entity         string|int      Entity or 'current' to select current entity
	 * @return                  int             0 if KO, 1 if OK
	 * @throws Exception
	 **/
	public function deleteOverwriteTranslation($lang_table, $transkey, $entity = 'current')
	{
		global $conf;

		$entity = (intval($entity) > 0 && $entity !== 'current') ? intval($entity) : $conf->entity;

		dol_syslog(get_class($this) . "::deleteOverwriteTranslation", LOG_DEBUG);

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "overwrite_trans";
		$sql .= " WHERE lang = '" . $this->db->escape($lang_table) . "'";
		$sql .= " AND transkey = '" . $this->db->escape($transkey) . "'";
		$sql .= " AND entity in (0, " . $this->db->escape($entity) . ")";

		// Send error if function not worked
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::deleteOverwriteTranslation constant deletion failed", LOG_DEBUG);
			return 0;
		}
		dol_syslog(get_class($this) . "::deleteOverwriteTranslation constant deleted", LOG_DEBUG);

		return 1;
	}

	/**
	 * Check for module update - rewrite this function to avoid error if not found
	 *
	 * @return int Return integer 0 if no update needed,  >0 if need update
	 */
	public function checkForUpdate()
	{
		$res = parent::checkForUpdate();
		// If last version file not found, dolibarr send a warning. We want to avoid this warning by returning 0 rather than -1
		return ($res == -1 ? 0 : $res);
	}
}
