<?php
/* Copyright (C) 2025     Ravi Trébuchet  <ravi@code42.fr>
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

// Parameters
$action = GETPOST("action", "aZ09");
$backtopage = GETPOST("backtopage", "alpha");
$modulepart = GETPOST("modulepart", "aZ09");    // Used by actions_setmoduleoptions.inc.php (contained into h2g2_actions_multiform.inc.php)


/*
 * Actions
 */

/*
 * View
 */
$arrayjs = array(
	'h2g2/js/documentation.js.php',
	'h2g2/js/multiform_setup.js.php',

	// Used for syntax hilights
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/rainbow.min.js',
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/language/generic.min.js',
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/js/language/php.min.js',
	// Used for lottie files
	'https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js'
);
$arraycss = array(
	'h2g2/css/documentation.css',
	'h2g2/css/multiform_setup.css',

	// Used for syntax hilights
	'https://cdnjs.cloudflare.com/ajax/libs/rainbow/1.2.0/themes/paraiso-dark.min.css',
);

llxHeader('', 'H2G2 | ' . $langs->trans('H2G2DocumentationMultiForm'), '', '', 0, 0, $arrayjs, $arraycss);

print '<div class="documentation-title">';
print '<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_92tJkB.json"  background="transparent"  speed="1"  style="width: 200px; height: 200px;"  loop autoplay></lottie-player>';
print '<span class="documentation-title__text">' . $langs->trans('H2G2MultiFormTitle') . '</span>';
print '</div>';
$ret = '';
//$ret .= '<div class="example">';
$ret .= '<div class="example__code">';
$ret .= '<pre>';
$ret .= '<div class="example__code-header">';
$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2SetupExample1') . '</div>';
$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
$ret .= '</div>';
$ret .= '<div class="example__code-content">';
$ret .= '<code data-language="php">';

$example = '// TODO : Include main

// Global declaration
global $langs, $db, $user, $conf, $hookmanager;

// Libraries
$moduleName = "h2g2";														// Used to store variable on the right location and have the right path
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once("/h2g2/class/h2g2.formsetup.class.php");
dol_include_once("/$moduleName/lib/library.lib.php"); // TODO : include library that contains header function

