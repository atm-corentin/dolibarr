<?php
$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory


dol_include_once('/advancedhrm/class/advancedhrm.favoris.class.php');
//var_dump(dol_include_once('/advancedhrm/class/advancedhrm.favoris.class.php'));


$trajet_id 	= GETPOST('id','int');
$fk_user 	= GETPOST('fk_user','int');
$depart 	= GETPOST('origins','alpha');
$arrivee 	= GETPOST('destinations','alpha');
$action 	= GETPOST('action','alpha');



switch ($action){

	case  "add" :

		return addFavoris($fk_user,$depart,$arrivee);
		break;

	case  "delete" :
		return deleteFavoris($trajet_id);
		break;
	case "changeTtc" :
		//var_dump("changeTtc");
		break;

	case "computeHtUnit" :
		getHtPrice();
		break;

	default : // List
		listeFavoris($fk_user);
		break;

}

function addFavoris($fk_user,$depart,$arrivee){
	global $db, $user, $conf;

	$advancedhrm_favoris = new Advancedhrm_Favoris($db);

	$advancedhrm_favoris->fk_user=$fk_user;
	$advancedhrm_favoris->adresse_depart=$depart;
	$advancedhrm_favoris->adresse_arrivee=$arrivee;
	$advancedhrm_favoris->entity=$conf->entity;

	$result = $advancedhrm_favoris->create($user);

	return $result;
}
/**
 * @param $trajet_id
 * @return float|int
 */
function deleteFavoris($trajet_id){
			global $user, $db;

			$advancedhrm_favoris = new Advancedhrm_Favoris($db);
			$advancedhrm_favoris->fetch($trajet_id);
			$result=$advancedhrm_favoris->delete($user);

			return $result;
}

/**
 * @param $fk_user
 * @return void
 */
function listeFavoris($fk_user){
			global $conf, $db, $user;
			$sql = "SELECT f.rowid, f.adresse_depart, f.adresse_arrivee
		FROM ".MAIN_DB_PREFIX."advancedhrm_favoris as f
		WHERE f.entity IN (".$conf->entity.")
		AND f.fk_user = ".$fk_user."
		ORDER BY f.rowid DESC";
			//var_dump($sql);exit;
			$tabFavoris="<table class='liste formdoc noborder' style='width:100%;box-shadow: 0 0 0 #CCCCCC !important;'>
				<thead>
					<tr class='liste_titre'>
						<th>Adresse de départ</th>
						<th>Adresse d'arrivée</th>
						<th align='center'>Actions</th>
					</tr>
				</thead>
			<tbody>";

			$resql=$db->query($sql);
			if ($resql){
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num){
					while ($i < $num){
						$obj = $db->fetch_object($resql);
						if ($obj){
							$trajet_id=$obj->rowid;
							$depart=$obj->adresse_depart;
							$arrivee=$obj->adresse_arrivee;

							$tabFavoris.= "<tr xmlns=\"http://www.w3.org/1999/html\">
					<td>" .$depart."</td>
					<td>".$arrivee."</td>
					<td align='center'>
						<a href='javascript:selectFavoris(\"".$depart."\",\"".$arrivee."\");' class='selectFavoris'>
							<i class='fa fa-square-o' aria-hidden='true' style='padding:5px;'></i><span>Sélectionner</span>
						</a>
						<a href='javascript:supprimerFavoris(".$trajet_id.");' >
							<i class='fa fa-trash-o' aria-hidden='true' style='padding:5px;'></i><span>Supprimer</span>
						</a>
					</td>
				</tr>";
						}
						$i++;
					}
					$tabFavoris.="</tbody></table>";
				}else{
					$tabFavoris="<p style='text-align:center'>Aucun favoris enregistré.</p>";
				}
			}

			print $tabFavoris;
}

//@todo impediment sur la façon de calculer
/**
 * @return void
 */
function getHtPrice(){
	global $db;
	//var_dump($_REQUEST);
	$fk_cat = GETPOSTISSET('fk_cat','int')  ? GETPOST('fk_cat','int') : 0;
	$fk_user = GETPOSTISSET('fk_user','int') ?   GETPOST('fk_user','int') : $user->id;
	$affectedUser = (GETPOST('affectedUser','int'));
	$id = GETPOST('id','int');
	$kms = GETPOST('kms','int');

	/** ************************************************************************************
	// cumul kilometrique de l'année en cours pour le user selectionné dans la note de frais
	/** ************************************************************************************ **/

	$sql = " SELECT * FROM ".MAIN_DB_PREFIX."expensereport";
	$sql .= " WHERE fk_user_author =".$affectedUser;
	//var_dump($sql);
	$resql = $db->query($sql);

	if ($resql){
		$cumulKms = 0;
		$year = date("Y");
		while($obj = $db->fetch_object($resql)){
			// selection de la qty dans des lignes qui sont de type frais kilometriques et qui sont dans l'année en cours
			$sqlline  = " SELECT qty FROM ".MAIN_DB_PREFIX."expensereport_det";
			$sqlline .= " WHERE fk_expensereport=".$obj->rowid;
			$sqlline .= " AND fk_c_type_fees = ".$fk_cat;
			$sqlline .= " AND YEAR(date) = ".$year;

			//var_dump($sqlline);
			$resqlLine = $db->query($sqlline);
			if ($resqlLine){
				$objLine = $db->fetch_object($resqlLine);
				$cumulKms += $objLine->qty;
			} else{
				var_dump("line ". $resqlLine);
			}

		}
	}else{
		var_dump($resql);
	}

	//var_dump("cumul : ". $cumulKms);
	// cumul kilometrique de l'année en cours pour le user selectionné dans la note de frais
	// selection des tranches pour la catégorie de vehicule selectionné
	require_once DOL_DOCUMENT_ROOT .'/expensereport/class/expensereport_ik.class.php';
	$exp = new ExpenseReportIk($db);
	$ranges = $exp->getRangesByCategory($fk_cat);

	$Tr = array();
	foreach ($ranges as $range){
		$r  = new stdClass();
		$r->coef = $range->coef;
		$r->fk_range = $range->fk_range;
		$Tr[] = $r;
	}
	echo  json_encode($Tr);


	// en fonction de la catégorie de vehicule et du max kilometre effectué par l'utilisateur
	// je devrais en deduire (sql) le coef à appliquer via range_ik dans la table llx_c_exp_tax_range

	/**
	 	select rowid from llx_c_exp_tax_range where fk__c_exp_tax_cat = (cat_selectionné) and cumul <= range_ik
		and range_ik > 0
	 *
	 *
	 */


	// si cumul < range

}

