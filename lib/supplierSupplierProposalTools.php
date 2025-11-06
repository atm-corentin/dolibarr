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
	$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.datec, sp.total_ht, sp.fk_statut, sp.entity, sp.date_livraison ';
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
	$object->status = $item->fk_statut;
	$object->entity = $item->entity;

	// Fetch extrafields if needed
	if (!empty($TOther_fields)) {
		$object->array_options = fetchSupplierProposalExtrafields($db, $item->rowid);
	}

	return $object;
}

/**
 * Print table header for supplier proposal list
 *
 * @param Translate $langs Language object
 * @param array $TOther_fields Extra fields to display
 * @param DoliDB $db Database handler
 * @return void
 */
function printSupplierProposalTableHeader($langs, $TOther_fields, $db)
{
	print '<thead>';
	print '<tr>';
	print ' <th class="text-center" >' . $langs->trans('Ref') . '</th>';
	print ' <th class="text-center" >' . $langs->trans('CLICHAUMEIL_DATEDELIVERYPLANNED') . '</th>';

	if (!empty($TOther_fields)) {
		$e = new ExtraFields($db);
		$e->fetch_name_optionals_label('supplier_proposal');

		foreach ($TOther_fields as $field) {
			// Check properties on SupplierProposal class
			if (property_exists('SupplierProposal', $field)) {
				print ' <th class="text-center" >' . $langs->trans($field) . '</th>';
			} elseif (strpos($field, 'EXTRAFIELD') !== false) {
				$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
				$label = isset($e->attributes['supplier_proposal']['label'][$extrafieldName])
					? $e->attributes['supplier_proposal']['label'][$extrafieldName]
					: $extrafieldName;
				print ' <th class="text-center" >' . $label . '</th>';
			}
		}
	}

	print ' <th class="text-center" >' . $langs->trans('TotalHT') . '</th>';
	print ' <th class="text-center" >' . $langs->trans('Status') . '</th>';
	print '</tr>';
	print '</thead>';
}

/**
 * Print table row for a supplier proposal
 *
 * @param SupplierProposal $object Supplier proposal object
 * @param Context $context External access context
 * @param array $TOther_fields Extra fields to display
 * @param ExtraFields $e ExtraFields object (if needed)
 * @return void
 */
function printSupplierProposalTableRow($object, $context, $TOther_fields, $e = null)
{
	print '<tr>';

	// Reference column with link
	print ' <td data-search="' . $object->ref . '" data-order="' . $object->ref . '"  ><a href="' . $context->getControllerUrl('supplier_proposal_card', '&id=' . $object->id) . '">' . $object->ref . '</a></td>';

	// Delivery date column
	print ' <td data-search="' . dol_print_date($object->delivery_date) . '" data-order="' . $object->delivery_date . '" >' . dol_print_date($object->delivery_date) . '</td>';

	// Extra fields columns
	if (!empty($TOther_fields)) {
		foreach ($TOther_fields as $field) {
			if (property_exists('SupplierProposal', $field)) {
				print ' <td data-search="' . strip_tags($object->{$field}) . '" data-order="' . strip_tags($object->{$field}) . '" >' . $object->{$field} . '</td>';
			} elseif (strpos($field, 'EXTRAFIELD') !== false) {
				$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
				$extrafieldValue = !empty($object->array_options['options_' . $extrafieldName]) ? $object->array_options['options_' . $extrafieldName] : '';

				if ($e) {
					$output = $e->showOutputField($extrafieldName, $extrafieldValue, '', 'supplier_proposal');
					print ' <td data-search="' . strip_tags($output) . '" data-order="' . strip_tags($output) . '" >' . $output . '</td>';
				} else {
					print ' <td data-search="' . strip_tags($extrafieldValue) . '" data-order="' . strip_tags($extrafieldValue) . '" >' . $extrafieldValue . '</td>';
				}
			}
		}
	}

	// Total HT column
	print ' <td data-search="' . $object->total_ht . '" data-order="' . $object->total_ht . '" >' . price($object->total_ht) . '</td>';

	// Status column
	print ' <td class="text-center" >' . $object->getLibStatut(0) . '</td>';

	print '</tr>';
}

/**
 * Include DataTable initialization script
 *
 * @param Context $context External access context
 * @param string $tableId Table ID to initialize
 * @param int $defaultSortColumn Default column index to sort
 * @return void
 */
function includeSupplierProposalDataTableScript($context, $tableId = 'supplier-propal-list', $defaultSortColumn = 2)
{
	$languageUrl = $context->getControllerUrl() . 'vendor/data-tables/french.json';

	print '<script type="text/javascript">';
	print 'initSupplierProposalDataTable("' . $tableId . '", "' . $languageUrl . '", ' . $defaultSortColumn . ');';
	print '</script>';
}
