<?php
/* Copyright (C) 2022 Ayoub Bayed  <ayoub@code42.fr>
 *
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
 * \file    h2g2/contactus_page.php
 * \ingroup h2g2
 * \brief   contactus_page for specific 42 modules.
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('h2g2'));

// Translations
$langs->loadLangs(array("errors","admin","h2g2@h2g2"));

// Load module informations
$module = GETPOST('module', 'alpha'); // modModuleName
$modulePath = GETPOST('modulePath', 'alpha'); // /contratplus/core/modules/modContratPlus.class.php (%2Fcontratplus%2Fcore%2Fmodules%2FmodContratPlus.class.php)

if ($module && $modulePath) {
	// Include and create instance of the module
	dol_include_once($modulePath);
	$moduleInstance = new $module($db);

	$moduleVersion = $moduleInstance->version;
	$moduleName = $moduleInstance->name;
}
/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

$page_name = "ContactUs";
// A rajouter dans llxHeader
$arrayofcss = array('/h2g2/css/contactbox.css');
$arrayofjs = array();
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans($page_name), '', 'contactus@h2g2');

// Dynamic header configuration using generateInformationTabHeader hook for context h2g2
$reshook = $hookmanager->executeHooks('generateContactUsTabHeader', array('module' => $module));
if ($hookmanager->resArray) {
	// Display the dol_fiche_head
	$head = $hookmanager->resArray['head'];
	$activeTab = $hookmanager->resArray['active_tab'];
	$langsFile = $hookmanager->resArray['langs'];
	$icon = $hookmanager->resArray['header_icon'];
	dol_fiche_head($head, $activeTab, $icon, 0, $langsFile);
}

// Display the two blocks of contact

print '<div class="contact-box">';
// Block 1 for evolution/needs
print '<a href="https://www.code42.store/contactus.php?module='.$moduleName.'" target="_blank" class="link">';
print '<div  class="contact-box-header block1">';
print '<span class="fas fa-comment-dots fa-4x centerBox icon-color"></span>';
print '<p class="contact-box-title centerBox">'.$langs->trans('ContactEvolution').'</p>';
print '</div></a>';

// Block 2 for bugs
print '<a href="https://code42.store/contactus.php?module='.$moduleName.'&version='.$moduleVersion.'" target="_blank" class="link">';
print '<div class="contact-box-header block2">';
print '<span class="fas fa-bug fa-4x icon-color centerBox" ></span>';
print '<p class=" contact-box-title centerBox">'.$langs->trans('ContactBug').'</p>';
print '</div>';
print '</div></a>';

print '<div class="info">'.$langs->trans('contactUsMessage').'</div>';
