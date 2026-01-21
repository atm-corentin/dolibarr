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
 *   	\file       get_index_settings.php
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

dol_include_once('/supersearch/lib/supersearch.lib.php');

global $conf, $langs, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;
$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

$indexName = GETPOST("indexName");

if (empty($indexName)) echo json_encode(array('statut' => 400, 'error' => array('message' => $langs->trans('SuperSearchErrorNotProvided', 'Index'))));
else {
	$curl = supersearchCurlInit('indexes/' . $uniqueId . '_' . $indexName . '/settings', 1);
	if ($curl != -1) {
		$response = curl_exec($curl);

		if (isset($response)) echo json_encode(array('statut' => 200, 'results' => $response));
		else echo json_encode(array('statut' => 400, 'error' => array('message' => $response)));
		supersearchCurlClose($curl);
	} else {
		echo json_encode(array('statut' => 400, 'error' => array('message' => $langs->trans('SuperSearchErrorNotProvided', 'URL'))));
	}
}
