<?php
/*
 * Copyright (C) 2022	Régis Houssin	<regis.houssin@inodbox.com>
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
 * \file multicompany/core/substitution/functions_multicompany.lib.php
 * \ingroup multicompany
 * \brief Some display function
 */


function clichaumeil_completesubstitutionarray(&$substitutionarray, $outputlangs, $object, $parameters) {
	global $conf, $mysoc;

	if (is_object($object) && $object->entity != $conf->entity && isset($substitutionarray['__CONTRACTS_LIST__'])) {
		$substitutionarray['__CONTRACTS_LIST__'] = '__CONTRACTS_LIST__';
	}

	return $substitutionarray;
}
