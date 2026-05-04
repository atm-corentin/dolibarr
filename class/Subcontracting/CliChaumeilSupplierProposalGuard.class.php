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

require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Guard and access helpers for supplier proposal selection workflows.
 */
class CliChaumeilSupplierProposalGuard
{
	/**
	 * Filter a supplier proposal list to keep only proposals readable by the current user.
	 *
	 * @param array<int,SupplierProposal> $supplierProposals Supplier proposals to filter.
	 * @param User                        $user              Current user.
	 * @return array<int,SupplierProposal>
	 */
	public function filterAccessibleSupplierProposals(array $supplierProposals, User $user): array
	{
		$filteredSupplierProposals = array();
		foreach ($supplierProposals as $supplierProposal) {
			if (empty($supplierProposal->id)) {
				continue;
			}
			if (!$this->canReadSupplierProposal($user, (int) $supplierProposal->id)) {
				continue;
			}

			$filteredSupplierProposals[] = $supplierProposal;
		}

		return $filteredSupplierProposals;
	}

	/**
	 * Tell whether the current user can read a supplier proposal.
	 *
	 * @param User $user               Current user.
	 * @param int  $supplierProposalId Supplier proposal id.
	 * @return bool
	 */
	public function canReadSupplierProposal(User $user, int $supplierProposalId): bool
	{
		if ($supplierProposalId <= 0) {
			return false;
		}

		return (bool) restrictedArea($user, 'supplier_proposal', $supplierProposalId, '', '', 'fk_soc', 'rowid', 0, 1);
	}

	/**
	 * Tell whether at least one proposal in the set has already been processed.
	 *
	 * @param array<int,SupplierProposal> $supplierProposals Supplier proposals to inspect.
	 * @return bool
	 */
	public function hasProcessedSupplierProposal(array $supplierProposals): bool
	{
		foreach ($supplierProposals as $supplierProposal) {
			if ($this->isSupplierProposalProcessed($supplierProposal)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Tell whether one supplier proposal has already been processed.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal to inspect.
	 * @return bool
	 */
	public function isSupplierProposalProcessed(SupplierProposal $supplierProposal): bool
	{
		$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
		if ($currentStatus === SupplierProposal::STATUS_CLOSE) {
			return true;
		}

		return $this->hasLinkedSupplierOrder($supplierProposal);
	}

	/**
	 * Tell whether one supplier proposal is already linked to at least one supplier order.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal to inspect.
	 * @return bool
	 */
	public function hasLinkedSupplierOrder(SupplierProposal $supplierProposal): bool
	{
		if (empty($supplierProposal->id)) {
			return false;
		}

		$linkedOrders = array();

		if (method_exists($supplierProposal, 'clearObjectLinkedCache')) {
			$supplierProposal->clearObjectLinkedCache();
		}
		$supplierProposal->fetchObjectLinked((int) $supplierProposal->id, $supplierProposal->element, '', 'order_supplier');
		if (!empty($supplierProposal->linkedObjects['order_supplier'])) {
			$linkedOrders = $linkedOrders + $supplierProposal->linkedObjects['order_supplier'];
		}

		if (method_exists($supplierProposal, 'clearObjectLinkedCache')) {
			$supplierProposal->clearObjectLinkedCache();
		}
		$supplierProposal->fetchObjectLinked('', '', (int) $supplierProposal->id, $supplierProposal->element, 'OR', 1, 'sourcetype', 1);
		if (!empty($supplierProposal->linkedObjects['order_supplier'])) {
			$linkedOrders = $linkedOrders + $supplierProposal->linkedObjects['order_supplier'];
		}

		return !empty($linkedOrders);
	}
}
