<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

global $langs, $db;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Notifications");

$morejs  = array();
$title = $langs->trans('member').' - '.$modname;
llxHeader(array(), $title,'','','','',$morejs,0,0);
// die("En cours de traitement ...");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('rowid', 'int'));
$object = new Adherent($db);
$notifs = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$notifs_2 = new gt_notifcs($db);
$object->fetch($id);


$my_limit = 10;
$page 	= GETPOST("page",'int');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$sortfield = "date";
$sortorder = "DESC";
$param ="";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;

$filter = ' AND name_module ="member" AND fk_module ='.$id;
$head = member_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans("member"), -1, $object->picto);

$notifs2->fetchAll($sortorder,$sortfield,0,0,$filter.' GROUP BY Date(date)');
$nbrtotal = $notifs->fetchAll('DESC','date',$limit+1,$offset,$filter);

$nbrtotal_2 = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0)
{
	$nbrtotal_2 = $notifs_2->fetchAll($sortorder,$sortfield,0,0,$filter);
	if (($page * $limit) > $nbrtotal_2)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
$param .= '&rowid='.$id;
$morphys["phy"] = $langs->trans("Physical");
$morphys["mor"] = $langs->trans("Moral");

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';

	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="rowid" type="hidden" value="'.$id.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';

	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbrtotal_2, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';
print '<div id="notif_memebre">';
	print '<table class="noborder">';
		
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
								if($item->action == 'creation'){
									$messag = $langs->trans("create");
									print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).'</strong>';
								}elseif($item->action == 'modification'){
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
											if($key == 'birth'){
												$olddate = $ddate = $langs->trans("None");
												if(!empty($value->oldval) && $value->oldval != $langs->trans("None"))
													$olddate = date('d/m/Y',$value->val);
												if(!empty($value->val) && $value->val != $langs->trans("None"))
													$ddate = date('d/m/Y',$value->val);
												$messag .=$olddate .' => '.$ddate.'</br>';
											}
											elseif($key == 'country_id'){
												$sql1 = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$object->country_id;
									                $resql = $db->query($sql1);
									                if($resql){
										                while ( $obj = $db->fetch_object($resql)) {
										                	$new_country = $obj->label;
										                }
									                }
								                $sql2 = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$value->oldval;
									                $resql = $db->query($sql2);
									                if($resql){
										                while ( $obj = $db->fetch_object($resql)) {
										                	$old_country = $obj->label;
										                }
									                }
													$messag .= $old_country.' => '.$new_country.'</br>';
											}elseif($key == 'state_id'){
										        $sql1 = 'select * from '.MAIN_DB_PREFIX.'c_departements WHERE rowid='.$object->state_id;
											        $resql = $db->query($sql1);
											        if($resql){
												        while ( $obj = $db->fetch_object($resql)) {
												            $new_state = $obj->nom;
												        }
											        }
										        $sql2 = 'select * from '.MAIN_DB_PREFIX.'c_departements WHERE rowid='.$value->oldval;
											        $resql = $db->query($sql2);
											        if($resql){
												        while ( $obj = $db->fetch_object($resql)) {
												            $old_state = $obj->nom;
												        }
											        }
												$messag .= (isset($old_state) ? $old_state : '').' => '.(isset($new_state) ? $new_state : '').'</br>';
											}elseif($key == 'gender'){
												$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"), ""=>$langs->trans("None"));
												$messag .= (isset($arraygender[$value->oldval]) ? $arraygender[$value->oldval] : '').' => '.(isset($arraygender[$value->val]) ? $arraygender[$value->val] : '').'</br>';
											}elseif($key == 'morphy'){
												$messag .= $morphys[$value->oldval].' => '.$morphys[$value->val].'</br>';
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
								}elseif($item->action == 'validation'){
									$messag = $langs->trans("valider");
									print '<strong>'.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$user_->gender.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'modif_pw'){
									$messag = $langs->trans('change_pw');
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'create_cotisation'){
									$cont = explode('_', $item->modul_child);
									$id_sub = (int)$cont[1];
									if(is_numeric($id_sub)){
										$sub = new Subscription($db);
										$sub->fetch($id_sub);
									}
									if($id_sub){
										$messag = " ".$langs->trans("addcotis")." ".$sub->getNomUrl(1).'-'.$sub->note.' '.$langs->trans('forthismembre');
										print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
									}
								}elseif($item->action == 'resiliate'){
									$messag = $langs->trans("ResiliateMember");
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'delete_cotisation'){
									$messag = $langs->trans("deletecotisation")." ".$item->modul_child;
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}elseif($item->action == 'modify_cotisation'){
									$messag = $langs->trans("modifcotisation");
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
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
