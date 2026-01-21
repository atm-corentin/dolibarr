<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';


dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');


global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array('projects', 'companies', 'gestionnotifs'));

$modname = $langs->trans("Notifications");


$notifs  = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$notifs_2 = new gt_notifcs($db);


$id = GETPOST('id');
$ref = GETPOST('ref');

$withproject = 1;
$object = new Task($db);
$projectstatic  = new Project($db);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$projectstatic->fetch($object->fk_project);
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}

		$object->project = clone $projectstatic;
	} else {
		dol_print_error($db);
	}
}


$morejs  = array();


$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);


$title = $object->ref . ' - ' . $langs->trans("Notes");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '')  ;
}
$help_url = '';

llxHeader("", $title, $help_url);
// die("En cours de traitement ...");

$my_limit 	= 10;
// $conf->liste_limit+1;
$sortfield 		= GETPOST("sortfield",'alpha');
$sortorder 		= GETPOST("sortorder",'alpha');
$page 	= GETPOST("page",'int');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "date";
if (!$sortorder) $sortorder = "DESC";
$param ="";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;


$filter = ' AND fk_module ='.$id.' AND name_module ="projet_task" ';
$notifs2->fetchAll('DESC','date',0,0,$filter.' GROUP BY Date(date)');
$nbrtotal = $notifs->fetchAll('DESC','date',$limit+1,$offset,$filter);

