<?php

$isCli = (PHP_SAPI === 'cli');
if ($isCli) {
	define('NOSESSION', 1);
	define('NOREQUIREMENU', 1);
	define('NOREQUIREHTML', 1);
	define('NOREQUIREAJAX', 1);
	define('NOREQUIRETRAN', 1);
}

require __DIR__ . '/../../..//main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

if (!$isCli) {
	if (empty($user) || empty($user->id) || empty($user->admin)) {
		accessforbidden();
	}
	$langs->loadLangs(array('clichaumeil@clichaumeil'));
}

if ($isCli && (empty($user) || empty($user->id))) {
	$user = new User($db);
	$user->fetch(1);
}

$entity = (int) $conf->entity;

$outputLines = array();
function outputLine(string $line, bool $isCli, array &$outputLines): void
{
	if ($isCli) {
		print $line . "\n";
		return;
	}
	$outputLines[] = $line;
}

if (!$isCli && GETPOST('confirm', 'alpha') !== 'yes') {
	llxHeader('', $langs->trans('CliChaumeilTestSeedTitle'));
	print '<div class="notice">' . $langs->trans('CliChaumeilTestSeedIntro') . '</div>';
	print '<div class="notice">';
	print '<strong>' . $langs->trans('CliChaumeilTestSeedWhatHappens') . '</strong>';
	print '<ul class="marginleftonly">';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseExisting') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseBackToNew') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseProspect') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseMulti') . '</li>';
	print '</ul>';
	print '</div>';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="confirm" value="yes">';
	print '<input class="button button-save" type="submit" value="' . $langs->trans('CliChaumeilTestSeedConfirm') . '">';
	print '</form>';
	llxFooter();
	exit;
}

function getCategoryIdByRefExt(DoliDB $db, string $refExt, int $entity): int
{
	$sql = "SELECT rowid FROM " . $db->prefix() . "categorie";
	$sql .= " WHERE ref_ext = '" . $db->escape($refExt) . "'";
	$sql .= " AND entity = " . $entity;
	$sql .= " AND type = 2"; // customer
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if (!empty($obj->rowid)) {
			return (int) $obj->rowid;
		}
	}
	return 0;
}

function findUniqueThirdpartyName(DoliDB $db, string $baseName, int $entity): string
{
	$counter = 1;
	$uniqueName = $baseName;
	while (true) {
		$sql = "SELECT rowid FROM " . $db->prefix() . "societe";
		$sql .= " WHERE name = '" . $db->escape($uniqueName) . "'";
		$sql .= " AND entity = " . $entity;
		$resql = $db->query($sql);
		if (!$resql) {
			return $uniqueName;
		}
		if ($db->num_rows($resql) === 0) {
			return $uniqueName;
		}
		$counter++;
		$uniqueName = $baseName . ' ' . $counter;
	}
}

function createThirdparty(DoliDB $db, User $user, string $name, int $clientType, bool $isCli, array &$outputLines): Societe
{
	$thirdparty = new Societe($db);
	$thirdparty->name = findUniqueThirdpartyName($db, $name, (int) $GLOBALS['conf']->entity);
	$thirdparty->client = $clientType;
	$thirdparty->code_client = -1; // auto

	$result = $thirdparty->create($user);
	if ($result <= 0) {
		print "Failed to create thirdparty {$name}: {$thirdparty->error}\n";
		exit(1);
	}

	outputLine("Created thirdparty #{$thirdparty->id} {$thirdparty->name} (type={$clientType})", $isCli, $outputLines);

	return $thirdparty;
}

function addCategoryToThirdparty(DoliDB $db, int $categoryId, Societe $thirdparty, bool $isCli, array &$outputLines): void
{
	if ($categoryId <= 0) {
		return;
	}
	$category = new Categorie($db);
	if ($category->fetch($categoryId) <= 0) {
		print "Failed to fetch category ID {$categoryId}: {$category->error}\n";
		exit(1);
	}
	$result = $category->add_type($thirdparty, 'customer');
	if ($result < 0 && $result != -3) {
		print "Failed to attach category {$category->label} to {$thirdparty->name}: {$category->error}\n";
		exit(1);
	}
	if ($result == -3) {
		outputLine("Category '{$category->label}' already attached to #{$thirdparty->id} {$thirdparty->name}", $isCli, $outputLines);
		return;
	}

	outputLine("Attached category '{$category->label}' to #{$thirdparty->id} {$thirdparty->name}", $isCli, $outputLines);
}

