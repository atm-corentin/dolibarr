<?php
/* Copyright (C) 2020 SuperAdmin
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
 * \file    h2g2/lib/h2g2.lib.php
 * \ingroup h2g2
 * \brief   Library files with common functions for H2G2
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function h2g2AdminPrepareHead()
{
	global $langs, $conf, $user;

	$langs->load("h2g2@h2g2");

	$h = 0;
	$head = array();

	if ($user->admin) {
		$head[$h][0] = dol_buildpath("/h2g2/admin/setup.php", 1);
		$head[$h][1] = $langs->trans("Settings");
		$head[$h][2] = 'settings';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/h2g2/admin/news.php", 1);
	$head[$h][1] = $langs->trans("H2G2NewsTab");
	$head[$h][2] = 'news';
	$h++;

	if ($user->admin) {
		$head[$h][0] = dol_buildpath("/h2g2/admin/theme.php", 1);
		$head[$h][1] = $langs->trans("H2G2ThemeTab");
		$head[$h][2] = 'theme';
		$h++;
		$head[$h][0] = dol_buildpath("/h2g2/admin/about.php", 1);
		$head[$h][1] = $langs->trans("About");
		$head[$h][2] = 'about';
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'h2g2');

	return $head;
}

/**
 * Get the url of datatable translation file
 *
 * @return string                   Url
 */
function getDatatableLanguageUrl()
{
	global $langs;

	return dol_buildpath('/h2g2/langs/' . $langs->getDefaultLang() . '/datatable.json', 1);
}

/**
 * Check if H2G2 module is installed with a version equal or superior as the given version
 *
 * @param  string    $minVersion    H2G2 module min version to check
 * @return bool
 */
function isH2G2InstalledWithMinVersion($minVersion)
{
	global $db;

	dol_include_once('/h2g2/core/modules/modH2G2.class.php');
	$h2g2 = new modH2G2($db);
	return version_compare($h2g2->version, $minVersion, '>=');
}

/**
 * Get list of modules not up to date
 *
 * @return array
 */
function getH2G2ModulesNotUpToDate()
{
	global $db, $conf, $langs;

	// Get all h2g2 modules installed with their max version on db
	$modulesInstalled = array();
	$sql = "SELECT module_name AS name, MAX(module_version) AS lastVersion FROM " . MAIN_DB_PREFIX . "c42migration WHERE entity = " . $conf->entity . " GROUP BY module_name";
	$resql = $db->query($sql);
	if ($resql) {
		while ($module = $db->fetch_object($resql)) {
			$modulesInstalled[$module->name] = $module->lastVersion;
		}
	}

	// Check which module is not updated
	$modulesToUpdate = array();
	$modulesdir = dolGetModulesDirs();
	foreach ($modulesdir as $dir) {
		$handle = @opendir(dol_osencode($dir));
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (is_readable($dir . $file) && preg_match("/^(mod.*)\.class\.php$/i", $file, $reg)) {
					$modulePath = str_replace(DOL_DOCUMENT_ROOT, '', $dir . $file);
					$moduleClassname = $reg[1];
					dol_include_once($modulePath);
					$moduleDescriptor = new $moduleClassname($db);
					$name = $moduleDescriptor->rights_class;
					$version = $moduleDescriptor->version;
					if (array_key_exists($name, $modulesInstalled) && version_compare($version, $modulesInstalled[$name], '>')) {
						$langs->load($name . '@' . $name);
						$modulesToUpdate[] = array('actual_version' => $modulesInstalled[$name], 'update_version' => $version, 'fullname' => $langs->trans($moduleDescriptor->name), 'name' => $name, 'className' => $moduleClassname);
					}
				}
			}
		}
	}

	return $modulesToUpdate;
}

/**
 * Display a multi select button with a dropdown.
 * A button definition must be defined like the following :
 *
 * array('href', 'withoutAction', 'label', 'disabled', 'title', 'picto', 'color')
 *
 * Example :
 *
 * array(
 *     'href' => $_SERVER['PHP_SELF'].'?action=new',
 *     'withoutAction' => false,
 *     'label' => $langs->trans('New'),
 *     'picto' => 'plus-circle',
 *     'colorbtn' => '#ffa500',
 *     'disabled' => $user->rights->h2g2->read,
 *     'title' => ($user->rights->h2g2->read ? '' : $langs->trans('NoRight')),
 * );
 *
 * @param  array      $mainBtn      Main button definition
 * @param  array      $entries      List of button definition
 * @param  boolean    $direction    Option direction display. Up if true, Down if false
 * @return string                      HTML to display
 */
