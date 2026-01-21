<?php
/* Copyright (C) 2022 Arthur Dupond <contact@atm-consulting.fr>
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
 * \file    advancedhrm/lib/advancedhrm.lib.php
 * \ingroup advancedhrm
 * \brief   Library files with common functions for AdvancedHRM
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function advancedhrmAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("advancedhrm@advancedhrm");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/advancedhrm/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	if(!empty($conf->holiday->enabled)) {
		$head[$h][0] = dol_buildpath("/advancedhrm/admin/abs_setup.php", 1);
		$head[$h][1] = $langs->trans("SetupTabHolidays");
		$head[$h][2] = 'abs';
		$h++;
	}

	if(!empty($conf->expensereport->enabled)) {
		$head[$h][0] = dol_buildpath("/advancedhrm/admin/ndf_setup.php", 1);
		$head[$h][1] = $langs->trans("SetupTabExpenseReport");
		$head[$h][2] = 'ndf';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/advancedhrm/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@advancedhrm:/advancedhrm/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@advancedhrm:/advancedhrm/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'advancedhrm@advancedhrm');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'advancedhrm@advancedhrm', 'remove');

	return $head;
}

/**
 *	Show header of a page used to transfer/dispatch data in accounting
 * Gère également la pré-sélection des utilisateurs  pour le formulaire
 * en fonction des conf et droits du module
 *
 *	@param	@form				$form            form
 *	@param	string				$nom            Name of report
 *	@param 	string				$periodStart    Period of report
 *	@param 	string				$periodEnd      Period of report
 *	@param 	string				$description    Description
 *	@param 	integer	            $builddate      Date of generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array				$moreparam		Array with list of params to add into form
 *	@param	string				$calcmode		Calculation mode
 *  @param  string              $varlink        Add a variable into the address of the page
 *  @param  object				$tool           DateTool object
 *	@return	void
 */
