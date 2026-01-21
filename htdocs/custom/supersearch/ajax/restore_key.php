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
 *   	\file       restore_key.php
 *		\ingroup    search
 *		\brief      Page to call with api
 */
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

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/supersearch/lib/supersearch.lib.php');

global $conf, $langs, $db;
$langs->load('supersearch@supersearch');

$result = 0;
$msg = array();
$consts = array(
	'SUPERSEARCH_ADMINKEY' => dolibarr_get_const($db, 'OLD_SUPERSEARCH_ADMINKEY'),
	'SUPERSEARCH_APIKEY' => dolibarr_get_const($db, 'OLD_SUPERSEARCH_APIKEY'),
	'SUPERSEARCH_CRUDKEY' => dolibarr_get_const($db, 'OLD_SUPERSEARCH_CRUDKEY'),
	'SUPERSEARCH_URL_MEILISEARCH' => dolibarr_get_const($db, 'OLD_SUPERSEARCH_URL_MEILISEARCH')
);

foreach ($consts as $name => $value) {
	if (dolibarr_set_const($db, $name, $value) > 0) {
		if (dolibarr_del_const($db, 'OLD_' . $name, -1) < 0) {
			$result--;
			$msg['OLD_' . $name] = 'DEL';
		}
	} else {
		$result--;
		$msg[$name] = 'SET';
	}
}

if ($result == 0) {
	echo json_encode(array('statut' => 200));
} else {
	echo json_encode(array('statut' => 400, 'msg' => $msg ));
}
