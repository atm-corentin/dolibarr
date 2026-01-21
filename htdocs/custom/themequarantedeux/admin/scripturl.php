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
 * \file    themequarantedeux/admin/scripturl.php
 * \ingroup themequarantedeux
 * \brief   About page of module themequarantedeux Script inject.
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

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/themequarantedeux.lib.php';

global $conf, $db;

// Translations
$langs->loadLangs(array("errors", "admin", "scriptsinject@scriptsinject","themequarantedeux@themequarantedeux"));
CONST SCRIPTS_INJECT_HEADER = array('label' => 'En tête', 'key' =>'HEADER');
CONST SCRIPTS_INJECT_FOOTER = array('label' => 'Bas de page', 'key' =>'FOOTER');

/*
 * Actions
 */

$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$form = new Form($db);

$page_name = "ScriptsInjectUrl";
$arraycss = array('themequarantedeux/css/scriptinject_style.css');
$arrayjs = array('themequarantedeux/js/scriptsinject.js.php');
llxHeader('', 'ScriptsInject', '', '', 0, 0, $arrayjs, $arraycss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_scriptsinject@themequarantedeux');

// Configuration header
$head = themequarantedeuxAdminPrepareHead();
print dol_get_fiche_head($head, 'scripturl', '', 0, 'scriptsinject@themequarantedeux');

$newBtnUrl = '';
$newBtnUrl = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $newBtnUrl, '');

print load_fiche_titre('', $newBtnUrl, '');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addurl">';
print '<input type="hidden" name="param" value="SCRIPTSINJECT_URL_PREFIX">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
print '<table style="width: 100%" id="scriptsinjecturls" class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td style="width: 50%" class="titlefield">'.$langs->trans("Parameter").'</td>';
print '<td style="width: 10%"class="titlefield">'.$langs->trans("Type").'</td>';
print '<td>'.$langs->trans("DateCreat").'</td>';
print '<td width="10%">'.$langs->trans("Actionstodo").'</td>';
print '<td width="10%">'.$langs->trans("Active").'</td>';
print '</tr>';
print '</tr>';

$sql = "SELECT rowid, url, type, date_creat, active FROM ".MAIN_DB_PREFIX."scriptsinject WHERE entity IN (".$conf->entity.", 0)";
$resql = $db->query($sql);

while ($obj = $db->fetch_object($resql)) {
	print '<tr>';
	print '<td>'.$obj->url.'</td>';
	if ($obj->type == null || $obj->type == SCRIPTS_INJECT_HEADER['key']) {
		print '<td>' .SCRIPTS_INJECT_HEADER['label'] . '</td>';
	} else {
		print '<td>'.SCRIPTS_INJECT_FOOTER['label'].'</td>';
	}
	print '<td>'.$obj->date_creat.'</td>';
	print '<td><span data-url="'.$obj->url.'" data-id="'.$obj->rowid.'" data-type="'.$obj->type.'" class="fas fa-edit editurl"></span><span data-key="'.$obj->rowid.'" class="fas fa-trash deleteurl"></span></td>';

	if ($obj->active == 1) {
		print '<td class="status" data-statusurl="'.$obj->rowid.'">'.img_picto($langs->trans("Activated"), 'switch_on').'</td>';
	} else {
		print '<td class="status" data-statusurl="'.$obj->rowid.'">'.img_picto($langs->trans("Disabled"), "switch_off").'</td>';
	}
	print '</tr>';
}

print '</table>';
print '</form>';


// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
