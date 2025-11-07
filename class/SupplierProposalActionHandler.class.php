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

dol_include_once('/comm/action/class/actioncomm.class.php');

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
	 * @param SupplierProposalFileManager $fileManager
	 * @param Translate $langs
	 * @param User $user
	 * @param Conf $conf
	 * @param DoliDB $db
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
	 * @param SupplierProposal $object
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	public function validateProposal(SupplierProposal $object) : array
	{
		dol_syslog("SupplierProposalActionHandler::validateProposal START for proposal ID=" . $object->id, LOG_DEBUG);

		// Check if file attachment is mandatory BEFORE moving files
		$mandatoryConfig = getDolGlobalInt('CLICHAUMEIL_MENDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL');
		dol_syslog("SupplierProposalActionHandler::validateProposal CLICHAUMEIL_MENDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL=" . $mandatoryConfig, LOG_DEBUG);

		if ($mandatoryConfig) {
			// Check if user uploaded a file in this validation (file in session)
			$keytoavoidconflict = '-' . $object->id;
			$hasFileInSession = !empty($_SESSION["listofnames" . $keytoavoidconflict])
				&& !empty($_SESSION["listofpaths" . $keytoavoidconflict]);

			dol_syslog("SupplierProposalActionHandler::validateProposal hasFileInSession=" . ($hasFileInSession ? 'YES' : 'NO'), LOG_DEBUG);

			if ($hasFileInSession) {
				$listofnames = explode(';', $_SESSION["listofnames" . $keytoavoidconflict]);
				dol_syslog("SupplierProposalActionHandler::validateProposal Files in session: " . print_r($listofnames, true), LOG_DEBUG);
			}

			if (!$hasFileInSession) {
				dol_syslog("SupplierProposalActionHandler::validateProposal BLOCKING validation - no file uploaded for this validation", LOG_WARNING);
				return array(
					'success' => false,
					'message' => $this->langs->trans('CLICHAUMEIL_ERROR_NO_PDF_ATTACHED'),
					'type' => 'errors'
				);
			}
		}

		// Move session files to proposal directory
		$moveResult = $this->fileManager->moveSessionFilesToProposal($object);
		dol_syslog("SupplierProposalActionHandler::validateProposal moveSessionFiles result: success=" . $moveResult['success'], LOG_DEBUG);

		// Update extrafield status
		$object->array_options["options_clichaumeil_supplierstatut"] = $this->langs->transnoentities('CLICHAUMEIL_FILE_RECEIVED');
		$res = $object->updateExtraField('clichaumeil_supplierstatut');

		if ($res >= 0) {
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
	 * @param SupplierProposal $object
	 * @param string $comment Comment text
	 * @param string $title Comment title
	 * @return array ['success' => bool, 'message' => string, 'type' => 'mesgs'|'errors']
	 */
	public function addComment(SupplierProposal $object, string $comment, string $title = '') : array
	{
		$keytoavoidconflict = '-' . $object->id;
		$hasFiles = !empty($_SESSION["listofpaths" . $keytoavoidconflict]);

		// Require either comment or files
		if (empty($comment) && !$hasFiles) {
			return array(
				'success' => false,
				'message' => $this->langs->trans('CommentError'),
				'type' => 'errors'
			);
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
	 * @param SupplierProposal $object
	 * @param string $comment
	 * @param string $title
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
		$actioncomm->label = ($title == "")
			? $this->langs->trans("CLICHAUMEIL_LABEL_OWNER", $this->user->firstname . ' ' . $this->user->lastname)
			: $title;
		$actioncomm->entity = $this->conf->entity;

		return $actioncomm->create($this->user);
	}
}
