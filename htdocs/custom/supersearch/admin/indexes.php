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
 * \file    supersearch/admin/indexes.php
 * \ingroup supersearch
 * \brief   supersearch indexes page.
 */

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
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
$index = GETPOST('index');
$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
$indexConfig = getIndexCustomization(); // Get all dolibarr customization from DB for all indexes
$dolUID = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

/*
 * Action
 */
if ($action == 'reset_custom_dolibarr') {
	$indexConfig = new stdClass();
	$defaultIndexConfig = file_get_contents(dol_buildpath('/supersearch/documents/customization_dolibarr.json'));
	$defaultIndexConfig = json_decode($defaultIndexConfig);
	$indexConfig->$index['url'] = $defaultIndexConfig->$index->url;
	$indexConfig->$index['color'] = $defaultIndexConfig->$index->color;
	$indexConfig->$index['picto'] = $defaultIndexConfig->$index->picto;
	$indexConfig->$index['position'] = $defaultIndexConfig->$index->position;
	$indexConfig->$index['disabled'] = 0;

	$ret = updateIndexCustomization($indexConfig);
	if ($ret < 0) {
		setEventMessages($langs->trans('SuperSearchErrorUpdateIndexCustomization'), '', 'errors');
	} else {
		header("Location: " . $_SERVER['PHP_SELF']);
		exit;
	}
} elseif ($action == 'index_attributes_reset') {
	resetAttributes($index);
	header("Location: " . $_SERVER['PHP_SELF']);
	exit;
} elseif ($action == 'update_index_customization') {
	setEventMessages($langs->trans('SuperSearchUpdateIndexCustomization'), '');
	header("Location: " . $_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */

$form = new Form($db);
$formother = new Formother($db);

$help_url = '';
$page_name = "Index";

$arrayofjs = array('/supersearch/js/index.js.php');
$arrayofcss = array('/supersearch/css/index.css');
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = supersearchAdminPrepareHead();
print dol_get_fiche_head($head, 'index', $langs->trans($page_name), -1, "supersearch@supersearch");

// Setup page goes here
print '<span class="opacitymedium">'.$langs->trans("SuperSearchSetupPage").'</span><br><br>';
print $langs->trans('InstanceUniqueID') . ' : ';

// #24
print '<span class="clpbrd42">' . $dolUID . '</span><br><br>';

if ($conf->entity == 1) {
	// Index Section
	print '<h1>' . $langs->trans('SuperSearchIndexCustomization') . '</h1>';
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<table id="tablelines" class="noborder indexcustomization" width="100%">';
	print '<thead>';
	print '<tr class="liste_titre">';
	print '<td align="left">' . $langs->trans('Index') . '</td>';
	print '<td align="left">' . $form->textwithpicto($langs->trans('URL'), $langs->trans('SuperSearchTooltipHelpURL')) . '</td>';
	print '<td align="left">' . $form->textwithpicto($langs->trans('Color'), $langs->trans('SuperSearchTooltipHelpColor')) . '</td>';
	print '<td align="left">' . $form->textwithpicto($langs->trans('Icon'), $langs->trans('SuperSearchTooltipHelpIcon')) . '<a class="fas fa-external-link-alt classfortooltip" title="Vers FontAwesome" href="https://fontawesome.com/v5/search?ic=free" target="_blank"></a></td>';
	print '<td align="center" colspan="2">' . $langs->trans('SuperSearchActions') . '</td>';
	print '<td colspan="3"></td>';
	print '</tr>';
	print '</thead>';
	foreach ($indexConfig as $index => $value) {
		print '<tbody data-index-group="' . $index . '">';
		print '<tr data-position="" data-index="' . $index . '" class="drag drop oddeven">';
		print '<td class="index-name">' . $index . '</td>';
		print '<td align="left"><input style="width: 100%" type="text" name="' . $index . '_url" value="' . $value['url'] . '"></td>';
		print '<td align="left">' . $formother->selectColor($value['color'], $index . '_color', $index . '_color', 1, '', 'minwidth75') . '</td>';
		print '<td><span title="' . $value['picto'] .'" style="color: ' . $value['color'] . '" class="' . $value['picto'] . ' classfortooltip"></span> <input style="width: 80%" type="text" name="' . $index . '_picto" value="' . $value['picto'] . '"></td>';
		print '<td align="center" class="linecolsave"><a data-index="' . $index . '" class="butAction"><span class="fas fa-save"></span>' . $langs->trans('Save') .'</a></td>';
		print '<td align="center" class="linecolreset"><a href="' . $_SERVER['PHP_SELF'] . '?action=reset_custom_dolibarr&index=' . $index . '&token=' . newToken() . '" class="butAction "><span class="fas fa-retweet"></span>' . $langs->trans('SuperSearchReset') . '</a></td>';
		print '<td align="center" class="linecoldropdown"><a data-index="' . $index . '" class="fas fa-chevron-down"></a></td>';
		print '<td align="center" class="linecoldisable" name="' . $index . '_disabled"><a data-index="' . $index . '" class="disabled-switch far ' . ($value['disabled'] ? 'fa-eye-slash' : 'fa-eye') . '"></a></td>';
		print '<td align="center" class="linecolmove tdlineupdown"></td>';
		print '</tr>';
		print '<tr data-index="' . $index . '" id="settings-' . $index . '" style="display: none;">';
		print '<td>' . $langs->trans('Parameters') . '</td>';
		print '<td align="center" colspan="3"><textarea class="settings" id="settings-' . $index . '" name="settings-' . $index . '"></textarea></td>';
		print '<td align="center" colspan="1"><a data-action="save" data-index="' . $index . '" class="butAction"><span class="fas fa-save"></span>' . $langs->trans('Save') . '</a></td>';
		print '<td align="center" colspan="1"><a data-action="reset" data-index="' . $index . '" class="butAction"><span class="fas fa-retweet"></span>' . $langs->trans('SuperSearchReset') . '</a></td>';
		print '<td align="center" colspan="2"></td>';
		print '</tr></tbody>';
	}
	print '</table>';
	print '</form>';
} else {
	print '<br><br><span>' . $langs->trans('SuperSearchConfigureInConfigEntity') . '</span>';
}

if (empty($conf->themequarantedeux->enabled)) {
	print '<script type="text/javascript">
				$(document).ready(function() {
					// Initialise the table
					$("#tablelines").tableDnD({
						onDragClass: "dragClass",
						dragHandle: "td.tdlineupdown"
					});

					$(".tdlineupdown").hover( function() { $(this).addClass("showDragHandle"); },
						function() { $(this).removeClass("showDragHandle"); }
					);
				});
			</script>';
} else {
	dol_include_once(dol_buildpath('/themequarantedeux/js/Sortable/Sortable.js', 2));
	print '<script type="text/javascript">
				$(document).ready(function() {
					let sortable = new Sortable($("#tablelines")[0], {
						ghostClass: "ghost",
						animation: 200,
						scroll: true,
						forceAutoScrollFallback: true,
						scrollSensitivity: 60,
						scrollSpeed: 20,
						bubbleScroll: true,
						selectedClass: "selected-sortable-item",
						avoidImplicitDeselect: false,
						handle: ".tdlineupdown, .index-name"
					});

					$(".tdlineupdown, .index-name").hover( function() { $(this).addClass("showDragHandle"); },
						function() { $(this).removeClass("showDragHandle"); }
					);
				});
			</script>';
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