$nbrtotal_2 = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0)
{
	$nbrtotal_2 = $notifs_2->fetchAll('DESC','date',0,0,$filter);
	if (($page * $limit) > $nbrtotal_2)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// $param .= '&id='.$id;
// $nbrtotal = $notifs->fetchAll('DESC','date',0,0,' AND fk_module ='.$id.' AND name_module ="tier" ');
	
$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

		$userWrite = $projectstatic->restrictedProjectArea($user, 'write');
	
	if (!empty($withproject)) {
		// Tabs for project
		$tab = 'tasks';
		$head = project_prepare_head($projectstatic);
		print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

		$param = (isset($mode) && $mode == 'mine' ? '&mode=mine' : '');
		// Project card

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $projectstatic->title;
		// Thirdparty
		if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (empty($user->rights->projet->all->lire)) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Usage
		if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS) || isModEnabled('eventorganization')) {
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (empty($conf->global->PROJECT_HIDE_TASKS)) {
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}
			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
			}
			print '</td></tr>';
		}

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($projectstatic->public) {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans('SharedProject');
		} else {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans('PrivateProject');
		}
		print '</td></tr>';

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (strcmp($projectstatic->budget_amount, '')) {
			print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
		}
		print '</td></tr>';

		// Date start - end project
		print '<tr><td>'.$langs->trans("Dates").'</td><td>';
		$start = dol_print_date($projectstatic->date_start, 'day');
		print ($start ? $start : '?');
		$end = dol_print_date($projectstatic->date_end, 'day');
		print ' - ';
		print ($end ? $end : '?');
		if ($projectstatic->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($projectstatic->description);
		print '</td></tr>';

		// Categories
		if (isModEnabled('categorie')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($projectstatic->id, 'project', 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		print '<br>';
	}

	$head = task_prepare_head($object);
	print dol_get_fiche_head($head, 'tab_notificationtask', $langs->trans('Task'), -1, 'projecttask', 0, '', 'reposition');


	$param = (GETPOST('withproject') ? '&withproject=1' : '');
	$linkback = GETPOST('withproject') ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

	if (!GETPOST('withproject') || empty($projectstatic->id)) {
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
		$object->next_prev_filter = " fk_projet IN (".$db->sanitize($projectsListId).")";
	} else {
		$object->next_prev_filter = " fk_projet = ".$projectstatic->id;
	}

	$morehtmlref = '';

	// Project
	if (empty($withproject)) {
		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("Project").': ';
		$morehtmlref .= $projectstatic->getNomUrl(1);
		$morehtmlref .= '<br>';

		// Third party
		$morehtmlref .= $langs->trans("ThirdParty").': ';
		$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
		$morehtmlref .= '</div>';
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);




print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="id" type="hidden" value="'.$id.'">';
    print '<input type="hidden" name="withproject" value="'.$withproject.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';
	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbrtotal_2, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';

print '<div id="notif_memebre">';
	print '<table class="noborder">';
		// print '<tr><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">'.$langs->trans("Notifications").'</div></td></tr>';
		// foreach ($notifs2->rows as $key => $value) {
		// 	print '<tr>';
		// 		$date = date('d M Y',strtotime($value->date));
		// 		$date2 = date('Y-m-d',strtotime($value->date));
		// 		$diff = date_diff(date_create(),date_create($value->date));
		// 		if($diff->d ==0){
		// 			$d = $langs->trans('Today');
		// 		}
		// 		elseif($diff->d == 1){
		// 			$d = $langs->trans('Yesterday');
		// 		}else{
		// 			$d = $date;
		// 		}
		// 		print '<td class="td_day"><span> '.$d.' </span></td>';
		// 	print '</tr>';
						// d($notifs->rows);
			if($notifs->rows && count($notifs->rows)>0){
				for ($i=0; $i < count($notifs->rows); $i++) { 
					$item = $notifs->rows[$i];
					$user_ = new User($db);
					$user_->fetch($item->fk_user);
					$date2 = date('Y-m-d',strtotime($item->date));
					if(!empty($date_old) && $date_old != $date2 || empty($date_old)){
						$date_old = $date2;
						print '<tr>';
							$date = date('d M Y',strtotime($date_old));
							$date2 = date('Y-m-d',strtotime($date_old));
							$diff = date_diff(date_create(),date_create($date_old));
							if($diff->d ==0){
								$d = $langs->trans('Today');
							}
							elseif($diff->d == 1){
								$d = $langs->trans('Yesterday');
							}else{
								$d = $date;
							}
							print '<td class="td_day"><span> '.$d.' </span></td>';
						print '</tr>';
					}
					// if(date('Y-m-d',strtotime($item->date)) == $date2){
						print '<tr>';
							print '<td>';
							// d($item->action);
								// if($item->action == 'create_task_project'){
								// 	$messag = "Ce produit est créée par ";
								// 	print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong>  '.$langs->trans('a').' '.date('H:i',strtotime($item->date));
								// }
								if($item->action == 'edit_task_project'){
									$messag =$langs->trans('Modif') .' '.date('H:i',strtotime($item->date)) .' : ';
									$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';
									$champs = json_decode($item->champs);
									if($champs){
										$task = new Task($db);
										$task->fetch($item->modul_child);

										$messag .= '<span class="champs_edit">';
										foreach ($champs as $key => $value) {
											if(empty(preg_match('/options_/i', $key))){
												$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).' <b>:</b></span>';
											}
											if($key == 'opp_percent'){
												$messag .= price($value->oldval, 0, $langs, 1, 0).' %' .' => '.price($value->val, 0, $langs, 1, 0).' % </br>';
												
											}elseif(strpos($key, 'date') === 0){
												$oldate = ($value->oldval>0) ? date('d/m/Y',$value->oldval) : '';
												$newate = ($value->val>0) ? date('d/m/Y',$value->val) : '';
												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';
											}elseif($key == 'socid'){
												$soc1 = new Societe($db);
												$soc1->fetch($object->socid);
												$soc2 = new Societe($db);
												$soc2->fetch($value->oldval);
												$messag .=$soc2->getNomUrl(1) .' => '.$soc1->getNomUrl(1).'</br>';
												
											}
											elseif($key == 'opp_status'){
												$code = dol_getIdFromCode($db, $value->oldval, 'c_lead_status', 'rowid', 'code');
												$code_obj = dol_getIdFromCode($db, $value->val, 'c_lead_status', 'rowid', 'code');
													$messag .= $langs->trans("OppStatus".$code).' => '.$langs->trans("OppStatus".$code_obj).'</br>';
											}
											elseif($key == 'planned_workload'){
												$messag .= convertSecondToTime($value->oldval, 'allhourmin') .' => '.convertSecondToTime($task->planned_workload, 'allhourmin').'</br>';
											}
											else{
												$val = str_replace('options_', '', $key);
												if((!empty($extrafields->attributes[$object->table_element]['label']) && !isset($extrafields->attributes[$object->table_element]['label'][$val])) || empty($extrafields->attributes[$object->table_element]['label'])){
													$messag .= $value->oldval.' => '.$value->val.'</br>';
												}
											}
										}

        								$messag .= $notifs->getExtraFields($object, $champs, 0, 1);

										$messag .= '</span>';
									}
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								}
								// elseif($item->action == 'create_contact_project'){
								// 	$cont = explode('_', $item->modul_child);



								// 	$id_ct = (int)$cont[1];

								// 	if(is_numeric($id_ct)){
								// 		$contact = new User($db);
								// 		$contact->fetch($id_ct);
								// 		if($contact->id){
								// 			$messag = " ".$langs->trans('creatnewcontact')." ".$contact->getNomUrl(1);
								// 			print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								// 		}
								// 	}
								// 	// print_r($contact);
								// }elseif($item->action == 'delete_contact_project'){
								// 	$cont = explode('_', $item->modul_child);
								// 	$id_ct = (int)$cont[1];
								// 	if(is_numeric($id_ct)){
								// 		$contact = new User($db);
								// 		$contact->fetch($id_ct);
								// 	}
								// 	if($contact->id){
								// 	}
								// 	$messag = " ".$langs->trans('retircontact')." ";
								// 	print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								// }
								elseif($item->action == 'create_task_project'){
									$messag = " ".$langs->trans("creatnewtask")." ".$item->modul_child." ".$langs->trans("ofthisprojet")." ";
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'supprime_task_project'){
									$messag = " ".$langs->trans("deletedtask")." ".$item->modul_child." ".$langs->trans("ofthisprojet")." ";
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								}
								// elseif($item->action == 'edit_task_project'){
								// 	$task = new Task($db);
								// 	$task->fetch($item->modul_child);
								// 	$messag = $langs->trans("Modifier").' '.$langs->trans("task").' '.$task->getNomUrl(1).' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)) .' : ';
								// 	$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
								// 	$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
								// 	$messag .= '<br>';
								// 	$champs = json_decode($item->champs);

								// 	if($champs){
								// 		$messag .= '<span class="champs_edit">';
								// 		foreach ($champs as $key => $value) {
								// 			$messag .= '<span style="color:#ccc; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).'</span>';
								// 			if($key == 'progress'){
								// 				$messag .= price($value->oldval, 0, $langs, 1, 0).' %' .' => '.price($value->val, 0, $langs, 1, 0).' % </br>';
												
								// 			}elseif(strpos($key, 'date') === 0){
								// 				$oldate = is_int($value->oldval) ? date('d/m/Y',$value->oldval) : '';
								// 				$newate = is_int($value->val) ? date('d/m/Y',$value->val) : '';
								// 				if($oldate || $newate)
								// 					$messag .= $oldate .' => '.$newate.'</br>';
								// 			}
								// 			elseif($key == 'planned_workload'){
								// 				$messag .= convertSecondToTime($value->oldval, 'allhourmin') .' => '.convertSecondToTime($task->planned_workload, 'allhourmin').'</br>';
								// 			}
								// 			else 
								// 				$messag .= $value->oldval.' => '.$value->val.'</br>';
								// 		}
								// 		$messag .= '</span>';
								// 	}
								// 	print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								// }
								// elseif($item->action == 'add_time_project'){
								// 	$sql ="SELECT * FROM ".MAIN_DB_PREFIX."projet_task_time WHERE rowid = ".$item->modul_child;
								// 	$resql = $db->query($sql);
								// 	if($resql){
								// 		while ($obj =$db->fetch_object($resql)) {
								// 			if($obj->fk_task){
								// 				$task = new Task($db);
								// 				$task->fetch($obj->fk_task);
								// 			}
								// 			$messag = $langs->trans("Add").' '.convertSecondToTime($obj->task_duration, 'allhourmin').' (H:m) '.$langs->trans('For').' '.$task->getNomUrl(1).' '.$langs->trans('at').'  '.date('H:i',strtotime($item->date)) .' : <br>';
								// 		}
								// 	}
									
								// 	print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								// }elseif($item->action == 'delete_time_project'){
								// 	$task = new Task($db);
								// 	$task_id = explode('_', $item->modul_child);
								// 	$task->fetch($task_id[0]);
								// 	$messag = $langs->trans("Delete").' '.convertSecondToTime($task_id[1], 'allhourmin').' (H:m) '.$langs->trans('de').' '.$langs->trans("temp_c").' '.$langs->trans("for").' '.$task->getNomUrl(1).' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)) .' : <br>';
									
								// 	print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								// }
							print '</td>';
						print '</tr>';
					// }
				}
			}
		// }
		print '<tr>';
		print '</tr>';
	print '</table>';
print '</div>';



?>

<script>
	$(document).ready(function() {
	})
</script>