/**
 * @return void
 */
function test(){
	global $db, $langs, $mysoc;
	$response = new stdClass();
	$fk_cat = GETPOSTISSET('fk_cat','int')  ? GETPOST('fk_cat','int') : 0;
	$fk_user = GETPOSTISSET('fk_user','int') ?   GETPOST('fk_user','int') : $user->id;


	$indexes = array();
	$indexes_2 = array();
	$rates = array();
	$kmExpId = 0;
	$coefs = array();
	$offsets = array();
	$ranges = array();
	$c_pays = 'c_pays';
	if((float) DOL_VERSION >= 3.7) {
		$c_pays = 'c_country';
	}




	$droitQty = $user->rights->ndfp->myactions->fraiskm ? $user->rights->ndfp->myactions->fraiskm : "0";
// on sélectionne toutes les TVAs
	$sql  = " SELECT e.rowid, e.code, e.fk_tva, t.taux, e.fk_tva_2";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_exp e";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva t ON t.rowid = e.fk_tva";
	$sql .= " WHERE e.active = 1 AND e.entity IN (0,".(! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)?"1,":"").$conf->entity.")";
	$sql .= " ORDER BY e.label ASC";
//var_dump($sql);

	$result = $db->query($sql);


	if ($result)
	{
		$num = $db->num_rows($result);

		if ($num)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $db->fetch_object($result);

				$indexes[$i] = $obj->fk_tva;
				$indexes_2[$i] = $obj->fk_tva_2;
				//$rates[$i] = (1 - $obj->taux/100);

				if ($obj->code == 'EX_KME'){
					$kmExpId = $obj->rowid;
				}
			}
			$response->indexes['label'] = 'indexs';
			$response->indexes['data'] = $indexes;
			$response->indexes_2['label'] = 'indexs2';
			$response->indexes_2['data'] = $indexes_2;
			$response->indexes_2 = $indexes_2;
		}
		$db->free($result);
	}

	$userSelected = new User($db);
	$result  = $userSelected->fetch($fk_user,$fk_cat);
	//var_dump($fk_user);
	//var_dump($fk_cat);
	if ($result > 0 ){
		// on selectionne l'utilisateur pour lequel on desire créer une note de frais
		$droitMaxRangCommerciaux = (int)$userSelected->rights->ndfp->myactions->maxRange;
		$droitMiddleRangCommerciaux =(int)$userSelected->rights->ndfp->myactions->middleRange;
	}else{
		setEventMessages($langs->trans("UserNotSelectedndfp"),$userSelected->errors,'errors');
	}

	$sql  = " SELECT r.range_ik, t.offset, t.coef";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_range r ON r.rowid = t.fk_range";
	$sql .= " WHERE r.active = 1 AND t.fk_cat = ".$fk_cat;
	$sql.= " ORDER BY r.range_ik ASC";
//var_dump($sql);
	$result = $db->query($sql);


	if ($result)
	{
		$num = $db->num_rows($result);

		if ($num)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $db->fetch_object($result);

				$coefs[$i] = $obj->coef;
				$offsets[$i] = $obj->offset;
				$ranges[$i] = $obj->range;

			}
			$response->coefs['label'] = 'coefs';
			$response->coefs['data'] = $coefs;


			$response->offsets['label'] = 'offsets';
			$response->offsets['data'] = $offsets;
			$response->ranges['label'] = 'ranges';
			$response->ranges['data'] = $ranges;
		}
		$db->free($result);
	}

// Load TVA, use id instead of value
	$sql  = "SELECT DISTINCT t.taux, t.rowid, t.recuperableonly";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX.$c_pays." as p";
	$sql.= " WHERE t.fk_pays = p.rowid";
	$sql.= " AND t.active = 1";
	$sql.= " AND p.code IN ('".$mysoc->country_code."')";
	$sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC";
//var_dump($sql);
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		if ($num)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $db->fetch_object($result);

				$rates[$i] = price2num(1 + $obj->taux/100);
			}
			$response->rates['label'] = 'rates';
			$response->rates['data'] = $rates;
		}

		$db->free($result);

	}


	echo  json_encode($response);
}




