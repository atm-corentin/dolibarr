<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');


global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Notifications");


$object  = new Product($db);
$notifs  = new gt_notifcs($db);
$notifs2 = new gt_notifcs($db);
$notifs_2 = new gt_notifcs($db);

$sortfield = GETPOST('sortfield', 'aZ09comma');

$sortorder = GETPOST('sortorder', 'aZ09comma');

$id = GETPOST('id');
$object->fetch($id);
$morejs  = array();



$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Card');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Card');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}


llxHeader('', $title,$helpurl);
// die("En cours de traitement ...");

$limit 	= 10;
// $conf->liste_limit+1;

$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$filter = ' AND fk_module ='.$id.' AND name_module ="product" ';

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
$param ="";
$param .= '&id='.$id;
// $nbrtotal = $notifs->fetchAll('DESC','date',0,0,' AND fk_module ='.$id.' AND name_module ="tier" ');
	
$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct".$object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

dol_fiche_head($head, 'tab_notification', $titre, -1, $picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter = " fk_product_type = ".$object->type;

$shownav = 1;
if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid');


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
							if($item->action == 'create_product'){
								$messag = "Ce projet créée par ";
								print ' <span class="messag">'.$messag.' '.$user_->gender.'</span> <strong>'.$user_->getNomUrl(1).' </strong>';
							}elseif($item->action == 'edit_product'){
								$messag = $langs->trans('Modif') .' '.date('H:i',strtotime($item->date)).' : ';

								$messag .= '<a class="down"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-down"></i></a>';
								$messag .= '<a class="up"><span style="color:#31a9d8"> &nbsp;&nbsp; '.$langs->trans("show_modifs").'</span> <i class="fas fa-chevron-up"></i></a>';
								$messag .= '<br>';

								$champs = json_decode($item->champs);
								$statsv = array('0' => $langs->trans('ProductStatusNotOnSell'),'1' => $langs->trans('ProductStatusOnSell'));

								$statsh = array('0' => $langs->trans('ProductStatusNotOnBuy'),'1' => $langs->trans('ProductStatusOnBuy'));
								if($champs){
									$messag .= '<span class="champs_edit">';
									foreach ($champs as $key => $value) {

										if(empty(preg_match('/options_/i', $key))){
											$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >';
												$messag .= $langs->trans($value->label);
												if($key == 'status') $messag .=' ('.$langs->trans("Sell").')';
												if($key == 'status_buy') $messag .= ' ('.$langs->trans("Buy").')';
											$messag .= '</span>';
										}

										if($key == 'birth'){
											$oldate = is_int($value->oldval) ? date('d/m/Y',$value->oldval) : '';
											$newate = is_int($value->val) ? date('d/m/Y',$value->val) : '';
											if($oldate || $newate)
												$messag .= $oldate .' => '.$newate.'</br>';
										}
										elseif($key == 'status'){
											$messag .=$statsv[$value->oldval] .' => '.$statsv[$value->val].'</br>';
										}elseif($key == 'status_buy'){
											$messag .=$statsh[$value->oldval] .' => '.$statsh[$value->val].'</br>';
										}
										elseif($key == 'country_id'){
											if($value->val>0){
												$sql = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$value->val;
								                $resql = $db->query($sql);
								                if($resql){
									                while ( $obj = $db->fetch_object($resql)) {
														$country=$obj->label;
									                }
								                }
							                }else{
												$country=$value->val;
							                }

											if($value->oldval>0){
								                $sql2 = 'select * from '.MAIN_DB_PREFIX.'c_country WHERE rowid='.$value->oldval;
								                $resql = $db->query($sql2);
								                if($resql){
									                while ( $obj = $db->fetch_object($resql)) {
														$old_country=$obj->label;
									                }
								                }
											}else{
												$old_country=$value->oldval;
											}
												$messag .= $old_country.' => '.$country.'</br>';
										}
										elseif($key == 'fk_default_warehouse'){

											$entrpold = $entrp = '';
											if(!empty($object->fk_default_warehouse)){
												$warehouse = new Entrepot($db);
	            								$warehouse->fetch($object->fk_default_warehouse);
            									$entrp = $warehouse->getNomUrl(1);
											}

											if(!empty($object->fk_default_warehouse)){
												$warehouse_old = new Entrepot($db);
	            								$warehouse_old->fetch($value->oldval);
	            								$entrpold = $warehouse->getNomUrl(1);
	            							}
											$messag .= $entrpold.' => '.$entrp.'</br>';
										}
										elseif($key == 'weight_units' || $key == 'weight'){
											// $messag .= $value->oldval." ".measuringUnitString(0, "weight",$object->weight_units ).' => '.$object->weight." ".measuringUnitString(0, "weight", $object->weight_units).'</br>';
											$messag .= $value->oldval." ";
											if( $value->oldval != $langs->trans("None"))
												$messag .= measuringUnitString(0, "weight", $object->weight_units);
											$messag .= ' => ';
											$messag .= $object->weight." ";
											if( $object->weight != $langs->trans("None"))
												$messag .= measuringUnitString(0, "weight", $object->weight_units);
											$messag .='</br>';
										}
										elseif($key == 'surface_units' || $key == 'surface'){
											$messag .= $value->oldval." ";
											if( $value->oldval != $langs->trans("None"))
												$messag .= measuringUnitString(0, "surface", $object->surface_units);
											$messag .= ' => ';
											$messag .= $object->surface." ";
											if( $object->surface != $langs->trans("None"))
												$messag .= measuringUnitString(0, "surface", $object->surface_units);
											$messag .='</br>';
										}
										elseif($key == 'lenght' || $key == "height" || $key == 'length_units' || $key == "height_units" || $key == 'volume_units' || $key == 'volume' || $key == 'width_units' || $key == 'width'){


											$messag .= $value->oldval." ";
											if( $value->oldval != $langs->trans("None"))
												$messag .= measuringUnitString(0, "size", $object->length_units);
											$messag .= ' => ';
											$messag .= $object->length." ";
											if( $object->length != $langs->trans("None"))
												$messag .= measuringUnitString(0, "size", $object->length_units);
											$messag .='</br>';


											// $messag .= $value->oldval." ".measuringUnitString(0, "size", $object->length_units).' => '.$object->length." ".measuringUnitString(0, "size", $object->length_units).'</br>';
										}
										elseif($key == 'finished'){
											$messag .= $object->getLibFinished().'</br>';
										}
										elseif($key == 'type'){
											$messag .= ($value->oldval== 1) ? ' => '.$langs->trans("Service") : ' => '.$langs->trans("Product") .'</br>';
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
									print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).'</strong> <span class="messag">'.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
								}
							}elseif($item->action == 'product_change_pris'){
								if($item->champs){
									$champs = json_decode($item->champs);
								}
								$messag = " ".$langs->trans('changedpriceproduct')." ".$champs->prix_vente.' '.$conf->currency;
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag"> '.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'create_prix_achat'){
								if($item->modul_child){
									$champs = explode('_',$item->modul_child);
									$id_prix=$champs[1];
									$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product_fournisseur_price WHERE rowid='.$id_prix;
									$resql = $db->query($sql);
									if($resql){
										while ($obj = $db->fetch_object($resql)) {
											if($obj->fk_soc){
												$societ = new Societe($db);
												$societ->fetch($obj->fk_soc);
												$soc = $societ->getNomUrl(1);
											}
											$prix = number_format($obj->price,2,',',' ');
										}
									}
								}
								$messag = $langs->trans('add_prix_achat')." ".$soc." ".$langs->trans("for_prix")." ".$prix." ".$conf->currency;
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag"> '.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
							}elseif($item->action == 'add_stok'){
								if($item->modul_child){
									$champs = explode('_',$item->modul_child);
									$id_movt=$champs[1];
									$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product_stock WHERE rowid='.$id_movt;
									$resql = $db->query($sql);
									if($resql){
										while ($obj = $db->fetch_object($resql)) {
											if($obj->fk_entrepot){
												$warehouse = new Entrepot($db);
            									$warehouse->fetch($obj->fk_entrepot);
            									$entrepot = $warehouse->getNomUrl(1);
											}
											$prix = number_format($obj->reel,2,',',' ');
										}
									}
								}
								$messag = $langs->trans('correct_stock')." ".$prix." ".$conf->currency." ".$langs->trans("inwarhous")." ".$entrepot;
								print '<strong>'.$user_->gender.' '.$user_->getNomUrl(1).' </strong> <span class="messag"> '.$messag.' '.$langs->trans("at").' '.date('H:i',strtotime($item->date)).'</span> ';
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
