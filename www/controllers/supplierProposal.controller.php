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

include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
dol_include_once('/clichaumeil/lib/supplierSupplierProposalTools.php');

class SupplierProposalController extends Controller
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
		$this->accessRight = isModEnabled('clichaumeil') && getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL') && $user->hasRight('externalaccess', 'view_supplier_proposals');;
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
		global $langs;

		$langs->load("clichaumeil@clichaumeil");

		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return;
		}

		$context->title = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSAL');
		$context->desc = $langs->trans('CLICHAUMEIL_VIEWSUPPLIERPROPOSALDESC');
		$context->menu_active[] = 'supplierProposal';

	}

	/**
	 * Display method - renders the page
	 *
	 * @return void
	 */
	public function display()
	{
		global $user;

		$context = Context::getInstance();

		if (!$context->controllerInstance->checkAccess()) {
			return $this->display404();
		}

		$this->loadTemplate('header');

		// Include JavaScript file
		print '<script type="text/javascript" src="' . dol_buildpath('/clichaumeil/js/supplierProposalExternal.js', 1) . '"></script>';

		print '<section id="section-supplierProposal"><div class="container">';
		$this->printSupplierProposalTable($user->socid);
		print '</div></section>';

		$this->loadTemplate('footer');
	}

	/**
	 * Print supplier proposal table for external access
	 *
	 * @param int $socId Third-party ID
	 * @return void
	 */
	private function printSupplierProposalTable($socId = 0)
	{
		global $langs, $db;

		$context = Context::getInstance();

		// Load language files
		$langs->load('supplier_proposal');
		$langs->load('fourn');

		// Get data
		$sql = getSupplierProposalExternalSql($db, $socId);
		$tableItems = $context->dbTool->executeS($sql);

		if (!empty($tableItems)) {
			// Get configured extra fields
			$TOther_fields = getSupplierProposalExtraFields();

			// Prepare ExtraFields object if needed
			$e = null;
			if (!empty($TOther_fields)) {
				$e = new ExtraFields($db);
			}

			// Start table
			print '<table id="supplier-propal-list" class="table table-striped" >';

			// Print header
			printSupplierProposalTableHeader($langs, $TOther_fields, $db);

			// Print body
			print '<tbody>';
			foreach ($tableItems as $item) {
				$object = createSupplierProposalFromItem($db, $item, $TOther_fields);
				printSupplierProposalTableRow($object, $context, $TOther_fields, $e);
			}
			print '</tbody>';
			print '</table>';

			// Include DataTable initialization
			includeSupplierProposalDataTableScript($context);

		} else {
			print '<div class="info clearboth text-center" >';
			print $langs->trans('EACCESS_Nothing');
			print '</div>';
		}
	}
}
