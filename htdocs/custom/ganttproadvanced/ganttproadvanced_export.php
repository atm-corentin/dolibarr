<?php


$taskcursor = 0;
$filtery = '';

$caradays = ' '.strtolower(substr($langs->trans("Day"),0,1));
if($action == 'pdf' || $action == 'xls') $caradays = '';

$tasksarray=[];


if($action == 'pdf') {


    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
    require_once dol_buildpath('/ganttproadvanced/pdf/pdf.lib.php');


    $pdf->SetMargins(5, 2, 5, false);
    $pdf->SetFooterMargin(10);
    $pdf->setPrintFooter(true);
    $pdf->SetAutoPageBreak(TRUE,10);

    $height=$pdf->getPageHeight();

    $pdf->SetFont('helvetica', '', 9, '', true);
    $pdf->AddPage('L');
    $margint = $pdf->getMargins()['top'];
    $margint = $pdf->getMargins()['top'];
    $marginb = $pdf->getMargins()['bottom'];
    $marginl = $pdf->getMargins()['left'];

    $pdf->SetTextColor(0, 0, 60);

    // $default_font_size = 10;
    $default_font_size = pdf_getPDFFontSize($langs);
    $pdf->SetFont('', 'B', $default_font_size);
    $posy   = $margint;
    $posx   = $marginl;

    $pdf->SetXY($marginl, $posy);

}





$html = '';

if($action == 'pdf') {
    gantt_page_head($pdf);
}

$level = 0;

foreach ($search_projects as $key => $onproject_id) {

    if(!empty($html) && $action == 'pdf') {
        $pdf->AddPage('', '', true);
        gantt_page_head($pdf);
    }

    if($action == 'pdf') {
        $html = '';
    } else {
        $html .= '<br><br><br><br>';
    }

    $indextasks = 0;
    $sortedtasks = array();

    $projectstatic->fetch($onproject_id);

    // ----------------------------------------------------------------------------------------------------- Project
    $obj = $projectstatic;
    $noformatstart = (int) $projectstatic->date_start;
    $noformatend = (int) $projectstatic->date_end;
    $dstart = $noformatstart; $dend = $noformatend;
    $dend = ($dend ? $dend : $dstart);

    // $datediff = $noformatend - $noformatstart;
    // $duration = round($datediff / (60 * 60 * 24));
    // if($duration <= 1)  $Duration = '1'.$caradays;
    // else  $Duration = $duration . $caradays;


    $duration = $ganttproadvanced->calculateWeekdaysWithOrWithoutWeekEnd($dstart, $dend);
    $Duration = $duration . $caradays;
    
    $nameinpdf = '';
    for ($k = 0; $k < $level; $k++) {
        if($k > 4) break;
        $nameinpdf .= '&nbsp;';
    }

    $totcar = 45;
    if($format == 'A3') $totcar = 54;

    $nameinpdf .= dol_htmlentitiesbr_decode($obj->title ? $obj->title : $obj->ref);
    if(strlen($nameinpdf) > $totcar) $nameinpdf = substr($nameinpdf, 0, $totcar).'...';
    $nameinpdf = dol_htmlentities($nameinpdf);

    $arr = array();
    $arr['task_name_pdf'] = '<b>'.$nameinpdf.'</b>';
    $arr['task_name'] = $obj->ref.' - '.$obj->title;
    $arr['task_start_date'] = $dstart;
    $arr['task_end_date'] = $dend;
    $arr['task_duration'] = $Duration;
    $percent = isset($projectstatic->array_options["options_ganttproadvancedprojectprogress"]) ? $projectstatic->array_options["options_ganttproadvancedprojectprogress"] : 0;
    $arr['task_percent'] = ($percent ? number_format($percent,0) : 0);
    $arr['task_ref'] = $obj->ref;
    $arr['task_color'] = $ganttproadvanced->p_projectcolor;

    $sortedtasks[-1] = $arr;
    $tasksarray = $sortedtasks;
    // ----------------------------------------------------------------------------------------------------

    $extrafields->fetch_name_optionals_label($projectstatic->table_element);
    $extrafields->fetch_name_optionals_label($taskstatic->table_element);
    // d($extrafields);

    $filterprogresscalc = '';
    
    $morewherefilter = '';
    if($search_debut && $search_fin){
        $morewherefilter .= ' AND (';
        $morewherefilter .= ' (CAST(t.dateo as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
        $morewherefilter .= ' OR ';
        $morewherefilter .= ' (CAST(t.datee as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
        $morewherefilter .= ')';
    }
    
    $tasksarray = $objtask->getTasksArray(0, 0, $onproject_id, 0, 0, '','-1', $morewherefilter, 0, 0, $extrafields, 0, array(), $_loadextras = 1);

    if (count($tasksarray) > 0) {
        $j = 0; $level = 0;
        $tasks = $ganttproadvanced->getTasksOfProjectPdf($sortedtasks, $indextasks, $j, 0, $tasksarray, $level, true, 0, $onproject_id, 1, $onproject_id, $filterprogresscalc, 0);
    } else {
        $tasks = $sortedtasks;
    }
    require dol_buildpath('/ganttproadvanced/tpl/ganttproadvanced_tpl.php');

    if($action == 'pdf') {
        $pdf->writeHTML($html, true, false, true, false, '');
    }

}
// echo $html;die();




