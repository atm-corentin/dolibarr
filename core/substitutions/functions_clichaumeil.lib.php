<?php
/*
 * Copyright (C) 2022	Régis Houssin	<regis.houssin@inodbox.com>
  * Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>

 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file clichaumeil/core/substitution/functions_clichaumeil.lib.php
 * \ingroup multicompany
 * \brief Some display function
 */


function clichaumeil_completesubstitutionarray(&$substitutionarray, $outputlangs, $object, $parameters) {


	$outputlangs->load('clichaumeil@clichaumeil');
	$substitutionarray['__CONTRACTS_LIST__'] = $outputlangs->trans('CliChaumeilContractsList');

	return $substitutionarray;
}
