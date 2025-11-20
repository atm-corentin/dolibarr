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
