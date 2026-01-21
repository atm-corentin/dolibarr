<?php

require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';
global $conf;
$name_=$conf->global->MAIN_INFO_SOCIETE_NOM;
// print_r($name_societ);die();
if (!class_exists('TCPDF')) {
    die(sprintf("Class 'TCPDF' not found in %s", DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php'));
}

// Extend the TCPDF class to create custom Header and Footer
    // global $conf;
class NCPDF extends TCPDF {
    //Page header
    public function Header() {
        $this->setTopMargin(4);
    }

    public function Footer() {
        // $this->setTopMargin(7);
        return $this->Cell(0, 10,$this->PageNo().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    

    
}
$format = $format ? $format : 'A4';

$pdf = new NCPDF('P', 'mm', $format, true, 'UTF-8', false, false);
// set document information
$pdf->SetCreator('intranet');
$pdf->SetAuthor('Admin');
$pdf->SetTitle((isset($title) ? $title : ''));
$pdf->SetSubject((isset($title) ? $title : ''));
$pdf->SetKeywords('');
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
// $pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(2); // PDF_MARGIN_HEADER
$pdf->SetFooterMargin(0);

// set auto page breaks


// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// set default font subsetting mode
$pdf->setFontSubsetting(true);
?>