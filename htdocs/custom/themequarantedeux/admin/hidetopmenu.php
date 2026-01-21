<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    themequarantedeux/admin/hidetopmenu.php
 * \ingroup themequarantedeux
 * \brief   ThemeQuarantedeux hidetopmenu page.
 */

// Load Dolibarr environment
$res = 0;
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1'); // Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


global $langs, $user, $conf, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/hidetopmenu.lib.php';
require_once '../lib/themequarantedeux.lib.php';

// Translations
$langs->loadLangs(array("admin", "themequarantedeux@themequarantedeux"));

// Access control
if (!$user->rights->themequarantedeux->configure) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

$activatedModules = getTopMenuEntries();

if (isset($conf->global->HIDETOPMENU_ENTRIES)) {
	$hidedEntries = explode(';', $conf->global->HIDETOPMENU_ENTRIES);
} else {
	$hidedEntries[] = "";
}

if (!isset($conf->global->HIDETOPMENU_ALL_ENTITIES)) dolibarr_set_const($db, 'HIDETOPMENU_ALL_ENTITIES', '0', 'chaine', 0, '', $conf->entity);

$error = 0;
$setupnotempty = 0;


/*
 * Actions
 */

$activateForAllEntities = 0;

if ($action == 'update') {
	if (!empty($activatedModules)) {
		// Get all modules to hide and put it as a constant seperated with ';'
		$formatedConstant = '';
		foreach ($activatedModules as $key => $module) {
			if (!$key) continue;
			if (GETPOST($key) === 'on') {
				$formatedConstant.= $module['mainmenu'].';';
			}
		}
	}

	if (strlen($formatedConstant) > 0) {
		// Delete last ';' character
		$formatedConstant = substr($formatedConstant, 0, -1);
	}

	// Update the constant
	if ($conf->global->HIDETOPMENU_ALL_ENTITIES == '1') {
		$entity = $activateForAllEntities;
	} else {
		$entity = $conf->entity;
	}

	$res = dolibarr_set_const($db, 'HIDETOPMENU_ENTRIES', $formatedConstant, 'chaine', 0, '', $entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	exit(header("Location:".$_SERVER['PHP_SELF']));
} else if ($action == 'setMulticompanyOption') {
	$value = GETPOST('value', 'int');

	// Update the constant
	$res = dolibarr_set_const($db, 'HIDETOPMENU_ALL_ENTITIES', $value, 'chaine', 0, '', $activateForAllEntities);

	if ($res > 0) {
		if ($value == '1') {
			// Set the entries of the entity for all entites
			$entityValue = $conf->global->HIDETOPMENU_ENTRIES;
			// Delete const for all entities to copy actual one
			dolibarr_del_const($db, 'HIDETOPMENU_ENTRIES', -1);
			$res = dolibarr_set_const($db, 'HIDETOPMENU_ENTRIES', $entityValue, 'chaine', 0, '', $activateForAllEntities);
		} else {
			// Delete the entries set for all entites
			dolibarr_del_const($db, 'HIDETOPMENU_ENTRIES', $activateForAllEntities);
		}
	}

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	exit(header("Location:".$_SERVER['PHP_SELF']));
}

/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "HideTopMenuSetup";
$arrayofcss = array(dol_buildpath('themequarantedeux/css/switchButton.css', 1));
llxHeader('', $langs->trans($page_name), '', '', 0, 0, array(), $arrayofcss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_hidetopmenu@themequarantedeux');

// Configuration header
$head = themequarantedeuxAdminPrepareHead();
dol_fiche_head($head, 'hidetopmenu', '', -1, "hidetopmenu@themequarantedeux");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("HideTopMenuSetupPage").'</span><br><br>';

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield" width="50%">'.$langs->trans("Parameter").'</td><td width="50%">'.$langs->trans("Value").'</td></tr>';

	foreach ($activatedModules as $key => $module) {
		if (!$key) continue;
		print '<tr class="oddeven"><td>';

		// Title can eather be an array or a string
		$title = '';
		if (is_array($module['title'])) {
			foreach ($module['title'] as $value) {
				$title.= $langs->trans($value).' ';
			}
			// Remove last space
			$title = substr($title, 0, -1);
		} else {
			$title = $langs->trans($module['title']);
		}
		print $title;

		print '</td><td>';
		//<input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.(in_array($key, $hidedEntries) ? '1' : '0').'">
		print '<div class="switch">';
		print '<input id="' . $key . '" name="' . $key . '" class="input" type="checkbox"';
		if (in_array($module['mainmenu'], $hidedEntries)) print ' checked';
		print '>';
		print '<label for="' . $key . '" class="slider">';
		print '</div>';

		print '</td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	// Print global option
	print '<h2><i class="fas fa-globe"></i>&nbsp;'.$langs->trans('GlobalOption').'</h2>';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield" width="50%">'.$langs->trans("Parameter").'</td><td width="50%">'.$langs->trans("Value").'</td></tr>';
	print '<tr><td>'.$langs->trans('ActivateMultiCompanyOption').'</td>';
	print '<td>';
	if ($conf->global->HIDETOPMENU_ALL_ENTITIES == '1') {
		print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setMulticompanyOption&value=0"><span class="fas fa-toggle-on font-status4" title="'.$langs->trans('Enabled').'"></span></a>';
	} else {
		print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setMulticompanyOption&value=1"><span class="fas fa-toggle-off" style=" color: #999;" title="'.$langs->trans('Disabled').'"></span></a>';
	}
	print '</td>';
	print '</tr>';
	print '</table>';

	// Print specific modules option
	print '<h2><i class="far fa-eye-slash"></i>&nbsp;'.$langs->trans('SetupModule').'</h2>';
	if (!empty($activatedModules)) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield" width="50%">'.$langs->trans("Parameter").'</td><td width="50%">'.$langs->trans("Value").'</td></tr>';

		foreach ($activatedModules as $key => $module) {
			if (!$key) continue;
			$setupnotempty++;

			print '<tr class="oddeven"><td>';

			// Title can eather be an array or a string
			$title = '';
			if (is_array($module['title'])) {
				foreach ($module['title'] as $value) {
					$title.= $langs->trans($value).' ';
				}
				// Remove last space
				$title = substr($title, 0, -1);
			} else {
				$title = $langs->trans($module['title']);
			}
			print $title;
			print '</td><td>'.(in_array($module['mainmenu'], $hidedEntries) ? '<i style="color: #19cd7a;" class="fas fa-check"></i>' : '<i style="color: red;" class="fas fa-times"></i>').'</td></tr>';
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		print '<br>'.$langs->trans("NoModuleActivated");
	}
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
