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
 *        \file       htdocs/sirene/admin/about.php
 *        \ingroup    sirene
 *        \brief      Page about of sirene module
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
dol_include_once('/sirene/core/modules/modSirene.class.php');

$langs->load("admin");
$langs->load("sirene@sirene");
$langs->load("opendsi@sirene");

if (!$user->admin) accessforbidden();
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;


/**
 * View
 */

llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("SireneSetup"), $linkback, 'title_setup');

$head = sirene_admin_prepare_head();

if ($isV14p) {
    print dol_get_fiche_head($head, 'about', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
} else {
    dol_fiche_head($head, 'about', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
}

$modClass = new modSirene($db);
$constantSireneLastVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';
$constantSireneVersion = getSireneDolGlobalString('MODULE_SIRENE_VERSION', 'NC');

$supportvalue = "/*****"."<br>";
$supportvalue.= " * Module : Sirene"."<br>";
$supportvalue.= " * Module version : ".$constantSireneLastVersion."<br>";
$supportvalue.= " * Module version installation initiale : ".$constantSireneVersion."<br>";
$supportvalue.= " * Dolibarr version : ".DOL_VERSION."<br>";
$supportvalue.= " * Dolibarr version installation initiale : ".getSireneDolGlobalString('MAIN_VERSION_LAST_INSTALL')."<br>";
$supportvalue.= " * Version PHP : ".PHP_VERSION."<br>";
$supportvalue.= " *****/"."<br><br>";
$supportvalue.= "Description de votre problème :"."<br>";

// print '<div class="div-table-responsive-no-min">';
print '<table class="centpercent">';

//print '<tr class="liste_titre"><td colspan="2">' . $langs->trans("Authors") . '</td>';
//print '</tr>'."\n";

// Easya Solutions
print '<tr>';
print '<form id="ticket" method="POST" target="_blank" action="https://support.easya.solutions/create_ticket.php">';
print '<input name=message type="hidden" value="'.$supportvalue.'" />';
print '<input name=email type="hidden" value="'.$user->email.'" />';
print '<td class="titlefield center"><img alt="Easya Solutions" src="../img/opendsi_dolibarr_preferred_partner.png" /></td>'."\n";
print '<td class="left"><p>'.$langs->trans("OpenDsiAboutDesc1").' <button type="submit" >'.$langs->trans("OpenDsiAboutDesc2").'</button> '.$langs->trans("OpenDsiAboutDesc3").'</p></td>'."\n";
print '</tr>'."\n";

print '</table>'."\n";

if ($isV14p) {
	print dol_get_fiche_end();
} else {
	dol_fiche_end();
}

llxFooter();
$db->close();
