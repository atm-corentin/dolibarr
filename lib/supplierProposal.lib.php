<?php
/*
 * Library file for supplier proposal card view and interactions
 * Adapted from ticket.lib.php
 */

// Include the correct class from the external module
include_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

dol_include_once('user/class/user.class.php');
dol_include_once('core/lib/functions2.lib.php');
dol_include_once('core/lib/files.lib.php');
dol_include_once('core/class/extrafields.class.php');
dol_include_once('externalaccess/class/html.formexternal.class.php');
dol_include_once('comm/action/class/actioncomm.class.php'); // Needed for timeline
dol_include_once('externalaccess/class/ExternalFormTicket.class.php');
dol_include_once('/subtotal/class/subtotal.class.php');
dol_include_once('/subtotal/class/actions_subtotal.class.php');


/**
 * Generate a unique filename in a directory by adding a counter suffix if file already exists
 *
 * @param string $directory Directory where the file will be placed
 * @param string $filename Original filename
 * @return string Unique filename (may have _1, _2, etc. suffix if original exists)
 */
function getUniqueFilename($directory, $filename)
{
	// Clean the filename
	$filename = dol_sanitizeFileName(dol_string_nohtmltag(basename($filename)));

	$dest = $directory . '/' . $filename;

	// If file doesn't exist, return original filename
	if (!file_exists($dest)) {
		return $filename;
	}

	// File exists, generate unique name with counter
	$pathinfo = pathinfo($filename);
	$basename = $pathinfo['filename'];
	$extension = !empty($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';

	$counter = 1;
	while (file_exists($directory . '/' . $basename . '_' . $counter . $extension)) {
		$counter++;
	}

	return $basename . '_' . $counter . $extension;
}


/**
 * Fetch supplier proposal without using getEntity() for multicompany compatibility
 * This is needed for external access where getEntity() may not work properly
 *
 * @param int $id Supplier proposal ID
 * @return SupplierProposal|false Object if found, false otherwise
 */
function fetchSupplierProposalLines($id)
{
	global $db, $conf, $user;

	if (empty($id)) {
		return false;
	}

	$sql = 'SELECT sp.rowid FROM ' . $db->prefix() . 'supplier_proposal sp';
	$sql .= ' WHERE sp.rowid = ' . intval($id);
	if (!empty($user->socid)) {
		$sql .= ' AND sp.fk_soc = ' . intval($user->socid);
	}

	$resql = $db->query($sql);
	if (!$resql || $db->num_rows($resql) == 0) {
		return false;
	}

	$object = new SupplierProposal($db);

	// Fetch main data with project information
	$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.fk_soc, sp.fk_projet, sp.datec, sp.date_valid,';
	$sql .= ' sp.date_livraison, sp.total_ht, sp.total_tva, sp.total_ttc, sp.fk_statut,';
	$sql .= ' sp.note_private, sp.note_public, sp.entity,';
	$sql .= ' sp.multicurrency_code, sp.multicurrency_tx, sp.multicurrency_total_ht,';
	$sql .= ' sp.multicurrency_total_tva, sp.multicurrency_total_ttc,';
	$sql .= ' p.ref as project_ref, p.title as project_title';
	$sql .= ' FROM ' . $db->prefix() . 'supplier_proposal sp';
	$sql .= ' LEFT JOIN ' . $db->prefix() . 'projet p ON sp.fk_projet = p.rowid';
	$sql .= ' WHERE sp.rowid = ' . intval($id);
	$resql = $db->query($sql);

	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			// Populate object properties
			$object->id = $obj->rowid;
			$object->ref = $obj->ref;
			$object->ref_ext = $obj->ref_ext;
			$object->socid = $obj->fk_soc;
			$object->fk_project = $obj->fk_projet;
			$object->date_creation = $db->jdate($obj->datec);
			$object->date_validation = $db->jdate($obj->date_valid);
			$object->delivery_date = $db->jdate($obj->date_livraison);
			$object->total_ht = $obj->total_ht;
			$object->total_tva = $obj->total_tva;
			$object->total_ttc = $obj->total_ttc;
			$object->status = $obj->fk_statut;
			$object->note_private = $obj->note_private;
			$object->note_public = $obj->note_public;
			$object->entity = $obj->entity;
			$object->multicurrency_code = $obj->multicurrency_code;
			$object->multicurrency_tx = $obj->multicurrency_tx;
			$object->multicurrency_total_ht = $obj->multicurrency_total_ht;
			$object->multicurrency_total_tva = $obj->multicurrency_total_tva;
			$object->multicurrency_total_ttc = $obj->multicurrency_total_ttc;
			// Project information
			$object->project_ref = $obj->project_ref;
			$object->project_title = $obj->project_title;
			$object->project = !empty($obj->project_ref) ? $obj->project_ref . (!empty($obj->project_title) ? ' - ' . $obj->project_title : '') : '';

			// Fetch lines manually (fetch_lines() doesn't exist for SupplierProposal)
			// Join with product and product_fournisseur_price tables to get product ref and supplier ref
			$sqlLines = 'SELECT spd.rowid, spd.fk_supplier_proposal, spd.fk_parent_line, spd.description, spd.qty,';
			$sqlLines .= ' spd.subprice, spd.tva_tx, spd.localtax1_tx, spd.localtax2_tx,';
			$sqlLines .= ' spd.total_ht, spd.total_tva, spd.total_localtax1, spd.total_localtax2, spd.total_ttc,';
			$sqlLines .= ' spd.fk_product, spd.product_type, spd.label, spd.fk_unit, spd.rang, spd.special_code,';
			$sqlLines .= ' spd.multicurrency_subprice, spd.multicurrency_total_ht, spd.multicurrency_total_tva, spd.multicurrency_total_ttc,';
			$sqlLines .= ' p.ref as product_ref, pfp.ref_fourn as ref_supplier';
			$sqlLines .= ' FROM ' . $db->prefix() . 'supplier_proposaldet spd';
			$sqlLines .= ' LEFT JOIN ' . $db->prefix() . 'product p ON spd.fk_product = p.rowid';
			$sqlLines .= ' LEFT JOIN ' . $db->prefix() . 'product_fournisseur_price pfp ON pfp.fk_product = spd.fk_product';
			$sqlLines .= ' AND pfp.fk_soc = ' . intval($object->socid);
			$sqlLines .= ' WHERE spd.fk_supplier_proposal = ' . intval($object->id);
			$sqlLines .= ' ORDER BY spd.rang ASC';

			$resqlLines = $db->query($sqlLines);
			if ($resqlLines) {
				$object->lines = array();
				$numLines = $db->num_rows($resqlLines);
				$i = 0;
				while ($i < $numLines) {
					$objLine = $db->fetch_object($resqlLines);

					$line = new SupplierProposalLine($db);
					$line->id = $objLine->rowid;
					$line->fk_supplier_proposal = $objLine->fk_supplier_proposal;
					$line->fk_parent_line = $objLine->fk_parent_line;
					$line->desc = $objLine->description;
					$line->qty = $objLine->qty;
					$line->subprice = $objLine->subprice;
					$line->tva_tx = $objLine->tva_tx;
					$line->localtax1_tx = $objLine->localtax1_tx;
					$line->localtax2_tx = $objLine->localtax2_tx;
					$line->total_ht = $objLine->total_ht;
					$line->total_tva = $objLine->total_tva;
					$line->total_localtax1 = $objLine->total_localtax1;
					$line->total_localtax2 = $objLine->total_localtax2;
					$line->total_ttc = $objLine->total_ttc;
					$line->fk_product = $objLine->fk_product;
					$line->product_type = $objLine->product_type;
					$line->ref_supplier = $objLine->ref_supplier; // From product_fournisseur_price table
					$line->product_ref = $objLine->product_ref; // Original ref from supplier_proposaldet
					$line->label = $objLine->label;
					$line->fk_unit = $objLine->fk_unit;
					$line->rang = $objLine->rang;
					$line->special_code = $objLine->special_code;
					$line->multicurrency_subprice = $objLine->multicurrency_subprice;
					$line->multicurrency_total_ht = $objLine->multicurrency_total_ht;
					$line->multicurrency_total_tva = $objLine->multicurrency_total_tva;
					$line->multicurrency_total_ttc = $objLine->multicurrency_total_ttc;

					$object->lines[] = $line;
					$i++;
				}
				$db->free($resqlLines);
			}
			// Fetch extrafields
			$object->fetch_optionals();
			return $object;
		}
	}
	return false;
}

