<?php
/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once __DIR__.'/../lib/clichaumeil.lib.php';
dol_include_once('/externalaccess/class/ExternalFormTicket.class.php');
dol_include_once('/subtotal/class/subtotal.class.php');
dol_include_once('/subtotal/class/actions_subtotal.class.php');
dol_include_once('/subtotal/lib/subtotal.lib.php');

/**
 * View class for Supplier Proposal rendering
 * Handles all HTML generation (following SRP)
 */
class SupplierProposalView
{
	// Product type for subtotal lines (from subtotal module)
	private const PRODUCT_TYPE_SUBTOTAL = 9;

	/** @var Translate */
	private $langs;

	/** @var Conf */
	private $conf;

	/** @var DoliDB */
	private $db;

	/** @var User */
	private $user;

	/** @var Context */
	private $context;

	/**
	 * Constructor
	 *
	 * @param Translate $langs Translation handler.
	 * @param Conf $conf Configuration object.
	 * @param DoliDB $db Database handler.
	 * @param User $user Current user.
	 * @param Context $context External access context.
	 */
	public function __construct(Translate $langs, Conf $conf, DoliDB $db, User $user, Context $context)
	{
		$this->langs = $langs;
		$this->conf = $conf;
		$this->db = $db;
		$this->user = $user;
		$this->context = $context;
	}

	/**
	 * Render proposal summary section
	 *
	 * @param SupplierProposal $object Supplier proposal object.
	 * @param Societe $thirdparty Supplier thirdparty.
	 * @param array $documents Documents linked to the proposal.
	 * @return string HTML
	 */
	public function renderProposalSummary(SupplierProposal $object, Societe $thirdparty, array $documents) : string
	{
		$currencyCode = !empty($object->multicurrency_code) ? $object->multicurrency_code : $this->conf->currency;

		$out = '<div class="container px-0">';
		$out .= $this->getEaNavbar();
		$out .= '<h5>' . $this->langs->trans('CLICHAUMEIL_SUPPLIERPROPOSAL', $object->ref) . '</h5>';
		$out .= '<div class="panel panel-default" id="propal-summary">';
		$out .= '<div class="panel-body">';

		// Main fields
		$out .= $this->renderField('CLICHAUMEIL_REFNAME', $thirdparty->name ?? '');
		$out .= $this->renderField('Label', $object->ref_supplier ?? '');
		$out .= $this->renderField('CLICHAUMEIL_PROJECT', $object->project_ref ?? '');
		$out .= $this->renderField('CLICHAUMEIL_STATUS', $this->getSupplierStatusLabel($object));
		$out .= $this->renderField('CLICHAUMEIL_DATEDELIVERYPLANNED', dol_print_date($object->delivery_date), '', ' :');
		$out .= $this->renderField('CLICHAUMEIL_TOTALHT', price($object->total_ht, 0, $this->langs, 1, 2, -1, $currencyCode), 'object-total-ht');
		$uploadedAttachments = $this->renderUploadedAttachments($documents, $object);
		if (!empty($uploadedAttachments)) {
			$out .= $this->renderField('CLICHAUMEIL_ATTACHED_FILES', $uploadedAttachments, 'object-attachments');
		}

		// Extrafields
		$out .= $this->renderExtrafields($object);

		$out .= '</div>'; // panel-body

		// Documents
		$out .= $this->renderDocumentsFooter($documents, $object);

		$out .= '</div>'; // panel
		$out .= $this->renderAttachmentReminder();
		$out .= '</div>'; // container


		return $out;
	}

	/**
	 * Get localized label for supplier status extrafield
	 *
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string
	 */
	private function getSupplierStatusLabel(SupplierProposal $object) : string
	{
		if (empty($object->array_options['options_clichaumeil_supplierstatut'])) {
			return '';
		}

		$value = $object->array_options['options_clichaumeil_supplierstatut'];

		// The value is stored as a translation key (e.g., 'CLICHAUMEIL_PENDING_FILE')
		// Translate it to get the localized label
		$translated = $this->langs->trans($value);

		// If translation exists, return it; otherwise return the raw value
		return ($translated !== $value) ? $translated : $value;
	}

