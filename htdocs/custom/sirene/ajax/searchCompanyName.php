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

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

$error_code = trim((string) GETPOST('errorcode', 'alpha')); // For invalid Token

$name = trim((string) GETPOST('name', 'alpha'));
$id = (int) GETPOST('id', 'int');

$langs->load('sirene@sirene');
$error = 0;
$error_msg = '';
$warning_msg = '';
$content = '';

/*
 * View
 */

if ($error_code == 'InvalidToken') {
	$error++;
	$error_msg = $langs->trans("SireneSecurityTokenHasExpiredSoActionHasBeenCanceledPleaseReload");
} elseif (!empty($name)) {
	$found = 0;
	$companies = [];
	$form = new Form($db);

	// Search near company name
	$sql = "SELECT rowid";
	$sql .= " FROM " . MAIN_DB_PREFIX . "societe";
	$sql .= " WHERE entity IN (" . getEntity('societe') . ")";
	$sql .= " AND (nom LIKE '" . $db->escape($name) . "%' OR '" . $db->escape($name) . "' LIKE CONCAT(nom, '%'))";
	$sql .= " AND status = 1";
	if ($id > 0) $sql .= " AND rowid != " . $id;

	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql) > 0) {
			$found = 1;
			while ($obj = $db->fetch_object($resql)) {
				if (!isset($companies[$obj->rowid])) {
					$company = new Societe($db);
					$company->fetch($obj->rowid);
					$companies[$obj->rowid] = $company->getNomUrl(1);
				}
			}
		}
	} else {
		$error++;
		$error_msg = $db->lasterror();
	}

	if (!$error) {
		// Search exact company name
		$sql = "SELECT rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe";
		$sql .= " WHERE entity IN (" . getEntity('societe') . ")";
		$sql .= " AND nom = '" . $db->escape($name) . "'";
		$sql .= " AND status = 1";
		if ($id > 0) $sql .= " AND rowid != " . $id;

		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$found = 2;
				while ($obj = $db->fetch_object($resql)) {
					if (!isset($companies[$obj->rowid])) {
						$company = new Societe($db);
						$company->fetch($obj->rowid);
						$companies[$obj->rowid] = $company->getNomUrl(1);
					}
				}
			}
		} else {
			$error++;
			$error_msg = $db->lasterror();
		}
	}

	if (!$error) {
		if ($found == 0) { // Not found
			$icon = 'check';
			$icon_color = '';
			$htmltext = $label = $langs->transnoentitiesnoconv('SireneCompanyNameOk');
			$tooltip_trigger = '';
			$tooltip_on = 1;
		} elseif ($found == 1) { // Found near
			$icon = 'warning';
			$icon_color = 'darkorange';
			$label = $langs->transnoentitiesnoconv('SireneCompanyNameWarning') . ' - ' . $langs->transnoentitiesnoconv("ClickToShowHelp");
			$htmltext = $langs->transnoentitiesnoconv('SireneOtherCompanyNameFound') . '<ul id="sirene_check_name_company_list"><li>' . implode('</li><li>', $companies) . '</li></ul>';
			$tooltip_trigger = 'sirene_check_name_infos';
			$tooltip_on = 3;
		} else { // $found == 2 // Found strict
			$icon = 'error';
			$icon_color = 'red';
			$label = $langs->transnoentitiesnoconv('SireneCompanyNameError') . ' - ' . $langs->transnoentitiesnoconv("ClickToShowHelp");
			$htmltext = $langs->transnoentitiesnoconv('SireneOtherCompanyNameFound') . '<ul id="sirene_check_name_company_list"><li>' . implode('</li><li>', $companies) . '</li></ul>';
			$tooltip_trigger = 'sirene_check_name_infos';
			$tooltip_on = 3;
		}

		$img = (empty($icon_color) ? '' : '<span style="color: ' . $icon_color . ' !important;">') . img_picto($label, $icon) . (empty($icon_color) ? '' : '</span>');
		$content = $form->textwithtooltip($img, $htmltext, $tooltip_on, 1, '', '', 3, '', 0, $tooltip_trigger, 0);
	}
}

if (!empty($warning_msg)) {
	$outjson = array(
		'warning' => $warning_msg,
	);
} elseif ($error) {
	$outjson = array(
		'error' => $error_msg,
	);
} elseif (!empty($content)) {
	$outjson = array(
		'content' => $content,
	);
} else {
	$outjson = [];
}

echo json_encode($outjson);
$db->close();
