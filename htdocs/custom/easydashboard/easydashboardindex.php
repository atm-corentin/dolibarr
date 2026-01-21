<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2019      florian Dufourg	<florian.dufourg@outlook.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       easydashboard/easydashboardindex.php
 *	\ingroup    easydashboard
 *	\brief      Main page of easydashboard
 */

 //print '<pre>'; print_r($conf); print '</pre>';exit();

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

// NO WARNING
error_reporting(E_ERROR | E_PARSE);

$dateselect=dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'int'), GETPOST('dateselectday', 'int'), GETPOST('dateselectyear', 'int'));
$datestart=dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
$dateend=dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));


// Security check 1
if(!$user->rights->easydashboard->read) accessforbidden();

// CHECK IF SQL MODE have ONLY_FULL_GROUP_BY
$resql = $db->query("SELECT @@sql_mode as sqlmode;");
$sqlMode = $db->fetch_object($resql);


if(strpos($sqlMode->sqlmode,"ONLY_FULL_GROUP_BY") !== false){
	//print "here";

	$title=$langs->trans("ModuleEasyDashboardName");

	llxHeader('', $title, '', '',0, 0, '', '', '', 'sidebar-collapse', '');

	print 'To be able to use easydashboard, please disable "ONLY_FULL_GROUP_BY" from MySql sql_mode';
	print "<br/><br/>";
	print '<a href="https://stackoverflow.com/questions/23921117/disable-only-full-group-by">https://stackoverflow.com/questions/23921117/disable-only-full-group-by</a>';

	exit;
}



/*
 * Actions
 */
if (GETPOST('datestartday', 'int')) $param.='&datestartday='.GETPOST('datestartday', 'int');
if (GETPOST('datestartmonth', 'int')) $param.='&datestartmonth='.GETPOST('datestartmonth', 'int');
if (GETPOST('datestartyear', 'int')) $param.='&datestartyear='.GETPOST('datestartyear', 'int');
if (GETPOST('dateendday', 'int')) $param.='&dateendday='.GETPOST('dateendday', 'int');
if (GETPOST('dateendmonth', 'int')) $param.='&dateendmonth='.GETPOST('dateendmonth', 'int');
if (GETPOST('dateendyear', 'int')) $param.='&dateendyear='.GETPOST('dateendyear', 'int');



/*
	TABLEAU DES DATES -> PERIODE
*/

$i = 0;
$now = dol_now();
if(!empty($conf->global->SOCIETE_FISCAL_MONTH_START)){
	$monthStart = $conf->global->SOCIETE_FISCAL_MONTH_START;
	
	if($monthStart==1){
		$yearStart = date("Y");
		
		$monthEnd = 12;
		$yearEnd = date("Y");
	}else{
		if(date("m")<$monthStart){
			$yearStart = date("Y")-1;
			$yearEnd = date("Y");
		}else{
			$yearStart = date("Y");
			$yearEnd = date("Y")+1;
		}
		$monthEnd = $monthStart-1;
	}
	
}else{
	$monthStart = 1;
	$yearStart = date("Y");
	
	$monthEnd = 12;
	$yearEnd = date("Y");
	
}


//If button show was clicked
if($datestart>0){
	$tmstpStart = $datestart;
}else{
	
	$tmstpStart = dol_get_first_day($yearStart,$monthStart);
	//$tmstpStart = mktime(0, 0, 0, $monthstart, 1, $yearStart );
}

if($dateend>0){
	$tmstpEnd = $dateend+3600*24-1;
	$tmstpEndToShow = $dateend;
}
else{
	$tmstpEnd = dol_get_last_day($yearEnd, $monthEnd);
	
	//$tmstpEnd = mktime(23, 59, 59, $monthEnd, 31, $yearEnd );
	$tmstpEndToShow = $tmstpEnd - 3600*24+1;
}

//See last 12 months
if (!empty($_POST["last12"])) {
	$currentMonth = intval(date("m"));
	$currentYear = date("Y");

	if($currentMonth == 1){
		$lastMonth = 12;
		$startYear = $currentYear-1;
		$endYear = $currentYear-1;
	}else{
		$lastMonth = $currentMonth - 1;
		$startYear = $currentYear-1;
		$endYear = $currentYear;
	}

	//print '<pre>'; print_r($endYear); print '</pre>';exit();

	$tmstpStart = dol_get_first_day($startYear ,$currentMonth);

	$tmstpEnd = dol_get_last_day($endYear, $lastMonth);

	$tmstpEndToShow = $tmstpEnd - 3600*24+1;
}

$datestartfiltre=$db->idate($tmstpStart);
$dateendfiltre=$db->idate($tmstpEnd);
$dateendToShow=$db->idate($tmstpEndToShow);


$period = array();

for ($i = $tmstpStart; $i <= $tmstpEnd; $i++)
{
	$period[] = date('m',$i).'/'.date('Y',$i);
	$i = strtotime ( '+1 month' , $i);
}

$NbPeriod = count($period);

//SGestion des paramètres
($conf->global->EASYDASHBOARD_IDPROJET_COUTFIXE)?$idProjetCF = trim($conf->global->EASYDASHBOARD_IDPROJET_COUTFIXE):$idProjetCF = 0;

($conf->global->EASYDASHBOARD_OPPORTUNITIES_TO_SHOW_PERCENT)?$percentOpportunityToShow = trim($conf->global->EASYDASHBOARD_OPPORTUNITIES_TO_SHOW_PERCENT):$percentOpportunityToShow = 60;

($conf->global->EASYDASHBOARD_GRAPH_NB_MAX_CF)?$graphMaxCF = trim($conf->global->EASYDASHBOARD_GRAPH_NB_MAX_CF):$graphMaxCF = '';

($conf->global->EASYDASHBOARD_GRAPH_NB_MAX_CA)?$graphMaxTurnover = trim($conf->global->EASYDASHBOARD_GRAPH_NB_MAX_CA):$graphMaxTurnover = '';

($conf->global->EASYDASHBOARD_ROUND_NUMBERS)?$constRoundNumber = $conf->global->EASYDASHBOARD_ROUND_NUMBERS:$constRoundNumber = 0;

($conf->global->MAIN_MONNAIE)?$constMainMonnaie = $conf->global->MAIN_MONNAIE:$constMainMonnaie = ' ';

$advancedDisplay = $conf->global->EASYDASHBOARD_USE_CFCV;

$chargeSocialFilter = explode(",",str_replace(" ","",$conf->global->EASYDASHBOARD_CHARGES_EXCLUDES));

$defautPeriodContract = 12;//Contrats annuels


/*
	FONCTION REQUETE WHERE SUIVANT TYPE DE COUT
*/

function whereProject($idProjetCF, $fk_projetField,$type_couts){

	//if(empty($idProjetCF)) print "error ".$idProjetCF.' - '.$fk_projetField;

	global $conf;


	if($type_couts == 'fixe'){
		if (! empty($conf->global->EASYDASHBOARD_EMPTYPROJECT_ISVARIABLECOST)){
			return ' AND ('.$fk_projetField.' IN ('.$idProjetCF.') AND '.$fk_projetField.' IS NOT NULL)';
		}else{	
			return ' AND ('.$fk_projetField.' IN ('.$idProjetCF.') OR '.$fk_projetField.' IS NULL)';
		}
	}elseif($type_couts == 'variable'){
		if (! empty($conf->global->EASYDASHBOARD_EMPTYPROJECT_ISVARIABLECOST)){
			return ' AND ('.$fk_projetField.' NOT IN ('.$idProjetCF.') OR '.$fk_projetField.' IS NULL)';
		}else{	
			return ' AND ('.$fk_projetField.' NOT IN ('.$idProjetCF.') AND '.$fk_projetField.' IS NOT NULL)';
		}
	}
}


/*
	FONCTION SQL QUERY
*/

function sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit=''){
	
	global $conf,$db;
	
	global $datestartfiltre,$dateendfiltre;
	
	if($groupBy == 'month'){
		$sql = 'SELECT DATE_FORMAT('.$dateName.', "%m/%Y") AS groupLabel';
	}else{
		$sql = 'SELECT '.$groupBy.' AS groupLabel';
	}
	
	foreach($tabData['sum'] as $valData){
		
		$asLabel = str_replace(".", "_", $valData);
		
		$sql .= ', SUM('.$valData.') AS '.$asLabel;
	}
	
	if(!empty($tabData['line'])){
		foreach($tabData['line'] as $valData){
			
			$asLabel = str_replace(".", "_", $valData);
			
			$sql .= ', '.$valData.' AS '.$asLabel;
		}
	}
	
	
	
	$sql.= " FROM ".$from;
	$sql.= " WHERE ".$where;
	$sql.= " GROUP BY groupLabel";
	
	if(!empty($orderBy))$sql.= " ORDER BY ".$orderBy;
	
	
	if(!empty($limit)) $sql.= " LIMIT ".$limit;
	
	//print $sql.'<br>';


	
	$resql = $db->query($sql);
	
	if (!$resql)
	{
		dol_print_error($db);
		return $db->lasterror();
	}
	
	$num = $db->num_rows($resql);

	$result = array();

	$result["sqlquery"] = $sql;
	
	if(is_array($listOfLabel)){
		foreach($listOfLabel as $valLabel){
			foreach($tabData['sum'] as $val){
				
				$asLabel = str_replace(".", "_", $val);
				
				$result[$asLabel][$valLabel] = null;
				$result['cumul_'.$asLabel][$valLabel] = null;
			}
		}
	}


	for($i = 0; $i<$num; $i++){
		$dataTemp = $db->fetch_object($resql);
		
		if(!empty($tabData['sum'])){
			foreach($tabData['sum'] as $val){
				
				$asLabel = str_replace(".", "_", $val);
				
				$result['sum_'.$asLabel] += $dataTemp->$asLabel;
				
				$result[$asLabel][$dataTemp->groupLabel] = round($dataTemp->$asLabel,0);

				$result['cumul_'.$asLabel][$dataTemp->groupLabel] += round($result['sum_'.$asLabel],0);
			}
		}
		
		if(!empty($tabData['line'])){
			foreach($tabData['line'] as $val){
				
				$asLabel = str_replace(".", "_", $val);
				
				$result[$asLabel][$dataTemp->groupLabel] = $dataTemp->$asLabel;
			}
		}
	}
	
	if(is_array($listOfLabel)){

		foreach($listOfLabel as $valLabel){
			foreach($tabData['sum'] as $val){
				
				$asLabel = str_replace(".", "_", $val);
				
				$mois = intval(substr($valLabel, 0, 2));
				$annee = intval(substr($valLabel, 3, 8));
				
				if($result['cumul_'.$asLabel][$valLabel] == null && mktime(0, 0, 0, $mois, 1, $annee) < dol_now()){
					$result['cumul_'.$asLabel][$valLabel] = $lastResult['cumul_'.$asLabel];
				}else{
					$lastResult['cumul_'.$asLabel] = $result['cumul_'.$asLabel][$valLabel];
				}
			}
			
			
			
		}
	}

	return $result;
}




/*
	TABLEAU COMMANDES EN COURS
*/

