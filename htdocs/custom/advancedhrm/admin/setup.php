<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 jean-pascal boudet <jean-pascal.boudet@atm-consulting.fr>
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
 * \file    advancedhrm/admin/setup.php
 * \ingroup advancedhrm
 * \brief   advancedhrm setup page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/advancedhrm.lib.php';


// Translations
$langs->loadLangs(array("admin", "advancedhrm@advancedhrm"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('advancedhrmsetup', 'planningSetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');

if(!class_exists('FormSetup')){
	// une Pr est en cour pour fixer certains elements de la class en V16 (car c'est des fix/new)
	if (versioncompare(explode('.' , DOL_VERSION), array(15)) < 0 && !class_exists('FormSetup')){
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}


$formSetup = new FormSetup($db);

//'ADVANCEDHRM_KEYAPI_GOOGLEMAPS'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1)
//$formSetup->newItem('ADVANCEDHRM_KEYAPI_GOOGLEMAPS')->setAsString();


// planning
//$formSetup->newItem('ADVANCEDHRM_PLANNING_PARAMETERS')->setAsTitle();

// Griser les week-ends dans la vue planning des absences.
//$formSetup->newItem('ADVANCEDHRM_ABSENCE_WEEKEND_SHOW')->setAsColor();

// Afficher les jours fériés dans la vue planning des absences.
//$formSetup->newItem('ADVANCEDHRM_ABSENCE_DAYOFF_SHOW')->setAsYesNo();

//$formSetup->newItem('ADVANCEDHRM_ABSENCE_CURRENTDAY_SHOW')->setAsYesNo();
// si pr eldy ok on n'utilise pas
// Permettre de voir, sur la vue planning des absences, les absences "à valider"
//$formSetup->newItem('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE')->setAsYesNo();
// si pr eldy ok on n'utilise pas
// Permettre sur les notes de frais d'interdire l'ajout de ligne repas si la distance entre le domicile et le restaurant, et la distance entre le travail et le restaurant sont inférieures à la distance paramétrée ci-dessous
//$formSetup->newItem('ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE')->setAsYesNo();
//Utiliser la vue hierarchique par défaut sur le planning utilisateur
//$formSetup->newItem('ADVANCEDHRM_USE_HIERARCHY_VIEW')->setAsYesNo();
//$formSetup->newItem('ADVANCEDHRM_DISPLAY_ALL_USERS')->setAsYesNo();

// Suite conf précédente avec l'input qui contient la distance minimale pour remboursement
//$item = $formSetup->newItem('ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE');
//$item->fieldAttr['type'] = 'number';
//$item->fieldAttr['min'] = '0';
//$item->fieldAttr['step'] = 'any';

// Activer l'utilisation avancée
/*$item = $formSetup->newItem('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE');
$item->setAsYesNo();
$item->helpText = $langs->transnoentities('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE_HELP');*/
//$item->



/*
 * Actions
 */

if ($action == 'update' && !empty($formSetup) && is_object($formSetup) && !empty($user->admin)) {

	$formSetup->saveConfFromPost();
	header('Location:'.$_SERVER['PHP_SELF']);
	exit;
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "AdvancedHrmSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = advancedhrmAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'advancedhrmsmall.svg@advancedhrm');

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("advancedHrhSetupPage").'</span><br>';
echo '<span class="opacitymedium">'.$langs->trans("ADVHRMThisModuleUseDependencies").'</span><br><br>';


if(empty($conf->holiday->enabled) && empty($conf->expensereport->enabled)){
	echo '<div class="warning">'.$langs->trans("NeedAtLeastActivationOfOneOFThesesModules").'</div>';
}else{
	echo '<fieldset style="margin-bottom: 20px;">';
	if(!empty($conf->holiday->enabled)){
		echo '<legend>'.$langs->trans('ModuleXIsActivated', $langs->transnoentitiesnoconv('ModuleHolidays')).'</legend>';
		echo '<span class="fa fa-arrow-right"></span> <a href="'.dol_buildpath("/advancedhrm/admin/abs_setup.php", 1).'" >'.$langs->trans("ConfigureOptionOFX", $langs->transnoentitiesnoconv('ModuleHolidays')).'</a>';

	}else{
		echo '<legend>'.$langs->trans('ModuleXNotActivated', $langs->transnoentitiesnoconv('ModuleHolidays')).'</legend>';
		echo $langs->trans("ModuleXCanToBeActivatedToAccessMoreConf", $langs->transnoentitiesnoconv('ModuleHolidays'));
		echo '</fieldset>';

	}
	echo '</fieldset>';

	echo '<fieldset>';
	if(!empty($conf->expensereport->enabled)){
		echo '<legend>'.$langs->trans('ModuleXIsActivated', $langs->transnoentitiesnoconv('ModuleExpenseReport')).'</legend>';
		echo '<span class="fa fa-arrow-right"></span> <a href="'.dol_buildpath("/advancedhrm/admin/ndf_setup.php", 1).'" >'.$langs->trans("ConfigureOptionOFX", $langs->transnoentitiesnoconv('ModuleExpenseReport')).'</a>';
	}else{
		echo '<legend>'.$langs->trans('ModuleXNotActivated', $langs->transnoentitiesnoconv('ModuleExpenseReport')).'</legend>';
		echo $langs->trans("ModuleXCanToBeActivatedToAccessMoreConf", $langs->transnoentitiesnoconv('ModuleExpenseReport'));
	}
	echo '</fieldset>';
}




if ($action == 'edit') {

	print $formSetup->generateOutput(true);
	print '<br>';
} else {
	if (!empty($formSetup->items)) {
		print $formSetup->generateOutput();

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
	else {
		// print '<br>'.$langs->trans("NothingToSetup");
	}
}

// Page end
print dol_get_fiche_end();


llxFooter();
$db->close();





