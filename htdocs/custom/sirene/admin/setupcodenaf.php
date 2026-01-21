<?php
/* Copyright (C) 2025      Open-DSI             <support@open-dsi.fr>
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
 *        \file       htdocs/sirene/admin/setupcodenaf.php
 *        \ingroup    sirene
 *        \brief      Page to setup sirene module (COde Naf)
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
dol_include_once('/sirene/class/codenaf.class.php');

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

if ($action == 'set_codenaf_options') {
	$reload_codenaf_csv = GETPOST('reload_codenaf_csv');
	if (empty($reload_codenaf_csv)) {
		$value = GETPOST('CODENAF_CSV_SEPARATOR_TO_USE', "alpha");
		$res = dolibarr_set_const($db, 'CODENAF_CSV_SEPARATOR_TO_USE', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
		$value = GETPOST('CODENAF_CSV_ENCLOSURE_TO_USE', "alpha");
		$res = dolibarr_set_const($db, 'CODENAF_CSV_ENCLOSURE_TO_USE', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
		$value = GETPOST('CODENAF_CSV_ESCAPE_TO_USE', "alpha");
		$res = dolibarr_set_const($db, 'CODENAF_CSV_ESCAPE_TO_USE', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
	} else {
		$codenaf = new CodeNaf($this->db);
		$result = $codenaf->importCsv(dol_buildpath('/sirene/install/data/codenaf.csv', 0));
		if ($result < 0) {
			setEventMessages($codenaf->error, $codenaf->errors, 'errors');
		} else {
			setEventMessage($langs->trans('SireneCodeNafNbInsert', $result));
		}
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
	print dol_get_fiche_head($head, 'codenaf', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
} else {
	dol_fiche_head($head, 'codenaf', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
}

print '<br>';

/********************************************************
 *  Code Naf options
 ********************************************************/
print load_fiche_titre($langs->trans("SireneCodeNafOptions"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '" />';
print '<input type="hidden" id="sirene_code_naf_action" name="action" value="set_codenaf_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// CODENAF_CSV_SEPARATOR_TO_USE
print '<tr class="oddeven">';
print '<td>' . $langs->trans("SireneCodeNafCSVSeparatorToUseName") . '</td>';
print '<td>' . $langs->trans("SireneCodeNafCSVSeparatorToUseDesc") . '</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_SEPARATOR_TO_USE" value="' . htmlspecialchars(getSireneDolGlobalString('CODENAF_CSV_SEPARATOR_TO_USE', ";")) . '">';
print '</td></tr>';

// CODENAF_CSV_ENCLOSURE_TO_USE
print '<tr class="oddeven">';
print '<td>' . $langs->trans("SireneCodeNafCSVEnclosureToUseName") . '</td>';
print '<td>' . $langs->trans("SireneCodeNafCSVEnclosureToUseDesc") . '</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_ENCLOSURE_TO_USE" value="' . htmlspecialchars(getSireneDolGlobalString('CODENAF_CSV_ENCLOSURE_TO_USE', '"')) . '">';
print '</td></tr>';

// CODENAF_CSV_ESCAPE_TO_USE
print '<tr class="oddeven">';
print '<td>' . $langs->trans("SireneCodeNafCSVEscapeToUseName") . '</td>';
print '<td>' . $langs->trans("SireneCodeNafCSVEscapeToUseDesc") . '</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_ESCAPE_TO_USE" value="' . htmlspecialchars(getSireneDolGlobalString('CODENAF_CSV_ESCAPE_TO_USE', '\\')) . '">';
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print ' &nbsp; ';
print '<input type="submit" class="button" name="reload_codenaf_csv" value="' . $langs->trans("SireneCodeNafReloadCSV") . '">';
print '</div>';

print '</form>' . "\n";

if ($isV14p) {
	print dol_get_fiche_end();
} else {
	dol_fiche_end();
}

llxFooter();
$db->close();
