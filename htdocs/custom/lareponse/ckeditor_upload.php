<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/lareponse/class/article.class.php');

$object = new Article($db);

// Get the current article's id reffering to the previous url
$url_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
parse_str($url_query, $parsed_query);
$id = $parsed_query['id'];

$object->fetch($id);

$upload_dir = $conf->lareponse->multidir_output[$object->entity ? $object->entity : $conf->entity]."/article/".dol_sanitizeFileName($object->ref);


// load file in memory documents/lareponse/article/$id/
if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
	if (! empty($_FILES)) {
		if (is_array($_FILES['file']['tmp_name'])) $userfiles=$_FILES['file']['tmp_name'];
		else $userfiles=array($_FILES['file']['tmp_name']);
		foreach ($userfiles as $key => $userfile) {
			if (empty($_FILES['file']['tmp_name'][$key])) {
				$error++;
				if ($_FILES['file']['error'][$key] == 1 || $_FILES['file']['error'][$key] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}
		}
		if (! $error) {
			if (! empty($upload_dir) && !file_exists($upload_dir.'/'.$_FILES['file']['name'])) {
				$result = dol_add_file_process($upload_dir, 0, 1, 'file', GETPOST('savingdocmask', 'alpha'));
			}
		}
	}
}

$upload_url = dol_buildpath('/viewimage.php', 2).'?modulepart=lareponse&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode('article/'.dol_sanitizeFileName($object->ref).'/'.$_FILES['file']['name']);
$array = array('url'=>$upload_url);
echo json_encode($array);
