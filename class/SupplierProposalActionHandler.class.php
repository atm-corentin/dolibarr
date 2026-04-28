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

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once __DIR__ . '/SupplierProposalService.class.php';
require_once __DIR__ . '/../lib/clichaumeil.lib.php';

/**
 * Action Handler class for Supplier Proposal
 * Handles business logic for actions (validate, comment, etc.)
 */
class SupplierProposalActionHandler
{
	/** @var SupplierProposalFileManager */
	private $fileManager;

	/** @var Translate */
	private $langs;

	/** @var User */
	private $user;

	/** @var Conf */
	private $conf;

	/** @var DoliDB */
	private $db;

	/**
	 * Constructor
	 *
	 * @param SupplierProposalFileManager $fileManager File manager service.
	 * @param Translate                   $langs       Translation handler.
	 * @param User                        $user        Current user.
	 * @param Conf                        $conf        Application configuration.
	 * @param DoliDB                      $db          Database handler.
	 */
	public function __construct(SupplierProposalFileManager $fileManager, Translate $langs, User $user, Conf $conf, DoliDB $db)
	{
		$this->fileManager = $fileManager;
		$this->langs = $langs;
		$this->user = $user;
		$this->conf = $conf;
		$this->db = $db;
	}

	/**
	 * Validate proposal with optional file check
	 *
	 * @param SupplierProposal $object Supplier proposal being validated.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	public function validateProposal(SupplierProposal $object) : array
	{
		dol_syslog("SupplierProposalActionHandler::validateProposal START for proposal ID=" . $object->id, LOG_DEBUG);

		$attachmentCheck = $this->checkMandatoryAttachment($object, 'SupplierProposalActionHandler::validateProposal');
		if (!$attachmentCheck['success']) {
			return $attachmentCheck;
		}

		// Move session files to proposal directory
		$moveResult = $this->fileManager->moveSessionFilesToProposal($object);
		dol_syslog("SupplierProposalActionHandler::validateProposal moveSessionFiles result: success=" . $moveResult['success'], LOG_DEBUG);

		return $this->markSupplierResponseReceived($object);
	}

	/**
	 * Submit the supplier response from the external portal.
	 *
	 * This is the single business action behind the "Reply" button: persist posted prices,
	 * create the agenda event/comment with optional files, then mark the supplier response as received.
	 *
	 * @param SupplierProposal $object     Supplier proposal.
	 * @param string           $comment    Supplier comment.
	 * @param string           $title      Optional agenda title.
	 * @param array            $linePrices Posted line prices indexed by supplier_proposaldet rowid.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	public function submitResponse(SupplierProposal $object, string $comment, string $title = '', array $linePrices = array()) : array
	{
		$attachmentCheck = $this->checkMandatoryAttachment($object, 'SupplierProposalActionHandler::submitResponse');
		if (!$attachmentCheck['success']) {
			return $attachmentCheck;
		}

		$priceResult = $this->updateLinePrices($object, $linePrices);
		if (!$priceResult['success']) {
			return $priceResult;
		}

		$commentResult = $this->addComment($object, $comment, $title, true);
		if (!$commentResult['success']) {
			return $commentResult;
		}

		return $this->markSupplierResponseReceived($object);
	}

	/**
	 * Update proposal line prices from the submitted form values.
	 *
	 * @param SupplierProposal $object     Supplier proposal.
	 * @param array            $linePrices Posted line prices indexed by line id.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	private function updateLinePrices(SupplierProposal $object, array $linePrices) : array
	{
		if (empty($linePrices)) {
			return array('success' => true, 'message' => '', 'type' => 'mesgs');
		}

		if (!method_exists($object, 'updateline')) {
			return array(
				'success' => false,
				'message' => $this->langs->trans('CLICHAUMEIL_AJAX_UPDATE_METHOD_MISSING'),
				'type' => 'errors'
			);
		}

		SupplierProposalService::ensureThirdpartyLoaded($object);

		$linesById = array();
		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				if (!empty($line->id)) {
					$linesById[(int) $line->id] = $line;
				}
			}
		}

		$previousStatus = isset($object->status) ? (int) $object->status : null;
		$draftWasRequested = false;
		$updatedCount = 0;

		foreach ($linePrices as $lineId => $submittedPrice) {
			$lineId = (int) $lineId;
			if (empty($lineId) || empty($linesById[$lineId]) || is_array($submittedPrice)) {
				continue;
			}

			$lineToUpdate = $linesById[$lineId];
			$newPuHt = price2num($submittedPrice);
			$currentPuHt = price2num($lineToUpdate->subprice);

			if (abs((float) $newPuHt - (float) $currentPuHt) < 0.000001) {
				continue;
			}

			if (!$draftWasRequested && $previousStatus !== null && $previousStatus !== (int) SupplierProposal::STATUS_DRAFT) {
				$draftResult = $object->setDraft($this->user);
				if ($draftResult < 0) {
					return array(
						'success' => false,
						'message' => $object->error ?: $this->db->lasterror(),
						'type' => 'errors'
					);
				}
				$draftWasRequested = true;
			}

			$res = $object->updateline(
				$lineToUpdate->id,
				$newPuHt,
				$lineToUpdate->qty,
				isset($lineToUpdate->remise_percent) ? $lineToUpdate->remise_percent : 0,
				$lineToUpdate->tva_tx,
				isset($lineToUpdate->localtax1_tx) ? $lineToUpdate->localtax1_tx : 0,
				isset($lineToUpdate->localtax2_tx) ? $lineToUpdate->localtax2_tx : 0,
				$lineToUpdate->desc,
				'HT',
				isset($lineToUpdate->info_bits) ? $lineToUpdate->info_bits : 0,
				isset($lineToUpdate->special_code) ? $lineToUpdate->special_code : 0,
				isset($lineToUpdate->fk_parent_line) ? $lineToUpdate->fk_parent_line : 0,
				SupplierProposalService::UPDATE_LINE_RECOMPUTE_TOTALS,
				isset($lineToUpdate->fk_fournprice) ? $lineToUpdate->fk_fournprice : 0,
				isset($lineToUpdate->pa_ht) ? $lineToUpdate->pa_ht : 0,
				isset($lineToUpdate->label) ? $lineToUpdate->label : '',
				isset($lineToUpdate->product_type) ? $lineToUpdate->product_type : 0,
				isset($lineToUpdate->array_options) && is_array($lineToUpdate->array_options) ? $lineToUpdate->array_options : array(),
				$this->getLineSupplierReference($lineToUpdate),
				isset($lineToUpdate->fk_unit) ? $lineToUpdate->fk_unit : 0
			);

			if ($res < 0) {
				$this->restorePreviousStatusAfterLineUpdate($object, $previousStatus);
				return array(
					'success' => false,
					'message' => $this->langs->trans('CLICHAUMEIL_AJAX_UPDATE_FAILED', $object->error ?: $this->db->lasterror()),
					'type' => 'errors'
				);
			}

			$lineToUpdate->subprice = $newPuHt;
			$updatedCount++;
		}

		$restoreResult = $this->restorePreviousStatusAfterLineUpdate($object, $previousStatus);
		if ($restoreResult < 0) {
			return array(
				'success' => false,
				'message' => $this->langs->trans('CLICHAUMEIL_AJAX_RESTORE_STATUS_FAILED', $object->error ?: $this->db->lasterror()),
				'type' => 'errors'
			);
		}

		dol_syslog(__METHOD__ . ' updated ' . $updatedCount . ' line price(s) for proposal id=' . ((int) $object->id), LOG_DEBUG);

		return array('success' => true, 'message' => '', 'type' => 'mesgs');
	}

	/**
	 * Restore the previous supplier proposal status after temporary draft line updates.
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @param int|null         $previousStatus Previous status.
	 * @return int 1 if no restore needed or restore OK, <0 on error.
	 */
	private function restorePreviousStatusAfterLineUpdate(SupplierProposal $object, ?int $previousStatus) : int
	{
		if ($previousStatus === null || $previousStatus === (int) SupplierProposal::STATUS_DRAFT) {
			return 1;
		}

		$currentStatus = isset($object->status) ? (int) $object->status : (int) SupplierProposal::STATUS_DRAFT;
		if ($currentStatus === $previousStatus) {
			return 1;
		}

		return $object->setStatut($previousStatus);
	}