/**
 * Main function to handle POST actions and display the supplier proposal card.
 *
 * @param int $supplierPropalId
 * @param int $socId
 * @param string $action
 * @return void
 */
function printSupplierProposalCard($supplierPropalId = 0, $socId = 0, $action = '')
{
	global $user, $langs, $db, $conf;

	$langs->loadLangs(array("clichaumeil@clichaumeil", "externalaccess@externalaccess"));

	$context = Context::getInstance();

	// --- Standard Page Load & POST Actions ---
	// Use custom fetch to avoid getEntity() issue in multicompany with external access
	$object = fetchSupplierProposalLines($supplierPropalId);
	if (!$object) {
		$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
		return;
	}

	// --- Handle POST Actions ---
	$postAction = GETPOST('action', 'alpha');
	$trackid = $object->id;

	dol_syslog("Supplier Proposal Card: postAction = " . $postAction, LOG_DEBUG);

	if ($postAction == 'add-comment-file') {
		// --- ACTION: Upload file to session ---

		// Upload file into session
		if (!empty($_FILES['addedfile']['name'])) {
			$uploadDir = $conf->admin->dir_temp ? $conf->admin->dir_temp : DOL_DATA_ROOT . '/admin/temp';
			dol_syslog("Supplier Proposal: Uploading file to session with trackid=" . $trackid . ", upload_dir=" . $uploadDir, LOG_DEBUG);

			$result = dol_add_file_process(
				$uploadDir,                     // Temporary upload directory
				0,                               // Don't allow overwrite
				0,                               // Store in session (not in DB)
				'addedfile',                     // Name of file input field
				'',                              // No savingdocmask
				null,                            // No link
				$trackid,                        // Track ID for session key
				1,                               // Generate thumbnails
				null                             // No object yet
			);

			dol_syslog("Supplier Proposal: Upload result = " . $result, LOG_DEBUG);

			if ($result > 0) {
				$context->setEventMessages($langs->trans('CLICHAUMEIL_FILEADDED'), 'mesgs');
			} elseif ($result < 0) {
				$context->setEventMessages($langs->trans('ErrorFileUpload'), 'errors');
			}
		}

	} elseif ($postAction == 'validate_proposal') {
		// 2. Validate the proposal

		// First, move any files from session to proposal directory (files uploaded but not yet attached to a comment)
		$keytoavoidconflict = '-' . $object->id;
		if (!empty($_SESSION["listofpaths".$keytoavoidconflict]) && !empty($_SESSION["listofnames".$keytoavoidconflict])) {
			$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
			$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);

			$uploadDirProposal = $conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
			dol_mkdir($uploadDirProposal);

			foreach ($listofpaths as $key => $val) {
				$src = $val;
				$filename = $listofnames[$key];

				dol_syslog("Validation: Moving session file to proposal dir: " . $filename, LOG_DEBUG);

				// Generate unique filename if file already exists in proposal directory
				$uniqueFilename = getUniqueFilename($uploadDirProposal, $filename);
				if ($uniqueFilename != $filename) {
					dol_syslog("Validation: File already exists, renamed to: " . $uniqueFilename, LOG_DEBUG);
				}

				// Move to supplier proposal directory
				$destProposal = $uploadDirProposal . '/' . $uniqueFilename;
				if (dol_move($src, $destProposal)) {
					// Index in ECM for proposal
					addFileIntoDatabaseIndex($uploadDirProposal, $uniqueFilename, '', 'uploaded', 0, $object);
					dol_syslog("Validation: File moved and indexed: " . $uniqueFilename, LOG_DEBUG);
				} else {
					dol_syslog("Validation: Failed to move file: " . $uniqueFilename, LOG_WARNING);
				}
			}

			// Clear session
			unset($_SESSION["listofpaths".$keytoavoidconflict]);
			unset($_SESSION["listofnames".$keytoavoidconflict]);
			unset($_SESSION["listofmimes".$keytoavoidconflict]);
		}

		// Check if file attachment is mandatory
		if (getDolGlobalInt('CLICHAUMEIL_MENDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL')) {
			$hasFile = false;

			// Check 1: Files already saved in proposal directory
			$uploadDir = $conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
			dol_syslog("Checking files in directory: " . $uploadDir, LOG_DEBUG);
			if (is_dir($uploadDir)) {
				// Check all files in directory (not just PDF)
				$allFiles = dol_dir_list($uploadDir, 'files', 0, '', null, 'date', SORT_DESC);
				dol_syslog("All files in directory: " . count($allFiles) . " - " . print_r(array_column($allFiles, 'name'), true), LOG_DEBUG);

				if (!empty($allFiles)) {
					$hasFile = true;
				}
			} else {
				dol_syslog("Directory does not exist: " . $uploadDir, LOG_DEBUG);
			}

			// Check 2: Files in session (uploaded but not yet saved with a comment)
			// Note: This should normally be empty since we moved files from session above
			if (!$hasFile) {
				$keytoavoidconflict = '-' . $object->id;
				dol_syslog("Checking session key: listofnames" . $keytoavoidconflict, LOG_DEBUG);
				if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
					$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
					dol_syslog("Session files: " . print_r($listofnames, true), LOG_DEBUG);
					if (!empty($listofnames) && !empty($listofnames[0])) {
						dol_syslog("Found file in session: " . $listofnames[0], LOG_DEBUG);
						$hasFile = true;
					}
				} else {
					dol_syslog("No files in session for key: listofnames" . $keytoavoidconflict, LOG_DEBUG);
				}
			}

			dol_syslog("Final hasFile result: " . ($hasFile ? 'true' : 'false'), LOG_DEBUG);

			if (!$hasFile) {
				$context->setEventMessages($langs->trans('CLICHAUMEIL_ERROR_NO_PDF_ATTACHED'), 'errors');
			} else {
				// File is attached, proceed with validation
				$object->array_options["options_clichaumeil_supplierstatut"] = $langs->transnoentities('CLICHAUMEIL_FILE_RECEIVED');
				$res = $object->updateExtraField('clichaumeil_supplierstatut');

				if ($res >= 0) {
					$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALVALIDATED'), 'mesgs');
				} else {
					$context->setEventMessages($object->error, 'errors');
				}
			}
		} else {
			// No mandatory PDF check, proceed with validation
			$object->array_options["options_clichaumeil_supplierstatut"] = $langs->transnoentities('CLICHAUMEIL_FILE_RECEIVED');
			$res = $object->updateExtraField('clichaumeil_supplierstatut');

			if ($res >= 0) {
				$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALVALIDATED'), 'mesgs');
			} else {
				$context->setEventMessages($object->error, 'errors');
			}
		}

	} elseif ($postAction == 'new-comment') {
		$comment = GETPOST('propal-comment', 'aZ09');
		$title = GETPOST('propal-title', 'aZ09');

		// Check if there are files in session
		$keytoavoidconflict = '-' . $object->id;
		$listofpaths = array();
		$listofnames = array();

		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) {
			$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		}
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
			$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		}

		// Process if there's a comment OR files to attach
		if (!empty($comment) || !empty($listofpaths)) {
			$res = 0; // Will hold action ID

			// Create action/comment if there's a comment OR files to attach
			// If only files without comment, use a default message
			$actioncomm = new ActionComm($db);
			$actioncomm->datep = dol_now();

			// Set note: use comment if provided, otherwise use default message for file attachment
			if (!empty($comment)) {
				$actioncomm->note_private = $comment;
			} else {
				// Only files without comment - use a default message
				$actioncomm->note_private = $langs->trans('CLICHAUMEIL_FILE_ATTACHED_WITHOUT_MESSAGE');
			}

			$actioncomm->elementtype = 'supplier_proposal';
			$actioncomm->elementid = $object->id;
			$actioncomm->user_creation_id = $user->id;
			$actioncomm->userownerid = $user->id;
			$actioncomm->type_code = 'AC_OTH';
			$actioncomm->label = ($title == "") ? $langs->trans("CLICHAUMEIL_LABEL_OWNER", $user->firstname . ' '. $user->lastname) : $title;
			$actioncomm->entity = $conf->entity;

			$res = $actioncomm->create($user);

			if ($res < 0) {
				$context->setEventMessages($langs->trans('CommentError'), 'error');
				return;
			}

			// Handle file attachments from session
			if (!empty($listofpaths)) {
				// 1. Supplier Proposal directory
				$uploadDirProposal = $conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
				dol_mkdir($uploadDirProposal);

				// 2. Action/Agenda directory (we always have an action now)
				$uploadDirAction = $conf->agenda->dir_output . '/' . $res;
				dol_mkdir($uploadDirAction);

				foreach ($listofpaths as $key => $val) {
					$src = $val;
					$filename = $listofnames[$key];

					dol_syslog("Processing file: " . $filename . " from " . $src, LOG_DEBUG);

					// Generate unique filename for proposal directory
					$uniqueFilename_proposal = getUniqueFilename($uploadDirProposal, $filename);
					if ($uniqueFilename_proposal != $filename) {
						dol_syslog("File already exists in proposal dir, renamed to: " . $uniqueFilename_proposal, LOG_DEBUG);
					}

					// Copy to supplier proposal directory
					$destProposal = $uploadDirProposal . '/' . $uniqueFilename_proposal;
					$copyResult = dol_copy($src, $destProposal);
					dol_syslog("Copy to proposal dir result: " . ($copyResult ? 'SUCCESS' : 'FAILED') . " - Destination: " . $destProposal, LOG_DEBUG);

					if ($copyResult) {
						// Index in ECM for proposal
						$ecmResult = addFileIntoDatabaseIndex($uploadDirProposal, $uniqueFilename_proposal, '', 'uploaded', 0, $object);
						dol_syslog("ECM indexing result: " . $ecmResult, LOG_DEBUG);
					}

					// Generate unique filename for action directory
					$uniqueFilename_action = getUniqueFilename($uploadDirAction, $filename);
					if ($uniqueFilename_action != $filename) {
						dol_syslog("File already exists in action dir, renamed to: " . $uniqueFilename_action, LOG_DEBUG);
					}

					// Move to action directory
					$destAction = $uploadDirAction . '/' . $uniqueFilename_action;
					$moveResult = dol_move($src, $destAction);
					dol_syslog("Move to action dir result: " . ($moveResult ? 'SUCCESS' : 'FAILED') . " - Destination: " . $destAction, LOG_DEBUG);
				}

				// Clear session
				unset($_SESSION["listofpaths".$keytoavoidconflict]);
				unset($_SESSION["listofnames".$keytoavoidconflict]);
				unset($_SESSION["listofmimes".$keytoavoidconflict]);
			}

			// Set appropriate success message
			if (!empty($comment) && !empty($listofpaths)) {
				$context->setEventMessages($langs->trans('CommentAdded'), 'mesgs');
			} elseif (!empty($comment)) {
				$context->setEventMessages($langs->trans('CommentAdded'), 'mesgs');
			} else {
				$context->setEventMessages($langs->trans('FileUploaded'), 'mesgs');
			}
		}
	} else {
		// --- Handle file removal (when no specific action is set) ---
		$removedfile_nb = GETPOST('removedfile', 'int');

		// If no value in hidden field, check individual button names (removedfile_0, removedfile_1, etc.)
		if (empty($removedfile_nb)) {
			// Check $_POST directly for removedfile_X buttons
			foreach ($_POST as $key => $value) {
				if (preg_match('/^removedfile_(\d+)$/', $key, $matches)) {
					$removedfile_nb = (int)$matches[1] + 1;
					dol_syslog("Supplier Proposal: Detected removal button click: " . $key . " = " . $value, LOG_DEBUG);
					break;
				}
			}
		}

		if ($removedfile_nb > 0) {
			dol_syslog("Supplier Proposal: Removing file #" . $removedfile_nb . " with trackid=" . $trackid, LOG_DEBUG);
			$result = dol_remove_file_process($removedfile_nb, 0, 0, $trackid);
			if ($result > 0) {
				$context->setEventMessages($langs->trans('FileRemoved'), 'mesgs');
			} else {
				$context->setEventMessages($langs->trans('ErrorFileRemove'), 'errors');
			}
		}
	}

	// --- Display View ---
	return printSupplierPropalCardView($supplierPropalId, $socId, $action);
}

