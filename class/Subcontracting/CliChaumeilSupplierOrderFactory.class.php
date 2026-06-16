<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderConfig.class.php';

/**
 * Build and validate one supplier order from one supplier proposal.
 */
class CliChaumeilSupplierOrderFactory
{
	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create and validate one supplier order from one supplier proposal.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal to convert.
	 * @param User             $user             Current user.
	 * @return CommandeFournisseur
	 */
	public function createValidatedOrderFromProposal(SupplierProposal $supplierProposal, User $user): CommandeFournisseur
	{
		$this->hydrateSupplierProposal($supplierProposal);

		$supplierOrder = new CommandeFournisseur($this->db);
		$this->mapHeaderFromProposal($supplierOrder, $supplierProposal);

		$supplierOrderId = $supplierOrder->create($user);
		if ($supplierOrderId <= 0) {
			throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to create supplier order.'));
		}

		$supplierOrder = $this->reloadSupplierOrder($supplierOrderId);
		$this->synchronizePostCreateMetadata($supplierOrder, $supplierProposal, $user);
		$this->copyProposalLinesToOrder($supplierOrder, $supplierProposal);
		$this->executeCreateFromHooks($supplierOrder, $supplierProposal);
		$this->attachSupplierFollowUpContacts($supplierOrder, $supplierProposal->thirdparty);

		$validationResult = $supplierOrder->valid($user);
		if ($validationResult <= 0) {
			throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to validate supplier order.'));
		}

		return $this->reloadSupplierOrder($supplierOrderId);
	}

	/**
	 * Load all source data required by the conversion.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal to hydrate.
	 * @return void
	 */
	private function hydrateSupplierProposal(SupplierProposal $supplierProposal): void
	{
		if (empty($supplierProposal->id)) {
			throw new RuntimeException('Supplier proposal id is missing.');
		}

		if (empty($supplierProposal->ref)) {
			$result = $supplierProposal->fetch((int) $supplierProposal->id);
			if ($result <= 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($supplierProposal, 'Unable to fetch supplier proposal.'));
			}
		}

		if (empty($supplierProposal->lines) && method_exists($supplierProposal, 'fetch_lines')) {
			$supplierProposal->fetch_lines();
		}

		$supplierProposal->fetch_optionals();

