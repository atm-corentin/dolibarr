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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

require_once __DIR__.'/../../class/SupplierProposalService.class.php';
require_once __DIR__.'/../../class/SupplierProposalFileManager.class.php';
require_once __DIR__.'/../../class/SupplierProposalActionHandler.class.php';
require_once __DIR__.'/../../class/SupplierProposalView.class.php';
require_once __DIR__.'/../../lib/supplierSupplierProposalTools.php';

/**
 * Controller for Supplier Proposal Card (external access)
 * Follows MVC pattern and SOLID principles
 *
 * Responsibilities:
 * - Handle HTTP requests/responses
 * - Validate user input
 * - Coordinate between Service, ActionHandler and View
 * - Manage session and context
 */
class SupplierProposalCardController extends Controller
{
	/** @var SupplierProposalService */
	private $service;

	/** @var SupplierProposalFileManager */
	private $fileManager;

	/** @var SupplierProposalActionHandler */
	private $actionHandler;

	/** @var SupplierProposalView */
	private $view;


	/**
	 * Action method - called before display
	 * Initialize services and handle POST actions
	 *
	 * @return bool true on success, false on failure
	 */
	public function action() : bool
	{
		global $langs, $conf, $db, $user;

		$langs->loadLangs(array(
			"clichaumeil@clichaumeil",
			"supplier_proposal",
			"fourn",
			"externalaccess@externalaccess",
			"externalticket@externalaccess",
			"mails"
		));

		$context = Context::getInstance();

		if (!hasSupplierProposalAccess()) {
			return false;
		}

		// Handle file download action BEFORE any output
		$postAction = GETPOST('action', 'alpha');
		if ($postAction == 'download-action-file') {
			$this->handleFileDownload();
			return true; // Stop execution after download
		}

		// Initialize services
		$this->service = new SupplierProposalService($db, $conf);
		$this->fileManager = new SupplierProposalFileManager($conf, $db);
		$this->actionHandler = new SupplierProposalActionHandler($this->fileManager, $langs, $user, $conf, $db);
		$this->view = new SupplierProposalView($langs, $conf, $db, $user, $context);

		// Set page context
		$context->title = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSAL');
		$context->desc = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSALDESC');
		$context->menu_active[] = 'supplierProposal';

		// Handle POST actions
		return $this->handlePostActions();
	}

	/**
	 * Display method - renders the page
	 *
	 * @return bool true on success, false on failure
	 */
	public function display() : bool
	{
		global $user, $db, $conf, $langs;

		$context = Context::getInstance();

		if (!hasSupplierProposalAccess()) {
			return false;
		}
		// Get supplier proposal ID
		$supplierPropalId = GETPOST('id', 'int');
		if (empty($supplierPropalId)) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
			return $this->display404();
		}

