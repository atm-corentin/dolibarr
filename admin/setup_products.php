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
 * \file    clichaumeil/admin/setup_products.php
 * \ingroup clichaumeil
 * \brief   Clichaumeil product settings page.
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
require_once '../lib/clichaumeil.lib.php';
require_once __DIR__ . '/../class/CliChaumeilProductCost.class.php';
require_once __DIR__ . '/../class/CliChaumeilPropalDefaultLineConfig.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

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

// Access control
if (!$user->admin) {
	accessforbidden();
}

$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

// --- Field: Default overhead rate (%) ---
$item = $formSetup->newItem('CLICHAUMEIL_DEFAULT_OVERHEAD_RATE');
$item->fieldAttr = [
	'type' => 'number',
	'min' => 0,
	'step' => '0.0001',
];
$item->defaultFieldValue = CliChaumeilProductCostCalculator::getDefaultOverheadRate();

// --- Field: Default proposal products/services ---
$defaultProposalProducts = buildDefaultPropalProductsFieldOptions($db);
$item = $formSetup->newItem('CLICHAUMEIL_DEFAULT_PROPAL_PRODUCT_ID')->setAsMultiSelect($defaultProposalProducts);
$item->defaultFieldValue = getDolGlobalString('CLICHAUMEIL_DEFAULT_PROPAL_PRODUCT_ID');
$item->cssClass = 'minwidth300 widthcentpercentminusxx';
$item->helpText = $langs->transnoentities('CLICHAUMEIL_DEFAULT_PROPAL_PRODUCT_IDTooltip');

// --- Field: Category for Height/Length fields ---
$categories = new Categorie($db);
$allCat = $categories->get_full_arbo(Categorie::TYPE_PRODUCT);

$arrayCat = array();
if (is_array($allCat) && !empty($allCat)) {
	foreach ($allCat as $cat) {
		$arrayCat[$cat['rowid']] = $cat['label'];
	}
}
$item = $formSetup->newItem('CLICHAUMEIL_PRODUCT_TARGET_CATEGORY')->setAsMultiSelect($arrayCat);

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

$help_url = '';
$title = "ClichaumeilSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

$linkback = '<a href="' . ($backtopage ? dol_escape_htmltag($backtopage) : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'products', $langs->trans($title), -1, "clichaumeil@clichaumeil");

echo '<span class="opacitymedium">' . $langs->trans("CliChaumeilProductsSetupPage") . '</span><br><br>';

print $formSetup->generateOutput(true);
print '<br>';

print dol_get_fiche_end();

llxFooter();
$db->close();

/**
 * Build available product/service options for the default proposal lines setup.
 *
 * Active catalog items are listed, while already configured inactive products are
 * kept available to preserve existing configuration values.
 *
 * @param DoliDB $db Database handler.
 * @return array<int,string>
 */
function buildDefaultPropalProductsFieldOptions(DoliDB $db): array
{
	$configReader = new CliChaumeilPropalDefaultLineConfig($db);
	$configuredIds = $configReader->getConfiguredProductIds();
	$options = array();

	$sql = 'SELECT p.rowid, p.ref, p.label, p.fk_product_type, p.tosell';
	$sql .= ' FROM ' . $db->prefix() . 'product AS p';
	$sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
	$sql .= ' AND p.tosell = 1';
	$sql .= ' ORDER BY p.ref ASC';

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$options[(int) $obj->rowid] = buildDefaultPropalProductOptionLabel(
				(string) $obj->ref,
				(string) $obj->label,
				(int) $obj->fk_product_type,
				false
			);
		}
		$db->free($resql);
	} else {
		dol_syslog(__FUNCTION__ . ' failed to load active product options: ' . $db->lasterror(), LOG_ERR);
	}

	foreach ($configuredIds as $configuredId) {
		if (isset($options[$configuredId])) {
			continue;
		}

		$product = $configReader->fetchConfiguredProductById($configuredId);
		if ($product === null) {
			continue;
		}

		$options[$configuredId] = buildDefaultPropalProductOptionLabel(
			(string) $product->ref,
			(string) $product->label,
			(int) $product->type,
			!(bool) $product->tosell
		);
	}

	return $options;
}

/**
 * Build one setup option label for a product/service.
 *
 * @param string $ref Reference.
 * @param string $label Label.
 * @param int    $productType Product type.
 * @param bool   $isInactive Whether the product is inactive.
 * @return string
 */
function buildDefaultPropalProductOptionLabel(string $ref, string $label, int $productType, bool $isInactive): string
{
	global $langs;

	$typeLabel = ($productType === Product::TYPE_SERVICE) ? $langs->trans('Service') : $langs->trans('Product');
	$optionLabel = trim($ref . ' - ' . $label . ' (' . $typeLabel . ')');
	if ($isInactive) {
		$optionLabel .= ' [' . $langs->trans('Disabled') . ']';
	}

	return $optionLabel;
}
