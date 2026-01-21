<?php
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$reqws=0;
if (! $reqws && file_exists("../../main.inc.php")) $reqws=@include("../../main.inc.php");       // For root directory
if (! $reqws && file_exists("../../../main.inc.php")) $reqws=@include("../../../main.inc.php"); // For "custom" 

if (empty($conf->produitlivrevendu->enabled) || !$user->rights->produitlivrevendu->lire) accessforbidden();

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


dol_include_once('/produitlivrevendu/lib/produitlivrevendu.lib.php');
dol_include_once('/produitlivrevendu/class/produitlivrevendu.class.php');

// ------------------------------------------------------------------------------------------------------------------------------------

$object = new produitlivrevendu($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$formproduct = new FormProduct($db);
$author = new User($db);
$produitstatic = new Product($db);
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($produitstatic->table_element);

// ------------------------------------------------------------------------------------------------------------------------------------

$permissiontoread = $user->rights->produitlivrevendu->lire;

// ------------------------------------------------------------------------------------------------------------------------------------

// Load translation files required by the page
$langs->loadLangs(array("sendings", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'users', 'admin', 'projects', 'products'));

$action      = GETPOST('action', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$exportpdf   = GETPOST('exportetat');
$exportxsl   = GETPOST('exportxsl');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'produitlivrevenduetat_dashboarddeliveredsold';
$optioncss   = GETPOST('optioncss', 'alpha');

$export = ($action == 'exportpdf' || $action == 'exportxsl') ? 1 : 0;

$search_options_famille = GETPOST('search_options_famille', 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_qtyrecp = GETPOST('search_qtyrecp', 'alpha');
$search_qtyvendu = GETPOST('search_qtyvendu', 'alpha');
$search_qtystock = GETPOST('search_qtystock', 'alpha');
$search_toolowstock = GETPOST('search_toolowstock', 'int');
$search_warehouse = GETPOST('search_warehouse', 'int');
$search_category = GETPOST('search_category', 'array');

$search_seuil_stock_alerte = GETPOST('search_seuil_stock_alerte', 'int');
$search_desiredstock = GETPOST('search_desiredstock', 'int');

$debutday = GETPOSTISSET('debutday') ? GETPOST('debutday', 'int') : 1;
$debutmonth = GETPOSTISSET('debutmonth') ? GETPOST('debutmonth', 'int') : 1;
$debutyear = GETPOSTISSET('debutyear') ? GETPOST('debutyear', 'int') : date('Y');

$finday = GETPOSTISSET('finday') ? GETPOST('finday', 'int') : date('d');
$finmonth = GETPOSTISSET('finmonth') ? GETPOST('finmonth', 'int') : date('m');
$finyear = GETPOSTISSET('finyear') ? GETPOST('finyear', 'int') : date('Y');

$debut = dol_mktime(0, 0, 0, $debutmonth, $debutday, $debutyear);
// if(!$finday) 
	$debutfin = dol_mktime(23, 59, 59, $debutmonth, $debutday, $debutyear);
$fin = dol_mktime(23, 59, 59, $finmonth, $finday, $finyear);

$ids_search_category = implode(',', $search_category);

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
    if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
		$sortfield = "ef.famille, o.ref";
		if (!$sortorder) {
			$sortorder = "ASC, ASC";
		}
	}else{
		$sortfield = "o.ref";
	}
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Definition of fields for list
$arrayfields = array();
if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
	$arrayfields['o.family'] = array('label'=>'Family', 'checked'=>1);
}

$arrayfields['o.ref'] = array('label'=>'RefProduct', 'checked'=>1);
$arrayfields['o.LblProduct'] = array('label'=>'LblProduct', 'checked'=>1);
$arrayfields['o.qtyrecp'] = array('label'=>'QtyRecp', 'checked'=>1);
$arrayfields['o.qtyrecus'] = array('label'=>'QtyRecus', 'checked'=>1);
$arrayfields['o.stockdate'] = array('label'=>'stockdate', 'checked'=>1);
$arrayfields['o.qtyvendu'] = array('label'=>'QtyVendu', 'checked'=>1);
$arrayfields['o.qtystock'] = array('label'=>'QtyStock', 'checked'=>1);
$arrayfields['o.seuil_stock_alerte'] = array('label'=>'StockLimit', 'checked'=>0);
$arrayfields['o.desired_stock'] = array('label'=>'DesiredStock', 'checked'=>0);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers

	$search_options_famille ="";
	$search_ref ="";
	$search_label ="";
	$search_qtyrecp ="";
	$search_qtyvendu ="";
	$search_qtystock ="";

	$debutday = "";
	$debutyear = "";
	$debutmonth = "";
	$finday = "";
	$finyear = "";
	$finmonth = "";
	$debut = "";
	$fin = "";
	$fin = "";
	$search_category = array();
	$search_warehouse = "";
	$search_toolowstock = "";
	$search_seuil_stock_alerte = "";
	$search_desiredstock = "";

	$toselect = array();
	$search_array_options = array();
}

