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
require_once __DIR__ . '/../../class/CliChaumeilCommissionConfig.class.php';
require_once __DIR__ . '/../../lib/clichaumeil.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';



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
					}else{
						$object->fk_unit = (int) dol_getIdFromCode($this->db, 'CM2', 'c_units', 'code', 'rowid');
						$baseUnit = $object->fk_unit;
					}

					//get unit
					$unit = new CUnits($this->db);
					$res = $unit->fetch($object->fk_unit);
					if ($res > 0 && !empty($unit->short_label)) {
						$shortLabelUnit = $unit->short_label;
					}else{
						$shortLabelUnit = $unit->label;
					}

					if ($object->fk_unit <= 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
						return -1;
					}
					$object->qty = (float) $height * (float) $length;

					if (empty($targetUnit)){
						$targetUnit = $baseUnit;
					}
					$converted = $unit->unitConverter($object->qty, $baseUnit, $targetUnit);
					$object->qty = $converted;

					setEventMessages($langs->trans('CliChaumeilSurfaceRecalculated',$shortLabelUnit), null, 'mesgs');

				}
				//For escape infinity loop ! use notriggers 1 !
				$result = $object->update($user, 1);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
					return -1;
				}

			break;
			case 'ORDER_VALIDATE':

				//Check for massaction
				if (empty($object->thirdparty)) {
					$object->fetch_thirdparty();
				}

				//Check extrafield(Thirdparty) ref_required & field object->ref_client(Commande)
				$customerRefRequired = $object->thirdparty->array_options['options_clichaumeil_ref_required'];
				$customerRefCommande = $object->ref_client;

				if ($customerRefRequired == 1 && empty($customerRefCommande)) {
					setEventMessages($langs->trans('CliChaumeilCustomerRefRequired', $object->getNomUrl()), null, 'errors');
					return -1;
				}

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

			default:
				dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}

		return 0;
	}

	/**
	 * Copy attached files from supplier proposal emails into agenda event folder.
	 *
	 * @param CommonObject $object
	 * @param Conf $conf
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
	 * @param string       $action Event action code
	 * @param CommonObject $object Object being processed
	 * @param User         $user   User performing the action
	 * @param Translate    $langs  Translation object
	 * @param bool         $handled Output parameter set to true if action was handled
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
	 * @param Product $product
	 * @param User    $user
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

		return ($result < 0) ? -1 : 1;
	}
}