function buildMultiEntriesButton($mainBtn, $entries, $direction = true)
{
	// Without action buttons are only used to group multiple actions with a main bouton
	$withoutAction = false;
	$morecss = '';
	if (key_exists('withoutAction', $mainBtn) && $mainBtn['withoutAction']) {
		$withoutAction = true;
		$morecss = ' without-action';
	}

	$str_direction = 'up';
	$str_directioninv = 'down';

	if (!$direction) {
		$str_direction = 'down';
		$str_directioninv = 'up';
	}

	$ret = '<div class="h2g2multiselect' . $morecss . '">';

	// Main button
	$ret .= '<div class="h2g2tooltip" data-direction="left">';

	if ($mainBtn['disabled'] ?? false) {
		$ret .= '<a href="#" class="butActionRefused h2g2multiselect__button h2g2tooltip__initiator' . $morecss . '">';

		$ret .= $mainBtn['picto'] . " " . $mainBtn['label'];

		if ($withoutAction) {
			// Without action button integrate the toggle in the button itself
			$ret .= '<span class="h2g2multiselect__chevron options' . $str_direction . '"><i class="fas fa-chevron-' . $str_direction . '"></i></span>';
		}
		$ret .= '</a>';
	} else {
		if ($mainBtn['colorbtn'] ?? false) {
			$ret .= '<a title="'. $mainBtn['title'] .'" href="' . $mainBtn['href'] . '" ' . (isset($mainBtn['colorbtn']) ? 'style="--btn-color:' . $mainBtn['colorbtn'] . '"' : '') . ' class="butAction h2g2multiselect__button classfortooltip' . $morecss . '">';
		} else {
			$ret .= '<a title="'. $mainBtn['title'] .'" href="' . $mainBtn['href'] . '" class="butAction h2g2multiselect__button classfortooltip' . $morecss . '">';
		}
		$ret .= $mainBtn['picto'] . " " . $mainBtn['label'];
		if ($withoutAction) {
			// Without action button integrate the toggle in the button itself
			$ret .= '<span style="background:' . $mainBtn['colorbtn'] . '" class="h2g2multiselect__chevron options' . $str_direction . '"><i class="fas fa-chevron-' . $str_direction . '"></i></span>';
		}
		$ret .= '</a>';
	}

	$ret .= '</div>';

	// Toggle button
	if (!$withoutAction) {
		// on récupere ici tout les paramètres dans href que l'on stock dans $params sous forme de tableau
		parse_str(parse_url($mainBtn['href'], PHP_URL_QUERY), $params);
		// The toggle button is not seprated for without action button
		$ret .= '<a ' . (isset($params['action']) ? 'href="action=' . $params['action'] . '"' : '') . ' class="butAction h2g2multiselect__chevron ' . $str_directioninv . '" ' . (isset($mainBtn['colorbtn']) ? 'style="--btn-color: ' . $mainBtn['colorbtn'] . '"' : '') . '><i class="fas fa-chevron-' . $str_directioninv . '"></i> </a>';
	}

	// Entries
	$ret .= '<div class="h2g2multiselect__options options' . $str_direction . ' hidden">';
	foreach ($entries as $entry) {
		$ret .= '<div title="' . $entry['title'] . '" class="classfortooltip" data-direction="left">';
		$ret .= '<div onclick="window.location.href=\'' . $entry['href'] . '\'" class="h2g2multiselect__options-entry ' . (!empty($entry['disabled']) ? 'disabled' : '') . ' h2g2tooltip__initiator">';
		if ($entry['pictocolor'] ?? false) $entry['picto'] = str_replace('class=', 'style="color:' . $entry['pictocolor'] . '" class=', $entry['picto']);
		$ret .= '<div class="h2g2multiselect__options-entry_label">';
		if (!empty($entry['disabled'])) $ret .= '<a href="#">' . $entry['picto'] . $entry['label'] . '</a>';
		else $ret .= '<a href="' . $entry['href'] . '">' . $entry['picto'] . $entry['label'] . '</a>';
		$ret .= '</div>';
		$ret .= '</div>';
		$ret .= '</div>';
	}
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}


