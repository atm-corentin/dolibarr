<?php
/*
 * Library file for supplier proposal card view and interactions
 * Adapted from ticket.lib.php
 */

// Include the correct class from the external module
include_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';


dol_include_once('user/class/user.class.php');
dol_include_once('core/lib/functions2.lib.php');
dol_include_once('core/class/extrafields.class.php');
dol_include_once('externalaccess/class/html.formexternal.class.php');
dol_include_once('comm/action/class/actioncomm.class.php'); // Needed for timeline
dol_include_once('externalaccess/class/ExternalFormTicket.class.php');


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
	$object = new SupplierProposal($db); // Use the correct class
	if (empty($supplierPropalId) || $object->fetch($supplierPropalId) <= 0) {
		$context->setEventMessages($langs->trans('SupplierProposalNotFound'), 'errors');
		return;
	}

	// --- Handle POST Actions ---
	$postAction = GETPOST('action', 'alpha');

	if ($postAction == 'validate_proposal' && checkUserSupplierPropalRight($user, $object, 'validate')) {

		$line_prices = GETPOST('line_prices', 'array');

		foreach ($object->lines as $line) {
			if (isset($line_prices[$line->id])) {
				$new_pu_ht = price2num($line_prices[$line->id]);

				if (method_exists($object, 'updateline')) {
					$object->updateline(
						$line->id, // Use 'id'
						$new_pu_ht,
						$line->qty,
						$line->remise_percent,
						$line->tva_tx,
						$line->desc,
						0, // info_bits
						0, // pu_ht_devise
						$line->fk_unit
					);
				}
			}
		}

		// 2. Validate the proposal
		$res = $object->valid($user);
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

			// Handle file attachments (using FormExternal logic)
			$formExternal = new ExternalFormTicket($db); // Use ExternalFormTicket
			$formExternal->ref = $object->ref;
			$formExternal->id = $object->id;
			$formExternal->withfile = 2;// Match the form setting

			// Define the upload dir based on the module's logic
			$upload_dir = $conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);

			//    $TUploadedFiles = $formExternal->uploadFiles($upload_dir);
			//    if (is_array($TUploadedFiles) && count($TUploadedFiles) > 0) {
			//       // Link files to the action
			//       foreach ($TUploadedFiles as $filename) {
			//          $actioncomm->add_file($filename, $formExternal->rel_dir);
			//       }
			//    }

			$context->setEventMessages($langs->trans('CommentAdded'), 'mesgs');
		}

		// Optional: Re-open logic