/**
 * Display the supplier proposal (view mode)
 *
 * @param int $supplierPropalId
 * @param int $socId
 * @param string $action
 * @return string
 */
function printSupplierPropalCardView($supplierPropalId = 0, $socId = 0, $action = '')
{
	global $langs, $db, $conf, $user, $hookmanager;

	$url = dol_buildpath('/clichaumeil/script/interface.php', 1);

	$langs->load('clichaumeil@clichaumeil');
	$langs->load('externalticket');

	$context = Context::getInstance();
	$out = '';

	// Load needed language files
	$langs->load('fourn');
	$langs->load('supplier_proposal@supplier_proposal'); // Load module's lang
	$langs->load('externalaccess@externalaccess');

	// Check module activation and rights
	if (!getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')) {
		return '';
	}
	if (!empty($context->fetchedSupplierPropal)) {
		$object = $context->fetchedSupplierPropal;
	} else {
		if (!empty($supplierPropalId)) {
			// Use custom fetch to avoid getEntity() issue in multicompany with external access
			$object = fetchSupplierProposalLines($supplierPropalId);
			if ($object) {
				$context->fetchedSupplierPropal = $object;
			}
		}
	}

	if (empty($object->id)) {
		$context->controller_found = false;
		return '';
	}

	// Security: Check if this proposal belongs to the user's company
	if ($object->socid != $user->socid) {
		$context->controller_found = false;
		return '';
	}

	// Get currency code for price display
	$currency_code = !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;

	// Navigation bar
	$outEaNavbar = getEaNavbar($context->getControllerUrl('supplier_proposal'));

	// Get attached documents (using a renamed helper)
	$documents = externalAccessGetSupplierPropalEcmList($object, true);
	$docFooter = '';

	if (!empty($documents)) {
		$docFooter .= '<div class="panel-footer">';
		// ... (file list logic) ...
		foreach ($documents as $doc) {
			$docFooter .= '<span id="document_' . $doc->id . '" class="timeline-documents" ';
			$docFooter .= ' data-id="' . $doc->id . '" ';
			$docFooter .= ' data-path="' . $doc->filepath . '"';
			$docFooter .= ' data-filename="' . dol_escape_htmltag($doc->filename) . '" ';
			$docFooter .= '>';

			$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
			$mime = dol_mimetype($filePath);

			$mimeAttr = ' mime="' . $mime . '" ';
			$class = '';
			if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
				$class .= ' documentpreview';
			}

			if (!empty($doc->share)) {
				$doclink = $context->getControllerUrl(false, array('action' => 'get-file', 'share' => $doc->share)) . 'script/interface.php?action=get-file&amp;share=' . $doc->share;
				$docFooter .= '<a href="' . $doclink . '" class="btn-link ' . $class . '" target="_blank"  ' . $mimeAttr . ' >';
				$docFooter .= img_mime($filePath) . ' ' . $doc->filename;
				$docFooter .= '</a>';
			} else {
				$docFooter .= img_mime($filePath) . ' ' . $doc->filename;
			}

			$docFooter .= '</span>';
		}
		$docFooter .= '</div>';
	}

	// --- Extrafields ---
	$extrafieldsHtml = print_supplierPropalCard_extrafields($object);
	$panelBodyTop = '';

	$parameters = array(
		'controller' => $context->controller,
		'out' => & $out,
		'outEaNavbar' => & $outEaNavbar,
		'docFooter' => & $docFooter,
		'extrafieldsHtml' => & $extrafieldsHtml,
		'panelBodyTop' => & $panelBodyTop,
	);

	$reshook = $hookmanager->executeHooks('externalAccessSupplierPropalCardSummary', $parameters, $object, $context->action);
	if ($reshook > 0) {
		$out .= $hookmanager->resPrint;
	} elseif ($reshook < 0) {
		$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} else {

		$tmpSoc = new Societe($db);
		$tmpSoc->fetch($object->socid);

		// --- Main Proposal Summary ---
		$out .= '<div class="container px-0">';
		$out .= $outEaNavbar;
		$out .= '
          <h5>' . $langs->trans('CLICHAUMEIL_SUPPLIERPROPOSAL', $object->ref ) . '</h5>
          <div class="panel panel-default" id="propal-summary">
             <div class="panel-body">
                ' . $panelBodyTop . '
                 <div class="row clearfix form-group" id="ref_supplier">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_REFNAME') . '</div>
                   <div class="col-md-10">' . $tmpSoc->name . '</div>
                </div>
                <div class="row clearfix form-group" id="ref_ext">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_REFSUPPLIER') . '</div>
                   <div class="col-md-10">' . $object->ref_ext . '</div>
                </div>
                 <div class="row clearfix form-group" id="ref_ext">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_PROJECT') . '</div>
                   <div class="col-md-10">' . $object->project_ref . '</div>
                </div>
                <div class="row clearfix form-group" id="status">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_STATUS') . '</div>
                   <div class="col-md-10">' . $object->array_options["options_clichaumeil_supplierstatut"] . '</div>
                </div>
                <div class="row clearfix form-group" id="DateCreation">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_DATECREATION') . '</div>
                   <div class="col-md-10">' . dol_print_date($object->date_creation, 'dayhour') . '</div>
                </div>
                <div class="row clearfix form-group" id="TotalHT">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_TOTALHT') . '</div>
                   <!-- Add ID for jQuery update -->
                   <div class="col-md-10" id="object-total-ht">' . price($object->total_ht, 0, $langs, 1, 2, -1, $currency_code) . '</div>
                </div>
                ' . $extrafieldsHtml . '
             </div>
             ' . $docFooter . '
          </div>
       </div>';
	}

	// --- WRAP EVERYTHING IN ONE FORM ---
	$out .= '<form role="form" autocomplete="off" class="form" method="post" enctype="multipart/form-data" action="' . $context->getControllerUrl('supplier_proposal_card') . '&id=' . $object->id . '&time=' . time() . '">';
	$out .= '<input type="hidden" name="token" value="' . newToken() . '" />';
	$out .= '<input type="hidden" name="id" value="' . $object->id . '" />';

	// --- SECTION: Proposal Lines ---
	$out .= '
    <div class="container px-0" style="margin-top: 20px;">
        <h5>' . $langs->trans('Lines') . '</h5>
        <div class="table-responsive">
            <table class="table table-striped" id="supplier-propal-lines">
                <thead>
                    <tr>
                        <th style="width: 10%;">' . $langs->trans('Ref') . '</th>
                        <th style="width: 10%;">' . $langs->trans('RefSuppllier') . '</th>
                        <th style="width: 45%;">' . $langs->trans('Description') . '</th>
                        <th class="text-right" style="width: 15%;">' . $langs->trans('Qty') . '</th>
                        <th class="text-right" style="width: 15%;">' . $langs->trans('UnitPriceHT') . '</th>
                        <th class="text-right" style="width: 210%;">' . $langs->trans('TotalHT') . '</th>
                    </tr>
                </thead>
                <tbody>';

	if (!empty($object->lines) && is_array($object->lines)) {
		foreach ($object->lines as $key => $line) {
			if (!isModEnabled('subtotal') || !TSubtotal::isModSubtotalLine($line)) {
				$out .= '<tr>
                      <td>' . nl2br($line->product_ref) . '</td>
                      <td>' . nl2br($line->ref_supplier) . '</td>
                      <td>' . nl2br($line->label) . '<span style="display: block;">'. nl2br($line->desc) . '</span></td>
                      <td class="text-right">' . $line->qty . '</td>
                      <td class="text-right">';

				// Add class "line-price-input" and data-line-id for jQuery
				$out .= '<input type="text" class="form-control text-right line-price-input"
						name="line_prices[' . $line->id . ']"
						value="' . price($line->subprice, 0, $langs, 0, 2, -1, '', 1) . '"
						data-line-id="' . $line->id . '" />';

				$out .= '   </td>
                      <!-- Add ID for jQuery update -->
                      <td class="text-right" id="line-total-' . $line->id . '">' . price($line->total_ht, 0, $langs, 1, 2, -1, $currency_code) . '</td>
                   </tr>';
			} else {
				if (TSubtotal::isSubtotal($line)) {
					$subtotal_amount = getTotalLineFromObject($object, $line, getDolGlobalString('SUBTOTAL_USE_NEW_FORMAT'), false);
					$out .= '<tr>
                      <td colspan="2">' . nl2br($line->label) . '</td>
                      <td>' . $line->desc . '</td>
                      <td class="text-right"></td>
                      <td class="text-right">' . price($subtotal_amount, 0, $langs, 1, 2, -1, $currency_code) . '</td>';
				} elseif (TSubtotal::isFreeText($line)) {
					$out .= '<tr>
                      <td></td>
                      <td colspan="4">' . nl2br($line->desc) . '</td>
					 </tr>';
				} else {
					$out .=
						'<tr>
						  <td></td>
						  <td colspan="4">
							' . nl2br($line->label).
							'<span style="display: block;">'. nl2br($line->desc) . '</span>
						  </td>
					 	</tr>';
				}
			}
		}
	}

	$out .= '       </tbody>
            </table>
        </div>';

	$out .= '<div class="text-right" style="margin-top: 20px;">
				<button type="submit" class="btn btn-success" id="btn-validate-proposal" name="action" value="validate_proposal">
					<i class="fa fa-check"></i> ' . $langs->trans('CLICHAUMEIL_SAVEANDVALIDATE') . '
				</button>
			</div>';

	$out .= '</div>';
	// --- END of Lines Section ---

	// --- SECTION: Timeline Messages ---
	$TMessage = supplierPropalGetActions($object); // Get timeline messages

	if (!empty($TMessage)) {
		// ... (Timeline display logic) ...
		$sortMsg = !empty($user->conf->EA_SPROPAL_MSG_SORT_ORDER) ? $user->conf->EA_SPROPAL_MSG_SORT_ORDER : 'asc';

		$out .= '
          <div class="container px-0" style="margin-top: 20px;">
             <h5>' . $langs->trans('Discussion') . '</h5>
          ';

		$out .= '
          <ul class="timeline">';

		$datelabel = "";
		$iComment = 0;
		$numComments = count($TMessage);

		if ($sortMsg == 'desc') {
			$TMessage = array_reverse($TMessage, true);
		}

		foreach ($TMessage as $actionstatic) {
			if ($datelabel != dol_print_date($actionstatic->datep) && empty($actionstatic->private)) {
				$datelabel = dol_print_date($actionstatic->datep);
				$out .= '<!-- timeline time label -->';
				$out .= '<li class="time-label"><span class="timeline-badge-date">' . $datelabel . '</span></li>';
				$out .= '<!-- /.timeline-label -->';
			}

			if (empty($actionstatic->private)) // Only show public messages
			{
				$out .= '<!-- timeline item -->' . "\n";
				$out .= '<li id="comment-message-' . $actionstatic->id . '" class="timeline-code-' . strtolower($actionstatic->code) . '">';
				$out .= '<!-- timeline icon -->' . "\n";

				$iconClass = 'fa fa-comments'; // Default
				$out .= '<i class="' . $iconClass . '"></i>' . "\n";

				if (++$iComment === $numComments) {
					$out .= '<div id="lastcomment"></div>';
				}

				$out .= '<div class="timeline-item">';
				// Date
				$out .= '<span class="time"><i class="fa fa-clock-o"></i> ';
				$out .= dol_print_date($actionstatic->datep, 'dayhour');
				$out .= '</span>';

				// Header
				$out .= '<h3 class="timeline-header">';
				$out .= '<span class="messaging-author">';
				if ($actionstatic->userownerid > 0) {
					if (!isset($userGetNomUrlCache[$actionstatic->userownerid])) { // is in cache ?
						$fuser = new User($db);
						$fuser->fetch($actionstatic->userownerid);
						$userGetNomUrlCache[$actionstatic->userownerid] = $fuser->getFullName($langs);
					}
					$out .= $userGetNomUrlCache[$actionstatic->userownerid];
				}
				$out .= '</span>';
				$out .= '</h3>';

				// Body
				$out .= '<div class="timeline-body">' . nl2br($actionstatic->note) . '</div>';

				// Files - List files directly from action directory (not from ECM to avoid showing in standard agenda)
				$action_dir = $conf->agenda->dir_output . '/' . $actionstatic->id;
				$files = array();
				if (is_dir($action_dir)) {
					$files = dol_dir_list($action_dir, 'files');
				}

				if (!empty($files)) {
					$footer = '<div class="timeline-documents-container">';
					foreach ($files as $file) {
						$filename = $file['name'];
						$filePath = $file['fullname'];
						$mime = dol_mimetype($filePath);
						$mimeAttr = ' mime="' . $mime . '" ';
						$class = '';
						if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
							$class .= ' documentpreview';
						}

						$footer .= '<span class="timeline-documents" ' . $mimeAttr . '>';

						// Create download link via external access controller
						$doclink = $context->getControllerUrl('supplier_proposal_card', array(
							'id' => $object->id,
							'action' => 'download-action-file',
							'actionid' => $actionstatic->id,
							'filename' => urlencode($filename)
						));
						$footer .= '<a href="' . $doclink . '" class="btn-link ' . $class . '" target="_blank">';
						$footer .= img_mime($filePath) . ' ' . $filename;
						$footer .= '</a>';

						$footer .= '</span>';
					}
					$footer .= '</div>';
					$out .= '<div class="timeline-footer">' . $footer . '</div>';
				}
				$out .= '</div>'; // .timeline-item
				$out .= '</li>';
				$out .= '<!-- END timeline item -->';
			}
		}
		$out .= "</ul>\n</div>";
	}
	// --- END of Timeline Section ---

	// --- SECTION: Comment Form ---
	$out .= '<!-- supplier_proposal.lib START print_supplierPropalCard_comment_form -->';
	$out .= '<div class="container px-0">';
	$out .= '<ul class="timeline ">';
	$out .= '<li class="time-label"><span class="timeline-badge-date"><i class="fa fa-comments" ></i> ' . $langs->transnoentities('CLICHAUMEIL_ADDMESSAGE') . '</span></li>';
	$out .= '<li class="timeline-code-ticket_msg">'; // Use ticket style
	$out .= '<div class="timeline-item">';
	$out .= '<div id="form-propal-message-container" class="timeline-body form-ticket-message-container">';

	$out .= '<div class="form-group">
           <textarea name="propal-comment" class="form-control" id="propal-comment" placeholder="' . $langs->transnoentities('CLICHAUMEIL_YOURCOMMENTHERE') . '" rows="10">' . dol_htmlentities(GETPOST('propal-comment', 'none')) . '</textarea>';
	$out .= '</div>';

	if (getDolGlobalString('FCKEDITOR_ENABLE_TICKET')) { // Re-use ticket conf
		$out .= '<script>CKEDITOR.replace("propal-comment", { enterMode: CKEDITOR.ENTER_BR });</script>';
	}

	//Files
	$formExternal = new ExternalFormTicket($db); // Use this class for its file form
	$formExternal->ref = $object->ref;
	$formExternal->id = $object->id;
	$formExternal->trackid = $object->id; // IMPORTANT: Set trackid to match session key
	$formExternal->withfile = 2;
	$formExternal->withcancel = 1;
	$formExternal->param = array('fk_user_create' => $user->id);
	$out .= '<div class="form-group">';
	$out .= $formExternal->showFilesForm(); // This helper generates the file input
	$out .= '</div>';

	$out .= '</div><!-- end timeline-body -->';
	$out .= '<div class="timeline-footer text-right">';
	$out .= '<div class="btn-group">';

	// 0=Draft, 1=Validated
	// We allow commenting on any status
	$out .= '<button type="submit" class="btn btn-success" name="action" value="new-comment" data-toggle="tooltip" title="' . dol_htmlentities($langs->transnoentities('CLICHAUMEIL_SENDMESSAGEHELP'), ENT_QUOTES) . '"  >' . $langs->transnoentities('CLICHAUMEIL_ADDMESSAGE') . '</button>';

	$out .= '</div><!-- end btn-group -->';
	$out .= '</div><!-- end timeline-footer -->';
	$out .= '</div><!-- end timeline-item -->';
	$out .= '</li>';
	$out .= '</ul>';

	$out .= '<!-- END print_supplierPropalCard_comment_form -->';
	// --- END of Comment Form Section ---

	// Close the single form
	$out .= '</form>';
	$out .= '</div>';

	$out .= '
    <script type="text/javascript">
    $(document).ready(function() {
        console.log("Supplier Proposal price updater initialized");
        console.log("AJAX URL: ' . $url . '");

        // Listen for the "blur" (focus out) event on our price inputs
        $(".line-price-input").on("blur", function() {
            const $input = $(this);
            const lineId = $input.data("line-id");
            const newPrice = $input.val();
            const propalId = ' . $object->id . ';
            const token = "' . newToken() . '";

            // Show loading feedback
            $input.css("background-color", "#fcf8e3"); // warning yellow

            $.ajax({
                type: "POST",
                url: "' . $url . '",
                data: {
                    action: "update_line_price",
                    token: token,
                    propalId: propalId,
                    lineId: lineId,
                    newPrice: newPrice
                },
                dataType: "json",
                timeout: 10000
            })
            .done(function(response) {
                if (response && response.status === "success") {
                    // Update totals on the page
                    $("#line-total-" + lineId).text(response.lineTotalHtFormatted);
                    $("#object-total-ht").text(response.objectTotalHtFormatted);
                    // Show success feedback
                    $input.css("background-color", "#dff0d8"); // success green
                    setTimeout(function() {
                        $input.css("background-color", "");
                    }, 1000);
                } else {
                    // Show error feedback
                    $input.css("background-color", "#f2dede"); // error red
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                // Show network/server error feedback
                console.error("AJAX fail callback:", {
                    status: jqXHR.status,
                    statusText: textStatus,
                    error: errorThrown,
                    responseText: jqXHR.responseText
                });
                $input.css("background-color", "#f2dede"); // error red
            })
            .always(function() {
                console.log("AJAX always callback - Request completed");
            });
        });

        ' . (getDolGlobalInt('CLICHAUMEIL_MENDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL') ? '
        // Validation for PDF attachment before form submission
        $("#btn-validate-proposal").on("click", function(e) {
            e.preventDefault();
            console.log("=== Validate button clicked ===");

            var form = $(this).closest("form");
            var fileInput = $("#addedfile");

            // Check if there are files to upload in the file input
            if (fileInput.length > 0 && fileInput[0].files.length > 0) {
                console.log("Files found in input, uploading first...");

                // Create FormData to upload file via AJAX
                var formData = new FormData();
                formData.append("action", "add-comment-file");
                formData.append("id", ' . $object->id . ');
                formData.append("token", "' . newToken() . '");

                // Add the file
                if (fileInput[0].files[0]) {
                    formData.append("addedfile", fileInput[0].files[0]);
                }

                // Upload file via AJAX
                $.ajax({
                    type: "POST",
                    url: window.location.href,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log("File uploaded, now submitting validation");
                        // After upload, submit the validation
                        form.find("input[name=action]").remove();
                        form.append(\'<input type="hidden" name="action" value="validate_proposal" />\');
                        form.submit();
                    },
                    error: function() {
                        console.error("Error uploading file");
                        alert("' . dol_escape_js($langs->trans('ErrorFileUpload')) . '");
                    }
                });
            } else {
                console.log("No file in input, submitting validation directly");
                // No file to upload, submit validation directly
                // Server-side will handle file check and show proper error message if needed
                form.find("input[name=action]").remove();
                form.append(\'<input type="hidden" name="action" value="validate_proposal" />\');
                form.submit();
            }
        });
        ' : '') . '
    });
    </script>
    ';
	// --- END OF NEW SCRIPT ---

	// --- Hook for final output ---
	$parameters = array(
		'controller' => $context->controller,
		'out' => & $out,
	);
	$reshook = $hookmanager->executeHooks('externalAccessSupplierPropalCard', $parameters, $object, $context->action);
	if ($reshook > 0) {
		print $hookmanager->resPrint;
	} elseif ($reshook < 0) {
		$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} else {
		print $out; // Print the final compiled output
	}
}


/**
 * Show extrafields for Supplier Proposal
 *
 * @param SupplierProposal $object
 * @return string
 * @throws Exception
 */
function print_supplierPropalCard_extrafields($object)
{
	global $conf, $db, $langs;
	dol_include_once('core/class/extrafields.class.php');
	$out = '';

	$elementType = 'supplier_proposal'; // Use the correct element type

	$e = new ExtraFields($db);
	$e->fetch_name_optionals_label($elementType);

	// Check for a new global conf for supplier propal extrafields
	$TAddedField = getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL') ? explode(',', getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL')) : [];

	if (!empty($TAddedField)) {
		foreach ($TAddedField as $fieldKey) {
			$fieldKey = strtr($fieldKey, array('EXTRAFIELD_' => ''));

			if (empty($e->attributes[$elementType]['label'][$fieldKey])) continue;

			$label = $langs->transnoentities($e->attributes[$elementType]['label'][$fieldKey]);
			$type = $e->attributes[$elementType]['type'][$fieldKey];
			$value = $object->array_options['options_' . $fieldKey];

			$valueFormatted = $e->showOutputField($fieldKey, $value, '', $elementType);

			if ($type == 'separate') {
				$out .= '<hr style="max-width : 100%;">';
			} else {
				$out .= '<div class="row clearfix form-group" id="extrafield-' . $fieldKey . '">';
				$out .= '<div class="col-md-2">' . $label . '</div>';
				$out .= '<div class="col-md-8"> ' . $valueFormatted . '</div> ';
				$out .= '</div > ';
			}
		}
	}
	return $out;
}

