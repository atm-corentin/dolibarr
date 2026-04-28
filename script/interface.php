<?php

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
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once __DIR__.'/../class/SupplierProposalService.class.php';
require_once __DIR__.'/../class/Subcontracting/CliChaumeilSupplierOrderConfig.class.php';
require_once __DIR__.'/../class/Subcontracting/CliChaumeilSubcontractorSelectionWorkflow.class.php';

$langs->loadLangs(array('clichaumeil@clichaumeil', 'supplier_proposal'));

$action = GETPOST('action', "alpha");
$propalId = GETPOST('propalId', 'int');
$lineId = GETPOST('lineId', 'int');
$newPrice = GETPOST('newPrice', 'alpha');  // Use 'alpha' for decimal numbers, then convert with price2num()
$newPuHt = price2num($newPrice);
$token = GETPOST('token', 'alphanohtml');

// CSRF protection: token must match current or next token stored in session
if (empty($token) || (!hash_equals((string) $token, (string) newToken()) && !hash_equals((string) $token, (string) currentToken()))) {
	header('Content-Type: application/json');
	http_response_code(403);
	echo json_encode(array('success' => false, 'message' => $langs->trans('CLICHAUMEIL_AJAX_INVALID_SECURITY_TOKEN')));
	exit;
}

switch ($action) {
	case 'choose_subcontractor':
		header('Content-Type: application/json');
		$langs->loadLangs(array('clichaumeil@clichaumeil', 'supplier_proposal'));
		$parentType = GETPOST('parent_type', 'aZ09');
		$parentId = GETPOST('parent_id', 'int');
		$supplierProposalId = GETPOST('supplier_proposal_id', 'int');
		$response = CliChaumeilSupplierOrderConfig::buildAjaxResponse(
			CliChaumeilSupplierOrderConfig::RESULT_ERROR,
			$langs->trans('CliChaumeilSelectError'),
			false,
			false,
			array(
				'parent_type' => $parentType,
				'parent_id' => $parentId,
				'supplier_proposal_id' => $supplierProposalId,
			)
		);

		if ($parentType === '' || $parentId <= 0 || $supplierProposalId <= 0) {
			dol_syslog(__METHOD__.' invalid ST-8 AJAX parameters parent_type='.$parentType.' parent_id='.$parentId.' supplier_proposal_id='.$supplierProposalId, LOG_WARNING);
			$response['message'] = $langs->trans('ErrorBadParameter');
			echo json_encode($response);
			exit;
		}

		$parentMap = array(
			'propal' => 'Propal',
			'commande' => 'Commande'
		);

		if (!isset($parentMap[$parentType])) {
			dol_syslog(__METHOD__.' unsupported ST-8 parent type '.$parentType, LOG_WARNING);
			$response['message'] = $langs->trans('ErrorBadParameter');
			echo json_encode($response);
			exit;
		}

		$parentClass = $parentMap[$parentType];
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

			$parent = new $parentClass($db);
		if ($parent->fetch($parentId) <= 0) {
			dol_syslog(__METHOD__.' unable to fetch ST-8 parent object type='.$parentType.' id='.$parentId, LOG_WARNING);
			$response['message'] = $langs->trans('ErrorRecordNotFound');
			echo json_encode($response);
			exit;
		}
			$restrictedAreaModule = $parentType === 'propal' ? 'propal' : 'commande';
		if (!restrictedArea($user, $restrictedAreaModule, $parent->id, '', '', 'fk_soc', 'rowid', 0, 1)) {
			dol_syslog(__METHOD__.' forbidden ST-8 parent access type='.$parentType.' id='.$parentId.' user='.$user->id, LOG_WARNING);
			$response['message'] = $langs->trans('CliChaumeil_St8ParentAccessForbidden');
			echo json_encode($response);
			exit;
		}

			$workflow = new CliChaumeilSubcontractorSelectionWorkflow($db, $conf, $langs);
			$response = $workflow->execute($parent, $supplierProposalId, $user);
		if (!empty($response['success']) && !empty($response['should_reload']) && !empty($response['message'])) {
			$messageStyle = ((string) ($response['status'] ?? '') === CliChaumeilSupplierOrderConfig::RESULT_WARNING) ? 'warnings' : 'mesgs';
			setEventMessages((string) $response['message'], null, $messageStyle);
		}
			echo json_encode($response);
			exit;

	case 'update_line_price':
		header('Content-Type: application/json'); // We will return JSON
		$response = array('status' => 'error', 'message' => $langs->trans('CLICHAUMEIL_AJAX_UNKNOWN_ERROR'));

		try {
			dol_syslog("AJAX update_line_price: propalId=$propalId, lineId=$lineId, newPrice=$newPuHt");

			if (!$propalId || !$lineId) {
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_MISSING_PROPOSAL_OR_LINE');
				echo json_encode($response);
				exit;
			}

			// Use service to fetch proposal (avoids getEntity() issues)
			$service = new SupplierProposalService($db, $conf);
			$object = $service->fetchProposalWithLines($propalId, 0); // 0 = no socid check for AJAX

			if (!$object) {
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_SUPPLIER_PROPOSAL_NOT_FOUND');
				dol_syslog("AJAX update_line_price: fetch failed for propalId=$propalId", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			if ($object->socid != $user->socid) {
				dol_syslog("AJAX update_line_price: Unauthorized access attempt by user " . $user->id . " on propalId=$propalId", LOG_ERR);
				accessforbidden();
			}

			if (getDolGlobalInt('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL')
				&& !$service->hasAttachedFile($object)) {
				$response['message'] = $langs->trans('CLICHAUMEIL_ERROR_NO_PDF_ATTACHED');
				dol_syslog("AJAX update_line_price: blocking update without attached file for proposal " . $object->id, LOG_WARNING);
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
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_LINE_NOT_FOUND');
				dol_syslog("AJAX update_line_price: lineId=$lineId not found", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			if (!method_exists($object, 'updateline')) {
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_UPDATE_METHOD_MISSING');
				dol_syslog("AJAX update_line_price: updateline method missing", LOG_ERR);
				echo json_encode($response);
				exit;
			}

			$previousStatus = $object->status;
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
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_LINE_NOT_FOUND_AFTER_REFETCH');
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
				$lineToUpdate->ref_supplier,           // ref_supplier
				$lineToUpdate->fk_unit                 // fk_unit
			);

			dol_syslog("AJAX update_line_price: updateline result=$res");

			if ($res < 0) {
				$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_UPDATE_FAILED', $object->error);
				dol_syslog("AJAX update_line_price: updateline failed: " . $object->error, LOG_ERR);
				echo json_encode($response);
				exit;
			}

			if ($previousStatus !== null && (int) $previousStatus !== (int) SupplierProposal::STATUS_DRAFT) {
				$restoreStatusResult = $object->setStatut((int) $previousStatus);
				dol_syslog("AJAX update_line_price: restore status query result=" . ((int) $restoreStatusResult) . " previousStatus=" . ((int) $previousStatus));

				if ($restoreStatusResult < 0) {
					$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_RESTORE_STATUS_FAILED', $object->error ?: $db->lasterror());
					dol_syslog("AJAX update_line_price: failed to restore status to $previousStatus: " . ($object->error ?: $db->lasterror()), LOG_ERR);
					echo json_encode($response);
					exit;
				}
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
			$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_PRICE_UPDATED_SUCCESS');
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
			$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_EXCEPTION', $e->getMessage());
			dol_syslog("AJAX update_line_price exception: " . $e->getMessage(), LOG_ERR);
		}

		echo json_encode($response);
		exit;

	default:
		break;
}