		$result = $supplierProposal->fetch_thirdparty();
		if ($result <= 0 || empty($supplierProposal->thirdparty) || empty($supplierProposal->thirdparty->id)) {
			throw new RuntimeException('Unable to fetch supplier thirdparty from supplier proposal.');
		}
	}

	/**
	 * Map the supplier proposal header onto the supplier order header.
	 *
	 * @param CommandeFournisseur $supplierOrder    Target supplier order.
	 * @param SupplierProposal    $supplierProposal Source supplier proposal.
	 * @return void
	 */
	private function mapHeaderFromProposal(CommandeFournisseur $supplierOrder, SupplierProposal $supplierProposal): void
	{
		$supplier = $supplierProposal->thirdparty;

		$supplierOrder->ref_supplier = (string) ($supplierProposal->ref_supplier ?? '');
		$supplierOrder->socid = (int) $supplierProposal->socid;
		$supplierOrder->cond_reglement_id = !empty($supplierProposal->cond_reglement_id) ? (int) $supplierProposal->cond_reglement_id : (int) ($supplier->cond_reglement_id ?? 0);
		$supplierOrder->mode_reglement_id = !empty($supplierProposal->mode_reglement_id) ? (int) $supplierProposal->mode_reglement_id : (int) ($supplier->mode_reglement_id ?? 0);
		$supplierOrder->fk_account = !empty($supplierProposal->fk_account) ? (int) $supplierProposal->fk_account : (int) ($supplier->fk_account ?? 0);
		$supplierOrder->note_private = (string) ($supplierProposal->note_private ?? '');
		$supplierOrder->note_public = (string) ($supplierProposal->note_public ?? '');
		$supplierOrder->delivery_date = $supplierProposal->delivery_date ?? null;
		$supplierOrder->fk_project = !empty($supplierProposal->fk_project) ? (int) $supplierProposal->fk_project : 0;
		$supplierOrder->origin = 'supplier_proposal';
		$supplierOrder->origin_id = (int) $supplierProposal->id;
		$supplierOrder->linked_objects = array('supplier_proposal' => (int) $supplierProposal->id);

		if (!empty($supplierProposal->multicurrency_code)) {
			$supplierOrder->multicurrency_code = (string) $supplierProposal->multicurrency_code;
		}
		if (isset($supplierProposal->multicurrency_tx)) {
			$supplierOrder->multicurrency_tx = (float) $supplierProposal->multicurrency_tx;
		}
		if (is_array($supplierProposal->array_options) && !empty($supplierProposal->array_options)) {
			$supplierOrder->array_options = $supplierProposal->array_options;
		}
	}

	/**
	 * Copy all eligible supplier proposal lines onto the supplier order.
	 *
	 * @param CommandeFournisseur $supplierOrder    Target supplier order.
	 * @param SupplierProposal    $supplierProposal Source supplier proposal.
	 * @return void
	 */
	private function copyProposalLinesToOrder(CommandeFournisseur $supplierOrder, SupplierProposal $supplierProposal): void
	{
		foreach ($supplierProposal->lines as $line) {
			if ($this->mustSkipSourceLine($line)) {
				continue;
			}

			$label = !empty($line->label) ? (string) $line->label : '';
			$description = !empty($line->desc) ? (string) $line->desc : '';
			$productType = !empty($line->product_type) ? (int) $line->product_type : 0;
			$refSupplier = !empty($line->ref_fourn) ? (string) $line->ref_fourn : (string) ($line->ref_supplier ?? '');
			$productFournPriceId = 0;
			$arrayOptions = array();
			if (method_exists($line, 'fetch_optionals')) {
				$line->fetch_optionals();
				if (is_array($line->array_options)) {
					$arrayOptions = $line->array_options;
				}
			}

			$lineId = !empty($line->id) ? (int) $line->id : (int) ($line->rowid ?? 0);

			// Supplier order lines do not expose fk_parent_line in addline(), so we align on the core payload we can persist.
			$addLineResult = $supplierOrder->addline(
				$description,
				(float) ($line->subprice ?? 0),
				(float) ($line->qty ?? 0),
				(float) ($line->tva_tx ?? 0),
				(float) ($line->localtax1_tx ?? 0),
				(float) ($line->localtax2_tx ?? 0),
				!empty($line->fk_product) ? (int) $line->fk_product : 0,
				$productFournPriceId,
				$refSupplier,
				(float) ($line->remise_percent ?? 0),
				'HT',
				0.0,
				$productType,
				0,
				0,
				null,
				null,
				$arrayOptions,
				!empty($line->fk_unit) ? (int) $line->fk_unit : null,
				(float) ($line->multicurrency_subprice ?? 0),
				(string) $supplierProposal->element,
				$lineId,
				-1,
				(int) ($line->special_code ?? 0),
				$label
			);

			if ($addLineResult < 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to copy supplier proposal line to supplier order.'));
			}
		}
	}

	/**
	 * Attach all supplier follow-up contacts to the supplier order.
	 *
	 * @param CommandeFournisseur $supplierOrder Target supplier order.
	 * @param Societe             $supplier      Supplier thirdparty.
	 * @return void
	 */
	private function attachSupplierFollowUpContacts(CommandeFournisseur $supplierOrder, Societe $supplier): void
	{
		$supplierContacts = $supplier->getContacts(0, CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE, 'order_supplier');
		if (!is_array($supplierContacts) && $supplierContacts !== -1) {
			throw new RuntimeException('Unexpected supplier contact payload while attaching follow-up contacts.');
		}
		if ($supplierContacts === -1) {
			throw new RuntimeException('Unable to load supplier follow-up contacts.');
		}

		foreach ($supplierContacts as $supplierContact) {
			$contactId = !empty($supplierContact['id']) ? (int) $supplierContact['id'] : 0;
			if ($contactId <= 0) {
				continue;
			}

			$result = $supplierOrder->add_contact(
				$contactId,
				CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE,
				CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_SOURCE,
				1
			);
			if ($result < 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to attach supplier follow-up contact to supplier order.'));
			}
		}
	}

	/**
	 * Reload one supplier order with its supplier and its lines.
	 *
	 * @param int $supplierOrderId Supplier order id.
	 * @return CommandeFournisseur
	 */
	private function reloadSupplierOrder(int $supplierOrderId): CommandeFournisseur
	{
		$supplierOrder = new CommandeFournisseur($this->db);
		$result = $supplierOrder->fetch($supplierOrderId);
		if ($result <= 0) {
			throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to reload supplier order.'));
		}

		$supplierOrder->fetch_thirdparty();
		if (empty($supplierOrder->lines) && method_exists($supplierOrder, 'fetch_lines')) {
			$supplierOrder->fetch_lines();
		}

		return $supplierOrder;
	}

	/**
	 * Synchronize metadata the same way as the standard supplier order card flow does.
	 *
	 * @param CommandeFournisseur $supplierOrder    Target supplier order.
	 * @param SupplierProposal    $supplierProposal Source supplier proposal.
	 * @param User                $user             Current user.
	 * @return void
	 */
	private function synchronizePostCreateMetadata(CommandeFournisseur $supplierOrder, SupplierProposal $supplierProposal, User $user): void
	{
		if (!empty($supplierProposal->delivery_date) && (int) $supplierOrder->delivery_date !== (int) $supplierProposal->delivery_date) {
			$result = $supplierOrder->setDeliveryDate($user, $supplierProposal->delivery_date);
			if ($result <= 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to synchronize supplier order delivery date.'));
			}
		}

		$sourceProjectId = !empty($supplierProposal->fk_project) ? (int) $supplierProposal->fk_project : 0;
		$currentProjectId = !empty($supplierOrder->fk_project) ? (int) $supplierOrder->fk_project : (int) ($supplierOrder->fk_projet ?? 0);
		if ($sourceProjectId > 0 && $currentProjectId !== $sourceProjectId) {
			$result = $supplierOrder->set_id_projet($user, $sourceProjectId);
			if ($result <= 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($supplierOrder, 'Unable to synchronize supplier order project.'));
			}
		}
	}

	/**
	 * Execute createFrom hooks to preserve third-party module side effects.
	 *
	 * @param CommandeFournisseur $supplierOrder    Target supplier order.
	 * @param SupplierProposal    $supplierProposal Source supplier proposal.
	 * @return void
	 */
	private function executeCreateFromHooks(CommandeFournisseur $supplierOrder, SupplierProposal $supplierProposal): void
	{
		$hookmanager = new HookManager($this->db);
		$hookmanager->initHooks(array('ordersuppliercard', 'globalcard'));
		$action = 'create';
		$parameters = array('objFrom' => $supplierProposal);
		$hookResult = $hookmanager->executeHooks('createFrom', $parameters, $supplierOrder, $action);
		if ($hookResult < 0) {
			throw new RuntimeException($this->buildHookErrorMessage($hookmanager, 'Unable to execute supplier order createFrom hooks.'));
		}
	}

	/**
	 * Tell whether one source line must be ignored during the conversion.
	 *
	 * @param object $line Source line.
	 * @return bool
	 */
	private function mustSkipSourceLine(object $line): bool
	{
		if (!isset($line->subprice) || $line->subprice === '' || $line->subprice === null) {
			return true;
		}
		if ((float) ($line->qty ?? 0) < 0) {
			return true;
		}
		if (defined('SUBTOTALS_SPECIAL_CODE') && isset($line->special_code) && (int) $line->special_code === (int) SUBTOTALS_SPECIAL_CODE) {
			return true;
		}

		return false;
	}

	/**
	 * Build a normalized error message from one Dolibarr business object.
	 *
	 * @param CommonObject $object        Business object.
	 * @param string       $fallbackError Fallback error.
	 * @return string
	 */
	private function buildObjectErrorMessage(CommonObject $object, string $fallbackError): string
	{
		if (!empty($object->error)) {
			return (string) $object->error;
		}
		if (!empty($object->errors) && is_array($object->errors)) {
			return implode(' | ', $object->errors);
		}
		if ($this->db->lasterror() !== '') {
			return $this->db->lasterror();
		}

		return $fallbackError;
	}

	/**
	 * Build a normalized error message from one hook manager execution.
	 *
	 * @param HookManager $hookmanager   Hook manager.
	 * @param string      $fallbackError Fallback error.
	 * @return string
	 */
	private function buildHookErrorMessage(HookManager $hookmanager, string $fallbackError): string
	{
		if (!empty($hookmanager->error)) {
			return (string) $hookmanager->error;
		}
		if (!empty($hookmanager->errors) && is_array($hookmanager->errors)) {
			return implode(' | ', $hookmanager->errors);
		}
		if ($this->db->lasterror() !== '') {
			return $this->db->lasterror();
		}

		return $fallbackError;
	}
}
