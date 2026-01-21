<?php

global $langs;
$langs->loadLangs(array('projects', 'companies'));

$colors = [0=>'#33a9a6', 1=>'#f39c12', 2=>'#3498db', 3=>'#ff6959', 4=>'#8956a1', 5 => '#7db55a'];

$Ymin = $Ymax = date('Y');
$Mmin = $Mmax = date('M');
$Dmin = $Dmax = date('d');

if($onproject_id){
	$sql2 = 'select MIN(t.dateo) as dmin, MAX(t.datee) as dmax, count(t.rowid) as nb from '.MAIN_DB_PREFIX.'projet_task as t  where t.fk_projet ='.$onproject_id;
	// $sql2 .= ($start ? ' AND YEAR(t.dateo) = "'.$start.'"' : '');
	// $sql2 .= ($end ? ' AND YEAR(t.datee) = "'.$end.'"' : '');


	$resql2 = $db->query($sql2);
	if($resql2){
		while ($ob=$db->fetch_object($resql2)) {
		   $nb = $ob->nb;

		   $_dmin = $ob->dmin;
		   $_dmax = $ob->dmax;

		   $Ymin=(int)dol_print_date($db->jdate($_dmin), '%Y');
		   $Mmin=(int)dol_print_date($db->jdate($_dmin), '%m');
		   $Dmin=(int)dol_print_date($db->jdate($_dmin), '%d');

		   $Ymax=(int)dol_print_date($db->jdate($_dmax), '%Y');
		   $Mmax=(int)dol_print_date($db->jdate($_dmax), '%m');
		   $Dmax=(int)dol_print_date($db->jdate($_dmax), '%d');

		}
	} else {
		// echo $sql2.'<br>';
		print_r($db->lasterror());
		die();
	}
}

$months = array(1 => $langs->trans("January"), 2 => $langs->trans("February"), 3 => $langs->trans("March"), 4 => $langs->trans("April"), 5 => $langs->trans("May"), 6 => $langs->trans("June"), 7 => $langs->trans("July"), 8 => $langs->trans("August"), 9 => $langs->trans("September"), 10 => $langs->trans("October"), 11 => $langs->trans("November"), 12 => $langs->trans("December"));

$monthshort = array(1 => $langs->trans("MonthShort01"), 2 => $langs->trans("MonthShort02"), 3 => $langs->trans("MonthShort03"), 4 => $langs->trans("MonthShort04"), 5 => $langs->trans("MonthShort05"), 6 => $langs->trans("MonthShort06"), 7 => $langs->trans("MonthShort07"), 8 => $langs->trans("MonthShort08"), 9 => $langs->trans("MonthShort09"), 10 => $langs->trans("MonthShort10"), 11 => $langs->trans("MonthShort11"), 12 => $langs->trans("MonthShort12"));
$monthveryshort = array(1 => $langs->trans("MonthVeryShort01"), 2 => $langs->trans("MonthVeryShort02"), 3 => $langs->trans("MonthVeryShort03"), 4 => $langs->trans("MonthVeryShort04"), 5 => $langs->trans("MonthVeryShort05"), 6 => $langs->trans("MonthVeryShort06"), 7 => $langs->trans("MonthVeryShort07"), 8 => $langs->trans("MonthVeryShort08"), 9 => $langs->trans("MonthVeryShort09"), 10 => $langs->trans("MonthVeryShort10"), 11 => $langs->trans("MonthVeryShort11"), 12 => $langs->trans("MonthVeryShort12"));


$nbDays = array(1=>31,2=>28,3=>31,4=>30,5=>31,6=>30,7=>31,8=>31,9=>30,10=>31,11=>30,12=>31);

$categor = ' ';
if($search_category > 0) {
	$categoryobj = new Categorie($db);
	$exist = $categoryobj->fetch($search_category);
	if($exist) {
		$categor .= '(';
		$categor .= $categoryobj->label;
		$categor .= ')';
	}
}



$divline5px = ($action == 'pdf') ? '<div style="line-height:5px;">&nbsp;</div>' : '';
$divline1px = ($action == 'pdf') ? '<div style="line-height:1px; ">&nbsp;</div>' : '';
$divline3px = ($action == 'pdf') ? '<div style="line-height:3px; ">&nbsp;</div>' : '';
$bordertable = ($action == 'pdf') ? '' : 'border="1"';
$fontsize5px = ($action == 'pdf') ? 'font-size:5px;' : '';
$fontsize7px = ($action == 'pdf') ? 'font-size:7px;' : '';
$fontsize8px = ($action == 'pdf') ? 'font-size:8.5px;' : '';
$fontsize10px = ($action == 'pdf') ? 'font-size:10px;' : '';
$bordercolor = ($action == 'pdf') ? '#F3F4F6' : '#000000';