/**
 * Display a multi select button with a dropdown.
 * A button definition must be defined like the following :
 *
 * array('href', 'rights')
 *
 * Example :
 *
 * array(
 *     'href' => $_SERVER['PHP_SELF'].'?action=new',
 *     'rights' => $user->rights->h2g2->read,
 * );
 *
 * @param  string    $type       create or delete
 * @param  array     $mainBtn    Main button definition
 * @param  array     $entries    List of button definition
 * @return string                      HTML to display
 */
function buildStandarMultiEntriesButton($type, $mainBtn, $entries)
{
	global $langs;
	// Without action buttons are only used to group multiple actions with a main bouton
	$morecss = '';

	$ret = '<div class="h2g2multiselect' . $morecss . '">';
	$ret .= '<div class="h2g2tooltip" data-direction="left">';

	$ret .= '<a style="--btn-color: var(' . ( $type == 'create' ? '--btn-color-success' : '--btn-color-danger' ) . ')"';

	if ($mainBtn['rights']) {
		$ret .= 'href="' . $mainBtn['href'] . '" class="h2g2multiselect_' . $type . ' butAction h2g2multiselect__button h2g2tooltip__initiator' . $morecss . '">';
	} else {
		$ret .= 'href="#" class="butActionRefused h2g2multiselect__button h2g2tooltip__initiator' . $morecss . '">';
	}

	if ($type == 'create') {
		$ret .= '<i class="fas fa-plus"></i> ' . $langs->trans('Create');
	} else {
		$ret .= '<i class="fas fa-times"></i> ' . $langs->trans('Delete');
	}

	$ret .= '</a>';
	$ret .= '</div>';

	$ret .= '<a style="--btn-color: var(' . ( $type == 'create' ? '--btn-color-success' : '--btn-color-danger' ) . ')"';

	$ret .= 'href="#" class="h2g2multiselect_' . $type . ' butAction h2g2multiselect__chevron down"><i class="fas fa-chevron-down"></i> </a>';

	// Entries
	$ret .= '<div class="h2g2multiselect__options optionsup hidden">';

	if ($type == 'create') {
		// Modify Button
		$modifyBtn = $entries[0];
		$ret .= '<div class="h2g2tooltip" data-direction="left">';
		$ret .= '<div class="h2g2multiselect__options-entry ' . ($modifyBtn['rights'] ? '' : 'disabled') . ' h2g2tooltip__initiator">';
		$ret .= '<div class="h2g2multiselect__options-entry_icon">';
		$ret .= '<i class="fas fa-pen" style="color:orange"></i>';
		$ret .= '</div>';
		$ret .= '<div class="h2g2multiselect__options-entry_label">';
		if ($modifyBtn['rights']) {
			$ret .= '<a href="' . $modifyBtn['href'] . '">' . $langs->trans('Modify') . '</a>';
		} else {
			$ret .= '<a href="#">' . $langs->trans('Modify') . '</a>';
		}
		$ret .= '</div>';
		$ret .= '</div>';
		$ret .= '</div>';

		// Save Button
		$saveBtn = $entries[1];
		$ret .= '<div class="h2g2tooltip" data-direction="left">';
		$ret .= '<div class="h2g2multiselect__options-entry ' . ($saveBtn['rights'] ? '' : 'disabled') . ' h2g2tooltip__initiator">';
		$ret .= '<div class="h2g2multiselect__options-entry_icon">';
		$ret .= '<i class="fas fa-save" style="color:green"></i>';
		$ret .= '</div>';
		$ret .= '<div class="h2g2multiselect__options-entry_label">';
		if ($saveBtn['rights']) {
			$ret .= '<a href="' . $saveBtn['href'] . '">' . $langs->trans('Save') . '</a>';
		} else {
			$ret .= '<a href="#">' . $langs->trans('Save') . '</a>';
		}
	} else {
		// Cancel Button
		$cancelBtn = $entries[0];
		$ret .= '<div class="h2g2tooltip" data-direction="left">';
		$ret .= '<div class="h2g2multiselect__options-entry ' . ($cancelBtn['rights'] ? '' : 'disabled') . ' h2g2tooltip__initiator">';
		$ret .= '<div class="h2g2multiselect__options-entry_icon">';
		$ret .= '<i class="fas fa-redo" style="color:gray"></i>';
		$ret .= '</div>';
		$ret .= '<div class="h2g2multiselect__options-entry_label">';
		if ($cancelBtn['rights']) {
			$ret .= '<a href="' . $cancelBtn['href'] . '">' . $langs->trans('Cancel') . '</a>';
		} else {
			$ret .= '<a href="#">' . $langs->trans('Cancel') . '</a>';
		}
	}

	$ret .= '</div>';
	$ret .= '</div>';
	$ret .= '</div>';
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Create a TopBarInfo
 * @param   string      $title      title of the news
 * @param   string      $content    content of the news
 * @param   int         $type       type of the news<br/>
 *                                  448312 => Green / Success<br/>
 *                                  448313 => LightBlue / Information<br/>
 *                                  448314 => Yellow / Warning<br/>
 *                                  448315 => Red / Danger (can't be close)<br/>
 * @param   datetime    $datestart  when the news are gonna to be display<br/>
 *                                  by default -> dol_now()
 * @param   datetime    $dateend    when the news are gonna to be remove<br/>
 *                                  by default -> dol_now() + 12 hours
 * @return  int                     <0 if KO, id of the news if OK
 */
function createTopBarInfo($title = '', $content = '', $type = 448312, $datestart = '', $dateend = '')
{
	global $db, $user;

	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	if (empty($datestart)) $datestart = dol_now();
	if (empty($dateend)) $dateend = dol_now() + 48 * 3600; // 48 hours later


	$sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'actioncomm';
	$sql .= ' WHERE (label LIKE "%' . $db->escape($title) . '%" OR note LIKE "%' . $db->escape($content) . '%")';
	$sql .= ' AND fk_action = ' . $type;
	$sql .= " AND datep >= '" . $db->idate(($datestart - (48 * 3600))) . "' AND datep2 <= '" . $db->idate($dateend) . "'";

	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql) > 0) {
			$obj = $db->fetch_object($resql);

			$actioncomm = new ActionComm($db);
			$actioncomm->fetch($obj->id);

			$actioncomm->datep = $datestart;
			$actioncomm->datef = $dateend;

			$ret = $actioncomm->update($user);

			return $ret;
		} else {
			$actioncomm = new ActionComm($db);
			$actioncomm->userownerid = $user->id;
			$actioncomm->label = $title;
			$actioncomm->note = $content;
			$actioncomm->type_code = $type;
			$actioncomm->datep = $datestart;
			$actioncomm->datef = $dateend;
			$id = $actioncomm->create($user);
			if ($id > 0) return $id;
			else return -1;
		}
	}

	return -1;
}

