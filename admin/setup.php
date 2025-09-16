<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    clichaumeil/admin/setup.php
 * \ingroup clichaumeil
 * \brief   Clichaumeil setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
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

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formmail.class.php";
require_once '../lib/clichaumeil.lib.php';
//require_once "../class/myclass.class.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "clichaumeil@clichaumeil"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
/** @var HookManager $hookmanager */
$hookmanager->initHooks(array('clichaumeilsetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';
$form = new Form($db);
$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

// Access control
if (!$user->admin) {
	accessforbidden();
}




// --- 1. Define constants for robustness ---
const REVIEW_YEAR_DELAY_KEY = 'CLICHAUMEIL_REVIEW_YEAR_DELAY';
const PRICING_MANAGERS_KEY = 'CLICHAUMEIL_PRICING_UPDATE_MANAGERS';
const EMAIL_TEMPLATE_KEY = 'CLICHAUMEIL_CRON_EMAIL_TEMPLATE';
const NOTIF_USERS_KEY = 'CLICHAUMEIL_CRON_NOTIF_USERS';




// --- 3. Build the form in a clean and readable way ---

// --- Field 1: Delay in years (Numeric) ---
$item = $formSetup->newItem(REVIEW_YEAR_DELAY_KEY);
$item->fieldAttr = [
    'type' => 'number',
    'min'  => 0,
    'step' => 1,
];
$item->defaultFieldValue = 1;


// --- Field 2: Responsible Managers (User Select) ---
buildUserMultiSelectField($formSetup, $form, PRICING_MANAGERS_KEY);


// --- Field 3: Email Template (Dropdown) ---
$formmail = new FormMail($db);
$formmail->fetchAllEMailTemplate('contract', $user, $langs);

// Using array_column to concisely create the [id => label] array
$templates = !empty($formmail->lines_model) ? array_column($formmail->lines_model, 'label', 'id') : [];

$item = $formSetup->newItem(EMAIL_TEMPLATE_KEY)->setAsSelect($templates);


// --- Field 4: Users to Notify (User Select) ---
buildUserMultiSelectField($formSetup, $form, NOTIF_USERS_KEY);


$setupnotempty += count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$moduledir = 'clichaumeil';
$myTmpObjects = array();
// TODO Scan list of objects to fill this array
$myTmpObjects['myobject'] = array('label' => 'MyObject', 'includerefgeneration' => 0, 'includedocgeneration' => 0, 'class' => 'MyObject');

$tmpobjectkey = GETPOST('object', 'aZ09');
if ($tmpobjectkey && !array_key_exists($tmpobjectkey, $myTmpObjects)) {
	accessforbidden('Bad value for object. Hack attempt ?');
}


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if($action == 'update') {
	header('Location: '.$_SERVER["PHP_SELF"]);
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "ClichaumeilSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "clichaumeil@clichaumeil");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("ClichaumeilSetupPage").'</span><br><br>';

if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}


if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();

/**
* Builds a multi-select field for active users.
*
* @param FormSetup $formSetup The FormSetup object.
* @param Form      $form      The Form object.
* @param string    $key       The configuration key.
* @return void
*/
function buildUserMultiSelectField(FormSetup $formSetup, Form $form, string $key): void
{
// Get the current value for pre-selection
$currentValue = getDolGlobalString($key);
$selectedUsers = !empty($currentValue) ? explode(',', $currentValue) : [];

$item = $formSetup->newItem($key)->setAsMultiSelect([]);

// The filter to select only active employees
$userFilter = '(employee:=:1) AND (u.statut:=:1)';

// The call to select_dolusers is more readable with variables
$item->fieldInputOverride = $form->select_dolusers(
$selectedUsers, // Already selected users
$key,           // HTML field name
1,              // Enable multi-select
null,           // Exclude users (none here)
0,              // Field size
'',             // Additional CSS class
'', '', 0, 0,
$userFilter,    // SQL filter
0, '', '', 0, 0,
true,           // Show empty field option
0
);
}
