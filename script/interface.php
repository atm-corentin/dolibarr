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
dol_include_once('/clichaumeil/class/SupplierProposalService.class.php');

$action = GETPOST('action', "alpha");
$propalId = GETPOST('propalId', 'int');
$lineId = GETPOST('lineId', 'int');
$newPrice = GETPOST('newPrice', 'alpha');  // Use 'alpha' for decimal numbers, then convert with price2num()
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

			// Use service to fetch proposal (avoids getEntity() issues)
			$service = new SupplierProposalService($db, $conf);
			$object = $service->fetchProposalWithLines($propalId, 0); // 0 = no socid check for AJAX

			if (!$object) {
				$response['message'] = 'Supplier proposal not found';
				dol_syslog("AJAX update_line_price: fetch failed for propalId=$propalId", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			if ($object->socid != $user->socid) {
				dol_syslog("AJAX update_line_price: Unauthorized access attempt by user " . $user->id . " on propalId=$propalId", LOG_ERR);
				accessforbidden();
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

			dol_syslog("AJAX update_line_price: BEFORE setDraft - object->status=" . $object->status . " (0=draft, 1=validated)");

			$draftResult = $object->setDraft($user);
			dol_syslog("AJAX update_line_price: setDraft result=$draftResult, object->status=" . $object->status);

			// Re-fetch to ensure status is updated in object
			$object = $service->fetchProposalWithLines($propalId, 0);
			dol_syslog("AJAX update_line_price: After re-fetch for draft, object->status=" . $object->status);

			// Find the line again after re-fetch
			$lineToUpdate = null;
			foreach ($object->lines as $line) {
				if ($line->id == $lineId) {
					$lineToUpdate = $line;
					break;
				}
			}

			if (!$lineToUpdate) {
				$response['message'] = 'Line not found after draft re-fetch';
				dol_syslog("AJAX update_line_price: lineId=$lineId not found after draft re-fetch", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			// Call the update line method with all necessary parameters
			dol_syslog("AJAX update_line_price: Calling updateline with lineId=" . $lineToUpdate->id . ", pu=$newPuHt, qty=" . $lineToUpdate->qty . ", type=" . $lineToUpdate->product_type);

			$res = $object->updateline(
				$lineToUpdate->id,                    // rowid
				$newPuHt,                              // pu (unit price)
				$lineToUpdate->qty,                    // qty
				$lineToUpdate->remise_percent,         // remise_percent
				$lineToUpdate->tva_tx,                 // txtva
				0,                                     // txlocaltax1
				0,                                     // txlocaltax2
				$lineToUpdate->desc,                   // desc
				'HT',                                  // price_base_type
				$lineToUpdate->info_bits,              // info_bits
				$lineToUpdate->special_code,           // special_code
				$lineToUpdate->fk_parent_line,         // fk_parent_line
				0,                                     // skip_update_total
				0,                                     // fk_fournprice
				0,                                     // pa_ht
				$lineToUpdate->label,                  // label
				$lineToUpdate->product_type,           // type (0=product, 1=service)
				$lineToUpdate->array_options,          // array_options (extrafields)
				$lineToUpdate->ref_fourn,              // ref_supplier
				$lineToUpdate->fk_unit                 // fk_unit
			);

			dol_syslog("AJAX update_line_price: updateline result=$res");

			$validResult = $object->valid($user);
			dol_syslog("AJAX update_line_price: valid result=$validResult");

			if ($res < 0) {
				$response['message'] = 'Update failed: ' . $object->error;
				dol_syslog("AJAX update_line_price: updateline failed: " . $object->error, LOG_ERR);
				echo json_encode($response);
				exit;
			}

			// Re-fetch object to get updated totals using service
			$object = $service->fetchProposalWithLines($propalId, 0);

			dol_syslog("AJAX update_line_price: After re-fetch, object has " . count($object->lines) . " lines");

			// Find the updated line
			$updatedLine = null;
			foreach ($object->lines as $line) {
				dol_syslog("AJAX update_line_price: Checking line id=" . $line->id . " (looking for " . $lineId . ") - total_ht=" . $line->total_ht);
				if ($line->id == $lineId) {
					$updatedLine = $line;
					dol_syslog("AJAX update_line_price: FOUND matching line! total_ht=" . $line->total_ht);
					break;
				}
			}

			if (!$updatedLine) {
				dol_syslog("AJAX update_line_price: WARNING - Line $lineId NOT FOUND after update!", LOG_WARNING);
			}

			$response['status'] = 'success';
			$response['message'] = 'Price updated successfully';
			$response['lineTotalHtFormatted'] = price($updatedLine ? $updatedLine->total_ht : 0);
			$response['objectTotalHtFormatted'] = price($object->total_ht);
			$response['debug'] = array(
				'lineFound' => ($updatedLine !== null),
				'lineTotalHt' => $updatedLine ? $updatedLine->total_ht : null,
				'objectTotalHt' => $object->total_ht,
				'numberOfLines' => count($object->lines)
			);

			dol_syslog("AJAX update_line_price: success - Line total HT: " . ($updatedLine ? $updatedLine->total_ht : 'LINE NOT FOUND'));

		} catch (Exception $e) {
			$response['message'] = 'Exception: ' . $e->getMessage();
			dol_syslog("AJAX update_line_price exception: " . $e->getMessage(), LOG_ERR);
		}

		echo json_encode($response);
		exit;

	default:
		break;
}
