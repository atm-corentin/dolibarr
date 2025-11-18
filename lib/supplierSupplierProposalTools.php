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

/**
 * Get SQL query to fetch supplier proposals for external access
 *
 * @param DoliDB $db Database handler
 * @param int $socId Third-party ID
 * @return string SQL query
 */
function getSupplierProposalExternalSql($db, $socId)
{
	$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.datec, sp.total_ht, sp.total_tva, sp.fk_statut, sp.entity, sp.date_livraison ';
	$sql .= ' FROM `' . $db->prefix() . 'supplier_proposal` sp';
	$sql .= ' WHERE sp.fk_soc = ' . intval($socId);
	$sql .= ' AND sp.fk_statut IN (' . SupplierProposal::STATUS_VALIDATED . ', ' . SupplierProposal::STATUS_SIGNED . ', ' . SupplierProposal::STATUS_CLOSE . ')';
	$sql .= ' ORDER BY sp.datec DESC';

	return $sql;
}

/**
 * Get configured extra fields for supplier proposal list
 *
 * @return array Array of extra field names
 */
function getSupplierProposalExtraFields()
{
	$TOther_fields = explode(',', getDolGlobalString('EACCESS_LIST_ADDED_COLUMNS'));
	if (empty($TOther_fields)) {
		$TOther_fields = array();
	}
	return $TOther_fields;
}

/**
 * Fetch extrafields for a supplier proposal
 *
 * @param DoliDB $db Database handler
 * @param int $rowid Supplier proposal ID
 * @return array Array of extrafields [key => value]
 */
function fetchSupplierProposalExtrafields($db, $rowid)
{
	$extrafields = array();

	// Get configured extra fields to determine which columns to select
	$TOther_fields = getSupplierProposalExtraFields();
	$extrafieldColumns = array();

	foreach ($TOther_fields as $field) {
		if (strpos($field, 'EXTRAFIELD_') !== false) {
			$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
			$extrafieldColumns[] = $db->escape($extrafieldName);
		}
	}

	// Build SQL with only necessary columns
	$sqlExtra = 'SELECT rowid, tms, fk_object';
	if (!empty($extrafieldColumns)) {
		$sqlExtra .= ', ' . implode(', ', $extrafieldColumns);
	}
	$sqlExtra .= ' FROM ' . $db->prefix() . 'supplier_proposal_extrafields';
	$sqlExtra .= ' WHERE fk_object = ' . intval($rowid);

	$resqlExtra = $db->query($sqlExtra);

	if ($resqlExtra) {
		$objExtra = $db->fetch_object($resqlExtra);
		if ($objExtra) {
			foreach ($objExtra as $key => $value) {
				if ($key != 'rowid' && $key != 'tms' && $key != 'fk_object' && $key != 'import_key') {
					$extrafields['options_' . $key] = $value;
				}
			}
		}
		$db->free($resqlExtra);
	}

	return $extrafields;
}

/**
 * Create supplier proposal object from database result
 *
 * @param DoliDB $db Database handler
 * @param object $item Database row object
 * @param array $TOther_fields Extra fields to fetch
 * @return SupplierProposal
 */
function createSupplierProposalFromItem($db, $item, $TOther_fields = array())
{
	$object = new SupplierProposal($db);
	$object->id = $item->rowid;
	$object->ref = $item->ref;
	$object->ref_ext = $item->ref_ext;
	$object->date_creation = $item->datec;
	$object->delivery_date = $item->date_livraison;
	$object->total_ht = $item->total_ht;
	$object->total_tva = $item->total_tva;
	$object->status = $item->fk_statut;
	$object->entity = $item->entity;

	// Fetch extrafields if needed
	if (!empty($TOther_fields)) {
		$object->array_options = fetchSupplierProposalExtrafields($db, $item->rowid);
	}

	return $object;
}

/**
 * check current access to controller
 *
 * @param void
 * @return  bool true if access granted, false otherwise
 */
function hasSupplierProposalAccess() : bool
{
	global $conf, $user;

	return isModEnabled('clichaumeil')
		&& getDolGlobalInt('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')
		&& $user->hasRight('clichaumeil', 'SupplierProposal' ,'read');
}
