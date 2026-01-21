<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 SuperAdmin
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
 * \file    supersearch/admin/setup.php
 * \ingroup supersearch
 * \brief   supersearch setup page.
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

global $langs, $user, $db, $hookmanager, $conf, $dolibarr_main_cookie_cryptkey, $dolibarr_main_instance_unique_id;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/supersearch/lib/supersearch.lib.php');

// Translations
$langs->loadLangs(array("admin", "supersearch@supersearch"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supersearchsetup', 'globalsetup'));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php
$masterKeyValue = GETPOST('setmasterkey', 'alpha');
$URLMeilisearchValue = GETPOST('seturlmeilisearch', 'alpha');
$adminKeyValue = GETPOST('setmadminkey', 'alpha');
$CRUDKeyValue = GETPOST('setcrudkey', 'alpha');
$APIKeyValue = GETPOST('setapikey', 'alpha');
$hitsPerPageValue = GETPOST('sethitsperpage', 'int');
$charsBeforeSearchValue = GETPOST('setcharsbeforesearch', 'int');
$reset = (GETPOST('reset', 'int') ? GETPOST('reset', 'int') : 0);
if (!empty($masterKeyValue)) dolibarr_set_const($db, 'SUPERSEARCH_MASTERKEY', $masterKeyValue, 'string');
if (!empty($URLMeilisearchValue)) {
	if (preg_match('/^https?:\/\//', $URLMeilisearchValue)) dolibarr_set_const($db, 'SUPERSEARCH_URL_MEILISEARCH', $URLMeilisearchValue, 'string');
	else setEventMessage($langs->trans('SuperSearchUrlError', $URLMeilisearchValue), 'errors');
}

if (!empty($adminKeyValue)) dolibarr_set_const($db, 'SUPERSEARCH_ADMINKEY', $adminKeyValue, 'string');
if (!empty($CRUDKeyValue)) dolibarr_set_const($db, 'SUPERSEARCH_CRUDKEY', $CRUDKeyValue, 'string');
if (!empty($APIKeyValue)) dolibarr_set_const($db, 'SUPERSEARCH_APIKEY', $APIKeyValue, 'string');
if (!empty($hitsPerPageValue)) dolibarr_set_const($db, 'SUPERSEARCH_HITS_PER_PAGE', $hitsPerPageValue, 'int');
if (!empty($charsBeforeSearchValue)) dolibarr_set_const($db, 'SUPERSEARCH_CHARS_BEFORE_SEARCH', $charsBeforeSearchValue, 'int');
$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
$hitsPerPage = dolibarr_get_const($db, 'SUPERSEARCH_HITS_PER_PAGE');
$charsBeforeSearch = dolibarr_get_const($db, 'SUPERSEARCH_CHARS_BEFORE_SEARCH');

/*
 * Action
 */

if (!empty($urlMeilisearch) && empty($adminKey) && !empty($masterKeyValue)) {
	$ret = initKeys(); // setup Admin/CRUD/Search key
	if ($ret < 0) {
		setEventMessage($langs->trans("SuperSearchErrorInitKey"), 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($reset) {
	disableSuperSearch();
	header("Location: " . DOL_URL_ROOT . "/admin/modules.php");
	exit;
}

if ($action == 'init_settings') {
	resetAttributes();
}

/*
 * View
 */

$form = new Form($db);
$help_url = '';
$page_name = "SuperSearchSetup";

//header
$arrayofjs = array('/supersearch/js/setup.js.php');
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs);

// Subheader
$linkback = '<a href="'.($backtopage ? : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = supersearchAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "supersearch@supersearch");

$valuetoshow = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

print '<span class="opacitymedium">'.$langs->trans("SuperSearchSetupPage").'</span><br><br>';
print $langs->trans('InstanceUniqueID') . ' : ';

// #24
print '<span class="clpbrd42">' . $valuetoshow . '</span>';

/*
 * View
 */

if ($conf->entity == 1) {
	if ((empty($urlMeilisearch) || empty($adminKey)) && empty($masterKeyValue)) {
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		if (empty($conf->global->OLD_SUPERSEARCH_URL_MEILISEARCH)) print '<input type="hidden" name="action" value="init_settings">';
		print '<input type="hidden" name="action" value="initsettings">';
		if (empty($adminKey)) {
			print '<p style="color: darkred; font-weight: bold">' . $langs->trans('SuperSearchMasterKeyNeedToBeSet') . '</p>';
			print '<span style="width: 100%">';
			print $langs->trans("SuperSearchMasterKey") . ' : ';
			print '<input style="width: 30%" type="text" name="setmasterkey" value=""> ';
			print '</span>';
		}
		if (empty($urlMeilisearch)) {
			print '<p style="color: darkred; font-weight: bold">' . $langs->trans('SuperSearchURLNeedToBeSet') . '</p>';
			print '<span style="width: 100%">';
			print $langs->trans("SuperSearchURLMeilisearch") . ' : ';
			print '<input style="width: 30%" type="text" name="seturlmeilisearch" value=""> ';
			print '</span>';
		}
		print '<br><button type="submit" class="butAction">' . $langs->trans('Save') . '</button>';
		if (!empty($conf->global->OLD_SUPERSEARCH_URL_MEILISEARCH)) print '<button id="restore-key" style="margin: 0 5px !important;" class="butAction">' . $langs->trans('SuperSearchRestoreKey') . '</button>';
		print '</form>';
	} elseif (!empty($urlMeilisearch) || !empty($adminKey)) {
		initIndexes(); // setup indexes for this instance of dolibarr (All entity is targeted) // Only in meilisearch instance
		$parameters = array(
			'SUPERSEARCH_URL_MEILISEARCH' => array('action' => 'seturlmeilisearch', 'label' => 'SuperSearchURLMeilisearch'),
			'SUPERSEARCH_ADMINKEY' => array('action' => 'setmadminkey', 'label' => 'SuperSearchAdminKey'),
			'SUPERSEARCH_CRUDKEY' => array('action' => 'setcrudkey', 'label' => 'SuperSearchCRUDKey'),
			'SUPERSEARCH_APIKEY' => array('action' => 'setapikey', 'label' => 'SuperSearchAPIKey')
		);

		// Server Section
		print '<h1>' . $langs->trans('SuperSearchServerTitle') . '</h1>';
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<table class="noborder" width="100%"><tbody>';
		print '<tr class="liste_titre">';
		print '<td><h4 align="left" width="30%">'.$langs->trans('Parameter').'</h4></td>';
		print '<td align="left" width="70%"><h4>'.$langs->trans('Value').'</h4></td>';
		print '</tr>';

		foreach ($parameters as $key => $value) {
			$const = dolibarr_get_const($db, $key);
			print '<tr class="oddeven">';
			print '<td>' . $langs->trans($value['label']) . '</td>';
			print '<td><input style="width: 100%" type="text" name="' . $value['action'] . '" value="' . $const . '"></td>';
			print '</tr>';
		}

		print '<tr class="pair"><td colspan="3" align="center">';
		print '<button type="submit" style="--btn-color: var(--btn-color-success);" class="butAction">' . $langs->trans('Save') . '</button>';
		print '<button id="api-test-connection-button" style="margin: 0 5px !important; --btn-color: var(--btn-color-success);" class="butAction">' . $langs->trans('SuperSearchTestConnection') . '</button>';
		print '<button id="reset-const-button" style="margin: 0 5px !important; --btn-color: var(--btn-color-danger);" class="butAction">' . $langs->trans('SuperSearchRemoveValue') . '</button>';
		print '<span id="api-response"></span>';
		print '</td></tr>';
		print '</tbody>';
		print '</table>';
		print '</form>';

		// Popup Section
		print '<h1>' . $langs->trans('SuperSearchPopupSettingsTitle') . '</h1>';
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<table class="noborder" width="100%"><tbody>';
		print '<tr class="liste_titre">';
		print '<td><h4 align="left" width="30%">' . $langs->trans('Parameter') . '</h4></td>';
		print '<td align="left" colspan="2" width="70%"><h4>' . $langs->trans('Value') . '</h4></td>';
		print '<td></td>';
		print '</tr>';
		print '<tr>';
		print '<td>' . $langs->trans('SuperSearchHitPerPage') . '</td>';
		print '<td><input class="width75" name="sethitsperpage" type="number" pattern="^[0-9]*$" value="' . (!empty($hitsPerPage) ? $hitsPerPage : 6) . '" min="1"></td>';
		print '<td><button type="submit" style="--btn-color: var(--btn-color-success);" class="butAction">' . $langs->trans('Save') . '</button></td>';
		print '</tr>';
		print '<tr>';
		print '<td>' . $langs->trans('SuperSearchCharsBeforeSearch') . '</td>';
		print '<td><input class="width75" name="setcharsbeforesearch" type="number" pattern="^[0-9]*$" value="' . (!empty($charsBeforeSearch) ? $charsBeforeSearch : 3) . '" min="1"></td>';
		print '<td><button type="submit" style="--btn-color: var(--btn-color-success);" class="butAction">' . $langs->trans('Save') . '</button></td>';
		print '</tr>';
		print '</tbody></table>';
		print '</form>';
	}
} else {
	print '<br><br><span>' . $langs->trans('SuperSearchConfigureInConfigEntity') . '</span>';
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