	/**
	 * Get the supplier reference carried by the proposal line itself.
	 *
	 * @param SupplierProposalLine $line Supplier proposal line.
	 * @return string
	 */
	private function getLineSupplierReference(SupplierProposalLine $line) : string
	{
		if (isset($line->ref_fourn) && $line->ref_fourn !== '') {
			return (string) $line->ref_fourn;
		}

		if (isset($line->ref_supplier) && $line->ref_supplier !== '') {
			return (string) $line->ref_supplier;
		}

		return '';
	}

	/**
	 * Check the mandatory attachment rule before a supplier response is accepted.
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @param string           $logPrefix Log prefix.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	private function checkMandatoryAttachment(SupplierProposal $object, string $logPrefix) : array
	{
		$mandatoryConfig = getDolGlobalInt('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL');
		dol_syslog($logPrefix . " CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL=" . $mandatoryConfig, LOG_DEBUG);

		if (!$mandatoryConfig) {
			return array('success' => true, 'message' => '', 'type' => 'mesgs');
		}

		$keytoavoidconflict = '-' . $object->id;
		$hasFilesInSession = !empty($_SESSION["listofnames" . $keytoavoidconflict])
			&& !empty($_SESSION["listofpaths" . $keytoavoidconflict]);
		$hasFilesInTimeline = $this->hasFilesInTimeline($object);
		$hasFile = $hasFilesInSession || $hasFilesInTimeline;

		dol_syslog(
			$logPrefix . " hasFilesInSession=" . ($hasFilesInSession ? 'YES' : 'NO') .
			" hasFilesInTimeline=" . ($hasFilesInTimeline ? 'YES' : 'NO'),
			LOG_DEBUG
		);

		if (!$hasFile) {
			dol_syslog($logPrefix . " blocking response - no attached file", LOG_WARNING);
			return array(
				'success' => false,
				'message' => $this->langs->trans('CLICHAUMEIL_ERROR_NO_PDF_ATTACHED'),
				'type' => 'errors'
			);
		}

		return array('success' => true, 'message' => '', 'type' => 'mesgs');
	}

	/**
	 * Mark supplier response as received and notify the internal follow-up manager.
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	private function markSupplierResponseReceived(SupplierProposal $object) : array
	{
		if (!is_array($object->array_options)) {
			$object->array_options = array();
		}
		$object->array_options["options_clichaumeil_supplierstatut"] = 'CLICHAUMEIL_FILE_RECEIVED';
		$res = $object->updateExtraField('clichaumeil_supplierstatut');
		if ($res < 0) {
			$errorMessage = $object->error ?: $this->db->lasterror();
			dol_syslog(__METHOD__ . ' failed to update supplier status for proposal id=' . ((int) $object->id) . ' error=' . $errorMessage, LOG_WARNING);
		} elseif (empty($object->array_options["options_clichaumeil_supplierresponsedate"])) {
			$object->array_options["options_clichaumeil_supplierresponsedate"] = dol_now();
			$responseDateResult = $object->updateExtraField('clichaumeil_supplierresponsedate');
			if ($responseDateResult < 0) {
				dol_syslog(__METHOD__ . ' failed to update supplier response date for proposal id=' . ((int) $object->id) . ' error=' . ($object->error ?: $this->db->lasterror()), LOG_WARNING);
			}
		}

		if ($res >= 0) {
			$this->sendSupplierResponseNotification($object);

			return array(
				'success' => true,
				'message' => $this->langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALVALIDATED'),
				'type' => 'mesgs'
			);
		} else {
			return array(
				'success' => false,
				'message' => $object->error,
				'type' => 'errors'
			);
		}
	}

	/**
	 * Add comment with optional files
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @param string           $comment Comment text.
	 * @param string           $title   Comment title.
	 * @param bool             $allowEmpty Whether a response event may be created without text/files.
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	public function addComment(SupplierProposal $object, string $comment, string $title = '', bool $allowEmpty = false) : array
	{
		$keytoavoidconflict = '-' . $object->id;
		$hasFiles = !empty($_SESSION["listofpaths" . $keytoavoidconflict]);

		// Require either comment or files
		if (empty($comment) && !$hasFiles && !$allowEmpty) {
			return array(
				'success' => false,
				'message' => $this->langs->trans('CommentError'),
				'type' => 'errors'
			);
		}

		if (empty($comment) && !$hasFiles && $allowEmpty) {
			$comment = $this->langs->trans('CLICHAUMEIL_SUPPLIER_RESPONSE_SUBMITTED');
		}

		// Create action/comment
		$actionId = $this->createAction($object, $comment, $title);

		if ($actionId < 0) {
			return array(
				'success' => false,
				'message' => $this->langs->trans('CommentError'),
				'type' => 'errors'
			);
		}

		// Handle files if present
		if ($hasFiles) {
			$this->fileManager->copySessionFilesToProposalAndAction($object, $actionId);
		}

		// Determine success message
		if (!empty($comment) && $hasFiles) {
			$message = $this->langs->trans('CommentAdded');
		} elseif (!empty($comment)) {
			$message = $this->langs->trans('CommentAdded');
		} else {
			$message = $this->langs->trans('FileUploaded');
		}

		return array(
			'success' => true,
			'message' => $message,
			'type' => 'mesgs'
		);
	}

	/**
	 * Create action in database
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @param string           $comment Comment text.
	 * @param string           $title   Action title.
	 * @return int Action ID if OK, <0 if error
	 */
	private function createAction(SupplierProposal $object, string $comment, string $title) : int
	{
		$actioncomm = new ActionComm($this->db);
		$actioncomm->datep = dol_now();

		// Set note: use comment if provided, otherwise default message
		if (!empty($comment)) {
			$actioncomm->note_private = $comment;
		} else {
			$actioncomm->note_private = $this->langs->trans('CLICHAUMEIL_FILE_ATTACHED_WITHOUT_MESSAGE');
		}

		$actioncomm->elementtype = 'supplier_proposal';
		$actioncomm->elementid = $object->id;
		$actioncomm->user_creation_id = $this->user->id;
		$actioncomm->userownerid = $this->user->id;
		$actioncomm->type_code = 'AC_OTH';

		// Set label/title
		if (!empty($title)) {
			$actioncomm->label = $title;
		} else {
			// If no message (only file), use specific title format
			if (empty($comment)) {
				$actioncomm->label = $this->langs->trans(
					"CLICHAUMEIL_FILE_DEPOSITED_BY",
					$this->user->lastname,
					$this->user->firstname
				);
			} else {
				// Default title for messages
				$actioncomm->label = $this->langs->trans(
					"CLICHAUMEIL_LABEL_OWNER",
					$this->user->firstname . ' ' . $this->user->lastname
				);
			}
		}

		// Link to project if proposal has one
		if (!empty($object->fk_project)) {
			$actioncomm->fk_project = $object->fk_project;
		}

		// Set status as "Done" (100%)
		$actioncomm->percentage = 100;

		$actionEntity = !empty($object->entity) ? $object->entity : $this->conf->entity;
		$actioncomm->entity = $actionEntity;

		return $actioncomm->create($this->user);
	}