$sql = 'SELECT c.rowid, c.ref, c.ref_client, c.total_ht,SUM(cd.buy_price_ht*cd.qty) AS sum_buy_price, s.nom, DATE_FORMAT(c.date_commande, "%d/%m/%Y") AS date_commande_format, ef.percent_expense';
$sql .= ', pr.title as projectName, c.facture, c.fk_statut';
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = c.fk_soc LEFT JOIN ".MAIN_DB_PREFIX."commandedet AS cd ON cd.fk_commande = c.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields AS ef on (c.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet AS pr on (c.fk_projet = pr.rowid)";
$sql.= ' WHERE c.entity IN ('.getEntity('commande').')';
$sql.= " AND c.facture = 0 AND c.fk_statut IN (1,2,3) ";
$sql.= " GROUP BY c.rowid";
$sql.= " ORDER BY c.total_ht DESC";

//print $sql;
		
$resql = $db->query($sql);
$num = $db->num_rows($resql);


$i = 0;
$totalcommandes = 0;
$commandesEnCours = array();

while ($i < $num)
{	
	
	$dataTemp = $db->fetch_object($resql);

	$commandesEnCours[$i]['total_ht']=$dataTemp->total_ht;
	$commandesEnCours[$i]['ref']=$dataTemp->ref;
	$commandesEnCours[$i]['client']=$dataTemp->nom;
	$commandesEnCours[$i]['ref_client']=$dataTemp->ref_client;
	$commandesEnCours[$i]['rowid']=$dataTemp->rowid;
	$commandesEnCours[$i]['buy_price_ht']=$dataTemp->sum_buy_price;
    $commandesEnCours[$i]['projectName']=$dataTemp->projectName;
	$commandesEnCours[$i]['percent_expense']=($dataTemp->percent_expense)?$dataTemp->percent_expense:0;
	$commandesEnCours[$i]['spent']=$dataTemp->sum_buy_price*($dataTemp->percent_expense/100);
	$commandesEnCours[$i]['facture']=$dataTemp->facture;
	$commandesEnCours[$i]['fk_statut']=$dataTemp->fk_statut;

	//Get invoices of order
	if(substr(DOL_VERSION,0,2) > 13){
		$sql2 = 'SELECT IF(ee.fk_source = '.$dataTemp->rowid.',ee.fk_target,ee.fk_source) AS id_fact, f.ref AS f_ref, f.total_ht';
	}else{
		$sql2 = 'SELECT IF(ee.fk_source = '.$dataTemp->rowid.',ee.fk_target,ee.fk_source) AS id_fact, f.ref AS f_ref, f.total';
	}
	
	$sql2.= ' FROM '.MAIN_DB_PREFIX.'element_element AS ee LEFT JOIN '.MAIN_DB_PREFIX.'facture AS f ON IF(ee.fk_source = '.$dataTemp->rowid.',ee.fk_target,ee.fk_source) = f.rowid';
	$sql2.= ' WHERE (ee.fk_source = '.$dataTemp->rowid.' AND ee.sourcetype = "commande" AND ee.targettype = "facture")';
	$sql2.= ' OR (ee.fk_target = '.$dataTemp->rowid.' AND ee.targettype = "commande" AND ee.sourcetype = "facture" )';
	
	$resql2 = $db->query($sql2);
	$num2 = $db->num_rows($resql2);
	
	$sumInvoices = 0;
	
	while ($y < $num2)
	{
		$dataTemp2 = $db->fetch_object($resql2);
		
		$sumInvoices += $dataTemp2->total;
		
		$y++;
	}
	
	$commandesEnCours[$i]['rest_to_invoice']=$dataTemp->total_ht - $sumInvoices;
	
	$totalcommandes += $dataTemp->total_ht;
	$totalCdeRevient += $dataTemp->sum_buy_price;
	$totalCdeRestToSpent += $dataTemp->sum_buy_price*(1-($dataTemp->percent_expense/100));
	$totalCdeResteAFacturer +=$commandesEnCours[$i]['rest_to_invoice'];
	
	$i++;
}

//DEBUG MODE
if($conf->global->EASYDASHBOARD_DEBUG_VALUE == "commandesEnCours"){
	print 'SQL:';
	print '</br>';
	print '<pre>'; print_r($sql); print '</pre>';
	print '</br>';
	print 'commandesEnCours';
	print '</br>';
	print '<pre>'; print_r($commandesEnCours); print '</pre>';exit();
}

/*
	TABLEAU CONTRATS : SERVICES ACTIFS
*/

$sql = 'SELECT cd.rowid, cd.fk_contrat, cd.description, cd.qty, cd.price_ht, cd.total_ht, c.ref_customer, c.ref, c.fk_soc,';
$sql.= ' cef.period_contract as options_period_contract, ef.revient as options_revient,';
$sql.= ' cd.fk_product as id_product, pro.label as product_label';
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat AS c ON c.rowid = cd.fk_contrat";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet_extrafields AS ef ON ef.fk_object = cd.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat_extrafields AS cef ON cef.fk_object = c.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS soc ON c.fk_soc = soc.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product AS pro ON cd.fk_product = pro.rowid";
$sql.= " WHERE cd.statut = 4";
$sql.= ' AND c.entity IN ('.getEntity('contract').')';
$sql.= ' AND soc.client > 0';
$sql.= ' AND cd.price_ht > 0';

if($conf->global->EASYDASHBOARD_CONTRACT_FILTER == "EXTRAFIELD_TYPE_CONTRAT_CLIENT"){
	$sql.= ' AND cef.type_contrat = 1';
}

$sql.= " ORDER BY cd.fk_contrat, cd.rowid DESC";

//print $sql;
		
$resql = $db->query($sql);
$num = $db->num_rows($resql);


$i = 0;
$totalcontrat = 0;
$contratsEnService = array();

while ($i < $num)
{
	$dataTemp = $db->fetch_object($resql);
	
	
	($dataTemp->options_period_contract)?$periodContract = $dataTemp->options_period_contract:$periodContract = $defautPeriodContract;
	
	$rapportCaAnnual = 12/$periodContract;	
	
	$totalcontrat += $dataTemp->total_ht*$rapportCaAnnual;
	$totalContratRevient += $dataTemp->options_revient*$dataTemp->qty*$rapportCaAnnual;

	$object = new Societe($db);
	$result=$object->fetch($dataTemp->fk_soc);

	$contratsEnService[$i]['price_ht']=$dataTemp->price_ht;

	if(!empty($dataTemp->id_product)){
		$contratsEnService[$i]['description']=$dataTemp->product_label;
	}else{
		$contratsEnService[$i]['description']=$dataTemp->description;
	}

	
	$contratsEnService[$i]['qty']=$dataTemp->qty;
	$contratsEnService[$i]['fk_contrat']=$dataTemp->fk_contrat;
	$contratsEnService[$i]['ref']=$dataTemp->ref;
	$contratsEnService[$i]['ref_customer']=$dataTemp->ref_customer;
	$contratsEnService[$i]['rowid']=$dataTemp->rowid;
	$contratsEnService[$i]['societe']=$object->nom;
	$contratsEnService[$i]['revient']=$dataTemp->options_revient;
	
	
	$i++;
}

//DEBUG MODE
if($conf->global->EASYDASHBOARD_DEBUG_VALUE == "contratsEnService"){
	print 'SQL:';
	print '</br>';
	print '<pre>'; print_r($sql); print '</pre>';
	print '</br>';
	print 'contratsEnService';
	print '</br>';
	print '<pre>'; print_r($contratsEnService); print '</pre>';exit();
}


/*
	TABLEAU DETTE
*/
if(!empty($conf->loan->enabled)){
	$sql = 'SELECT l.rowid, l.capital, pl.datep, SUM(pl.amount_capital) as montant_capital_rembourse';
	$sql.= " FROM ".MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl ON l.rowid = pl.fk_loan";
	$sql.= ' WHERE l.active = 1';
	$sql.= " AND l.entity = ".$conf->entity;
	$sql.= " GROUP BY l.rowid";
	
	//print $sql;
			
	$resql = $db->query($sql);
	$num = $db->num_rows($resql);
	
	
	$i = 0;
	$totalCapitalEmprunte = 0;
	$totalCapitalRembourse = 0;
	
	while ($i < $num)
	{
		$dataTemp = $db->fetch_object($resql);
		
		$totalCapitalEmprunte += $dataTemp->capital;
		$totalCapitalRembourse += $dataTemp->montant_capital_rembourse;
		
		$i++;
	}
}


/*
	TABLEAU DES DEVIS - OPPORTUNITE
*/
$sql = 'SELECT p.rowid, p.total_ht, p.ref, s.nom, p.ref_client, pr.ref, pr.title, pr.opp_percent, SUM(pd.buy_price_ht * pd.qty) AS revient';
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p LEFT JOIN ".MAIN_DB_PREFIX."projet AS pr ON pr.rowid = p.fk_projet";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = p.fk_soc ";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propaldet AS pd ON p.rowid = pd.fk_propal ";
$sql.= ' WHERE p.entity IN ('.getEntity('propal').')';
$sql.= ' AND p.fk_statut IN (1)';
//$sql.= ' AND pr.opp_percent > '.$percentOpportunityToShow;
$sql.= ' GROUP BY p.rowid';
$sql.= " ORDER BY p.total_ht DESC";

//print $sql;
		
$resql = $db->query($sql);
$num = $db->num_rows($resql);

$i = 0;


while ($i < $num)
{
	$dataTemp = $db->fetch_object($resql);

	$opportunity80[$i]['rowid']=$dataTemp->rowid;
	$opportunity80[$i]['total_ht']=$dataTemp->total_ht;
	$opportunity80[$i]['societe']=$dataTemp->nom;
	$opportunity80[$i]['ref_client']=$dataTemp->ref_client;
	$opportunity80[$i]['revient']=$dataTemp->revient;

	$totalCAOpportunities += $dataTemp->total_ht;
	$totalRevientOpportunities += $dataTemp->revient;

	$i++;
}



/*
	PROJECTION TRESORERIE
*/
$societestatic = new Societe($db);
$facturestatic = new Facture($db);
$facturefournstatic = new FactureFournisseur($db);
$socialcontribstatic = new ChargeSociales($db);

// Remainder to pay in future
$sqls = array();

// Customer invoices
$sql = "SELECT 'invoice' as family, f.rowid as objid, f.ref as ref, f.total_ttc, f.type, f.date_lim_reglement as dlr,";
$sql .= " s.rowid as socid, s.nom as name, s.fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
$sql .= " WHERE f.entity IN  (".getEntity('invoice').")";
$sql .= " AND f.paye = 0 AND f.fk_statut = 1"; // Not paid
$sql .= " ORDER BY dlr ASC";
$sqls[] = $sql;

// Supplier invoices
$sql = " SELECT 'invoice_supplier' as family, ff.rowid as objid, ff.ref as ref, ff.ref_supplier as ref_supplier, (-1*ff.total_ttc) as total_ttc, ff.type, ff.date_lim_reglement as dlr,";
$sql .= " s.rowid as socid, s.nom as name, s.fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ff.fk_soc = s.rowid";
$sql .= " WHERE ff.entity = ".$conf->entity;
$sql .= " AND ff.paye = 0 AND fk_statut = 1"; // Not paid
$sql .= " ORDER BY dlr ASC";
$sqls[] = $sql;

// Social contributions
$sql = " SELECT 'social_contribution' as family, cs.rowid as objid, cs.libelle as ref, (-1*cs.amount) as total_ttc, ccs.libelle as type, cs.date_ech as dlr";
$sql .= ", cs.fk_account";
$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as cs";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_chargesociales as ccs ON cs.fk_type = ccs.id";
$sql .= " WHERE cs.entity = ".$conf->entity;
$sql .= " AND cs.paye = 0"; // Not paid
$sql .= " ORDER BY dlr ASC";
$sqls[] = $sql;

$error = 0;
$tab_sqlobjOrder = array();
$tab_sqlobj = array();

foreach ($sqls as $sql) {
	$resql = $db->query($sql);
	if ($resql) {
		while ($sqlobj = $db->fetch_object($resql)) {
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[] = $db->jdate($sqlobj->dlr);
		}
		$db->free($resql);
	} else {
		$error++;
	}
}


// Sort array
if (!$error)
{
	array_multisort($tab_sqlobjOrder, $tab_sqlobj);

	// Apply distinct filter
	foreach ($tab_sqlobj as $key=>$value) {
		$tab_sqlobj[$key] = "'".serialize($value)."'";
	}
	$tab_sqlobj = array_unique($tab_sqlobj);
	foreach ($tab_sqlobj as $key=>$value) {
		$tab_sqlobj[$key] = unserialize(trim($value, "'"));
	}

	$num = count($tab_sqlobj);
	$solde = array();

	$i = 0;
	while ($i < $num)
	{
		$ref = '';
		$refcomp = '';
		$totalpayment = '';

		$obj = array_shift($tab_sqlobj);

		if ($obj->family == 'invoice_supplier')
		{
			$facturefournstatic->id = $obj->objid; 
			$totalpayment = -1 * $facturefournstatic->getSommePaiement(); // Payment already done
		}
		if ($obj->family == 'invoice')
		{
			$facturestatic->id = $obj->objid;
			$totalpayment = $facturestatic->getSommePaiement(); // Payment already done
			$totalpayment += $facturestatic->getSumDepositsUsed();
			$totalpayment += $facturestatic->getSumCreditNotesUsed();
		}
		if ($obj->family == 'social_contribution')
		{
			$socialcontribstatic->id = $obj->objid;
			$totalpayment = -1 * $socialcontribstatic->getSommePaiement(); // Payment already done
		}

		$total_ttc = $obj->total_ttc;
		if ($totalpayment) $total_ttc = $obj->total_ttc - $totalpayment;

		$dateOfInvoice = strtotime($obj->dlr);
		$monthOfInvoice = date('m',$dateOfInvoice);
		$yearOfInvoice = date('Y',$dateOfInvoice);
		$periodeOfInvoice = $monthOfInvoice.'/'.$yearOfInvoice;

		if (price2num($total_ttc) != 0) $solde[$periodeOfInvoice] += $total_ttc;

		$i++;
	}
}
else
{
	dol_print_error($db);
}

//print '<pre>'; print_r($solde); print '</pre>';exit();

//***************************************************************************************************************************
// Get CA BY period
//***************************************************************************************************************************

if(substr(DOL_VERSION,0,2) > 13){
	$tabData['sum'] = ['total_ht','total_tva'];
}else{
	$tabData['sum'] = ['total','tva'];
}

$listOfLabel = $period;
$dateName = 'datef';
$from = MAIN_DB_PREFIX.'facture';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND fk_statut IN (1,2)';
$where .= ' AND entity IN ('.getEntity('invoice').')';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))	$where .= " AND type IN (0,1,2,5)";
else $where .= " AND type IN (0,1,2,3,5)";

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$CAByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

if(substr(DOL_VERSION,0,2) > 13){
	$CAByPeriod["total"] = $CAByPeriod["total_ht"];
	$CAByPeriod["cumul_total"] = $CAByPeriod["cumul_total_ht"];
	$CAByPeriod["sum_total"] = $CAByPeriod["sum_total_ht"];
	$CAByPeriod["tva"] = $CAByPeriod["total_tva"];
	$CAByPeriod["cumul_tva"] = $CAByPeriod["cumul_total_tva"];
	$CAByPeriod["sum_tva"] = $CAByPeriod["sum_total_tva"];
}

//print '<pre>'; print_r($CAByPeriod); print '</pre>';exit();

//DEBUG MODE
if($conf->global->EASYDASHBOARD_DEBUG_VALUE == "CAByPeriod"){
	print '<pre>'; print_r($CAByPeriod); print '</pre>';exit();
}

//***************************************************************************************************************************
// Get various Miscellaneous payments (credit) BY period
//***************************************************************************************************************************

/*
$tabData['sum'] = ['amount'];

$listOfLabel = $period;
$dateName = 'datep';
$from = MAIN_DB_PREFIX.'payment_various';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND sens IN (1)';
$where .= " AND entity = ".$conf->entity;
//$where .= whereProject($idProjetCF, 'fk_projet','fixe');

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$variousCreditByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);
*/
//print '<pre>'; print_r($variousCreditByPeriod); print '</pre>';exit();



//***************************************************************************************************************************
// Get ffour fixes BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'datef';
$tabData['sum'] = ['total_ht','total_tva'];
$from = MAIN_DB_PREFIX.'facture_fourn';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND fk_statut IN (1,2)';
$where .= " AND entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 'fk_projet','fixe');

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$fFourFixesByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

//***************************************************************************************************************************
// Get ffour variables BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'datef';
$tabData['sum'] = ['total_ht','total_tva'];
$from = MAIN_DB_PREFIX.'facture_fourn';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND fk_statut IN (1,2)';
$where .= " AND entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 'fk_projet','variable');

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$fFourVariablesByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

//***************************************************************************************************************************
// Get salaires fixes BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'datesp';
$tabData['sum'] = ['amount'];

if(substr(DOL_VERSION,0,2) > 13){
	$from = MAIN_DB_PREFIX.'salary';
}else{
	$from = MAIN_DB_PREFIX.'payment_salary';
}


$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND entity IN ('.$conf->entity.')';
$where .= whereProject($idProjetCF, 'fk_projet','fixe');

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$salaireFixeByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

//print '<pre>'; print_r($salaireFixeByPeriod); print '</pre>';exit();

//***************************************************************************************************************************
// Get salaires variables BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'datesp';
$tabData['sum'] = ['amount'];

if(substr(DOL_VERSION,0,2) > 13){
	$from = MAIN_DB_PREFIX.'salary';
}else{
	$from = MAIN_DB_PREFIX.'payment_salary';
}

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND entity IN ('.$conf->entity.')';
$where .= whereProject($idProjetCF, 'fk_projet','variable');

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$salaireVariableByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


//***************************************************************************************************************************
// Get charges sociales fixes BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'cs.periode';
$tabData['sum'] = ['amount'];
$from = MAIN_DB_PREFIX.'chargesociales AS cs LEFT JOIN '.MAIN_DB_PREFIX."c_chargesociales AS csd ON csd.id = cs.fk_type";

