<?php
/* Copyright (C) 2026 ATM Consulting
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
 * \file    clichaumeil/admin/api_connections.php
 * \ingroup clichaumeil
 * \brief   Clichaumeil API connections settings page (ANTALIS).
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
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formsetup.class.php";
require_once '../lib/clichaumeil.lib.php';
require_once __DIR__ . '/../class/SupplierPriceSync/SupplierPriceSyncConstants.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "clichaumeil@clichaumeil"));

$hookmanager->initHooks(array('clichaumeilapiconnections', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Access control
if (!isModEnabled('clichaumeil') || !$user->admin) {
	accessforbidden();
}

// Build the supplier list (active suppliers only).
$supplierOptions = array();
$sql = "SELECT rowid, nom FROM " . $db->prefix() . "societe";
$sql .= " WHERE fournisseur = 1 AND entity IN (" . getEntity('societe') . ")";
$sql .= " ORDER BY nom";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$supplierOptions[$obj->rowid] = $obj->nom;
	}
	$db->free($resql);
}

// The HTTP password is deliberately NOT managed by FormSetup: FormSetup renders
// the stored value in the HTML "value" attribute (clear text in the page source).
// It is handled by a dedicated, never-prefilled form below (see action setantalispassword).
$formSetup = new FormSetup($db);
$formSetup->newItem(SupplierPriceSyncConstants::CONST_BASE_URL)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_HTTP_LOGIN)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_THIRDPARTY_ID)->setAsSelect($supplierOptions);
$formSetup->newItem(SupplierPriceSyncConstants::CONST_CUSTOMER_ID)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_USER_CODE)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_DELIVERY_ADDRESS_ID)->setAsString();

/*
 * Actions
 */

if ($action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'setantalispassword' && !empty($user->admin)) {
	$newPassword = GETPOST(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD, 'alphanohtml');
	if ($newPassword !== '') {
		dolibarr_set_const($db, SupplierPriceSyncConstants::CONST_HTTP_PASSWORD, $newPassword, 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
	}

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

$help_url = '';
$title = "CliChaumeil_ApiConnectionsTab";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

$linkback = '<a href="' . ($backtopage ? dol_escape_htmltag($backtopage) : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'api_connections', $langs->trans("CliChaumeil_AntalisApiTitle"), -1, "clichaumeil@clichaumeil");

echo '<span class="opacitymedium">' . $langs->trans("CliChaumeil_AntalisApiTitle") . '</span><br><br>';

print $formSetup->generateOutput(true);
print '<br>';

// Dedicated HTTP password form: never pre-filled, only updated when a value is submitted.
$hasPassword = (getDolGlobalString(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD) !== '');
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" autocomplete="off">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setantalispassword">';
print '<table class="noborder centpercent"><tr class="liste_titre"><td>' . $langs->trans('CLICHAUMEIL_SUPPLIER_ANTALIS_HTTP_PASSWORD') . '</td><td></td></tr>';
print '<tr class="oddeven"><td>';
print '<input type="password" name="' . SupplierPriceSyncConstants::CONST_HTTP_PASSWORD . '" value="" autocomplete="new-password" class="flat">';
print ' <span class="opacitymedium">' . $langs->trans('CliChaumeil_AntalisPasswordHint') . '</span>';
if ($hasPassword) {
	print ' ' . img_picto('', 'tick', 'class="paddingleft"') . ' ' . $langs->trans('CliChaumeil_AntalisPasswordConfigured');
}
print '</td><td class="right"><input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('Save')) . '"></td></tr>';
print '</table></form>';
print '<br>';

echo '<div class="info">' . $langs->trans("CliChaumeil_AntalisPriceSyncCronComment") . '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
