<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

$element = GETPOST('element');

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/'.$element.'.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';


global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Comments");

$morejs  = array('/gestionnotifs/js/notifs_facture.js.php');
llxHeader(array(), $modname,'','','','',$morejs,0,0);
// die("En cours de traitement ...");

// print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$object = new Adherent($db);
$object->fetch($id);
$fct = $element.'_prepare_head';

$head = $fct($object);

dol_fiche_head($head, 'tab_commentaire', $langs->trans($element), -1, $object->picto);

?>

<script>
	$(document).ready(function() {
		$('#tab_commentaire').removeClass("tabunactive");
		$('#tab_commentaire').addClass("tabactive");
	})
</script>