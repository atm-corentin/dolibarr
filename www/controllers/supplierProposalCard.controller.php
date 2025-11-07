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
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/societe/class/societe.class.php');

require_once __DIR__ . '/../../class/SupplierProposalService.class.php';
require_once __DIR__ . '/../../class/SupplierProposalFileManager.class.php';
require_once __DIR__ . '/../../class/SupplierProposalActionHandler.class.php';
require_once __DIR__ . '/../../class/SupplierProposalView.class.php';
require_once __DIR__ . '/../../lib/supplierSupplierProposalTools.php';

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
	 * Check access rights
	 *
	 * @return bool
	 */
	public function checkAccess()
	{
		global $conf, $user;
		$this->accessRight = isModEnabled('clichaumeil')
			&& getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')
			&& $user->hasRight('externalaccess', 'view_supplier_proposals');
		return true;
	}

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

		if (!checkAccess()) {
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

		if (!checkAccess()) {
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
		print $this->view->renderTimeline($TMessage, $object);
		print $this->view->renderCommentForm($object);

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
			return true; // Continue to display to show error message
		}

		// Fetch proposal for actions
		$object = $this->service->fetchProposalWithLines($supplierPropalId, $user->socid);
		if (!$object || $object->socid != $user->socid) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALNOTFOUND'), 'errors');
			return true; // Continue to display to show error message
		}

		switch ($postAction) {
			case 'add-comment-file':
				$this->handleFileUpload($object);
				break;

			case 'validate_proposal':
				$this->handleValidateProposal($object);
				break;

			case 'new-comment':
				$this->handleNewComment($object);
				break;

			default:
				dol_syslog('Unknown POST action received: ' . $postAction, LOG_WARNING);
				break;
		}

		// Always return true to continue to display() which will show the messages
		return true;
	}

	/**
	 * Handle file download
	 *
	 * @return void
	 */
	private function handleFileDownload() :	void
	{
		global $conf;

		$actionid = GETPOST('actionid', 'int');
		$filename = GETPOST('filename', 'alpha');

		if ($actionid > 0 && !empty($filename)) {
			$filename = basename($filename); // Security: prevent path traversal
			$filepath = $conf->agenda->dir_output . '/' . $actionid . '/' . $filename;

			if (file_exists($filepath) && is_file($filepath)) {
				// Set headers for file download
				$mime = dol_mimetype($filepath);
				header('Content-Type: ' . $mime);
				header('Content-Disposition: attachment; filename="' . $filename . '"');
				header('Content-Length: ' . filesize($filepath));
				readfile($filepath);
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
		global $langs;

		$context = Context::getInstance();
		$result = $this->fileManager->uploadFileToSession($object->id);

		if ($result > 0) {
			$context->setEventMessages($langs->trans('CLICHAUMEIL_FILEADDED'), 'mesgs');
		} elseif ($result < 0) {
			$context->setEventMessages($langs->trans('ErrorFileUpload'), 'errors');
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

		$config = array(
			'ajaxUrl' => $url,
			'propalId' => $object->id,
			'token' => newToken(),
			'mandatoryFiles' => getDolGlobalInt('CLICHAUMEIL_MENDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL'),
			'errorFileUploadMsg' => dol_escape_js($langs->trans('ErrorFileUpload'))
		);

		print '<script type="text/javascript">';
		print 'initSupplierProposalCard(' . json_encode($config) . ');';
		print '</script>';

		return true;
	}
}
