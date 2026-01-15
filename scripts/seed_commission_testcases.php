<?php

$isCli = (PHP_SAPI === 'cli');
if ($isCli) {
	print "This script must be executed from the browser only.\n";
	exit(1);
}

require __DIR__ . '/../../..//main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

if (!empty($langs)) {
	$langs->loadLangs(array('clichaumeil@clichaumeil'));
}

if (!$isCli) {
	if (empty($user) || empty($user->id) || empty($user->admin)) {
		accessforbidden();
	}
}

$entity = (int) $conf->entity;

$outputLines = array();
function outputLine(string $line, array &$outputLines): void
{
	$outputLines[] = $line;
}

function renderOutput(array $lines): string
{
	$escaped = array();
	foreach ($lines as $line) {
		$escaped[] = dol_escape_htmltag($line);
	}
	return implode('<br>', $escaped);
}

function renderSeedStyles(): string
{
	return '<style>
	:root{
		--seed-bg:#f8f8f8;
		--seed-ink:#222;
		--seed-muted:#666;
		--seed-accent:#3d5ca5;
		--seed-accent-soft:#e9edf8;
		--seed-card:#ffffff;
		--seed-border:#ddd;
		--seed-shadow:0 6px 18px rgba(0, 0, 0, 0.08);
	}
	.clichaumeil-seed{
		max-width:980px;
		margin:24px auto 40px;
		padding:0 16px;
		color:var(--seed-ink);
	}
	.seed-hero{
		background:var(--seed-card);
		border:1px solid var(--seed-border);
		border-radius:10px;
		padding:18px 20px;
		box-shadow:var(--seed-shadow);
	}
	.seed-hero h1{
		margin:0 0 6px;
		font-size:20px;
	}
	.seed-hero p{
		margin:0;
		color:var(--seed-muted);
		font-size:14px;
	}
	.seed-grid{
		display:grid;
		grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
		gap:16px;
		margin-top:18px;
	}
	.seed-card{
		background:var(--seed-card);
		border:1px solid var(--seed-border);
		border-radius:10px;
		padding:16px 18px;
		box-shadow:var(--seed-shadow);
	}
	.seed-title{
		font-weight:600;
		margin-bottom:8px;
		font-size:14px;
		text-transform:uppercase;
		letter-spacing:0.8px;
		color:var(--seed-muted);
	}
	.seed-list{
		margin:8px 0 0 18px;
		color:var(--seed-ink);
	}
	.seed-pill{
		display:inline-flex;
		align-items:center;
		padding:6px 10px;
		border-radius:14px;
		background:var(--seed-accent-soft);
		color:var(--seed-accent);
		font-size:12px;
		margin:4px 6px 0 0;
	}
	.seed-actions{
		margin-top:18px;
		display:flex;
		gap:12px;
		align-items:center;
	}
	.seed-actions .button{
		border-radius:6px;
	}
	.seed-output{
		background:#1c1c1c;
		color:#f1f1f1;
		border-radius:8px;
		padding:14px;
		font-family:"Courier New",Courier,monospace;
		font-size:12.5px;
		line-height:1.6;
	}
	.seed-output .seed-meta{
		color:#cfcfcf;
		margin-bottom:8px;
	}
	</style>';
}

if (!$isCli && GETPOST('confirm', 'alpha') !== 'yes') {
	llxHeader('', $langs->trans('CliChaumeilTestSeedTitle'));
	print renderSeedStyles();
	print '<div class="clichaumeil-seed">';
	print '<div class="seed-hero">';
	print '<h1>' . $langs->trans('CliChaumeilTestSeedTitle') . '</h1>';
	print '<p>' . $langs->trans('CliChaumeilTestSeedIntro') . '</p>';
	print '</div>';
	print '<div class="seed-grid">';
	print '<div class="seed-card">';
	print '<div class="seed-title">' . $langs->trans('CliChaumeilTestSeedWhatHappens') . '</div>';
	print '<ul class="seed-list">';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseExisting') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseBackToNew') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseProspect') . '</li>';
	print '<li>' . $langs->trans('CliChaumeilTestSeedCaseMulti') . '</li>';
	print '</ul>';
	print '</div>';
	print '<div class="seed-card">';
	print '<div class="seed-title">' . $langs->trans('CliChaumeilTestSeedNamesIntro') . '</div>';
	print '<div class="opacitymedium">' . $langs->trans('CliChaumeilTestSeedNamesList', 'UTest Existing', 'UTest BackToNew', 'UTest Prospect', 'UTest MultiTags') . '</div>';
	print '<div>';
	print '<span class="seed-pill">UTest Existing</span>';
	print '<span class="seed-pill">UTest BackToNew</span>';
	print '<span class="seed-pill">UTest Prospect</span>';
	print '<span class="seed-pill">UTest MultiTags</span>';
	print '</div>';
	print '</div>';
	print '</div>';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="confirm" value="yes">';
	print '<div class="seed-actions">';
	print '<input class="button button-save" type="submit" value="' . $langs->trans('CliChaumeilTestSeedConfirm') . '">';
	print '</div>';
	print '</form>';
	print '</div>';
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
		$sql .= " WHERE nom = '" . $db->escape($uniqueName) . "'";
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

