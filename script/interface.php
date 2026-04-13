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

/**
 * Check whether supplier proposal already has at least one attached file
 * either in current upload session or in existing timeline actions.
 *
 * @param SupplierProposal $object Supplier proposal.
 * @param Conf             $conf   Application configuration.
 * @param DoliDB           $db     Database handler.
 * @return bool
 */
function clichaumeilSupplierProposalHasAttachedFile(SupplierProposal $object, Conf $conf, DoliDB $db): bool
{
	$keytoavoidconflict = '-' . $object->id;
	$hasFilesInSession = !empty($_SESSION["listofnames" . $keytoavoidconflict])
		&& !empty($_SESSION["listofpaths" . $keytoavoidconflict]);

	if ($hasFilesInSession) {
		return true;
	}

	$sql = "SELECT id, entity FROM " . $db->prefix() . "actioncomm";
	$sql .= " WHERE fk_element = " . ((int) $object->id);
	$sql .= " AND elementtype = '" . $db->escape($object->element) . "'";

	$resql = $db->query($sql);
	if (!$resql) {
		return false;
	}

	while ($action = $db->fetch_object($resql)) {
		$actionEntity = !empty($action->entity) ? $action->entity : $conf->entity;
		if (empty($conf->agenda->multidir_output[$actionEntity])) {
			continue;
		}

		$actionDir = $conf->agenda->multidir_output[$actionEntity] . '/' . $action->id;
		if (!is_dir($actionDir)) {
			continue;
		}

		$files = dol_dir_list($actionDir, 'files');
		if (!empty($files)) {
			$db->free($resql);
			return true;
		}
	}

	$db->free($resql);
	return false;
}

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
	echo json_encode(array('success' => false, 'message' => 'Invalid security token'));
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

			if (getDolGlobalInt('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL')
					&& !clichaumeilSupplierProposalHasAttachedFile($object, $conf, $db)) {
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
					$lineToUpdate->ref_supplier,           // ref_supplier
					$lineToUpdate->fk_unit                 // fk_unit
				);

				dol_syslog("AJAX update_line_price: updateline result=$res");

			if ($res < 0) {
				$response['message'] = 'Update failed: ' . $object->error;
				dol_syslog("AJAX update_line_price: updateline failed: " . $object->error, LOG_ERR);
				echo json_encode($response);
				exit;
			}

				$validResult = $object->valid($user);
				dol_syslog("AJAX update_line_price: valid result=$validResult");

			if ($validResult < 0) {
				// WARNING: manual rollback of status without full transaction/trigger rollback.
				if ($previousStatus !== null) {
					$db->query("UPDATE " . $db->prefix() . "supplier_proposal SET fk_statut = " . ((int) $previousStatus) . " WHERE rowid = " . ((int) $object->id));
					$object->status = $previousStatus;
					dol_syslog("AJAX update_line_price: restore status to $previousStatus after failed validation");
				}

				$response['message'] = 'Validation failed: ' . $object->error;
				dol_syslog("AJAX update_line_price: validation failed: " . $object->error, LOG_ERR);
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