/**
 * Check if Menu exist
 * @param   string          $modName        moduleName / right_class
 * @param   string          $type          'left' or 'top'
 * @param   string          $url            target url of the menu
 * @param   string          $mainMenu       Main menu name (default '')
 * @param   string          $leftMenu       Left menu name (default '')
 *
 * @return  int                             0 doesn't exist / 1 exist
 */
function isMenuExist($modName, $type, $url, $mainMenu = '', $leftMenu = '')
{
	global $db, $conf;

	$sql = "SELECT *";
	$sql .= " FROM " . MAIN_DB_PREFIX . "menu";
	$sql .= " WHERE type = '" . $type . "'";
	$sql .= " AND module = '" . $modName . "'";
	$sql .= " AND (mainmenu = '" . str_replace('fk_mainmenu=', '', $mainMenu) . "' OR mainmenu = '' OR mainmenu IS NULL)"; // editing menu put mainmenu and leftmenu to '' -> L.75 admin/menus/edit.php
	if ($type == 'left') $sql .= " AND (leftmenu = '" . $leftMenu . "' OR leftmenu = '' OR leftmenu IS NULL)";
	$sql .= " AND url LIKE '" . str_replace('?', '%', $url) . "'";
	$sql .= " AND entity = " . $conf->entity;
	$sql .= " ORDER BY tms DESC LIMIT 1";
	$resql = $db->query($sql);

	if ($resql && $db->num_rows($resql) == 0) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Update a menu field
 * @param string        $modName        moduleName / right_class
 * @param string        $type           'left' or 'top'
 * @param string        $url            target url of the menu
 * @param string        $fieldName      the field's name (titre, position, type...)
 * @param string        $fieldValue     the news field's value
 *
 * @return  int                         <0 if KO, >0 if OK
 */
function updateMenu($modName, $type, $url, $fieldName, $fieldValue)
{
	// #102
	global $db, $conf;

	$sql = "UPDATE " . MAIN_DB_PREFIX . "menu";
	$sql .= " SET " . $fieldName . " = '" . $db->escape($fieldValue) . "'";
	$sql .= " WHERE module = '" . $modName ."'";
	$sql .= " AND type = '" . $type . "'";
	$sql .= " AND url = '" . $url ."'";
	$sql .= " AND entity = " . $conf->entity;
	$resql = $db->query($sql);
	if ($resql) {
		return 1;
	} else {
		return -1;
	}
}


/**
 *    Insert a parameter (key,value) into database (delete old key then insert it again).
 *
 * @param  DoliDB    $db           Database handler
 * @param  string    $name         Name of constant
 * @param  string    $value        Value of constant
 * @param  string    $type         Type of constant. Deprecated, only strings are allowed for $value. Caller must json encode/decode to store other type of data.
 * @param  int       $visible      Is constant visible in Setup->Other page (0 by default)
 * @param  string    $note         Note on parameter
 * @param  int       $entity       Multi company id (0 means all entities)
 * @param  string    $usedTable    Table used to store constant value
 * @return     int                    -1 if KO, 1 if OK
 *
 * @see        dolibarr_set_const(), dolibarr_del_const(), dolibarr_get_const(), dol_set_user_param()
 */
function setExternalConst($db, $name, $value, $type = 'chaine', $visible = 0, $note = '', $entity = 1, $usedTable = "")
{
	if (empty($usedTable)) dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $entity);
	else {
		global $conf;

		// Clean parameters
		$name = trim($name);

		// Check parameters
		if (empty($name)) {
			dol_print_error($db, "Error: Call to function dolibarr_set_const with wrong parameters", LOG_ERR);
			exit;
		}

		dol_syslog("H2G2 - setExternalConst name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity", LOG_DEBUG);

		$db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $usedTable;
		$sql .= " WHERE name = " . $db->encrypt($name);
		if ($entity >= 0) $sql .= " AND entity = " . ((int) $entity);

		$resql = $db->query($sql);

		if (strcmp($value, '')) {    // true if different. Must work for $value='0' or $value=0
			if (!preg_match('/^(MAIN_LOGEVENTS|MAIN_AGENDA_ACTIONAUTO)/', $name) && (preg_match('/(_KEY|_EXPORTKEY|_SECUREKEY|_SERVERKEY|_PASS|_PASSWORD|_PW|_PW_TICKET|_PW_EMAILING|_SECRET|_SECURITY_TOKEN|_WEB_TOKEN)$/', $name))) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
				$newvalue = dolEncrypt($value);
			} else $newvalue = $value;

			$sql = "INSERT INTO " . MAIN_DB_PREFIX . $usedTable . "(name, value, type, visible, note, entity)";
			$sql .= " VALUES (";
			$sql .= $db->encrypt($name);
			$sql .= ", " . $db->encrypt($newvalue);
			$sql .= ", '" . $db->escape($type) . "', " . ((int) $visible) . ", '" . $db->escape($note) . "', " . ((int) $entity) . ")";

			$resql = $db->query($sql);
		}

		if ($resql) {
			$db->commit();
			$conf->global->$name = $value;
			return 1;
		} else {
			$error = $db->lasterror();
			$db->rollback();
			return -1;
		}
	}
}