// Mass actions
$objectclass = 'produitlivrevendu';
$objectlabel = 'produitlivrevendu';
$uploaddir = $conf->produitlivrevendu->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';


/*
 *	View
*/


$form = new Form($db);
$produitlivrevendu = new produitlivrevendu($db);
$companystatic = new Societe($db);

$now = dol_now();

$help_url = '';
$title = $langs->trans("DashboardDeliveredSold");
$morejs = array();
$morecss = array();



$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}


if ($search_options_famille && $search_options_famille != '-1') {
	$param .= "&search_options_famille=".urlencode($search_options_famille);
}
if ($search_ref) {
	$param .= "&search_ref=".urlencode($search_ref);
}
if ($search_label) {
	$param .= "&search_label=".urlencode($search_label);
}
if ($search_qtyrecp) {
	$param .= "&search_qtyrecp=".urlencode($search_qtyrecp);
}
if ($search_qtyvendu) {
	$param .= "&search_qtyvendu=".urlencode($search_qtyvendu);
}
if ($search_qtystock) {
	$param .= "&search_qtystock=".urlencode($search_qtystock);
}

if ($debutyear) {
	$param .= "&debutyear=".urlencode($debutyear);
}
if ($debutmonth) {
	$param .= "&debutmonth=".urlencode($debutmonth);
}
if ($finday) {
	$param .= "&finday=".urlencode($finday);
}
if ($finyear) {
	$param .= "&finyear=".urlencode($finyear);
}
if ($finmonth) {
	$param .= "&finmonth=".urlencode($finmonth);
}
if ($debut) {
	$param .= "&debut=".urlencode($debut);
}
if ($fin) {
	$param .= "&fin=".urlencode($fin);
}
if ($search_desiredstock) {
	$param .= "&search_desiredstock=".urlencode($search_desiredstock);
}
if ($search_seuil_stock_alerte) {
	$param .= "&search_seuil_stock_alerte=".urlencode($search_seuil_stock_alerte);
}
if ($search_category) {
    $param .= '&' . http_build_query(['search_category' => $search_category]);
}
if ($search_warehouse) {
	$param .= "&search_warehouse=".urlencode($search_warehouse);
}
if ($search_toolowstock) {
	$param .= "&search_toolowstock=".urlencode($search_toolowstock);
}


if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

// d('Commande: PO2501-0004');

// $ssql = 'SELECT l.*, r.* FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch as l LEFT JOIN '.MAIN_DB_PREFIX.'reception as r ON r.rowid=l.fk_reception WHERE l.fk_commande=23';
// 	echo $sql;
// $res = $db->query($ssql);
// if($res){
// 	while ($obj = $db->fetch_object($res)) {
// 		d($obj, 0);
// 	}
// }else{
// 	d('Error: '.$db->lasterror(), 0);
// }

// d('Commande: PO2501-0003');
// $ssql = 'SELECT l.*, r.* FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch as l LEFT JOIN '.MAIN_DB_PREFIX.'reception as r ON r.rowid=l.fk_reception WHERE l.fk_commande=11';
// 	echo $sql;
// $res = $db->query($ssql);
// if($res){
// 	while ($obj = $db->fetch_object($res)) {
// 		d($obj, 0);
// 	}
// }else{
// 	d('Error: '.$db->lasterror(), 0);
// }

$sql = "SELECT ";
$sql .= " o.rowid as idprod, o.ref as ref_product, o.label, o.fk_product_type as ptype, o.entity as pentity, o.description, o.seuil_stock_alerte, o.desiredstock";

if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
	$sql .= ", ef.famille";
}

