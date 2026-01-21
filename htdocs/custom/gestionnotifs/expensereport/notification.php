<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array("trips", "bills", "mails", "other"));


$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('id', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$object = new ExpenseReport($db);
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
	if ($ret <= 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

$title = $langs->trans('ExpenseReport')." - ".$langs->trans('notifications');
$help_url = "EN:Module_Expense_Reports|FR:Module_Notes_de_frais";
llxHeader('', $title, $help_url);

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


$filter = ' AND name_module = "expensereport" AND fk_module ='.$id;

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
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }


$head = expensereport_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans('ExpenseReport'), -1, $object->picto);

// ExpenseReport card
$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
$morehtmlref = '';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

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

		$arractions = array(
			'EXPENSE_REPORT_VALIDATE'	=> $object::STATUS_VALIDATED,
			'EXPENSE_REPORT_APPROVE' 	=> $object::STATUS_APPROVED, 
			'EXPENSE_REPORT_UNPAID'		=> $object::STATUS_APPROVED, 
			'EXPENSE_REPORT_PAID'		=> $object::STATUS_CLOSED, 
			'EXPENSE_REPORT_DENY'		=> $object::STATUS_REFUSED, 
			'EXPENSE_REPORT_CANCEL'		=> $object::STATUS_CANCELED, 
		);

		foreach ($notifs2->rows as $key => $value) {
			$nf = new gt_notifcs($db);
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
							$statusline = '';
							if($item->action == 'edited'){
								$messag =$langs->trans('Modif') .' '.date('H:i',strtotime($item->date));
								$champs = json_decode($item->champs);

								if($champs){
									$messag .= ' : <a class="down"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;'.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';

									$messag .= '<span class="champs_edit">';
									foreach ($champs as $key => $value) {
										if($value->oldval == $value->val) continue;

										$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).' <b>:</b></span>';
										if(strpos($key, 'date') === 0){
											$messag .= (is_int($value->oldval) ? date('d/m/Y', $value->oldval) : '') .' => '.date('d/m/Y',$value->val).'</br>';
											
										} elseif($key == 'fk_user_validator') {
											$tmpuser1 = new Societe($db);
											$tmpuser1->fetch($value->oldval);
											$tmpuser2 = new User($db);
											$tmpuser2->fetch($object->fk_user_validator);
											$messag .=$tmpuser1->getNomUrl(1) .' => '.$tmpuser2->getNomUrl(1).'</br>';
										} else {
											$messag .= $value->oldval.' => '.$value->val.'</br>';
										}
									}
									$messag .= '</span>';
								}
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' </span> ';

							}elseif($item->action == 'created'){
								print '<span class="messag">'.$langs->trans('CreatedBy').' '.$user_->getNomUrl(1).'</span><strong>';
								print ' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)).'</strong>';
							}
							elseif(isset($arractions[$item->action])) {
								$statusline = $object->LibStatut($arractions[$item->action]);
							}

							if($statusline) {
								print '<span class="messag">'.$user_->getNomUrl(1).'</span><strong>';
								print ' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)).'</strong>';
								print ' : '.$statusline;
							}

						print '</td>';
					print '</tr>';
				}
			}
		}
		print '<tr>';
		print '</tr>';
	print '</table>';
print '</div>';

llxFooter();
$db->close();