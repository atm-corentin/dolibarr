<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Thomas BACHELEY <thomas@code42.fr>
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
 * \file    supersearch/admin/data_migrations.php
 * \ingroup supersearch
 * \brief   supersearch data migration page.
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

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once('supersearch/lib/supersearch.lib.php');

global $langs, $user, $db, $hookmanager, $conf, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;
$langs->loadLangs(array("admin", "supersearch@supersearch"));
$hookmanager->initHooks(array('supersearchsetup', 'globalsetup'));
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$table = array(
	'Product' => 'product',
	'Societe' => 'societe',
	'Article' => 'lareponse_article',
	'Device' => 'c_gestionparc_device',
	'Application' => 'c_gestionparc_application',
	'Fichinter' => 'fichinter',
	'Propal' => 'propal',
	'Contact' => 'socpeople',
	'Project' => 'projet',
);

/*
 * Action
 */

if (!empty($action)) {
	$index = GETPOST('index');
	if ($action == 'import') {
		$ret = importDataToMeilisearch($index, $table[str_replace($uniqueId . '_', '', $index)]);
		if ($ret < 1) setEventMessage('Meilisearch: ' . $langs->trans('SuperSearchError') . ' Code ' . $ret, 'errors');
	} elseif ($action == 'delete') {
		$ret = deleteDataToMeilisearch($index, $table[str_replace($uniqueId . '_', '', $index)]);
		if ($ret < 1) setEventMessage('Meilisearch: ' . $langs->trans('SuperSearchError') . ' Code ' . $ret, 'errors');
	} elseif ($action == 'empty') {
		$ret = emptyIndex($index);
		if ($ret < 1) setEventMessage('Meilisearch: ' . $langs->trans('SuperSearchError') . ' Code ' . $ret, 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "SuperSearchDataMigrations";

$arrayofjs = array();
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback);

$head = supersearchAdminPrepareHead();
print dol_get_fiche_head($head, 'data_migrations', $langs->trans($page_name), -1, "supersearch@supersearch");

print '<table class="noborder" width="100%"><tbody>';
print '<tr class="liste_titre">';
print '<td><b>' . $langs->trans('Index') . '</b></td>';
print '<td align="center"><b>' . $langs->trans('SupersearchDataInDolibarrEntity') . ' / ' . $langs->trans('SupersearchDataInDolibarrTotal') . '</b></td>';
print '<td align="center"><b>' . $langs->trans('SupersearchDataInMeilisearch') . '</b></td>';
print '<td><b>' . $langs->trans('Action') . '</b></td>';
print '</tr>';

$curl = supersearchCurlInit('/indexes', 1);
$response = curl_exec($curl);
$response = json_decode($response, true);
$indexConfig = getIndex();

foreach ($response['results'] as $index) {
	$indexName = $index['uid'];
	$idx = str_replace($uniqueId . '_', '', $indexName);
	if (!in_array($idx, $indexConfig)) continue;
	$curl = supersearchCurlInit('/indexes/' . $indexName . '/documents?limit=1', 1); // we only want "total" in result
	$res = curl_exec($curl);
	$res = json_decode($res, true);
	supersearchCurlClose($curl);

	$resql = $db->query('SELECT COUNT(*) as count FROM ' . MAIN_DB_PREFIX . $table[str_replace($uniqueId . '_', '', $indexName)]);
	$count = 0;
	if ($resql) $count = $db->fetch_object($resql)->count;

	$resql = $db->query('SELECT COUNT(*) as count FROM ' . MAIN_DB_PREFIX . $table[str_replace($uniqueId . '_', '', $indexName)] . ' WHERE entity = ' . $conf->entity);
	$countEntity = 0;
	if ($resql) $countEntity = $db->fetch_object($resql)->count;

	$res['total'] = intval($res['total']);
	$count = intval($count);
	$countEntity = intval($countEntity);

	print '<tr class="oddeven">';
	print '<td>' . str_replace($uniqueId . '_', '', $indexName) . '</td>';
	print '<td align="center">' . $countEntity . ' / ' . $count . '</td>';
	print '<td align="center">' . $res['total'] . '</td>';
	print '<td>';
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="index" value="' . $indexName . '">';
	$curl = supersearchCurlInit('/tasks?indexUids=' . $indexName . '&statuses=processing', 1);
	$response = curl_exec($curl);
	$response = json_decode($response, true);
	supersearchCurlClose($curl);

	if ($response['total'] == 0) {
		if ($res['total'] == $count) {
			print '<span style="color: green" class="fas fa-check"></span> ';
			if ($conf->entity == 1) print '<button name="action" value="empty" type="submit" style="--btn-color: var(--btn-color-warning)" class="butAction">' . $langs->trans('SuperSearchEmptyIndex') . '</button> ';
		} elseif ($conf->entity == 1) {
			if ($res['total'] > $count) print '<span style="color: var(--btn-color-warning)" class="fas fa-exclamation-circle"></span> <button name="action" value="delete" type="submit" class="butActionDelete">' . $langs->trans('SuperSearchDeleteInMeilisearch') . '</button> ';
			else print '<span style="color: var(--btn-color-warning)" class="fas fa-exclamation-circle"></span> <button name="action" value="import" type="submit" style="--btn-color: var(--btn-color-warning)" class="butAction">' . $langs->trans('SuperSearchImportInMeilisearch') . '</button> ';
		} else {
			print '<span style="color: var(--btn-color-warning)" class="fas fa-exclamation-circle"></span> <span class="opacitymedium">' . $langs->trans('SuperSearchConfigureInConfigEntity') . '</span>';
		}
	} else {
		print '<span class="badge badge-status1 badge-status" title="' . $langs->trans('SuperSearchTaskInProgress') . '">' . $langs->trans('SuperSearchTaskInProgress') . '</span>';
	}
	print '</form>';
	print '</td>';
	print '</tr>';
}
print '</tbody>';
print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