function createThirdparty(DoliDB $db, User $user, string $name, int $clientType, int $entity, array &$outputLines): Societe
{
	$thirdparty = new Societe($db);
	$thirdparty->name = findUniqueThirdpartyName($db, $name, $entity);
	$thirdparty->client = $clientType;
	$thirdparty->code_client = -1; // auto

	$result = $thirdparty->create($user);
	if ($result <= 0) {
		print $GLOBALS['langs']->trans('CliChaumeilTestSeedErrCreateThirdparty', $name, $thirdparty->error) . "\n";
		exit(1);
	}

	outputLine($GLOBALS['langs']->trans('CliChaumeilTestSeedCreatedThirdparty', $thirdparty->id, $thirdparty->name, $clientType), $outputLines);

	return $thirdparty;
}

function addCategoryToThirdparty(DoliDB $db, int $categoryId, Societe $thirdparty, array &$outputLines): void
{
	if ($categoryId <= 0) {
		return;
	}
	$category = new Categorie($db);
	if ($category->fetch($categoryId) <= 0) {
		print $GLOBALS['langs']->trans('CliChaumeilTestSeedErrFetchCategory', $categoryId, $category->error) . "\n";
		exit(1);
	}
	$result = $category->add_type($thirdparty, 'customer');
	if ($result < 0 && $result != -3) {
		print $GLOBALS['langs']->trans('CliChaumeilTestSeedErrAttachCategory', $category->label, $thirdparty->name, $category->error) . "\n";
		exit(1);
	}
	if ($result == -3) {
		outputLine($GLOBALS['langs']->trans('CliChaumeilTestSeedCategoryAlready', $category->label, $thirdparty->id, $thirdparty->name), $outputLines);
		return;
	}

	outputLine($GLOBALS['langs']->trans('CliChaumeilTestSeedCategoryAttached', $category->label, $thirdparty->id, $thirdparty->name), $outputLines);
}

function createInvoice(DoliDB $db, User $user, int $thirdpartyId, int $date, array &$outputLines): int
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
		print $GLOBALS['langs']->trans('CliChaumeilTestSeedErrCreateInvoice', $thirdpartyId, $invoice->error) . "\n";
		exit(1);
	}

	$lineResult = $invoice->addline('Test line', 100, 1, 0);
	if ($lineResult < 0) {
		print $GLOBALS['langs']->trans('CliChaumeilTestSeedErrAddInvoiceLine', $invoice->id, $invoice->error) . "\n";
		exit(1);
	}

	outputLine($GLOBALS['langs']->trans('CliChaumeilTestSeedCreatedInvoice', $invoice->id, $thirdpartyId, dol_print_date($date, '%Y-%m-%d')), $outputLines);

	return (int) $invoice->id;
}

$catNew = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_NOUVEAU', $entity);
$catExisting = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_ANCIEN', $entity);
$catPublic = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_MARCHE_PUBLIC', $entity);
$catSub = getCategoryIdByRefExt($db, 'CLICHAUMEIL_CAT_SOUS_TRAITANCE', $entity);

if (empty($catNew) || empty($catExisting) || empty($catPublic) || empty($catSub)) {
	print $langs->trans('CliChaumeilTestSeedMissingCategories') . "\n";
	exit(1);
}

$now = dol_now();

// Case 1: Should become Existing (Ancien)
outputLine($langs->trans('CliChaumeilTestSeedHeaderExisting'), $outputLines);
$tpExisting = createThirdparty($db, $user, 'UTest Existing', 1, $entity, $outputLines);
createInvoice($db, $user, $tpExisting->id, dol_time_plus_duree($now, -13, 'm'), $outputLines);
createInvoice($db, $user, $tpExisting->id, dol_time_plus_duree($now, -2, 'm'), $outputLines);
addCategoryToThirdparty($db, $catNew, $tpExisting, $outputLines);

