<?php

class DateTool
{
	public $db;
	public $TJourTrans;
	public $TMonthTrans;
	public $TColorType;
	public $TdayOff = array() ;
	public const DAY_LENGTH_TIME_FORMAT = 86400;
	public const WEEKEND_COLOR = 0;
	public const DAYOFF_COLOR = 6;

	public const HALFDAY_MORNING = 0;
	public const HALFDAY_AFTERNOON = 1;

	// Inspiration num_public_holiday() date.lib.php
 	public const NBJOUFORJOUROFFWITHRULES = array(
		'eastermonday' => 1,
		'goodfriday' => 2,
		'ascension' => 39,
		'pentecote' => 49,
		'pentecost' => 50,
		'pentecotemonday' => 49,
		'viernessanto' => 2,
		'fronleichnam' => 60,
	);

	// INTERNAL COLORS
	public $TColor = array(
		0=>array(
			'color'=> '000000',
			'code'=> 'WEEKEND')
	,6=>array(
			'color'=> '020302',
			'code'=> 'DAYOFF')
	);

	function __construct($db,$langs , $mysoc){
		global $conf;
		$this->db = $db;
		if(getDolGlobalString('ADVANCEDHRM_ABSENCE_WEEKEND_SHOW')) {
			$this->TColor[0]['color'] = ltrim(getDolGlobalString('ADVANCEDHRM_ABSENCE_WEEKEND_SHOW'), '#');
		}
		$this->setDayLabels($langs);
		$this->setMonthLabels($langs);

		$this->setColors($mysoc);

		$this->setDayOff($mysoc);

	}

	/**
	 *  remplie un array avec les traductions des jours de la semaine
	 * @return void
	 */
	private function setDayLabels($langs){
		$this->TJourTrans=array(
		1=>substr($langs->trans('Monday'),0,1)
		,2=>substr($langs->trans('Tuesday'),0,1)
		,3=>substr($langs->trans('Wednesday'),0,1)
		,4=>substr($langs->trans('Thursday'),0,1)
		,5=>substr($langs->trans('Friday'),0,1)
		,6=>substr($langs->trans('Saturday'),0,1)
		,7=>substr($langs->trans('Sunday'),0,1)
		);
	}

	/**
	 *  remplie un array avec les traductions des Mois de l'année
	 * 	 * @return void
	 */
	private function setMonthLabels($langs){
		$this->TMonthTrans=array(
			1=>$langs->transnoentities('January')
			,2=>$langs->transnoentities('February')
			,3=>$langs->transnoentities('March')
			,4=>$langs->transnoentities('April')
			,5=>$langs->transnoentities('May')
			,6=>$langs->transnoentities('June')
			,7=>$langs->transnoentities('July')
			,8=>$langs->transnoentities('August')
			,9=>$langs->transnoentities('September')
			,10=>$langs->transnoentities('October')
			,11=>$langs->transnoentities('November')
			,12=>$langs->transnoentities('December')
		);
	}

	/**
	 * recupération des couleurs et type d'absence dans le dictionnaire llx_c_
	 * @return void
	 */
	private function setColors($mysoc){

		// USER DEFINED COLORS FOR TYPE HOLIDAY
		$sql  = '  SELECT type.rowid, c.color ,type.code, delay, label' ;
		$sql .=	' FROM '.MAIN_DB_PREFIX.'c_holiday_types type ';
		$sql .=	' LEFT JOIN '.MAIN_DB_PREFIX.'advancedhrm_holidaytypecolor c ON c.fk_holidayType = type.rowid';
		$sql .=	' WHERE fk_country IS NULL OR fk_country = '.$mysoc->country_id;
		$sql .=	' ORDER BY type.sortorder';

		$resql = $this->db->query($sql);
		if ($resql){
			while ($obj = $this->db->fetch_object($resql)){
				$this->TColorType[$obj->rowid]['color'] = $obj->color;
				$this->TColorType[$obj->rowid]['code'] = $obj->code;
				$this->TColorType[$obj->rowid]['label'] = $obj->label;
				if ($obj->delay > 0){
					$this->TColorType[$obj->rowid]['delay'] = $obj->delay;
				}
			}
		}
	}
	/**
	 * Stock les jours fériés relatifs au pays dans mysoc
	 * @param $mysoc
	 * @return int
	 */
	private function setDayOff($mysoc){

		if (GETPOSTISSET('date_startyear') && GETPOSTINT('date_startyear')){
			$annee = GETPOST('date_startyear', 'int');
		}else{
			$annee = gmdate("Y", dol_now());
		}

		$dateStart = GETPOSTINT('date_startyear');
		$dateEnd = GETPOSTINT('date_endyear');
		$TYears = array();
		if ($dateStart < $dateEnd){
			for ($y = $dateStart; $y <= $dateEnd; $y++) {
				$TYears[] = $y;
			}
		}

		// ici on Stock les jours fériés relatif au pays de la société mère
		$sql = " SELECT a.id    as rowid, a.entity, a.code, a.fk_country as country_id, c.code as country_code, c.label as country, a.dayrule, a.day, a.month, a.year, a.active ";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_hrm_public_holiday as a LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON a.fk_country=c.rowid AND c.active=1";
		$sql .= " WHERE (a.fk_country = 0 OR a.fk_country = ".$mysoc->country_id.")";
		$resql = $this->db->query($sql);
		if ($resql){
			while ($obj = $this->db->fetch_object($resql)){
				if ((strlen($obj->day) == 1 || strlen($obj->month) == 1) && $obj->day > 0 && $obj->month > 0) {
					// traitemen des jours et mois à un chiffre
					if(strlen($obj->day) == 1) {
						$tempDay = str_pad($obj->day, 2, '0', STR_PAD_LEFT);
						if (strlen($obj->month) > 1) $tempMonth = $obj->month;
					}
					if(strlen($obj->month) == 1) {
						$tempMonth = str_pad($obj->month, 2, '0', STR_PAD_LEFT);
						if (strlen($obj->day) > 1) $tempDay= $obj->day;
					}
				}elseif(!empty($obj->dayrule)){
					if(!function_exists('getSpecialDayruleDayDate')){
						require_once __DIR__ . '/../lib/advancedhrm.lib.php';
					}
					if (!empty($TYears)){ // quand le formulaire renvoi une année de début et de fin différente
						foreach ($TYears as $year){
							$TDate = getSpecialDayruleDayDate($obj->dayrule, $year);
							$tempDay = $TDate['day'];
							$tempMonth = $TDate['month'];
							$this->assignDayOff($tempMonth, $tempDay, $obj, $year); // assignation des dates par année*
						}
					}else{ // quand on à une seule année
						$TDate = getSpecialDayruleDayDate($obj->dayrule, $annee);
						$tempDay = $TDate['day'];
						$tempMonth = $TDate['month'];
						$this->assignDayOff($tempMonth, $tempDay, $obj, $annee);
					}
				}else{ // afectation des variables sur les jours fixes
					$tempDay = $obj->day;
					$tempMonth = $obj->month;
				}
				if (empty($obj->dayrule)){ // les jours fériés fixe de l'année dans le dictionnaire
					$this->assignDayOff($tempMonth ?? '', $tempDay ?? '', $obj, $annee);
				}
			}
		}
		return 0;
	}

