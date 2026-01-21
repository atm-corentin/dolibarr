<?php

/* Copyright (C) 2022 Fabien FERNANDES ALVES  <fabien@code42.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

/**
 * \file    supercotrolia/ajax/ajax_get_intervention_to_receive.php
 * \ingroup supercotrolia
 * \brief   Ajax file to get all intervention to receive.
 */

// Load Dolibarr environment
use h2g2\QueryBuilder;
use supercotrolia\SuperCotroliaIntervention;

if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', 1); // Disables token renewal
}

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) { $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php";
}
if (!$res) { die("Include of main fails");
}

dol_include_once('/h2g2/lib/default_list.lib.php');
dol_include_once('/h2g2/class/querybuilder.class.php');
dol_include_once('/h2g2/class/querybuilderexception.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php'; // TODO : You must adapt this include to your object

global $db, $langs, $user, $conf;

// Load translation files required by the page
$langs->loadLangs(array("supercotrolia@supercotrolia", "other"));

// Variable to define
$context = GETPOST('context', 'alphanohtml');
$object = new Facture($db); // TODO : You must adapt the object to yours

// Automatically calculated variables
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);
$perPage = GETPOST("perPage", 'int') ? GETPOST("perPage", 'int') : $conf->liste_limit;
$page = GETPOST("page", 'int') ?: 1;
$offset = $perPage * ($page - 1);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (!$sortfield) { reset($object->fields); $sortfield="t.".key($object->fields);
}   // Set here default search field. By default 1st field in definition. Reset is required to avoid key() to return null.
if (!$sortorder) { $sortorder = "ASC";
}
$filters = GETPOST('filters', 'none');
$filters = json_decode(urldecode($filters));

$arrayfields = getArrayFieldsForListing($context, $object, $extrafields);

$tmpvar = "MAIN_SELECTEDFIELDS_".$context; // To get list of saved seleteced properties
if (!empty($user->conf->$tmpvar)) {
	$tmparray = explode(',', $user->conf->$tmpvar);
	foreach ($arrayfields as $key => $val) {
		if (in_array($key, $tmparray)) { $arrayfields[$key]['checked'] = 1;
		} else { $arrayfields[$key]['checked'] = 0;
		}
	}
}

/*
 * Prepare the query
 */

// Table and join section
// --------------------------------------------------------------------
$qb = QueryBuilder::table($object->table_element.' AS t');
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$qb->leftJoin($object->table_element.'_extrafields AS ef', 't.rowid', 'ef.fk_object');
}

// Where section
// --------------------------------------------------------------------
foreach ($filters as $key => $value) {
	if ($key === 'button_removefilter' || $key === 'button_quicklist_addfilter') continue; // #62
	$key = explode('search_', $key)[1];
	if (strpos($key, 'options_') !== false) { // This is an extrafield
		$key = 'ef.'.explode('options_', $key)[1];
	} else {
		$key = 't.'.$key;
	}

	if ($value->operator == 'LIKE') {
		$val = '%'.$value->value.'%';
	} else {
		$val = $value->value;
	}

	$qb->where($key, $value->operator, $val);
}

if ($object->ismultientitymanaged == 1) {
	$qb->where('t.entity', $conf->entity);
} else {
	$qb->disableEntityCheck();
}

// Manage pagination before doing the select and the pagination
$qbTotal = clone $qb;
$qbTotal->select('COUNT(t.rowid) AS total');

// Select section
// --------------------------------------------------------------------

// Select the id which is required
$qb->select('t.rowid AS id');

// Select object fields
foreach ($object->fields as $key => $val) {
	if ($arrayfields['t.'.$key]['checked']) {
		$qb->select('t.'.$key);
	}
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		if ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' && $arrayfields['ef.'.$key]['checked']) {
			$qb->select("ef.".$key.' AS options_'.$key);
		}
	}
}

// Sort section
// --------------------------------------------------------------------
$qb->orderBy($sortfield, $sortorder);

// Pagination with limit and offset section
// --------------------------------------------------------------------
$qb->limit($perPage);
if ($offset > 0) {
	$qb->offset($offset);
}

/*
 * Execute the query
 */
$values = $qb->get();
$total = 0;
$resTotal = $qbTotal->get();
if ($resTotal && count($resTotal) > 0) {
	$total = $resTotal[0]->total;
}

$prevPage = (($page - 1 > 1) ? $page - 1 : 1);
$lastPage = ($total > 0 ? ceil($total / $perPage) : 1);
$nextPage = (($page + 1 <= $lastPage) ? $page + 1 : $page);

// Format values for display
$values = formatValueForDisplay($values, $object, $extrafields, $context);

$ret = array(
	'status' => 200,
	'success' => true,
	'values' => $values,
	'total' => $total,
	'page' => $page,
	'perPage' => $perPage,
	'prevPage' => $prevPage,
	'nextPage' => $nextPage,
	'lastPage' => $lastPage
);

echo json_encode($ret);