// Translations
$langs->loadLangs(array("admin", "h2g2@h2g2", "$moduleName@$moduleName"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($moduleName . "setup", "h2g2setup", "globalsetup"));

// Access control
if ((!$user->admin) ?? false) accessforbidden();

// Parameters
$action = GETPOST("action", "aZ09");
$backtopage = GETPOST("backtopage", "alpha");
$modulepart = GETPOST("modulepart", "aZ09");    // Used by actions_setmoduleoptions.inc.php (contained into h2g2_actions_multiform.inc.php)

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

// START - Creation of forms / constant
// List of forms
$forms = array();

// FIRST FORM
$formSetup = new H2G2FormSetup($db, $moduleName);							// $moduleName is used to store const image files for example
$formSetup->setTitle("H2G2EmptyMenu1", "fas fa-cog");			// Define Title and Picto for the form

$formSetup->newItem("FAKE_CONST_1")->setAsImage();							// Define variable as an image
$formSetup->newItem("FAKE_CONST_2")->setAsString();							// Define variable as a string
$formSetup->newItem("FAKE_CONST_3")->setAsYesNo();							// Define variable as a toggle switch

$forms[] = $formSetup;														// Add the first form to form list

// SECOND FORM
$formSetupSecond = new H2G2FormSetup($db, $moduleName);
$formSetupSecond->setTitle("H2G2EmptyMenu2", "fas fa-file-alt");

$formSetupSecond->newItem("FAKE_CONST_4", "table_const")		// Second parameter is used to store variable in an other table that "llx_const" (is we want to store a too big variable for this table)
->setHelp("ThisIsAText")													// Define an help text for this const
//You can also add a translation for the key "FAKE_CONST_4Tooltip" in your translation file to add a tooltip
->setAsHtml()																// Set const as HTML
->setHeight(800);															// Define HTML const height -> for HTML

$item = $formSetupSecond->newItem("FAKE_CONST_5");
$arrayOfValues = array(
	"Value 1",
	"Value 2",
	"Value 3"
);
$item->setAsSelect($arrayOfValues);											// Set const as select

$formSetupSecond->newItem("FAKE_CONST_6")->setAsColor();							// Define variable as a color

$forms[] = $formSetupSecond;												// Add the second form to form list
// END - Creation of forms / constant

/*
 * Actions
 */

// Actions of multi form setup
include dol_buildpath("/h2g2/tpl/h2g2_actions_multiform.inc.php");

/*
 * View
 */
// Useful variables
$pageName = "ContratPlusSetupCustomContract";    	// Page Name/Title
$pagePicto = "pdf";                                	// Picto that will be displayed
$tabPicto = "contratplus@contratplus";           	// Picto that will be displayed at the left of admin tabs
$headerFunction = "generateHeader";                	// Name of function that generate $head
$jsArray = array();                                	// Javascript files to include
$cssArray = array();                            	// CSS files to include

// Generate multi form
include dol_buildpath("/h2g2/tpl/h2g2_view_multiform.inc.php");

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();';

$ret .= $example;
$ret .= '</code>';
$ret .= '</div>';
$ret .= '</pre>';
//$ret .= '</div>';

$ret .= '<div class="h2g2-multiform-example">';
print $ret;
// Global declaration
global $langs, $db, $user, $conf, $hookmanager;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once("/h2g2/class/h2g2.formsetup.class.php");

// Translations
$langs->loadLangs(array("admin", "h2g2@h2g2"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array("h2g2setup", "globalsetup"));

// Access control
if ((!$user->admin) ?? false) accessforbidden();

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

// List of forms
$forms = array();
$moduleName = "h2g2";                                                        // Used to store variable on the right location

// PDF GENERAL
$formSetup = new H2G2FormSetup($db, $moduleName);                            // $moduleName is used to store const image files for example
$formSetup->setTitle("H2G2EmptyMenu1", "fas fa-cog");            // Define Title and Picto for the form

$formSetup->newItem("FAKE_CONST_1")->setAsImage();                            // Define variable as an image
$formSetup->newItem("FAKE_CONST_2")->setAsString();                            // Define variable as a string
$formSetup->newItem("FAKE_CONST_3")->setAsYesNo();                            // Define variable as a toggle switch

$forms[] = $formSetup;                                                        // Add the first form to form list

// PDF CONTENT
$formSetupContent = new H2G2FormSetup($db, $moduleName);
$formSetupContent->setTitle("H2G2EmptyMenu2", "fas fa-file-alt");

$formSetupContent->newItem("FAKE_CONST_4", "table_const")        // Second parameter
->setHelp("ThisIsAText")                                                    // Define an help text for this const
//You can also add a translation for the key 'FAKE_CONST_4Tooltip' in your translation file to add a tooltip
->setAsHtml()                                                                // Set const as HTML
->setHeight(800);                                                            // Define HTML const height

$item = $formSetupContent->newItem("FAKE_CONST_5");
$arrayOfValues = array(
	"Value 1",
	"Value 2",
	"Value 3"
);
$item->setAsSelect($arrayOfValues);                                            // Set const as select

$formSetupContent->newItem("FAKE_CONST_6")->setAsColor();                            // Define variable as a color

$forms[] = $formSetupContent;                                                // Add the second form to form list

/*
 * View
 */

$pageName = "ContratPlusSetupCustomContract";        // Page Name/Title
$pagePicto = "pdf";                                    // Picto that will be displayed
$tabPicto = "contratplus@contratplus";            // Picto that will be displayed at the left of admin tabs
$headerFunction = "generateHeader";                    // Name of function that generate $head
$jsArray = array();                                    // Javascript files to include
$cssArray = array();                                // CSS files to include

// Generate multi form
print "<span id='admin-help-button' data-help1='" . $langs->trans("H2G2HelpAdminTool1") . "' data-help2='" . $langs->trans("H2G2HelpAdminTool2") . "' data-help3='" . $langs->trans("H2G2HelpAdminTool3") . "' class='fa fa-question-circle'></span>";
print "<div id='enable-form-buttons' style='min-height: 200px;' class='tabbar'>";
print "<ul id='H2G2-admin-tabs' class='flex-center'>";
print "</ul>";

// Print form setup entries depending on the action
$activeFrom = GETPOST("form-name");
if (empty($activeFrom)) $activeFrom = $forms[0]->title;
$firstForm = true;
$lastForm = false;
foreach ($forms as $formNumber => $form) {
	if (($formNumber - 1) == count($forms)) $last = true;
	print "<div id='form-" . $form->title . "' class='H2G2-form ";
	if ($activeFrom != $form->title) print 'H2G2-hidden';
	print "'>";
	print $form->generateOutput($action == 'edit', $firstForm, $lastForm);
	print "</div>";
	$firstForm = false;
}
print "</div>";

// Tabs action
print "<div class='tabsAction'>";
if ($action == 'edit') {
	print "<input type='hidden' id='h2g2-form-name' name='form-name' value='" . $activeFrom . "'>";
	print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
	print '<a class="button button-cancel" type="submit" href="' . $_SERVER['PHP_SELF'] . '">' . $langs->trans('Cancel') . '</a>';
} else {
	print "<form method='POST' action='" . $_SERVER["PHP_SELF"] . "'>";
	print "<input type='hidden' name='token' value='" . newToken() . "'>";
	print "<input type='hidden' name='action' value='edit'>";
	print "<input type='hidden' id='h2g2-form-name' name='form-name' value='" . $activeFrom . "'>";
	print '<input type="submit" id="H2G2-button-modify" class="butAction" value="' . $langs->trans("Modify") . '">';
	print "</form>";
}

print "</div>";

print '<div id="tutorial-overlay" class="H2G2-hidden"><div id="H2G2-highlight"></div>';
print "<div class='H2G2-alert'>";
print '<p id="H2G2-help-text"></p>';
print '<div><button type="button" class="butAction" onclick="hideTutorialOverlay()">' . $langs->trans("H2G2Close") . '</button>';
print '<button type="button" id="H2G2-button-help-next" class="butAction" onclick="hideTutorialOverlay()">' . $langs->trans("Next") . '</button></div>';
print '</div></div>';


$ret .= '</div>';

print "<br><br><hr>";

print "<div>";
print "<h3>" . $langs->trans("H2G2SetupExampleItems") . "</h3>";
print "<table class='noborder centpercent'>";
print "<tr><th>" . $langs->trans("H2G2SetupExampleObject") . "</th>";
print "<th align='left'>" . $langs->trans("H2G2SetupExampleFunction") . "</th>";
print "<th align='left'>" . $langs->trans("H2G2SetupExampleDescription") . "</th>";
print "</tr>";
print getExampleLine("H2G2FormSetupItem", "setAsYesNo"); // Set as checkbox
print getExampleLine("H2G2FormSetupItem", "setAsString"); // Set as string
print getExampleLine("H2G2FormSetupItem", "setAsTitle"); // Set as title
print getExampleLine("H2G2FormSetupItem", "setAsTextarea"); // Set as text area
print getExampleLine("H2G2FormSetupItem", "setAsHtml"); // Set as html
print getExampleLine("", "setFullSize"); // Set as full size
print getExampleLine("", "setHeight"); // Set height
print getExampleLine("", "setAutoSave"); // Add save button
print getExampleLine("H2G2FormSetupItem", "setAsSelect"); // Set as select
print getExampleLine("H2G2FormSetupItem", "setAsMultiSelect"); // Set as multi select
print getExampleLine("H2G2FormSetupItem", "setAsImage"); // Set as image
print getExampleLine("H2G2FormSetupItem", "setAsColor"); // Set as color
print getExampleLine("H2G2FormSetupItem", "setAsCategory"); // Set as category
print getExampleLine("H2G2FormSetupItem", "setAsProduct"); // Set as product
print getExampleLine("H2G2FormSetupItem", "setAsEmailTemplate"); // Set as email template
print getExampleLine("H2G2FormSetupItem", "setAsThirdpartyType"); // Set as thirdparty type
print getExampleLine("H2G2FormSetupItem", "setAsOther"); // Set as other
print getExampleLine("H2G2FormSetupItem", "setHelp"); // Set the item's help text

print "</table>";
print "</div>";
/*
 * Example 1 : Basic button - Font awesome
 */

print '</div>';

// Close grid layout
print '</div>';

// End of page
llxFooter();
$db->close();

/**
 * Print function line
 *
 * @param  string    $object      Target object
 * @param  string    $function    Function name ($transKey = 'H2G2SetupExampleFunction' . $function' | Help tooltip trans key = $transKey + 'Tooltip')
 * @return string
 */
function getExampleLine($object, $function)
{
	global $langs, $db;

	$transKey = "H2G2SetupExampleFunction" . ucfirst($function);
	$transKeyHelp = $transKey . "Tooltip";

	$form = new Form($db);

	$line = '<tr>';
	$line .= "<td align='center'>$object</td>";
	$line .= "<td>$function()</td>";
	$line .= '<td>' . $langs->trans($transKey);
	if ($langs->trans($transKeyHelp) !== $transKeyHelp) {
		// TODO : Replace by h2g2 tooltip function
		$line .= $form->textwithpicto('', $langs->trans($transKeyHelp), 1, 'info', 'h2g2-pointer', 0, 3);
	}
	$line .= '</td>';
	$line .= '</tr>';

	return $line;
}
