<?php 
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';


dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');
$langs->load('gestionnotifs@gestionnotifs');

global $langs;


$notifics = new gt_notifcs($db);
$notifics->fetchAll('DESC','rowid',5,0);
$html = '<ul id="ul_notifics">';
$data_id = array(
	'member' => [ 'id' =>'rowid','class' => "Adherent"],
	'project' => [ 'id' =>'rowid','class' => "Projet"],
	'societe' => [ 'id' =>'socid','class' => "Societe"],
	'product' => [ 'id' =>'id','class' => "Product"],
	'facture' => [ 'id' =>'facid','class' => "Facture"],
);
if($notifics->rows && count($notifics->rows)>0){
	for ($i=0; $i < count($notifics->rows); $i++) { 
		$notif = $notifics->rows[$i];
		$cl = $data_id[$notif->name_module]['class'];
		// $obj = new $cl($db);
		// $obj->fetch($notif->fk_module);
		$html .= '<li><label style="color:#928b8b;">'.date('d/m/Y H:i',strtotime($notif->date)).'</label> '.$langs->trans($notif->action).' </li>';
	}
}
$html .= '</ul>';

echo json_encode($html);
// die();