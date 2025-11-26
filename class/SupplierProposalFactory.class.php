<?php
/* Copyright (C) 2025 ATM Consulting <support@atm-consulting.fr>
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

/**
 * Factory class for creating SupplierProposal objects
 * Follows the Factory pattern to centralize object creation logic
 */
class SupplierProposalFactory
{
	/** @var DoliDB */
	private $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create a SupplierProposal object from a database row
	 *
	 * @param object $row Database row object (typically from fetch_object)
	 * @param array $arrayOptions Optional pre-fetched array_options (extrafields data)
	 * @return SupplierProposal Hydrated supplier proposal object
	 */
	public function createFromDatabaseRow($row, array $arrayOptions = array()) : SupplierProposal
	{
		$object = new SupplierProposal($this->db);

		// Map database fields to object properties
		$object->id = $row->rowid;
		$object->ref = $row->ref;
		$object->ref_ext = $row->ref_ext ?? '';
		$object->date_creation = $row->datec;
		$object->delivery_date = $row->date_livraison ?? null;
		$object->total_ht = $row->total_ht;
		$object->total_tva = $row->total_tva;
		$object->status = $row->fk_statut;
		$object->entity = $row->entity;

		// Attach extrafields if provided
		if (!empty($arrayOptions)) {
			$object->array_options = $arrayOptions;
		}

		return $object;
	}
}