	/**
	 * Send notification email to the internal follow-up manager if configured.
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @return void
	 */
	private function sendSupplierResponseNotification(SupplierProposal $object): void
	{
		$recipients = $this->getSupplierProposalFollowupEmails($object);
		if (empty($recipients)) {
			return;
		}

		$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
		if (empty($from)) {
			dol_syslog(__METHOD__ . ' missing MAIN_MAIL_EMAIL_FROM for supplier proposal id=' . ((int) $object->id), LOG_WARNING);
			return;
		}

		if (empty($object->thirdparty) || empty($object->thirdparty->id)) {
			$fetchThirdpartyResult = $object->fetch_thirdparty();
			if ($fetchThirdpartyResult <= 0) {
				dol_syslog(__METHOD__ . ' failed to load thirdparty for supplier proposal id=' . ((int) $object->id) . ' error=' . $object->error, LOG_WARNING);
			}
		}

		$supplierName = '';
		if (!empty($object->thirdparty) && !empty($object->thirdparty->name)) {
			$supplierName = $object->thirdparty->name;
		}
		if ($supplierName === '') {
			$supplierName = $this->langs->trans('ThirdParty');
		}

		$proposalUrl = dol_buildpath('/supplier_proposal/card.php', 2) . '?id=' . ((int) $object->id);
		$subject = $this->langs->transnoentitiesnoconv('CliChaumeilSupplierResponseMailSubject', $supplierName, $object->ref);
		$proposalLink = '<a href="' . dol_escape_htmltag($proposalUrl) . '">' . dol_escape_htmltag($proposalUrl) . '</a>';
		$body = $this->langs->transnoentitiesnoconv('CliChaumeilSupplierResponseMailBody', $supplierName, $object->ref, $proposalLink);

		$mail = new CMailFile(
			$subject,
			implode(',', $recipients),
			$from,
			$body,
			array(),
			array(),
			array(),
			'',
			'',
			0,
			1
		);

		if (!$mail->sendfile()) {
			dol_syslog(__METHOD__ . ' failed to send supplier response email for proposal id=' . ((int) $object->id) . ' error=' . $mail->error, LOG_WARNING);
		}
	}

