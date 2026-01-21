<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array('admin', 'orders', 'sendings', 'companies', 'bills', 'propal', 'receptions', 'supplier_proposal', 'deliveries', 'products', 'stocks', 'productbatch'));


$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('id', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$object = new CommandeFournisseur($db);
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

$title = $langs->trans('SupplierOrder')." - ".$langs->trans('notifications');
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
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


$filter = ' AND name_module ="order_supplier" AND fk_module ='.$id;

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

$soc = new Societe($db);
$soc->fetch($object->socid);

$head = ordersupplier_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans('SupplierOrder'), -1, $object->picto);

$soc = new Societe($db);
$soc->fetch($object->socid);

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref supplier
$usercancreate ="";
$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref .= '<br>'.$langs->trans('ThirdParty');
if (!empty($conf->global->MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER) && !empty($usercancreate) && $action == 'edit_thirdparty') {
	$morehtmlref .= ' : ';
	$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	$morehtmlref .= '<input type="hidden" name="action" value="set_thirdparty">';
	$morehtmlref .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	$morehtmlref .= $form->select_company($object->thirdparty->id, 'new_socid', 's.fournisseur=1', '', 0, 0, array(), 0, 'minwidth300');
	$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	$morehtmlref .= '</form>';
}
if (empty($conf->global->MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER) || $action != 'edit_thirdparty') {
	if (!empty($conf->global->MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER) && $object->statut == CommandeFournisseur::STATUS_DRAFT) {
		$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit_thirdparty&token='.$_SESSION['newtoken'].'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetThirdParty')).'</a>';
	}
	$morehtmlref .= ' : '.$object->thirdparty->getNomUrl(1, 'supplier');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
	}
}

// Project
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
	if ($usercancreate) {
		if ($action != 'classify' && $caneditproject) {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.$_SESSION['newtoken'].'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		}
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$morehtmlref .= $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $object->socid : -1), $object->fk_project, 'projectid', 0, 0, 1, 1, 1, 0, 0, '', 1, 0, 'maxwidth500');
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= ' : '.$proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= ' - '.$proj->title;
			}
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';

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
			'ORDER_SUPPLIER_STATUS_DRAFT' => $object::STATUS_DRAFT,
			'ORDER_SUPPLIER_VALIDATE' => $object::STATUS_VALIDATED,
			'ORDER_SUPPLIER_CLASSIFY_BILLED' => $object::STATUS_VALIDATED,
			'ORDER_SUPPLIER_APPROVE' => $object::STATUS_ACCEPTED,
			'ORDER_SUPPLIER_REFUSE' => $object::STATUS_REFUSED,
			'ORDER_SUPPLIER_CANCEL' => $object::STATUS_CANCELED,
			'ORDER_SUPPLIER_SUBMIT' => $object::STATUS_ORDERSENT,
			'ORDER_SUPPLIER_RECEIVE' => $object::STATUS_RECEIVED_PARTIALLY,
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
											
										} elseif(strpos($key, 'fk_user') === 0) {
											$tmpuser1 = new User($db);
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

								$billed = ($item->action == 'ORDER_SUPPLIER_CLASSIFY_BILLED') ? 1 : 0;
								$statusline = $object->LibStatut($arractions[$item->action], 0, $billed);
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



?>

<script>
	$(document).ready(function() {
	})
</script>
<?php

llxFooter();
