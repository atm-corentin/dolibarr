<?php

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;

if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');


if (!$user->rights->ganttproadvanced->project->close) {
	accessforbidden();
}

$langs->load('ganttproadvanced@ganttproadvanced');
// $langs->loadLangs(array('ganttproadvanced@ganttproadvanced', 'projects', 'companies', 'commercial'));
// $langs->loadLangs(array('projects', 'companies', 'suppliers', 'compta', 'propal', 'bills', 'orders', 'supplier_proposal'));

$modname = $langs->trans("CloseProject");

$form          = new Form($db);
$user_         = new User($db);
$object 	   = new Project($db);
$task 	   = new Task($db);
$ganttproadvanced  	   = new ganttproadvanced($db);

$newToken     = $_SESSION['newtoken'] ? $_SESSION['newtoken'] : '';
$var 		  = true;
$sortfield 	  = ($_GET['sortfield']) ? $_GET['sortfield'] : "t.fk_statut";
$sortorder 	  = ($_GET['sortorder']) ? $_GET['sortorder'] : "ASC";
$id 		  = $_GET['id'];
$action   	  = $_GET['action'];
$toselect     = GETPOST('toselect', 'array');
$massaction   = GETPOST('massaction', 'alpha');

$srch_ref         = GETPOST('srch_ref');
$srch_title       = GETPOST('srch_title');
$srch_dateo       = GETPOST('srch_dateo');
$srch_datee       = GETPOST('srch_datee');
$srch_status      = GETPOST('srch_status');

$srch_date_startmonth = GETPOST('srch_date_startmonth', 'int');
$srch_date_startday   = GETPOST('srch_date_startday', 'int');
$srch_date_startyear  = GETPOST('srch_date_startyear', 'int');

$srch_date_endmonth = GETPOST('srch_date_endmonth', 'int');
$srch_date_endday   = GETPOST('srch_date_endday', 'int');
$srch_date_endyear  = GETPOST('srch_date_endyear', 'int');

$srch_date_start   = dol_mktime(0, 0, 0, $srch_date_startmonth, $srch_date_startday, $srch_date_startyear);
$srch_date_end     = dol_mktime(23, 59, 59, $srch_date_endmonth ,$srch_date_endday ,$srch_date_endyear); 



$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = 'ASC';
}
if (!$sortfield) {
	$sortfield = 't.rowid';
}
$pageprev = $page - 1;
$pagenext = $page + 1;


if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$typedate = GETPOST('typedate') ? GETPOST('typedate') : 'datec';


// $fin = dol_now();
// $dt_fin = dol_getdate($fin);

// $finyear = $dt_fin['year'];
// $finmonth = $dt_fin['mon'];

$srch_date_start = $srch_date_start ? $srch_date_start : dol_mktime(0, 0, 0, date('m'), 1, date('Y'));
$srch_date_end = $srch_date_end ? $srch_date_end : dol_get_last_day(date('Y'), date('m'));

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {
	$sql = "";
	$srch_ref = "";
	$srch_title = "";
	$srch_status = "";
	// $srch_date_start = "";
	// $srch_date_end = "";
	// $srch_date_startmonth = "";
	// $srch_date_startday = "";
	// $srch_date_startyear = "";
	// $srch_date_endmonth = "";
	// $srch_date_endday = "";
	// $srch_date_endyear = "";
	// $typedate = "";

}


$sql = "SELECT t.rowid, t.entity, t.ref, t.title, t.description, t.public, t.datec, t.opp_amount, t.budget_amount,";
$sql .= " t.tms, t.dateo, t.datee, t.date_close, t.fk_soc, t.fk_user_creat, t.fk_user_modif, t.fk_user_close, t.fk_statut as status, t.fk_opp_status, t.opp_percent,";
$sql .= " t.note_private, t.note_public, t.model_pdf, t.usage_opportunity, t.usage_task, t.usage_bill_time, t.usage_organize_event, t.email_msgid,";
$sql .= " t.accept_conference_suggestions, t.accept_booth_suggestions, t.price_registration, t.price_booth, t.max_attendees";

$sql .= " FROM ".MAIN_DB_PREFIX."projet as t";
$sql .= " WHERE t.entity=".$conf->entity;