function gantt_page_head(&$pdf) {

    global $conf, $langs, $mysoc;
    
    $margint = $pdf->getMargins()['top'];
    $margint = $pdf->getMargins()['top'];
    $marginb = $pdf->getMargins()['bottom'];
    $marginl = $pdf->getMargins()['left'];

    $pdf->SetTextColor(0, 0, 60);

    $default_font_size = pdf_getPDFFontSize($langs);
    // $default_font_size = 10;
    $pdf->SetFont('', '', $default_font_size);
    $posy   = $margint;
    $posx   = $marginl;

    $heightimg = 15;
    // Logo
    if ($mysoc && $mysoc->logo)
    {
        $logodir = $conf->mycompany->dir_output;
        if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO))
        {
            $logo = $logodir.'/logos/thumbs/'.$mysoc->logo_small;
        }
        else {
            $logo = $logodir.'/logos/'.$mysoc->logo;
        }
        
        if (is_readable($logo))
        {
            $height = pdf_getHeightForLogo($logo);
            $pdf->Image($logo, $marginl, $posy, 0, $heightimg); // width=0 (auto)
        }
        else
        {
            $pdf->SetTextColor(200, 0, 0);
            $pdf->SetFont('', 'B', $default_font_size - 2);
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
        }
    }
    else
    {
        $heightimg = 8;
        $text = $mysoc->name;
        $pdf->MultiCell(100, 4, $langs->convToOutputCharset($text), 0, 'L');
    }

    // $formatarray = pdf_getFormat();
        
    $titlewidth = 40;

    $topright = $mysoc->town ? $mysoc->town.', ' : '';
    $topright .= $langs->trans("The").' '.date('d/m/Y');

    $lengthtxt = strlen($topright);

    $pagewidth = $pdf->getPageWidth();

    $posx = ($pagewidth/2)-$pdf->getMargins()['right']+$titlewidth;
    $posy += 5;
    $pdf->SetXY($posx, $posy);
    $pdf->SetTextColor(0, 0, 60);

    $pdf->MultiCell(($pagewidth/2)-$titlewidth, 4, $topright, 0, 'R');

    $posx   = $marginl;

    // $posy   = $margint + $heightimg + 2;

    $pdf->SetTextColor(0, 0, 60);

    $pdf->SetXY($posx, $posy);

}

if($action == 'pdf') {
    ob_start();
    $pdf->Output($langs->trans("ganttproadvanced").'.pdf', 'I');
    die();
}