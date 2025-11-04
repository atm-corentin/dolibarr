<?php
include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';

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
//		return parent::checkAccess();
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
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return;
		}

		$context->title = $langs->trans('ViewSupplierProposal');
		$context->desc = $langs->trans('ViewSupplierProposalDesc');
		$context->menu_active[] = 'supplierProposal';

		$hookRes = $this->hookDoAction();

		if (empty($hookRes)) {

		}
	}


	/**
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

		$hookRes = $this->hookPrintPageView();
		if (empty($hookRes)) {
			print '<section id="section-ticket"><div class="container">';
			self::print_ticketTable($user->socid);
			print '</div></section>';
		}
		$this->loadTemplate('footer');
	}


	static public function print_ticketTable($socId = 0)
	{
		global $langs, $db, $user, $conf, $hookmanager;
		$context = Context::getInstance();

		$propalFournStatic = new SupplierProposal($context->dbTool->db); // For static calls

		// Load the language file for supplier proposals
		$langs->load('supplier_proposal');
		$langs->load('fourn'); // Load supplier lang file for good measure

		// SQL query to fetch supplier proposals for the current third party
		$sql = 'SELECT t.rowid ';
		$sql .= ' FROM `' . $db->prefix() . 'supplier_proposal` t'; // Use llx_propal_fourn table
		$sql .= ' WHERE t.fk_soc = ' . intval($socId);
		$sql .= ' ORDER BY t.datec DESC';

		$tableItems = $context->dbTool->executeS($sql);

		$supplierPropalMorePanelHeader = ''; // Renamed variable

		$parameters = array(
			'tableItems' => $tableItems,
			'supplierPropalMorePanelHeader' => &$supplierPropalMorePanelHeader // Renamed
		);

// Use a new hook name for supplier proposals
		$reshook = $hookmanager->executeHooks('externalAccessBeforeSupplierPropalList', $parameters, $object, $context->action);    // Note that $action and $object may have been modified by hook

		if (!empty($supplierPropalMorePanelHeader)) {
			print $supplierPropalMorePanelHeader;
		}

// "New" button has been removed

		if (!empty($tableItems)) {
			// --- Extrafields configuration ---
			// We only use generic extrafields, as no specific conf key was provided for supplier proposals
			$TOther_fields = explode(',', getDolGlobalString('EACCESS_LIST_ADDED_COLUMNS'));
			if (empty($TOther_fields)) $TOther_fields = array();

			// You might want to add a new conf key 'EACCESS_LIST_ADDED_COLUMNS_SUPPLIER_PROPAL'
			// in your setup and uncomment the lines below if you do
			// $TOther_fields_supp_propal = explode(',', getDolGlobalString('EACCESS_LIST_ADDED_COLUMNS_SUPPLIER_PROPAL'));
			// if(empty($TOther_fields_supp_propal)) $TOther_fields_supp_propal = array();
			// $TOther_fields = array_merge($TOther_fields, $TOther_fields_supp_propal);


			// Changed table ID to "supplier-propal-list"
			print '<table id="supplier-propal-list" class="table table-striped" >';
			print '<thead>';
			print '<tr>';
			print ' <th class="text-center" >' . $langs->trans('Ref') . '</th>';
			print ' <th class="text-center" >' . $langs->trans('RefSupplier') . '</th>';
			print ' <th class="text-center" >' . $langs->trans('DateCreation') . '</th>';

			if (!empty($TOther_fields)) {
				$e = new ExtraFields($db);
				foreach ($TOther_fields as $field) {
					// Check properties on PropalFourn class
					if (property_exists('PropalFourn', $field)) print ' <th class="text-center" >' . $langs->trans($field) . '</th>';
					elseif (strpos($field, 'EXTRAFIELD') !== false) {

						// Fetch extrafields for 'propal_fourn' element
						if (empty($e->attributes)) $e->fetch_name_optionals_label('propal_fourn');
						print ' <th class="text-center" >' . $e->attributes['propal_fourn']['label'][strtr($field, array('EXTRAFIELD_' => ''))] . '</th>';
					}
				}
			}

			print ' <th class="text-center" >' . $langs->trans('TotalHT') . '</th>';
			print ' <th class="text-center" >' . $langs->trans('Status') . '</th>';
			print '</tr>';
			print '</thead>';
			print '<tbody>';

			foreach ($tableItems as $item) {
				// Use the PropalFourn object
				$object = new SupplierProposal($db);
				$object->fetch($item->rowid);

				print '<tr>';
				// Link to 'supplier_proposal_card' controller with the propal ID
				print ' <td data-search="' . $object->ref . '" data-order="' . $object->ref . '"  ><a href="' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $item->rowid) . '">' . $object->ref . '</a></td>';
				print ' <td data-search="' . $object->ref_supplier . '" data-order="' . $object->ref_supplier . '" >' . $object->ref_supplier . '</td>';
				print ' <td data-search="' . dol_print_date($object->datec) . '" data-order="' . $object->datec . '" >' . dol_print_date($object->datec) . '</td>';


				if (!empty($TOther_fields)) {
					foreach ($TOther_fields as $field) {
						if (property_exists('PropalFourn', $field)) {
							print ' <td data-search="' . strip_tags($object->{$field}) . '" data-order="' . strip_tags($object->{$field}) . '" >' . $object->{$field} . '</td>';
						} elseif (strpos($field, 'EXTRAFIELD') !== false) {
							// Print extrafield value for 'propal_fourn'
							print ' <td data-search="'
								. strip_tags($e->showOutputField(strtr($field, array('EXTRAFIELD_' => '')), $object->array_options['options_' . strtr($field, array('EXTRAFIELD_' => ''))], '', 'propal_fourn')) . '" data-order="'
								. strip_tags($e->showOutputField(strtr($field, array('EXTRAFIELD_' => '')), $object->array_options['options_' . strtr($field, array('EXTRAFIELD_' => ''))], '', 'propal_fourn')) . '" >'
								. strip_tags($e->showOutputField(strtr($field, array('EXTRAFIELD_' => '')), $object->array_options['options_' . strtr($field, array('EXTRAFIELD_' => ''))], '', 'propal_fourn')) . '</td>';
						}
					}
				}

				print ' <td data-search="' . $object->total_ht . '" data-order="' . $object->total_ht . '" >' . price($object->total_ht) . '</td>';
				// Use the status function from the PropalFourn class
				print ' <td class="text-center" >' . $object->getLibStatut(0) . '</td>';
				print '</tr>';
			}
			print '</tbody>';
			print '</table>';
			?>
			<script type="text/javascript">
				$(document).ready(function () {
					// Target the new table ID
					$("#supplier-propal-list").DataTable({
						"language": {
							"url": "<?php print $context->getControllerUrl(); ?>vendor/data-tables/french.json"
						},
						'order': [[2, 'desc']], // Order by the 3rd column (DateCreation)

						responsive: true,
						columnDefs: [{
							orderable: false,
							"aTargets": [-1] // Disable ordering on the last column (Status)
						}, {
							"bSearchable": false,
							"aTargets": [-2, -1] // Disable search on TotalHT and Status
						}]
					});
				});
			</script>
			<?php
		} else {
			print '<div class="info clearboth text-center" >';
			print  $langs->trans('EACCESS_Nothing');
			print '</div>';
		}
	}
}
