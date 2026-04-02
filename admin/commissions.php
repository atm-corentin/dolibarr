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
 * \file    clichaumeil/admin/commissions.php
 * \ingroup clichaumeil
 * \brief   Commission setup page.
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

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once '../lib/clichaumeil.lib.php';
require_once __DIR__ . '/../class/CliChaumeilCommissionConfig.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "clichaumeil@clichaumeil"));

$hookmanager->initHooks(array('clichaumeilsetup', 'globalsetup'));

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$form = new Form($db);
$setupnotempty = 0;

if (!$user->admin) {
	accessforbidden();
}

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

// Coefficients
$item = $formSetup->newItem('CliChaumeilCommissionsSectionCommercialCoefficients')->setAsTitle();
$coefficients = CliChaumeilCommissionConfig::getDefaultCommercialCoefficients();
foreach ($coefficients as $constKey => $defaultValue) {
	$item = $formSetup->newItem($constKey);
	$item->fieldAttr = array(
		'type' => 'number',
		'min' => 0,
		'step' => '0.01',
	);
	$item->defaultFieldValue = $defaultValue;
}

$item = $formSetup->newItem('CliChaumeilCommissionsSectionPrintManagementCoefficients')->setAsTitle();
$printManagementCoefficients = CliChaumeilCommissionConfig::getDefaultPrintManagementCoefficients();
foreach ($printManagementCoefficients as $constKey => $defaultValue) {
	$item = $formSetup->newItem($constKey);
	$item->fieldAttr = array(
		'type' => 'number',
		'min' => 0,
		'step' => '0.01',
	);
	$item->defaultFieldValue = $defaultValue;
}

// Categories (customer/prospect)
$categories = new Categorie($db);
$allCategories = $categories->get_full_arbo(Categorie::TYPE_CUSTOMER);
$categoryOptions = array('' => $langs->trans('None'));
if (is_array($allCategories)) {
	foreach ($allCategories as $cat) {
		$categoryOptions[$cat['rowid']] = $cat['label'];
	}
}

$item = $formSetup->newItem('CliChaumeilCommissionsSectionCategories')->setAsTitle();
$item = $formSetup->newItem(CliChaumeilCommissionConfig::CAT_MARCHE_PUBLIC)->setAsSelect($categoryOptions);
$item = $formSetup->newItem(CliChaumeilCommissionConfig::CAT_SOUS_TRAITANCE)->setAsSelect($categoryOptions);
$item = $formSetup->newItem(CliChaumeilCommissionConfig::CAT_NOUVEAU)->setAsSelect($categoryOptions);
$item->helpText = $langs->trans('CliChaumeilCommissionsCronHelp');
$item = $formSetup->newItem(CliChaumeilCommissionConfig::CAT_ANCIEN)->setAsSelect($categoryOptions);
$item->helpText = $langs->trans('CliChaumeilCommissionsCronHelp');

// Groups
$groupOptions = array('' => $langs->trans('None'));
$sql = "SELECT rowid, nom FROM " . $db->prefix() . "usergroup";
$sql .= " WHERE entity IN (0, " . ((int) $conf->entity) . ")";
$sql .= " ORDER BY nom";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$groupOptions[$obj->rowid] = $obj->nom;
	}
}

$item = $formSetup->newItem('CliChaumeilCommissionsSectionGroups')->setAsTitle();
$item = $formSetup->newItem(CliChaumeilCommissionConfig::GROUP_COMMERCIAL)->setAsSelect($groupOptions);
$item = $formSetup->newItem(CliChaumeilCommissionConfig::GROUP_MANAGER_COMMERCIAL)->setAsSelect($groupOptions);
$item = $formSetup->newItem(CliChaumeilCommissionConfig::GROUP_PRINT_MANAGER)->setAsSelect($groupOptions);
$item = $formSetup->newItem(CliChaumeilCommissionConfig::GROUP_PRINT_MANAGEMENT)->setAsSelect($groupOptions);

if ($action !== 'update') {
	$missingCategoryKeys = array();
	$categoryConstants = array(
		CliChaumeilCommissionConfig::CAT_MARCHE_PUBLIC,
		CliChaumeilCommissionConfig::CAT_SOUS_TRAITANCE,
		CliChaumeilCommissionConfig::CAT_NOUVEAU,
		CliChaumeilCommissionConfig::CAT_ANCIEN,
	);
	$categoryChecker = new Categorie($db);
	$customerCategoryType = isset($categoryChecker->MAP_ID['customer']) ? (int) $categoryChecker->MAP_ID['customer'] : (int) Categorie::TYPE_CUSTOMER;
	foreach ($categoryConstants as $constKey) {
		$catId = getDolGlobalInt($constKey);
		if (empty($catId)) {
			$missingCategoryKeys[] = $langs->trans($constKey);
			continue;
		}
		if ($categoryChecker->fetch($catId) <= 0 || (int) $categoryChecker->type !== $customerCategoryType) {
			$missingCategoryKeys[] = $langs->trans($constKey);
		}
	}

	if (!empty($missingCategoryKeys)) {
		setEventMessages($langs->transnoentitiesnoconv('CliChaumeilCommissionsMissingCategoryConfig', implode(', ', $missingCategoryKeys)), null, 'warnings');
	}

	$missingGroupKeys = array();
	$groupConstants = array(
		CliChaumeilCommissionConfig::GROUP_COMMERCIAL,
		CliChaumeilCommissionConfig::GROUP_MANAGER_COMMERCIAL,
		CliChaumeilCommissionConfig::GROUP_PRINT_MANAGER,
		CliChaumeilCommissionConfig::GROUP_PRINT_MANAGEMENT,
	);
	foreach ($groupConstants as $constKey) {
		if (empty(getDolGlobalInt($constKey))) {
			$missingGroupKeys[] = $langs->trans($constKey);
		}
	}
	if (!empty($missingGroupKeys)) {
		setEventMessages($langs->transnoentitiesnoconv('CliChaumeilCommissionsMissingGroupConfig', implode(', ', $missingGroupKeys)), null, 'warnings');
	}
}

$setupnotempty += count($formSetup->items);

if ($action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

$help_url = '';
$title = "CliChaumeilCommissions";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'commissions', $langs->trans($title), -1, "clichaumeil@clichaumeil");

echo '<span class="opacitymedium">' . $langs->trans("CliChaumeilCommissionsSetupPage") . '</span><br>';
echo '<span class="opacitymedium">' . $langs->trans("CliChaumeilCommissionsCronIntro") . '</span><br><br>';

if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}

if (empty($setupnotempty)) {
	print '<br>' . $langs->trans("NothingToSetup");
}

print dol_get_fiche_end();

llxFooter();
$db->close();
