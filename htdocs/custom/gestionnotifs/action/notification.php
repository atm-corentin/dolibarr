<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array("agenda"));



$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('id', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$object = new ActionComm($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = new FormMargin($db);
$soc = new Societe($db);
$form = new Form($db);
$notifs =new gt_notifcs($db);
$notifs2 =new gt_notifcs($db);
$notifs_2 =new gt_notifcs($db);


if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret <= 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}
$object->fetch_thirdparty();

llxHeader('', $langs->trans('Proposal'), 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

// die("En cours de traitement ...");

// print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "");
$my_limit = 10;
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


$filter = ' AND name_module ="action" AND fk_module ='.$id;

$notifs2->fetchAll('DESC','date',0,0,$filter.' GROUP BY Date(date)');
$nbrtotal = $notifs->fetchAll('DESC','date',$limit+1,$offset,$filter);
// d($nbrtotal,0);
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

$head = actions_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans("Events"), -1, 'event');


// Link to other agenda views
$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1">';
$linkback .= img_picto($langs->trans("BackToList"), 'object_calendarlist', 'class="pictoactionview pictofixedwidth"');
$linkback .= '<span class="hideonsmartphone">'.$langs->trans("BackToList").'</span>';
$linkback .= '</a>';
$linkback .= '</li>';
$linkback .= '<li class="noborder litext">';
$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_month&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
$linkback .= img_picto($langs->trans("ViewCal"), 'object_calendar', 'class="pictoactionview pictofixedwidth"');
$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewCal").'</span>';
$linkback .= '</a>';
$linkback .= '</li>';
$linkback .= '<li class="noborder litext">';
$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_week&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
$linkback .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview pictofixedwidth"');
$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewWeek").'</span>';
$linkback .= '</a>';
$linkback .= '</li>';
$linkback .= '<li class="noborder litext">';
$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
$linkback .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview pictofixedwidth"');
$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewDay").'</span>';
$linkback .= '</a>';
$linkback .= '</li>';
$linkback .= '<li class="noborder litext">';
$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?mode=show_peruser&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
$linkback .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview pictofixedwidth"');
$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewPerUser").'</span>';
$linkback .= '</a>';

dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'id', 'nom');

$modname = $langs->trans("Notifications");

print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

		$extrafields = new ExtraFields($db);
        $extrafields->fetch_name_optionals_label($object->table_element);

		// foreach ($notifs2->rows as $key => $value) {
		// 	$nf = new gt_notifcs($db);
			// $nf->fetchAll('DESC','date',$limit+1,$offset,$filter.' AND CAST(date as date) ="'.$value->date.'"');
			// if($nf->rows && count($nf->rows) > 0){
			// }
			if($notifs->rows && count($notifs->rows)>0){
				for ($i=0; $i < count($notifs->rows); $i++) { 
					$item = $notifs->rows[$i];
					$user_ = new User($db);
					$user_->fetch($item->fk_user);

					$date2 = date('Y-m-d',strtotime($item->date));
					if(!empty($date_old) && $date_old != $date2 || empty($date_old)){
						$date_old = $date2;
						print '<tr>';
							$date2=date('Y-m-d',strtotime($date_old));
							$date = date('d M Y',strtotime($date_old));
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
						print '<tr>';
							print '<td>';
								if($item->action == 'create_action'){
									$messag = $langs->trans('EventCreatedBy')." ";
									print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)).'</strong>';
								}elseif($item->action == 'edit_action'){
									$messag = $langs->trans('Modif') .' '.date('H:i',strtotime($item->date)) .': ';
									$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
							     	$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
								    $messag .= '<br>';
									$champs = json_decode($item->champs);
									if($champs){

										$messag .= '<span class="champs_edit">';
										foreach ($champs as $key => $value) {
											$val = str_replace('options_', '', $key);
											// if($extrafields->attributes[$object->table_element]['type'][$val] == 'date'){
											// 	$oldfield = dol_print_date($value->oldval, 'day');
											// 	$newfield = dol_print_date($value->val, 'day');
											// 	if($oldfield == $newfield){
											// 		$messag .= "";
											// 	}else{
											// 		$messag .= $langs->trans($value->label);
											// 	}
											// }else{
												// d($extrafields->attributes[$object->table_element]['type'],0);
											if(empty(preg_match('/options_/i', $key))){
												$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >';
													$messag .= $langs->trans($value->label);
												$messag .= '</span>';
											}
											if($key == 'datep') {
												$oldate = ($value->oldval>0) ? dol_print_date($value->oldval, 'dayhour') : '';
												$newate = ($value->val>0) ? dol_print_date($value->val, 'dayhour') : '';
												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';
											}elseif($key == 'datef'){
												$oldate = ($value->oldval>0) ? dol_print_date($value->oldval, 'dayhour') : '';
												$newate = ($value->val>0) ? dol_print_date($value->val, 'dayhour') : '';
												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';
											}
											elseif($key == 'fk_project'){
												$projet = new Project($db);


												if($value->oldval>0){
													$projet->fetch($value->oldval);
													$oldval = $projet->ref.' - '.$projet->title;
												}else{
													$oldval = $value->oldval;
												}

												if($value->val>0){
													$projet->fetch($value->val);
													$newval = $projet->ref.' - '.$projet->title;
												}else{
													$newval = $value->val;
												}

												$messag .= $oldval.' => '.$newval.'</br>';
											}
											elseif($key == 'socid'){
												if($value->val>0){
													$soc1 = new Societe($db);
										            $soc1->fetch($value->val);
										            $concerne1 = $soc1->getNomUrl(1);
										        }else{
										            $concerne1 = $value->val;
										        }

    											if($value->oldval>0){
													$soc2 = new Societe($db);
													$soc2->fetch($value->oldval);
													$concerne2 = $soc2->getNomUrl(1);
												}else{
        											$concerne2 = $value->oldval;
												}

												$messag .= $concerne2.' => '.$concerne1.'</br>';
												
											}
											elseif($key == 'userassigned'){
												$newvalarray = json_decode($value->val);
												$oldvalarray = json_decode($value->oldval);
												$assigneold = new User($db);
												if(isset($oldvalarray)){
													foreach ($oldvalarray as $key => $value) {
														$assigneold->fetch($value->id);
														$oldvals .= $assigneold->getNomUrl(1);
													}
												}
												$assignenew = new User($db);
												if(isset($newvalarray)){
													foreach ($newvalarray as $key => $value) {
														$assignenew->fetch($value->id);
														$vals .= $assignenew->getNomUrl(1);
													}

												}
												$messag .= $oldvals .' => '.$vals.'</br>';
												
											}
											elseif($key == 'socpeopleassigned'){
												$newvalarray = !empty($value->val) ? json_decode($value->val) : '';
												$oldvalarray = !empty($value->oldval) ? json_decode($value->oldval) : '';
												$contactold = new Contact($db);

												if(!empty($oldvalarray)){
													foreach ($oldvalarray as $key => $value) {
														$contactold->fetch($value->id);
														$oldvalscontact .= $contactold->getNomUrl(1).' ';
													}
												}else{
													$oldvalscontact .= $langs->trans('None');
												}

												$contactnew = new Contact($db);
												if(!empty($newvalarray)){
													foreach ($newvalarray as $key => $value) {
														$contactnew->fetch($value->id);
														$valscontact .= $contactnew->getNomUrl(1).' ';
													}

												}else{
													$valscontact .= $langs->trans('None');
												}

												$messag .= $oldvalscontact.' => '.$valscontact.'</br>';
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
<?php

llxFooter();
