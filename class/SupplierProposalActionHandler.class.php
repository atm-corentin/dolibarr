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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
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
		$mandatoryConfig = getDolGlobalInt('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL');
		dol_syslog("SupplierProposalActionHandler::validateProposal CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL=" . $mandatoryConfig, LOG_DEBUG);

		if ($mandatoryConfig) {
			// Check if user uploaded a file in this validation (file in session)
			$keytoavoidconflict = '-' . $object->id;
			$hasFilesInSession = !empty($_SESSION["listofnames" . $keytoavoidconflict])
				&& !empty($_SESSION["listofpaths" . $keytoavoidconflict]);

			// Check if there is already a file attached through a previous timeline action
			$hasFilesInTimeline = $this->hasFilesInTimeline($object);

			$hasFileInSession = $hasFilesInSession || $hasFilesInTimeline;

			dol_syslog(
				"SupplierProposalActionHandler::validateProposal hasFilesInSession=" . ($hasFilesInSession ? 'YES' : 'NO') .
				" hasFilesInTimeline=" . ($hasFilesInTimeline ? 'YES' : 'NO'),
				LOG_DEBUG
			);

			if ($hasFilesInSession) {
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
		$statusValue = $this->resolveSupplierStatusValue($object, 'CLICHAUMEIL_FILE_RECEIVED');
		$object->array_options["options_clichaumeil_supplierstatut"] = $statusValue;
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
	 * Check if any timeline action already stores files
	 *
	 * @param SupplierProposal $object
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

	/**
	 * Resolve the extrafield option value according to the real options definitions
	 *
	 * @param SupplierProposal $object
	 * @param string $translationKey
	 * @return string
	 */
	private function resolveSupplierStatusValue(SupplierProposal $object, string $translationKey) : string
	{
		$label = $this->langs->transnoentities($translationKey);

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label($object->table_element);

		if (!empty($extrafields->attributes[$object->element]['param']['clichaumeil_supplierstatut']['options'])) {
			foreach ($extrafields->attributes[$object->element]['param']['clichaumeil_supplierstatut']['options'] as $key => $value) {
				if ($value == $label || $key == $label) {
					return $key;
				}
			}
		}

		if (function_exists('clichaumeilGetSupplierStatusLabelMap')) {
			$map = clichaumeilGetSupplierStatusLabelMap();
			if (isset($map[$label])) {
				return $map[$label];
			}
		}

		return $label;
	}
}
