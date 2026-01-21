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
 *   	\file       update_index_customization.php
 *		\ingroup    search
 *		\brief      Page to update index customization
 */

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');

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

$indexConfig = getIndexCustomization();
$indexes = GETPOST('indexes', 'array');

foreach ($indexes as $index) {
	$idx = $index['index'];
	$indexConfig->$idx['url'] = $index['url'];
	$indexConfig->$idx['picto'] = $index['picto'];
	$indexConfig->$idx['position'] = $index['position'];
	$indexConfig->$idx['disabled'] = $index['disabled'] == "true" ? 1 : 0;

	preg_match('/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/i', $index['color'], $matches); // separate r,g,b value
	$indexConfig->$idx['color'] = sprintf("#%02x%02x%02x", intval($matches[1]), intval($matches[2]), intval($matches[3])); // convert to hex code
}


$ret = updateIndexCustomization($indexConfig);
if ($ret > 0) {
	echo json_encode(array('statut' => 200, 'result'=> 'OK'));
} else {
	echo json_encode(array('statut' => 400, 'message'=> $langs->trans('SuperSearchErrorUpdateIndexCustomization')));
}