$type = 'fixe';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= " AND cs.entity = ".$conf->entity;

if(!empty($chargeSocialFilter) && is_array($chargeSocialFilter)){
	$where .= ' AND csd.code NOT IN ("'.implode('","',$chargeSocialFilter).'")';
}

$where .= whereProject($idProjetCF, 'cs.fk_projet',$type);

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$cSocialesFixeByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


//***************************************************************************************************************************
// Get charges sociales variables BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'cs.periode';
$tabData['sum'] = ['amount'];
$from = MAIN_DB_PREFIX.'chargesociales AS cs LEFT JOIN '.MAIN_DB_PREFIX."c_chargesociales AS csd ON csd.id = cs.fk_type";

$type = 'variable';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= " AND entity = ".$conf->entity;

if(!empty($chargeSocialFilter) && is_array($chargeSocialFilter)){
	$where .= ' AND csd.code NOT IN ("'.implode('","',$chargeSocialFilter).'")';
}

$where .= whereProject($idProjetCF, 'cs.fk_projet',$type);

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$cSocialesVariablesByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


//***************************************************************************************************************************
// Get emprunt fixes BY period
//***************************************************************************************************************************
$empruntFixeByPeriod = array();

if(!empty($conf->loan->enabled)){
	$listOfLabel = $period;
	$dateName = 'pl.datep';
	$tabData['sum'] = ['pl.amount_insurance','pl.amount_interest'];
	$from = MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl ON l.rowid = pl.fk_loan";
	$type = 'fixe';

	$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
	$where .= " AND entity = ".$conf->entity;
	$where .= whereProject($idProjetCF, 'l.fk_projet',$type);

	$groupBy = 'month';
	$orderBy = $dateName.' ASC';

	$empruntFixeByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);
}

//***************************************************************************************************************************
// Get emprunt variables BY period
//***************************************************************************************************************************
$empruntVariableByPeriod = array();

if(!empty($conf->loan->enabled)){
	$listOfLabel = $period;
	$dateName = 'pl.datep';
	$tabData['sum'] = ['pl.amount_insurance','pl.amount_interest'];
	$from = MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl ON l.rowid = pl.fk_loan";
	$type = 'variable';

	$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
	$where .= " AND entity = ".$conf->entity;
	$where .= whereProject($idProjetCF, 'l.fk_projet',$type);

	$groupBy = 'month';
	$orderBy = $dateName.' ASC';

	$empruntVariableByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);
}

//***************************************************************************************************************************
// Get notes de frais fixes BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'ed.date';
$tabData['sum'] = ['ed.total_ht', 'ed.total_tva'];
$from = MAIN_DB_PREFIX."expensereport_det AS ed LEFT JOIN ".MAIN_DB_PREFIX."expensereport AS e ON e.rowid = ed.fk_expensereport";
$type = 'fixe';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= " AND e.entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 'ed.fk_projet',$type);

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$notesFraisFixeByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

//***************************************************************************************************************************
// Get notes de frais variables BY period
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'ed.date';
$tabData['sum'] = ['ed.total_ht', 'ed.total_tva'];
$from = MAIN_DB_PREFIX."expensereport_det AS ed LEFT JOIN ".MAIN_DB_PREFIX."expensereport AS e ON e.rowid = ed.fk_expensereport";
$type = 'variable';

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= " AND e.entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 'ed.fk_projet',$type);

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$notesFraisVariableByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


//***************************************************************************************************************************
// Get trésorerie by month
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'b.dateo';
$tabData['sum'] = ['b.amount'];
$from = MAIN_DB_PREFIX."bank AS b LEFT JOIN ".MAIN_DB_PREFIX."bank_account AS ba ON b.fk_account = ba.rowid";

$where = $dateName." <= '".$dateendfiltre."'";
$where .= " AND ba.entity = ".$conf->entity;


$groupBy = 'month';
$orderBy = $dateName.' ASC';

$tresoByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

//print '<pre>'; print_r($tresoByPeriod); print '</pre>';exit();

//***************************************************************************************************************************
// Get TVA Payée
//***************************************************************************************************************************
$listOfLabel = $period;
$dateName = 'datev';
$tabData['sum'] = ['amount'];
$from = MAIN_DB_PREFIX."tva";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND entity IN ('.getEntity('tax').')';

$groupBy = 'month';
$orderBy = $dateName.' ASC';

$tvaPayeeByPeriod = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);



//***************************************************************************************************************************
// Get CA BY client
//***************************************************************************************************************************
if(substr(DOL_VERSION,0,2) > 13){
	$tabData['sum'] = ['t.total_ht'];
	$orderBy = 't_total_ht DESC';
}else{
	$tabData['sum'] = ['t.total'];
	$orderBy = 't_total DESC';
}

$listOfLabel = '';
$dateName = 't.datef';
$from = MAIN_DB_PREFIX."facture as t LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = t.fk_soc";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND t.fk_statut IN (1,2)';
$where .= ' AND t.entity IN ('.getEntity('invoice').')';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))	$where .= " AND t.type IN (0,1,2,5)";
else $where .= " AND t.type IN (0,1,2,3,5)";

$groupBy = 's.nom';
$limit = $graphMaxTurnover;

$CAByClient = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit);

if(substr(DOL_VERSION,0,2) > 13){
	$CAByClient["sum_t_total"] = $CAByClient["sum_t_total_ht"];
	$CAByClient["t_total"] = $CAByClient["t_total_ht"];
	$CAByClient["cumul_t_total"] = $CAByClient["cumul_t_total_ht"];
}

//print '<pre>'; print_r($CAByClient); print '</pre>';exit();

//***************************************************************************************************************************
// Get variable cost BY client
//***************************************************************************************************************************
if(substr(DOL_VERSION,0,2) > 13){
	$tabData['sum'] = ['t.total_ht'];
	$orderBy = 't_total_ht DESC';
}else{
	$tabData['sum'] = ['t.total'];
	$orderBy = 't_total DESC';
}

$listOfLabel = '';
$dateName = 't.datef';

$from = MAIN_DB_PREFIX."facture_fourn as t";
$from .= " LEFT JOIN ".MAIN_DB_PREFIX."projet AS pr ON pr.rowid = t.fk_projet";
$from .= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = pr.fk_soc";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND t.fk_statut IN (1,2)';
$where .= " AND t.entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 't.fk_projet','variable');

$groupBy = 's.nom';
$limit = $graphMaxTurnover;

$variableCostByClient = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit);

if(substr(DOL_VERSION,0,2) > 13){
	$variableCostByClient["sum_t_total"] = $variableCostByClient["sum_t_total_ht"];
	$variableCostByClient["t_total"] = $variableCostByClient["t_total_ht"];
	$variableCostByClient["cumul_t_total"] = $variableCostByClient["cumul_t_total_ht"];
}

//print '<pre>'; print_r($variableCostByClient); print '</pre>';exit();

//***************************************************************************************************************************
// Get CA BY project
//***************************************************************************************************************************
if(substr(DOL_VERSION,0,2) > 13){
	$tabData['sum'] = ['fc.total_ht'];
	$orderBy = 'fc_total_ht DESC';
}else{
	$tabData['sum'] = ['fc.total'];
	$orderBy = 'fc_total DESC';
}

$listOfLabel = '';
$dateName = 'pr.datee';
$tabData['line'] = ['pr.title','pr.ref','pr.rowid'];
$from = MAIN_DB_PREFIX."projet as pr LEFT JOIN ".MAIN_DB_PREFIX."facture as fc ON pr.rowid = fc.fk_projet";

$where = "1 = 1";
//$where = "(".$dateName." > '".$datestartfiltre."' OR (pr.dateo > '".$datestartfiltre."' AND pr.dateo < '".$dateendfiltre."'))";
//$where .= " AND (pr.datec >= '".$datestartfiltre."' AND pr.datec <= '".$dateendfiltre."')";
if(!empty($conf->global->EASYDASHBOARD_IDPROJET_COUTFIXE)){
	$where .= ' AND pr.rowid NOT IN ('.$conf->global->EASYDASHBOARD_IDPROJET_COUTFIXE.')';
}
$where .= ' AND pr.fk_statut IN (1)';
$where .= ' AND fc.fk_statut IN (1,2)';
$where .= ' AND fc.entity IN ('.getEntity('invoice').')';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))	$where .= " AND fc.type IN (0,1,2,5)";
else $where .= " AND fc.type IN (0,1,2,3,5)";

$groupBy = 'pr.rowid';

$CAByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


if(substr(DOL_VERSION,0,2) > 13){
	$CAByProject["fc_total"] = $CAByProject["fc_total_ht"];
}

//DEBUG MODE
if($conf->global->EASYDASHBOARD_DEBUG_VALUE == "CAByProject"){
	print '<pre>'; print_r($CAByProject); print '</pre>';exit();
}

if(!empty($CAByProject["fc_total"])){
	
	$listIdProject = array_keys($CAByProject["fc_total"]);

	//***************************************************************************************************************************
	// Get factures fournisseurs BY project
	//***************************************************************************************************************************

	$listOfLabel = $listIdProject;
	$dateName = '';
	$tabData['sum'] = ['total_ht'];
	$tabData['line'] = '';
	$from = MAIN_DB_PREFIX."facture_fourn";

	$where = ' fk_statut IN (1,2)';
	$where .= ' AND entity = '.$conf->entity;
	$where .= ' AND fk_projet IN ('.implode(',',$listIdProject).')';

	$groupBy = 'fk_projet';
	$orderBy = '';

	$fFourByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

	//DEBUG MODE
	if($conf->global->EASYDASHBOARD_DEBUG_VALUE == "fFourByProject"){
		print '<pre>'; print_r($fFourByProject); print '</pre>';exit();
	}

	//***************************************************************************************************************************
	// Get salaires BY project
	//***************************************************************************************************************************

	$listOfLabel = $listIdProject;
	$dateName = '';
	$tabData['sum'] = ['amount'];
	$tabData['line'] = '';
	if(substr(DOL_VERSION,0,2) > 13){
		$from = MAIN_DB_PREFIX.'salary';
	}else{
		$from = MAIN_DB_PREFIX.'payment_salary';
	}

	$where = ' entity IN ('.$conf->entity.')';
	$where .= ' AND fk_projet IN ('.implode(',',$listIdProject).')';

	$groupBy = 'fk_projet';
	$orderBy = '';

	$salairesByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


	//***************************************************************************************************************************
	// Get charges BY project
	//***************************************************************************************************************************

	$listOfLabel = $listIdProject;
	$dateName = '';
	$tabData['sum'] = ['amount'];
	$tabData['line'] = '';
	$from = MAIN_DB_PREFIX."chargesociales";

	$where = ' entity = '.$conf->entity;
	$where .= ' AND fk_projet IN ('.implode(',',$listIdProject).')';

	$groupBy = 'fk_projet';
	$orderBy = '';

	$chargesByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);


	//***************************************************************************************************************************
	// Get loan report BY project
	//***************************************************************************************************************************

	$loanByProject = array();

	if(!empty($conf->loan->enabled)){
		$listOfLabel = $listIdProject;
		$dateName = '';
		$tabData['sum'] = ['pl.amount_insurance','pl.amount_interest'];
		$tabData['line'] = '';
		$from = MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl ON l.rowid = pl.fk_loan";

		$where = ' l.entity = '.$conf->entity;
		$where .= ' AND l.fk_projet IN ('.implode(',',$listIdProject).')';

		$groupBy = 'l.fk_projet';
		$orderBy = '';

		$loanByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);
	}

	//***************************************************************************************************************************
	// Get expense report BY project
	//***************************************************************************************************************************

	$listOfLabel = $listIdProject;
	$dateName = '';
	$tabData['sum'] = ['ed.total_ht'];
	$tabData['line'] = '';
	$from = MAIN_DB_PREFIX."expensereport as e LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det AS ed ON e.rowid = ed.fk_expensereport";

	$where = ' e.entity = '.$conf->entity;
	$where .= ' AND ed.fk_projet IN ('.implode(',',$listIdProject).')';

	$groupBy = 'ed.fk_projet';
	$orderBy = '';

	$expenseReportByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);
	
	//***************************************************************************************************************************
	// Get task duration BY project
	//***************************************************************************************************************************

	/*
	$listOfLabel = $listIdProject;
	$dateName = '';
	$tabData['sum'] = ['tt.task_duration'];
	$tabData['line'] = '';
	$from = MAIN_DB_PREFIX."projet_task as t LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time AS tt ON t.rowid = tt.fk_task";

	$where = ' t.entity = '.$conf->entity;
	$where .= ' AND t.fk_projet IN ('.implode(',',$listIdProject).')';

	$groupBy = 't.fk_projet';
	$orderBy = '';

	$taskTimeByProject = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

	*/
/*
	print '<pre>';
	print_r($taskTimeByProject);
	print '</pre>';
*/

}


//***************************************************************************************************************************
// Get task duration BY project
//***************************************************************************************************************************
/*
$listOfLabel = '';
$dateName = 'tt.task_date';
$tabData['sum'] = ['tt.task_duration'];
$tabData['line'] = ['t.label'];
$from = MAIN_DB_PREFIX."projet_task as t LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time AS tt ON t.rowid = tt.fk_task";

$where = ' t.entity = '.$conf->entity;
$where .= whereProject($idProjetCF, 't.fk_projet','fixe');
$where .= ' AND '.$dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";;

$groupBy = 't.rowid';
$orderBy = '';

$taskTimeByFG = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

if(!empty($taskTimeByFG['tt_task_duration'])){
	foreach($taskTimeByFG['tt_task_duration'] as $key=>$val){
		$taskTimeByFG['tt_task_duration'][$key] = round($taskTimeByFG['tt_task_duration'][$key]/3600,1);
	}
}
*/
	
//***************************************************************************************************************************
// Get ffour total BY fournisseur
//***************************************************************************************************************************
$listOfLabel = '';
$dateName = 't.datef';
$tabData['sum'] = ['t.total_ht'];
$tabData['line'] = '';
$from = MAIN_DB_PREFIX."facture_fourn as t LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = t.fk_soc";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND t.fk_statut IN (1,2)';
$where .= " AND t.entity = ".$conf->entity;

$groupBy = 's.nom';
$orderBy = 't_total_ht DESC';
$limit = $graphMaxCF;

$fFourTotalByFournisseur = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit);

