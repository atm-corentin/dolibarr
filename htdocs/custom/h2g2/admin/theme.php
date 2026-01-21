<?php
/* Copyright (C) 2023     Thomas BACHELEY  <thomas@code42.fr>
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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
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

global $langs, $db;

dol_include_once('/h2g2/lib/h2g2.lib.php');
dol_include_once('/h2g2/lib/documentation_theme.lib.php'); // include function to read file / MD
dol_include_once('/themequarantedeux/core/modules/modThemeQuaranteDeux.class.php');

/*
 * View
 */

$page_name = "H2G2MenuTheme";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_h2g2@h2g2');

// Configuration header
$head = h2g2AdminPrepareHead();
dol_fiche_head($head, 'theme', '', 0, 'h2g2@h2g2');

if (class_exists('modThemeQuaranteDeux')) {
	$quarantedeux = new ModThemeQuaranteDeux($db);

	print '<h1>';
	print $langs->trans('H2G2ThemeVersion', $quarantedeux->version);
	print '</h1>';
	print '<hr style="border-top: 3px solid #bbb;">';
	displayThemeFileContent('CHANGELOG.md');
} else {
	print $langs->trans('H2G2ThemeNotFound');
}

llxFooter();
$db->close();