function planningUserHead($form, $nom, $periodStart, $periodEnd,  $description, $builddate, $exportlink = '', $moreparam = array(),  $varlink = '',$tool)
{
	global $langs, $db, $conf, $user;

	print "\n\n<!-- start banner planning user -->\n";

	$head = array();
	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
	$head[$h][1] = $langs->trans("planningUser");
	$head[$h][2] = 'planningUser';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=showPlanning'.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print dol_get_fiche_head($head, 'planningUser');

	foreach ($moreparam as $key => $value) {
		print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
	}

	print '<div style="display :flex; ">';
	print '<div style="width:50%;">';




	print '<table class="border centpercent tableforfield">';

	// Ligne de titre
	print '<tr>';
	print '<td class="titlefieldcreate">'.$langs->trans("Name").'</td>';
	print '<td colspan="3">';
	print $nom ;
	print '</td>';
	print '</tr>';

	// Ligne de description
	print '<tr>';
	print '<td>'.$langs->trans("Periodanalyse").'</td>';
	print '<td colspan="3">'.$periodStart.' '.$periodEnd.'</td>';

	print '</tr>';

	// groupe
	print '<tr>';
	print '<td>'.$langs->trans("groups");
	print $form->textwithpicto('',$langs->trans("allGroupsIfEmpty"),1,"info");
	print '</td>';

	print '<td>';
	//@todo  ajouter entity  !
	$sql =' SELECT rowid, nom from '.MAIN_DB_PREFIX.'usergroup ';

	$resql = $db->query($sql);
	$Tgroup = array();
	while ($obj = $db->fetch_object($resql)){
		$Tgroup[$obj->rowid] = $obj->nom;
	}
	print $form->multiselectarray('groups', $Tgroup, GETPOST('groups', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);

	print '</td>';

	print '</tr>';
	print '<tr><td>'.$langs->trans('andor').'</td><td></td><td></td></tr>';

	// user
	print '<tr>';
	print '<td>'.$langs->trans("users").'</td>';
	print '<td>';

	$sql = ' SELECT DISTINCT u.rowid,u.lastname,u.firstname from '.MAIN_DB_PREFIX.'user as  u';

	$sql .= ' WHERE  1=1 ';

	// Lire toutes les demandes de congé (même celles des utilisateurs non subordonnés)
	if (!$user->hasRight('holiday', 'readall')){
		$sql .= ' AND u.fk_user = '.$user->id;
		$sql .=  ' OR u.rowid ='.$user->id;
	}

	// Lire les demandes de congés des utilisateurs externes
	if (!$user->hasRight('advancedhrm', 'holidaysPlanning', 'readExternal')){
		$sql .= ' AND (u.fk_soc = 0 OR u.fk_soc IS NULL) ';
	}

	if(!getDolGlobalString('ADVANCEDHRM_DISPLAY_ALL_USERS')) {
		$sql .= ' AND (u.employee = 1) ';
	}
	$sql .= ' AND u.statut != 0';

	$resql = $db->query($sql);
	if ($resql){
		while ($obj = $db->fetch_object($resql)){
			$userlist[$obj->rowid] = $obj->firstname . ' '. $obj->lastname;
		}
	}

	print img_picto('', 'users') . $form->multiselectarray('users', $userlist, GETPOST('users', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
	print '</td>';

	print '</tr>';
	print '</table>';

	print '</div>';

	print '<div style="width:50%;">';
	print _showHelpColor($tool);
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Submit").'"></div>';

	print '</form>';

	print "\n<!-- end banner planning user -->\n\n";
}

/**
 * retourne un tableau html avec les legendes couleurs pour les type d'absences
 * @param $datetool
 * @param $css
 * @return string
 */
function _showHelpColor($datetool){

	global $langs;
	$html = '<div class="planninglegende"><h5>'.$langs->trans('legende').'</h5></div>';

	$html .= '<table class="planninglegende" width="100%">';

	// ajout des couleurs Utilisateurs
	foreach ($datetool->TColorType as $key => $tct){

		if (isset($datetool->TColorType[$key]['color'])){

			$html .= '<tr>';
			$html .= '<td  class="legende" bgcolor="#'.$datetool->TColorType[$key]['color'].'">';
			$html .= ' ';
			$html .= '</td>';

			$html .= '<td>';

			$labeltoshow = ($langs->trans($datetool->TColorType[$key]['code']) != $datetool->TColorType[$key]['code'] ? $langs->trans($datetool->TColorType[$key]['code']) : $datetool->TColorType[$key]['label']);
			$html .= $labeltoshow;

			if (isset($datetool->TColorType[$key]['delay']))
				$html .= ' ' .$langs->trans( 'delayMesssage', $datetool->TColorType[$key]['delay']);
			$html .= '</td>';
			// à valider
			$html .= '<td  class="legende" '.setColorOnToValidateStatus($datetool->TColorType[$key]['color']).' >';
			$html .= ' ';
			$html .= '</td>';

			$html .= '<td>';
			$html .= $labeltoshow .' '.  $langs->trans('toValidate');
			$html .= '</td>';
			$html .= '</tr>';

		}
	}
	$html .= '<tr><td colspan="4"><hr></td>';
	// ajout des couleurs pré-édfinis week-end et fériés
	$html .= '<tr>';
	foreach ($datetool->TColor as $key => $tct){
		//var_dump($tct);
		if (isset($datetool->TColor[$key]['color'])){


			$html .= '<td  class="legende" bgcolor="#'.$datetool->TColor[$key]['color'].'">';
			$html .= ' ';
			$html .= '</td>';

			$html .= '<td>';
			$html .= $langs->trans($datetool->TColor[$key]['code']);
			$html .= '</td>';



		}
	}
	$html .= '</tr>';


	$html .= "</table>";
	return $html;
}

/**
 * retourne un style css hachuré si le statut est à valider (DRAFT)
 * @param $statut
 * @param $color
 * @return string
 */
function setColorOnToValidateStatus($color){

	$cssRayureToValidate ='style="';
	$cssRayureToValidate  .='background: linear-gradient(45deg, #'.$color;
	$cssRayureToValidate  .=' 12.5%, #fff';
	$cssRayureToValidate  .=' 12.5%, #fff 37.5%, ';
	$cssRayureToValidate  .='#'.$color.' 37.5%, ';
	$cssRayureToValidate  .='#'.$color.' 62.5%, #fff 62.5%, #fff 87.5%, ';
	$cssRayureToValidate  .='#'.$color.' 87.5%);';
	$cssRayureToValidate  .='background-size: 5Px 5px; ';
	$cssRayureToValidate  .='background-position: 5px 5px; ';
	$cssRayureToValidate .='"';

	return $cssRayureToValidate;

}

/**
 * retourne une ligne de table tr des presences jour par jour pour le mois courant.
 * @param $dateTool
 * @param $startingDay
 * @param $endingDay
 * @param $Tabsence
 * @param $tm
 * @param $nbUserSelected
 * @return string
 */
function drawCumulPresence($dateTool, $startingDay, $endingDay, $Tabsence, $tm, $nbUserSelected, $currentDateFormated){
	global $langs, $conf;
	//var_dump($Tabsence);
	$planningHtml = "<tr>";
	$planningHtml .= '<td class="cssline presence-right">' . $langs->trans('CumulPresente') . "</td>";

	// affichage total presence pour le mois en cours
	for ($i = 1; $i <= 31; $i++) {

		if ($i >=$startingDay && $i <= $endingDay ){
			$ii = $i;
			$stdDate = $dateTool->dateToString(getFormatedNumber($ii), getFormatedNumber($tm['currentMonth']), $tm['yearStart']);
			//var_dump($stdDate);
			$value = 0;
			// ON calcul si nous ne sommes pas sur un week-end ou un jour férié
			if (date('N',strtotime($tm['yearStart'] . '-' . $tm['currentMonth'].'-'.$ii)) < 6 && (empty($dateTool->Tdayoff[$tm['currentMonth'] . '-' . $ii]) || is_null($dateTool->Tdayoff[$tm['currentMonth'] . '-' . $ii]))) {

				$temp = !empty($Tabsence[$tm['yearStart'] . '-' . getFormatedNumber($tm['currentMonth']).'-'.getFormatedNumber($ii)]['abs']) ?  $nbUserSelected - (int) $Tabsence[$tm['yearStart'] . '-' . getFormatedNumber($tm['currentMonth']).'-'.getFormatedNumber($ii)]['abs'] : $nbUserSelected;
				$addClasse = ' footer-today ';
				$value = $temp;

			}

			$classes = 'class="presence-right center ';
			// encadrement footer si date du jour
			$classes .= ($currentDateFormated == $stdDate) && getDolGlobalString('ADVANCEDHRM_ABSENCE_CURRENTDAY_SHOW') ? $addClasse.'"' : '"';
			$planningHtml .= '<td colspan="2" '.$classes .'">'.$value.'</td>';

		}else{
			$planningHtml .= '<td  colspan="2" class="csscubeless">&nbsp;</td>';
		}

	}
	$planningHtml .= "</tr>";

	return $planningHtml;
}

/**
 * Remplie un tableau Tabsence pour les utilisateur selectionnés compris dans la période d'analyse.
 * @param $db
 * @param $conf
 * @param $langs
 * @param $date_start
 * @param $date_end
 * @param $sqlUsers
 * @param $Tabsence
 * @return void
 */
function getHolidays($db,$conf, $langs, $date_start, $date_end, $sqlUsers, &$Tabsence){

	global $user;

	$sql = " SELECT  DISTINCT h.date_debut as used_date, h.*,  ";
	$sql .= " u.lastname, u.firstname,";
	$sql .= " t.code, t.label";
	$sql .= " FROM " . MAIN_DB_PREFIX . "holiday as h ";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON (h.fk_user=u.rowid) ";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_holiday_types t ON (h.fk_type=t.rowid)";
	$sql .= " WHERE h.date_debut<='" . date('Y-m-d', $date_end) . ' 23:59:59' . "'";
	$sql .= " AND h.date_fin>='" . date('Y-m-d', $date_start) . ' 00:00:00' . "'";
	$sql .= " AND u.statut=" . User::STATUS_ENABLED;

	// voir les absences à valider en plus
	if (getDolGlobalInt('ADVANCEDHRM_SEE_ABSENCE_TO_VALIDATE')) {
		$sql .= " AND h.statut in(" . Holiday::STATUS_VALIDATED .  ',' . Holiday::STATUS_APPROVED . ') ';
	} else {
		$sql .= " AND h.statut=" . Holiday::STATUS_APPROVED;
	}
	$sql .= " AND u.rowid in (" . implode(',', $sqlUsers) . ")";
	$sql .= " AND u.entity IN (0,".$conf->entity.")  ";
	$sql .= " ORDER BY used_date,u.rowid ";


	$resql = $db->query($sql);


	if ($resql) {
		while ($objResult = $db->fetch_object($resql)) {
			//fonction qui loop sur la durée de l'absence et creer des entrées correspondantes dans Tabsence
			_setHolidayDuration($objResult, $Tabsence);
		}

	} else {
		setEventMessage($langs->trans('NoEntry'));
	}
	//var_dump($Tabsence);
}

/**
 * listes des utilisateurs à afficher dans le planning
 * @param $db
 * @param $Tgroups
 * @param $Tusers
 * @return array
 */
function getUsersFromSelectedArrays($db, $Tgroups, $Tusers ){
	global $langs, $user, $conf;
	$TusersToProcess = array();


	/** GROUPES  */
	// si pas de selection on sélectionne les subalternes de façon récursive
	if (is_array($Tgroups) && count($Tgroups) == 0 && is_array($Tusers) && count($Tusers) == 0) {
		if(!getDolGlobalString('ADVANCEDHRM_USE_HIERARCHY_VIEW')) {
			$sql = 'SELECT ug.fk_usergroup FROM '.MAIN_DB_PREFIX.'usergroup_user as ug';
			$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'user as u on ug.fk_user = u.rowid
					WHERE u.statut != 0 ';
			if(!getDolGlobalString('ADVANCEDHRM_DISPLAY_ALL_USERS')) {
				$sql .= ' AND (u.employee = 1) ';
			}
			$resql = $db->query($sql);
			if($resql) {
				while($obj = $db->fetch_object($resql)) {
					$Tgroups[] = $obj->fk_usergroup;
				}
			}
		} else {
			$Tusers = $user->getAllChildIds(1);
		}

	}
	if (is_array($Tgroups) && count($Tgroups) > 0) {


		$sql = ' SELECT DISTINCT u.rowid,u.lastname,u.firstname from '.MAIN_DB_PREFIX.'user as  u';
		$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'usergroup_user as ug on ug.fk_user = u.rowid  ';
		$sql .= ' WHERE u.statut != 0 AND fk_usergroup in ('.implode (',', $Tgroups) .')';

		// Lire toutes les demandes de congé (même celles des utilisateurs non subordonnés)
		if (!$user->hasRight('holiday', 'readall')){
			$sql .= ' AND ug.fk_user = '.$user->id;
		}
		// Lire les demandes de congés des utilisateurs externes
		if (!$user->hasRight('advancedhrm', 'holidaysPlanning', 'readExternal')){
			$sql .= ' AND (u.fk_soc = 0 OR u.fk_soc IS NULL) ';
		}
		if(!getDolGlobalString('ADVANCEDHRM_DISPLAY_ALL_USERS')) {
			$sql .= ' AND (u.employee = 1) ';
		}

		$resql = $db->query($sql);
		if ($resql){
			while ($obj = $db->fetch_object($resql)){
				$TusersToProcess[$obj->rowid]['id'] = $obj->rowid;
				$TusersToProcess[$obj->rowid]['name'] = $obj->firstname . ' '. $obj->lastname;

				$currentUser = new User($db);
				$result = $currentUser->fetch($obj->rowid);
				if ($result){
					$TusersToProcess[$obj->rowid]['getNomUrl'] = $currentUser->getNomUrl(1);
				}else{
					$TusersToProcess[$obj->rowid]['getNomUrl'] = $obj->firstname . ' '. $obj->lastname;;
				}
			}
		}
	}

	//-------------------------------------------------------------
	// USER
	//-------------------------------------------------------------
	/** USERS  */
	if (is_array($Tusers) && count($Tusers) > 0) {
		foreach ($Tusers as $u) {
			if (!array_key_exists($u, $TusersToProcess))
				$TusersToProcess[$u]['id'] = $u;
			$us = new User($db);
			$result = $us->fetch($u);
			if ($result){
				$TusersToProcess[$u]['name'] = $us->lastname . ' ' . $us->firstname;
				$TusersToProcess[$u]['getNomUrl'] = $us->getNomUrl(1);
			}else{
				setEventMessage($langs->trans('errorLoadGenNomUrl'));
			}
		}
	}
	$sqlUsers = array();
	$Tabsence = array();

	foreach ($TusersToProcess as $tuser) {
		$sqlUsers[] = $tuser['id'];
	}

	return array('moreSql' => $sqlUsers,'Tusers'=> $TusersToProcess);
}

/**
 * @param $tm
 * @param $i
 * @param $dateTool
 * @param $currentYear
 * @return string
 */
function _setWeekEndColor($tm,$i,$dateTool, $currentYear){

	global $conf, $db;
	$value = '';

	/**
	 * le format 'N' de la fonction date renvoie un chiffre correspondant au jour selectionné ( 6 samedi et 7 dimanche )
	 */
	if (!getDolGlobalString('ADVANCEDHRM_ABSENCE_WEEKEND_SHOW')) $conf->global->ADVANCEDHRM_ABSENCE_WEEKEND_SHOW = '#000000';
	$value =(date('N',strtotime($tm['yearStart'].'-'.$tm['currentMonth'].'-'.$i )) >= 6  ) ? 'bgcolor="' . getDolGlobalString('ADVANCEDHRM_ABSENCE_WEEKEND_SHOW').'"' : '';


	if (getDolGlobalString('ADVANCEDHRM_ABSENCE_DAYOFF_SHOW')) {
		// is on a un jour férié on surcharge la couleur
		if (array_key_exists($tm['currentMonth'] . '-' . $i, $dateTool->Tdayoff)){
			$TDisplayDayOff = $dateTool->Tdayoff[$tm['currentMonth'] . '-' . $i];
			if (!empty($TDisplayDayOff)) {
				if (($TDisplayDayOff['year'] == $currentYear && $TDisplayDayOff['dayrule'] == 1 ) ||  $TDisplayDayOff['dayrule'] == 0){ // Classe css jours fériés calculés par annéee
					$value = 'bgcolor="#'.$dateTool->TColor[DateTool::DAYOFF_COLOR]['color'].'"';
				}
			}
		}
	}

	return $value;
}

/**
 *
 *  pour afficher facilement les plannings , on va construire des enregistrements dans Tabsence
 *  pour des absences de 1 jours à n jours consécutifs
 *
 *  on va egalement etablir les règles d'affichage (par demi journée ) dans chaque ligne Tabsence (plus facile à gerer dans
 *  la fonction principale
 *
 * @param $objResult absence (stdClass)
 * @param $Tabsence tableau d'absence pour la period sélectionnée
 * @return void
 */
function _setHolidayDuration($objResult,&$Tabsence){

	global $db, $langs;

	// nb jour de diff entre date debut et fin
	$beginDate = new DateTime($objResult->date_debut);
	$endDate = new DateTime($objResult->date_fin);
	$interval = $beginDate->diff($endDate);
	$diff = $interval->days;

	$abs = new Holiday($db);
	$res = $abs->fetch($objResult->rowid);
	$nom ="";
	if ($res){
		$nom = $abs->getNomUrl(1);
	}else{

		setEventMessage($langs->trans('ErrorOnLoadHoliday'));
	}
	// on loop sur le nb de jour et on créer une Tabsence pour chaque jour de la série
	for($i = 0 ; $i <= $diff ; $i++){

		if ($i === 0) {
			switch ($objResult->halfday) {
				case '-1':
				case '2':
					$currentHalfday = DateTool::HALFDAY_AFTERNOON;
					break;
				case '0':
				case '1':
				default:
					$currentHalfday = DateTool::HALFDAY_MORNING;
					break;
			}
		} else {
			$currentHalfday = DateTool::HALFDAY_MORNING;
		}

		// enregistrement original
		while ($currentHalfday <= DateTool::HALFDAY_AFTERNOON) {
			if (($currentHalfday !== DateTool::HALFDAY_MORNING || (!in_array($objResult->halfday, [0, 1]) && (!in_array($objResult->halfday, [-1, 2]) || $diff <= 0)))
				&& ($currentHalfday !== DateTool::HALFDAY_AFTERNOON || (!in_array($objResult->halfday, [0, -1]) && (!in_array($objResult->halfday, [1, 2]) || $diff <= 0 || $i >= $diff)))) {
				break;
			}

			$temp = $objResult->used_date;
			if ($i === 0) {
				$newUsed = $objResult->used_date;
			} else {
				$newUsed = date('Y-m-d', strtotime($temp . ' + ' . $i . ' days'));
			}

			$fkUser = $objResult->fk_user;
			if ($objResult->statut == Holiday::STATUS_APPROVED) {
				if (!isset($Tabsence[$newUsed][$fkUser])) {
					$Tabsence[$newUsed]['abs'] = ($Tabsence[$newUsed]['abs'] ?? 0) + 1;
				}
			}
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['result'] = '';
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['rowid'] = $objResult->rowid;
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['date_end'] = $objResult->date_fin;
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['type'] = $objResult->fk_type;
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['halfday'] = $objResult->halfday;
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['getNomUrl'] = $nom;
			$Tabsence[$newUsed][$fkUser][$currentHalfday]['statut'] = $objResult->statut;

			$currentHalfday++;
		}


	}
}

/**
 * return a formated 2 digit number prefixed by 0
 * @param $i
 * @return void
 */
function getFormatedNumber($i){
	$ii = $i;
	if (strlen($i) == 1) {
		$ii = str_pad($i, 2, '0', STR_PAD_LEFT);
	}
	return $ii;
}


/**
 *	Return HTML code to output a button to open a dialog popup box.
 *  Such buttons must be included inside a HTML form.
 *
 *  Backport et customisation du la function dolButtonToOpenUrlInDialogPopup présente en 16.0
 *
 *	@param	string	$name				A name for the html component
 *	@param	string	$label 	    		Label shown in Popup title top bar
 *	@param  string	$buttonstring  		button string
 *	@param  string	$url				Url to open
 *  @param	string	$disabled			Disabled text
 *  @param	string	$morecss			More CSS
 *  @param	string	$backtopagejsfields	The back to page must be managed using javascript instead of a redirect.
 *  									Value is 'keyforpopupid:Name_of_html_component_to_set_with id,Name_of_html_component_to_set_with_label'
 * 	@return	string						HTML component with button
 */
function dolButtonToOpenUrlInDialogPopupHrm($name, $label, $buttonstring, $url, $disabled = '', $morecss = 'button bordertransp', $backtopagejsfields = '')
{
	if (strpos($url, '?') > 0) {
		$url .= '&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup='.urlencode($name);
	} else {
		$url .= '?dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup='.urlencode($name);
	}

	$out = '';

	$backtopagejsfieldsid = ''; $backtopagejsfieldslabel = '';
	if ($backtopagejsfields) {
		$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
		if (empty($tmpbacktopagejsfields[1])) {	// If the part 'keyforpopupid:' is missing, we add $name for it.
			$backtopagejsfields = $name.":".$backtopagejsfields;
			$tmp2backtopagejsfields = explode(',', $tmpbacktopagejsfields[0]);
		} else {
			$tmp2backtopagejsfields = explode(',', $tmpbacktopagejsfields[1]);
		}
		$backtopagejsfieldsid = empty($tmp2backtopagejsfields[0]) ? '' : $tmp2backtopagejsfields[0];
		$backtopagejsfieldslabel = empty($tmp2backtopagejsfields[1]) ? '' : $tmp2backtopagejsfields[1];
		$url .= '&backtopagejsfields='.urlencode($backtopagejsfields);
	}

	//print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="file_manager">';
	$out .= '<!-- a link for button to open url into a dialog popup backtopagejsfields = '.$backtopagejsfields.' -->'."\n";
	$out .= '<a class="cursorpointer button_'.$name.($morecss ? ' '.$morecss : '').'"'.$disabled.' title="'.dol_escape_htmltag($label).'">'.$buttonstring.'</a>';
	$out .= '<div id="idfordialog'.$name.'" class="hidden">div for dialog</div>';
	$out .= '<div id="varforreturndialogid'.$name.'" class="hidden">div for returned id</div>';
	$out .= '<div id="varforreturndialoglabel'.$name.'" class="hidden">div for returned label</div>';
	$out .= '<!-- Add js code to open dialog popup on dialog -->';
	$out .= '<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery(".button_'.$name.'").click(function () {
						console.log(\'Open popup with jQuery(...).dialog() on URL '.dol_escape_js(DOL_URL_ROOT.$url).'\');
						var $tmpdialog = $(\'#idfordialog'.$name.'\');
						$tmpdialog.html(\'<iframe class="iframedialog" id="iframedialog'.$name.'" style="border: 0px;" src="'.DOL_URL_ROOT.$url.'" width="100%" height="98%"></iframe>\');
						$tmpdialog.dialog({
							autoOpen: false,
						 	modal: true,
						 	height: (window.innerHeight - 150),
						 	width: \'80%\',
						 	title: \''.dol_escape_js($label).'\',
							open: function (event, ui) {
								console.log("open popup name='.$name.', backtopagejsfields='.$backtopagejsfields.'");
       						},
							close: function (event, ui) {
								returnedid = jQuery("#varforreturndialogid'.$name.'").text();
								returnedlabel = jQuery("#varforreturndialoglabel'.$name.'").text();
								console.log("popup has been closed. returnedid (js var defined into parent page)="+returnedid+" returnedlabel="+returnedlabel);
								if (returnedid != "" && returnedid != "div for returned id") {
									jQuery("#'.(empty($backtopagejsfieldsid)?"none":$backtopagejsfieldsid).'").val(returnedid);
								}
								if (returnedlabel != "" && returnedlabel != "div for returned label") {
									jQuery("#'.(empty($backtopagejsfieldslabel)?"none":$backtopagejsfieldslabel).'").val(returnedlabel);
								}
							}
						});

						$tmpdialog.dialog(\'open\');
					});
				});
			</script>';
	return $out;
}

/**
 * Return an array filled with the day and the month for the dayrule given as paramater
 * Attention, les dates sont juste sur 90% des cas (exception)
 *
 * @param $specialdayrule
 * @param $annee
 * @return array|int
 */
function getSpecialDayruleDayDate($specialdayrule, $annee): array {

	$TDate = array();
	$date_paques = getGMTEasterDatetime($annee);
	if($specialdayrule == 'genevafast') {
		$date = strtotime('next thursday', strtotime('next sunday', mktime(0, 0, 0, 9, 1, $annee)));
	}
	elseif(array_key_exists($specialdayrule, DateTool::NBJOUFORJOUROFFWITHRULES)) {
		$date = $date_paques + DateTool::DAY_LENGTH_TIME_FORMAT * DateTool::NBJOUFORJOUROFFWITHRULES[$specialdayrule];
	}

	if(empty($date)) return -1;
	$TDate['day'] = gmdate('d', $date);
	$TDate['month'] = gmdate('m', $date);
	return $TDate;

}


