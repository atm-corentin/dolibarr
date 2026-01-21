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
dol_include_once('/sirene/class/client/SireneClientRnaApi.class.php');

$error_code = trim((string) GETPOST('errorcode', 'alpha')); // For invalid Token

$only_company_open = !empty(GETPOST('sirene_only_open', 'int'));
$only_company_siege = !empty(GETPOST('sirene_only_open_siege', 'int'));
$number = max(20, (int) GETPOST('sirene_number', 'int'));

$company_name = trim((string) GETPOST('sirene_company_name', 'alpha'));
$siren_siret = trim((string) GETPOST('sirene_siren_siret', 'alpha'));
$naf = trim((string) GETPOST('sirene_naf', 'alpha'));
$rna = trim((string) GETPOST('sirene_rna', 'alpha'));
$town = trim((string) GETPOST('sirene_town', 'alpha'));
$zipcode = trim((string) GETPOST('sirene_zipcode', 'alpha'));

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
} elseif (!empty($company_name) || !empty($siren_siret) || !empty($naf) || !empty($rna) || !empty($town) || !empty($zipcode)) {
	// Clean parameters
	$number = max(20, min(100, (int) $number));
	$siren_siret = str_replace(' ', '', $siren_siret);
	$naf = str_replace(' ', '', $naf);
	if ((!empty($naf)) && stristr($naf, '.') === false) {
		$naf = substr($naf, 0, 2) . '.' . substr($naf, 2, 3);
	}
	$rna = str_replace(' ', '', $rna);

	// siren has 9 digits and siret has 14 digits
	$siret = '';
	$siren = '';
	if (strlen($siren_siret) == 9) {
		$siren = $siren_siret;
	} elseif (strlen($siren_siret) == 14) {
		$siret = $siren_siret;
	}

	// Connection to sirene API
	$client_sirene = new SireneClientSireneApi($db);
	$result = $client_sirene->connection();
	if ($result < 0) {
		$error_msg = $client_sirene->errorsToString();
		$error++;
	} else {
		$companies_results = $client_sirene->searchCompanies($company_name, $siren, $siret, $rna, $naf, $zipcode, $number, $town, $only_company_open, $only_company_siege);
		if (!isset($companies_results)) {
			$error_msg = $client_sirene->errorsToString();
			$error++;
		}
	}

	if (!$error && empty($companies_results)) {
		// Connection to RNA API
		$client_rna = new SireneClientRnaApi($db);
		$result = $client_rna->connection();
		if ($result < 0) {
			$error_msg = $client_rna->errorsToString();
			$error++;
		} else {
			$companies_results = $client_rna->searchCompanyByRna($rna);
			if (!isset($companies_results)) {
				$error_msg = $client_sirene->errorsToString();
				$error++;
			}
		}
	}

	if (!$error && !empty($companies_results)) {
		$out = <<<STYLE
	<style>
		#sirene_table .green {
			color: #118822 !important;
		}
	</style>
STYLE;
		$out .= <<<SCRIPT
   <script type="text/javascript">
        $(document).ready(function () {
			// Select the radio if click on the line
			$('table#sirene_table tr').click(function(event) {
				if (event.target.type !== 'radio') {
					$(':radio', this).trigger('click');
				}
			});
		});
	</script>
SCRIPT;

		$out .= '<table id="sirene_table" class="noborder centpercent">' . "\n";
		$out .= '<tr class="liste_titre">' . "\n";
		// Radio choice
		$out .= '<td width="20px"></td>' . "\n";
		// Company name
		$out .= '<td>' . $langs->trans("SireneCompanyName") . '</td>' . "\n";
		// Address
		$out .= '<td>' . $langs->trans("Address") . '</td>' . "\n";
		// Create date
		$out .= '<td>' . $langs->trans("SireneCreateDate") . '</td>' . "\n";
		// Close date
		$out .= '<td>' . $langs->trans("SireneStatus") . '</td>' . "\n";
		// Code NAF
		$out .= '<td>' . $langs->trans("SireneCodeNaf") . '</td>' . "\n";
		// SIREN
		$out .= '<td>' . $langs->trans("SireneSiren") . '</td>' . "\n";
		// SIRET
		$out .= '<td>' . $langs->trans("SireneSiret") . '</td>' . "\n";
		// RNA
		$out .= '<td>' . $langs->trans("SireneRna") . '</td>' . "\n";
		$out .= '</tr>' . "\n";

		$found = false;
		foreach ($companies_results as $key => $company_infos) {
			$out .= '<tr class="oddeven">' . "\n";
			// Radio choice
			$checked = false;
			if ($company_infos['status'] == Sirene::COMPANY_ADMIN_STATUS_OPENED && !$found) {
				// Check the first open company found
				$checked = true;
				$found = true;
			}
			$out .= '<td><input type="radio" class="sirene_choice" name="sirene_choice" ' . ($checked ? ' checked="checked"' : '') . ' value="' . $key . '"></td>' . "\n";
			// Company name
			$company_name = !empty($company_infos['company_name_all']) ? $company_infos['company_name_all'] : $langs->trans('SireneValueUndefined');
			$siege_html = !empty($company_infos['siege']) ? '&nbsp;<span class="fa fa-star classfortooltip green" style="font-size: 0.8em;" title="' . $langs->trans('SireneCompanySiegeHelp') . '"></span>' : '';
			$out .= '<td>' . $company_name . $siege_html . '</td>' . "\n";
			// Address
			$company_address = !empty($company_infos['address_all']) ? $company_infos['address_all'] : $langs->trans('SireneValueUndefined');
			$out .= '<td>' . $company_address . '</td>' . "\n";
			// Create date
			$company_date_creation = !empty($company_infos['date_creation']) ? $company_infos['date_creation'] : $langs->trans('SireneValueUndefined');
			$out .= '<td>' . $company_date_creation . '</td>' . "\n";
			// Close date
			$out .= '<td>' . $company_infos['status_all'] . '</td>' . "\n";
			// Code NAF
			$out .= '<td>' . $company_infos['codenaf_all'] . '</td>' . "\n";
			// SIREN
			$out .= '<td>' . $company_infos['siren'] . '</td>' . "\n";
			// SIRET
			$out .= '<td>' . $company_infos['siret'] . '</td>' . "\n";
			// RNA
			$out .= '<td>' . $company_infos['rna'] . '</td>' . "\n";
			$out .= '</tr>' . "\n";
		}
		$out .= '</table>' . "\n";
	} else {
		$warning_msg = $langs->trans("SireneWarningNoCompanyFound");
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
} else {
	$outjson = array(
		'companies_results' => json_encode($companies_results),
		'content' => $out,
	);
}

echo json_encode($outjson);
$db->close();