/**
 * Fetch all ActionComm events linked to the supplier proposal
 *
 * @param SupplierProposal $object
 * @return array
 */
function supplierPropalGetActions($object)
{
	global $db;

	$TAction = array();

	$sql = "SELECT id as rowid, fk_user_author, email_from, datec, datep, label, note, code"; // note, not note_private
	$sql .= ' FROM ' . $db->prefix() . 'actioncomm';
	$sql .= ' WHERE fk_element = ' . intval($object->id);
	$sql .= ' AND elementtype = "' . $db->escape($object->element) . '"';
	$sql .= ' ORDER BY datep ASC';

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$action = new ActionComm($db);
			$action->fetch($obj->rowid);
			$TAction[] = $action;
		}
	}

	return $TAction;
}


/**
 * Get ECM file list for a specific ActionComm
 *
 * @param ActionComm $object
 * @param bool $pulicOnly
 * @return    array
 */
function externalAccessGetActionCommEcmList($object, $pulicOnly = true)
{
	global $conf, $db;

	$documents = array();

	$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id, ecm.filepath, ecm.filename, ecm.share';
	$sql .= ' FROM ' . $db->prefix() . 'ecm_files ecm';
	$sql .= ' WHERE ecm.filepath = \'agenda/' . intval($object->id) . '\''; // Actions files are in 'agenda'
	if ($pulicOnly) {
		$sql .= ' AND ecm.share IS NOT NULL ';
	}
	$sql .= ' ORDER BY ecm.position ASC';

	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			while ($obj = $db->fetch_object($resql)) {
				$documents[$obj->id] = $obj;
			}
		}
	}
	return $documents;
}


