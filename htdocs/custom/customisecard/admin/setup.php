<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 SuperAdmin <root@roor.fr>
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
 * \file    customisecard/admin/setup.php
 * \ingroup customisecard
 * \brief   CustomiseCard setup page.
 */

// Load Dolibarr environment
$res = 0;
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
dol_include_once('/customisecard/lib/customisecard.lib.php');

// Translations
$langs->loadLangs(array("admin", "customisecard@customisecard"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

$arrayofparameters = array(
	'CUSTOMISECARD_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1),
	'CUSTOMISECARD_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1)
);

$error = 0;
$setupnotempty = 0;


/*
 * Actions
 */
include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

// Unable custom multientity only possible from main entity
if ($action == 'unablemultientity' && $conf->entity == 1) {
	dolibarr_set_const($db, 'CUSTOMISECARD_MULTIENTITY', 1, 'int', 1, $langs->trans('CustomcardMultientity'), 1);
	exit(header('Location: '.$_SERVER['PHP_SELF']));
}

// Disable custom multientity only possible from main entity
if ($action == 'disablemultientity' && $conf->entity == 1) {
	dolibarr_set_const($db, 'CUSTOMISECARD_MULTIENTITY', 0, 'int', 1, $langs->trans('CustomcardMultientity'), 1);
	exit(header('Location: '.$_SERVER['PHP_SELF']));
}

/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "CustomiseCardSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_customisecard@customisecard');

// Configuration header
$head = customisecardAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "customisecard@customisecard");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("CustomiseCardSetupPage").'</span><br><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

// Setup custom multi entity
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans('CustomMultientity'), $langs->trans('TooltipCustomMultientity'));

// Print button if we are on main entity
if ($conf->entity == 1) {
	print '</td>';
	if ($conf->global->CUSTOMISECARD_MULTIENTITY == 1)
		print '<td><a href="setup.php?action=disablemultientity"><span class="fas fa-toggle-on font-status4" style="" title="' . $langs->trans('Activate') . '"></span></a></td>';
	else print '<td><a href="setup.php?action=unablemultientity"><span class="fas fa-toggle-off font-status4" style="" title="' . $langs->trans('Disable') . '"></span></a></td>';
} else {
	if (dolibarr_get_const($db, 'CUSTOMISECARD_MULTIENTITY', 1) == 1)
		print '<td><span class="fas fa-toggle-on font-status4" style="" title="' . $langs->trans('Activate') . '"></span></td>';
	else print '<td><span class="fas fa-toggle-off font-status4" style="" title="' . $langs->trans('Disable') . '"></span></td>';
}
print '</tr>';

print '</table>';

// Page end
dol_fiche_end();

llxFooter();
$db->close();