//***************************************************************************************************************************
// Get ffour fixes BY fournisseur
//***************************************************************************************************************************
$listOfLabel = '';
$dateName = 't.datef';
$tabData['sum'] = ['t.total_ht'];
$tabData['line'] = '';
$from = MAIN_DB_PREFIX."facture_fourn as t LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = t.fk_soc";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND t.fk_statut IN (1,2)';
$where .= " AND t.entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 't.fk_projet','fixe');

$groupBy = 's.nom';
$orderBy = 't_total_ht DESC';
$limit = $graphMaxCF;

$fFourFixesByFournisseur = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit);

//***************************************************************************************************************************
// Get ffour variables BY fournisseur
//***************************************************************************************************************************
$listOfLabel = '';
$dateName = 't.datef';
$tabData['sum'] = ['t.total_ht'];
$from = MAIN_DB_PREFIX."facture_fourn as t LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = t.fk_soc";

$where = $dateName." BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND t.fk_statut IN (1,2)';
$where .= " AND t.entity = ".$conf->entity;
$where .= whereProject($idProjetCF, 't.fk_projet','variable');

$groupBy = 's.nom';
$orderBy = 't_total_ht DESC';
$limit = $graphMaxCF;

$fFourVariablesByFournisseur = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy, $limit);



/*
	CALCULS
	*********************************************************************************
*/

$chiffreAffaire = $CAByPeriod["sum_total"];
$totalFactFixe = $fFourFixesByPeriod["sum_total_ht"];
$totalFactVariable = $fFourVariablesByPeriod["sum_total_ht"];
$totalSalaireFixe = $salaireFixeByPeriod["sum_amount"];
$totalSalaireVariable = $salaireVariableByPeriod["sum_amount"];
$totalChargesSocialesFixe = $cSocialesFixeByPeriod["sum_amount"];
$totalChargesSocialesVariable = $cSocialesVariablesByPeriod["sum_amount"];
$totalEmpruntsFixe = $empruntFixeByPeriod["sum_pl_amount_insurance"]+$empruntFixeByPeriod["sum_pl_amount_interest"];
$totalEmpruntsVariable = $empruntVariableByPeriod["sum_pl_amount_insurance"]+$empruntVariableByPeriod["sum_pl_amount_interest"];
$totalNotesFraisFixe = $notesFraisFixeByPeriod["sum_ed_total_ht"];
$totalNotesFraisVariable = $notesFraisVariableByPeriod["sum_ed_total_ht"];

$totalCoutsVariables = $totalFactVariable+$totalSalaireVariable+$totalChargesSocialesVariable+$totalEmpruntsVariable+$totalNotesFraisVariable;
$totalCoutsFixes = $totalFactFixe+$totalSalaireFixe+$totalChargesSocialesFixe+$totalEmpruntsFixe+$totalNotesFraisFixe;

$totalFactFour = $totalFactVariable+$totalFactFixe;
$totalSalaires = $totalSalaireVariable+$totalSalaireFixe;
$totalChargesSociales = $totalChargesSocialesVariable+$totalChargesSocialesFixe;
$totalEmprunts = $totalEmpruntsVariable+$totalEmpruntsFixe;
$totalNotesFrais = $totalNotesFraisVariable+$totalNotesFraisFixe;
$totalDepenses = $totalFactFour+$totalSalaires+$totalChargesSociales+$totalEmprunts+$totalNotesFrais;

$benefices = $chiffreAffaire - $totalCoutsVariables - $totalCoutsFixes;
$MCV = $chiffreAffaire - $totalCoutsVariables;
$chiffreAffaireCritique = 0;

$periodDays = intval(($now-$tmstpStart)/(3600*24));
$periodMonth = $periodDays/30.4;

if($periodMonth < 1){
	$periodMonth = 1;
}

$coutsFixesMoyens = round($totalCoutsFixes/$periodMonth);


if ($chiffreAffaire>0){
	$seuilRentabilite = $totalCoutsFixes/($MCV/$chiffreAffaire);
}else{
	$seuilRentabilite = 0;
}


$totalTvaPayee = $tvaPayeeByPeriod["sum_amount"];
$tvaDeductibleCalculee = $fFourFixesByPeriod["sum_total_tva"]+$fFourVariablesByPeriod["sum_total_tva"]+$notesFraisFixeByPeriod['sum_ed_total_tva']+$notesFraisFixeByPeriod['sum_ed_total_tva']+$notesFraisVariableByPeriod['sum_ed_total_tva'];
$tvaDueCalculee = $CAByPeriod["sum_tva"];
$tvaCalculee = $tvaDueCalculee-$tvaDeductibleCalculee;

$margeTotaleCde = $totalcommandes-$totalCdeRevient;

if(!empty($totalcommandes)){
	$txMarqueGlobalCde = round($margeTotaleCde/$totalcommandes,2)*100;
}

$margeTotaleContrat = $totalcontrat-$totalContratRevient;
if(!empty($totalcontrat)) $txMarqueGlobalContrat = round($margeTotaleContrat/$totalcontrat,2)*100;
$revientMoyenContrat = round($totalContratRevient/12);
$caMoyenContrat = round($totalcontrat/12);

$margeTotaleOpportunities = $totalCAOpportunities-$totalRevientOpportunities;

if(!empty($totalCAOpportunities)){
	$txMarqueGlobalOpportunities = round($margeTotaleOpportunities/$totalCAOpportunities,2)*100;
}

$nbTotalPeriod = count ($period);
$nbPeriodRestant = 0;
$cumulSolde = 0;

//INITIALISE VARIABLE
$tabProjectionTreso = array();

//LOOP ON PERIOD
$i=0;
foreach($period as $val){
	
	$tabDepensesMois[$val] = 	$fFourFixesByPeriod["total_ht"][$val]
								+$fFourVariablesByPeriod["total_ht"][$val]
								+$salaireFixeByPeriod["amount"][$val]
								+$salaireVariableByPeriod["amount"][$val]
								+$cSocialesFixeByPeriod["amount"][$val]
								+$cSocialesVariablesByPeriod["amount"][$val]
								+$empruntFixeByPeriod["pl_amount_insurance"][$val]+$empruntFixeByPeriod["pl_amount_interest"][$val]
								+$empruntVariableByPeriod["pl_amount_insurance"][$val]+$empruntVariableByPeriod["pl_amount_interest"][$val]
								+$notesFraisFixeByPeriod["ed_total_ht"][$val]
								+$notesFraisVariableByPeriod["ed_total_ht"][$val];
								
	$tabDepensesCumul[$val] = 	$fFourFixesByPeriod["cumul_total_ht"][$val]
								+$fFourVariablesByPeriod["cumul_total_ht"][$val]
								+$salaireFixeByPeriod["cumul_amount"][$val]
								+$salaireVariableByPeriod["cumul_amount"][$val]
								+$cSocialesFixeByPeriod["cumul_amount"][$val]
								+$cSocialesVariablesByPeriod["cumul_amount"][$val]
								+$empruntFixeByPeriod["cumul_pl_amount_insurance"][$val]+$empruntFixeByPeriod["cumul_pl_amount_interest"][$val]
								+$empruntVariableByPeriod["cumul_pl_amount_insurance"][$val]+$empruntVariableByPeriod["cumul_pl_amount_interest"][$val]
								+$notesFraisFixeByPeriod["cumul_ed_total_ht"][$val]
								+$notesFraisVariableByPeriod["cumul_ed_total_ht"][$val];
								
	
	$tabBeneficesMois[$val] = $CAByPeriod['total'][$val]-$tabDepensesMois[$val];
	
	$tabBeneficesCumul[$val] = $CAByPeriod['cumul_total'][$val]-$tabDepensesCumul[$val];
	
	$tabCFMois[$val] = 	$fFourFixesByPeriod["total_ht"][$val]
						+$salaireFixeByPeriod["amount"][$val]
						+$cSocialesFixeByPeriod["amount"][$val]
						+$empruntFixeByPeriod["pl_amount_insurance"][$val]+$empruntFixeByPeriod["pl_amount_interest"][$val]
						+$notesFraisFixeByPeriod["ed_total_ht"][$val];
					
	$tabCVMois[$val] = 	$fFourVariablesByPeriod["total_ht"][$val]
						+$salaireVariableByPeriod["amount"][$val]
						+$cSocialesVariablesByPeriod["amount"][$val]
						+$empruntVariableByPeriod["pl_amount_insurance"][$val]+$empruntVariableByPeriod["pl_amount_interest"][$val]
						+$notesFraisVariableByPeriod["ed_total_ht"][$val];
						
	$tabDepensesMois[$val] = $tabCFMois[$val]+$tabCVMois[$val];
	
	$tresoCumulMois[$val] = $tresoByPeriod['cumul_b_amount'][$val];
							
	//Clean futur datas
	$mois = substr($val, 0, 2);
	$annee = substr($val, 3, 8);
	
	if(empty($tabDepensesMois[$val]) && mktime(0, 0, 0, $mois, 1, $annee) > dol_now()) $tabDepensesCumul[$val] = null;
	if(empty($tabBeneficesMois[$val]) && mktime(0, 0, 0, $mois, 1, $annee) > dol_now()) $tabBeneficesCumul[$val] = null;
	if(empty($tresoCumulMois[$val]) && mktime(0, 0, 0, $mois, 1, $annee) > dol_now()) $tresoCumulMois[$val] = null;
	
	$cumulSolde += $solde[$val];

	if(mktime(0, 0, 0, $mois, 1, $annee) >= mktime(0, 0, 0, date('m'), 1, date('y'))){
		
		if(empty($nbPeriodRestant)) $nbPeriodRestant = $nbTotalPeriod - $i;
		
		$tabProjectionCACommandes[$val] = round($totalCdeResteAFacturer/$nbPeriodRestant);
		$tabProjectionCAContrats[$val] = $caMoyenContrat;
		$tabProjectionCoutsFixesMoyens[$val] = $coutsFixesMoyens;
				
		$tabProjectionCoutsVariables[$val] = $revientMoyenContrat + round($totalCdeRestToSpent/$nbPeriodRestant);
		
		$tabProjectionMarge[$val] = $tabProjectionCACommandes[$val]+$tabProjectionCAContrats[$val]-$tabProjectionCoutsFixesMoyens[$val]-$tabProjectionCoutsVariables[$val];
				
		
		//CUMUL
		$totalProduitProjection = $tabProjectionCACommandes[$val] + $tabProjectionCAContrats[$val];
		$totalDépensesProjections = $tabProjectionCoutsFixesMoyens[$val]+$tabProjectionCoutsVariables[$val];
		
		$tabProjectionCumulCA[$val] = $lastCumulCA + $totalProduitProjection;
		$lastCumulCA = $tabProjectionCumulCA[$val];
		
		$tabProjectionCumulDepenses[$val] = $lastCumulDepenses + $totalDépensesProjections;
		$lastCumulDepenses = $tabProjectionCumulDepenses[$val];

		$tabProjectionCumulMarge[$val] = $lastCumulMarge + $totalProduitProjection - $totalDépensesProjections;
		$lastCumulMarge = $tabProjectionCumulMarge[$val];


		if(!empty($tresoCumulMois[$val]) || !empty($solde[$val])){
			$tabProjectionTreso[$val] = $tresoByPeriod['sum_b_amount']+$cumulSolde;
		}

		
	}else{
		$tabProjectionCACommandes[$val] = 0;
		$tabProjectionCAContrats[$val] = 0;
		$tabProjectionCoutsFixesMoyens[$val] = 0;
		$tabProjectionCoutsVariables[$val] = 0;
		$tabProjectionMarge[$val] = 0;
		
		$tabProjectionCumulCA[$val] = $CAByPeriod['cumul_total'][$val];
		$lastCumulCA = $tabProjectionCumulCA[$val];
		
		$tabProjectionCumulDepenses[$val] = $tabDepensesCumul[$val];
		$lastCumulDepenses = $tabProjectionCumulDepenses[$val];
		
		$tabProjectionCumulMarge[$val] = $tabBeneficesCumul[$val];
		$lastCumulMarge = $tabProjectionCumulMarge[$val];

		$tabProjectionTreso[$val] = $tresoCumulMois[$val];
	}
	
	$i++;
	
}


//LOOP ON PROJECTS

if(!empty($listIdProject)){
	$loanCostByProject = array();
	foreach($listIdProject as $val){							
		
		$tabCAByProject[$val] = ($CAByProject['fc_total'][$val])?$CAByProject['fc_total'][$val]:0;
		
		$tabfFourByProject[$val] = ($fFourByProject['total_ht'][$val])?$fFourByProject['total_ht'][$val]:0;
		
		$tabsalariesAndChargesByProject[$val] = $chargesByProject['amount'][$val]+$salairesByProject['amount'][$val];
		
		$tabLoanCostByProject[$val] = $loanByProject['pl_amount_insurance'][$val] + $loanByProject['pl_amount_interest'][$val];
		
		$tabexpenseReportByProject[$val] = ($expenseReportByProject['ed_total_ht'][$val])?$expenseReportByProject['ed_total_ht'][$val]:0;
		
		$tabResultByProject[$val] = $tabCAByProject[$val]-$tabfFourByProject[$val]-$tabsalariesAndChargesByProject[$val]-$tabLoanCostByProject[$val]-$tabexpenseReportByProject[$val];
		
		/*
		if(empty($taskTimeByProject['tt_task_duration'][$val])){
			$tabTaskTimeByProject[$val] = 0;
		}else{
			$tabTaskTimeByProject[$val] = round($taskTimeByProject['tt_task_duration'][$val]/3600,1);
		}
		*/
	}


	//SORT BY RESULT
	$tabResultByProjectSorted = array_sort($tabResultByProject, '', SORT_DESC);

	$graphMaxElement = 50;
	$loopCounter = 0;

	foreach($tabResultByProjectSorted as $key=>$val){
		
		$tabCAByProjectSorted[$key] = $tabCAByProject[$key];
		$tabfFourByProjectSorted[$key] = $tabfFourByProject[$key];
		$tabsalariesAndChargesByProjectSorted[$key] = $tabsalariesAndChargesByProject[$key];
		$tabLoanCostByProjectSorted[$key] = $tabLoanCostByProject[$key];
		$tabexpenseReportByProjectSorted[$key] = $tabexpenseReportByProject[$key];
		
		/*
		$tabTaskTimeByProjectSorted[$key] = $tabTaskTimeByProject[$key];
		*/
		
		$titlesSorted[$key] = $CAByProject['pr_ref'][$key].' - '.$CAByProject['pr_title'][$key];
		
		$idProjectSorted[$key] = $CAByProject['pr_rowid'][$key];

		$loopCounter++;

		if($loopCounter > $graphMaxElement){
			break;
		}
	}
}