// $html.= '<h3 align="center"> '.$sql2.'</h3>';
$html .= '<table border="0" width="100%">';
    $html .= '<tr>';
        $html .= '<td colspan="20">';
			$html.= '<h4 align="center"> '.$langs->trans("ganttproadvanced").'</h4>';
        $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
        $html .= '<td colspan="20">';
			$html.= '<h3 align="center"> '.$projectstatic->ref .' - '. $projectstatic->title.'</h3>';
        $html .= '</td>';
    $html .= '</tr>';
$html .= '</table>';

if($action == 'pdf') {
	$html.= '<br><br><br><br>';
}

$html.= '<meta charset="utf-8" />';

if($onproject_id){
	$progressproj = isset($projectstatic->array_options["options_ganttproadvancedprojectprogress"]) ? $projectstatic->array_options["options_ganttproadvancedprojectprogress"] : 0;
    $html .= '<br><br><br><br>';
    $html .= '<table '.$bordertable.' style="border: 1px solid '.$bordercolor.';'.$fontsize10px.'background-color:#34495e;color:#fff;width:100%;" cellpadding="7px" width="100%">';
        $html .= '<tr>';
            $html .= '<td colspan="4" align="center" style="width:33.33%;"><span>'.$langs->trans("DateStart").': </span> '.dol_print_date($projectstatic->date_start,'day').' </td>';
            // $html .= '<td style="width:40%;"> <b>'.$projectstatic->ref .' - '.$projectstatic->label.'</b> '.$projectstatic->getLibStatut(5).'</td>';
            $html .= '<td colspan="4" align="center" style="width:33.33%;"><br><span>'.$langs->trans("ProgressProject").': </span> '.$progressproj.'%</td>';
            $html .= '<td colspan="4" align="center" style="width:33.33%;"><span>'.$langs->trans("DateEnd").': </span> '.dol_print_date($projectstatic->date_end,'day').'</td>';
        $html .= '</tr>';
    $html .= '</table>';
}
$html.= '<br><br>';

