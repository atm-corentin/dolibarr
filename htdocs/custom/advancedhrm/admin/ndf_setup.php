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
if(! $res && ! empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if(! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if(! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
// Try main.inc.php using relative path
if(! $res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if(! $res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if(! $res) {
	die('Include of main fails');
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/advancedhrm.lib.php';
require_once '../class/ndftools.class.php';

// Translations
$langs->loadLangs(array('admin', 'advancedhrm@advancedhrm', 'expensereports', 'trips'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('advancedhrmndfsetup', 'planningSetup', 'globalsetup'));

// Access control
if(! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');

if(! class_exists('FormSetup')) {
	// une Pr est en cour pour fixer certains elements de la class en V16 (car c'est des fix/new)
	if(versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && ! class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	}
	else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}

$formSetup = new FormSetup($db);

//'ADVANCEDHRM_KEYAPI_GOOGLEMAPS'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1)
// Permettre sur les notes de frais d'interdire l'ajout de ligne repas si la distance entre le domicile et le restaurant, et la distance entre le travail et le restaurant sont inférieures à la distance paramétrée ci-dessous
$formSetup->newItem('ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE')->setAsYesNo();
// Suite conf précédente avec l'input qui contient la distance minimale pour remboursement
$item = $formSetup->newItem('ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE');
$item->fieldAttr['type'] = 'number';
$item->fieldAttr['min'] = '0';
$item->fieldAttr['step'] = 'any';

$TNdfType = NdfTools::listOfTypes();
$formSetup->newItem('ADVANCEDHRM_NDF_TYPE_IS_MEAL')->setAsMultiSelect($TNdfType);
$formSetup->newItem('ADVANCEDHRM_NDF_TYPE_IS_INVITATIONAL')->setAsMultiSelect($TNdfType);


// planning
$formSetup->newItem('ADVANCEDHRM_MAP_PARAMETERS')->setAsTitle();
$formSetup->newItem('ADVANCEDHRM_ENABLE_GOOGLEMAPS')->setAsYesNo();
$formSetup->newItem('ADVANCEDHRM_KEYAPI_GOOGLEMAPS')->setAsString();


//Utiliser la vue hierarchique par défaut sur le planning utilisateur



// Activer l'utilisation avancée
/*$item = $formSetup->newItem('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE');
$item->setAsYesNo();
$item->helpText = $langs->transnoentities('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE_HELP');*/
//$item->

/*
 * Actions
 */

if($action == 'update' && ! empty($formSetup) && is_object($formSetup) && ! empty($user->admin)) {
	$formSetup->saveConfFromPost();
	header('Location:'.$_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = 'AdvancedHrmSetup';

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = advancedhrmAdminPrepareHead();
print dol_get_fiche_head($head, 'ndf', $langs->trans($page_name), -1, 'advancedhrmsmall.svg@advancedhrm');

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans('advancedHrhSetupPage').'</span><br><br>';

if($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
}
else {
	if(! empty($formSetup->items)) {
		print $formSetup->generateOutput();

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'">'.$langs->trans('Modify').'</a>';
		print '</div>';
	}
	else {
		print '<br>'.$langs->trans('NothingToSetup');
	}
}

// Page end
print dol_get_fiche_end();
?>
<script type="text/javascript">
	$(document).ready(function(){
        showHideLineBySelector('ADVANCEDHRM_ENABLE_GOOGLEMAPS', 'ADVANCEDHRM_KEYAPI_GOOGLEMAPS');
        showHideLineBySelector('ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE', 'ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE');
        showHideLineBySelector('ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE', 'ADVANCEDHRM_NDF_TYPE_IS_MEAL');
    });

    function showHideConfLine(selector, target, forceShow = false, forceHide = false) {
        //Si on est en mode edit ou si on est en mode vu et que le bouton est en mode enable ou si nous cliquons sur le bouton pour enable la conf
        if (!forceHide && (($('#'+selector).val() == 1 && $('#'+selector).length > 0)
            || ($('#'+selector).length == 0 && $('#del_'+selector).is(':visible'))
            || forceShow)) {
            $('#helplink'+target).closest('tr').show();
        } else {
            $('#helplink'+target).closest('tr').hide();
        }
    }

    function showHideLineBySelector(selectorTriggered, targetSelector) {
         $('#set_'+selectorTriggered).on('click', function () {
            showHideConfLine(selectorTriggered, targetSelector, true);
        });
        $('#del_'+selectorTriggered).on('click', function () {
            showHideConfLine(selectorTriggered, targetSelector, false, true);
        });
        $('#'+selectorTriggered).on('change', function () {
			showHideConfLine(selectorTriggered, targetSelector);
        });
        showHideConfLine(selectorTriggered, targetSelector);
    }
</script>
<?php
llxFooter();
$db->close();





