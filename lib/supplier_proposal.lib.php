<?php
/*
 * Library file for supplier proposal card view and interactions
 * Adapted from ticket.lib.php
 */

// Include the correct class from the external module
include_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';


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
 * Fetch supplier proposal without using getEntity() for multicompany compatibility
 * This is needed for external access where getEntity() may not work properly
 *
 * @param int $id Supplier proposal ID
 * @return SupplierProposal|false Object if found, false otherwise
 */
function fetchSupplierProposalForExternalAccess($id)
{
	global $db, $conf, $user;

	if (empty($id)) {
		return false;
	}

	// First, check if the proposal exists and belongs to the user's company
	// Note: No entity filter to allow access to proposals from all entities
	// Security: Only allow access if the proposal belongs to the user's company
	$sql = 'SELECT sp.rowid FROM ' . $db->prefix() . 'supplier_proposal sp';
	$sql .= ' WHERE sp.rowid = ' . intval($id);
	if (!empty($user->socid)) {
		$sql .= ' AND sp.fk_soc = ' . intval($user->socid);
	}

	$resql = $db->query($sql);
	if (!$resql || $db->num_rows($resql) == 0) {
		return false;
	}

	// Now fetch the object normally, knowing it exists in the right entity
	// We temporarily override the fetch method by fetching data manually
	$object = new SupplierProposal($db);

	// Fetch main data
	$sql = 'SELECT * FROM ' . $db->prefix() . 'supplier_proposal WHERE rowid = ' . intval($id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			// Populate object properties
			$object->id = $obj->rowid;
			$object->ref = $obj->ref;
			$object->ref_ext = $obj->ref_ext;
			$object->socid = $obj->fk_soc;
			$object->datec = $db->jdate($obj->datec);
			$object->date_creation = $db->jdate($obj->datec);
			$object->date_validation = $db->jdate($obj->date_valid);
			$object->date_livraison = $db->jdate($obj->date_livraison);
			$object->delivery_date = $db->jdate($obj->date_livraison);
			$object->total_ht = $obj->total_ht;
			$object->total_tva = $obj->total_tva;
			$object->total_ttc = $obj->total_ttc;
			$object->statut = $obj->fk_statut;
			$object->status = $obj->fk_statut;
			$object->note_private = $obj->note_private;
			$object->note_public = $obj->note_public;
			$object->entity = $obj->entity;
			$object->multicurrency_code = $obj->multicurrency_code;
			$object->multicurrency_tx = $obj->multicurrency_tx;
			$object->multicurrency_total_ht = $obj->multicurrency_total_ht;
			$object->multicurrency_total_tva = $obj->multicurrency_total_tva;
			$object->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

			// Fetch lines manually (fetch_lines() doesn't exist for SupplierProposal)
			require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

			$sql_lines = 'SELECT * FROM ' . $db->prefix() . 'supplier_proposaldet';
			$sql_lines .= ' WHERE fk_supplier_proposal = ' . intval($object->id);
			$sql_lines .= ' ORDER BY rang ASC';

			$resql_lines = $db->query($sql_lines);
			if ($resql_lines) {
				$object->lines = array();
				$num_lines = $db->num_rows($resql_lines);
				$i = 0;
				while ($i < $num_lines) {
					$obj_line = $db->fetch_object($resql_lines);

					$line = new SupplierProposalLine($db);
					$line->id = $obj_line->rowid;
					$line->rowid = $obj_line->rowid;
					$line->fk_supplier_proposal = $obj_line->fk_supplier_proposal;
					$line->fk_parent_line = $obj_line->fk_parent_line;
					$line->desc = $obj_line->description;
					$line->description = $obj_line->description;
					$line->qty = $obj_line->qty;
					$line->subprice = $obj_line->subprice;
					$line->tva_tx = $obj_line->tva_tx;
					$line->localtax1_tx = $obj_line->localtax1_tx;
					$line->localtax2_tx = $obj_line->localtax2_tx;
					$line->total_ht = $obj_line->total_ht;
					$line->total_tva = $obj_line->total_tva;
					$line->total_localtax1 = $obj_line->total_localtax1;
					$line->total_localtax2 = $obj_line->total_localtax2;
					$line->total_ttc = $obj_line->total_ttc;
					$line->fk_product = $obj_line->fk_product;
					$line->product_type = $obj_line->product_type;
					$line->ref = $obj_line->ref;
					$line->label = $obj_line->label;
					$line->fk_unit = $obj_line->fk_unit;
					$line->rang = $obj_line->rang;
					$line->special_code = $obj_line->special_code;
					$line->multicurrency_subprice = $obj_line->multicurrency_subprice;
					$line->multicurrency_total_ht = $obj_line->multicurrency_total_ht;
					$line->multicurrency_total_tva = $obj_line->multicurrency_total_tva;
					$line->multicurrency_total_ttc = $obj_line->multicurrency_total_ttc;

					$object->lines[] = $line;
					$i++;
				}
				$db->free($resql_lines);
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

	$langs->load('clichaumeil@clichaumeil');

	$context = Context::getInstance();


	// --- Standard Page Load & POST Actions ---
	// Use custom fetch to avoid getEntity() issue in multicompany with external access
	$object = fetchSupplierProposalForExternalAccess($supplierPropalId);
	if (!$object) {
		$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
		return;
	}

	// --- Handle POST Actions ---
	$postAction = GETPOST('action', 'alpha');
	$trackid = $object->id;

	if ($postAction == 'download-action-file') {
		// --- ACTION: Download file from action directory ---
		$actionid = GETPOST('actionid', 'int');
		$filename = GETPOST('filename', 'alpha');

		if ($actionid > 0 && !empty($filename)) {
			$filename = basename($filename); // Security: prevent path traversal
			$filepath = $conf->agenda->dir_output . '/' . $actionid . '/' . $filename;

			if (file_exists($filepath) && is_file($filepath)) {
				// Set headers for file download
				$mime = dol_mimetype($filepath);
				header('Content-Type: ' . $mime);
				header('Content-Disposition: inline; filename="' . $filename . '"');
				header('Content-Length: ' . filesize($filepath));
				readfile($filepath);
				exit;
			} else {
				http_response_code(404);
				echo 'File not found';
				exit;
			}
		}
	} elseif ($postAction == 'add-comment-file') {
		// --- ACTION: Upload file to session ---

		// Upload file into session
		if (!empty($_FILES['addedfile']['name'])) {
			$upload_dir = $conf->admin->dir_temp ? $conf->admin->dir_temp : DOL_DATA_ROOT . '/admin/temp';
			dol_syslog("Supplier Proposal: Uploading file to session with trackid=" . $trackid . ", upload_dir=" . $upload_dir, LOG_DEBUG);

			$result = dol_add_file_process(
				$upload_dir,                     // Temporary upload directory
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
				$context->setEventMessages($langs->trans('FileUploaded'), 'mesgs');
			} elseif ($result < 0) {
				$context->setEventMessages($langs->trans('ErrorFileUpload'), 'errors');
			}
		}

		// No redirect, stay on same page to continue adding files or write comment

	} elseif ($postAction == 'validate_proposal') {
		// 2. Validate the proposal
		$object->array_options["options_clichaumeil_supplierstatut"] = $langs->transnoentities('CLICHAUMEIL_FILE_RECEIVED');

		$res = $object->updateExtraField('clichaumeil_supplierstatut');

		if ($res >= 0) {
			$context->setEventMessages($langs->trans('SupplierProposalValidated'), 'mesgs');
		} else {
			$context->setEventMessages($object->error, 'errors');
		}

		// Redirect to avoid form re-submission
//		header('Location: ' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $supplierPropalId));
//		exit;

	} elseif ($postAction == 'new-comment') {
		// --- ACTION: Add new Comment ---

		$comment = GETPOST('propal-comment', 'alpha');
		$title = GETPOST('propal-title', 'alpha');

		if (!empty($comment)) {
			$actioncomm = new ActionComm($db);
			$actioncomm->datep = dol_now();
			$actioncomm->note_private = $comment; // Note: using note_private?
			$actioncomm->elementtype = 'supplier_proposal'; // Set element type
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
			$keytoavoidconflict = '-' . $object->id;
			$listofpaths = array();
			$listofnames = array();

			if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) {
				$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
			}
			if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
				$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
			}

			// Copy uploaded files to both supplier proposal and action directories
			if (!empty($listofpaths)) {
				// 1. Supplier Proposal directory
				$upload_dir_proposal = $conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
				dol_mkdir($upload_dir_proposal);

				// 2. Action/Agenda directory (for external access only, not indexed in ECM)
				$upload_dir_action = $conf->agenda->dir_output . '/' . $res;
				dol_mkdir($upload_dir_action);

				foreach ($listofpaths as $key => $val) {
					$src = $val;
					$filename = $listofnames[$key];

					// Copy to supplier proposal directory
					$dest_proposal = $upload_dir_proposal . '/' . $filename;
					if (dol_copy($src, $dest_proposal)) {
						// Index in ECM for proposal
						addFileIntoDatabaseIndex($upload_dir_proposal, $filename, '', 'uploaded', 0, $object);
					}

					// Move to action directory (NOT indexed in ECM to avoid showing in standard agenda)
					$dest_action = $upload_dir_action . '/' . $filename;
					dol_move($src, $dest_action);
					// Note: NOT calling addFileIntoDatabaseIndex for action, files won't show in standard agenda
				}

				// Clear session
				unset($_SESSION["listofpaths".$keytoavoidconflict]);
				unset($_SESSION["listofnames".$keytoavoidconflict]);
				unset($_SESSION["listofmimes".$keytoavoidconflict]);
			}

			$context->setEventMessages($langs->trans('CommentAdded'), 'mesgs');
		}

		// Redirect to the comment
		//    header('Location: ' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $supplierPropalId . '#lastcomment'));
		//    exit;
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
	return print_supplierPropalCard_view($supplierPropalId, $socId, $action);
}


/**
 * Display the supplier proposal (view mode)
 *
 * @param int $supplierPropalId
 * @param int $socId
 * @param string $action
 * @return string
 */
function print_supplierPropalCard_view($supplierPropalId = 0, $socId = 0, $action = '')
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

	/** @var SupplierProposal $object */
	if (!empty($context->fetchedSupplierPropal)) {
		$object = $context->fetchedSupplierPropal;
	} else {
		if (!empty($supplierPropalId)) {
			// Use custom fetch to avoid getEntity() issue in multicompany with external access
			$object = fetchSupplierProposalForExternalAccess($supplierPropalId);
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
          <h5>' . $langs->trans('SupplierProposal') . ' ' . $object->ref . '</h5>
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
                <div class="row clearfix form-group" id="status">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_STATUS') . '</div>
                   <div class="col-md-10">' . $object->array_options["options_clichaumeil_supplierstatut"] . '</div>
                </div>
                <div class="row clearfix form-group" id="DateCreation">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_DATECREATION') . '</div>
                   <div class="col-md-10">' . dol_print_date($object->datec, 'dayhour') . '</div>
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
                        <th style="width: 50%;">' . $langs->trans('Description') . '</th>
                        <th class="text-right" style="width: 15%;">' . $langs->trans('Qty') . '</th>
                        <th class="text-right" style="width: 15%;">' . $langs->trans('UnitPriceHT') . '</th>
                        <th class="text-right" style="width: 210%;">' . $langs->trans('TotalHT') . '</th>
                    </tr>
                </thead>
                <tbody>';

	if (!empty($object->lines)) {
		foreach ($object->lines as $key => $line) {
			if (!isModEnabled('subtotal') || !TSubtotal::isModSubtotalLine($line)) {
				$out .= '<tr>
                      <td>' . nl2br($line->ref) . '</td>
                      <td>' . $line->desc . '</td>
                      <td class="text-right">' . $line->qty . '</td>
                      <td class="text-right">';

				// If proposal is validated (statut 1), show an input box
				if ($object->status == SupplierProposal::STATUS_VALIDATED) {
					// Add class "line-price-input" and data-line-id for jQuery
					$out .= '<input type="text" class="form-control text-right line-price-input"
                            name="line_prices[' . $line->id . ']"
                            value="' . price($line->subprice, 0, $langs, 0, 2, -1, '', 1) . '"
                            data-line-id="' . $line->id . '" />';
				} else {
					$out .= price($line->subprice, 0, $langs, 1, 2, -1, $currency_code);
				}

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

	// Show Validate button if status is empty or pending file
	$supplier_status = !empty($object->array_options['options_clichaumeil_supplierstatut']) ? $object->array_options['options_clichaumeil_supplierstatut'] : '';
	if (empty($supplier_status) || $supplier_status == $langs->trans('CLICHAUMEIL_PENDING_FILE')) {
		$out .= '<div class="text-right" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success" name="action" value="validate_proposal">
                        <i class="fa fa-check"></i> ' . $langs->trans('CLIACHAUMEIL_SAVEANDVALIDATE') . '
                    </button>
                </div>';
	}

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

	$out .= '</div"><!-- end btn-group -->';
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

	$element_type = 'supplier_proposal'; // Use the correct element type

	$e = new ExtraFields($db);
	$e->fetch_name_optionals_label($element_type);

	// Check for a new global conf for supplier propal extrafields
	$TAddedField = getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL') ? explode(',', getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL')) : [];

	if (!empty($TAddedField)) {
		foreach ($TAddedField as $field_key) {
			$field_key = strtr($field_key, array('EXTRAFIELD_' => ''));

			if (empty($e->attributes[$element_type]['label'][$field_key])) continue;

			$label = $langs->transnoentities($e->attributes[$element_type]['label'][$field_key]);
			$type = $e->attributes[$element_type]['type'][$field_key];
			$value = $object->array_options['options_' . $field_key];

			$value_formatted = $e->showOutputField($field_key, $value, '', $element_type);

			if ($type == 'separate') {
				$out .= '<hr style="max-width : 100%;">';
			} else {
				$out .= '<div class="row clearfix form-group" id="extrafield-' . $field_key . '">';
				$out .= '<div class="col-md-2">' . $label . '</div>';
				$out .= '<div class="col-md-8"> ' . $value_formatted . '</div> ';
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

	$element_type = 'supplier_proposal';
	$ref_dir = dol_sanitizeFileName($object->ref);

	$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id, ecm.filepath, ecm.filename, ecm.share';
	$sql .= ' FROM ' . $db->prefix() . 'ecm_files ecm';
	$sql .= ' WHERE ((ecm.src_object_type = \'' . $db->escape($element_type) . '\' ';
	$sql .= ' AND  ecm.src_object_id = ' . intval($object->id) . ') ';
	$sql .= ' OR  ecm.filepath = \'' . $db->escape($element_type . '/' . $ref_dir) . '\' )';

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