$sql .= (!empty($srch_ref)) ? " AND t.ref like '%".$srch_ref."%'" : "";
$sql .= (!empty($srch_title)) ? " AND t.title like '%".$srch_title."%'" : "";
// $sql .= (!empty($srch_date_start)) ? " AND t.dateo like '%".$srch_date_start."%'" : "";
// $sql .= (!empty($srch_date_end)) ? " AND t.datee like '%".$srch_date_end."%'" : "";
if ($srch_status != '' && $srch_status >= 0) {
	if ($srch_status == 99) {
		$sql .= " AND t.fk_statut <> 2";
	} else {
		$sql .= " AND t.fk_statut = ".((int) $srch_status);
	}
}

if($srch_date_start && $srch_date_end && $typedate){
	$sql .= ' AND (';
	$sql .= ' CAST(t.'.$typedate.' as date) BETWEEN "'.$db->idate($srch_date_start).'" AND "'.$db->idate($srch_date_end).'")';
}


if ($action == 'confirm_close' && GETPOST('confirm') == 'yes' ) {
	$error=0;
	$objectstatic = new Project($db);
	$sqlclose = $sql.' AND t.fk_statut != '.Project::STATUS_CLOSED;

	$result = $db->query($sqlclose);
	if($result){
		$num_proj=$db->num_rows($result);
		while ($obj = $db->fetch_object($result)) {
			$sqltask = 'UPDATE '.MAIN_DB_PREFIX.$task->table_element.' SET progress = 100 WHERE fk_projet = '.$obj->rowid;
			$res_sqltask = $db->query($sqltask);
			if($res_sqltask){

				$objectstatic->id = $obj->rowid;
				$objectstatic->entity = $obj->entity;
				$objectstatic->ref = $obj->ref;
				$objectstatic->title = $obj->title;
				$objectstatic->description = $obj->description;
				$objectstatic->date_c = $db->jdate($obj->datec);
				$objectstatic->datec = $db->jdate($obj->datec); // TODO deprecated
				$objectstatic->date_m = $db->jdate($obj->tms);
				$objectstatic->datem = $db->jdate($obj->tms); // TODO deprecated
				$objectstatic->date_start = $db->jdate($obj->dateo);
				$objectstatic->date_end = $db->jdate($obj->datee);
				$objectstatic->date_close = $db->jdate($obj->date_close);
				$objectstatic->note_private = $obj->note_private;
				$objectstatic->note_public = $obj->note_public;
				$objectstatic->socid = $obj->fk_soc;
				$objectstatic->user_author_id = $obj->fk_user_creat;
				$objectstatic->user_modification_id = $obj->fk_user_modif;
				$objectstatic->user_close_id = $obj->fk_user_close;
				$objectstatic->public = $obj->public;
				$objectstatic->statut = Project::STATUS_CLOSED; // deprecated
				$objectstatic->status = Project::STATUS_CLOSED;
				$objectstatic->opp_status = $obj->fk_opp_status;
				$objectstatic->opp_amount	= $obj->opp_amount;
				$objectstatic->opp_percent = $obj->opp_percent;
				$objectstatic->budget_amount = $obj->budget_amount;
				$objectstatic->model_pdf = $obj->model_pdf;
				$objectstatic->modelpdf = $obj->model_pdf; // deprecated
				$objectstatic->usage_opportunity = (int) $obj->usage_opportunity;
				$objectstatic->usage_task = (int) $obj->usage_task;
				$objectstatic->usage_bill_time = (int) $obj->usage_bill_time;
				$objectstatic->usage_organize_event = (int) $obj->usage_organize_event;
				$objectstatic->accept_conference_suggestions = (int) $obj->accept_conference_suggestions;
				$objectstatic->accept_booth_suggestions = (int) $obj->accept_booth_suggestions;
				$objectstatic->price_registration = $obj->price_registration;
				$objectstatic->price_booth = $obj->price_booth;
				$objectstatic->max_attendees = $obj->max_attendees;
				$objectstatic->email_msgid = $obj->email_msgid; 

				$res = $objectstatic->update($user);

				if($res<=0) $error++;
			}
		}
	}

	if($num_proj > 0) {
		setEventMessages($langs->trans("ThisProjectisClosed", $num_proj), null, 'mesgs');
	}
	else {
		setEventMessages($langs->trans("NoProjectToClose"), null, 'errors');
	}
	// header("Location: list.php?restore_lastsearch_values=1");
	// exit;			

}


