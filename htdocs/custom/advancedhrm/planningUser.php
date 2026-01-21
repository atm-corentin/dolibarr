<?php

/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
* Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
* Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
* Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
* Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
* Copyright (C) 2013-2021  Alexandre Spangaro      <aspangaro@open-dsi.fr>
* Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
* Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
* Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
* Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
* along with this program. If not, see <https://www.gnu.org/licenses/>.
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


require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

dol_include_once('advancedhrm/lib/advancedhrm.lib.php');
dol_include_once('advancedhrm/class/datetool.class.php');
dol_include_once('holiday/class/holiday.class.php');


// Load translation files required by the page
$langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "errors"));

if (!$user->hasRight('holiday', 'read')){
	accessforbidden();
}

$id_journal = GETPOST('id_journal', 'int');
$action = GETPOST('action', 'aZ09');

$date_startmonth = GETPOST('date_startmonth','int');
$date_startday = GETPOST('date_startday','int');
$date_startyear = GETPOST('date_startyear','int');
$date_endmonth = GETPOST('date_endmonth','int');
$date_endday = GETPOST('date_endday','int');
$date_endyear = GETPOST('date_endyear','int');
$Tusers = GETPOST('users' , "array");
$Tgroups = GETPOST('groups' , "array");

$now = dol_now();

$dateTool = new DateTool($db, $langs, $mysoc);

$hookmanager->initHooks(array('planningUser'));
$parameters = array();

// Security check
if (empty($conf->advancedhrm->enabled)) {
	accessforbidden();
}
// test pour interdire l'acces à la page aux utilisateurs externes
if (!empty($user->fk_soc) && $user->fk_soc > 0){
	accessforbidden();
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);


if (empty($date_start) && empty ($date_end)){
	$date_start=strtotime( date('Y-m-01') );
	$date_end=strtotime( date('Y-m-t') );
}

$form = new Form($db);

$additionalHeader ='/custom/advancedhrm/css/advancedhrm.css';
llxHeader('', $langs->trans("planningUser"),'','',0,0,'',array($additionalHeader));

$nomlink = '';
$periodlink = '';
$exportlink = '';
$builddate = dol_now();
$description = $langs->trans("PlanningDescription") . '<br>';

$periodStart = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0);
$periodEnd = $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
$varlink = '?action='.$action . "&token=".newToken();
$TcumulPresence = array();

planningUserHead($form, $langs->trans('planningUserReport'), $periodStart, $periodEnd, $description, $builddate, $exportlink, array('param1' => 'value1'),  $varlink, $dateTool);