/**
 * Get ECM file list for the main supplier proposal
 *
 * @param SupplierProposal $object
 * @param bool $pulicOnly
 * @return    array
 */
function externalAccessGetSupplierPropalEcmList($object, $pulicOnly = true)
{
	global $conf, $db;
	$documents = array();

	$elementType = 'supplier_proposal';
	$refDir = dol_sanitizeFileName($object->ref);

	$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id, ecm.filepath, ecm.filename, ecm.share';
	$sql .= ' FROM ' . $db->prefix() . 'ecm_files ecm';
	$sql .= ' WHERE ((ecm.src_object_type = \'' . $db->escape($elementType) . '\' ';
	$sql .= ' AND  ecm.src_object_id = ' . intval($object->id) . ') ';
	$sql .= ' OR  ecm.filepath = \'' . $db->escape($elementType . '/' . $refDir) . '\' )';

	if ($pulicOnly) {
		$sql .= ' AND ecm.share IS NOT NULL ';
	}

	$sql .= ' AND ecm.entity = ' . intval($conf->entity) . ' ';
	$sql .= ' ORDER BY ecm.position ASC';

	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			while ($obj = $db->fetch_object($resql)) {
				$documents[$obj->id] = $obj;
			}
		}
	}
	return $documents;
}

?>