$sql .= ', v.qtyvendu';
$sql .= ', r.qtyrecp';
$sql .= ', s.qtystock';
// $sql .= ', sd.stockdate';

if ($debut) {
	$sql .= ', stap.qtystock as qtystockapresdebut';
	$sql .= ', stav.qtystock as qtystockavantdebut';
	$sql .= ', (sr.qtystockreel-stap.qtystock) as stockreelavantdebut';
}else{
	$sql .= ", NULL as qtybefore";
}

if($fin){
	$sql .= ', se.qtystock as qtyafter';
	$sql .= ', (sr.qtystockreel-se.qtystock) as stockreelapresfin';
}else{
	$sql .= ", NULL as qtyafter";
}

$sql .= ', sr.qtystockreel';

$sql .= " FROM  ".MAIN_DB_PREFIX."product as o";
$sql .= " LEFT JOIN  ".MAIN_DB_PREFIX."product_extrafields as ef ON ef.fk_object=o.rowid";

$sql .= ' LEFT JOIN (
	SELECT fk_product, SUM('.$db->ifsql('reel IS NULL', '0', 'reel').') as qtystockreel FROM '.MAIN_DB_PREFIX.'product_stock as st
	'.($search_warehouse>0 ? ' WHERE fk_entrepot='.$search_warehouse : '').'
	GROUP BY fk_product
) as sr ON sr.fk_product=o.rowid';

if($debut){
	$sql .= " LEFT JOIN (
		SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as qtystock FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
		WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
		".($debut>0 ? " AND CAST(mv.datem as date) > '".$db->idate($debutfin)."' " : "")."
		GROUP BY mv.fk_product
	) as stap ON stap.fk_product=o.rowid";
	
	$sql .= " LEFT JOIN (
		SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as qtystock FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
		WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
		".($debut>0 ? " AND CAST(mv.datem as date) < '".$db->idate($debut)."' " : "")."
		GROUP BY mv.fk_product
	) as stav ON stav.fk_product=o.rowid";

}

if($fin){
	$sql .= " LEFT JOIN (
		SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as qtystock FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
		WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
		AND CAST(mv.datem as date) > '".$db->idate($fin)."'
		GROUP BY mv.fk_product
	) as se ON se.fk_product=o.rowid";
}


// elseif (($debut && !$fin) || (!$debut && $fin)) {
// 	$sql .= " LEFT JOIN (
// 		SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as qtystock FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
// 		WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
// 		".($debut>0 ? " AND CAST(mv.datem as date) >= '".$db->idate($debut)."' " : "")."
// 		".($fin>0 ? " AND CAST(mv.datem as date) >= '".$db->idate($fin)."' " : "")."
// 		GROUP BY mv.fk_product
// 	) as ss ON ss.fk_product=o.rowid";
// }

// $sql .= " LEFT JOIN (
// 		SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as stockdate FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
// 		WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
// 		".($debutfin>0 ? " AND CAST(mv.datem as date) >= '".$db->idate($debutfin)."' " : "")."
// 		GROUP BY mv.fk_product
// 	) as sd ON sd.fk_product=o.rowid";

$sql .= " LEFT JOIN (
	SELECT mv.fk_product, SUM(".$db->ifsql('mv.value IS NULL', '0', 'mv.value').") as qtystock FROM ".MAIN_DB_PREFIX."stock_mouvement as mv 
	WHERE ".($search_warehouse>0 ? " mv.fk_entrepot=".$search_warehouse : '1>0')."
	GROUP BY mv.fk_product
) as s ON s.fk_product=o.rowid";
// ".($debut>0 ? " AND CAST(mv.datem as date) >= '".$db->idate($debut)."' " : "")."
// ".($fin>0 ? " AND CAST(mv.datem as date) <= '".$db->idate($fin)."' " : "")."

$table_name = ((floatval(DOL_VERSION) < 20) ? 'commande_fournisseur_dispatch' : 'receptiondet_batch');

$sql .= " LEFT JOIN (
	SELECT l.fk_product, SUM(l.qty) as qtyrecp FROM ".MAIN_DB_PREFIX.$table_name." as l 
	LEFT JOIN ".MAIN_DB_PREFIX."reception as rec ON rec.rowid=l.fk_reception
	WHERE l.fk_product>0 
	".($debut>0 ? " AND CAST(rec.date_valid as date) >= '".$db->idate($debut)."' " : "")."
	".($fin>0 ? " AND CAST(rec.date_valid as date) <= '".$db->idate($fin)."' " : "")."
	GROUP BY l.fk_product
) as r ON r.fk_product=o.rowid ";

