<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Notifications");

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);
// die("En cours de traitement ...");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$object = new Adherent($db);
$notifs = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$object->fetch($id);


$limit 	= $conf->liste_limit+1;

$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$head = member_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans("member"), -1, $object->picto);

$notifs2->fetchAll('DESC','date',$limit,$offset,' GROUP BY date');
$nbrtotal = $notifs->fetchAll('DESC','date',$limit,$offset,' AND fk_module ="adherent" ');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid');

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotal);


print '<div id="notif_memebre">';
	print '<table class="noborder">';
		// print '<tr><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">'.$langs->trans("Notifications").'</div></td></tr>';
		foreach ($notifs2->rows as $key => $value) {
			print '<tr>';
				$date = date('d M Y',strtotime($value->date));
				$diff = date_diff(date_create(),date_create($value->date));
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
			if($notifs->rows && count($notifs->rows)>0){
				for ($i=0; $i < count($notifs->rows); $i++) { 
					$item = $notifs->rows[$i];
					$user_ = new User($db);
					$user_->fetch($item->fk_user);
					if($item->date == $value->date){
						print '<tr>';
							print '<td>';
								if($item->action == 'creation'){
									$messag = "Ce Adhérent été crée par ";
									print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).'</strong>';
								}elseif($item->action == 'modification'){
									$messag = " été Modifié Ce Adhérent ";
									print '<strong>'.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$user_->gender.'</span> ';
								}elseif($item->action == 'validation'){
									$messag = " été Validé Ce Adhérent ";
									print '<strong>'.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$user_->gender.'</span> ';
								}
							print '</td>';
						print '</tr>';
					}
				}
			}
		}
		print '<tr>';
		print '</tr>';
	print '</table>';
print '</div>';



?>

<script>
	$(document).ready(function() {
		$('#tab_notification').removeClass("tabunactive");
		$('#tab_notification').addClass("tabactive");
	})
</script>

<style>
	.td_day{
		width: 100%;
	    margin-top: 15px;
	    text-align: center;
	    margin-bottom: 30px;
	    padding: 0px !important;
	    border-bottom: 1px solid #ced4da;
	}
	.td_day span{
		position: relative;
	    top: 10px;
	    margin: 0 auto;
	    padding: 0 10px;
	    font-weight: bold;
	    font-size: 16px;
	    background: white;
	}
</style>