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
dol_include_once('/custom/sirene/class/sirene.class.php');

$langs->load("admin");
$langs->load("sirene@sirene");
$langs->load("opendsi@sirene");

if (!$user->admin) accessforbidden();
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

$action = GETPOST('action', 'aZ09');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set_sirene_api_options') {
	$value = GETPOST('SIRENE_API_RNA_URI', "alpha");
	if (empty($value)) $value = 'https://siva-integ1.cegedim.cloud/apim/api-asso/api/structure/';
	$res = dolibarr_set_const($db, 'SIRENE_API_RNA_URI', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);
	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
}

if ($action != '') {
	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"));
		//        Header("Location: " . $_SERVER["PHP_SELF"]);
		//        exit;
	} else {
		setEventMessages('', $errors, 'errors');
	}
}

/*
 *  View
 */

$form = new Form($db);

llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("SireneSetup"), $linkback, 'title_setup');

$head = sirene_admin_prepare_head();
if ($isV14p) {
	print dol_get_fiche_head($head, 'rna', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
} else {
	dol_fiche_head($head, 'rna', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
}

print '<br>';

/********************************************************
 *  Sirene API options
 ********************************************************/
print '<div id="sirene_api_options"></div>';
print load_fiche_titre($langs->trans("SireneApiOptions"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '#sirene_api_options">';
print '<input type="hidden" name="token" value="' . newToken() . '" />';
print '<input type="hidden" name="action" value="set_sirene_api_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE_API_RNA_URI
$sireneAPIUrl = getSireneDolGlobalString('SIRENE_API_RNA_URI', 'https://siva-integ1.cegedim.cloud/apim/api-asso/api/structure/');
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiRnaUrlName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneApiRnaUrlDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_API_RNA_URI" class="centpercent" value="' . dol_escape_htmltag($sireneAPIUrl) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_API_RNA_VERIFY_SSL
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiRnaVerifySslName") . '</td>';
print '<td>' . $langs->trans("SireneApiRnaVerifySslDesc") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_API_RNA_VERIFY_SSL', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_API_RNA_VERIFY_SSL')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_API_RNA_VERIFY_SSL">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_API_RNA_VERIFY_SSL">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

if ($isV14p) {
	print dol_get_fiche_end();
} else {
	dol_fiche_end();
}

llxFooter();
$db->close();