$html.= '<table class="liste_" style="width:100%;" cellpadding="0px" cellspacing="0" >';
	$html.= '<tr>';
		$html.= '<td class="leftcolumn">';
			$html.= '<table '.$bordertable.' class="liste_" style="width:100%;" cellpadding="0px" cellspacing="0" >';
				$html.= '<thead>';
					$html.= '<tr class="liste_titre">';
						$html.= '<th rowspan="2" class="thganttitles lef_ref_td" ><strong><br>'.$langs->trans("Tasks").'</strong></th>';
						// $html.= '<th rowspan="2" class="thganttitles lef_dur_td" ><strong><br>'.$langs->trans("Duration").' ('.substr($langs->trans("Day"),0,1).')</strong></th>';
						$html.= '<th rowspan="2" class="thganttitles lef_dur_td" ><strong><br>'.substr($langs->trans("Day"),0,1).'</strong></th>';
						// $html.= '<th rowspan="2" class="thganttitles lef_perc_td" ><strong><br>'.$langs->trans("Comp % ").'</strong></th>';
						$html.= '<th rowspan="2" class="thganttitles lef_start_td" ><strong><br>'.$langs->trans("DateStart").'</strong></th>';
						$html.= '<th rowspan="2" class="thganttitles lef_end_td" ><strong><br>'.$langs->trans("DateEnd").'</strong></th>';
						$html.= '<th rowspan="2" class="thganttitles lef_perc_td" ><strong><br>%</strong></th>';
					$html.= '</tr>';

					$html.= '<tr class="liste_titre">';
					$html.= '</tr>';

					// $html.= '<tr class="liste_titre"><th colspan="5" style="line-height:0px;">&nbsp;</th></tr>';
				$html.= '</thead>';

				$html.= '<tbody>';
		
					if (!$tasks)
					{
					  	// print '<div class="opacitymedium" align="center">'.$langs->trans("NoTasks").'</div>';
					}
					// d($tasks);
					if ($tasks && count($tasks) > 0) {
						$cl = "pair";
						foreach ($tasks as $key => $value) {
							if($value['task_name']){
								$html .='<tr class="'.$cl.'">';
									$start = dol_print_date($value['task_start_date'], 'day');
									$fin = dol_print_date($value['task_end_date'],'day');

									$html .= '<td align="left" class="lefttddata lef_ref_td">'.$divline5px.'&nbsp;&nbsp; '.$value['task_name_pdf'].'</td>';
									$html .= '<td align="center" class="lefttddata lef_dur_td">'.$divline5px.''.$value['task_duration'].'</td>';
									$html .= '<td align="center" class="lefttddata lef_start_td">'.$divline5px.''.$start.'</td>';
						    		$html .= '<td align="center" class="lefttddata lef_end_td">'.$divline5px.''.$fin.'</td>';
									$html .= '<td align="center" class="lefttddata lef_perc_td">'.$divline5px.''.$value['task_percent'].'</td>';

								$html .='</tr>';
							if ($cl == "pair") { $cl = "impair"; }else{ $cl = "pair"; }
							}
						}
					}
	
				$html.= '</tbody>';
			$html.= '</table>';
		$html.= '</td>';

		$html.= '<td class="rightcolumn">';
		$html.= '<table '.$bordertable.' style="width:100%; border-collapse: collapse;">';
		$html.= '<thead>';
		if($Ymin == $Ymax && ($Mmax - $Mmin) <= 2){

			$html.= '<tr>';
				for ($i=$Ymin; $i <= $Ymax ; $i++) {
					$jd=1; $jf = 12;
					if($i == $Ymin) $jd=$Mmin;
					if($i == $Ymax) $jf=$Mmax;

					for ($j=$jd; $j <= $jf ; $j++) { 

						if($j == 2) $nbDays[$j] = cal_days_in_month(CAL_GREGORIAN, $j, $i);

						if($j == $Mmin && $j == $Mmax) $colspan=($Dmax-$Dmin)+1;
						elseif($j == $Mmin) $colspan=($nbDays[$j]-$Dmin)+1;
						elseif($j == $Mmax) $colspan=$Dmax;
						else $colspan = $nbDays[$j];
						
						$monthtxt = '';
						if($colspan > 1 || ($Ymin == $Ymax && $jd == $jf )) $monthtxt = $months[$j];
						$html.= '<td align="center" colspan="'.$colspan.'" class="toptdshead">'.$tmpline1px.($Ymax ? $Ymax : '').'</td>';
					} 
				}
			$html.= '</tr>';

			$html.= '<tr>';
				for ($i=$Ymin; $i <=$Ymax ; $i++) {
					$jd=1; $jf = 12;
					if($i == $Ymin) $jd=$Mmin;
					if($i == $Ymax) $jf=$Mmax;
					for ($j=$jd; $j <= $jf ; $j++) { 

						if($j == 2) $nbDays[$j] = cal_days_in_month(CAL_GREGORIAN, $j, $i);

						$d=1; $df = $nbDays[$j];
						if($j == $Mmin) $d=$Dmin;
						if($j == $Mmax) $df=$Dmax;

						for ($k=$d; $k <= $df ; $k++) { 
							$html.= '<td align="center" class="toptdshead">'.$divline3px.($k ? $k : '').'</td>';
						}
					} 
				}
			$html.= '</tr>';
		}

		else{

			$html.= '<tr>';
				for ($i=$Ymin; $i <=$Ymax ; $i++) { 
					// if() $colspan=(12-$Mmin)+1;
					if($Ymax == $Ymin) $colspan = ($Mmax-$Mmin)+1;
					elseif($i == $Ymin) $colspan=(12-$Mmin)+1;

					elseif($i == $Ymax) $colspan=$Mmax;

					else $colspan = 12;

					$colspan = ($colspan ? $colspan : 1) ;

					$colspan = $colspan*3;
					$html.= '<td colspan="'.$colspan.'" align="center" class="toptdshead">'.$i.'</td>';
				}
			$html.= '</tr>';

			$html.= '<tr>';
				$vsh = 0;
				if($Ymax-$Ymin > 3) $vsh = 1;
				for ($i=$Ymin; $i <=$Ymax ; $i++) {
					$jd=1; $jf = 12;
					if($i == $Ymin) $jd=$Mmin;
					if($i == $Ymax) $jf=$Mmax;

					$colspan = 3;

					for ($j=$jd; $j <= $jf ; $j++) { 
						$monthname = $monthshort[$j];
						if($vsh) $monthname = $monthveryshort[$j];
						$html.= '<td colspan="'.$colspan.'"  align="center" class="toptdshead">'.$divline3px.$monthname.'</td>';
					} 
				}
			$html.= '</tr>';
		}
		$html.= '</thead>';

		$html.= '<tbody>';

		if ($tasks && count($tasks) > 0) {
			$c=0;
			$cl_ = "pair";

			foreach ($tasks as $key => $value) {
				if($value['task_ref']){
					if($c >= count($colors))
        				$c = 0;
        			$css='';

					$html .='<tr class="'.$cl_.'">';
						
						// $start = explode('/', dol_print_date($value['task_start_date'], 'day'));
						$start = dol_getdate($value['task_start_date']);
						// $fin = explode('/', dol_print_date($value['task_end_date'],'day'));
						$fin = dol_getdate($value['task_end_date']);

						// $percent = ($value['task_percent_complete'] ? number_format($value['task_percent_complete'],0) : 0) .'%';
						
						$percent = '0' .'%';

						$ystart= (int)$start['year'];
						$mstart=(int)$start['mon'];
						$dstart=(int)$start['mday'];

						$yfin=(int)$fin['year'];
						$mfin=(int)$fin['mon'];
						$dfin=(int)$fin['mday'];
						$v=$Mmax-$Mmin;
						if($Ymin == $Ymax && ($Mmax - $Mmin) <= 2){
							for ($i=$Ymin; $i <=$Ymax ; $i++) {
								$jd=1; $mf = 12;
								
								if($i == $Ymin) $md=$Mmin;
								if($i == $Ymax) $mf=$Mmax;

								for ($m=$md; $m <= $mf ; $m++) { 

									if($m == 2) $nbDays[$m] = cal_days_in_month(CAL_GREGORIAN, $m, $i);

									$d=1; $df = $nbDays[$m];
									if($m == $md)
										if($i == $Ymin) $d=$Dmin;
									if($m == $mf) $df=$Dmax;
									for ($k=$d; $k <= $df ; $k++) { 
										
										// // if(($i == $yfin || $i == $ystart) && ($mstart >= $j  || $mstart <= $j) && ($mfin <= $jf  || $mstart <= $jf))
										// if( ( ($m >= $mstart && $m<=$mfin)  && $dstart<=$k && $k<=$dfin) ){
										if( 
											($m == $mstart  && $dstart == $k ) 
											|| ($m == $mfin && $m!=$mstart  &&  $k<=$dfin ) 
											|| ($m < $mfin && $m >= $mstart) 

											// || ($m <= $mfin && $m >= $mstart)  && ( ($dstart>=$k && $k<=$dfin) || ($dstart<=$k && $k<=$dfin) )) 
											|| ( ($m == $mstart && $m==$mfin) && ( $k>=$dstart && $k<=$dfin) ) 
										) {
											$bord = "border-left:1px solid white; border-right:1px solid white; border-top:none; border-bottom:none; ";
											// $css = 'background-color:'.$colors[$c];
											$css = 'background-color:'.$value['task_color'].';';
											$txtpercent=$percent;
											$percent = '';
											$text =$dstart.' - '.$dfin;
										}
										else{
											$bord = "border:1px solid #fff;";
											$css='';
											$txtpercent = '';
											$text ='';
										} 
											
										$tmpcss = ($action == 'xls') ? $css : '';
										// $tmpcss .= (($action == 'xls') ? 'width:10px;' : '');

										$bord = ($action == 'pdf') ? $bord : '';

										$html.= '<td align="center" class="bgcolortasktd" style=" '.$bord.$tmpcss.'">';
										$html.= '<div class="bgcolortaskdivider">&nbsp;</div>';
										$html.= '<div class="bgcolortaskcontent" style=" '.$css.'"></div>';
										$html.= '</td>';
										// $html.= '<td align="center" class="bgcolortasktd" style="border:1px solid lightgrey">'.$k.'</td>';
										

									}
								} 
							}
						}
						else{
							if($ystart || $yfin){
								for ($i=$Ymin; $i <=$Ymax ; $i++) {
									$jd=1; $jf = 12;
									
									if($i == $Ymin) $jd=$Mmin;
									if($i == $Ymax) $jf=$Mmax;

									for ($j=$jd; $j <= $jf ; $j++) { 
										// if(($i == $yfin || $i == $ystart) && ($mstart >= $j  || $mstart <= $j) && ($mfin <= $jf  || $mstart <= $jf))
										// if(($i == $yfin && $i == $ystart && $j>=$mstart && $j<=$mfin) || (($i <= $yfin && $i >= $ystart) && ( $j >= $mstart || ( $i>$ystart && (($j <= $mstart || $j >= $mstart) && $j<=$mfin) )) )) {

										if(($i == $ystart && $i != $yfin && $j>=$mstart) 
											|| ($i == $yfin && $i != $ystart && $j <= $mfin)
											|| ($i == $ystart && $i == $yfin && $j>=$mstart && $j <= $mfin)
											|| ($i > $ystart && $i < $yfin)
										)
										{

											$bord = "border-left:1px solid white; border-right:1px solid white; border-top:none; border-bottom:none; ";
											// $css = 'background-color:'.$colors[$c];
											$css = 'background-color:'.$value['task_color'].';';

											$txtpercent=$percent;
											$percent = '';
										}
										else{
											$bord = "border:1px solid #fff;";
											$css='';
											$txtpercent = '';
										} 

										$emptd = '<td data-month="'.$monthshort[$j].'" colspan="" align="center" class="" style="'.$bord.'">';
										$emptd .= '<div class="bgcolortaskdivider">&nbsp;</div>';
										$emptd .= '</td>';

										$maxdays = $nbDays[$j];
										if($j == 2) $maxdays = cal_days_in_month(CAL_GREGORIAN, $j, $i);

										$colspan3 = 3;
										if($ystart == $i && $mstart == $j && $dstart > 11) {
											$cols = 0;
											if($dstart >= 21) {
												$cols = 2;
												$colspan3--;
											}
											$html.= '<td data-month="'.$monthshort[$j].'" colspan="'.$cols.'" align="center" class="" style="">';
											$html.= '<div class="bgcolortaskdivider">&nbsp;</div>';
											$html.= '</td>';
											$colspan3--;
										}

										$tdpercent2 = '';
										if($yfin == $i && $mfin == $j && $dfin < $maxdays) {
											$cols = 0;
											if($dfin <= 11) {
												$cols = 2;
												$colspan3--;
											}

											$tdpercent2 .= '<td data-month="'.$monthshort[$j].'" colspan="'.$cols.'" align="center" class="" style="">';
											$tdpercent2 .= '<div class="bgcolortaskdivider">&nbsp;</div>';
											$tdpercent2 .= '</td>';
											$colspan3--;
										}

										if($colspan3 >= 0) {

											$tmpcss = ($action == 'xls') ? $css : '';
											// $tmpcss .= (($action == 'xls') ? 'width:10px;' : '');

											$bord = ($action == 'pdf') ? $bord : '';

											$html.= '<td data-month="'.$monthshort[$j].'" colspan="'.$colspan3.'" align="center" class="bgcolortasktd" style="'.$bord.$tmpcss.'">';
											$html.= '<div class="bgcolortaskdivider">&nbsp;</div>';
											$html.= '<div class="bgcolortaskcontent" style=" '.$css.'"></div>';
											$html.= '</td>';
										}

										$html.= $tdpercent2;

										


									} 
								}
							}else{
								$html .= '<td></td>';
							}
						}

					$html .='</tr>';
					$c++;
					if ($cl_ == "pair") { $cl_ = "impair"; }else{ $cl_ = "pair"; }
					
				}
			}
		}
		$html.= '</tbody>';

		$html.= '</table>';
		$html.= '</td>';
	$html.= '</tr>';
