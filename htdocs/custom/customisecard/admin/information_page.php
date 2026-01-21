<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
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
 * \file    customisecard/admin/information_page.php
 * \ingroup customisecard
 * \brief   Information page of module Customisecard.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/customisecard/lib/customisecard.lib.php');

// Translations
$langs->loadLangs(array("errors","admin","customisecard@customisecard"));

// Load dolibarr informations
$dolibarrVersion = DOL_VERSION;
$dolibarrInitialVersion = $conf->global->MAIN_VERSION_LAST_INSTALL;

// Load browser informations
$browser = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
$browserName = $browser['browsername'];
$browserOs = $browser['browseros'];
$browserVersion = $browser['browserversion'];
$browserUserAgent = dol_escape_htmltag($_SERVER['HTTP_USER_AGENT']);
$browserScreen = $_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];

// Load os informations
$osName = PHP_OS;
$osVersion = version_os();

// Load webserver informations
$webserverVersion = $_SERVER["SERVER_SOFTWARE"];

// Load php informations
$phpVersion = version_php();

// Load database informations
$dbVersion = $db::LABEL.' '.$db->getVersion();
$dbPilote = $conf->db->type.($db->getDriverInfo() ? ' ('.$db->getDriverInfo().')':'');

// Load module informations
$moduleName = $langs->trans('ModuleCustomiseCardName');
$moduleVersion = getCustomisecardModuleVersion();


$informations = array(
	'module' => array($langs->trans('InformationBoxVersion') => $moduleVersion, $langs->trans('InformationBoxName') => $moduleName),
	'dolibarr' => array($langs->trans('InformationBoxVersion') => $dolibarrVersion, $langs->trans('InformationBoxInitialVersion') => $dolibarrInitialVersion),
	'php' => array($langs->trans('InformationBoxVersion') => $phpVersion),
	'db' => array($langs->trans('InformationBoxVersion') => $dbVersion, $langs->trans('InformationBoxPilote') => $dbPilote),
	'webserver' => array($langs->trans('InformationBoxVersion') => $webserverVersion),
	'browser' => array($langs->trans('InformationBoxVersion') => $browserVersion, $langs->trans('InformationBoxUserAgent') => $browserUserAgent, $langs->trans('InformationBoxName') => $browserName, $langs->trans('InformationBoxOs') => $browserOs, $langs->trans('InformationBoxScreen') => $browserScreen),
	'os' => array($langs->trans('InformationBoxVersion') => $osVersion, $langs->trans('InformationBoxName') => $osName),
);

/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

$page_name = "TecInformation";
$arrayofcss = array(dol_buildpath('customisecard/css/informationBox.css', 1));
$arrayofjs = array();
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans($page_name), '', 'object_customisecard@customisecard');

// Configuration header
$head = customisecardAdminPrepareHead();
dol_fiche_head($head, 'information', '', 0, 'customisecard@customisecard');

print '<div class="modinfo-box">';
foreach ($informations as $key => $value) {
	print '<div class="modinfo-box__container">';
	print '<div class="modinfo-box__title">'.$langs->trans('Info'.$key).'</div>';

	print '<div class="modinfo-box__content">';
	foreach ($value as $lineKey => $lineValue) {
		print '<p><b>'.$lineKey.'</b> : '.$lineValue.'</p>';
	}
	print '</div>';
	print '</div>';
}
print '</div>';

// Hidden div containing content that will be copied
print '<div id="modinfoBoxResume">';
print "------------\n";
foreach ($informations as $key => $value) {
	$sectionTitle = $langs->trans('Info'.$key);

	print $sectionTitle."\n";


	foreach ($value as $lineKey => $lineValue) {
		print $lineKey.' : '.$lineValue."\n";
	}
	print "------------\n";
}
print '</div>';

// Button to copy the content
print '<div class="center"><div class="tooltip">';
print '<span class="tooltiptext" id="myTooltip">'.$langs->trans('Copy').' !</span>';
print '<button class="button butAction" onclick="copyInformations()" onmouseout="outFunc()"><span class="tooltiptext" id="myTooltip">'.$langs->trans('Copy').' !</span><i class="fas fa-clipboard"></i>&nbsp;'.$langs->trans('CopyInformation').'</button>';
print '</div></div>';

// Script to copy the content
print '<script>
function copyInformations() {
  var copyText = document.getElementById("modinfoBoxResume");
  var toCopy = copyText.innerHTML;

  /* Copy the text */
  navigator.clipboard.writeText(toCopy).then(() => {
    console.log("Copied the text: " + toCopy);    
  });
  var tooltip = document.getElementById("myTooltip");
  tooltip.style.display = "block";
}

function outFunc() {
  var tooltip = document.getElementById("myTooltip");
    tooltip.style.display = "none";
}
</script>';
