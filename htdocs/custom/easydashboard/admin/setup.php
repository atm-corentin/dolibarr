<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      florian Dufourg	<florian.dufourg@outlook.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    easydashboard/admin/setup.php
 * \ingroup easydashboard
 * \brief   EasyDashboard setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

global $langs, $user;

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/easydashboard.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "easydashboard@easydashboard"));

$form = new form($db);


// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters=array(
	'EASYDASHBOARD_USE_CFCV'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array('0'=>'SimpleDisplay', '1'=>'AdvancedDisplay')),
	'EASYDASHBOARD_USE_STAT_ON_PROJECTS'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array('0'=>'No', '1'=>'Yes')),
	'EASYDASHBOARD_IDPROJET_COUTFIXE'=>array('css'=>'minwidth200','enabled'=>1),
	'EASYDASHBOARD_EMPTYPROJECT_ISVARIABLECOST'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array('0'=>'FixedCosts', '1'=>'VariableCosts')),
	'EASYDASHBOARD_CHARGES_EXCLUDES'=>array('css'=>'minwidth400','enabled'=>1),
	'EASYDASHBOARD_GRAPH_NB_MAX_CF'=>array('css'=>'minwidth200','enabled'=>1),
	'EASYDASHBOARD_GRAPH_NB_MAX_CA'=>array('css'=>'minwidth200','enabled'=>1),
	'EASYDASHBOARD_ROUND_NUMBERS'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array('0'=>'0', '1'=>'1', '2'=>'2')),
	'EASYDASHBOARD_CONTRACT_FILTER'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array('ALL'=>'ALL', 'EXTRAFIELD_TYPE_CONTRAT_CLIENT'=>'EXTRAFIELD_TYPE_CONTRAT_CLIENT')),
	'EASYDASHBOARD_DEBUG_VALUE'=>array('css'=>'minwidth200','enabled'=>1,'arrayofkeyval'=>array(
		'0'=>'no debug',
		'fFourByProject'=>'fFourByProject',
		'CAByProject'=>'CAByProject',
		'CAByPeriod'=>'CAByPeriod',
		'contratsEnService'=>'contratsEnService',
		'commandesEnCours'=>'commandesEnCours',
		)),
	//'EASYDASHBOARD_OPPORTUNITIES_TO_SHOW_PERCENT'=>array('css'=>'minwidth200','enabled'=>1)
);



/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}



/*
 * View
 */

$page_name = "EasyDashboardSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_easydashboard@easydashboard');

// Configuration header
$head = easydashboardAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "easydashboard@easydashboard");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("EasyDashboardSetupPage").'</span><br><br>';


if ($action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val)
	{
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		if($key == 'EASYDASHBOARD_PERIOD_START' || $key == 'EASYDASHBOARD_PERIOD_END'){
			
			$date = DateTime::createFromFormat("d/m/Y", $conf->global->$key);
			$dateToShow = '';
			if ($conf->global->$key) $dateToShow = $db->idate($date->getTimestamp());
			print '</td><td>'.$form->selectDate($dateToShow, $key, 0, 0, 1, '', 1, 0).'</td></tr>';
			
		}elseif(array_key_exists('arrayofkeyval', $val)){
			print '</td><td>';
			print '<select name='.$key.'>';
			
			foreach ($val['arrayofkeyval'] as $k => $v){
				
				if ($k == $conf->global->$key) $selectVar = 'selected';
				else $selectVar = '';
				
				print '<option value="'.$k.'" '.$selectVar.'>'.$langs->trans($v).'</option>';
			}
			

			print '</select>';	
			print '</td></tr>';
		}else{
			print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
		}
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{
	if (! empty($arrayofparameters))
	{
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach($arrayofparameters as $key => $val)
		{
			print '<tr class="oddeven"><td>';
			
			print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));

			if(array_key_exists('arrayofkeyval', $val) && $conf->global->$key != ''){
				print '</td><td>' . $langs->trans($val['arrayofkeyval'][$conf->global->$key]) . '</td></tr>';
			}else{
				print '</td><td>' . $conf->global->$key . '</td></tr>';
			}
			
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
	else
	{
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
dol_fiche_end();

llxFooter();
$db->close();