$html.= '</table>';



	// $html .='<tr><td align="center" colspan="4">'.$langs->trans("NoTasks").'</td></tr>';
// }
$html.= '<style>
th{font-family: Arial, Helvetica, sans-serif;font-weight:bold;}
td{font-family: Arial, Helvetica, sans-serif;overflow: auto; white-space: nowrap;}

.totp_table td{text-align:left;}
.title1{text-align:center;font-size:13;font-weight:bold;}
.title2{text-align:center;font-size:10;}

/* .liste_ td{border:solid 1px lightgrey;}
.liste_ th{color: #fff;border-bottom: solid 1px lightgrey;border: solid 1px lightgrey;}*/

table tr.pair td{background-color: #F3F4F6;}
table tr.impair td{background-color: #fff;}

.tfoot td{background-color: #eee;border-top:none}

/*.liste_ th{text-align:center;background-color: #3498db;color:#fff;border:1px solid #fff;}*/

.bgcolortasktd{height: 20px;}
.bgcolortaskdivider{line-height:2px;height:2px;}
.bgcolortaskcontent{line-height:5px;height:5px;}

.badge-status2{background-color: #9c9c26;color: #ffffff; width: 100%;}
.badge-status1{background-color: #bc9526;color: #ffffff; width: 100%;}
.badge-status3{background-color: #bca52b;color: #212529; width: 100%;}

/*
td, th {border:solid 1px red}
*/

.toptdshead {
	/*height:14px;font-size:5px;border:1px solid #fff; background-color:#3498db; color:#fff;*/
}
.liste_ th{text-align:center;border:1px solid '.$bordercolor.';}

.noborder1{border:none;}
.noborderright{border-left:none;}
.lef_start_td{width: 13.5%;}
.lef_end_td{width: 13.5%;}
.lef_perc_td{width: 7%;}
.lef_dur_td{width: 9%;}
.lef_ref_td{width: 57%;}

.leftcolumn{width: 30%;}
.rightcolumn{width: 70%;}
</style>';

$testhtml 	= GETPOST('testhtml', 'int');
if($testhtml > 0) {
	echo $html;die;	
} 


if($format == 'A3') {

	$html.= '<style>
	.thganttitles{height:28px;'.$fontsize8px.'}
	.lefttddata{height: 20px;'.$fontsize8px.'}
	.toptdshead {height:14px;'.$fontsize8px.'border:1px solid '.$bordercolor.';}

	.lef_start_td{width: 13.5%;}
	.lef_end_td{width: 13.5%;}
	.lef_perc_td{width: 7%;}
	.lef_dur_td{width: 9%;}
	.lef_ref_td{width: 57%;}

	.leftcolumn{width: 30%;}
	.rightcolumn{width: 70%;}
	</style>';

} else {

	$html.= '<style>
	.thganttitles{height:28px;'.$fontsize7px.'}
	.lefttddata{height: 20px;'.$fontsize7px.'}
	.toptdshead {height:14px;'.$fontsize5px.'border:1px solid '.$bordercolor.';}

	.lef_start_td{width: 13.5%;}
	.lef_end_td{width: 13.5%;}
	.lef_perc_td{width: 7%;}
	.lef_dur_td{width: 9%;}
	.lef_ref_td{width: 57%;}

	.leftcolumn{width: 30%;}
	.rightcolumn{width: 70%;}
	</style>';

}

$cols = 4;



// // ------------------------------------------------------------------------------- Method works But to Slow
// $chart = '<td data-month="'.$monthshort[$j].'" align="center" class="bgcolortasktd" style="'.$bord.'">';
// $chart.= '<div class="bgcolortaskdivider">&nbsp;</div>';
// $chart.= '<div class="bgcolortaskcontent" style=" '.$css.'"></div>';
// $chart.= '</td>';

// $td1 = $emptd; $td2 = $emptd; $td3 = $emptd; $td4 = $emptd;

// if($ystart == $i && $mstart == $j) {
// 	if($dstart >1 && $dstart <= 7) {
// 		$td1 = $chart; $td2 = $chart; $td3 = $chart; $td4 = $chart;
// 	}
// 	elseif($dstart > 7 && $dstart <= 15) {
// 		$td1 = $emptd; $td2 = $chart; $td3 = $chart; $td4 = $chart;
// 	}
// 	elseif($dstart > 15 && $dstart < 21) {
// 		$td1 = $emptd; $td2 = $emptd; $td3 = $chart; $td4 = $chart;
// 	}
// 	elseif($dstart >= 21 && $dstart <= $maxdays) {
// 		$td1 = $emptd; $td2 = $emptd; $td3 = $emptd; $td4 = $chart;
// 	}
// }

// if($yfin == $i && $mfin == $j && $dfin < $maxdays) {
// 	if($dfin >1 && $dfin <= 7) {
// 		$td1 = $chart; $td2 = $emptd; $td3 = $emptd; $td4 = $emptd;
// 	}
// 	elseif($dfin > 7 && $dfin <= 15) {
// 		$td1 = $chart; $td2 = $chart; $td3 = $emptd; $td4 = $emptd;
// 	}
// 	elseif($dfin > 15 && $dfin < 21) {
// 		$td1 = $chart; $td2 = $chart; $td3 = $chart; $td4 = $emptd;
// 	}
// 	elseif($dfin >= 21 && $dfin <= $maxdays) {
// 		$td1 = $chart; $td2 = $chart; $td3 = $chart; $td4 = $chart;
// 	}
// }

// $html.= $td1;
// $html.= $td2;
// $html.= $td3;
// $html.= $td4;
// // ----------------------------------------------------------------------------------------------------

// die($html);