		// Fetch proposal
		$object = $this->service->fetchProposalWithLines($supplierPropalId, $user->socid);
		if (!$object) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
			return $this->display404();
		}

		// Security: Check if proposal belongs to user's company
		if ($object->socid != $user->socid) {
			return $this->display404();
		}

		// Handle file error messages from JavaScript
		$this->handleFileErrorMessages();

		// Fetch related data
		$thirdparty = new Societe($db);
		$thirdparty->fetch($object->socid);
		$documents = $this->service->getProposalDocuments($object, true);
		$TMessage = $this->service->fetchProposalActions($object);

		// Currency
		$currencyCode = !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;

		// Load templates
		$this->loadTemplate('header');

		// Include JavaScript
		print $this->view->includeJavaScript('supplierProposalCard.js');

		// Start form
		print '<form role="form" autocomplete="off" class="form" method="post" enctype="multipart/form-data" action="' . $context->getControllerUrl('supplier_proposal_card') . '&id=' . $object->id . '&time=' . time() . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '" />';
		print '<input type="hidden" name="id" value="' . $object->id . '" />';

		// Render sections
		print $this->view->renderProposalSummary($object, $thirdparty, $documents);
		print $this->view->renderProposalLines($object, $currencyCode);
		print $this->view->renderCommentForm($object); // Form first to keep it above discussion history
		print $this->view->renderTimeline($TMessage, $object);

		// Close form
		print '</form>';

		// JavaScript initialization
		if (!$this->renderJavaScriptInit($object)) {
			dol_syslog('Failed to render JavaScript init for supplier proposal ' . $object->id, LOG_ERR);
			return false;
		}

		// Load template footer
		// We also check if the footer loading fails
		if (!$this->loadTemplate('footer')) {
			dol_syslog('Failed to load footer for supplier proposal ' . $object->id, LOG_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Handle POST actions
	 *
	 * @return bool true to continue to display(), false to stop
	 */
	private function handlePostActions() : bool
	{
		global $user, $db, $langs;

		$postAction = GETPOST('action', 'alpha');
		if (empty($postAction)) {
			$this->handleFileRemoval();
			return true; // Continue to display even if file removal fails
		}

		$context = Context::getInstance();
		$supplierPropalId = GETPOST('id', 'int');

		if (empty($supplierPropalId)) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
			$this->redirectToProposal($supplierPropalId);
			return false; // Stop execution after redirect
		}

		// Fetch proposal for actions
		$object = $this->service->fetchProposalWithLines($supplierPropalId, $user->socid);
		if (!$object || $object->socid != $user->socid) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
			$this->redirectToProposal($supplierPropalId);
			return false; // Stop execution after redirect
		}

		switch ($postAction) {
			case 'add-comment-file':
				$this->handleFileUpload($object);
				$this->redirectToProposal($supplierPropalId, 'form-propal-message-container');
				return false; // Stop execution after redirect

			case 'validate_proposal':
				$this->handleValidateProposal($object);
				$this->redirectToProposal($supplierPropalId);
				return false; // Stop execution after redirect

			case 'new-comment':
				$this->handleNewComment($object);
				// Scroll back to the comment form after posting
				$this->redirectToProposal($supplierPropalId, 'form-propal-message-container');
				return false; // Stop execution after redirect

			default:
				dol_syslog('Unknown POST action received: ' . $postAction, LOG_WARNING);
				break;
		}

		return true;
	}

	/**
	 * Handle file download
	 *
	 * @return void
	 */
	private function handleFileDownload() :	void
	{
		global $conf, $db;

		$actionid = GETPOST('actionid', 'int');
		$filename = GETPOST('filename', 'alpha');

		if ($actionid > 0 && !empty($filename)) {
			$filename = basename($filename); // Security: prevent path traversal
			$path = '';
			$action = new ActionComm($db);
			if ($action->fetch($actionid) > 0) {
				$entity = !empty($action->entity) ? $action->entity : $conf->entity;
				$agendaRoot = $conf->agenda->multidir_output[$entity] ?? '';
				if (!empty($agendaRoot)) {
					$path = $agendaRoot . '/' . $actionid . '/' . $filename;
				}
			}
			if (empty($path)) {
				return;
			}

			if (file_exists($path) && is_file($path)) {
				// Set headers for file download
				$mime = dol_mimetype($path);
				header('Content-Type: ' . $mime);
				header('Content-Disposition: attachment; filename="' . $filename . '"');
				header('Content-Length: ' . filesize($path));
				readfile($path);
				exit;
			}
		}
	}

	/**
	 * Handle file upload to session
	 *
	 * @param SupplierProposal $object
	 * @return void
	 */
	private function handleFileUpload(SupplierProposal $object) : void
	{
		global $langs, $conf;

		$context = Context::getInstance();
		$result = $this->fileManager->uploadFileToSession($object->id);

		if ($result['success']) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_FILEADDED'), 'mesgs');
		} else {
			// Get translated error message based on error code
			$errorMessage = $this->getUploadErrorTranslation($result['error_code'], $conf);
			$context->setEventMessages($errorMessage, 'errors');
		}
	}

	/**
	 * Get translated error message for file upload errors
	 *
	 * @param string $errorCode Error code from FileManager
	 * @param Conf $conf Configuration object
	 * @return string Translated error message
	 */
	private function getUploadErrorTranslation(string $errorCode, Conf $conf) : string
	{
		global $langs;

		switch ($errorCode) {
			case 'FILE_TOO_LARGE':
				$maxSize = ini_get('upload_max_filesize');
				if (empty($maxSize)) {
					$maxSize = ini_get('post_max_size');
				}
				return $langs->trans('CLICHAUMEIL_FILE_TOO_LARGE', $maxSize);

			case 'PARTIAL_UPLOAD':
				return $langs->trans('CLICHAUMEIL_FILE_PARTIAL_UPLOAD');

			case 'NO_FILE':
				return $langs->trans('CLICHAUMEIL_FILENOTFOUND');

			case 'NO_TMP_DIR':
				return $langs->trans('CLICHAUMEIL_FILE_NO_TMP_DIR');

			case 'CANT_WRITE':
				return $langs->trans('CLICHAUMEIL_FILE_CANT_WRITE');

			case 'EXTENSION_BLOCKED':
				return $langs->trans('CLICHAUMEIL_FILE_EXTENSION_BLOCKED');

			case 'UPLOAD_FAILED':
			case 'UNKNOWN_ERROR':
			default:
				return $langs->trans('CLICHAUMEIL_FILE_UPLOAD_ERROR');
		}
	}

	/**
	 * Handle proposal validation
	 *
	 * @param SupplierProposal $object
	 * @return void
	 */
	private function handleValidateProposal(SupplierProposal $object) : void
	{
		$context = Context::getInstance();
		$result = $this->actionHandler->validateProposal($object);

		$context->setEventMessages($result['message'], $result['type']);
	}

	/**
	 * Handle new comment
	 *
	 * @param SupplierProposal $object
	 * @return void
	 */
	private function handleNewComment(SupplierProposal $object) : void
	{
		$comment = GETPOST('propal-comment', 'restricthtml');
		$title = GETPOST('propal-title', 'aZ09');

		$context = Context::getInstance();
		$result = $this->actionHandler->addComment($object, $comment, $title);

		$context->setEventMessages($result['message'], $result['type']);
	}

	/**
	 * Handle file removal from session
	 *
	 * @return void
	 */
	private function handleFileRemoval() : void
	{
		global $langs;

		$removedfileNb = GETPOST('removedfile', 'int');
		$supplierPropalId = GETPOST('id', 'int');

		// Check button names if hidden field is empty
		if (empty($removedfileNb)) {
			foreach ($_POST as $key => $value) {
				if (preg_match('/^removedfile_(\d+)$/', $key, $matches)) {
					$removedfileNb = (int)$matches[1] + 1;
					break;
				}
			}
		}

		$context = Context::getInstance();
		if ($removedfileNb > 0 && !empty($supplierPropalId)) {
			// dol_remove_file_process() doesn't return a value, it sets session messages
			// So we just call it and don't check the return value
			// The success message is already set by dol_remove_file_process() via setEventMessages()
			$this->fileManager->removeFileFromSession($removedfileNb, $supplierPropalId);
		}
	}

	/**
	 * Render JavaScript initialization
	 *
	 * @param SupplierProposal $object
	 * @return bool
	 */
	private function renderJavaScriptInit(SupplierProposal $object) : bool
	{
		global $langs, $conf;

		$url = dol_buildpath('/clichaumeil/script/interface.php', 1);

		// Get max file size from PHP configuration
		$maxFileSize = $this->getMaxUploadSize();
		$maxFileSizeFormatted = $this->formatBytes($maxFileSize);

		$config = array(
			'ajaxUrl' => $url,
			'propalId' => $object->id,
			'token' => newToken(),
			'mandatoryFiles' => getDolGlobalInt('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL'),
			'errorFileUploadMsg' => dol_escape_js($langs->trans('CLICHAUMEIL_FILE_UPLOAD_ERROR')),
			'maxFileSize' => $maxFileSize,
			'maxFileSizeFormatted' => $maxFileSizeFormatted,
			'fileTooLargeMsg' => dol_escape_js($langs->trans('CLICHAUMEIL_FILE_TOO_LARGE', $maxFileSizeFormatted))
		);

		print '<script type="text/javascript">';
		print 'initSupplierProposalCard(' . json_encode($config) . ');';
		print '</script>';

		return true;
	}

	/**
	 * Get maximum upload size from PHP configuration
	 *
	 * @return int Maximum upload size in bytes
	 */
	private function getMaxUploadSize() : int
	{
		// Get upload_max_filesize
		$uploadMax = ini_get('upload_max_filesize');
		$uploadMaxBytes = $this->parseSize($uploadMax);

		// Get post_max_size
		$postMax = ini_get('post_max_size');
		$postMaxBytes = $this->parseSize($postMax);

		// Return the smaller of the two
		return min($uploadMaxBytes, $postMaxBytes);
	}

	/**
	 * Parse size string (e.g., "8M", "2G") to bytes
	 *
	 * @param string $size Size string
	 * @return int Size in bytes
	 */
	private function parseSize(string $size) : int
	{
		$size = trim($size);
		$last = strtolower($size[strlen($size) - 1]);
		$size = (int)$size;

		switch ($last) {
			case 'g':
				$size *= 1024;
				// fall through
			case 'm':
				$size *= 1024;
				// fall through
			case 'k':
				$size *= 1024;
		}

		return $size;
	}

	/**
	 * Format bytes to human-readable size
	 *
	 * @param int $bytes Size in bytes
	 * @return string Formatted size (e.g., "8M", "2G")
	 */
	private function formatBytes(int $bytes) : string
	{
		if ($bytes >= 1073741824) {
			return round($bytes / 1073741824, 2) . 'G';
		} elseif ($bytes >= 1048576) {
			return round($bytes / 1048576, 2) . 'M';
		} elseif ($bytes >= 1024) {
			return round($bytes / 1024, 2) . 'K';
		} else {
			return $bytes . 'B';
		}
	}

	/**
	 * Redirect to proposal card page (POST/Redirect/GET pattern)
	 *
	 * @param int $proposalId Supplier proposal ID
	 * @return void
	 */
	private function redirectToProposal(int $proposalId, string $fragment = '') : void
	{
		$context = Context::getInstance();

		// Build redirect URL without action parameter (clean URL for GET request)
		$redirectUrl = $context->getControllerUrl('supplier_proposal_card') . '&id=' . $proposalId;

		// Optionally add an anchor to keep the user near a specific section after redirect
		if (!empty($fragment)) {
			$redirectUrl .= '&scroll_to=' . urlencode(ltrim($fragment, '#'));
			$redirectUrl .= '#' . ltrim($fragment, '#');
		}

		// Perform header redirect
		header('Location: ' . $redirectUrl);
		exit;
	}

	/**
	 * Handle file error messages sent from JavaScript
	 * Displays error messages via setEventMessages when JavaScript detects file issues
	 *
	 * @return void
	 */
	private function handleFileErrorMessages() : void
	{
		global $langs, $conf;

		$fileError = GETPOST('file_error', 'alpha');

		if (empty($fileError)) {
			return;
		}

		$context = Context::getInstance();

		// Get max file size for the error message
		$maxFileSize = $this->getMaxUploadSize();
		$maxFileSizeFormatted = $this->formatBytes($maxFileSize);

		switch ($fileError) {
			case 'FILE_TOO_LARGE':
				$context->setEventMessages(
					$langs->trans('CLICHAUMEIL_FILE_TOO_LARGE', $maxFileSizeFormatted),
					'errors'
				);
				break;

			case 'UPLOAD_ERROR':
				$context->setEventMessages(
					$langs->trans('CLICHAUMEIL_FILE_UPLOAD_ERROR'),
					'errors'
				);
				break;

			default:
				// Unknown error code
				$context->setEventMessages(
					$langs->trans('CLICHAUMEIL_FILE_UNKNOWN_ERROR'),
					'errors'
				);
				break;
		}
	}
}
