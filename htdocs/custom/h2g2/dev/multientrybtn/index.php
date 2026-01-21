<?php
/* Copyright (C) 2022     Fabien FERNANDES ALVES  <fabien@code42.fr>
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

dol_include_once('/h2g2/lib/h2g2.lib.php');
dol_include_once('/h2g2/lib/documentation_multientry_btn.lib.php');

global $user, $db, $langs;

$exampleId = 1;

/*
 * Actions
 */

/*
 * View
 */
$arrayjs = array(
	'h2g2/js/documentation.js.php',

	// Used for syntax hilights
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/rainbow.min.js',
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/language/generic.min.js',
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/language/php.min.js',

	// Used for lottie files
	'https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js'
);
$arraycss = array(
	'h2g2/css/documentation.css',

	// Used for syntax hilights
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/themes/paraiso-dark.min.css',
);

llxHeader('', 'H2G2 | ' . $langs->trans('H2G2MenuMultientryButton'), '', '', 0, 0, $arrayjs, $arraycss);

print '<div class="documentation-title">';
print '<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_92tJkB.json"  background="transparent"  speed="1"  style="width: 200px; height: 200px;"  loop autoplay></lottie-player>';
print '<span class="documentation-title__text">' . $langs->trans('MultientityButtonTitle') . '</span>';
print '</div>';

// Open grid layout
print '<div class="documentation-grid">';

/*
 * Example 1 : Basic button - Font awesome
 */
print getBasicFontAwesomeExample();

/*
 * Example 2 : Basic button - Material icons
 */
print getBasicMaterialIconsExample();

/*
 * Example 3 : Without action button
 */
print getWithoutActionExample();

/*
 * Example 4 : Gestion Parc example
 */
print getGPExample();

/*
 * Example 5 : Custom HTML as picto
 */
print getHTMLExample();

/*
 * Example 6 : Up or Down button
 */
print getUpDownExample();

/*
 * Example 7 : Create button
 */
print getCreateExample();

/*
 * Example 8 : Delete button
 */
print getDeleteExample();

/*
 * Example 9 : Colored button
 */
print getColoredExample();
// Close grid layout
print '</div>';

// End of page
llxFooter();
$db->close();