/**
 * Get const from external table or core's one
 *
 * @param  string    $name         Constant name
 * @param  string    $usedTable    Table used to get const
 * @return string|void Constant value
 */
function getExternalConst($name, $usedTable)
{
	global $db, $conf;

	if (!empty($usedTable)) {
		$sql = "SELECT value FROM " . MAIN_DB_PREFIX . $usedTable;
		$sql .= " WHERE name = " . $db->encrypt($name);
		$sql .= " AND entity = " . ((int) $conf->entity);

		$resql = $db->query($sql);

		if ($resql) return ($db->fetch_object($resql)->value ?? "");
	}
	if (isset($conf->global->$name)) return $conf->global->$name;
	return "";
}


/**
 * Generates a boolean with an icon
 *
 * @param boolean $success      true for success icon or false for failure icon
 * @param string|null $size     (Optional) FA size of the icon https://docs.fontawesome.com/web/style/size
 * @return string
 */
function generateBoolean($success, $size = null) {
    $icon = $success ? 'fas fa-check-circle' : 'fas fa-times-circle';

    return '<i class="' . $icon . ' ' . $size . '" style="color: ' .  ($success ? 'green' : 'red') . '"></i>';
}

/**
 * Generates a font awesome based on several parameters
 * https://fontawesome.com/v5/search
 *
 * Example usage:
 *
 * print generateIcon("fas fa-cogs", "red", "fa-5x", "width: 500px", "title=\\"help\\"");
 *
 * @param string $name Name of the icon ("fas fa-check-circle", "fas fa-database", etc...)
 * @param string|null $color CSS color of the icon ("red", "rgb(X,X,X)", "#000000", etc...)
 * @param string|null $size FontAwesome size of the icon ("fa-xs", "fa-5x", etc...) https://docs.fontawesome.com/v5/web/style/size
 * @param string|null $customStyle Custom CSS style
 * @return string
 */