// Case 2: Was Existing, should become New (inactive >= 18 months)
outputLine($langs->trans('CliChaumeilTestSeedHeaderBackToNew'), $outputLines);
$tpBackToNew = createThirdparty($db, $user, 'UTest BackToNew', 1, $entity, $outputLines);
createInvoice($db, $user, $tpBackToNew->id, dol_time_plus_duree($now, -24, 'm'), $outputLines);
createInvoice($db, $user, $tpBackToNew->id, dol_time_plus_duree($now, -19, 'm'), $outputLines);
addCategoryToThirdparty($db, $catExisting, $tpBackToNew, $outputLines);

// Case 3: Prospect with no invoices (should become New)
outputLine($langs->trans('CliChaumeilTestSeedHeaderProspect'), $outputLines);
$tpProspect = createThirdparty($db, $user, 'UTest Prospect', 2, $entity, $outputLines);

// Case 4: Multiple categories (manual tags + New)
outputLine($langs->trans('CliChaumeilTestSeedHeaderMulti'), $outputLines);
$tpMulti = createThirdparty($db, $user, 'UTest MultiTags', 1, $entity, $outputLines);
addCategoryToThirdparty($db, $catPublic, $tpMulti, $outputLines);
addCategoryToThirdparty($db, $catSub, $tpMulti, $outputLines);
addCategoryToThirdparty($db, $catNew, $tpMulti, $outputLines);
createInvoice($db, $user, $tpMulti->id, dol_time_plus_duree($now, -13, 'm'), $outputLines);
createInvoice($db, $user, $tpMulti->id, dol_time_plus_duree($now, -2, 'm'), $outputLines);

outputLine($langs->trans('CliChaumeilTestSeedSummary'), $outputLines);
outputLine($langs->trans('CliChaumeilTestSeedSummaryExisting', $tpExisting->id, $tpExisting->name), $outputLines);
outputLine($langs->trans('CliChaumeilTestSeedSummaryNew', $tpBackToNew->id, $tpBackToNew->name), $outputLines);
outputLine($langs->trans('CliChaumeilTestSeedSummaryNew', $tpProspect->id, $tpProspect->name), $outputLines);
outputLine($langs->trans('CliChaumeilTestSeedSummaryMulti', $tpMulti->id, $tpMulti->name), $outputLines);
outputLine($langs->trans('CliChaumeilTestSeedRunCron'), $outputLines);

if (!$isCli) {
	llxHeader('', $langs->trans('CliChaumeilTestSeedTitle'));
	print renderSeedStyles();
	print '<div class="clichaumeil-seed">';
	print '<div class="seed-hero">';
	print '<h1>' . $langs->trans('CliChaumeilTestSeedTitle') . '</h1>';
	print '<p>' . $langs->trans('CliChaumeilTestSeedIntro') . '</p>';
	print '</div>';
	print '<div class="seed-grid">';
	print '<div class="seed-card">';
	print '<div class="seed-title">' . $langs->trans('CliChaumeilTestSeedResultTitle') . '</div>';
	print '<div class="seed-output">';
	print '<div class="seed-meta">' . dol_escape_htmltag($langs->trans('CliChaumeilTestSeedWhatHappens')) . '</div>';
	print renderOutput($outputLines);
	print '</div>';
	print '</div>';
	print '<div class="seed-card">';
	print '<div class="seed-title">' . $langs->trans('CliChaumeilTestSeedNamesIntro') . '</div>';
	print '<div class="opacitymedium">' . $langs->trans('CliChaumeilTestSeedNamesList', 'UTest Existing', 'UTest BackToNew', 'UTest Prospect', 'UTest MultiTags') . '</div>';
	print '<div>';
	print '<span class="seed-pill">UTest Existing</span>';
	print '<span class="seed-pill">UTest BackToNew</span>';
	print '<span class="seed-pill">UTest Prospect</span>';
	print '<span class="seed-pill">UTest MultiTags</span>';
	print '</div>';
	print '</div>';
	print '</div>';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="confirm" value="yes">';
	print '<div class="seed-actions">';
	print '<input class="button" type="submit" value="' . $langs->trans('CliChaumeilTestSeedConfirm') . '">';
	print '</div>';
	print '</form>';
	print '</div>';
	llxFooter();
}