$sql .= " LEFT JOIN (
	SELECT d.fk_product, SUM(d.qty) as qtyvendu FROM ".MAIN_DB_PREFIX."facturedet as d 
	LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid=d.fk_facture
	WHERE d.fk_product>0
	".($debut>0 ? " AND CAST(f.datef as date) >= '".$db->idate($debut)."' " : "")."
	".($fin>0 ? " AND CAST(f.datef as date) <= '".$db->idate($fin)."' " : "")."
	GROUP BY d.fk_product

) as v ON v.fk_product = o.rowid";

$sql .= " WHERE o.entity IN (".getEntity('product').") AND o.fk_product_type=".Product::TYPE_PRODUCT;

// $sql .= " AND (r.qtyrecp>0 OR v.qtyvendu>0)"; 

// if ($debut) $sql .= " AND CAST(o.datec as date) >= '".$db->idate($debut)."'";
// if ($fin) $sql .= " AND CAST(o.datec as date) <= '".$db->idate($fin)."'";

if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
	$sql .= (!empty($search_options_famille) && $search_options_famille != '-1') ? ' AND ef.famille LIKE "%'.$search_options_famille.'%"' : '';
}

if ($search_ref) $sql .= natural_search("o.ref", $search_ref);
if ($search_label) $sql .= natural_search("o.label", $search_label);

$sql .= !empty($search_desiredstock) ? ' AND o.desiredstock='.$search_desiredstock : '';
$sql .= !empty($search_seuil_stock_alerte) ? ' AND o.seuil_stock_alerte='.$search_seuil_stock_alerte : '';
$sql .= !empty($search_qtyrecp) ? ' AND r.qtyrecp='.$search_qtyrecp : '';
$sql .= !empty($search_qtyvendu) ? ' AND v.qtyvendu='.$search_qtyvendu : '';
$sql .= !empty($search_qtystock) ? ' AND s.qtystock='.$search_qtystock : '';

$sql .= ($search_warehouse>0) ? ' AND o.rowid IN (SELECT fk_product FROM '.MAIN_DB_PREFIX.'product_stock WHERE fk_entrepot='.$search_warehouse.')' : '';
$sql .= !empty($search_category) ? ' AND o.rowid IN (SELECT fk_product FROM '.MAIN_DB_PREFIX.'categorie_product WHERE fk_categorie IN ('.$ids_search_category.'))' : '';

$sql .= " GROUP BY o.rowid";
// die();
// Count total nb of records
$nbtotalofrecords = '';
$result = $db->query($sql);
$nbtotalofrecords = $db->num_rows($result);
if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
	$page = 0;
	$offset = 0;
}
if ($search_toolowstock) {
	$sql .= " HAVING s.qtystock < o.seuil_stock_alerte";
}
// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);

if ($limit && !$export) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}
$num = $db->num_rows($resql);
$pu_transport = $object->pu_transport ? $object->pu_transport : $object->pu_fraistransp;


########################################################################################################################################### Data html
$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields


