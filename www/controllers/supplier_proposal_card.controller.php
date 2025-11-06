<?php
include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';

class SupplierProposalCardController extends Controller
{
	/**
	 * check current access to controller
	 *
	 * @param void
	 * @return  bool
	 */
	public function checkAccess()
	{
		global $conf, $user;
		$this->accessRight = isModEnabled('clichaumeil') && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL') && $user->hasRight('externalaccess', 'view_supplier_proposals');
		return true;
	}


	/**
	 * action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @param void
	 * @return void
	 */
	public function action()
	{
		global $langs, $conf, $user;

		$langs->load("clichaumeil@clichaumeil");

		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return;
		}

		// Handle file download action BEFORE any output
		$postAction = GETPOST('action', 'alpha');
		if ($postAction == 'download-action-file') {
			$actionid = GETPOST('actionid', 'int');
			$filename = GETPOST('filename', 'alpha');

			if ($actionid > 0 && !empty($filename)) {
				$filename = basename($filename); // Security: prevent path traversal
				$filepath = $conf->agenda->dir_output . '/' . $actionid . '/' . $filename;

				if (file_exists($filepath) && is_file($filepath)) {
					// Set headers for file download
					$mime = dol_mimetype($filepath);

					// By default, force download (attachment) for all files
					// Can be overridden with ?inline=1 to display in browser (for PDF/images)
					$disposition = 'attachment';

					// Allow inline display via URL parameter
					if (GETPOST('inline', 'int') == 1) {
						$disposition = 'inline';
					}

					header('Content-Type: ' . $mime);
					header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
					header('Content-Length: ' . filesize($filepath));
					readfile($filepath);
					exit;
				} else {
					http_response_code(404);
					echo 'File not found';
					exit;
				}
			}
		}

		$context->title = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSAL');
		$context->desc = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSALDESC');
		$context->menu_active[] = 'supplierProposal';
	}

	/**
	 * Display method
	 *
	 * @param void
	 * @return void
	 */
	public function display()
	{
		global $conf, $user;

		$context = Context::getInstance();

		if (!$context->controllerInstance->checkAccess()) {
			return $this->display404();
		}

		$this->loadTemplate('header');

		print '<section id="section-supplierProposalCard"><div class="container">';

		$supplierProposalId = GETPOST('id', 'int');
		printSupplierProposalCard($supplierProposalId, $user->socid);

		print '</div></section>';

		$this->loadTemplate('footer');
	}
}