//LOOP ON CUSTOMER


//print '<pre>'; print_r($variableCostByClient); print '</pre>';exit();


if(!empty($CAByClient['t_total'])){
	$MCVByClient = array();

	$sumMCVByClient = 0;
	foreach($CAByClient['t_total'] as $key => $val){
		

		//print '<pre>'; print_r($key . "=>" . $val); print '</pre>';

		if($variableCostByClient["t_total"] && array_key_exists($key,$variableCostByClient["t_total"])){
			//print '<pre>'; print_r("cost=>" . $variableCostByClient["t_total"][$key]); print '</pre>';

			$MCVByClient[$key] = $val - $variableCostByClient["t_total"][$key];

		}else{
			$MCVByClient[$key] = $val;
		}

		$sumMCVByClient += $MCVByClient[$key];
			
		//$tabCAByProject[$val] = ($CAByProject['fc_total'][$val])?$CAByProject['fc_total'][$val]:0;
	}
	//print '<pre>'; print_r($sumMCVByClient); print '</pre>';exit();
}


/*
 *	View
 ****************************************************************************
 */

$title=$langs->trans("ModuleEasyDashboardName");

llxHeader('', $title, '', '',0, 0, '', '', '', 'sidebar-collapse', '');


print '<style>';
print '#canvas_graph_projet:hover {cursor: pointer;}';
print '#canvas_graph_CA:hover {cursor: pointer;}';
print '#canvas_graph_CF:hover {cursor: pointer;}';
print '#canvas_graph_CV:hover {cursor: pointer;}';
print '#canvas_graph_results_project:hover {cursor: pointer;}';
print '</style>';


$form = new form($db);

$greenColors = ['#6B8E23','#556B2F','#2E8B57','#3CB371','#8FBC8F','#98FB98','#90EE90','#00FA9A','#00FF7F','#9ACD32','#ADFF2F','#006400','#008000','#228B22','#00FF00','#32CD32','#7FFF00'];

$blueColors = ['#483D8B','#6A5ACD','#191970','#00008B','#0000CD','#4169E1','#4682B4','#6495ED','#B0C4DE','#00BFFF','#87CEEB','#ADD8E6','#B0E0E6'];

$redColors = ['#8B0000','#FF0000','#B22222','#DC143C','#CD5C5C','#F08080','#E9967A','#FA8072','#FFA07A'];

$yellowColors = ['#FFA500','#FFD700','#FFFF00','#FFFFE0','#FFD700','#ADFF2F','#00FF00'];

$contrastedColors = ['#00FFFF','#008B8B','#00008B','#FFF8DC','#ADFF2F','#00FF00'];

//*******************************************************
//Functions
//*******************************************************

print '<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment.min.js"></script>';
print '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>';

function printBox($titre,$valeur,$title = ''){
	
	print '<div class="boxstatsindicator thumbstat150 nobold nounderline" style="margin: 10px;">';
	print '<div class="boxstats130 boxstatsborder" style="margin: 0 auto;">';
	print '<div class="boxstatscontent" title="'.$title.'">';
	print '<span class="boxstatstext">';
	print $titre;
	print '</span>';
	print '<br>';
	print '<span class="dashboardlineindicator">';
	print '<strong>';
	print $valeur;
	print '</strong>';
	print '</span>';
	print '</div>';
	print '</div>';
	print '</div>';
}

function printProjectionBox($titre,$valeur,$title = ''){
	
	print '<div class="boxstatsindicator thumbstat150 nobold nounderline projectionbox" style="margin: 10px; display:none">';
	print '<div class="boxstats130 boxstatsborder" style="margin: 0 auto;">';
	print '<div class="boxstatscontent" title="'.$title.'">';
	print '<span class="boxstatstext">';
	print $titre;
	print '</span>';
	print '<br>';
	print '<span class="dashboardlineindicator" style="color:grey;">';
	print '<strong>';
	print $valeur;
	print '</strong>';
	print '</span>';
	print '</div>';
	print '</div>';
	print '</div>';
}

function printBigBox($titre,$valeur,$title = ''){
	
	print '<div class="boxstatsindicator nobold nounderline" style="display: inline-flex; width: 220px; flex-grow: 1; flex-shrink: 0; margin: 10px;">';
	print '<div class="boxstats130 boxstatsborder" style="margin: 0 auto;">';
	print '<div class="boxstatscontent" title="'.$title.'">';
	print '<span class="boxstatstext">';
	print $titre;
	print '</span>';
	print '<br>';
	print '<span class="dashboardlineindicator">';
	print '<strong>';
	print $valeur;
	print '</strong>';
	print '</span>';
	print '</div>';
	print '</div>';
	print '</div>';
}



function printGraph($id_graph,$widthGraph,$titre,$legend,$labelList,$datasetsArray,$eventClick=false,$typeGraph='bar'){
	
	global $langs,$conf,$advancedDisplay;

	$labelListArray = json_decode($labelList);

	if(is_array($labelListArray)){
		$titleLength = count($labelListArray);
		$datasetsArrayCorrected = array();
	
	
		foreach ($datasetsArray as $key => $uniqueDataSet) {

			foreach ($uniqueDataSet as $k => $value) {
				if($k == "data"){

					$n = array_keys($value); //<---- Grab all the keys of your actual array and put in another array
					$count = array_search($titleLength, $n); //<--- Returns the position of the offset from this array using search

					if(!empty($count)){
						$new_arr = array_slice($value, 0, $count + 1, true);//<--- Slice it with the 0 index as start and position+1 as the length parameter.
					}else{
						$new_arr = $value;
					}

					$datasetsArrayCorrected[$key][$k] = $new_arr;
	
				}else{
					$datasetsArrayCorrected[$key][$k] = $value;
				}
			}
			
		}
	}


	
	if($eventClick) $eventClickText = 'onClick: '.$id_graph.',';
	else $eventClickText = '';
	
	print '<div style="width:'.$widthGraph.';"><canvas id="'.$id_graph.'" class="graph"></canvas></div><br>';
	
	print '<script>';
	
	
	if($id_graph == 'canvas_graph_main' && !empty($advancedDisplay)){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								
								var labelText = data.datasets[tooltipItem.datasetIndex].label;
								
								if(tooltipItem.datasetIndex == 0){
									var totalCA = 0;
									
									data.datasets[3].data.forEach(element => totalCA += element);
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / totalCA).toFixed(2) + " % '.$langs->trans("ofTotal").')";
								
								}else if(tooltipItem.datasetIndex == 1 || tooltipItem.datasetIndex == 2){
								
									var caAmount = data.datasets[0].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
								
								}else if(tooltipItem.datasetIndex == 3){
								
									var totalCA = 0;
									
									data.datasets[3].data.forEach(element => totalCA += element);
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / totalCA).toFixed(2) + " % '.$langs->trans("ofTotal").')";
								
								}else if(tooltipItem.datasetIndex < 7){
																		
									var caAmount = data.datasets[3].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
									
								}else if(tooltipItem.datasetIndex == 8){	
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.'";
								
								}else if(tooltipItem.datasetIndex > 12){
									
									var caAmount = data.datasets[12].data[tooltipItem.index] + data.datasets[13].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
																	
								}else{
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.'";
									
								}							
							},
							afterLabel: function (tooltipItem, data) {
								
								if(tooltipItem.datasetIndex == 4 || tooltipItem.datasetIndex == 5){
									var totalCost = data.datasets[4].data[tooltipItem.index] + data.datasets[5].data[tooltipItem.index];
									
									return " '.$langs->transnoentitiesnoconv("TotalCost").'" + " : " + Intl.NumberFormat().format(totalCost) + " '.$conf->global->MAIN_MONNAIE.'";
								}
								
								if(tooltipItem.datasetIndex == 12 || tooltipItem.datasetIndex == 13){
									
									var totalCA = data.datasets[12].data[tooltipItem.index] + data.datasets[13].data[tooltipItem.index];
									
									return " '.$langs->transnoentitiesnoconv("TotalCA").'" + " : " + Intl.NumberFormat().format(totalCA) + " '.$conf->global->MAIN_MONNAIE.'";
									
								}
								
								if(tooltipItem.datasetIndex == 14 || tooltipItem.datasetIndex == 15){
									var totalCost = data.datasets[14].data[tooltipItem.index] + data.datasets[15].data[tooltipItem.index];
									
									return " '.$langs->transnoentitiesnoconv("TotalCost").'" + " : " + Intl.NumberFormat().format(totalCost) + " '.$conf->global->MAIN_MONNAIE.'";
								}
							
							},
						}';
	}elseif($id_graph == 'canvas_graph_main' && empty($advancedDisplay)){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								
								var labelText = data.datasets[tooltipItem.datasetIndex].label;
								
								if(tooltipItem.datasetIndex == 0){
									var totalCA = 0;
									
									data.datasets[3].data.forEach(element => totalCA += element);
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / totalCA).toFixed(2) + " % '.$langs->trans("ofTotal").')";
								
								}else if(tooltipItem.datasetIndex == 1 || tooltipItem.datasetIndex == 2){
								
									var caAmount = data.datasets[0].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
								
								}else if(tooltipItem.datasetIndex == 3){
								
									var totalCA = 0;
									
									data.datasets[3].data.forEach(element => totalCA += element);
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / totalCA).toFixed(2) + " % '.$langs->trans("ofTotal").')";
								
								}else if(tooltipItem.datasetIndex < 6){
									
									var caAmount = data.datasets[3].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
									
								}else if(tooltipItem.datasetIndex == 6){
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.'";

								}else if(tooltipItem.datasetIndex > 11){
									
									var caAmount = data.datasets[10].data[tooltipItem.index] + data.datasets[11].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
																	
								}else{
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.'";
									
								}							
							},
							afterLabel: function (tooltipItem, data) {
								
								
								if(tooltipItem.datasetIndex == 10 || tooltipItem.datasetIndex == 11){
									
									var totalCA = data.datasets[10].data[tooltipItem.index] + data.datasets[11].data[tooltipItem.index];
									
									return " '.$langs->transnoentitiesnoconv("TotalCA").'" + " : " + Intl.NumberFormat().format(totalCA) + " '.$conf->global->MAIN_MONNAIE.'";
									
								}
							
							},
						}';
	
	}elseif($id_graph == 'canvas_graph_projet'){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								
								var labelText = data.datasets[tooltipItem.datasetIndex].label;
								
								if(tooltipItem.datasetIndex == 0){
									var totalCA = 0;
									
									data.datasets[0].data.forEach(element => totalCA += element);
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / totalCA).toFixed(2) + " % '.$langs->trans("ofTotal").')";
								
								}else{
									
									var caAmount = data.datasets[0].data[tooltipItem.index];
									
									return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / caAmount).toFixed(2) + " % '.$langs->trans("ofCA").')";
								
								}						
							},
						}';
						
	}elseif($id_graph == 'canvas_graph_time_task_project'){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var timeTask = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var total = eval(data.datasets[tooltipItem.datasetIndex].data.join("+"));
								return Intl.NumberFormat().format(timeTask) + " ( " + parseFloat(timeTask * 100 / total).toFixed(2) + " % )";
							},
						}';
						
	}elseif($id_graph == 'canvas_graph_task_FG'){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var total = eval(data.datasets[tooltipItem.datasetIndex].data.join("+"));
								return Intl.NumberFormat().format(amount) + " h ( " + parseFloat(amount * 100 / total).toFixed(2) + " % )";
							},
						}';
						
	}elseif($typeGraph == 'pie' || $typeGraph == 'doughnut'){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var total = eval(data.datasets[tooltipItem.datasetIndex].data.join("+"));
								return Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / total).toFixed(2) + " % )";
							},
						}';
						
						
	}elseif($typeGraph == 'horizontalBar'){
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var total = eval(data.datasets[tooltipItem.datasetIndex].data.join("+"));
								var labelText = data.datasets[tooltipItem.datasetIndex].label;
								
								return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / total).toFixed(2) + " % '.$langs->trans("ofTotal").')";
							},
						}';
						
	}else{
		$tooltipsPerso = 'callbacks: {
							title: function (tooltipItem, data) { return data.labels[tooltipItem[0].index]; },
							label: function (tooltipItem, data) {
								var amount = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var total = eval(data.datasets[tooltipItem.datasetIndex].data.join("+"));
								var labelText = data.datasets[tooltipItem.datasetIndex].label;
								
								return labelText + " : " + Intl.NumberFormat().format(amount) + " '.$conf->global->MAIN_MONNAIE.' ( " + parseFloat(amount * 100 / total).toFixed(2) + " % '.$langs->trans("ofTotal").')";
							},
						}';
	}

	
	print 'var Chart_'.$id_graph.' = new Chart(	
								document.getElementById("'.$id_graph.'").getContext("2d"),
								{
									type: "'.$typeGraph.'",
									data: {
										labels: '.$labelList.',
										datasets: '.json_encode($datasetsArrayCorrected).'
									},
									options: {
										'.$eventClickText.'
										responsive: true,
										tooltips: {
											mode: "index",
											intersect: true,
										},
										tooltips: {'.$tooltipsPerso.'},
										legend: '.json_encode($legend).',
										title:{
											display: true,
											text:document.createElement("span").innerHTML="'.$titre.'"
										},';
										
	if($typeGraph =='bar'){
		
		print 	'scales: {
					yAxes: [{
						id:"leftY",
						type:"linear",
						position: "left",
						ticks: {
							beginAtZero:true,
							callback: function(value, index, values) {
								return Intl.NumberFormat().format(value);
								}	
						}
					}]
				}';
		
	}

	print '}});';
	
	print '</script>';

}


