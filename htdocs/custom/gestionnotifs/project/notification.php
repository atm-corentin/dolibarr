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


$object  = new project($db);
$notifs  = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$notifs_2 = new gt_notifcs($db);


$id = GETPOST('id');
$object->fetch($id);
$morejs  = array();



$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);

$title = $langs->trans("Project").' - '.$object->ref.(isset($object->thirdparty->name) ? ' - '.$object->thirdparty->name : '').($object->title ? ' - '.$object->title : '');
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE)) $title = $object->ref.(isset($object->thirdparty->name) ? ' - '.$object->thirdparty->name : '').($object->title ? ' - '.$object->title : '');
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

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


$filter = ' AND ((fk_module ='.$id.' AND name_module ="project") OR (name_module ="projet_task" AND fk_module  IN (select rowid from '.MAIN_DB_PREFIX.'projet_task WHERE fk_projet='.$id.'))) ';
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
$param .= '&id='.$id;
// $nbrtotal = $notifs->fetchAll('DESC','date',0,0,' AND fk_module ='.$id.' AND name_module ="tier" ');
	
$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

	$head = project_prepare_head($object);

dol_fiche_head($head, 'tab_notification', $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));

		// Project card

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $object->title;
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : ';
		if (isset($object->thirdparty->id) && $object->thirdparty->id > 0)
		{
			$morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->rights->projet->all->lire)
		{
			$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
			$object->next_prev_filter = " rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="id" type="hidden" value="'.$id.'">';
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
								if($item->action == 'create_project'){
									$messag = "Ce produit est créée par ";
									print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong>  '.$langs->trans('a').' '.date('H:i',strtotime($item->date));
								}elseif($item->action == 'edit_project'){
									$messag =$langs->trans('Modif') .' '.date('H:i',strtotime($item->date)) .' : ';
									$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';
									$champs = json_decode($item->champs);

									if($champs){
										$messag .= '<span class="champs_edit">';
										foreach ($champs as $key => $value) {
											$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).' <b>:</b></span>';
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
											else 
												$messag .= $value->oldval.' => '.$value->val.'</br>';
										}
										$messag .= '</span>';
									}
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								}elseif($item->action == 'create_contact_project'){
									$cont = explode('_', $item->modul_child);



									$id_ct = (int)$cont[1];

									if(is_numeric($id_ct)){
										$contact = new User($db);
										$contact->fetch($id_ct);
										if($contact->id){
											$messag = " ".$langs->trans('creatnewcontact')." ".$contact->getNomUrl(1);
											print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
										}
									}
									// print_r($contact);
								}elseif($item->action == 'delete_contact_project'){
									$cont = explode('_', $item->modul_child);
									$id_ct = (int)$cont[1];
									if(is_numeric($id_ct)){
										$contact = new User($db);
										$contact->fetch($id_ct);
									}
									if($contact->id){
									}
									$messag = " ".$langs->trans('retircontact')." ";
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'create_task_project'){
									$messag = " ".$langs->trans("creatnewtask")." ".$item->modul_child." ".$langs->trans("ofthisprojet")." ";
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'supprime_task_project'){
									$messag = " ".$langs->trans("deletedtask")." ".$item->modul_child." ".$langs->trans("ofthisprojet")." ";
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag">'.$messag.' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'edit_task_project'){
									$task = new Task($db);
									$task->fetch($item->modul_child);
									$messag = $langs->trans("Modifier").' '.$langs->trans("task").' '.$task->getNomUrl(1).' '.$langs->trans('a').' '.date('H:i',strtotime($item->date)) .' : ';
									$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';
									$champs = json_decode($item->champs);

									if($champs){
										$messag .= '<span class="champs_edit">';
										foreach ($champs as $key => $value) {
											if(empty(preg_match('/options_/i', $key))){
												$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).'</span>';
											}
											if($key == 'progress'){
												$messag .= price($value->oldval, 0, $langs, 1, 0).' %' .' => '.price($value->val, 0, $langs, 1, 0).' % </br>';
												
											}elseif(strpos($key, 'date') === 0){
												$oldate = ($value->oldval>0) ? date('d/m/Y',$value->oldval) : '';
												$newate = ($value->val>0) ? date('d/m/Y',$value->val) : '';
												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';
											}
											elseif($key == 'planned_workload'){
												$messag .= convertSecondToTime($value->oldval, 'allhourmin') .' => '.convertSecondToTime($task->planned_workload, 'allhourmin').'</br>';
											}
											else{
												if(empty(preg_match('/options_/i', $key))){
													$messag .= $value->oldval.' => '.$value->val.'</br>';
												}
											}
										}

        								$messag .= $notifs->getExtraFields($object, $champs, 0, 1);

										$messag .= '</span>';
									}
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								}elseif($item->action == 'add_time_project'){
									$sql ="SELECT * FROM ".MAIN_DB_PREFIX."projet_task_time WHERE rowid = ".$item->modul_child;
									$resql = $db->query($sql);
									if($resql){
										while ($obj =$db->fetch_object($resql)) {
											if($obj->fk_task){
												$task = new Task($db);
												$task->fetch($obj->fk_task);
											}
											$messag = $langs->trans("Add").' '.convertSecondToTime($obj->task_duration, 'allhourmin').' (H:m) '.$langs->trans('For').' '.$task->getNomUrl(1).' '.$langs->trans('at').'  '.date('H:i',strtotime($item->date)) .' : <br>';
										}
									}
									
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								}elseif($item->action == 'delete_time_project'){
									$task = new Task($db);
									$task_id = explode('_', $item->modul_child);
									$task->fetch($task_id[0]);
									$messag = $langs->trans("Delete").' '.convertSecondToTime($task_id[1], 'allhourmin').' (H:m) '.$langs->trans('de').' '.$langs->trans("temp_c").' '.$langs->trans("for").' '.$task->getNomUrl(1).' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)) .' : <br>';
									
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';
								}
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