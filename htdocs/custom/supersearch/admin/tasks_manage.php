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
 * \file    supersearch/admin/task_manage.php
 * \ingroup supersearch
 * \brief   supersearch task manage page.
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

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/supersearch/lib/supersearch.lib.php');

global $langs, $user, $db, $hookmanager, $conf, $dolibarr_main_cookie_cryptkey, $dolibarr_main_instance_unique_id;

$langs->loadLangs(array("admin", "supersearch@supersearch"));
$hookmanager->initHooks(array('supersearchsetup', 'globalsetup'));
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php
$help_url = '';
$page_name = "SuperSearchSetup";
$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;
$arrayofjs = array('/supersearch/js/setup.js.php', '/supersearch/js/datatable.js');
$arrayofcss = array('/supersearch/css/datatable.css');

llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = supersearchAdminPrepareHead();
print dol_get_fiche_head($head, 'tasks', $langs->trans($page_name), -1, "supersearch@supersearch");

// Setup page content
echo '<span class="opacitymedium">' . $langs->trans("SuperSearchSetupPage") . '</span><br><br>';

// Title
print '<h1>' . $langs->trans('SuperSearchTasksManage') . '</h1>';

// Task list table
print '<table id="taskTable" class="noborder" style="width:100%">';
print '<thead>';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('SuperSearchTaskUid') . '</td>';
print '<td>' . $langs->trans('SuperSearchIndexUid') . '</td>';
print '<td>' . $langs->trans('Status') . '</td>';
print '<td>' . $langs->trans('Type') . '</td>';
print '<td>' . $langs->trans('SuperSearchFinishedAt') . '</td>';
print '<td>' . $langs->trans('Details') . '</td>';
print '</tr>';
print '</thead>';
	print '<tbody>';
	// Fetching tasks
	$curl = supersearchCurlInit('tasks?limit=10000', 1); // because by default limit is 20
if ($curl != -1) {
	$response = curl_exec($curl);
	$response = json_decode($response, true);
	supersearchCurlClose($curl);

	foreach ($response['results'] as $task) {
		print '<tr class="oddeven">';
		print '<td>' . $task['uid'] . '</td>';
		print '<td>' . str_replace($uniqueId . '_', '', $task['indexUid']) . '</td>';
		print '<td>' . $task['status'] . '</td>';
		print '<td>' . $task['type'] . '</td>';
		print '<td>' . dol_print_date(dol_stringtotime($task['finishedAt']), "%d/%m/%Y %H:%M:%S", "tzserver") . '</td>';
		print '<td>' . json_encode($task['details']) . '</td>';
		print '</tr>';
	}
}
print '</tbody>';
print '</table>';
print '</div>';

// Initialisation de DataTables
print '<script>
    $(document).ready(function() {

        $("#taskTable").DataTable({
        	language: {
        		url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json"
    		},
    		order: [0, \'desc\']
        })
    });

</script>';

llxFooter();
$db->close();
