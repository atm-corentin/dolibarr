<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       get_intervention_plus.php
 *		\ingroup    search
 *		\brief      Page to call with api
 */

use interventionplus\Intervention;

if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

use \h2g2\QueryBuilder;

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
dol_include_once('/supersearch/lib/supersearch.lib.php');
dol_include_once('/h2g2/class/querybuilder.class.php');
dol_include_once('/interventionplus/class/intervention.class.php');
dol_include_once('/supercotrolia/lib/supercotrolia.lib.php');

global $db, $langs;

$langs->load('supersearch@supersearch');

$id = GETPOST("id", "int");

$resql = QueryBuilder::table('fichinter as f')
	->leftJoin('supercotrolia_intervention_fields as spf', 'spf.fk_object', 'f.rowid')
	->leftJoin('supercotrolia_part as sp', 'f.rowid', 'sp.fk_inter')
	->leftJoin('c_supercotrolia_part_type as cspt', 'sp.type', 'cspt.rowid')
	->leftJoin('c_supercotrolia_part_type as cspt2', 'cspt.fk_parent', 'cspt2.rowid')
	->LeftJoin('supercotrolia_car as car', 'car.fk_inter', 'sp.fk_inter')
	->select('cspt2.label as pole, car.type_car, f.fk_statut as statut')
	->where('f.rowid', '=', $id)
	->disableEntityCheck();

$resql = $resql->get();
$inter = $resql[0];
$inter->badge = new Intervention($db);
$inter->badge = $inter->badge->libStatut($inter->statut);

echo json_encode(
	array(
		'pole' => $inter->pole ?? '',
		'brand' => dol_trunc(getCarLabel($inter->type_car)) ?? '',
		'status' => $inter->badge ?? ''
	)
);