?>
<div class="tabBar">
	<form method="POST" id="searchFormList" class="listactionsfilter" action="">
		<input type="hidden" name="token" value="<?php  print newToken(); ?>">
		<div class="arearef valignmiddle centpercent">
			<td class="liste_titre nowraponall" align="center">
				<?php print $form->selectDate($datestartfiltre, 'datestart', 0, 0, 1, '', 1, 0); ?>
			</td>
			<td class="liste_titre nowraponall" align="center">
				<?php print $form->selectDate($dateendToShow, 'dateend', 0, 0, 1, '', 1, 0); ?>
			</td>
			<input type="submit" class="button" value="<?php print $langs->trans("show") ?>">
			<input type="submit" class="button" name="last12" value="<?php print $langs->trans("last12months") ?>">

			<?php 
				if(!empty($conf->global->EASYDASHBOARD_CHARGES_EXCLUDES)){
					print $langs->trans("EASYDASHBOARD_CHARGES_EXCLUDES") . " : " . $conf->global->EASYDASHBOARD_CHARGES_EXCLUDES; 
				}
			?>
		</div>
	</form>	

	
	<!--START : CA AND RESULTS***********************************************************************************************-->	
	<div class="underbanner clearboth" style="border-width: thick;"></div>
	<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
		<tbody>
			<tr>
				<td class="nobordernopadding valignmiddle col-title">
					<span class="fa fa-file-invoice-dollar marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
					<div class="titre inline-block"><b><?php print $langs->trans("REVENUE_AND_RESULTS") ?></b></div>
				</td>
				<td class="nobordernopadding valignmiddle">
					<br>
					<?php
					
					printBox(	$langs->trans("turnover"),
								price($chiffreAffaire, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
								'');					

					if(!empty($chiffreAffaire)){
						printBigBox(	$langs->trans("ProfitLoss"),
									price($benefices, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($benefices/$chiffreAffaire)*100,1).' %)',
									round(($benefices/$chiffreAffaire)*100,1).'% '.$langs->trans("ofCA")
									);
						
						if($advancedDisplay){
							printBigBox(	$langs->trans("MCV"),
										price($MCV, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($MCV/$chiffreAffaire)*100,1).' %)',
										round(($MCV/$chiffreAffaire)*100,1).'% '.$langs->trans("ofCA")
										);
						}
					}
					
					//PROJECTIONS
					printProjectionBox(	$langs->trans("turnoverProjection"),
								price($lastCumulCA, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
								'');
								
					printProjectionBox(	$langs->trans("ProfitLossProjection"),
									price($lastCumulMarge, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($lastCumulMarge/$lastCumulCA)*100,1).' %)',
									round(($lastCumulMarge/$lastCumulCA)*100,1).'% '.$langs->trans("ofCA")
									);

					?>
				</td>
			</tr>			
		</tbody>
	</table>
	
	
	
	<div class="fichecenter">
		<div class="underbanner clearboth"></div>
			
			<center>
				
				<?php
				
					//**************************************************************************************************
					//PRINT MAIN GRAPH
					//**************************************************************************************************

					$datasetsArray = array();
					
					$datasetsArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("facturesClientCumul")),
						'backgroundColor'=>'blue',
						'borderColor'=>'blue',
						'data'=>array_values($CAByPeriod['cumul_total']),
						'fill'=>false,
					];					
					
					$datasetsArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("depensesCumul")),
						'backgroundColor'=>'red',
						'borderColor'=>'red',
						'data'=>array_values($tabDepensesCumul),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("beneficesCumul")),
						'backgroundColor'=>'green',
						'borderColor'=>'green',
						'data'=>array_values($tabBeneficesCumul),
						'fill'=>false,
					];

					$datasetsArray[] = [
						'type'=>'bar',
						'stack'=>'Stack 0',
						'label'=>html_entity_decode($langs->trans("facturesClientMois")),
						'backgroundColor'=>'blue',
						'borderColor'=>'black',
						'data'=>array_values($CAByPeriod['total']),
						'fill'=>true,
					];

					if($advancedDisplay){
						$datasetsArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("CFMois")),
							'backgroundColor'=>'red',
							'borderColor'=>'black',
							'data'=>array_values($tabCFMois),
							'fill'=>true,
						];
						
						$datasetsArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("CVMois")),
							'backgroundColor'=>'orange',
							'borderColor'=>'black',
							'data'=>array_values($tabCVMois),
							'fill'=>true,
						];
					}else{
						$datasetsArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("depensesMois")),
							'backgroundColor'=>'red',
							'borderColor'=>'black',
							'data'=>array_values($tabDepensesMois),
							'fill'=>true,
						];
					}
					
					$datasetsArray[] = [
						'type'=>'bar',
						'stack'=>'Stack 2',
						'label'=>html_entity_decode($langs->trans("BeneficesMois")),
						'backgroundColor'=>'green',
						'borderColor'=>'black',
						'data'=>array_values($tabBeneficesMois),
						'fill'=>true,
					];
					
					$datasetsArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("Treso")),
						'backgroundColor'=>'black',
						'borderColor'=>'black',
						'data'=>array_values($tresoCumulMois),
						'fill'=>false,
						'hidden'=>true
					];
					
					
					//PROJECTIONS**********************************************************************
					$datasetsProjArray = array();
					
					$datasetsProjArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("ProjectionFacturesClientCumul")),
						'backgroundColor'=>'blue',
						'borderColor'=>'grey',
						'data'=>array_values($tabProjectionCumulCA),
						'fill'=>false,
						'hidden'=> false
					];
					
					$datasetsProjArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("ProjectionDepensesCumul")),
						'backgroundColor'=>'red',
						'borderColor'=>'grey',
						'data'=>array_values($tabProjectionCumulDepenses),
						'fill'=>false,
						'hidden'=> false
					];
					
					$datasetsProjArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("ProjectionMargeCumul")),
						'backgroundColor'=>'green',
						'borderColor'=>'grey',
						'data'=>array_values($tabProjectionCumulMarge),
						'fill'=>false,
						'hidden'=> false
					];
					
					$datasetsProjArray[] = [
						'type'=>'line',
						'label'=>html_entity_decode($langs->trans("ProjectionTreso")),
						'backgroundColor'=>'black',
						'borderColor'=>'grey',
						'data'=>array_values($tabProjectionTreso),
						'fill'=>false,
						'hidden'=> true
					];	
					
					$datasetsProjArray[] = [
						'type'=>'bar',
						'stack'=>'Stack 0',
						'label'=>html_entity_decode($langs->trans("ProjCACde")),
						'backgroundColor'=>"rgba(0, 0, 255, 0.3)",
						'borderColor'=>'black',
						'data'=>array_values($tabProjectionCACommandes),
						'fill'=>false,
						'hidden'=> false
					];
					
					$datasetsProjArray[] = [
						'type'=>'bar',
						'stack'=>'Stack 0',
						'label'=>html_entity_decode($langs->trans("ProjCAContrats")),
						'backgroundColor'=>"rgba(50, 60, 255, 0.3)",
						'borderColor'=>'black',
						'data'=>array_values($tabProjectionCAContrats),
						'fill'=>false,
						'hidden'=> false
					];
					
					if($advancedDisplay){
						$datasetsProjArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("MainFixCost")),
							'backgroundColor'=>"rgba(255, 0, 0, 0.3)",
							'borderColor'=>'black',
							'data'=>array_values($tabProjectionCoutsFixesMoyens),
							'fill'=>false,
							'hidden'=> false
						];
						
						$datasetsProjArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("ProjVariableCost")),
							'backgroundColor'=>"rgba(255, 165, 0, 0.3)",
							'borderColor'=>'black',
							'data'=>array_values($tabProjectionCoutsVariables),
							'fill'=>false,
							'hidden'=> false
						];
					}else{
						$datasetsProjArray[] = [
							'type'=>'bar',
							'stack'=>'Stack 1',
							'label'=>html_entity_decode($langs->trans("ProjVariableCost")),
							'backgroundColor'=>"rgba(255, 0, 0, 0.3)",
							'borderColor'=>'black',
							'data'=>array_values($tabProjectionCoutsVariables),
							'fill'=>false,
							'hidden'=> false
						];
					}
					
					$datasetsProjArray[] = [
						'type'=>'bar',
						'stack'=>'Stack 2',
						'label'=>html_entity_decode($langs->trans("ProjMarge")),
						'backgroundColor'=>"rgba(0, 255, 0, 0.3)",
						'borderColor'=>'black',
						'data'=>array_values($tabProjectionMarge),
						'fill'=>false,
						'hidden'=> false
					];
					

					printGraph(	'canvas_graph_main',
										'1000px',
										$langs->transnoentitiesnoconv("ResultsByMonth"),
										['display'=>true,'position'=>'right'],
										json_encode($period),
										$datasetsArray,
										false
									); 
				
				?>
				
		
			<button class="button" id="addDataset"><?php print $langs->trans("ShowProjection"); ?> <span class="classfortooltip fas fa-info-circle" style="padding: 0px; padding: 0px; padding-right: 3px !important;" title="<?php print $langs->trans("InfosProjection"); ?>"></span></button>
			
			<script>
			
				document.getElementById('addDataset').addEventListener('click', function() {

					var newDatasetArray = <?php print json_encode($datasetsProjArray); ?>;				

					for (var item in newDatasetArray) {
						//console.log (dataSet);
						Chart_canvas_graph_main.data.datasets.push(newDatasetArray[item]);
						
					}

					Chart_canvas_graph_main.update();
					
					$('#addDataset').hide();
					
					$('.projectionbox').show();
					
				});

			</script>
				
			</center>
		<div class="underrefbanner clearboth"></div>
	</div>
	
	<?php if(!empty($conf->projet->enabled)){ ?>
	<br>
	<div class="fichecenter">
	<div class="underbanner clearboth"></div>

			<center>
				
				<?php
				if(!empty($listIdProject) && !empty($conf->global->EASYDASHBOARD_USE_STAT_ON_PROJECTS)){

					//**************************************************************************************************
					//PRINT PROJECT GRAPH
					//**************************************************************************************************
					$datasetsArray = array();
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("turnover")),
						'stack'=>'Stack 0',
						'backgroundColor'=>'blue',
						'borderColor'=>'black',
						'data'=>array_values($tabCAByProjectSorted),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("cost")),
						'stack'=>'Stack 1',
						'backgroundColor'=>'red',
						'borderColor'=>'black',
						'data'=>array_values($tabfFourByProjectSorted),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("SalariesAndCharges")),
						'stack'=>'Stack 1',
						'backgroundColor'=>'indianred',
						'borderColor'=>'black',
						'data'=>array_values($tabsalariesAndChargesByProjectSorted),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("Loans")),
						'stack'=>'Stack 1',
						'backgroundColor'=>'crimson',
						'borderColor'=>'black',
						'data'=>array_values($tabLoanCostByProjectSorted),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("ExpenseReport")),
						'stack'=>'Stack 1',
						'backgroundColor'=>'darksalmon',
						'borderColor'=>'black',
						'data'=>array_values($tabexpenseReportByProjectSorted),
						'fill'=>false,
					];
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("MCV")),
						'stack'=>'Stack 2',
						'backgroundColor'=>'green',
						'borderColor'=>'black',
						'data'=>array_values($tabResultByProjectSorted),
						'fill'=>false,
					];

					
										
					printGraph(	'canvas_graph_projet',
										'1300px',
										$langs->transnoentitiesnoconv("TurnoverCostMCVByProject"),
										['display'=>true,'position'=>'right','hover'=>'true'],
										json_encode(array_values($titlesSorted)),
										$datasetsArray,
										false
									); 	
				}

				//print '<pre>'; print_r($datasetsArray); print '</pre>';exit();
				?>

			<button class="button" id="getProjectsCsv"><?php print $langs->trans("CreateCsvFile"); ?> <span class="classfortooltip fas fa-info-circle" style="padding: 0px; padding: 0px; padding-right: 3px !important;" ></span></button>
			
			<script>
			
				document.getElementById('getProjectsCsv').addEventListener('click', function() {

					let datas = <?php echo json_encode($datasetsArray) ?>;
					let titles = <?php echo json_encode(array_values($titlesSorted)) ?>;

					let csvContent = "data:text/csv;charset=utf-8,";

					let titleRow = "Project;";
					titleRow += titles.join(";");
					csvContent += titleRow + "\r\n";

					const titlesLength = titles.length;

					datas.forEach(function(rowArray) {
							
							let dataRow = rowArray.data;

							if(dataRow){
								if(titlesLength){
									dataRow.length = titlesLength;
								}

								let row = rowArray.label + ";";
								row += dataRow.join(";");
								csvContent += row + "\r\n";
							}
					});

					var encodedUri = encodeURI(csvContent);
					window.open(encodedUri);

					//console.log(datas, titles);

					
				});

			</script>
			
			</center>
		<div class="underrefbanner clearboth"></div>
	</div>
	<?php } ?>
	
	<?php
	if(!empty($listIdProject) && !empty($conf->global->EASYDASHBOARD_USE_STAT_ON_PROJECTS) && !empty($conf->projet->enabled)){
	?>
	<div class="fichecenter">
		<div class="">
			<div class="underbanner clearboth"></div>
			<table class="centpercent notopnoleftnoright">
				<tbody>					
					<tr>
						<td>
						
							<?php
							
								//**************************************************************************************************
								//PRINT PROJECT RESULT GRAPH
								//**************************************************************************************************

							
								$datasetsArray = array();
								
								$datasetsArray[] = [
									'label'=>html_entity_decode($langs->trans("MCV")),
									'backgroundColor'=>'green',
									'borderColor'=>'white',
									'data'=>array_values($tabResultByProjectSorted),
									'fill'=>false,
								];
															
								printGraph(	'canvas_graph_results_project',
													'100%',
													$langs->transnoentitiesnoconv("MCVByProject"),
													['display'=>false],
													json_encode(array_values($titlesSorted)),
													$datasetsArray,
													false,
													'horizontalBar'
												); 	
							?>							
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		

	<?php
	}
	?>
	

<!--START : TURNOVER BY CUSTOMER***********************************************************************************************-->
	<div class="fichecenter">
		<div class="fichehalfleft">
			<div class="underbanner clearboth"></div>
				<table class="centpercent notopnoleftnoright">
					<tbody>					
						<tr>
							<td>
		
							<?php
								//**************************************************************************************************
								//PRINT CLIENT CA GRAPH
								//**************************************************************************************************

								if(!empty($chiffreAffaire)){
								
									$datasetsArray = array();
									
									$datasetsArray[] = [
										'label'=>html_entity_decode($langs->trans("turnover")),
										'backgroundColor'=>$blueColors,
										'borderColor'=>'white',
										'data'=>array_values($CAByClient['t_total']),
										'fill'=>false,
									];
								
									printGraph(	'canvas_graph_CA',
														'800px',
														$langs->transnoentitiesnoconv("RevenuByCustomer"),
														['display'=>true],
														json_encode(array_keys($CAByClient['t_total'])),
														$datasetsArray,
														false,
														'pie'
													); 	
								}

							?>	
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="fichehalfright">
			<table class="centpercent notopnoleftnoright">
				<tbody>					
					<tr>
						<td>
		
							<?php
								//**************************************************************************************************
								//PRINT CLIENT MCV GRAPH
								//**************************************************************************************************

								if(!empty($chiffreAffaire)){
								
									$datasetsArray = array();
									
									$datasetsArray[] = [
										'label'=>html_entity_decode($langs->trans("MCV")),
										'backgroundColor'=>$greenColors,
										'borderColor'=>'white',
										'data'=>array_values($MCVByClient),
										'fill'=>false,
									];
								
									//print '<pre>'; print_r($MCVByClient); print '</pre>';exit();
									
									printGraph(	'canvas_graph_MCV_customer',
														'800px',
														$langs->transnoentitiesnoconv("MCVByCustomer"),
														['display'=>true],
														json_encode(array_keys($MCVByClient)),
														$datasetsArray,
														false,
														'pie'
													); 	

									
								}

							?>	
						</td>
					</tr>
				</tbody>
			</table>

		</div>
	</div>
	
