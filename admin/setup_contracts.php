<?php
/* Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>
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
 * \file    clichaumeil/admin/setup_contracts.php
 * \ingroup clichaumeil
 * \brief   Clichaumeil contract settings page.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
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
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formmail.class.php";
require_once '../lib/clichaumeil.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "clichaumeil@clichaumeil"));

$hookmanager->initHooks(array('clichaumeilsetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');
$form = new Form($db);

// Access control
if (!$user->admin) {
	accessforbidden();
}

$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

// --- Field: Responsible managers (User Select) ---
buildUserMultiSelectField($formSetup, $form, 'CLICHAUMEIL_PRICING_UPDATE_MANAGERS');

// --- Field: Delay in years (Numeric) ---
$item = $formSetup->newItem('CLICHAUMEIL_REVIEW_YEAR_DELAY');
$item->fieldAttr = [
	'type' => 'number',
	'min' => 0,
	'step' => 1,
];
$item->defaultFieldValue = 1;

// --- Field: Email Template (Dropdown) ---
$formmail = new FormMail($db);
$formmail->fetchAllEMailTemplate('contract', $user, $langs);
$templates = !empty($formmail->lines_model) ? array_column($formmail->lines_model, 'label', 'id') : [];
$formSetup->newItem('CLICHAUMEIL_CRON_EMAIL_TEMPLATE')->setAsSelect($templates);

// --- Field: Users to Notify (User Select) ---
buildUserMultiSelectField($formSetup, $form, 'CLICHAUMEIL_CRON_NOTIF_USERS');

/*
 * Actions
 */

if ($action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

$help_url = '';
$title = "ClichaumeilSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

$linkback = '<a href="' . ($backtopage ? dol_escape_htmltag($backtopage) : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'contracts', $langs->trans($title), -1, "clichaumeil@clichaumeil");

echo '<span class="opacitymedium">' . $langs->trans("CliChaumeilContractsSetupPage") . '</span><br><br>';

print $formSetup->generateOutput(true);
print '<br>';

print dol_get_fiche_end();

llxFooter();
$db->close();