// $sql .= ' GROUP BY t.rowid';
$sql .= $db->order($sortfield, $sortorder);
// Count total nb of records with no order and no limits
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0) {
    $resql = $db->query($sql);
    if ($resql) {
        $nbtotalofrecords = $db->num_rows($resql);
    } else {
        dol_print_error($db);
    }
    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
}
// Add limit
$sql .= $db->plimit($limit + 1, $offset);
// echo $sql;
$reslistrecu = $db->query($sql);
$num = $reslistrecu ? $db->num_rows($reslistrecu) : 0;

$paramp ="";
if($reslistrecu){

	$param .= $limit ? '&limit='.$limit : '';
	$param .= (!empty($srch_ref)) ? '&srch_ref='.urlencode($srch_ref) : '';
	$param .= (!empty($srch_title)) ? '&srch_title='.urlencode($srch_title) : '';
	$param .= (!empty($srch_datec)) ? '&srch_datec='.urlencode($srch_datec) : '';
	$param .= (!empty($srch_status)) ? '&srch_status='.urlencode($srch_status) : '';

	$paramp .= (!empty($srch_date_start)) ? '&srch_date_start='.urlencode($srch_date_start) : '';
	$paramp .= (!empty($srch_date_end)) ? '&srch_date_end='.urlencode($srch_date_end) : '';
	$paramp .= (!empty($srch_date_startmonth)) ? '&srch_date_startmonth='.urlencode($srch_date_startmonth) : '';
	$paramp .= (!empty($srch_date_startday)) ? '&srch_date_startday='.urlencode($srch_date_startday) : '';
	$paramp .= (!empty($srch_date_startyear)) ? '&srch_date_startyear='.urlencode($srch_date_startyear) : '';
	$paramp .= (!empty($srch_date_endmonth)) ? '&srch_date_endmonth='.urlencode($srch_date_endmonth) : '';
	$paramp .= (!empty($srch_date_endday)) ? '&srch_date_endday='.urlencode($srch_date_endday) : '';
	$paramp .= (!empty($srch_date_endyear)) ? '&srch_date_endyear='.urlencode($srch_date_endyear) : '';
	$paramp .= (!empty($typedate)) ? '&typedate='.urlencode($typedate) : '';
	$param .= $paramp;

	// Selection of new fields

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$morejs  = array();
	llxHeader(array(), $modname,'','','','',$morejs,0,0);
	// echo $sql;

	$arrayofmassactions = array();
	if ($user->rights->ganttproadvanced->supprimer)
		$arrayofmassactions['predeletecerfa'] 	= img_picto('', 'delete', 'class="pictofixedwidth"').' '.$langs->trans("Delete");

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

	$totalarray['nbfield']=0;

	$modganttproadvanced = new modganttproadvanced($db);
	$mve = $modganttproadvanced->version;

	echo '<link rel="stylesheet" href="css/ganttproadvanced_custom.css?v='.$mve.'">';

	if($action == "close"){
	    print $form->formconfirm("closeproject.php?page=0".$paramp,$langs->trans('confirm') , $langs->trans('ConfirmCloseProject'),"confirm_close", 'index.php?page='.$page, 0, 1);
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="index_project">'."\n";
		print '<input name="pagem" type="hidden" value="'.$page.'">';
		print '<input type="hidden" name="token" value="'.$newToken.'">';
		print '<input name="sortfield" type="hidden" value="'.$sortfield.'">';
		print '<input name="sortorder" type="hidden" value="'.$sortorder.'">';
		print '<input name="offsetm" type="hidden" value="'.$offset.'">';
		print '<input name="limitm" type="hidden" value="'.$limit.'">';

		print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'project', 0, '', '', $limit, 0, 0, 1);

		print '<div id="selectcloseproject" style="width:100%">';
		    print '<div class="divsearchfield">';
		    	print $ganttproadvanced->typeDate($typedate, 'typedate', 0);
		    print '</div>';
		    print '<div class="divsearchfield">';
		        print $langs->trans('Period').': ';
				print $form->selectDate($srch_date_start, 'srch_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				print '&nbsp;'.$form->selectDate($srch_date_end, 'srch_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('To'));
		    print '</div>'; 
		    print '<div class="divsearchfield">';
					print '<button type="submit" class="liste_titre button_search reposition" name="button_search_period" value="x"><span class="fa fa-search"></span></button>';

		    	// $searchpicto = $form->showFilterButtons();
				// print $searchpicto;
		    print '</div>';
		    print '<div class="divsearchfield  pull-right" >';
                // print '<input type="submit" value="'.$langs->trans('Close').'" name="bouton" class="button buttonCloturer" />';
		    	if($num>0 && !empty($srch_date_start) && !empty($srch_date_end))
            	print '<a href="./closeproject.php?action=close&id='.$id.$param.'" name="action" class="butAction buttonCloturer" value="close" >'.$langs->trans('Close').'</a>';

		    print '</div>'; 
		print '</div>';

		print '<table id="table-1" class="noborder" style="width: 100%;" >';
			print '<thead>';

				print '<tr class="liste_titre">';
					print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "t.ref", "", $param, 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("ProjectLabel"),$_SERVER["PHP_SELF"], "t.title", '', $param, 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"], "t.dateo", '', $param, 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"], "t.datee", '', $param, 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("DateCreationShort"),$_SERVER["PHP_SELF"], "t.datec", '', $param, 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"], "t.fk_statut", '', $param, 'align="right"', $sortfield, $sortorder);
					print '<th align="center"></th>';
				print '</tr>';

				print '<tr class="liste_titre nc_filtrage_tr">';
					print '<td align="left"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';
					print '<td align="left"><input style="max-width: 129px;" class="" type="text" class="" id="srch_title" name="srch_title" value="'.$srch_title.'"/></td>';
					print '<td align="left"></td>';
					print '<td align="left"></td>';
					print '<td align="left"></td>';
					print '<td class="right">';
						$arrayofstatus = array();
						if(!empty($object->statuts_short)){
							foreach ($object->statuts_short as $key => $val) {
								$arrayofstatus[$key] = $langs->trans($val);
							}
						}
						$arrayofstatus['99'] = $langs->trans("NotClosed").' ('.$langs->trans('Draft').' + '.$langs->trans('Opened').')';
						print $form->selectarray('srch_status', $arrayofstatus, $srch_status, 1, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth125 selectarrowonleft');
						print ajax_combobox('srch_status');
					print '</td>';

				print '<td align="center">';
						$searchpicto = $form->showFilterButtons();
                    	print $searchpicto;
					print '</td>';
				print '</tr>';
			print '</thead>';

			print '<tbody>';
		
				$colspn = 7;
				$i=0;
				$acheteur = new User($db);
				if($num>0){
					while ($i < min($num, $limit)) {

						$var = !$var;
						$obj = $db->fetch_object($reslistrecu);

						$object->id         = $obj->rowid;
						$object->ref        = $obj->ref;
						$object->title      = $obj->title;
						$object->statut     = $obj->status;
						$object->public     = $obj->public;
						$object->status     = $obj->status;

						print '<tr '.$bc[$var].' >';
				    		print '<td align="left">';
								print $object->getNomUrl(1);
							print '</td>';

							print '<td align="left">'.$obj->title.'</td>';
							print '<td align="left">'.dol_print_date($db->jdate($obj->dateo), 'day').'</td>';
							print '<td align="left">'.dol_print_date($db->jdate($obj->datee), 'day').'</td>';
							print '<td align="left">'.dol_print_date($db->jdate($obj->datec), 'dayhour').'</td>';
							print '<td class="right">'.$object->getLibStatut(5).'</td>';
							print '<td align="left"></td>';
						print '</tr>';
						$i++;
					}
				}else{
					print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
				}
			print '</tbody>';
		print'</table>';
	print '</form>';

}else{
	dol_print_error($db);
}

llxFooter();