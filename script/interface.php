<?php

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
global $conf, $langs, $db, $user;

// Include necessary files
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';

$action = GETPOST('action', "alpha");
$propalId = GETPOST('propalId', 'int');
$lineId = GETPOST('lineId', 'int');
$newPrice = GETPOST('newPrice');
$newPuHt = price2num($newPrice);

switch ($action) {
	case 'update_line_price':
		header('Content-Type: application/json'); // We will return JSON
		$response = array('status' => 'error', 'message' => 'Unknown error');

		try {
			dol_syslog("AJAX update_line_price: propalId=$propalId, lineId=$lineId, newPrice=$newPuHt");

			if (!$propalId || !$lineId) {
				$response['message'] = 'Missing $propalId or lineId';
				echo json_encode($response);
				exit;
			}

			$object = new SupplierProposal($db);
			$fetch_result = $object->fetch($propalId);

			if ($fetch_result <= 0) {
				$response['message'] = 'Supplier proposal not found: ' . $object->error;
				dol_syslog("AJAX update_line_price: fetch failed for propalId=$propalId", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			$lineToUpdate = null;

			// Find the line to get its properties
			foreach ($object->lines as $line) {
				if ($line->id == $lineId) {
					$lineToUpdate = $line;
					break;
				}
			}

			if (!$lineToUpdate) {
				$response['message'] = 'Line not found in proposal';
				dol_syslog("AJAX update_line_price: lineId=$lineId not found", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			if (!method_exists($object, 'updateline')) {
				$response['message'] = 'Method updateline not found';
				dol_syslog("AJAX update_line_price: updateline method missing", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			// Call the update line method
			$res = $object->updateline(
				$lineToUpdate->id,
				$newPuHt,
				$lineToUpdate->qty,
				$lineToUpdate->remise_percent,
				$lineToUpdate->tva_tx,
				0,
				0,
				$lineToUpdate->desc,
				$price_base_type = 'HT',
			);

			if ($res < 0) {
				$response['message'] = 'Update failed: ' . $object->error;
				dol_syslog("AJAX update_line_price: updateline failed: " . $object->error, LOG_ERR);
				echo json_encode($response);
				exit;
			}

			// Re-fetch object to get updated totals
			$object->fetch($propalId);

			// Find the updated line
			$updatedLine = null;
			foreach ($object->lines as $line) {
				if ($line->id == $lineId) {
					$updatedLine = $line;
					break;
				}
			}

			$response['status'] = 'success';
			$response['message'] = 'Price updated successfully';
			$response['lineTotalHtFormatted'] = price($updatedLine ? $updatedLine->total_ht : 0);
			$response['objectTotalHtFormatted'] = price($object->total_ht);

			dol_syslog("AJAX update_line_price: success");

		} catch (Exception $e) {
			$response['message'] = 'Exception: ' . $e->getMessage();
			dol_syslog("AJAX update_line_price exception: " . $e->getMessage(), LOG_ERR);
		}

		echo json_encode($response);
		exit;

	default:
		break;
}
