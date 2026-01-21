<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/advancedictionaries/admin/about.php
 *		\ingroup    advancedictionaries
 *		\brief      Page about of advancedictionaries module
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
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/advancedictionaries/lib/advancedictionaries.lib.php');
dol_include_once('/advancedictionaries/core/modules/modAdvanceDictionaries.class.php');

$langs->load("admin");
$langs->load("advancedictionaries@advancedictionaries");
$langs->load("opendsi@advancedictionaries");

if (!$user->admin) accessforbidden();


/**
 * View
 */

$wikihelp='EN:AdvanceDictionaries_En|FR:AdvanceDictionaries_Fr|ES:AdvanceDictionaries_Es';
llxHeader('', $langs->trans("AdvanceDictionariesSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AdvanceDictionariesSetup"),$linkback,'title_setup');
print "<br>\n";


$head=advancedictionaries_prepare_head();

print dol_get_fiche_head($head, 'about', $langs->trans("Module163017Name"), 0, 'opendsi@advancedictionaries');

$modClass = new modAdvanceDictionaries($db);
$constantLastVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';
$constantSireneVersion = !empty($conf->global->MODULE_SIRENE_VERSION) ? $conf->global->MODULE_SIRENE_VERSION : 'NC';

$supportvalue = "/*****"."<br>";
$supportvalue.= " * Module : ".$langs->trans("Module163017Name")."<br>";
$supportvalue.= " * Module version : ".$constantLastVersion."<br>";
$supportvalue.= " * Dolibarr version : ".DOL_VERSION."<br>";
$supportvalue.= " * Dolibarr version installation initiale : ".$conf->global->MAIN_VERSION_LAST_INSTALL."<br>";
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

print dol_get_fiche_end();


llxFooter();

$db->close();
