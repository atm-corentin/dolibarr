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
	 * @param Translate $langs
	 * @param Conf $conf
	 * @param DoliDB $db
	 * @param User $user
	 * @param Context $context
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
	 * @param SupplierProposal $object
	 * @param Societe $thirdparty
	 * @param array $documents
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
		$out .= $this->renderField('CLICHAUMEIL_PROJECT', $object->project_ref ?? '' );
		$out .= $this->renderField('CLICHAUMEIL_STATUS', $this->getSupplierStatusLabel($object));
		$out .= $this->renderField('CLICHAUMEIL_DATEDELIVERYPLANNED', dol_print_date($object->delivery_date), '' ,' :');
		$out .= $this->renderField('CLICHAUMEIL_TOTALHT', price($object->total_ht, 0, $this->langs, 1, 2, -1, $currencyCode), 'object-total-ht');

		// Extrafields
		$out .= $this->renderExtrafields($object);

		$out .= '</div>'; // panel-body

		// Documents
		$out .= $this->renderDocumentsFooter($documents);

		$out .= '</div>'; // panel
		$out .= '</div>'; // container

		return $out;
	}

	/**
	 * Get localized label for supplier status extrafield
	 *
	 * @param SupplierProposal $object
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
	 * @param SupplierProposal $object
	 * @param string $currencyCode
	 * @return string HTML
	 */
	public function renderProposalLines(SupplierProposal $object, string $currencyCode) : string
	{
		$out = '<div class="container px-0">';
		$out .= '<div class="table-responsive">';
		$out .= '<table class="table table-striped" id="supplier-propal-lines">';
		$out .= '<thead>';
		$out .= '<tr>';
		$out .= '<th style="width: 10%;">' . $this->langs->trans('Ref') . '</th>';
		$out .= '<th style="width: 15%;">' . $this->langs->trans('CLICHAUMEIL_REFSUPPLLIER') . '</th>';
		$out .= '<th style="width: 40%;">' . $this->langs->trans('Description') . '</th>';
		$out .= '<th class="text-right" style="width: 10%;">' . $this->langs->trans('Qty') . '</th>';
		$out .= '<th class="text-right" style="width: 15%;">' . $this->langs->trans('UnitPriceHT') . '</th>';
		$out .= '<th class="text-right" style="width: 15%;">' . $this->langs->trans('TotalHT') . '</th>';
		$out .= '</tr>';
		$out .= '</thead>';
		$out .= '<tbody>';

		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				// Hide subtotal lines (product_type = 9) on external access view
				if ((int) $line->product_type === 9) {
					continue;
				}
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
	 * @param SupplierProposalLine $line
	 * @param string $currencyCode
	 * @param SupplierProposal $object
	 * @return string HTML
	 */
	private function renderLine(SupplierProposalLine $line, string $currencyCode, SupplierProposal $object) : string
	{
		// Always detect subtotal lines, even if module flag isn't exposed to external access
		// Subtotal lines are hidden in this view (see renderProposalLines)
		if (TSubtotal::isModSubtotalLine($line)) {
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
		$out .= '<td class="text-right">' . $line->qty . '</td>';
		$out .= '<td class="text-right">';
		$out .= '<input type="text" class="form-control text-right line-price-input"';
		$out .= ' name="line_prices[' . $line->id . ']"';
		$out .= ' value="' . price($line->subprice, 0, $this->langs, 0, 2, -1, '', 1) . '"';
		$out .= ' data-line-id="' . $line->id . '" />';
		$out .= '</td>';
		$out .= '<td class="text-right" id="line-total-' . $line->id . '">' . price($line->total_ht, 0, $this->langs, 1, 2, -1, $currencyCode) . '</td>';
		$out .= '</tr>';

		return $out;
	}

	/**
	 * Render subtotal line
	 *
	 * @param SupplierProposalLine $line
	 * @param string $currencyCode
	 * @param SupplierProposal $object
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
			$out .= '<td class="text-right">' . price($subtotalAmount, 0, $this->langs, 1, 2, -1, $currencyCode) . '</td>';
		} elseif (TSubtotal::isFreeText($line)) {
			$out .= '<td colspan="5">' . nl2br($line->desc) . '</td>';
			$out .= '<td></td>';
		} else {
			$out .= '<td colspan="5">';
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
	 * @param array $TMessage Array of ActionComm objects
	 * @param SupplierProposal $object
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
	 * @param ActionComm $action
	 * @param int $iComment
	 * @param int $numComments
	 * @param array $userGetNomUrlCache
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
		$out .= '<div class="timeline-body">' . nl2br($action->note_private) . '</div>';

		// Files
		$out .= $this->renderTimelineFiles($action);

		$out .= '</div>'; // timeline-item
		$out .= '</li>';

		return $out;
	}

	/**
	 * Render timeline files
	 *
	 * @param ActionComm $action
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
	 * @param SupplierProposal $object
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
	private function renderField(string $label = '', string $value = '', string $id = '' , string $param = "") : string
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
	 * @param SupplierProposal $object
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
	 * @param array $documents
	 * @return string HTML
	 */
	private function renderDocumentsFooter(array $documents) : string
	{
		if (empty($documents)) {
			return '';
		}

		$out = '<div class="panel-footer">';

		foreach ($documents as $doc) {
			$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
			$mime = dol_mimetype($filePath);
			$class = in_array($mime, array('image/png', 'image/jpeg', 'application/pdf')) ? 'documentpreview' : '';

			$out .= '<span id="document_' . $doc->id . '" class="timeline-documents" data-id="' . $doc->id . '" data-path="' . $doc->filepath . '" data-filename="' . dol_escape_htmltag($doc->filename) . '" mime="' . $mime . '">';

			if (!empty($doc->share)) {
				$doclink = $this->context->getControllerUrl(false, array('action' => 'get-file', 'share' => $doc->share)) . 'script/interface.php?action=get-file&amp;share=' . $doc->share;
				$out .= '<a href="' . $doclink . '" class="btn-link ' . $class . '" target="_blank">';
				$out .= img_mime($filePath) . ' ' . $doc->filename;
				$out .= '</a>';
			} else {
				$out .= img_mime($filePath) . ' ' . $doc->filename;
			}

			$out .= '</span>';
		}

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