$htmletat  = '';
$htmletat .= '<table class="tagtable nobottomiftotal liste listwithfilterbefore" '.($export ? ' border="1px" cellpadding="5px" cellspacing="0"' : '').'>'."\n";
	$htmletat .= '<thead class="firsthead">';
		// Fields title search
		// --------------------------------------------------------------------
		// Action column
		if(!$export){
			$htmletat .= '<tr class="liste_titre_filter">';
            	if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
					if (!empty($arrayfields['o.family']['checked'])) {

						$htmletat .= '<td class="liste_titre left">';
							// $htmletat .= '<input type="text" class="flat" name="search_options_famille" value="'.$search_options_famille.'" size="8">';
						$htmletat .= '</td>';
					}
				}
				if (!empty($arrayfields['o.ref']['checked'])) {
					$htmletat .= '<td class="liste_titre left">';
						$htmletat .= '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.LblProduct']['checked'])) {
					$htmletat .= '<td class="liste_titre left" >';
						$htmletat .= '<input type="text" class="flat" name="search_label" value="'.$search_label.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.qtyrecp']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_qtyrecp" value="'.$search_qtyrecp.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.qtyrecus']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_qtyrecus" value="'.$search_qtyrecus.'" size="8">';
					$htmletat .= '</td>';
				}
				// if (!empty($arrayfields['o.stockdate']['checked'])) {
				// 	$htmletat .= '<td class="liste_titre right">';
				// 		// $htmletat .= '<input type="text" class="flat" name="search_qtyrecus" value="'.$search_qtyrecus.'" size="8">';
				// 	$htmletat .= '</td>';
				// }
				if (!empty($arrayfields['o.qtyvendu']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_qtyvendu" value="'.$search_qtyvendu.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.qtystock']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_qtystock" value="'.$search_qtystock.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.seuil_stock_alerte']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_seuil_stock_alerte" value="'.$search_seuil_stock_alerte.'" size="8">';
					$htmletat .= '</td>';
				}
				if (!empty($arrayfields['o.desired_stock']['checked'])) {
					$htmletat .= '<td class="liste_titre right">';
						$htmletat .= '<input type="text" class="flat" name="search_desiredstock" value="'.$search_desiredstock.'" size="8">';
					$htmletat .= '</td>';
				}
				
				// Action column
				if (!$export) {
					$htmletat .= '<td class="liste_titre maxwidthsearch">';
					$searchpicto = $form->showFilterButtons();
					$htmletat .= $searchpicto;
					$htmletat .= '</td>';
				}
			$htmletat .= '</tr>'."\n";
		}


		$totalarray = array();
		$totalarray['nbfield'] = 0;
		$colsptotal = 0;
		// Fields title label
		// --------------------------------------------------------------------
		$htmletat .= '<tr class="liste_titre">';
        	if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
				if (!empty($arrayfields['o.family']['checked'])) {
					$colsptotal++;
					$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.family']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "ef.famille"), "", $param, 'align="left"', $sortfield, $sortorder);
				}
			}
			if (!empty($arrayfields['o.ref']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.ref']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "o.ref"), "", $param, 'align="left"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.LblProduct']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.LblProduct']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "o.label"), "", $param, 'align="left"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.qtyrecp']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.qtyrecp']['label'], $_SERVER["PHP_SELF"], ($export ? '' : 'r.qtyrecp'), '', $param, 'align="right"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.qtyrecus']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.qtyrecus']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'align="right"', $sortfield, $sortorder);
			}
			// if (!empty($arrayfields['o.stockdate']['checked'])) {
			// 	$colsptotal++;
			// 	$htmletat .= produitlivrevendu_print_liste_field_titre($langs->trans('stockle', dol_print_date($debut, 'day')), $_SERVER["PHP_SELF"], ($export ? '' : ''), '', $param, 'align="right"', $sortfield, $sortorder);
			// }
			if (!empty($arrayfields['o.qtyvendu']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.qtyvendu']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "v.qtyvendu"), '', $param, 'align="right"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.qtystock']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.qtystock']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "s.qtystock"), '', $param, 'align="right"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.seuil_stock_alerte']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.seuil_stock_alerte']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "o.seuil_stock_alerte"), '', $param, 'align="right"', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['o.desired_stock']['checked'])) {
				$colsptotal++;
				$htmletat .= produitlivrevendu_print_liste_field_titre($arrayfields['o.desired_stock']['label'], $_SERVER["PHP_SELF"], ($export ? '' : "o.desiredstock"), '', $param, 'align="right"', $sortfield, $sortorder);
			}
			
			if($export)
				$htmletat .= produitlivrevendu_print_liste_field_titre($langs->trans('Magasin'), $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
			else
				$htmletat .= produitlivrevendu_print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');

		$htmletat .= "</tr>\n";
	$htmletat .= "</thead>";

	// Loop on record
	// --------------------------------------------------------------------
	$i = 0;
	$total = 0;
	$totalarray = array();

	$totalqty=0;
	$totalprice = 0;
	$totalavance = 0; 
	$lastdate = '';
	
	$totalarray['nbfield'] = 0;
	$totalarray['val'] = array();
	$totalarray['val']['o.qty'] = 0;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);
		if (empty($obj)) {
			break; // Should not happen
		}


		$produitstatic->id = $obj->idprod;
		$produitstatic->ref = $obj->ref_product;
		$produitstatic->description = $obj->description;
		$produitstatic->label = $obj->label;


		$htmletat .= '<tr class="oddeven">';
        	if($extrafields->attributes[$produitstatic->table_element]['label'] && isset($extrafields->attributes[$produitstatic->table_element]['label']['famille'])){
				if (!empty($arrayfields['o.family']['checked'])) {
					$htmletat .= '<td align="left">';
						$htmletat .= $obj->famille;
					$htmletat .= '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}
			if (!empty($arrayfields['o.ref']['checked'])) {
				$htmletat .= '<td align="left" class="nowraponall">';
					if(!empty(!$export)){
						$htmletat .= $produitstatic->getNomUrl(1);
					}else{
						$htmletat .= $obj->ref_product;
					}
				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			if (!empty($arrayfields['o.LblProduct']['checked'])) {
				$htmletat .= '<td align="left">';
					$htmletat .= $obj->label;
				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			if (!empty($arrayfields['o.qtyrecp']['checked'])) {
				$htmletat .= '<td align="right">';
					$htmletat .= $obj->qtyrecp ? price2num($obj->qtyrecp) : '0';
				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			$stockadate = $obj->stockreelavantdebut;
			// $stockadate = $obj->qtystockreel-$obj->stockdate;

			if (!empty($arrayfields['o.qtyrecus']['checked'])) {
				$htmletat .= '<td align="right">';
					$qtyrecus = ($obj->qtyrecp+$stockadate);
					$htmletat .= $qtyrecus ? price2num($qtyrecus) : '0';
					// $htmletat .= ('<br>stockadate: '.$stockadate);

					// $htmletat .= '<br>qtystock: '.$obj->qtystock;
					
					// $htmletat .= '<br>qtystockreel: '.$obj->qtystockreel;

					// $htmletat .= '<br>stockreelavantdebut: '.$obj->stockreelavantdebut;
					// $htmletat .= '<br>stockreelapresfin: '.$obj->stockreelapresfin;
					// $htmletat .= '<br>qtystockavantdebut: '.$obj->qtystockavantdebut;
					// $htmletat .= '<br>qtystockapresdebut: '.$obj->qtystockapresdebut;

				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// if (!empty($arrayfields['o.stockdate']['checked'])) {
			// 	$htmletat .= '<td align="right">';
			// 		$htmletat .= $stockadate ? price2num($stockadate) : '0';
			// 	$htmletat .= '</td>';
			// 	if (!$i) {
			// 		$totalarray['nbfield']++;
			// 	}
			// }
			if (!empty($arrayfields['o.qtyvendu']['checked'])) {
				$htmletat .= '<td align="right">';
					$htmletat .= $obj->qtyvendu ? price2num($obj->qtyvendu) : '0';
				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			if (!empty($arrayfields['o.qtystock']['checked'])) {
				$htmletat .= '<td align="right">';
					// if($debut && $fin){
					// 	$qtys = $obj->qtystock - ($obj->qtybefore+$obj->qtyafter);
					// }
					// if(($debut && !$fin) || (!$debut && $fin)){
					// 	$qtys = $obj->qtystock - $obj->qtybefore;
					// }
					// // $htmletat .= '<br>Qté : '.($qtys ? price2num($qtys) : '0');

					// $qtys = $obj->qtystockreel - $qtys;
					$qtys = $obj->qtystockreel - ($obj->stockreelapresfin);
					if($qtys<0 || (!empty($qtys) && $qtys < $obj->seuil_stock_alerte)){
						$htmletat .= '<span class="warning">';
					}
					// $htmletat .= $obj->qtystock ? price2num($obj->qtystock) : '0';
					// $htmletat .= '<br>Qté Stock Reel: '.($obj->qtystockreel ? price2num($obj->qtystockreel) : '0');
					// $htmletat .= '<br>Qté avant date: '.($obj->qtybefore ? price2num($obj->qtybefore) : '0');
					// $htmletat .= '<br>Qté après date: '.($obj->qtyafter ? price2num($obj->qtyafter) : '0');


					// $htmletat .= '<br>Qté : '.($qtys ? price2num($qtys+$obj->qtystock) : '0');
					
					$htmletat .= $qtys ? price2num($qtys) : '0';

					if($qtys<0 || (!empty($qtys) && $qtys < $obj->seuil_stock_alerte)){
						$htmletat .= img_warning($langs->trans("StockLowerThanLimit", $obj->seuil_stock_alerte)).' ';
						$htmletat .= '</span>';
					}
				$htmletat .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			if (!empty($arrayfields['o.seuil_stock_alerte']['checked'])) {
				$htmletat .= '<td align="right">';
					$htmletat .= $obj->seuil_stock_alerte ? price2num($obj->seuil_stock_alerte) : '0';
				$htmletat .= '</td>';
			}
			if (!empty($arrayfields['o.desired_stock']['checked'])) {
				$htmletat .= '<td align="right">';
					$htmletat .= $obj->desiredstock ? price2num($obj->desiredstock) : '0';
				$htmletat .= '</td>';
			}
			
			$htmletat .= '<td></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		$htmletat .= '</tr>'."\n";

		$i++;
	}


	// Show total line
	// include dol_buildpath('/produitlivrevendu/tpl/core/custom_list_print_total.tpl.php');
	// $htmletat .= $totalhtml;

	// If no record found
	if ($num == 0) {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		$htmletat .= '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	$db->free($resql);

$htmletat .= '</table>'."\n";



########################################################################################################################################### Exporter PDF 
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
if ($action == 'exportpdf') {
    global $langs,$mysoc;
    require_once dol_buildpath('/produitlivrevendu/pdf/pdf.lib.php');

    $pdf->SetFont('times', '', 9, '', true);
    $pdf->AddPage('L');
    $array_format = pdf_getFormat();

    $marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:5;
    $marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:5;
    $margin = $marge_haute+$marge_basse+45;

    $page_largeur = $array_format['width'];
    $page_hauteur = $array_format['height'];
    $format = array($page_largeur,$page_hauteur);

    $marge_gauche=5;
    $marge_droite=5;
    $marge_haute =5;
    $marge_basse =5;
    $emetteur = $mysoc;



    $default_font_size = pdf_getPDFFontSize($langs);

    pdf_pagehead($pdf,$langs,$page_hauteur);


    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('helvetica','B', $default_font_size-2);

    $posy=$marge_haute;
    $posx=$page_largeur-$marge_droite-100;

    $pdf->SetXY($marge_gauche,$posy);
    $height=2;

    // Header
    // Logo
    $logo=$conf->mycompany->dir_output.'/logos/'.$emetteur->logo;

    if ($emetteur->logo)
    {
        if (is_readable($logo))
        {
            $height=pdf_getHeightForLogo($logo);
            $pdf->Image($logo, $marge_gauche, $posy, 0, $height); // width=0 (auto)
        }
        else
        {
            $pdf->SetTextColor(200,0,0);
            $pdf->SetFont('helvetica','B', $default_font_size -2);
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
        }
    }
    else
    {
        $text=$emetteur->name;
        $pdf->MultiCell(40, 2, $langs->convToOutputCharset($text), 0, 'L');
    }
    
    $posy = $pdf->GetY();
    $posxr = $page_largeur+30;

    $pdf->SetFont('helvetica','', $default_font_size-1);
    $pdf->SetXY($posxr,$posy);
    $pdf->writeHTMLCell(30, 2, $posxr, $posy, '<b>'.$langs->trans('Date').'</b>: '.dol_print_date(dol_now(), 'day'), 0, 2, 0, true, 'L', true);

    $posyaddress = (int)$posy + (int)$height;
    
    // $pdf->SetXY($marge_gauche, $posyaddress+2);
    // $pdf->MultiCell(40, 2, $langs->convToOutputCharset($emetteur->address), 0, 'L');

    // $pdf->SetXY($marge_gauche+60, $posy+2);
    // $pdf->MultiCell(160, 2, $langs->convToOutputCharset($emetteur->address), 0, 'L');

    // $posyhtml = $pdf->GetY();
    // $heightrect = $posyhtml - $posy;
	// $pdf->SetDrawColor(192, 192, 192);

	// $pdf->Rect($marge_gauche+58, $posy+1, 162, $heightrect);

    $pdf->SetXY($marge_gauche, $posyaddress+2);
    $pdf->MultiCell(40, 2, $langs->convToOutputCharset($emetteur->address), 0, 'L');

    $posyhtml = $pdf->GetY();

    $pdf->SetFont('helvetica','', $default_font_size);
    $pdf->SetTextColor(0,0,0);
    
    // $pdf->MultiCell(100, 3, $langs->trans('produitlivrevendu').' '.$object->num, '', 'C');
    $posx3 = (($page_largeur-$marge_droite-$marge_gauche)/3);

    $posy = $pdf->GetY();

    $posy = ($posyaddress>$posy) ? $posyaddress : $posy;

    
    // Show sender
    $posy=$pdf->GetY();
    $posy = ($posyhtml>$posy) ? $posyhtml : $posy;

    $posx=$marge_gauche;
    // Show sender frame
    $pdf->SetFont('helvetica','', $default_font_size - 3);
    $pdf->SetXY($posx,$posy+5);
    
    $pdf->SetFillColor(230,230,230);
    $pdf->SetTextColor(0,0,60);
    
    $html = '';
    // Body
    // require template
    require_once dol_buildpath('/produitlivrevendu/tpl/pdf_produitlivrevendu.tpl.php');
    $pdf->writeHTML($html, true, false, true, false, '');
    
    if($pdf->getPage() == $nb || $pdf->getNumPages() <= 1){
        $pdf->SetFooterMargin(50);
        $pdf->setPrintFooter(true);
        $pdf->SetAutoPageBreak(TRUE,50);
    }
    ob_start();
    $pdf->Output($langs->trans('DashboardDeliveredSold').'.pdf', 'I');
    // ob_end_clean();
    die();
}

/*-------------excel-----------------*/
if ($action == 'exportxsl') {
    $html = '';
	$filename="Produit livre vendu.xls";
    require_once dol_buildpath('/produitlivrevendu/tpl/pdf_produitlivrevendu.tpl.php');
	header("Content-Type: application/xls; charset=UTF-8");
	header("Content-Disposition: attachment; filename=".$filename);

	echo $html;
	die(); 
}

$morejs = array('produitlivrevendu/js/script.js.php');
$morecss = array('produitlivrevendu/css/style.css');

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');


$arrayofselected = is_array($toselect) ? $toselect : array();



// List of mass actions available
$arrayofmassactions = array();
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// print '<div class="error">En cours de traitement ...</div>';
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$newcardbutton = '';
	$morehtmlrightbeforearrow = '';

	$morehtmlrightbeforearrow .= '<a href="'.$_SERVER['PHP_SELF'].'?action=exportpdf'.$param.'" target="_blank" class="butAction badge badge-status4" value="1">'.img_picto('', 'fa-file-pdf', 'valignmiddle pictotitle widthpictotitle').' '.$langs->trans('Pdf').'</a>';
	$morehtmlrightbeforearrow .= ' <a href="'.$_SERVER['PHP_SELF'].'?action=exportxsl'.$param.'" class="butAction badge badge-status2" value="1">'.img_picto('', 'fa-file-excel', 'valignmiddle pictotitle widthpictotitle').' '.$langs->trans('Excel').'</a>'.'&nbsp&nbsp';

	// $morehtmlrightbeforearrow .= '<button type="submit" name="exportetat" formtarget="_blank" class="butAction badge badge-status4" value="1">'.img_picto('', 'fa-file-pdf', 'valignmiddle pictotitle widthpictotitle').' '.$langs->trans('Pdf').'</button>';
	// $morehtmlrightbeforearrow .= ' <button type="submit" name="exportxsl" class="butAction badge badge-status2" value="1">'.img_picto('', 'fa-file-excel', 'valignmiddle pictotitle widthpictotitle').' '.$langs->trans('Excel').'</button>'.'&nbsp&nbsp';


	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1, $morehtmlrightbeforearrow);

	produitlivrevendu_periode($debut, $fin, $search_options_famille);

	print '<div class="liste_titre liste_titre_bydiv centpercent">';

		if (isModEnabled('categorie') && $user->rights->categorie->lire) {
			print '<div class="divsearchfield">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
                print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('search_category', $cate_arbo, $search_category, 0, 0, 'minwidth300');
			print '</div>';
		}

		print '<div class="divsearchfield">';
			print img_picto('', 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</div>';

		print '<div class="divsearchfield">';
			print '<label for="search_toolowstock">'.$langs->trans("StockTooLow").' </label><input type="checkbox" id="search_toolowstock" name="search_toolowstock" value="1"'.($search_toolowstock ? ' checked' : '').'>';
		print '</div>';

	print '</div>';

    print '<div class="scrollpropriete">';
		print $htmletat;
    print '</div>';	
print '</form>'."\n";

// End of page
llxFooter();
$db->close();