function createInvoice(DoliDB $db, User $user, int $thirdpartyId, int $date, bool $isCli, array &$outputLines): int
{
	$invoice = new Facture($db);
	$invoice->socid = $thirdpartyId;
	$invoice->type = Facture::TYPE_STANDARD;
	$invoice->date = $date;
	$invoice->datef = $date;
	$invoice->cond_reglement_id = 0;
	$invoice->mode_reglement_id = 0;

	$result = $invoice->create($user);
	if ($result <= 0) {
		print "Failed to create invoice for thirdparty {$thirdpartyId}: {$invoice->error}\n";
		exit(1);
	}

	$lineResult = $invoice->addline('Test line', 100, 1, 0);
	if ($lineResult < 0) {
		print "Failed to add invoice line for invoice {$invoice->id}: {$invoice->error}\n";
		exit(1);
	}

	outputLine("Created invoice #{$invoice->id} for thirdparty #{$thirdpartyId} (date=" . dol_print_date($date, '%Y-%m-%d') . ")", $isCli, $outputLines);

	return (int) $invoice->id;
}

$catNew = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_NOUVEAU', $entity);
$catExisting = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_ANCIEN', $entity);
$catPublic = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_MARCHE_PUBLIC', $entity);
$catSub = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_SOUS_TRAITANCE', $entity);

if (empty($catNew) || empty($catExisting) || empty($catPublic) || empty($catSub)) {
	print "Missing categories. Activate module and ensure categories exist.\n";
	exit(1);
}

$now = dol_now();

// Case 1: Should become Existing (Ancien)
outputLine('Case 1: New -> Existing (two invoices)', $isCli, $outputLines);
$tpExisting = createThirdparty($db, $user, 'UTest Existing', 1, $isCli, $outputLines);
createInvoice($db, $user, $tpExisting->id, dol_time_plus_duree($now, -13, 'm'), $isCli, $outputLines);
createInvoice($db, $user, $tpExisting->id, dol_time_plus_duree($now, -2, 'm'), $isCli, $outputLines);
addCategoryToThirdparty($db, $catNew, $tpExisting, $isCli, $outputLines);

// Case 2: Was Existing, should become New (inactive >= 18 months)
outputLine('Case 2: Existing -> New (inactive >= 18 months)', $isCli, $outputLines);
$tpBackToNew = createThirdparty($db, $user, 'UTest BackToNew', 1, $isCli, $outputLines);
createInvoice($db, $user, $tpBackToNew->id, dol_time_plus_duree($now, -24, 'm'), $isCli, $outputLines);
createInvoice($db, $user, $tpBackToNew->id, dol_time_plus_duree($now, -19, 'm'), $isCli, $outputLines);
addCategoryToThirdparty($db, $catExisting, $tpBackToNew, $isCli, $outputLines);

// Case 3: Prospect with no invoices (should become New)
outputLine('Case 3: Prospect with no invoices (stays New)', $isCli, $outputLines);
$tpProspect = createThirdparty($db, $user, 'UTest Prospect', 2, $isCli, $outputLines);

// Case 4: Multiple categories (manual tags + New)
outputLine('Case 4: Multiple categories + New -> Existing', $isCli, $outputLines);
$tpMulti = createThirdparty($db, $user, 'UTest MultiTags', 1, $isCli, $outputLines);
addCategoryToThirdparty($db, $catPublic, $tpMulti, $isCli, $outputLines);
addCategoryToThirdparty($db, $catSub, $tpMulti, $isCli, $outputLines);
addCategoryToThirdparty($db, $catNew, $tpMulti, $isCli, $outputLines);
createInvoice($db, $user, $tpMulti->id, dol_time_plus_duree($now, -13, 'm'), $isCli, $outputLines);
createInvoice($db, $user, $tpMulti->id, dol_time_plus_duree($now, -2, 'm'), $isCli, $outputLines);

outputLine("Summary:", $isCli, $outputLines);
outputLine("- {$tpExisting->id} {$tpExisting->name} (should become Existing)", $isCli, $outputLines);
outputLine("- {$tpBackToNew->id} {$tpBackToNew->name} (should become New)", $isCli, $outputLines);
outputLine("- {$tpProspect->id} {$tpProspect->name} (should become New)", $isCli, $outputLines);
outputLine("- {$tpMulti->id} {$tpMulti->name} (manual categories + New, should become Existing)", $isCli, $outputLines);
outputLine("Run the cron manually to apply segmentation.", $isCli, $outputLines);

if (!$isCli) {
	llxHeader('', $langs->trans('CliChaumeilTestSeedTitle'));
	print '<div class="notice">' . $langs->trans('CliChaumeilTestSeedIntro') . '</div>';
	print '<div class="notice">';
	print '<strong>' . $langs->trans('CliChaumeilTestSeedWhatHappens') . '</strong>';
	print '<ul class="marginleftonly">';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseExisting') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseBackToNew') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseProspect') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseMulti') . '</li>';
	print '</ul>';
	print '</div>';
	print '<pre>' . dol_escape_htmltag(implode("\n", $outputLines), 0, 1) . '</pre>';
	llxFooter();
}