if ($action == 'showPlanning'){
	$errors = 0;

	if (empty($date_start) ) {
		$errors++;
		setEventMessage($langs->trans('noDatesSelected'),'errors');

	}else{
		if (empty($date_end)) {
			$date_end = strtotime(date('Y-m-t', $date_start));
		}
	}

	if (!$errors) {

		// compartimenter les dates par mois dans Tmonth
		$Tmonth = $dateTool->getAllMonthBetweenDates((int)$date_start, (int) $date_end);

		$TusersToProcess = array();
		$Tabsence = array();

		/** USERS ET GROUPES SELECTION */
		$result = getUsersFromSelectedArrays($db, $Tgroups, $Tusers);
		$moreSqlUsers = $result['moreSql'];
		$TusersToProcess = $result['Tusers'];

		/** HOLIDAYS SELECTION */
		getHolidays($db, $conf, $langs, $date_start, $date_end, $moreSqlUsers,$Tabsence);

		$cssDayOff = 'bgcolor="grey"';
		$planningHtml = '<div class="wrapper" >';
		$planningHtml .= '<table class="planning" border="0">';
		$currentYear = '';
		$currentMonth = '';
		$firstMonth = true;
		$lastMonth = count($Tmonth) == 1;

		/**  -------------------------------------------------------------
		   ABSENCES
		  Afficher les values absences pour chaque  Tmonth / Tuser
		//------------------------------------------------------------- */

		$nbUserSelected = count($TusersToProcess);

		//var_dump($Tmonth);
		print_barre_liste($langs->trans('Employees'),0,'','','','','',0, $nbUserSelected, 'user');

		foreach ($Tmonth as $keyMonth => $tm) {

			$lastMonth = ($keyMonth + 1 ) == count($Tmonth);

			/** ***********************************************************************
			 * PROCESSING DRAW HEADER CURRENT YEAR
			 **********************************************************************  */

			//var_dump($dateTool->TMonthTrans[$tm['currentMonthShort']]);
			// Affichage Année en cours de traitement
			if ($currentYear != $tm['yearStart']) {
				$currentYear = $tm['yearStart'];
				$planningHtml .= '<tr class="emptyTr">';
				$planningHtml .= '<td class="cssline" ></td>';
				$planningHtml .= '</tr>';
				$planningHtml .= '<tr class="enteteYear">';
				$planningHtml .= '<td class="year-title" >' . $currentYear .  ' </td><td colspan="4" class="month-title">'.ucfirst($dateTool->TMonthTrans[$tm['currentMonthShort']]).'</td>';
				$planningHtml .= '</tr>';
			}else{
				$planningHtml .= '<tr class="emptyTr">';
				$planningHtml .= '<td class="cssline" ></td>';
				$planningHtml .= '</tr>';
				$planningHtml .= '<tr class="enteteYear">';
				$planningHtml .= '<td></td><td colspan="4" class="month-title">' . ucfirst($dateTool->TMonthTrans[$tm['currentMonthShort']]) . ' </td>';
				$planningHtml .= '</tr>';
			}

			$planningHtml .= '<tr class="emptyTr">';
			$planningHtml .= '<td class="cssline" ></td>';
			$planningHtml .= '</tr>';

			/** ***********************************************************************
			 * PROCESSING DRAW HEADER CURRENT MONTH
			 **********************************************************************  */
			$planningHtml .= '<tr class="tr-header">';
			$planningHtml .= '<td class="cssline center"></td>';
			$startingDay = ($firstMonth) ? $tm['dayStart'] : '1';
			$endingDay = (!$lastMonth) ? $tm['dayEnd']: $tm['dayEndSelected'];
			$currentDate = dol_getdate(dol_now());

			$currentDateFormated = $dateTool->dateToString(getFormatedNumber($currentDate['mday']) , getFormatedNumber($currentDate['mon']), $currentDate['year']);

			//var_dump($currentDateFormated);
			for ($i = 1; $i <= 31 ; $i++) {
				if ($i >= $startingDay && $i <= $endingDay){
					$stdDate = $dateTool->dateToString(getFormatedNumber($i), getFormatedNumber($tm['currentMonth']), $tm['yearStart']);

					$classe ='class="csscubeday  center';

					if (getDolGlobalString('ADVANCEDHRM_ABSENCE_CURRENTDAY_SHOW')){
						if ($currentDateFormated == $stdDate){
							$classe .= ' header-today';
						}
					}
					$classe .= '"';


					$planningHtml .= '<td  colspan ="2" '.$classe.' >' .$stdDate.'</td>';
				}else{
					$planningHtml .= '<td  colspan="2" class="csscubeless-header">&nbsp;</td>';
				}
			}
			$planningHtml .= "</tr>";

			/** ***********************************************************************
			 * PROCESSING DRAW LINES
			 **********************************************************************  */
			foreach ($TusersToProcess as $currentUser) {

				$planningHtml .= "<tr>";
				$planningHtml .= '<td class="cssline presence-right">' . $currentUser['getNomUrl'] . "</td>";

				// foreach sur les jours de 01 à 31
				for ($i = 1; $i <= 31; $i++) {
					$stdDate = $dateTool->dateToString( getFormatedNumber($i), getFormatedNumber($tm['currentMonth']), $tm['yearStart']);
					if ($i >=$startingDay && $i <= $endingDay ){
						// pour ajouter un 0 À $I si le chiffre est compris entre 1 et 9  pour la selection de la clé date dans Tabsence
						$ii = $i;
						$ii = getFormatedNumber($i);

						// $i est compris dans le nombre de jours dans le mois actif
						if ($i <= $tm['dayEnd']) {

							// On ajoute les deux demi-journées à la table
							for ($tdHalfDay = 0 ; $tdHalfDay < 2;$tdHalfDay++) {
								$planningHtml .= '<td  ';
								$getNomUrl = '';

								$arrayDateValue = $tm['yearStart'].'-'.$tm['currentMonth'].'-'.$ii;
								// si une couleur est à utiliser pour cette td
								$usedColor = isset($Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]);

								if ($usedColor) {
									// Couleur pleine si validée
									if ($Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]['statut'] == Holiday::STATUS_APPROVED) {
										$cssTypeDay = 'bgcolor="#' . $dateTool->TColorType[$Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]['type']]['color'] . '"';
										$planningHtml .= $cssTypeDay;
										// Couleur hachurée si Brouillon
									} else if (in_array($Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]['statut'], array(Holiday::STATUS_VALIDATED))) {

										$planningHtml .= ' ' . setColorOnToValidateStatus($dateTool->TColorType[$Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]['type']]['color']);
									}
									// on stock le getNomUrl ( pas exploité pour le moment  ...)
									$getNomUrl = $Tabsence[$arrayDateValue][$currentUser['id']][$tdHalfDay]['getNomUrl'];
								} else {
									$getNomUrl = '';
									$planningHtml .= _setWeekEndColor($tm, $ii, $dateTool, $currentYear);
								}

								// Ajout des classes pour ces tds morning Afternoon
								$planningHtml .= 'class="csscube linkFontsized ';

								// encadrement current day
								if (getDolGlobalString('ADVANCEDHRM_ABSENCE_CURRENTDAY_SHOW')) {
									if ($currentDateFormated == $stdDate) {
										// morning td
										if ($tdHalfDay == 0) {
											$planningHtml .= ' body-today-left';
										} else { // afternoon td
											$planningHtml .= ' body-today-right';
										}
									}
								}
								// Rend les cases où il n'y a pas d'absence cliquables pour ouverture popup de création de demande d'absence
								$fuserid = $currentUser['id'];
								$childids = $user->getAllChildIds(1);

								if (! (in_array($fuserid, $childids) && !$user->hasRight('holiday', 'write')) || (! in_array($fuserid, $childids) && ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('holiday', 'writeall_advance') || !$user->hasRight('holiday', 'writeall'))))) {
									if (empty($getNomUrl)) {
										$linkContent = "<div class='canOpenPopin' style='height:100%;width:100%'></div>";
										$urlToCall = '/holiday/card.php?mainmenu=hrm&leftmenu=holiday&action=create&fuserid=' . $currentUser['id'] .
											'&date_debut_=' . $arrayDateValue . '&date_debut_month=' . $tm['currentMonth'] . '&date_debut_year=' . $tm['yearStart'] . '&date_debut_day=' . $ii .
											'&date_fin_=' . $arrayDateValue . '&date_fin_month=' . $tm['currentMonth'] . '&date_fin_year=' . $tm['yearStart'] . '&date_fin_day=' . $ii;
										$getNomUrl = dolButtonToOpenUrlInDialogPopupHrm('popupNewAbsence' . '-' . $ii . '-' . $tdHalfDay . '-' . $currentUser['id'], $langs->transnoentities('NewAbsence'), $linkContent, $urlToCall);
									}
								}
								$planningHtml .= ' linkFontsized" >'.$getNomUrl.'</td>';
							}
						}
					}else{
						$planningHtml .= '<td  class="csscubeless">&nbsp;</td>' . '<td class="csscubeless" >&nbsp;</td>';
					}
				}
				$planningHtml .= "<tr>";
			}
			/** DRAW CUMUL PRESENCE  */
			$planningHtml .= drawCumulPresence($dateTool,  $startingDay, $endingDay, $Tabsence, $tm, $nbUserSelected,$currentDateFormated);

			// nous ne sommes plus sur le premier mois de traitement
			$firstMonth = false;
		}
		$planningHtml .= "</table></div>";



/* * *************************************************************
 * View
 *************************************************************   */
		echo '<hr>';
		echo $planningHtml;

	}

}

// End of page
llxFooter();