function generateIcon($name, $color = "", $size = "", $customStyle = "", $customParameter = "") {
    $style = "";

    if (!empty($color)) $style .= 'color:' . $color . ';';
    if (!empty($customStyle)) $style .= $customStyle;
    if (!empty($style)) $style = 'style="' . $style . '"';

    return '<i class="' . $name . ' ' . $size . '" ' . $style . ' ' . $customParameter . ' ></i>';
}

/**
 * Generates a tooltip using various parameters
 *
 *  Example usage:
 *
 *  print generateTooltip("Hover me", "I am in the popup", "color: red", "color: blue", "right");
 *
 * @param string $innerHtml     HTML element that opens the popup when hovered
 * @param string $tooltipHtml   HTML content for the popup
 * @param string $innerCss      CSS for the hoverable element
 * @param string $tooltipCss    CSS for the popup
 * @param string $position      Position of the popup: "top", "bottom" (default), "left", "right"
 * @return string
 */
function generateTooltip($innerHtml, $tooltipHtml, $innerCss = "", $tooltipCss = "", $position = "bottom") {
    $innerCss = !empty($innerCss) ? 'style="' . $innerCss . '"' : "";
    $tooltipCss = !empty($tooltipCss) ? 'style="' . $tooltipCss . '"' : "";

    return '<div class="h2g2tooltip" ' . $innerCss . '>'
            . $innerHtml
            . '<div class="h2g2tooltiptext ui-widget-shadow h2g2' . $position . '" ' . $tooltipCss . '>' . $tooltipHtml . '</div>'
        . '</div>';
}

/**
 * Checks if a var is empty
 * For arrays, checks if all its values are empty
 * For objects, checks if all its properties are empty
 *
 * @param mixed $var            The variable to check
 * @param string|null $key      The key to check
 * @param boolean|null $nested  In case of an array or object, consider verifying each child
 * @return bool                 true if empty, false otherwise
 */
function isVarEmpty($var, $key = null, $nested = false) {
    if (is_array($var)) {
        if ($key) return empty($var[$key]);
        foreach ($var as $value) {
            if ($nested ? !isVarEmpty($value, null, true) : !empty($value)) return false;
        }
        return true;
    } else if (is_object($var)) {
        if ($key) return empty($var->$key);
        foreach (get_object_vars($var) as $value) {
            if ($nested ? !isVarEmpty($value, null, true) : !empty($value)) return false;
        }
        return true;
    } else return empty($var);
}
