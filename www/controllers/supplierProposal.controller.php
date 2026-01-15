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

require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once __DIR__.'/../../lib/supplierSupplierProposalTools.php';
require_once __DIR__.'/../../class/SupplierProposalListView.class.php';
require_once __DIR__.'/../../class/SupplierProposalService.class.php';

class SupplierProposalController extends Controller
{
	/**
	 * action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @param void
	 * @return bool true on success, false on failure
	 */
	public function action() : bool
	{
		global $langs;

		$langs->load("clichaumeil@clichaumeil");

		$context = Context::getInstance();
		if (!hasSupplierProposalAccess()) {
			return false;
		}

		$context->title = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSAL');
		$context->desc = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSALDESC');
		$context->menu_active[] = 'supplierProposal';
		return true;
	}

	/**
	 * Display method - renders the page
	 *
	 * @return bool true on success, false on failure
	 */
	public function display() : bool
	{
		global $user;

		$context = Context::getInstance();

		if (!hasSupplierProposalAccess()) {
			return $this->display404();
		}
		if (!$this->loadTemplate('header')) {
			dol_syslog('Failed to load template header for supplierProposalExternal.', LOG_ERR);
			return false;
		}

		print '<script type="text/javascript" src="' . dol_buildpath('/clichaumeil/js/supplierProposalExternal.js', 1) . '"></script>';
		print '<section id="section-supplierProposal"><div class="container">';
		$this->printSupplierProposalTable($user->socid);
		print '</div></section>';

		if (!$this->loadTemplate('footer')) {
			dol_syslog('Failed to load template footer for supplierProposalExternal.', LOG_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Print supplier proposal table for external access
	 *
	 * @param ?int $socId Third-party ID
	 * @return void
	 */
	private function printSupplierProposalTable(?int $socId = 0) : void
	{
		global $langs, $db, $conf;

		$context = Context::getInstance();

		// Load language files
		$langs->load('supplier_proposal');
		$langs->load('fourn');
		$langs->load('clichaumeil@clichaumeil');

		// Initialize service
		$service = new SupplierProposalService($db, $conf, $langs);

		// Get data
		$sql = $service->getSqlForExternalList($socId);
		$tableItems = $context->dbTool->executeS($sql);

		$extraFields = getSupplierProposalExtraFields();
		$listView = new SupplierProposalListView($langs, $conf, $db, $context);
		print $listView->renderTable($tableItems, $extraFields);
	}
}
