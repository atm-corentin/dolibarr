<?php
/* Copyright (C) 2025       Open-Dsi        <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/sirene/ajax/getSearchChoiceResults.php
 * \brief   File to return Ajax response on search on Sirene API
 * 			Return confirm box for choice on result list
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
//if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/sirene/class/client/SireneClientSireneApi.class.php');

$error_code = trim((string) GETPOST('errorcode', 'alpha')); // For invalid Token

$siret = trim((string) GETPOST('sirene_siret', 'alpha'));

$langs->load('sirene@sirene');
$error = 0;
$error_msg = '';
$out = '';
$companies_results = [];
$warning_msg = '';

/*
 * View
 */

if ($error_code == 'InvalidToken') {
	$error++;
	$error_msg = $langs->trans("SireneSecurityTokenHasExpiredSoActionHasBeenCanceledPleaseReload");
} elseif (!empty($siret)) {
	// Connection to sirene API
	$client_sirene = new SireneClientSireneApi($db);
	$result = $client_sirene->connection();
	if ($result < 0) {
		$error_msg = $client_sirene->errorsToString();
		$error++;
	} else {
		$company_infos = $client_sirene->getOneCompanyBySiret($siret);
		if (!isset($company_infos)) {
			$error_msg = $client_sirene->errorsToString();
			$error++;
		}
	}
} else {
	$warning_msg = $langs->trans("SireneWarningProvideAtLeastOneCriteria");
}

if (!empty($warning_msg)) {
	$outjson = array(
		'warning' => $warning_msg,
	);
} elseif ($error) {
	$outjson = array(
		'error' => $error_msg,
	);
} elseif (!empty($company_infos)) {
	$outjson = array(
		'company_infos' => json_encode($company_infos),
	);
}

echo json_encode($outjson);
$db->close();
