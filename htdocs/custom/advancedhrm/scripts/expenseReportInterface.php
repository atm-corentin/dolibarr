<?php

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

$mainfile = '../../main.inc.php';
for ($i = 0; $i < 4; $i++)
{
	if (is_file($mainfile))
	{
		require_once $mainfile;
		break;
	}
	else $mainfile = '../'.$mainfile;
}

$get = GETPOST('get', 'alphanohtml');
$expenseType = GETPOST('expenseType', 'alphanohtml');
$idDet= GETPOST('idLine', 'int');

switch ($get) {

	case 'getInfos':
		print json_encode(_getInfosExpenseType($expenseType,$idDet));
		break;

	default:
		break;
}

/**
 * @param $expenseType
 * @return array
 */
function _getInfosExpenseType($expenseType = 0,$idDet = 0)
{
	global $db;
	//var_dump(GETPOST('rowid','int'));
	$ret = array(
		"msg" 		=> "Nothing found",
		"success" 	=> false,
		"selectedUser" => "",
		"return"	=> 0
	);

	dol_include_once('advancedhrm/class/expensetypevatlink.class.php');
	$etv = new ExpenseTypeVATLink($db);
	$Tvatlink = $etv->fetchAll('', '', 1, 0, array('fk_expensetype'=>$expenseType));
	if (!empty($Tvatlink))
	{
		$sql = "SELECT taux FROM ".MAIN_DB_PREFIX."c_tva WHERE rowid = ".$Tvatlink[0]->fk_vat_tx;
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$ret = array(
				"msg" 		=> "VAT found",
				"success" 	=> true,
				"return"	=> $obj->taux
			);
		}
	}

	return $ret;
}
