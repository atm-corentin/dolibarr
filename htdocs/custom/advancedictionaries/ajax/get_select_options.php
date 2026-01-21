<?php
/* Copyright (C) 2019       Open-Dsi        <support@open-dsi.fr>
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
 * \file    htdocs/advancedictionaries/ajax/dictionary.php
 * \brief   File to return Ajax response on dictionary lines request
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');
//if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

$module = GETPOST('dictionary_module', 'alpha');
$name = GETPOST('dictionary_name', 'alpha');
$rootPath = GETPOST('dictionary_root_path', 'alpha');
$field_updated = GETPOST('field_updated', 'alpha');
$key_prefix = GETPOST('key_prefix', 'alpha');
$key_suffix = GETPOST('key_suffix', 'alpha');

/*
 * View
 */

top_httphead();
$outjson = array();

if (!empty($module) && !empty($name)) {
	dol_include_once('/advancedictionaries/class/dictionary.class.php');
	$dictionary = Dictionary::getDictionary($db, $module, $name, 0, $rootPath);

	if (empty($field_updated)) {
		$fields_to_update = array();
		foreach ($dictionary->fields as $info) {
			if (!empty($info['update_list_values']) && is_array($info['update_list_values'])) {
				$fields_to_update = array_merge($fields_to_update, $info['update_list_values']);
			}
		}
		$fields_to_update = array_flip(array_flip($fields_to_update));
	} elseif (!empty($dictionary->fields[$field_updated]['update_list_values']) &&
		is_array($dictionary->fields[$field_updated]['update_list_values'])
	) {
		$fields_to_update = $dictionary->fields[$field_updated]['update_list_values'];
	}

	if (!empty($fields_to_update)) {
		$dictionaryLine = $dictionary->getNewDictionaryLine();
		$fieldsValue = $dictionary->getFieldsValueFromForm($key_prefix, $key_suffix, 2);
		$fieldsValue += $dictionary->getFixedFieldsValue();
		$dictionaryLine->fields = $fieldsValue;

		$values = array();
		foreach ($fields_to_update as $field_name) {
			$values[$field_name] = $dictionaryLine->showInputFieldAD($field_name, '', $key_prefix, $key_suffix, 0, 1);
		}
		$outjson['values'] = $values;
	}
}

print json_encode($outjson);
$db->close();
