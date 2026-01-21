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
 * \file    h2g2/information_page.php
 * \ingroup h2g2
 * \brief   Information page for all code 42 modules.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) { $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
}
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) { $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) { $res=@include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php")) { $res=@include "../../../main.inc.php";
}
if (! $res) { die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
use \h2g2\MigrationManager;
dol_include_once('h2g2/class/migrationmanager.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('h2g2'));

// Translations
$langs->loadLangs(array("errors","admin","h2g2@h2g2"));

// Load dolibarr informations
$dolibarrVersion = DOL_VERSION;
$dolibarrInitialVersion = $conf->global->MAIN_VERSION_LAST_INSTALL;
$dolibarrMainUrl = ${'dolibarr_main_url_root'};
$dolibarrMainUrlAlt = ${'dolibarr_main_url_root_alt'};
$dolibarrMainDocument = ${'dolibarr_main_document_root'};
$dolibarrMainDocumentAlt = ${'dolibarr_main_document_root_alt'};
$dolibarrMainData = ${'dolibarr_main_data_root'};
$dolibarrMainID = ${'dolibarr_main_instance_unique_id'};

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
$phparray = phpinfo_array();
$phpDisplayErrors = $phparray['Core']['display_errors']['local'];

// Load database informations
$dbVersion = $db::LABEL.' '.$db->getVersion();
$dbPilote = $conf->db->type.($db->getDriverInfo() ? ' ('.$db->getDriverInfo().')':'');

// Load module informations
$module = GETPOST('module', 'alpha'); // modContratPlus
$modulePath = GETPOST('modulePath', 'alpha'); // /contratplus/core/modules/modContratPlus.class.php (%2Fcontratplus%2Fcore%2Fmodules%2FmodContratPlus.class.php)

if ($module && $modulePath) {
	// Include and create instance of the module
	dol_include_once($modulePath);
	$moduleInstance = new $module($db);

	$moduleVersion = $moduleInstance->version;
	$moduleName = $moduleInstance->name;
	$galaxyModule = false;

	if (is_subclass_of($moduleInstance, 'TheGalaxy')) {
		$galaxyModule = true;
		$moduleInitVersion = MigrationManager::getInitialInstallationVersion($moduleInstance->rights_class);
		$moduleDbVersion = MigrationManager::getLastVersionInstalled($moduleInstance->rights_class);
	}
} else {
	$moduleVersion = 'Unknown';
	$moduleName = 'Unknown';
}
$moduleActivationDate = dol_print_date($moduleInstance->getLastActivationDate(), '%d/%m/%Y %H:%M');

$informations = array(
	'module' => array(
		$langs->trans('InformationBoxName') => $moduleName,
		$langs->trans('InformationBoxModuleVersion') => $moduleVersion,
		$langs->trans("InformationBoxLastActivation") => $moduleActivationDate
	),
	'dolibarr' => array(
		$langs->trans('InformationBoxVersion') => $dolibarrVersion,
		$langs->trans('InformationBoxInitialVersion') => $dolibarrInitialVersion,
		$langs->trans('InformationBoxMainUrl') => $dolibarrMainUrl,
		$langs->trans('InformationBoxMainUrlAlt') => $dolibarrMainUrlAlt,
		$langs->trans('InformationBoxMainDocument') => $dolibarrMainDocument,
		$langs->trans('InformationBoxMainDocumentAlt') => $dolibarrMainDocumentAlt,
		$langs->trans('InformationBoxMainData') => $dolibarrMainData,
		$langs->trans('InformationBoxMainId') => $dolibarrMainID,
	),
	'php' => array(
		$langs->trans('InformationBoxVersion') => $phpVersion,
		$langs->trans('InformationBoxDisplayError') => $phpDisplayErrors
	),
	'db' => array(
		$langs->trans('InformationBoxVersion') => $dbVersion,
		$langs->trans('InformationBoxPilote') => $dbPilote
	),
	'webserver' => array(
		$langs->trans('InformationBoxVersion') => $webserverVersion
	),
	'browser' => array(
		$langs->trans('InformationBoxVersion') => $browserVersion,
		$langs->trans('InformationBoxUserAgent') => $browserUserAgent,
		$langs->trans('InformationBoxName') => $browserName,
		$langs->trans('InformationBoxOs') => $browserOs,
		$langs->trans('InformationBoxScreen') => $browserScreen
	),
	'os' => array(
		$langs->trans('InformationBoxVersion') => $osVersion,
		$langs->trans('InformationBoxName') => $osName
	),
);

if ($galaxyModule) {
	$informations['module'][$langs->trans('InformationBoxInitialVersion')] = $moduleInitVersion;
	$informations['module'][$langs->trans('InformationBoxInstalledVersion')] = $moduleDbVersion;
}

/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

$page_name = "TecInformation";
$arrayofcss = array('h2g2/css/informationBox.css');
$arrayofjs = array();
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans($page_name), '', 'information@h2g2');

// Dynamic header configuration using generateInformationTabHeader hook for context h2g2
$reshook = $hookmanager->executeHooks('generateInformationTabHeader', array('module' => $module));
if ($hookmanager->resArray) {
	// Display the dol_fiche_head
	$head = $hookmanager->resArray['head'];
	$activeTab = $hookmanager->resArray['active_tab'];
	$langsFile = $hookmanager->resArray['langs'];
	$icon = $hookmanager->resArray['header_icon'];
	dol_fiche_head($head, $activeTab, $icon, 0, $langsFile);
}

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
print '<span class="tooltiptext" id="myTooltip">'.$langs->trans('Copied').' !</span>';
print '<button class="button" onclick="copyInformations()" onmouseout="outFunc()"><i class="fas fa-clipboard"></i>&nbsp;'.$langs->trans('CopyInformation').'</button>';
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