	/**
	 * Return unique email recipients for internal SALESREPFOLL contacts.
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @return string[]
	 */
	private function getSupplierProposalFollowupEmails(SupplierProposal $object): array
	{
		$contacts = $object->liste_contact(-1, 'internal');
		if (empty($contacts) || !is_array($contacts)) {
			return array();
		}

		$emails = array();
		foreach ($contacts as $contact) {
			$contactCode = $contact['code'] ?? $contact['code_type_contact'] ?? '';
			if ($contactCode !== 'SALESREPFOLL') {
				continue;
			}

			$email = trim((string) ($contact['email'] ?? ''));
			if ($email === '') {
				continue;
			}

			$emails[$email] = $email;
		}

		return array_values($emails);
	}

	/**
	 * Check if any timeline action already stores files
	 *
	 * @param SupplierProposal $object Supplier proposal.
	 * @return bool
	 */
	private function hasFilesInTimeline(SupplierProposal $object) : bool
	{
		$sql = "SELECT id, entity FROM " . $this->db->prefix() . "actioncomm";
		$sql .= " WHERE fk_element = " . intval($object->id);
		$sql .= " AND elementtype = '" . $this->db->escape($object->element) . "'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		while ($action = $this->db->fetch_object($resql)) {
			$actionEntity = !empty($action->entity) ? $action->entity : $this->conf->entity;
			if (empty($this->conf->agenda->multidir_output[$actionEntity])) continue;
			$actionDir = $this->conf->agenda->multidir_output[$actionEntity] . '/' . $action->id;
			if (is_dir($actionDir)) {
				$files = dol_dir_list($actionDir, 'files');
				if (!empty($files)) {
					$this->db->free($resql);
					return true;
				}
			}
		}

		$this->db->free($resql);
		return false;
	}
}
