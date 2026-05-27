<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modClichaumeil_ClichaumeilTriggers.class.php
 * \ingroup clichaumeil
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modClichaumeil_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
require_once __DIR__ . '/../../class/chaumeilrfa.class.php';
require_once __DIR__ . '/../../class/CliChaumeilProductCost.class.php';
require_once __DIR__ . '/../../class/CliChaumeilProposalMarginGuard.class.php';
require_once __DIR__ . '/../../class/CliChaumeilPropalDefaultLineConfig.class.php';
require_once __DIR__ . '/../../class/CliChaumeilPropalDefaultLineService.class.php';
require_once __DIR__ . '/../../class/CliChaumeilCommissionConfig.class.php';
require_once __DIR__ . '/../../lib/clichaumeil.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once __DIR__ . '/../../class/Subcontracting/CliChaumeilSubcontractorSelectionWorkflow.class.php';
require_once __DIR__ . '/../../class/Subcontracting/CliChaumeilSupplierProposalGuard.class.php';



/**
 *  Class of triggers for Clichaumeil module
 */
class InterfaceClichaumeilTriggers extends DolibarrTriggers
{

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "demo";
		$this->description = "Clichaumeil triggers.";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'clichaumeil@clichaumeil';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('clichaumeil')) {
			return 0; // If module is not enabled, we do nothing
		}

		$langs->load('clichaumeil@clichaumeil');

		$handled = false;
		$result = $this->handleProductCostSynchronization($action, $object, $user, $langs, $handled);
		if ($handled) {
			return $result;
		}

		$handled = false;
		$result = $this->handleThirdpartyCategoryManagement($action, $object, $user, $langs, $handled);
		if ($handled) {
			return $result;
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)

		// Or you can execute some code here
		switch ($action) {  // @phan-suppress-current-line PhanNoopSwitchCases
			case 'PROPOSAL_SUPPLIER_SENTBYMAIL':
				$this->copySupplierProposalMailAttachmentsToAgenda($object, $conf);
				break;

			case 'LINEORDER_INSERT':
			case 'LINEORDER_MODIFY':
			case 'LINEPROPAL_INSERT':
			case 'LINEPROPAL_MODIFY':
				if ($action === 'LINEPROPAL_MODIFY') {
					$guardResult = $this->guardProtectedPropalLineModification($object, $user, $langs);
					if ($guardResult < 0) {
						return -1;
					}
				}

				//Clean fields
				$height = 0;
				$length = 0;
				if (!empty($object->array_options["options_clichaumeil_height"]) && !empty($object->array_options["options_clichaumeil_length"])) {
					$height = abs(price2num($object->array_options["options_clichaumeil_height"]));
					$length = abs(price2num($object->array_options["options_clichaumeil_length"]));
					$object->array_options["options_clichaumeil_height"] = $height;
					$object->array_options["options_clichaumeil_length"] = $length;
				}


				if ($height > 0 && $length > 0) {
					// Get rowid from c_units dictionary for the 'CM2' code
					if (!empty($object->array_options["options_clichaumeil_units"])) {
						$object->fk_unit = (int) $object->array_options["options_clichaumeil_units"];
						$targetUnit = $object->fk_unit;
						$baseUnit = (int) dol_getIdFromCode($this->db, 'CM2', 'c_units', 'code', 'rowid');
					} else {
						$object->fk_unit = (int) dol_getIdFromCode($this->db, 'CM2', 'c_units', 'code', 'rowid');
						$baseUnit = $object->fk_unit;
					}

					//get unit
					$unit = new CUnits($this->db);
					$res = $unit->fetch($object->fk_unit);
					if ($res > 0 && !empty($unit->short_label)) {
						$shortLabelUnit = $unit->short_label;
					} else {
						$shortLabelUnit = $unit->label;
					}

					if ($object->fk_unit <= 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
						return -1;
					}
					$object->qty = (float) $height * (float) $length;

					if (empty($targetUnit)) {
						$targetUnit = $baseUnit;
					}
					$converted = $unit->unitConverter($object->qty, $baseUnit, $targetUnit);
					$object->qty = $converted;

					setEventMessages($langs->trans('CliChaumeilSurfaceRecalculated', $shortLabelUnit), null, 'mesgs');
				}
				//For escape infinity loop ! use notriggers 1 !
				$result = $object->update($user, 1);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
					return -1;
				}

			break;
			case 'LINEPROPAL_DELETE':
				return $this->guardProtectedPropalLineDeletion($object, $user, $langs);

			case 'ORDER_VALIDATE':
				return $this->handleOrderValidate($object, $langs);

			case 'PROPAL_VALIDATE':
				return $this->handleProposalValidate($object, $langs);

			case 'PROPAL_CREATE':
				return $this->handlePropalCreateDefaultLines($object, $user);

			case 'externalAccessInitController':
				externalAccessInitController($object, $user, $langs, $conf);
				break;

			case 'PROPOSAL_SUPPLIER_CREATE':
				// Set default supplier status extrafield when the supplier proposal originates from a customer proposal
				if (get_class($object) === 'SupplierProposal' || $object instanceof SupplierProposal) {
					$origin = !empty($object->origin) ? $object->origin : (isset($object->origin_type) ? $object->origin_type : '');
					if (empty($origin) && !empty($object->linkedObjectsIds) && !empty($object->linkedObjectsIds['propal'])) {
						$origin = 'propal';
					}
					if ($origin === 'propal' || $origin === 'commande') {
						if (!is_array($object->array_options)) {
							$object->array_options = array();
						}

						$key = 'options_clichaumeil_supplierstatut';

						if (empty($object->array_options[$key])) {
							$object->array_options[$key] = 'CLICHAUMEIL_PENDING_FILE';
							if (method_exists($object, 'insertExtraFields')) {
								$save = $object->insertExtraFields();
								if ($save < 0) {
									setEventMessages($object->error, $object->errors, 'errors');
									return -1;
								}
							}
						}
					}

					if (!empty($user->id)) {
						$add = $object->add_contact($user->id, 'SALESREPFOLL', 'internal', 1);
						if ($add < 0 && $add != -2) {
							setEventMessages($object->error, $object->errors, 'errors');
							dol_syslog(__METHOD__ . ' ' . $object->error, LOG_ERR);
							return -1;
						}
					}
				}
				break;

			case 'PROPOSAL_SUPPLIER_SIGN':
				return $this->handleSupplierProposalSign($object, $user, $langs, $conf);

			default:
				dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}

		return 0;
	}

	/**
	 * Inject configured protected lines on standard proposal creation.
	 *
	 * @param CommonObject $object Proposal object.
	 * @param User         $user   Current user.
	 * @return int
	 */
	private function handlePropalCreateDefaultLines(CommonObject $object, User $user): int
	{
		if (!$object instanceof Propal) {
			return 0;
		}

		if (!empty($object->context['createfromclone'])) {
			return 0;
		}

		$service = new CliChaumeilPropalDefaultLineService(
			$this->db,
			new CliChaumeilPropalDefaultLineConfig($this->db)
		);
		if (!$service->shouldInjectOnCreate($object)) {
			return 0;
		}

		$service->injectConfiguredLines($object, $user);

		return 0;
	}

	/**
	 * Block unauthorized modification of protected default proposal lines.
	 *
	 * @param CommonObject $object Current trigger line object.
	 * @param User         $user   Current user.
	 * @param Translate    $langs  Translation helper.
	 * @return int
	 */
	private function guardProtectedPropalLineModification(CommonObject $object, User $user, Translate $langs): int
	{
		if (!$object instanceof PropaleLigne) {
			return 0;
		}

		$service = new CliChaumeilPropalDefaultLineService(
			$this->db,
			new CliChaumeilPropalDefaultLineConfig($this->db)
		);
		if ($service->guardEditLineObject($object, $user, false)) {
			return 0;
		}

		$object->error = $langs->trans('CLICHAUMEIL_DEFAULT_PROPAL_LINE_EDIT_FORBIDDEN', (string) $object->id);
		$object->errors[] = $object->error;

		return -1;
	}

	/**
	 * Block unauthorized deletion of protected default proposal lines.
	 *
	 * @param CommonObject $object Current trigger line object.
	 * @param User         $user   Current user.
	 * @param Translate    $langs  Translation helper.
	 * @return int
	 */
	private function guardProtectedPropalLineDeletion(CommonObject $object, User $user, Translate $langs): int
	{
		if (!$object instanceof PropaleLigne) {
			return 0;
		}

		$service = new CliChaumeilPropalDefaultLineService(
			$this->db,
			new CliChaumeilPropalDefaultLineConfig($this->db)
		);
		if ($service->guardDeleteLineObject($object, $user, false)) {
			return 0;
		}

		$object->error = $langs->trans('CLICHAUMEIL_DEFAULT_PROPAL_LINE_DELETE_FORBIDDEN', (string) $object->id);
		$object->errors[] = $object->error;

		return -1;
	}

	/**
	 * Validate order-specific thirdparty requirements.
	 *
	 * @param CommonObject $object Order-like object.
	 * @param Translate    $langs  Translation handler.
	 * @return int
	 */
	private function handleOrderValidate(CommonObject $object, Translate $langs): int
	{
		if (empty($object->thirdparty)) {
			$fetchThirdpartyResult = $object->fetch_thirdparty();
			if ($fetchThirdpartyResult <= 0) {
				$message = !empty($object->error) ? $object->error : 'Failed to load thirdparty during ORDER_VALIDATE.';
				dol_syslog(__METHOD__ . ' - ' . $message, LOG_ERR);
				setEventMessages($langs->trans('Error'), null, 'errors');
				return -1;
			}
		}

		$customerRefRequired = !empty($object->thirdparty->array_options['options_clichaumeil_ref_required']) ? (int) $object->thirdparty->array_options['options_clichaumeil_ref_required'] : 0;
		$customerRefCommande = isset($object->ref_client) ? (string) $object->ref_client : '';

		if ($customerRefRequired === 1 && $customerRefCommande === '') {
			$message = $langs->trans('CliChaumeilCustomerRefRequired', $object->getNomUrl());
			dol_syslog(__METHOD__ . ' - ' . $message . ' order_id=' . ((int) $object->id), LOG_WARNING);
			setEventMessages($message, null, 'errors');
			return -1;
		}

		return 0;
	}

	/**
	 * Block proposal validation when at least one line has a negative margin.
	 *
	 * @param CommonObject $object Proposal object.
	 * @param Translate    $langs  Translation handler.
	 * @return int
	 */
	private function handleProposalValidate(CommonObject $object, Translate $langs): int
	{
		if (!$object instanceof Propal) {
			return 0;
		}

		$guard = new CliChaumeilProposalMarginGuard();

		try {
			$blockingLineIds = $guard->getBlockingLineIds($object);
		} catch (RuntimeException $exception) {
			dol_syslog(__METHOD__ . ' - ' . $exception->getMessage() . ' proposal_id=' . ((int) $object->id), LOG_ERR);
			setEventMessages($langs->trans('CliChaumeil_PropalMarginValidationUnexpectedError'), null, 'errors');
			return -1;
		}

		if (empty($blockingLineIds)) {
			return 0;
		}

		$message = $guard->getCardBlockingMessage($langs);
		dol_syslog(__METHOD__ . ' - ' . $message . ' proposal_id=' . ((int) $object->id) . ' line_ids=' . implode(',', $blockingLineIds), LOG_WARNING);
		setEventMessages($message, null, 'errors');
		return -1;
	}

	/**
	 * Trigger the ST-8 subcontractor selection workflow when a supplier proposal is signed.
	 *
	 * Called for PROPOSAL_SUPPLIER_SIGN. Applies only to supplier proposals linked to a customer
	 * propal or commande (subcontracting context). Silently ignores all other cases.
	 * Never returns < 0 so it never blocks Dolibarr's own status transition.
	 *
	 * @param CommonObject $object Signed supplier proposal.
	 * @param User         $user   Current user.
	 * @param Translate    $langs  Translation handler.
	 * @param Conf         $conf   Application configuration.
	 * @return int Always 0.
	 */
	private function handleSupplierProposalSign(CommonObject $object, User $user, Translate $langs, Conf $conf): int
	{
		if (!($object instanceof SupplierProposal)) {
			return 0;
		}

		$parent = $this->resolveSupplierProposalParent($object);
		if ($parent === null) {
			dol_syslog(__METHOD__.' supplier_proposal #'.((int) $object->id).' has no linked propal/commande — skipping ST-8 workflow', LOG_DEBUG);
			return 0;
		}

		// Reload with fresh data: cloture() may have altered in-memory state.
		$freshProposal = new SupplierProposal($this->db);
		if ($freshProposal->fetch((int) $object->id) <= 0) {
			dol_syslog(__METHOD__.' failed to reload supplier_proposal #'.((int) $object->id), LOG_WARNING);
			return 0;
		}
		$freshProposal->fetch_optionals();

		$guard = new CliChaumeilSupplierProposalGuard();
		if ($guard->isSupplierProposalProcessed($freshProposal)) {
			dol_syslog(__METHOD__.' supplier_proposal #'.((int) $object->id).' already processed — skipping', LOG_DEBUG);
			return 0;
		}

		$workflow = new CliChaumeilSubcontractorSelectionWorkflow($this->db, $conf, $langs);
		$result   = $workflow->execute($parent, (int) $object->id, $user);

		// [] means execute() was skipped by the re-entrancy guard (button flow in progress) — silent skip.
		if ($result === []) {
			dol_syslog(__METHOD__.' re-entrancy guard returned [] — workflow handled by button flow', LOG_DEBUG);
			return 0;
		}

		$status = $result['status'] ?? '';
		if ($status !== CliChaumeilSupplierOrderConfig::RESULT_SUCCESS) {
			$logMsg = __METHOD__.' ST-8 workflow non-success supplier_proposal_id='.((int) $object->id).' status='.$status.' message='.($result['message'] ?? '');
			dol_syslog($logMsg, LOG_WARNING);
		}

		// Never block the Dolibarr status transition.
		return 0;
	}

	/**
	 * Resolve the parent commercial document (propal or commande) of a supplier proposal.
	 *
	 * Searches bidirectionally in llx_element_element: first where the supplier proposal
	 * is the target (typical creation-from-propal case), then where it is the source.
	 *
	 * @param SupplierProposal $object Supplier proposal to inspect.
	 * @return CommonObject|null Propal or Commande if found, null otherwise.
	 */
	private function resolveSupplierProposalParent(SupplierProposal $object): ?CommonObject
	{
		// Direction 1: supplier_proposal is the TARGET — the parent is the SOURCE.
		if (method_exists($object, 'clearObjectLinkedCache')) {
			$object->clearObjectLinkedCache();
		}
		$object->fetchObjectLinked('', '', (int) $object->id, $object->element, 'OR', 1, 'sourcetype', 1);

		if (!empty($object->linkedObjects['propal']) && is_array($object->linkedObjects['propal'])) {
			return reset($object->linkedObjects['propal']);
		}
		if (!empty($object->linkedObjects['commande']) && is_array($object->linkedObjects['commande'])) {
			return reset($object->linkedObjects['commande']);
		}

		// Direction 2: supplier_proposal is the SOURCE — the parent is the TARGET.
		if (method_exists($object, 'clearObjectLinkedCache')) {
			$object->clearObjectLinkedCache();
		}
		$object->fetchObjectLinked((int) $object->id, $object->element, '', '');

		if (!empty($object->linkedObjects['propal']) && is_array($object->linkedObjects['propal'])) {
			return reset($object->linkedObjects['propal']);
		}
		if (!empty($object->linkedObjects['commande']) && is_array($object->linkedObjects['commande'])) {
			return reset($object->linkedObjects['commande']);
		}

		return null;
	}

	/**
	 * Copy attached files from supplier proposal emails into agenda event folder.
	 *
	 * @param CommonObject $object Current supplier proposal object.
	 * @param Conf         $conf   Global configuration object.
	 * @return void
	 */
	private function copySupplierProposalMailAttachmentsToAgenda(CommonObject $object, Conf $conf) : void
	{
		if (empty($object->id) || empty($object->element)) {
			return;
		}

		if ($object->element !== 'supplier_proposal') {
			return;
		}

		if (empty($_SESSION['LAST_ACTION_CREATED'])) {
			return;
		}

		if (empty($object->attachedfiles) || !is_array($object->attachedfiles)) {
			return;
		}

		$actionId = (int) $_SESSION['LAST_ACTION_CREATED'];
		if ($actionId <= 0) {
			return;
		}

		$paths = $object->attachedfiles['paths'] ?? array();
		$names = $object->attachedfiles['names'] ?? array();
		if (empty($paths) || empty($names)) {
			return;
		}

		$agendaRoot = $conf->agenda->multidir_output[$conf->entity] ?? '';
		if (empty($agendaRoot)) {
			return;
		}

		$destDir = $agendaRoot . '/' . $actionId;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		if (dol_mkdir($destDir) < 0) {
			return;
		}

		foreach ($paths as $idx => $srcfile) {
			if (empty($srcfile) || !is_file($srcfile)) {
				continue;
			}

			$filename = $names[$idx] ?? basename($srcfile);
			$filename = dol_sanitizeFileName($filename);
			if (empty($filename)) {
				continue;
			}

			$destfile = $destDir . '/' . $filename;
			if (is_file($destfile)) {
				continue;
			}

			dol_copy($srcfile, $destfile, 0, 0, 1);
		}
	}

	/**
	 * Handle thirdparty category changes and apply default category on creation.
	 *
	 * @param string       $action Event action code
	 * @param CommonObject $object Object being processed
	 * @param User         $user   User performing the action
	 * @param Translate    $langs  Translation object
	 * @param bool         $handled Output parameter set to true if action was handled
	 * @return int Return integer <0 if KO, 0 if OK or not handled
	 */
	private function handleThirdpartyCategoryManagement($action, $object, User $user, Translate $langs, &$handled = false)
	{
		if ($action === 'COMPANY_CREATE') {
			$handled = true;

			if (!($object instanceof Societe)) {
				return 0;
			}

			$categoryId = getDolGlobalInt(CliChaumeilCommissionConfig::CAT_NOUVEAU);
			if (empty($categoryId)) {
				return 0;
			}

			$category = new Categorie($this->db);
			if ($category->fetch($categoryId) <= 0) {
				return 0;
			}

			if ($category->containsObject('customer', $object->id) > 0) {
				return 0;
			}

			$result = $category->add_type($object, 'customer');
			if ($result < 0) {
				$this->error = $category->error;
				$this->errors = $category->errors;
				return -1;
			}

			return 1;
		}

		if ($action !== 'CATEGORY_MODIFY') {
			$handled = false;
			return 0;
		}

		$handled = true;
		// Category change events are logged only by the cron job (single event per tier).
		return 0;
	}
	/**
	 * Handle product cost synchronization when a product is saved.
	 *
	 * This method checks if the action is a product-related event and if so,
	 * calculates and updates the cost price from extrafields.
	 *
	 * @param string       $action  Event action code.
	 * @param CommonObject $object  Object being processed.
	 * @param User         $user    User performing the action.
	 * @param Translate    $langs   Translation object.
	 * @param bool         $handled Output parameter set to true if action was handled.
	 * @return int         Return integer <0 if KO, 0 if OK or not handled
	 */
	private function handleProductCostSynchronization($action, $object, User $user, Translate $langs, &$handled = false)
	{
		$handledActions = array('PRODUCT_CREATE', 'PRODUCT_MODIFY', 'PRODUCT_PRICE_MODIFY');
		if (!in_array($action, $handledActions, true)) {
			$handled = false;
			return 0;
		}

		$handled = true;

		if (!($object instanceof Product) || !CliChaumeilProductCostCalculator::isSupportedProduct($object)) {
			return 0;
		}

		if ($action === 'PRODUCT_CREATE') {
			$defaultApplied = $this->applyDefaultOverheadRateIfMissing($object, $user);
			if ($defaultApplied < 0) {
				return -1;
			}
		}

		$result = CliChaumeilProductCostCalculator::calculateAndUpdateProductCostPriceFromExtrafields($object, $user);
		if ($result < 0) {
			$this->errors[] = $langs->trans('Error');
			return -1;
		}

		return 0;
	}

	/**
	 * Apply module default overhead rate on product creation when missing.
	 *
	 * @param Product $product Product being created.
	 * @param User    $user    Current user.
	 * @return int
	 */
	private function applyDefaultOverheadRateIfMissing(Product $product, User $user): int
	{
		if (empty($product->id)) {
			return 0;
		}

		$product->fetch_optionals($product->id);
		$key = 'options_clichaumeil_fg_percent';
		$currentValue = $product->array_options[$key] ?? null;
		if ($currentValue !== null && $currentValue !== '') {
			return 0;
		}

		$product->array_options[$key] = CliChaumeilProductCostCalculator::getDefaultOverheadRate();
		$result = $product->updateExtraField('clichaumeil_fg_percent', 'CLICHAUMEIL_PRODUCT_COST', $user);
		if ($result < 0) {
			dol_syslog(__METHOD__ . ' failed to update default overhead rate for product #' . (int) $product->id, LOG_ERR);
		}

		return ($result < 0) ? -1 : 1;
	}
}
