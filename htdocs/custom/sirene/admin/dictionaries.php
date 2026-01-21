<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *        \file       htdocs/sirene/admin/setup.php
 *        \ingroup    sirene
 *        \brief      Page to setup sirene module
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/sirene/lib/sirene.lib.php');
dol_include_once('/sirene/lib/opendsi_common.lib.php');

$langs->loadLangs(array('admin', 'sirene@sirene', 'opendsi@sirene'));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$prevrowid = GETPOST('prevrowid', 'int');
$module = GETPOST('module', 'alpha');
$name = GETPOST('name', 'alpha');

$canRead = $user->rights->advancedictionaries->read;
$canCreate = $user->rights->advancedictionaries->create;
$canUpdate = $user->rights->advancedictionaries->create;
$canDelete = $user->rights->advancedictionaries->delete;
$canDisable = $user->rights->advancedictionaries->disable;
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

require dol_buildpath('/advancedictionaries/core/actions_dictionaries.inc.php');

/**
 * View
 */

$wikihelp = '';
llxHeader('', $langs->trans("SireneSetup"), $wikihelp);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('SireneSetup'), $linkback, 'title_setup');

$head = sirene_admin_prepare_head();
if ($isV14p) {
	print dol_get_fiche_head($head, 'dictionaries', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
} else {
	dol_fiche_head($head, 'dictionaries', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
}

$moduleFilter = ''; // array or string to set the dictionaries of witch modules to show in dictionaries list
$familyFilter = 'sirene'; // array or string to set the dictionaries of witch family to show in dictionaries list

require dol_buildpath('/advancedictionaries/core/tpl/dictionaries.tpl.php');

if ($isV14p) {
	print dol_get_fiche_end();
} else {
	dol_fiche_end();
}

llxFooter();
$db->close();