	/**
	 * Render proposal lines table
	 *
	 * @param SupplierProposal $object Supplier proposal object.
	 * @param string $currencyCode Currency code used for price rendering.
	 * @return string HTML
	 */
	public function renderProposalLines(SupplierProposal $object, string $currencyCode) : string
	{
		$this->langs->loadLangs(array('products', 'stocks'));

		$out = '<div class="container px-0">';
		$out .= '<div class="table-responsive">';
		$out .= '<table class="table table-striped" id="supplier-propal-lines">';
		$out .= '<thead>';
		$out .= '<tr>';
		$out .= '<th style="width: 10%;">' . $this->langs->trans('Ref') . '</th>';
		$out .= '<th style="width: 14%;">' . $this->langs->trans('CLICHAUMEIL_REFSUPPLLIER') . '</th>';
		$out .= '<th style="width: 34%;">' . $this->langs->trans('Description') . '</th>';
		$out .= '<th class="text-right" style="width: 10%;">' . $this->langs->trans('Qty') . '</th>';
		$out .= '<th style="width: 8%;">' . $this->langs->trans('Unit') . '</th>';
		$out .= '<th class="text-right" style="width: 12%;">' . $this->langs->trans('UnitPriceHT') . '</th>';
		$out .= '<th class="text-right" style="width: 12%;">' . $this->langs->trans('TotalHT') . '</th>';
		$out .= '</tr>';
		$out .= '</thead>';
		$out .= '<tbody>';

		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				$out .= $this->renderLine($line, $currencyCode, $object);
			}
		}

		$out .= '</tbody>';
		$out .= '</table>';
		$out .= '</div>';

		// Validation button
		$out .= '<div class="text-right" style="margin-top: 20px;">';
		$out .= '<button type="submit" class="btn btn-success" id="btn-validate-proposal" name="action" value="validate_proposal">';
		$out .= '<i class="fa fa-check"></i> ' . $this->langs->trans('CLICHAUMEIL_SAVEANDVALIDATE');
		$out .= '</button>';
		$out .= '</div>';

		$out .= '</div>'; // container

		return $out;
	}

	/**
	 * Render single line
	 *
	 * @param SupplierProposalLine $line Proposal line to render.
	 * @param string $currencyCode Currency code used for totals.
	 * @param SupplierProposal $object Parent supplier proposal.
	 * @return string HTML
	 */
	private function renderLine(SupplierProposalLine $line, string $currencyCode, SupplierProposal $object) : string
	{
		// Always detect subtotal lines, even if module flag isn't exposed to external access
		// Subtotal lines are hidden in this view (see renderProposalLines)
		if (TSubtotal::isSubtotal($line)) {
			return '';
		}

		$out = '<tr>';
		$out .= '<td>' . nl2br($line->product_ref) . '</td>';
		$out .= '<td>' . nl2br($line->ref_supplier) . '</td>';
		$out .= '<td>';
		if (!empty($line->label)) {
			$out .= '<strong>' . nl2br($line->label) . '</strong>';
		}
		if (!empty($line->desc)) {
			if (!empty($line->label)) {
				$out .= '<br>';
			}
			$out .= nl2br($line->desc);
		}
		$out .= '</td>';
		if (TSubtotal::isModSubtotalLine($line)) {
			$out .= '<td></td>';
			$out .= '<td></td>';
			$out .= '<td></td>';
			$out .= '<td></td>';
		} else {
			$out .= '<td class="text-right">' . $line->qty . '</td>';
			$out .= '<td>' . dol_escape_htmltag((string) ($line->unit_short_label ?? '')) . '</td>';
			$out .= '<td class="text-right">';
			$out .= '<input type="text" class="form-control text-right line-price-input"';
			$out .= ' name="line_prices[' . $line->id . ']"';
			$out .= ' value="' . price($line->subprice, 0, $this->langs, 0, 2, -1, '', 1) . '"';
			$out .= ' data-line-id="' . $line->id . '" />';
			$out .= '</td>';
			$out .= '<td class="text-right" id="line-total-' . $line->id . '">' . price($line->total_ht, 0, $this->langs, 1, 2, -1, $currencyCode) . '</td>';
		}
		$out .= '</tr>';

		return $out;
	}

	/**
	 * Render subtotal line
	 *
	 * @param SupplierProposalLine $line Subtotal line to render.
	 * @param string $currencyCode Currency code used for totals.
	 * @param SupplierProposal $object Parent supplier proposal.
	 * @return string HTML
	 */
	private function renderSubtotalLine(SupplierProposalLine $line, string $currencyCode, SupplierProposal $object) : string
	{
		$out = '<tr>';

		if (TSubtotal::isSubtotal($line)) {
			$subtotalAmount = getTotalLineFromObject($object, $line, getDolGlobalString('SUBTOTAL_USE_NEW_FORMAT'), false);
			$out .= '<td colspan="2">' . nl2br($line->label) . '</td>';
			$out .= '<td>' . $line->desc . '</td>';
			$out .= '<td class="text-right"></td>';
			$out .= '<td class="text-right"></td>';
			$out .= '<td class="text-right"></td>';
			$out .= '<td class="text-right">' . price($subtotalAmount, 0, $this->langs, 1, 2, -1, $currencyCode) . '</td>';
		} elseif (TSubtotal::isFreeText($line)) {
			$out .= '<td colspan="6">' . nl2br($line->desc) . '</td>';
			$out .= '<td></td>';
		} else {
			$out .= '<td colspan="6">';
			if (!empty($line->label)) {
				$out .= '<strong>' . nl2br($line->label) . '</strong>';
			}
			if (!empty($line->desc)) {
				if (!empty($line->label)) {
					$out .= '<br>';
				}
				$out .= nl2br($line->desc);
			}
			$out .= '</td>';
			$out .= '<td></td>';
		}

		$out .= '</tr>';

		return $out;
	}

	/**
	 * Render timeline/discussion section
	 *
	 * @param array $TMessage Array of ActionComm objects.
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	public function renderTimeline(array $TMessage, SupplierProposal $object) : string
	{
		if (empty($TMessage)) {
			return '';
		}

		$sortMsg = !empty($this->user->conf->EA_SPROPAL_MSG_SORT_ORDER) ? $this->user->conf->EA_SPROPAL_MSG_SORT_ORDER : 'asc';

		$out = '<div class="container px-0" style="margin-top: 20px;">';
		$out .= '<h5>' . $this->langs->trans('Discussion') . '</h5>';
		$out .= '<ul class="timeline">';

		if ($sortMsg == 'desc') {
			$TMessage = array_reverse($TMessage, true);
		}

		$datelabel = "";
		$iComment = 0;
		$numComments = count($TMessage);
		$userGetNomUrlCache = array();

		foreach ($TMessage as $actionstatic) {
			if (empty($actionstatic->private)) {
				if ($datelabel != dol_print_date($actionstatic->datep)) {
					$datelabel = dol_print_date($actionstatic->datep);
					$out .= '<li class="time-label"><span class="timeline-badge-date">' . $datelabel . '</span></li>';
				}
				$out .= $this->renderTimelineItem($actionstatic, ++$iComment, $numComments, $userGetNomUrlCache);
			}
		}

		$out .= '</ul>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Render single timeline item
	 *
	 * @param ActionComm $action Timeline action to render.
	 * @param int $iComment Current comment index.
	 * @param int $numComments Total number of comments.
	 * @param array $userGetNomUrlCache Cache of rendered user labels.
	 * @return string HTML
	 */
	private function renderTimelineItem(ActionComm $action, int $iComment, int $numComments, array &$userGetNomUrlCache) : string
	{
		$out = '<li id="comment-message-' . $action->id . '" class="timeline-code-' . strtolower($action->code) . '">';
		$out .= '<i class="fa fa-comments"></i>';

		if ($iComment === $numComments) {
			$out .= '<div id="lastcomment"></div>';
		}

		$out .= '<div class="timeline-item">';
		$out .= '<span class="time"><i class="fa fa-clock-o"></i> ' . dol_print_date($action->datep, 'dayhour') . '</span>';

		// Header
		$out .= '<h3 class="timeline-header">';
		$out .= '<span class="messaging-author">';
		if ($action->userownerid > 0) {
			if (!isset($userGetNomUrlCache[$action->userownerid])) {
				$fuser = new User($this->db);
				$fuser->fetch($action->userownerid);
				$userGetNomUrlCache[$action->userownerid] = $fuser->getFullName($this->langs);
			}
			$out .= $userGetNomUrlCache[$action->userownerid];
		}
		$out .= '</span>';
		$out .= '</h3>';

		// Body
		$out .= '<div class="timeline-body">';
		if ($action->code === 'AC_PROPOSAL_SUPPLIER_SENTBYMAIL' && !empty($action->email_subject)) {
			$out .= '<div class="timeline-mail-header">';
			$out .= '<div><strong>' . $this->langs->trans('MailTopic') . '</strong> ' . dol_escape_htmltag($action->email_subject) . '</div>';
			$out .= '<div><strong>' . $this->langs->trans('MailFrom') . '</strong> ' . dol_escape_htmltag($action->email_from) . '</div>';
			$out .= '<div><strong>' . $this->langs->trans('MailTo') . '</strong> ' . dol_escape_htmltag($action->email_to) . '</div>';
			if (!empty($action->email_tocc)) {
				$out .= '<div><strong>' . $this->langs->trans('MailCC') . '</strong> ' . dol_escape_htmltag($action->email_tocc) . '</div>';
			}
			$out .= '</div>';
		}
		if ($action->code === 'AC_PROPOSAL_SUPPLIER_SENTBYMAIL') {
			$out .= dol_string_onlythesehtmltags($action->note_private);
		} else {
			$out .= dol_string_onlythesehtmltags(dol_htmlentitiesbr($action->note_private));
		}
		$out .= '</div>';

		// Files (agenda attachments) or fallback to proposal documents for sent emails
		$timelineFilesHtml = '';
		if (in_array($action->code, array('AC_OTH', 'AC_PROPOSAL_SUPPLIER_SENTBYMAIL'), true)) {
			$timelineFilesHtml = $this->renderTimelineFiles($action);
		}
		if (!empty($timelineFilesHtml)) {
			$out .= $timelineFilesHtml;
		}

		$out .= '</div>'; // timeline-item
		$out .= '</li>';

		return $out;
	}

	/**
	 * Render timeline files
	 *
	 * @param ActionComm $action Timeline action carrying attachments.
	 * @return string HTML
	 */
	private function renderTimelineFiles(ActionComm $action) : string
	{
		$actionEntity = !empty($action->entity) ? $action->entity : $this->conf->entity;
		$agendaRoot = $this->conf->agenda->multidir_output[$actionEntity] ?? '';
		if (empty($agendaRoot)) {
			return '';
		}

		// Use multidir_output for agenda to support multi-entity
		$actionDir = $agendaRoot . '/' . $action->id;
		$files = array();

		if (is_dir($actionDir)) {
			$files = dol_dir_list($actionDir, 'files');
		}

		if (empty($files)) {
			return '';
		}

		$out = '<div class="timeline-footer"><div class="timeline-documents-container">';

		foreach ($files as $file) {
			$filename = $file['name'];
			$filePath = $file['fullname'];
			$mime = dol_mimetype($filePath);
			$class = in_array($mime, array('image/png', 'image/jpeg', 'application/pdf')) ? 'documentpreview' : '';

			$doclink = $this->context->getControllerUrl('supplier_proposal_card', array(
				'id' => $action->elementid,
				'action' => 'download-action-file',
				'actionid' => $action->id,
				'filename' => urlencode($filename)
			));

			$out .= '<span class="timeline-documents" mime="' . $mime . '">';
			$out .= '<a href="' . $doclink . '" class="btn-link ' . $class . '">';
			$out .= img_mime($filePath) . ' ' . $filename;
			$out .= '</a>';
			$out .= '</span>';
		}

		$out .= '</div></div>';

		return $out;
	}


	/**
	 * Render comment form
	 *
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	public function renderCommentForm(SupplierProposal $object) : string
	{
		$out = '<div class="container px-0">';
		// Anchor placed just before the form to align scroll above the title
		$out .= '<div id="form-propal-message-container"></div>';
		$out .= '<ul class="timeline">';
		$out .= '<li class="time-label"><span class="timeline-badge-date"><i class="fa fa-comments"></i> ' . $this->langs->transnoentities('CLICHAUMEIL_ADDMESSAGE') . '</span></li>';
		$out .= '<li class="timeline-code-ticket_msg">';
		$out .= '<div class="timeline-item">';
		$out .= '<div class="timeline-body form-ticket-message-container">';

		// Textarea
		$out .= '<div class="form-group">';
		$out .= '<textarea name="propal-comment" class="form-control" id="propal-comment" placeholder="' . $this->langs->transnoentities('CLICHAUMEIL_YOURCOMMENTHERE') . '" rows="10">' . dol_htmlentities(GETPOST('propal-comment', 'none')) . '</textarea>';
		$out .= '</div>';

		if (getDolGlobalString('FCKEDITOR_ENABLE_TICKET')) {
			$out .= '<script>CKEDITOR.replace("propal-comment", { enterMode: CKEDITOR.ENTER_BR });</script>';
		}

		// File upload
		$formExternal = new ExternalFormTicket($this->db);
		$formExternal->ref = $object->ref;
		$formExternal->id = $object->id;
		$formExternal->trackid = $object->id;
		$formExternal->withfile = 2;
		$formExternal->withcancel = 1;
		$formExternal->param = array('fk_user_create' => $this->user->id);
		$out .= '<div class="form-group">';
		$out .= $formExternal->showFilesForm();
		$out .= '</div>';

		$out .= '</div>'; // timeline-body
		$out .= '<div class="timeline-footer text-right">';
		$out .= '<div class="btn-group">';
		$out .= '<button type="submit" class="btn btn-success" id="btn-send-comment" name="action" value="new-comment" data-toggle="tooltip" title="' . dol_htmlentities($this->langs->transnoentities('CLICHAUMEIL_SENDMESSAGEHELP'), ENT_QUOTES) . '">' . $this->langs->transnoentities('CLICHAUMEIL_ADDMESSAGE') . '</button>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '</div>'; // timeline-item
		$out .= '</li>';
		$out .= '</ul>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Render a simple field row
	 *
	 * @param string $label Translation key
	 * @param string $value Value to display
	 * @param string $id Optional ID for the value div
	 * @param string $param Optional ID for the value div
	 * @return string HTML
	 */
	private function renderField(string $label = '', string $value = '', string $id = '', string $param = "") : string
	{
		$idAttr = $id ? ' id="' . $id . '"' : '';
		$out = '<div class="row clearfix form-group">';
		$out .= '<div class="col-md-3">' . $this->langs->transnoentities($label) . $param . '</div>';
		$out .= '<div class="col-md-9"' . $idAttr . '>' . $value . '</div>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * Render extrafields
	 *
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	private function renderExtrafields(SupplierProposal $object) : string
	{
		$out = '';
		$elementType = 'supplier_proposal';
		$e = new ExtraFields($this->db);
		$e->fetch_name_optionals_label($elementType);

		$TAddedField = getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL')
			? explode(',', getDolGlobalString('EACCESS_CARD_ADDED_FIELD_SUPPLIER_PROPAL'))
			: array();

		if (!empty($TAddedField)) {
			foreach ($TAddedField as $fieldKey) {
				$fieldKey = strtr($fieldKey, array('EXTRAFIELD_' => ''));

				if (empty($e->attributes[$elementType]['label'][$fieldKey])) {
					continue;
				}

				$label = $this->langs->transnoentities($e->attributes[$elementType]['label'][$fieldKey]);
				$type = $e->attributes[$elementType]['type'][$fieldKey];
				$value = $object->array_options['options_' . $fieldKey];
				$valueFormatted = $e->showOutputField($fieldKey, $value, '', $elementType);

				if ($type == 'separate') {
					$out .= '<hr style="max-width: 100%;">';
				} else {
					$out .= $this->renderField($label, $valueFormatted, 'extrafield-' . $fieldKey);
				}
			}
		}

		return $out;
	}

	/**
	 * Render documents footer
	 *
	 * @param array $documents Documents linked to the proposal.
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	private function renderDocumentsFooter(array $documents, SupplierProposal $object) : string
	{
		if (empty($documents)) {
			return '';
		}

		$visibleDocuments = array();
		foreach ($documents as $doc) {
			if (empty($doc->gen_or_uploaded) || $doc->gen_or_uploaded !== 'uploaded') {
				$visibleDocuments[] = $doc;
			}
		}

		if (empty($visibleDocuments)) {
			return '';
		}

		$out = '<div class="panel-footer">';

		foreach ($visibleDocuments as $doc) {
			$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
			$mime = dol_mimetype($filePath);
			$class = in_array($mime, array('image/png', 'image/jpeg', 'application/pdf')) ? 'documentpreview' : '';
			$downloadUrl = $this->context->getControllerUrl('supplier_proposal_card', array(
				'id' => $object->id,
				'action' => 'download-proposal-file',
				'fileid' => $doc->id
			));

			$out .= '<span id="document_' . $doc->id . '" class="timeline-documents" data-id="' . $doc->id . '" data-path="' . $doc->filepath . '" data-filename="' . dol_escape_htmltag($doc->filename) . '" mime="' . $mime . '">';

			$out .= '<a href="' . $downloadUrl . '" class="btn-link ' . $class . '" target="_blank">';
			$out .= img_mime($filePath) . ' ' . dol_escape_htmltag($doc->filename);
			$out .= '</a>';

			$out .= '</span>';
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * Render uploaded attachments list inside summary
	 *
	 * @param array $documents Documents linked to the proposal.
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	private function renderUploadedAttachments(array $documents, SupplierProposal $object) : string
	{
		if (empty($documents)) {
			return '';
		}

		$uploaded = array();
		foreach ($documents as $doc) {
			if (!empty($doc->gen_or_uploaded) && $doc->gen_or_uploaded === 'uploaded') {
				$uploaded[] = $doc;
			}
		}

		if (empty($uploaded)) {
			return '';
		}

		$out = '<div class="clichaumeil-attachments">';
		foreach ($uploaded as $doc) {
			$out .= $this->renderDocumentLink($doc, $object);
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * Render a single document link
	 *
	 * @param object $doc ECM document row.
	 * @param SupplierProposal $object Supplier proposal object.
	 * @return string HTML
	 */
	private function renderDocumentLink(object $doc, SupplierProposal $object) : string
	{
		$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
		$mime = dol_mimetype($filePath);
		$class = in_array($mime, array('image/png', 'image/jpeg', 'application/pdf')) ? 'documentpreview' : '';
		$downloadUrl = $this->context->getControllerUrl('supplier_proposal_card', array(
			'id' => $object->id,
			'action' => 'download-proposal-file',
			'fileid' => $doc->id
		));

		$out = '<div class="attachment-item">';
		$out .= '<a href="' . $downloadUrl . '" class="btn-link ' . $class . '" target="_blank">';
		$out .= img_mime($filePath) . ' ' . dol_escape_htmltag($doc->filename);
		$out .= '</a>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Get navigation bar
	 *
	 * @return string HTML
	 */
	private function getEaNavbar() : string
	{
		return getEaNavbar($this->context->getControllerUrl('supplier_proposal'));
	}

	/**
	 * Render the mandatory attachment reminder
	 *
	 * @return string HTML
	 */
	private function renderAttachmentReminder() : string
	{
		$out = '<div class="alert alert-danger" role="alert" style="margin-bottom: 15px;">';
		$out .= $this->langs->transnoentities('CLICHAUMEIL_SUPPLIERPROPOSAL_ATTACHMENT_REMINDER');
		$out .= '</div>';

		return $out;
	}

	/**
	 * Include JavaScript file reference
	 *
	 * @param string $jsFile JavaScript filename
	 * @return string HTML
	 */
	public function includeJavaScript($jsFile) : string
	{
		return '<script type="text/javascript" src="' . dol_buildpath('/clichaumeil/js/' . $jsFile, 1) . '"></script>';
	}
}
