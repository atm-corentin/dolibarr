<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Notifications");

$object  = new Societe($db);
$notifs  = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$notifs_2 = new gt_notifcs($db);


$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('socid', 'int'));
$object->fetch($id);
$morejs  = array();

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name." - ".$langs->trans('Card');

llxHeader(array(), $title,'','','','',$morejs,0,0);
// die("En cours de traitement ...");

$my_limit 	= 10;

// $limit 	= $conf->liste_limit+1;
$sortfield 		= GETPOST("sortfield",'alpha');
$sortorder 		= GETPOST("sortorder",'alpha');
$page 	= GETPOST("page",'int');
$limit  = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;

$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "date";
if (!$sortorder) $sortorder = "DESC";
$param ="";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;



$head = societe_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans("ThirdParty"), -1, 'company');

$filter = ' AND fk_module ='.$id.' AND name_module ="societe" ';

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
$param .= '&socid='.$id;
// $nbrtotal = $notifs->fetchAll('DESC','date',0,0,' AND fk_module ='.$id.' AND name_module ="tier" ');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');



print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';

	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="socid" type="hidden" value="'.$id.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';

	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbrtotal_2, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';

print '<div id="notif_memebre">';
	print '<table class="noborder">';
		// print '<tr><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">'.$langs->trans("Notifications").'</div></td></tr>';
		// foreach ($notifs2->rows as $key => $value) {
			
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
							if($item->action == 'create_tier'){
								$messag = $langs->trans('soccreatby')." ";
								print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong> '.$langs->trans('at').' '.date('H:i',strtotime($item->date));
							}elseif($item->action == 'edit_tier'){
								$messag = $langs->trans('Modif') .' '.date('H:i',strtotime($item->date)) .': ';
								$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';
								$champs = json_decode($item->champs);
								
								if($champs){
									$messag .= '<span class="champs_edit">';
									foreach ($champs as $key => $value) {
										if(empty(preg_match('/options_/i', $key))){
											$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).'<b>:</b> </span>';
										}
										if($key == 'birth') {
											$oldate = is_int($value->oldval) ? date('d/m/Y',$value->oldval) : '';
											$newate = is_int($value->val) ? date('d/m/Y',$value->val) : '';
											if($oldate || $newate)
												$messag .= $oldate .' => '.$newate.'</br>';
										}
										elseif($key == 'country_id'){
											$sql = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$value->oldval;
							                $resql = $db->query($sql);
							                while ( $obj = $db->fetch_object($resql)) {
							                	$oldcountry = $obj->label;
							                }
							                $sql2 = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$value->val;
							                $resql = $db->query($sql2);
							                while ( $obj = $db->fetch_object($resql)) {
							                	$newcountry = $obj->label;
							                }
											$messag .= $oldcountry.' => '.$newcountry.'</br>';
										}elseif($key == 'state_id'){
									        $sql1 = 'select * from '.MAIN_DB_PREFIX.'c_departements WHERE rowid='.$value->val;
										        $resql = $db->query($sql1);
										        while ( $obj = $db->fetch_object($resql)) {
										            $new_state = $obj->nom;
										        }
									        $sql2 = 'select * from '.MAIN_DB_PREFIX.'c_departements WHERE rowid='.$value->oldval;
										        $resql = $db->query($sql2);
										        while ( $obj = $db->fetch_object($resql)) {
										            $old_state = $obj->nom;
										        }
											$messag .= $old_state.' => '.$new_state.'</br>';

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
								print '<strong>'.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$user_->gender.'</span> ';
							}elseif($item->action == 'create_contact_tier'){
								$cont = explode('_', $item->modul_child);
								$id_ct = (int)$cont[1];
								if(is_numeric($id_ct)){
									$contact = new Contact($db);
									$contact->fetch($id_ct);
								}
								if($contact->id){
									$messag = " ".$langs->trans('creatnewcontact')." ".$contact->getNomUrl(1);
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans('ofcompany').' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)) .'</span> ';
								}
							}elseif($item->action == 'supprimer_contact_tier'){
								$messag = " ".$langs->trans('retircontact')." ".$item->modul_child.' '.$langs->trans('ofcompany');
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag">'.$messag.' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)) .'</span> ';
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

<?php

llxFooter();