//		if (GETPOST('action_sub', 'alpha') == 'new-comment-reopen' && $object->statut > 1) {
//			if (method_exists($object, 'set_status')) {
//				$object->set_status(1); // 1 = STATUS_VALIDATED
//			}
//		}

		// Redirect to the comment
		//    header('Location: ' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $supplierPropalId . '#lastcomment'));
		//    exit;
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
			$object = new SupplierProposal($db); // Use correct class
			$object->fetch($supplierPropalId);
			$context->fetchedSupplierPropal = $object;
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

	// Navigation bar
	$outEaNavbar = getEaNavbar($context->getControllerUrl('supplier_proposals', '&save_last_search_values=1'));

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
		// --- Main Proposal Summary ---
		$out .= $outEaNavbar;
		$out .= '
       <div class="container px-0">
          <h5>' . $langs->trans('SupplierProposal') . ' ' . $object->ref . '</h5>
          <div class="panel panel-default" id="propal-summary">
             <div class="panel-body">
                ' . $panelBodyTop . '
                <div class="row clearfix form-group" id="ref_supplier">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_REFSUPPLIER') . '</div>
                   <div class="col-md-10">' . $object->ref_supplier . '</div>
                </div>
                <div class="row clearfix form-group" id="status">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_STATUS') . '</div>
                   <div class="col-md-10">' . $object->getLibStatut(0) . '</div>
                </div>
                <div class="row clearfix form-group" id="DateCreation">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_DATECREATION') . '</div>
                   <div class="col-md-10">' . dol_print_date($object->datec, 'dayhour') . '</div>
                </div>
                <div class="row clearfix form-group" id="TotalHT">
                   <div class="col-md-2">' . $langs->transnoentities('CLICHAUMEIL_TOTALHT') . '</div>
                   <!-- Add ID for jQuery update -->
                   <div class="col-md-10" id="object-total-ht">' . price($object->total_ht) . '</div>
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
                        <th>' . $langs->trans('Ref') . '</th>
                        <th>' . $langs->trans('Description') . '</th>
                        <th class="text-right">' . $langs->trans('Qty') . '</th>
                        <th class="text-right" style="min-width: 120px;">' . $langs->trans('UnitPriceHT') . ' (' . $langs->trans($conf->currency) . ')</th>
                        <th class="text-right">' . $langs->trans('TotalHT') . '</th>
                    </tr>
                </thead>
                <tbody>';

	if (!empty($object->lines)) {
		foreach ($object->lines as $line) {
			if ($line->product_type != 9) {
				$out .= '<tr>
                      <td>' . nl2br($line->ref) . '</td>
                      <td>' . $line->desc . '</td>
                      <td class="text-right">' . $line->qty . '</td>
                      <td class="text-right">';

				// If proposal is a draft (statut 0), show an input box
				if ($object->status == supplierProposal::STATUS_DRAFT) {
					// Add class "line-price-input" and data-line-id for jQuery
					$out .= '<input type="text" class="form-control text-right line-price-input"
                            name="line_prices[' . $line->id . ']"
                            value="' . price($line->subprice, 0, $langs, 0, 0, -1, '', 1) . '"
                            data-line-id="' . $line->id . '" />';
				} else {
					$out .= price($line->subprice);
				}

				$out .= '   </td>
                      <!-- Add ID for jQuery update -->
                      <td class="text-right" id="line-total-' . $line->id . '">' . price($line->total_ht) . '</td>
                   </tr>';
			}
		}
	}

	$out .= '       </tbody>
            </table>
        </div>';

	// Show Validate button only if in Draft status and user has rights
	if ($object->status == supplierProposal::STATUS_DRAFT ) {
		$out .= '<div classs="text-right" style="margin-top: 20px; text-align: right;"> <!-- Fixed typo classs -> class -->
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

				// Files
				$documents = externalAccessGetActionCommEcmList($actionstatic, true);
				if (!empty($documents)) {
					$footer = '<div class="timeline-documents-container">';
					foreach ($documents as $doc) {
						$footer .= '<span id="document_' . $doc->id . '" class="timeline-documents" ';
						// ...
						$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
						$mime = dol_mimetype($filePath);
						$mimeAttr = ' mime="' . $mime . '" ';
						$class = '';
						if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
							$class .= ' documentpreview';
						}

						if (!empty($doc->share)) {
							$doclink = $context->getControllerUrl(false, array('action' => 'get-file', 'share' => $doc->share));
							$footer .= '<a href="' . $doclink . '" class="btn-link ' . $class . '" target="_blank"  ' . $mimeAttr . ' >';
							$footer .= img_mime($filePath) . ' ' . $doc->filename;
							$footer .= '</a>';
						} else {
							$footer .= img_mime($filePath) . ' ' . $doc->filename;
						}
						// ...
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
	$formExternal->withfile = 2;
	$formExternal->withcancel = 1;
	$formExternal->param = array('fk_user_create' => $user->id);
	$out .= '<div class="form-group">';
	$out .= $formExternal->showFilesForm(); // This helper generates the file input
	$out .= '</div>';

	$out .= '</div><!-- end timeline-body -->';
	$out .= '<div class="timeline-footer text-right">';
	$out .= '<div class="btn-group">';

	$url = dol_buildpath('/clichaumeil/script/interface.php', 1);

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
                console.log("AJAX done callback - Response:", response);
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
                    console.error("Error updating price:", response ? response.message : "No response");
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
 * Check user rights for supplier proposals
 *
 * @param User $user
 * @param SupplierProposal $object
 * @param string $rightToTest
 * @return bool
 */
function checkUserSupplierPropalRight($user, $object, $rightToTest = '')
{
	global $hookmanager, $db, $conf; // Added $db and $conf

	// Add fields from hooks
	$parameters = array('user' => $user, 'object' => $object, 'rightToTest' => $rightToTest);
	$reshook = $hookmanager->executeHooks('checkUserSupplierPropalRight', $parameters);

	if($reshook == 1) return true;
	if($reshook == -1) return false;

	// Check if user is linked to the thirdparty of the object
	if($user->socid > 0 && intval($object->socid) === intval($user->socid)){ // Use socid from your code

		// General view right
		if($rightToTest == 'view' && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')){ // Use your conf
			return true;
		}
		// Right to comment (if they can view)
		if($rightToTest == 'comment' && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')){
			return true;
		}

		// Right to validate (if they can view AND propal is draft)
		if($rightToTest == 'validate' && $object->status == supplierProposal::STATUS_DRAFT && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')){
			return true;
		}

		// Right to re-open (if they can view AND propal is closed/refused/canceled)
		if($rightToTest == 'reopen' && in_array((int)$object->status, array(2, 3, 4)) && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')){
			return true;
		}
	}

	return false;
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
	$sql .= ' WHERE fk_element = ' . $object->id;
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
	$sql .= ' WHERE ecm.filepath = \'agenda/' . $object->id . '\''; // Actions files are in 'agenda'
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
