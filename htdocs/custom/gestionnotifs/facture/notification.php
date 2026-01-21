<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array('bills', 'companies', 'compta', 'products', 'banks', 'main', 'withdrawals'));



$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$object = new Facture($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = new FormMargin($db);
$soc = new Societe($db);
$form = new Form($db);
$notifs =new gt_notifcs($db);
$notifs2 =new gt_notifcs($db);
$notifs_2 =new gt_notifcs($db);

if ($id > 0 || !empty($ref)) {
	if ($action != 'add') {
		$ret = $object->fetch($id, $ref, '', '', isset($conf->global->INVOICE_USE_SITUATION) ? $conf->global->INVOICE_USE_SITUATION : '');
	}
}
$object->fetch_thirdparty();

$title = $langs->trans('InvoiceCustomer')." - ".$langs->trans('Card');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);
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


$filter = ' AND name_module ="facture" AND fk_module ='.$id;

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

$param .= '&facid='.$id;
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$head = facture_prepare_head($object);
dol_fiche_head($head, 'tab_notification', $langs->trans("InvoiceCustomer"), -1, 'bill');


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref invoice
	$usercancreate ="";
	if ($object->status == $object::STATUS_DRAFT && ! $mysoc->isInEEC() && ! empty($conf->global->INVOICE_ALLOW_FREE_REF)) {
		$morehtmlref .= $form->editfieldkey("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', null, null, '', 1);
		$morehtmlref .= '<br>';
	}
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($usercancreate)
		{
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				$morehtmlref .= $proj->ref;
				$morehtmlref .= '</a>';
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	$object->totalpaye = !empty($totalpaye) ? $totalpaye : ''; // To give a chance to dol_banner_tab to use already paid amount to show correct status

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);
$modname = $langs->trans("Notifications");

print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="facid" type="hidden" value="'.$id.'">';
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
				// if($item->fk_module == $id && !empty($item->modul_child)){
				if($item->fk_module == $id){
					print '<tr>';	
						print '<td>';
							
							if($item->action == 'facture_change_pris'){
								$messag = $langs->trans("invoicecreatby")." ";
								print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</strong>';
							}elseif($item->action == 'edit_facture'){
								
								$messag = $langs->trans("Modify").' '.$langs->trans('at').' '.date('H:i',strtotime($item->date)).': ';
								$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
									$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
									$messag .= '<br>';

								$champs = json_decode($item->champs);
								if($champs){
									$messag .= '<span class="champs_edit">';
									foreach ($champs as $key => $value) {
										if(empty(preg_match('/options_/i', $key))){
											$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >'.$langs->trans($value->label).'<b>:</b></span> ';
										}
										if($key == 'date'){
											$messag .= ' => '.date('d/m/Y',$value->val).'</br>';
										}elseif($key == 'date_lim_reglement'){
											$messag .=' => '.date('d/m/Y',$value->val).'</br>';
										}elseif($key == 'mode_reglement_id'){
											$form->load_cache_types_paiements();
											$form->cache_types_paiements[$value->val]['label'];
											$messag .=' => '.$form->cache_types_paiements[$value->val]['label'].'</br>';
										}elseif($key == 'fk_incoterms'){
											$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $object->fk_incoterms;
											$result = $db->query($sql);
											if ($result)
											{
												$res = $db->fetch_object($result);
												$outcode = isset($res->code) ? $res->code : '';
											}
											$messag .=' => '.$outcode.'</br>';
										}else{
											if(empty(preg_match('/options_/i', $key))){
												$messag .= $value->oldval.' => '.$value->val.'</br>';
											}
										}
											// $messag .=' => '.$value->val.'</br>';
									}

    								$messag .= $notifs->getExtraFields($object, $champs, 0, 1);

									$messag .= '</span>';
								}
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.'</span> ';
							}elseif($item->action == 'valider_facture'){
								$messag = " ".$langs->trans('validthisfactur')." ";
								print '<strong>'.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$user_->gender. ' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'inset_line_facture'){
								$filed = $item->modul_child;
								$filed = explode(':', $filed);
								$messag = " ".$langs->trans("insertnewline")." ".$filed[1]." ".$filed[0]." ".$langs->trans("de_prix")." ".$filed[2]." ".$langs->trans("tothisfactur")." : ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'create_reglement_facture'){
								$filed = $item->modul_child;
								$filed = explode(':', $filed);
								$messag = " ".$langs->trans("enternewpaymnt")." ".$filed[0]." ".$langs->trans("of_amount")." ".$filed[1]." ".$langs->trans("tofactur")." : ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'supprime_reglement_facture'){
								$filed = $item->modul_child;
								$messag = " ".$langs->trans("remove_paymnt")." ".$filed." ".$langs->trans("offactur")." : ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'annule_facture'){
								$messag = " ".$langs->trans("cancelFactur")." ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'payed_facture'){
								$messag = " ".$langs->trans("paidfactur")." ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("ontime").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'unvalid_facture'){
								$messag = " ".$langs->trans("BillStatusDraft")." ";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("ontime").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'unpayed_facture'){
								$messag = " ".$langs->trans('reopenfactur')."";
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("ontime").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'create_contact_facture'){
								$cont = explode('_', $item->modul_child);
								$id_ct = (int)$cont[1];
								if(is_numeric($id_ct)){
									$contact = new User($db);
									$contact->fetch($id_ct);
								}
								if($contact->id){
									$messag = " ".$langs->trans("creatnewcontact")." ".$contact->getNomUrl(1);
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}
							}elseif($item->action == 'delete_contact_facture'){
								$cont = explode('_', $item->modul_child);
								$id_ct = (int)$cont[1];
								if(is_numeric($id_ct)){
									$contact = new User($db);
									$contact->fetch($id_ct);
								}
								$messag = " ".$langs->trans('retircontact')." ".$contact->getNomUrl(1).' '.$langs->trans('oflistcontact');
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.date('H:i',strtotime($item->date)).'</span> ';
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
