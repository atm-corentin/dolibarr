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

		$response = array('success' => false, 'message' => $langs->trans('CliChaumeilSelectError'), 'debug' => array());

		if (!$user->hasRight('supplier_proposal', 'creer') && !$user->hasRight('supplier_proposal', 'cloturer')) {
			$response['message'] = $langs->trans('NotEnoughPermissions');
			echo json_encode($response);
			exit;
		}

		$token = GETPOST('token', 'alphanohtml');
		$parentType = GETPOST('parent_type', 'aZ09');
		$parentId = GETPOST('parent_id', 'int');
		$supplierProposalId = GETPOST('supplier_proposal_id', 'int');

		$response['debug']['received'] = array(
			'parent_type' => $parentType,
			'parent_id' => $parentId,
			'supplier_proposal_id' => $supplierProposalId
		);

		if ($action !== 'choose_subcontractor' || empty($parentType) || empty($parentId) || empty($supplierProposalId)) {
			$response['message'] = $langs->trans('ErrorBadParameter');
			echo json_encode($response);
			exit;
		}

		$parentMap = array(
			'propal' => 'Propal',
			'commande' => 'Commande'
		);

		if (!isset($parentMap[$parentType])) {
			$response['message'] = $langs->trans('ErrorBadParameter');
			echo json_encode($response);
			exit;
		}

		$parentClass = $parentMap[$parentType];
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		$parent = new $parentClass($db);
		if ($parent->fetch($parentId) <= 0) {
			$response['message'] = $langs->trans('ErrorRecordNotFound');
			$response['debug']['parent_fetch'] = 'ko';
			echo json_encode($response);
			exit;
		} else {
			$response['debug']['parent_fetch'] = 'ok';
		}

		$supplierProposals = SupplierProposalService::loadLinkedSupplierProposals($parent, $db);
		$supplierProposals = SupplierProposalService::preloadThirdparties($supplierProposals, $db);

		if (empty($supplierProposals)) {
			$response['message'] = $langs->trans('CliChaumeilNoSupplierProposal');
			$response['debug']['linked'] = 'empty';
			echo json_encode($response);
			exit;
		} else {
			$response['debug']['linked_count'] = count($supplierProposals);
		}

		$linkedIds = array();
		foreach ($supplierProposals as $proposal) {
			if (!empty($proposal->id)) {
				$linkedIds[] = (int) $proposal->id;
			}
		}
		$response['debug']['linked_ids'] = $linkedIds;

		if (!in_array((int) $supplierProposalId, $linkedIds, true)) {
			$response['message'] = $langs->trans('CliChaumeilProposalNotLinked');
			echo json_encode($response);
			exit;
		}

		$db->begin();
		$errorMessage = '';

		foreach ($supplierProposals as $proposal) {
			if (empty($proposal->id)) {
				continue;
			}

			$targetStatus = ((int) $proposal->id === (int) $supplierProposalId) ? SupplierProposal::STATUS_SIGNED : SupplierProposal::STATUS_NOTSIGNED;
			$currentStatus = isset($proposal->status) ? (int) $proposal->status : (int) $proposal->statut;

			if ($currentStatus === $targetStatus) {
				continue;
			}

			$supplierProposal = new SupplierProposal($db);
			if ($supplierProposal->fetch((int) $proposal->id) <= 0) {
				$errorMessage = $langs->trans('ErrorRecordNotFound');
				$response['debug']['fetch_fail'] = $proposal->id;
				break;
			}

			if (method_exists($supplierProposal, 'fetch_thirdparty')) {
				$supplierProposal->fetch_thirdparty();
			}

			$result = $supplierProposal->cloture($user, $targetStatus, '');
			if ($result > 0) {
				$response['debug']['updated'][] = array('id' => $supplierProposal->id, 'status' => $targetStatus);
				continue;
			}

			$errorMessage = 'Update failed for proposal '.$supplierProposal->id.' : '.($db->lasterror() ? $db->lasterror() : $langs->trans('CliChaumeilSelectError'));
			$response['debug']['updated'][] = array('id' => $supplierProposal->id, 'status' => $targetStatus, 'error' => $errorMessage);
			dol_syslog('CliChaumeil choose_subcontractor update failed for proposal '.$supplierProposal->id.' : '.$errorMessage, LOG_ERR);
			break;
		}

		if (!empty($errorMessage)) {
			$db->rollback();
			$response['message'] = $errorMessage;
			$response['debug']['rollback'] = true;
			echo json_encode($response);
			exit;
		}

		$db->commit();
		setEventMessages($langs->trans('CliChaumeilSubcontractorChosen'), null, 'mesgs');
		$response['success'] = true;
		$response['message'] = '';
		$response['debug']['status'] = 'ok';
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

			SupplierProposalService::ensureThirdpartyLoaded($object);

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

			dol_syslog("AJAX update_line_price: Calling updateline with lineId=" . $lineToUpdate->id . ", pu=$newPuHt, qty=" . $lineToUpdate->qty . ", type=" . $lineToUpdate->product_type);

			$updateResult = $service->updateLinePricePreservingStatus($object, $lineToUpdate, $newPuHt, $user);
			dol_syslog("AJAX update_line_price: service update result=" . (empty($updateResult['success']) ? 'KO' : 'OK'));

			if (empty($updateResult['success'])) {
				$errorCode = $updateResult['error_code'] ?? 'UPDATE_FAILED';
				$errorMessage = $updateResult['message'] ?? '';
				if ($errorCode === 'UPDATE_METHOD_MISSING') {
					$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_UPDATE_METHOD_MISSING');
				} elseif ($errorCode === 'RESTORE_STATUS_FAILED') {
					$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_RESTORE_STATUS_FAILED', $errorMessage);
				} else {
					$response['message'] = $langs->trans('CLICHAUMEIL_AJAX_UPDATE_FAILED', $errorMessage);
				}
				dol_syslog("AJAX update_line_price: service update failed: " . $errorMessage, LOG_ERR);
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