<!--START : TOTAL COST***********************************************************************************************-->	
	<br>	
	<div class="underbanner clearboth" style="border-width: thick;"></div>
	<table class="centpercent notopnoleftnoright table-fiche-title">
		<tbody>
			<tr>
				<td class="nobordernopadding valignmiddle col-title">
					<span class="fa fa-money marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
					<div class="titre inline-block"><b><?php print $langs->trans("TOTAL_COST") ?></b></div>
				</td>
				<td class="nobordernopadding valignmiddle">
					<br>
					<?php
					
						if(!empty($chiffreAffaire)){
					
							printBigBox(	$langs->trans("TotalCost"),
										price($totalDepenses, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($totalDepenses/$chiffreAffaire)*100,1).' %)',
										round(($totalDepenses/$chiffreAffaire)*100,1).'% '.$langs->trans("ofCA")
										);
						}else{
							
							printBigBox(	$langs->trans("TotalCost"),
										price($totalDepenses, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										''
										);
						}
														
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="underbanner clearboth"></div>
	
	
	<div class="fichecenter">
		<div class="fichehalfleft">

				<?php
				
					//**************************************************************************************************
					//PRINT TOTAL COST GRAPH
					//**************************************************************************************************
					if(!empty($totalDepenses)){							
									
						$datasetsArray = array();
						
						$datasetsArray[] = [
							'label'=>html_entity_decode($langs->trans("cost")),
							'backgroundColor'=>'red',
							'borderColor'=>'red',
							'data'=>array_values($fFourTotalByFournisseur['t_total_ht']),
							'fill'=>false,
						];					
					
					
						printGraph(	'canvas_graph_total_cost',
											'100%',
											$langs->transnoentitiesnoconv("TOTAL_COST_BY_CUSTOMER"),
											['display'=>false],
											json_encode(array_keys($fFourTotalByFournisseur['t_total_ht'])),
											$datasetsArray,
											false
										);
					}
				
				?>

		</div>
		
		<div class="fichehalfright">
		
			<?php
				//**************************************************************************************************
				//PRINT TOTAL COST PIE GRAPH
				//**************************************************************************************************
				if(!empty($totalDepenses)){

					$datasetsArray = array();
					
					$datasetsArray[] = [
						'label'=>html_entity_decode($langs->trans("turnover")),
						'backgroundColor'=>$contrastedColors,
						'borderColor'=>'black',
						'data'=>[	round($totalFactFixe+$totalFactVariable,$constRoundNumber),
									round($totalSalaireFixe+$totalSalaireVariable,$constRoundNumber),
									round($totalChargesSocialesFixe+$totalChargesSocialesVariable,$constRoundNumber),
									round($totalEmpruntsFixe+$totalEmpruntsVariable,$constRoundNumber),
									round($totalNotesFraisFixe+$totalNotesFraisVariable,$constRoundNumber)
								],
						'fill'=>false,
					];
				
					printGraph(	'canvas_graph_total_cost_pie',
										'100%',
										$langs->transnoentitiesnoconv("TOTAL_COST_BY_TYPE"),
										['display'=>true,'position'=>'right'],
										json_encode([$langs->transnoentitiesnoconv("Provider"),$langs->transnoentitiesnoconv("Salaries"),$langs->transnoentitiesnoconv("Charges"),$langs->transnoentitiesnoconv("InterestAndInsurance"),$langs->transnoentitiesnoconv("ExpenseReport")]),
										$datasetsArray,
										false,
										'doughnut'
									); 	
				}
			?>
		
		</div>
	</div>
	
<!--END : TOTAL COST***********************************************************************************************-->		
	
	
<?php if($advancedDisplay){ ?>
	<!--START : FIX COST***********************************************************************************************-->	
		<br>	
		<div class="underbanner clearboth" style="border-width: thick;"></div>
		<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
			<tbody>
				<tr>
					<td class="nobordernopadding valignmiddle col-title">
						<span class="fa fa-money marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
						<div class="titre inline-block"><b><?php print $langs->trans("FIXED_COST") ?></b></div>
					</td>
					<td class="nobordernopadding valignmiddle">
						<br>
						<?php
						
							if(!empty($chiffreAffaire) && !empty($totalCoutsFixes)){
						
								printBigBox(	$langs->trans("FixedCosts"),
											price($totalCoutsFixes, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($totalCoutsFixes/$chiffreAffaire)*100,1).' %)',
											round(($totalCoutsFixes/$chiffreAffaire)*100,1).'% '.$langs->trans("ofCA")
											);
											
							}else{
								
								printBigBox(	$langs->trans("FixedCosts"),
											price($totalCoutsFixes, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
											''
											);
							}
							
								printBigBox(	$langs->trans("FixedCostsByMonth"),
											price($coutsFixesMoyens, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
											''
											);

											/*
								if(!empty($taskTimeByFG['tt_task_duration'])){
									printBigBox(	$langs->trans("TimeSpent"),
											convertSecondToTime($taskTimeByFG['sum_tt_task_duration'], 'allhourmin'),
											''
											);
								}
								*/

						?>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="underbanner clearboth"></div>
		
		<div class="fichecenter">
			<div class="fichehalfleft">

					<?php
					
						//**************************************************************************************************
						//PRINT FIX COST GRAPH
						//**************************************************************************************************
						if(!empty($totalCoutsFixes) && !empty($fFourFixesByFournisseur['t_total_ht'])){									
										
							$datasetsArray = array();
							
							$datasetsArray[] = [
								'label'=>html_entity_decode($langs->trans("cost")),
								'backgroundColor'=>'red',
								'borderColor'=>'red',
								'data'=>array_values($fFourFixesByFournisseur['t_total_ht']),
								'fill'=>false,
							];					
						
							printGraph(	'canvas_graph_CF',
												'100%',
												$langs->transnoentitiesnoconv("FIXE_COST_BY_CUSTOMER"),
												['display'=>false],
												json_encode(array_keys($fFourFixesByFournisseur['t_total_ht'])),
												$datasetsArray,
												false
											); 
						}
					?>

			</div>
			
			<div class="fichehalfright">
			
				<?php
					//**************************************************************************************************
					//PRINT FIX COST PIE GRAPH
					//**************************************************************************************************
					if(!empty($totalCoutsFixes)){
				
						$datasetsArray = array();
						
						$datasetsArray[] = [
							'label'=>html_entity_decode($langs->trans("turnover")),
							'backgroundColor'=>$contrastedColors,
							'borderColor'=>'black',
							'data'=>[	round($totalFactFixe,$constRoundNumber),
										round($totalSalaireFixe,$constRoundNumber),
										round($totalChargesSocialesFixe,$constRoundNumber),
										round($totalEmpruntsFixe,$constRoundNumber),
										round($totalNotesFraisFixe,$constRoundNumber)
									],
							'fill'=>false,
						];
					
						printGraph(	'canvas_graph_fix_cost_pie',
											'100%',
											$langs->transnoentitiesnoconv("FIXE_COST_BY_TYPE"),
											['display'=>true,'position'=>'right'],
											json_encode([$langs->transnoentitiesnoconv("Provider"),$langs->transnoentitiesnoconv("Salaries"),$langs->transnoentitiesnoconv("Charges"),$langs->transnoentitiesnoconv("InterestAndInsurance"),$langs->transnoentitiesnoconv("ExpenseReport")]),
											$datasetsArray,
											false,
											'doughnut'
										); 	
					}
					
				?>
			
			
			</div>
			
			
		</div>
	<!--END : FIX COST***********************************************************************************************-->		



	<!--START : VARIABLE COST***********************************************************************************************-->	
		<br>	
		<div class="underbanner clearboth" style="border-width: thick;"></div>
		<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
			<tbody>
				<tr>
					<td class="nobordernopadding valignmiddle col-title">
						<span class="fa fa-money marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
						<div class="titre inline-block"><b><?php print $langs->trans("VARIABLES_COST") ?></b></div>
					</td>
					<td class="nobordernopadding valignmiddle">
						<br>
						<?php
						
							if(!empty($chiffreAffaire) && !empty($totalCoutsVariables)){
						
								printBigBox(	$langs->trans("VariableCosts"),
											price($totalCoutsVariables, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.round(($totalCoutsVariables/$chiffreAffaire)*100,1).' %)',
											round(($totalCoutsVariables/$chiffreAffaire)*100,1).'% '.$langs->trans("ofCA")
											);
							}else{
								
								printBigBox(	$langs->trans("VariableCosts"),
											price($totalCoutsVariables, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
											''
											);
							}
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="underbanner clearboth"></div>
		
		<div class="fichecenter">
			<div class="fichehalfleft">

					<?php
					
						//**************************************************************************************************
						//PRINT VARIABLE COST GRAPH
						//**************************************************************************************************									
						if(!empty($totalCoutsVariables) && !empty($fFourVariablesByFournisseur['t_total_ht'])){
								
							$datasetsArray = array();
							
							$datasetsArray[] = [
								'label'=>html_entity_decode($langs->trans("cost")),
								'backgroundColor'=>'red',
								'borderColor'=>'red',
								'data'=>array_values($fFourVariablesByFournisseur['t_total_ht']),
								'fill'=>false,
							];
						
							printGraph(	'canvas_graph_CV',
												'100%',
												$langs->transnoentitiesnoconv("VARIABLE_COST_BY_CUSTOMER"),
												['display'=>false],
												json_encode(array_keys($fFourVariablesByFournisseur['t_total_ht'])),
												$datasetsArray,
												false
											); 
						}
					?>

			</div>
			
			<div class="fichehalfright">
			
				<?php
					//**************************************************************************************************
					//PRINT VARIABLE COST PIE GRAPH
					//**************************************************************************************************
					if(!empty($totalCoutsVariables)){
				
						$datasetsArray = array();
						
						$datasetsArray[] = [
							'label'=>html_entity_decode($langs->trans("turnover")),
							'backgroundColor'=>$contrastedColors,
							'borderColor'=>'black',
							'data'=>[	round($totalFactVariable,$constRoundNumber),
										round($totalSalaireVariable,$constRoundNumber),
										round($totalChargesSocialesVariable,$constRoundNumber),
										round($totalEmpruntsVariable,$constRoundNumber),
										round($totalNotesFraisVariable,$constRoundNumber)
									],
							'fill'=>false,
						];
					
						printGraph(	'canvas_graph_variable_cost_pie',
											'100%',
											$langs->transnoentitiesnoconv("VARIABLE_COST_BY_TYPE"),
											['display'=>true,'position'=>'right'],
											json_encode([$langs->transnoentitiesnoconv("Provider"),$langs->transnoentitiesnoconv("Salaries"),$langs->transnoentitiesnoconv("Charges"),$langs->transnoentitiesnoconv("InterestAndInsurance"),$langs->transnoentitiesnoconv("ExpenseReport")]),
											$datasetsArray,
											false,
											'doughnut'
										); 
					}
				
				?>
			
			
			</div>
		</div>
	<!--END : VARIABLE COST***********************************************************************************************-->
<?php } ?>

<?php if (!empty($totalCapitalEmprunte) && !empty($conf->loan->enabled)){ ?>
<!--START : DETTE***********************************************************************************************-->	
	<br>	
	<div class="underbanner clearboth" style="border-width: thick;"></div>
	<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
		<tbody>
			<tr>
				<td class="nobordernopadding valignmiddle col-title">
					<span class="fa fa-coins marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
					<div class="titre inline-block"><b><?php print $langs->trans("LOAN") ?></b></div>
				</td>
				<td class="nobordernopadding valignmiddle">
					<br>
					<?php	

						printBox(	$langs->trans("totalLoanRestToPay"),
									price($totalCapitalEmprunte - $totalCapitalRembourse, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');

						printBox(	$langs->trans("totalLoans"),
									price($totalCapitalEmprunte, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
									
						printBox(	$langs->trans("totalLoanPaid"),
									price($totalCapitalRembourse, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
					
					?>
				</td>
			</tr>
		</tbody>
	</table>
<!--END : DETTE***********************************************************************************************-->	
<?php } ?>



<!--START : TVA***********************************************************************************************-->	
	<br>	
	<div class="underbanner clearboth" style="border-width: thick;"></div>
	<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
		<tbody>
			<tr>
				<td rowspan="2" class="nobordernopadding valignmiddle col-title">
					<span class="fa fa-percent marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
					<div class="titre inline-block"><b><?php print strtoupper($langs->trans("VAT")) ?></b></div>
				</td>
				<td class="nobordernopadding valignmiddle">
					<br>
					<?php
					
						printBox(	$langs->trans("VATCalculated"),
									price($tvaCalculee, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
									
						printBox(	$langs->trans("dueVAT"),
									price($tvaDueCalculee, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
									
						printBox(	$langs->trans("deductibleVAT"),
									price($tvaDeductibleCalculee, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
					
					?>
				</td>
			</tr>
			<tr>
				<td>
					<?php
					
						printBox(	$langs->trans("VATPaid"),
									price($totalTvaPayee, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
					
					?>
				</td>
			</tr>
		</tbody>
	</table>
<!--END : TVA***********************************************************************************************-->		
		
	<?php if(! empty($conf->commande->enabled)){ ?>

		<!--START : RUNNING ORDERS***********************************************************************************************-->	
		<br>	
		<div class="underbanner clearboth" style="border-width: thick;"></div>
		<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
			<tbody>
				<tr>
					<td rowspan="2" class="nobordernopadding valignmiddle col-title">
						<span class="fa fa-shopping-cart marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
						<div class="titre inline-block"><b><?php print $langs->trans("RunningOrders") ?></b></div>
					</td>
					<td class="nobordernopadding valignmiddle">
						<br>
						<?php
						
							printBox(	$langs->trans("RestToInvoice"),
										price($totalCdeResteAFacturer, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										'');
																				
							printBox(	$langs->trans("RestToSpend"),
										price($totalCdeRestToSpent, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										'');
						
						?>
					</td>
				</tr>
				<tr>
					<td>
						<?php
						
							printBox(	$langs->trans("turnover"),
										price($totalcommandes, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										'');
						
							printBox(	$langs->trans("Marge"),
										price($margeTotaleCde, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										'');
										
							printBox(	$langs->trans("TxMarque"),
										$txMarqueGlobalCde.'%',
										'');
						
						?>
					</td>
				</tr>
			</tbody>
		</table>
		

		<?php if(!empty($commandesEnCours)){ ?>
		<div class="underbanner clearboth"></div>
		
		<table class="border tableforfield" width="100%">
			<tbody>
				<tr class="liste_titre">
					<th><?php print $langs->trans("Ref") ?></th>
					<th><?php print $langs->trans("Note") ?></th>
					<th><?php print $langs->trans("Project") ?></th>
                    <th><?php print $langs->trans("Customer") ?></th>
					<th><?php print $langs->trans("Ref_customer") ?></th>
					<th><?php print $langs->trans("Total_ca") ?></th>
					<th><?php print $langs->trans("Marge") ?></th>
					<th><?php print $langs->trans("RestToInvoice") ?></th>
					<th><?php print $langs->trans("RestToSpend") ?> <i class="fas fa-info-circle" title="<?php print $langs->trans("InfosAlreadySpent"); ?>"></i></th>
					<th><?php print $langs->trans("Facture") ?></th>
					<th><?php print $langs->trans("Status") ?></th>
				</tr>
				
			<?php 
				
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

            //print '<pre>'; print_r($commandesEnCours); print '</pre>';exit();
			
			foreach($commandesEnCours as $val)
			{
				$myCde = new Commande($db);
				$myCde->fetch($val['rowid']);

				$generic_commande = new Commande($db);
				
				print '<tr>';

					print '<td>'.$myCde->getNomUrl(1).'</td>';
					print '<td>'.$myCde->note_public.'</td>';
                    print '<td>'.$val['projectName'].'</td>';
					print '<td>'.$val['client'].'</td>';
					print '<td>'.$val['ref_client'].'</td>';
					print '<td>'.price($val['total_ht'], 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).'</td>';					
					
					$marge = $val['total_ht']-$val['buy_price_ht'];
					
					$restToSpend = $val['buy_price_ht'] - $val['spent'];
					
					$tx_marque = round($marge/$val['total_ht']*100,1);
					
					print '<td><b>'.price($marge, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.$tx_marque.' %)<b></td>';
					print '<td>'.price($val['rest_to_invoice'], 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).'</td>';
					print '<td>'.price($restToSpend, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE)
								.' '.$langs->trans("on").' '.price($val['buy_price_ht'], 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE)
								.' ('.$val['percent_expense'].' % '.$langs->trans("alreadySpent").')</td>';
				
					// Billed
					print '<td class="center">'.yn($val['facture']).'</td>';

					// Status
					print '<td class="nowrap center">'.$generic_commande->LibStatut($val['fk_statut'], $val['facture'], 5, 1).'</td>';

				print '</tr>';
			}
			?>
			</tbody>
		</table>
	<?php
		}
	}
	?>
		
		

	<?php if(! empty($conf->contrat->enabled)){ ?>

		<!--START : RUNNING CONTRACTS***********************************************************************************************-->	
		<br>	
		<div class="underbanner clearboth" style="border-width: thick;"></div>
		<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
			<tbody>
				<tr>
					<td class="nobordernopadding valignmiddle col-title">
						<span class="fa fa-file-contract marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
						<div class="titre inline-block"><b><?php print $langs->trans("RunningContracts") ?></b></div>
					</td>
					<td class="nobordernopadding valignmiddle">
					
						<br>
						<?php
							
							$totalContratMensuel = $totalcontrat/12;
							printBox(	$langs->trans("turnover"),
										price($totalcontrat, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										price($totalContratMensuel, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' '.$langs->trans("byMonth"));
							
							$margeTotaleContratMensuel = $margeTotaleContrat/12;
							printBox(	$langs->trans("Marge"),
										price($margeTotaleContrat, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
										price($margeTotaleContratMensuel, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' '.$langs->trans("byMonth"));
										
							printBox(	$langs->trans("TxMarque"),
										$txMarqueGlobalContrat.'%',
										'');
						
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="underbanner clearboth"></div>


		<table class="border tableforfield" width="100%">
			<tbody>
				<tr class="liste_titre">
					<th><?php print $langs->trans("Ref") ?></th>
					<th><?php print $langs->trans("Societe") ?></th>
					<th><?php print $langs->trans("period_contract") ?> <i class="fas fa-info-circle" title="<?php print $langs->trans("InfosPeriodContracts"); ?>"></i></th>
					<th><?php print $langs->trans("Label") ?></th>
					<th><?php print $langs->trans("Annual_Total_ca") ?></th>
					<th><?php print $langs->trans("Annual_Marge") ?> <i class="fas fa-info-circle" title="<?php print $langs->trans("InfosMargeContracts"); ?>"></i></th>
				</tr>
				
			<?php 
			
			if(!empty($contratsEnService)){
				require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
				
				$lastRef = '';
				
				foreach($contratsEnService as $val)
				{
					$myContrat = new Contrat($db);
					$myContrat->fetch($val['fk_contrat']);
					$extrafields = new ExtraFields($db);
					$extralabels=$extrafields->fetch_name_optionals_label($myContrat->table_element);
															
					if ($lastRef <> $val['ref'] && $lastRef <> ''){
						
						$totalLine .= '<script type="text/javascript">
									jQuery(document).ready(function(){
										
										jQuery(".'.$lastRef.'_line").hide();
										
										jQuery("#'.$lastRef.'_total span").click(function(){
																		
										   if (jQuery(".'.$lastRef.'_line").is(":hidden")) {
											   
											   jQuery(".'.$lastRef.'_line").show(300);
											   jQuery("#'.$lastRef.'_total>span").addClass("fa-minus-square").removeClass("fa-plus-square");
										   } else {
											   
											   jQuery(".'.$lastRef.'_line").hide(300);
											   jQuery("#'.$lastRef.'_total>span").addClass("fa-plus-square").removeClass("fa-minus-square");
										   }

										});
									});
								</script>';
						
						$totalLine .= '<tr><td id="'.$lastRef.'_total" ><span class="cursorpointer fa fa-plus-square"></span> '.$lastNomUrl.'</td>';
						
						$totalLine .= '<td><b>'.$lastSociete.'</b></td>';
						$totalLine .= '<td><b>'.$lastPeriod.'</b></td>';
						$totalLine .= '<td><b>'.$lastRefClient.'</b></td>';
						
						$totalLine .= '<td><b>'.price($totalCaPerContrat, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).'</b></td>';
						
						$tx_marque = round($totalMargePerContrat/$totalCaPerContrat*100,1);
						$totalLine .= '<td><b>'.price($totalMargePerContrat, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).' ('.$tx_marque.' %)</td>';
						
						$totalLine .= '</tr>';
						
						print $totalLine;
						print $linesDetail;
						
						$totalLine = '';
						$linesDetail = '';
						$totalCaPerContrat = 0;
						$totalMargePerContrat = 0;
						
					}
					
					
					$linesDetail .= '<tr class="'.$val['ref'].'_line">';
						
						$linesDetail .= '<td></td>';
						$linesDetail .= '<td></td>';
						$linesDetail .= '<td></td>';

						$linesDetail .= '<td>'.dol_trunc($val['description'],50).'</td>';
						
						($myContrat->array_options['options_period_contract'])?$periodContract = $myContrat->array_options['options_period_contract']:$periodContract = $defautPeriodContract;
						
						$rapportCaAnnual = 12/$periodContract;
						$total_ca_annual = $val['qty']*$val['price_ht']*$rapportCaAnnual;
						
						$linesDetail .= '<td title="('.$rapportCaAnnual.')x'.$val['qty'].'x'.price($val['price_ht'], 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).'">'.price($total_ca_annual, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).'</td>';
						
						$revientAnnuel = $val['qty']*$val['revient']*$rapportCaAnnual;
						
						$marge_annuelle = $total_ca_annual-$revientAnnuel;
						
						if(!empty($total_ca_annual)){
							
							$tx_marque = round($marge_annuelle/$total_ca_annual*100,1);
							$linesDetail .= '<td>'.price($marge_annuelle, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).' ('.$tx_marque.' %)</td>';
						}else{
							$linesDetail .= '<td>'.price($marge_annuelle, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).'</td>';
						}
						
					$linesDetail .= '</tr>';
					
					
					$totalCaPerContrat += $total_ca_annual;
					$totalMargePerContrat += $marge_annuelle;
					
					
					$lastRef = $val['ref'];
					$lastNomUrl = $myContrat->getNomUrl(1);
					$lastSociete = $val['societe'];
					$lastRefClient = $myContrat->ref_customer;
					$lastPeriod = $extrafields->showOutputField('period_contract', $myContrat->array_options['options_period_contract'], '', $myContrat->table_element);
				}
				
				//Dernier contrat à afficher
				$totalLine .= '<script type="text/javascript">
							jQuery(document).ready(function(){
								
								jQuery(".'.$lastRef.'_line").hide();
								
								jQuery("#'.$lastRef.'_total").click(function(){
																				
								   if (jQuery(".'.$lastRef.'_line").is(":hidden")) {
									   
									   jQuery(".'.$lastRef.'_line").show(300);
									   jQuery("#'.$lastRef.'_total>span").addClass("fa-minus-square").removeClass("fa-plus-square");
								   } else {
									   
									   jQuery(".'.$lastRef.'_line").hide(300);
									   jQuery("#'.$lastRef.'_total>span").addClass("fa-plus-square").removeClass("fa-minus-square");
								   }

								});
							});
						</script>';
				
				$totalLine .= '<tr><td id="'.$lastRef.'_total" ><span class="cursorpointer fa fa-plus-square"></span> '.$lastNomUrl.'</td>';
		
				$totalLine .= '<td><b>'.$lastSociete.'</b></td>';
				$totalLine .= '<td><b>'.$lastPeriod.'</b></td>';
				$totalLine .= '<td><b>'.$lastRefClient.'</b></td>';
				
				$totalLine .= '<td><b>'.price($totalCaPerContrat, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).'</b></td>';
				
				$tx_marque = round($totalMargePerContrat/$totalCaPerContrat*100,1);
				$totalLine .= '<td><b>'.price($totalMargePerContrat, 0, $langs, 0, -1, 2, $conf->global->MAIN_MONNAIE).' ('.$tx_marque.' %)</b></td>';
				
				$totalLine .= '</tr>';
				
				print $totalLine;
				print $linesDetail;
			}

			?>
			</tbody>
		</table>
	<?php
	}
	?>

		
		
		
	<!--START : LIST OF OPPORTUNITIES***********************************************************************************************-->	
	<br>	
	<div class="underbanner clearboth" style="border-width: thick;"></div>
	<table class="centpercent table-fiche-title" style="margin-bottom: 15px">
		<tbody>
			<tr>
				<td class="nobordernopadding valignmiddle col-title">
					<span class="fa fa-briefcase marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>
					<div class="titre inline-block"><b><?php print $langs->trans("Opportunities"); ?></b></div>
				</td>
				<td class="nobordernopadding valignmiddle">
				
					<br>
					<?php
									
						printBox(	$langs->trans("turnover"),
									price($totalCAOpportunities, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
														
						printBox(	$langs->trans("Marge"),
									price($margeTotaleOpportunities, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE),
									'');
									
						printBox(	$langs->trans("TxMarque"),
									$txMarqueGlobalOpportunities.'%',
									'');
					
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="underbanner clearboth"></div>


	<table class="border tableforfield" width="100%">
		<tbody>
			<tr class="liste_titre">
				<th><?php print $langs->trans("Ref") ?></th>
				<th><?php print $langs->trans("Societe") ?></th>
				<th><?php print $langs->trans("Ref_customer") ?></th>
				<th><?php print $langs->trans("Total_ca") ?></th>
				<th><?php print $langs->trans("revient") ?></th>
				<th><?php print $langs->trans("Marge") ?></th>
			</tr>
			
		<?php 
		if(!empty($opportunity80)){
			foreach($opportunity80 as $val)
			{
				$myPropal = new Propal($db);
				$myPropal->fetch($val['rowid']);
				
				$marge = $val['total_ht']-$val['revient'];
				$txMarque = round($marge/$val['total_ht']*100,1);
				
				print '<tr>';
					print '<td>'.$myPropal->getNomUrl(1).'</td>';
					print '<td>'.$val['societe'].'</td>';
					print '<td>'.$val['ref_client'].'</td>';
					print '<td>'.price($val['total_ht'], 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).'</td>';
					print '<td>'.price($val['revient'], 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).'</td>';
					print '<td><strong>'.price($marge, 0, $langs, 0, -1, $constRoundNumber, $conf->global->MAIN_MONNAIE).' ('.$txMarque.'%)</strong></td>';
				print '</tr>';
			}
		}
		?>
		</tbody>
	</table>
	
	<div class="clearboth"></div>

</div>



<script>

window.onload = function() {

	$(".graph").click(function(evt){
		
		var chartId = $(this).attr('id');
		var chartName = 'Chart_' + chartId;	
		
		var activePoints = window[chartName].getElementsAtEvent(evt);
		
		
		
		if(activePoints.length > 0)
		{
			//get the internal index of slice in pie chart
			var clickedElementindex = activePoints[0]["_index"];

			//get specific label by index 
			var label = window[chartName].data.labels[clickedElementindex];

			console.log(label);

			if (chartId == 'canvas_graph_projet' || chartId == 'canvas_graph_results_project'){
				
				var idProjectSorted = <?php print json_encode(array_values($idProjectSorted)); ?>;
				
				link_base = <?php print '"'.dol_buildpath("/projet/element.php", 1).'"' ?>;
				link_label = "?id="+idProjectSorted[clickedElementindex];
				
				window.open(link_base+link_label, "_blank");
			}
			
			if (chartId == 'canvas_graph_CA'){
				
				link_base = <?php print '"'.dol_buildpath("/compta/facture/list.php?leftmenu=customers_bills&search_year=".date("Y",$tmstpEnd), 1).'"' ?>;
				link_label = "&search_societe="+label;
				
				window.open(link_base+link_label, "_blank");
			}
			
			if (chartId == 'canvas_graph_CF' || chartId == 'canvas_graph_CV'){
				
				link_base = <?php print '"'.dol_buildpath("/fourn/facture/list.php?leftmenu=suppliers_bills&year=".date("Y",$tmstpEnd), 1).'"' ?>;
				link_label = "&search_company="+label;
				
				window.open(link_base+link_label, "_blank");
			}
		}

		
	});

};

</script>


<?php

// End of page
llxFooter();
$db->close();

function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}