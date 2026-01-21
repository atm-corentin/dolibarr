<?php
/* Copyright (C) 2004-2017  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Ravi Trébuchet       <ravi@code42.fr>
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
 * \file    customisecard/admin/setup.php
 * \ingroup customisecard
 * \brief   CustomiseCard setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user, $conf, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
dol_include_once('/customisecard/lib/customisecard.lib.php');

// Translations
$langs->loadLangs(array("admin", "customisecard@customisecard"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) require_once __DIR__ . '/../backport/v16/core/class/html.formsetup.class.php';
	else require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);

// Add options
$formSetup->newItem('MAIN_DISABLE_FREE_LINES')->setAsYesNo();
if ($conf->propal->enabled ?? false)
	$formSetup->newItem('CUSTOMISECARD_HIDE_DESCRIPTION_PROPAL')->setAsYesNo();
if ($conf->commande->enabled ?? $conf->order->enabled ?? false)
	$formSetup->newItem('CUSTOMISECARD_HIDE_DESCRIPTION_ORDER')->setAsYesNo();
if ($conf->facture->enabled ?? $conf->invoice->enabled ?? false)
	$formSetup->newItem('CUSTOMISECARD_HIDE_DESCRIPTION_INVOICE')->setAsYesNo();
if ($conf->fournisseur->enabled ?? $conf->supplier->enabled ?? false) {
	$formSetup->newItem('CUSTOMISECARD_HIDE_DESCRIPTION_SUPPLIER_ORDER')->setAsYesNo();
	$formSetup->newItem('CUSTOMISECARD_HIDE_DESCRIPTION_SUPPLIER_INVOICE')->setAsYesNo();
}
$formSetup->newItem('CUSTOMISECARD_HIDE_LIMITED_DURATION')->setAsYesNo();

$setupnotempty = count($formSetup->items);

/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "CustomiseCardPersonalization";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = customisecardAdminPrepareHead();
dol_fiche_head($head, 'personalization', '', -1, "customisecard@customisecard");

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("CustomiseCardPersonalizationPage") . '</span><br><br>';

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>';
	print '</div>';
} else {
	print '<br>' . $langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
