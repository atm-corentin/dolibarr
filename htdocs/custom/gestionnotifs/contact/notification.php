<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
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


$object  = new Contact($db);
// d($object,false);
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

$title = $title = $langs->trans('contact')." - ".$langs->trans('Card');
$help_url = "EN:Module_contact|FR:Module_contact|ES:M&oacute;dulo_Proyectos";

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


$filter = ' AND fk_module ='.$id.' AND name_module ="contact" ';
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
$nbrtotal = $notifs->fetchAll('DESC','date',0,0,' AND fk_module ='.$id.' AND name_module ="contact" ');
	
$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

	$head = contact_prepare_head($object);

dol_fiche_head($head, 'tab_notification', $langs->trans("Contact"), -1, (isset($object->public) ? 'contactpub' : 'contact'));

$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$objsoc = new Societe($db);

$morehtmlref .= '<div class="refidno">';
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
	$objsoc->fetch($object->socid);
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' : ';
	if ($objsoc->id > 0) {
		$morehtmlref .= $objsoc->getNomUrl(1, 'contact');
	} else {
		$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
	}
}
$morehtmlref .= '</div>';

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
								if($item->action == 'create_contact'){
									$messag = "Ce contact est créée par ";
									print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong>  '.$langs->trans('a').' '.date('H:i',strtotime($item->date));
								}elseif($item->action == 'edit_contact'){
									$messag =$langs->trans('Modif') .' '.date('H:i',strtotime($item->date)) .' : ';
									$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';
									$champs = json_decode($item->champs);

									if($champs){
										$messag .= '<span class="champs_edit">';
										foreach ($champs as $key => $value) {

											if(empty(preg_match('/options_/i', $key))){
												$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).' <b>:</b></span>';
											}

											if($key == 'opp_percent'){
												$messag .= price($value->oldval, 0, $langs, 1, 0).' %' .' => '.price($value->val, 0, $langs, 1, 0).' % </br>';
												
											}elseif(strpos($key, 'date') === 0){
												$oldate = is_int($value->oldval) ? date('d/m/Y',$value->oldval) : '';
												$newate = is_int($value->val) ? date('d/m/Y',$value->val) : '';
												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';
											}
											elseif($key == 'socid'){
												if($value->val>0){
													$soc1 = new Societe($db);
													$soc1->fetch($value->val);
										            $tier1 = $soc1->getNomUrl(1);
												}else{
										            $tier1 = $langs->trans("None");
												}
    											if($value->oldval>0){
													$soc2 = new Societe($db);
													$soc2->fetch($value->oldval);
													$tier2 = $soc2->getNomUrl(1);
												}else{
        											$tier2 = $langs->trans("None");
												}
												$messag .=$tier2 .' => '.$tier1.'</br>';
											}elseif($key == 'civility_code'){

												$oldate = $langs->getLabelFromKey($db, "Civility".$value->oldval, "c_civility", "code", "label", $code);

												$newate = $langs->getLabelFromKey($db, "Civility".$value->val, "c_civility", "code", "label", $code);

												if($oldate || $newate)
													$messag .= $oldate .' => '.$newate.'</br>';

											}else{
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
								
								elseif($item->action == 'enable_contact' || $item->action == 'disable_contact'){
									
									$messag = $langs->trans($item->action);
								    print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong> à '.date('H:i',strtotime($item->date));
									}
									

							print '</td>';
						print '</tr>';
				}
			}

		print '<tr>';
		print '</tr>';
	print '</table>';
print '</div>';



?>

<script>
	$(document).ready(function() {
	})
</script>