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
			print '<section id="section-supplierProposal"><div class="container">';
			self::printSupplierProposalTable($user->socid);
			print '</div></section>';
		}
		$this->loadTemplate('footer');
	}

	static public function printSupplierProposalTable($socId = 0)
	{
		global $langs, $db, $user, $conf, $hookmanager;
		$context = Context::getInstance();

		$propalFournStatic = new SupplierProposal($context->dbTool->db); // For static calls

		// Load the language file for supplier proposals
		$langs->load('supplier_proposal');
		$langs->load('fourn'); // Load supplier lang file for good measure

		// SQL query to fetch supplier proposals for the current third party
		// Select all needed fields to avoid using fetch() which calls getEntity()
		// Note: No entity filter to show all proposals across entities for this supplier
		$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.datec, sp.total_ht, sp.fk_statut, sp.entity ';
		$sql .= ' FROM `' . $db->prefix() . 'supplier_proposal` sp';
		$sql .= ' WHERE sp.fk_soc = ' . intval($socId);
		$sql .= ' AND sp.fk_statut = 1';
		$sql .= ' ORDER BY sp.datec DESC';

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

		if (!empty($tableItems)) {
			// --- Extrafields configuration ---
			// We only use generic extrafields, as no specific conf key was provided for supplier proposals
			$TOther_fields = explode(',', getDolGlobalString('EACCESS_LIST_ADDED_COLUMNS'));
			if (empty($TOther_fields)) $TOther_fields = array();

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
					// Check properties on SupplierProposal class
					if (property_exists('SupplierProposal', $field)) print ' <th class="text-center" >' . $langs->trans($field) . '</th>';
					elseif (strpos($field, 'EXTRAFIELD') !== false) {

						// Fetch extrafields for 'supplier_proposal' element
						if (empty($e->attributes)) $e->fetch_name_optionals_label('supplier_proposal');
						print ' <th class="text-center" >' . $e->attributes['supplier_proposal']['label'][strtr($field, array('EXTRAFIELD_' => ''))] . '</th>';
					}
				}
			}

			print ' <th class="text-center" >' . $langs->trans('TotalHT') . '</th>';
			print ' <th class="text-center" >' . $langs->trans('Status') . '</th>';
			print '</tr>';
			print '</thead>';
			print '<tbody>';

			foreach ($tableItems as $item) {
				// Create object instance for methods only (avoid fetch() with getEntity() issue)
				$object = new SupplierProposal($db);
				$object->id = $item->rowid;
				$object->ref = $item->ref;
				$object->ref_ext = $item->ref_ext;
				$object->datec = $item->datec;
				$object->total_ht = $item->total_ht;
				$object->statut = $item->fk_statut;
				$object->entity = $item->entity;

				// Only fetch extrafields if needed
				if (!empty($TOther_fields)) {
					// Fetch extrafields separately without full fetch
					$sql_extra = 'SELECT * FROM ' . $db->prefix() . 'supplier_proposal_extrafields';
					$sql_extra .= ' WHERE fk_object = ' . intval($item->rowid);
					$resql_extra = $db->query($sql_extra);
					if ($resql_extra) {
						$obj_extra = $db->fetch_object($resql_extra);
						if ($obj_extra) {
							foreach ($obj_extra as $key => $value) {
								if ($key != 'rowid' && $key != 'tms' && $key != 'fk_object' && $key != 'import_key') {
									$object->array_options['options_' . $key] = $value;
								}
							}
						}
						$db->free($resql_extra);
					}
				}

				print '<tr>';
				// Link to 'supplier_proposal_card' controller with the propal ID
				print ' <td data-search="' . $object->ref . '" data-order="' . $object->ref . '"  ><a href="' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $item->rowid) . '">' . $object->ref . '</a></td>';
				print ' <td data-search="' . $object->ref_ext . '" data-order="' . $object->ref_ext . '" >' . $object->ref_ext . '</td>';
				print ' <td data-search="' . dol_print_date($object->datec) . '" data-order="' . $object->datec . '" >' . dol_print_date($object->datec) . '</td>';


				if (!empty($TOther_fields)) {
					foreach ($TOther_fields as $field) {
						if (property_exists('SupplierProposal', $field)) {
							print ' <td data-search="' . strip_tags($object->{$field}) . '" data-order="' . strip_tags($object->{$field}) . '" >' . $object->{$field} . '</td>';
						} elseif (strpos($field, 'EXTRAFIELD') !== false) {
							$extrafield_name = strtr($field, array('EXTRAFIELD_' => ''));
							$extrafield_value = !empty($object->array_options['options_' . $extrafield_name]) ? $object->array_options['options_' . $extrafield_name] : '';
							// Print extrafield value for 'supplier_proposal'
							print ' <td data-search="'
								. strip_tags($e->showOutputField($extrafield_name, $extrafield_value, '', 'supplier_proposal')) . '" data-order="'
								. strip_tags($e->showOutputField($extrafield_name, $extrafield_value, '', 'supplier_proposal')) . '" >'
								. $e->showOutputField($extrafield_name, $extrafield_value, '', 'supplier_proposal') . '</td>';
						}
					}
				}

				print ' <td data-search="' . $object->total_ht . '" data-order="' . $object->total_ht . '" >' . price($object->total_ht) . '</td>';
				// Use the status function from the SupplierProposal class
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