	/**
	 * Assigne les jours fériés calculés et non calculés contenu entre les dates start et end du formulaire
	 * @param string $tempMonth
	 * @param string $tempDay
	 * @param Object $obj
	 * @param String $annee
	 * @return void
	 */
	private function assignDayOff(string $tempMonth, string $tempDay, Object $obj, String $annee) :void{
		$key = $tempMonth.'-'.$tempDay;
		$this->Tdayoff[$key]['rowid'] = $obj->rowid;
		$this->Tdayoff[$key]['code'] = $obj->code;
		$this->Tdayoff[$key]['day'] = $tempDay;
		$this->Tdayoff[$key]['month'] = $tempMonth;
		$this->Tdayoff[$key]['year'] = $annee;
		$this->Tdayoff[$key]['dayrule'] = ($obj->dayrule ? 1 : 0);
	}
	/**
	 * @param $dateStart
	 * @param $dateEnd
	 * @return int nb month between two dates
	 */
	private function getNbMonth($dateStart, $dateEnd){

		$year1 = date('Y', $dateStart);
		$year2 = date('Y', $dateEnd);

		$month1 = date('m', $dateStart);
		$month2 = date('m', $dateEnd);

		return (($year2 - $year1) * 12) + ($month2 - $month1);

	}

	/**
	 * get alls informations about gap between date start and end
	 * @param $dateStart
	 * @param $dateEnd
	 * @return array
	 */
	public function getAllMonthBetweenDates($dateStart, $dateEnd){

		$nbMonth = $this->getNbMonth($dateStart, $dateEnd);
		$Tmonth = array();

		// initial date
		$Tmonth[0]['dateStart'] = date('Y-m-d', (int) $dateStart) . ' 00:00:00';
		$Tmonth[0]['dayStart'] = date('d', strtotime($Tmonth[0]['dateStart']));
		$Tmonth[0]['yearStart'] = date('Y', strtotime($Tmonth[0]['dateStart']));
		$temp = date('Y-m-t', strtotime($Tmonth[0]['dateStart']));
		$Tmonth[0]['dayEnd'] = date('d', strtotime($temp));

		$Tmonth[0]['dayEndSelected'] = date('d',(int) $dateEnd);
		//$Tmonth[0]['yearEnd'] = date('Y', strtotime($temp));
		$Tmonth[0]['dateEnd'] = date('Y-m-d', (int) $dateEnd) . ' 23:59:59';
		$Tmonth[0]['currentMonth'] = date('m', strtotime($Tmonth[0]['dateStart']));
		$Tmonth[0]['currentMonthShort'] = date('n', strtotime($Tmonth[0]['dateStart']));

		// get alls informations about gap between date start and end
		for($i = 1 ; $i <= $nbMonth; $i++){
			$newDateStart = date('Y-m-d', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));
			$newDateEnd = date('Y-m-t', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));

			$Tmonth[$i]['dateStart'] = $newDateStart . ' 00:00:00';
			$Tmonth[$i]['dayStart'] = date('d', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));
			$Tmonth[$i]['yearStart'] = date('Y', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));
			$Tmonth[$i]['dayEnd'] = date('d', strtotime($newDateEnd));
			$Tmonth[$i]['dayEndSelected'] = date('d', ($dateEnd));
			//$Tmonth[$i]['yearEnd'] = date('Y', strtotime($newDateEnd));
			$Tmonth[$i]['dateEnd'] = $newDateEnd . ' 23:59:59';
			$Tmonth[$i]['currentMonth'] = date('m', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));
			$Tmonth[$i]['currentMonthShort'] = date('n', strtotime($Tmonth[0]['dateStart']. ' + '. $i .' months'));;

		}
		return $Tmonth;
	}

	/**
	 * @param $day
	 * @param $month
	 * @param $year
	 * @return void
	 */
	public function dateToString($day,$month,$year){
		$newDateNumber = date('N',strtotime($year.'-'.$month.'-'.$day ));
		return  '<strong>'. $this->TJourTrans[(int)$newDateNumber] .'</strong>'. " "  .  $day . "/".$month;
	}

}
