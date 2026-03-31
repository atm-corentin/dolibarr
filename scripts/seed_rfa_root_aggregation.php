<?php
declare(strict_types=1);

$isCli = (PHP_SAPI === 'cli');

if ($isCli && !defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if ($isCli && !defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');
}
if ($isCli && !defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if ($isCli && !defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

require __DIR__.'/../../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/clichaumeil/class/chaumeilrfa.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/clichaumeil/class/Rfa/RfaGlobalListRepository.php';
require_once DOL_DOCUMENT_ROOT.'/custom/clichaumeil/class/Rfa/RfaGlobalListService.php';

/**
 * Return the CLI option value.
 *
 * @param array<int,string> $argv Command line arguments.
 * @param string $name Option name without leading dashes.
 * @param string $default Default value.
 * @return string
 */
function getCliOption(array $argv, string $name, string $default = ''): string
{
	foreach ($argv as $argument) {
		if (strpos($argument, '--'.$name.'=') === 0) {
			return (string) substr($argument, strlen($name) + 3);
		}
	}

	return $default;
}

/**
 * Throw an exception and log it once.
 *
 * @param string $message Error message.
 * @return never
 * @throws RuntimeException Always thrown.
 */
function fail(string $message): void
{
	dol_syslog(__FILE__.' '.$message, LOG_ERR);
	throw new RuntimeException($message);
}

/**
 * Render basic styles for browser mode.
 *
 * @return string
 */
function renderSeedStyles(): string
{
	return '<style>
	.clichaumeil-seed{max-width:1080px;margin:24px auto 40px;padding:0 16px;color:#222}
	.seed-hero,.seed-card{background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08)}
	.seed-hero{padding:18px 20px}
	.seed-card{padding:16px 18px}
	.seed-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:18px}
	.seed-title{font-weight:600;margin-bottom:8px;font-size:14px;text-transform:uppercase;letter-spacing:.8px;color:#666}
	.seed-list{margin:8px 0 0 18px}
	.seed-actions{margin-top:18px;display:flex;gap:12px;align-items:center}
	.seed-output{background:#1c1c1c;color:#f1f1f1;border-radius:8px;padding:14px;font-family:"Courier New",Courier,monospace;font-size:12.5px;line-height:1.6;white-space:pre-wrap}
	.seed-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:14px;background:#e9edf8;color:#3d5ca5;font-size:12px;margin:4px 6px 0 0}
	</style>';
}

/**
 * Escape and render stdout lines for browser mode.
 *
 * @param array<int,string> $lines Output lines.
 * @return string
 */
function renderOutput(array $lines): string
{
	$escapedLines = array();

	foreach ($lines as $line) {
		$escapedLines[] = dol_escape_htmltag($line);
	}

	return implode("\n", $escapedLines);
}

/**
 * Load the technical user used to create seed data.
 *
 * @param DoliDB $db Database handler.
 * @param int $userId User id.
 * @return User
 */
function loadSeedUser(DoliDB $db, int $userId): User
{
	$seedUser = new User($db);
	$result = $seedUser->fetch($userId);
	if ($result <= 0) {
		fail('Unable to load seed user with id '.$userId.'.');
	}

	return $seedUser;
}

/**
 * Build a unique third party name.
 *
 * @param DoliDB $db Database handler.
 * @param string $baseName Base name.
 * @param int $entity Entity id.
 * @return string
 */
function findUniqueThirdpartyName(DoliDB $db, string $baseName, int $entity): string
{
	$counter = 1;
	$uniqueName = $baseName;

	while (true) {
		$sql = 'SELECT s.rowid';
		$sql .= ' FROM '.$db->prefix().'societe AS s';
		$sql .= " WHERE s.nom = '".$db->escape($uniqueName)."'";
		$sql .= ' AND s.entity = '.((int) $entity);

		$resql = $db->query($sql);
		if (!$resql) {
			fail('Unable to check unique supplier name: '.$db->lasterror());
		}

		$numRows = $db->num_rows($resql);
		$db->free($resql);

		if ($numRows === 0) {
			return $uniqueName;
		}

		$counter++;
		$uniqueName = $baseName.' '.$counter;
	}
}

/**
 * Create one supplier third party.
 *
 * @param DoliDB $db Database handler.
 * @param User $user User executing the creation.
 * @param string $name Base supplier name.
 * @param int $entity Entity id.
 * @return Societe
 */
function createSupplier(DoliDB $db, User $user, string $name, int $entity): Societe
{
	$supplier = new Societe($db);
	$supplier->name = findUniqueThirdpartyName($db, $name, $entity);
	$supplier->status = 1;
	$supplier->client = 0;
	$supplier->fournisseur = 1;
	$supplier->code_client = -1;
	$supplier->code_fournisseur = -1;

	$result = $supplier->create($user);
	if ($result <= 0) {
		fail('Unable to create supplier "'.$name.'": '.$supplier->error);
	}

	return $supplier;
}

/**
 * Attach a child supplier to a parent supplier.
 *
 * @param Societe $child Child supplier.
 * @param int $parentId Parent supplier id.
 * @return void
 */
function attachParent(Societe $child, int $parentId): void
{
	$result = $child->setParent($parentId);
	if ($result <= 0) {
		fail('Unable to attach supplier #'.$child->id.' to parent #'.$parentId.'.');
	}
}

/**
 * Create one RFA row.
 *
 * @param DoliDB $db Database handler.
 * @param User $user User executing the creation.
 * @param int $supplierId Supplier id.
 * @param string $label RFA label.
 * @param int $year Target year.
 * @param float $threshold Threshold.
 * @param float $rate Rate percentage.
 * @param int $status RFA status.
 * @return ChaumeilRfa
 */
function createRfa(DoliDB $db, User $user, int $supplierId, string $label, int $year, float $threshold, float $rate, int $status = ChaumeilRfa::STATUS_WON): ChaumeilRfa
{
	$rfa = new ChaumeilRfa($db);
	$rfa->label = $label;
	$rfa->fk_soc = $supplierId;
	$rfa->status = $status;
	$rfa->datestart = dol_mktime(0, 0, 0, 1, 1, $year);
	$rfa->dateend = dol_mktime(23, 59, 59, 12, 31, $year);
	$rfa->palier = $threshold;
	$rfa->raterfa = $rate;

	$result = $rfa->create($user);
	if ($result <= 0) {
		fail('Unable to create RFA for supplier #'.$supplierId.': '.$rfa->error);
	}

	return $rfa;
}

/**
 * Create, validate and close one supplier invoice.
 *
 * @param DoliDB $db Database handler.
 * @param User $user User executing the creation.
 * @param int $supplierId Supplier id.
 * @param int $year Invoice year.
 * @param float $amountHt HT amount.
 * @param string $label Invoice label.
 * @return FactureFournisseur
 */
function createClosedSupplierInvoice(DoliDB $db, User $user, int $supplierId, int $year, float $amountHt, string $label): FactureFournisseur
{
	$invoice = new FactureFournisseur($db);
	$invoice->socid = $supplierId;
	$invoice->fk_soc = $supplierId;
	$invoice->type = FactureFournisseur::TYPE_STANDARD;
	$invoice->date = dol_mktime(12, 0, 0, 2, 15, $year);
	$invoice->ref_supplier = 'SEED-RFA-'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').'-'.$supplierId.'-'.(string) mt_rand(1000, 9999);
	$invoice->note_private = $label;

	$result = $invoice->create($user);
	if ($result <= 0) {
		fail('Unable to create supplier invoice for supplier #'.$supplierId.': '.$invoice->error);
	}

	$lineResult = $invoice->addline($label, $amountHt, 0, 0, 0, 1);
	if ($lineResult <= 0) {
		fail('Unable to add supplier invoice line for supplier #'.$supplierId.': '.$invoice->error);
	}

	$invoice->fetch($invoice->id);

	$validateResult = $invoice->validate($user);
	if ($validateResult <= 0) {
		fail('Unable to validate supplier invoice #'.$invoice->id.': '.$invoice->error);
	}

	$invoice->fetch($invoice->id);

	$closeResult = $invoice->setPaid($user);
	if ($closeResult <= 0) {
		fail('Unable to close supplier invoice #'.$invoice->id.': '.$invoice->error);
	}

	$invoice->fetch($invoice->id);

	return $invoice;
}

/**
 * Return a map of display rows indexed by supplier name.
 *
 * @param DoliDB $db Database handler.
 * @param int $year Target year.
 * @return array<string,array<string,mixed>>
 */
function loadGlobalRfaRowsByName(DoliDB $db, int $year): array
{
	$repository = new RfaGlobalListRepository($db);
	$service = new RfaGlobalListService($repository);
	$listResult = $service->buildList($year, array(), 'fk_soc', 'ASC', 0, 0);
	$rowsByName = array();

	if (empty($listResult['rows']) || !is_array($listResult['rows'])) {
		return $rowsByName;
	}

	foreach ($listResult['rows'] as $row) {
		if (!isset($row['soc_name'])) {
			continue;
		}

		$rowsByName[(string) $row['soc_name']] = $row;
	}

	return $rowsByName;
}

/**
 * Print one line on stdout.
 *
 * @param string $message Message to print.
 * @return void
 */
function out(string $message): void
{
	global $outputLines, $isCli;

	$outputLines[] = $message;
	if ($isCli) {
		print $message."\n";
	}
}

try {
	if (!$isCli) {
		if (empty($user) || empty($user->id) || empty($user->admin)) {
			accessforbidden();
		}
	}

	$seedYear = $isCli ? (int) getCliOption($argv, 'year', dol_print_date(dol_now(), '%Y')) : GETPOSTINT('year');
	if ($seedYear <= 0) {
		$seedYear = (int) dol_print_date(dol_now(), '%Y');
	}
	if ($seedYear < 2000 || $seedYear > 2100) {
		fail('Invalid --year option. Expected a year between 2000 and 2100.');
	}

	$seedUserId = $isCli ? (int) getCliOption($argv, 'user-id', '1') : (int) $user->id;
	if ($seedUserId <= 0) {
		fail('Invalid --user-id option. Expected a positive integer.');
	}

	$batchCode = $isCli ? getCliOption($argv, 'batch', dol_print_date(dol_now(), '%Y%m%d%H%M%S')) : dol_print_date(dol_now(), '%Y%m%d%H%M%S');
	$batchCode = preg_replace('/[^A-Za-z0-9_-]/', '', $batchCode) ?? '';
	if ($batchCode === '') {
		fail('Invalid batch code after sanitization.');
	}

	if (!$isCli && GETPOST('confirm', 'alpha') !== 'yes') {
		llxHeader('', 'Seed RFA root aggregation');
		print renderSeedStyles();
		print '<div class="clichaumeil-seed">';
		print '<div class="seed-hero">';
		print '<h1>Seed RFA root aggregation</h1>';
		print '<p>Ce script crée un batch de fournisseurs tests, des factures fournisseur closes et des RFA pour valider l’agrégation des filiales vers la maison mère racine.</p>';
		print '</div>';
		print '<div class="seed-grid">';
		print '<div class="seed-card">';
		print '<div class="seed-title">Cas couverts</div>';
		print '<ul class="seed-list">';
		print '<li>Maison mère avec plusieurs filiales</li>';
		print '<li>Chaîne récursive sur plusieurs niveaux</li>';
		print '<li>Maison mère seule</li>';
		print '<li>Filiale avec RFA propre à ignorer</li>';
		print '<li>Groupe sans RFA racine qui ne doit pas sortir</li>';
		print '</ul>';
		print '</div>';
		print '<div class="seed-card">';
		print '<div class="seed-title">Année ciblée</div>';
		print '<span class="seed-pill">'.dol_escape_htmltag((string) $seedYear).'</span>';
		print '</div>';
		print '</div>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="confirm" value="yes">';
		print '<input type="hidden" name="year" value="'.((int) $seedYear).'">';
		print '<div class="seed-actions">';
		print '<input class="button button-save" type="submit" value="Lancer le seed">';
		print '</div>';
		print '</form>';
		print '</div>';
		llxFooter();
		exit;
	}

	$entity = (int) $conf->entity;
	$seedUser = loadSeedUser($db, $seedUserId);
	$outputLines = array();

	out('Seed RFA root aggregation');
	out('Entity: '.$entity);
	out('Year: '.$seedYear);
	out('Batch: '.$batchCode);
	out('');

	$expectedBusinessRows = array();
	$expectedHiddenSuppliers = array();

	$alphaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS1 MERE RFA 2 FILIALES ROOT '.$batchCode, $entity);
	$alphaChildOne = createSupplier($db, $seedUser, 'RFA TEST CAS1 FILIALE 1 SANS RFA '.$batchCode, $entity);
	$alphaChildTwo = createSupplier($db, $seedUser, 'RFA TEST CAS1 FILIALE 2 SANS RFA '.$batchCode, $entity);
	attachParent($alphaChildOne, (int) $alphaRoot->id);
	attachParent($alphaChildTwo, (int) $alphaRoot->id);
	createRfa($db, $seedUser, (int) $alphaRoot->id, 'RFA ROOT ALPHA 5', $seedYear, 1000.0, 5.0);
	createRfa($db, $seedUser, (int) $alphaRoot->id, 'RFA ROOT ALPHA 10', $seedYear, 2000.0, 10.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $alphaRoot->id, $seedYear, 400.0, 'Seed Alpha Root Invoice');
	createClosedSupplierInvoice($db, $seedUser, (int) $alphaChildOne->id, $seedYear, 700.0, 'Seed Alpha Child One Invoice');
	createClosedSupplierInvoice($db, $seedUser, (int) $alphaChildTwo->id, $seedYear, 1200.0, 'Seed Alpha Child Two Invoice');
	$expectedBusinessRows[$alphaRoot->name] = array('ca' => 2300.0, 'rate' => 10.0, 'discount' => 230.0);
	$expectedHiddenSuppliers[] = $alphaChildOne->name;
	$expectedHiddenSuppliers[] = $alphaChildTwo->name;

	$betaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS2 RACINE RECURSIVE RFA '.$batchCode, $entity);
	$betaChild = createSupplier($db, $seedUser, 'RFA TEST CAS2 NIVEAU 1 SANS RFA '.$batchCode, $entity);
	$betaGrandChild = createSupplier($db, $seedUser, 'RFA TEST CAS2 NIVEAU 2 SANS RFA '.$batchCode, $entity);
	attachParent($betaChild, (int) $betaRoot->id);
	attachParent($betaGrandChild, (int) $betaChild->id);
	createRfa($db, $seedUser, (int) $betaRoot->id, 'RFA ROOT BETA 3', $seedYear, 500.0, 3.0);
	createRfa($db, $seedUser, (int) $betaRoot->id, 'RFA ROOT BETA 7', $seedYear, 1500.0, 7.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $betaChild->id, $seedYear, 600.0, 'Seed Beta Child Invoice');
	createClosedSupplierInvoice($db, $seedUser, (int) $betaGrandChild->id, $seedYear, 500.0, 'Seed Beta Grandchild Invoice');
	$expectedBusinessRows[$betaRoot->name] = array('ca' => 1100.0, 'rate' => 3.0, 'discount' => 33.0);
	$expectedHiddenSuppliers[] = $betaChild->name;
	$expectedHiddenSuppliers[] = $betaGrandChild->name;

	$gammaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS3 FOURNISSEUR SEUL AVEC RFA '.$batchCode, $entity);
	createRfa($db, $seedUser, (int) $gammaRoot->id, 'RFA ROOT GAMMA 2', $seedYear, 800.0, 2.0);
	createRfa($db, $seedUser, (int) $gammaRoot->id, 'RFA ROOT GAMMA 4', $seedYear, 1000.0, 4.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $gammaRoot->id, $seedYear, 900.0, 'Seed Gamma Root Invoice');
	$expectedBusinessRows[$gammaRoot->name] = array('ca' => 900.0, 'rate' => 2.0, 'discount' => 18.0);

	$deltaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS4 MERE RFA FILIALE AVEC RFA '.$batchCode, $entity);
	$deltaChild = createSupplier($db, $seedUser, 'RFA TEST CAS4 FILIALE AVEC RFA PROPRE '.$batchCode, $entity);
	$deltaSibling = createSupplier($db, $seedUser, 'RFA TEST CAS4 FILIALE 2 SANS RFA '.$batchCode, $entity);
	attachParent($deltaChild, (int) $deltaRoot->id);
	attachParent($deltaSibling, (int) $deltaRoot->id);
	createRfa($db, $seedUser, (int) $deltaRoot->id, 'RFA ROOT DELTA 4', $seedYear, 500.0, 4.0);
	createRfa($db, $seedUser, (int) $deltaRoot->id, 'RFA ROOT DELTA 8', $seedYear, 800.0, 8.0);
	createRfa($db, $seedUser, (int) $deltaChild->id, 'RFA CHILD DELTA SHOULD BE IGNORED', $seedYear, 100.0, 99.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $deltaChild->id, $seedYear, 600.0, 'Seed Delta Child Invoice');
	createClosedSupplierInvoice($db, $seedUser, (int) $deltaSibling->id, $seedYear, 300.0, 'Seed Delta Sibling Invoice');
	$expectedBusinessRows[$deltaRoot->name] = array('ca' => 900.0, 'rate' => 8.0, 'discount' => 72.0);
	$expectedBusinessRows[$deltaChild->name] = array('ca' => 600.0, 'rate' => 99.0, 'discount' => 594.0);
	$expectedHiddenSuppliers[] = $deltaSibling->name;

	$epsilonRoot = createSupplier($db, $seedUser, 'RFA TEST CAS5 MERE SANS RFA '.$batchCode, $entity);
	$epsilonChild = createSupplier($db, $seedUser, 'RFA TEST CAS5 FILIALE SANS RFA '.$batchCode, $entity);
	attachParent($epsilonChild, (int) $epsilonRoot->id);
	createClosedSupplierInvoice($db, $seedUser, (int) $epsilonChild->id, $seedYear, 700.0, 'Seed Epsilon Child Invoice');
	$expectedHiddenSuppliers[] = $epsilonRoot->name;
	$expectedHiddenSuppliers[] = $epsilonChild->name;

	$zetaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS6 MERE SANS RFA FILIALE AVEC RFA '.$batchCode, $entity);
	$zetaChild = createSupplier($db, $seedUser, 'RFA TEST CAS6 FILIALE AVEC RFA PROPRE '.$batchCode, $entity);
	attachParent($zetaChild, (int) $zetaRoot->id);
	createRfa($db, $seedUser, (int) $zetaChild->id, 'RFA CHILD ZETA 5', $seedYear, 500.0, 5.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $zetaChild->id, $seedYear, 650.0, 'Seed Zeta Child Invoice');
	$expectedBusinessRows[$zetaChild->name] = array('ca' => 650.0, 'rate' => 5.0, 'discount' => 32.5);
	$expectedHiddenSuppliers[] = $zetaRoot->name;

	$etaRoot = createSupplier($db, $seedUser, 'RFA TEST CAS7 MERE RFA PALIER NON ATTEINT '.$batchCode, $entity);
	$etaChild = createSupplier($db, $seedUser, 'RFA TEST CAS7 FILIALE SANS RFA '.$batchCode, $entity);
	attachParent($etaChild, (int) $etaRoot->id);
	createRfa($db, $seedUser, (int) $etaRoot->id, 'RFA ROOT ETA 5', $seedYear, 1000.0, 5.0);
	createClosedSupplierInvoice($db, $seedUser, (int) $etaRoot->id, $seedYear, 400.0, 'Seed Eta Root Invoice');
	createClosedSupplierInvoice($db, $seedUser, (int) $etaChild->id, $seedYear, 300.0, 'Seed Eta Child Invoice');
	$expectedHiddenSuppliers[] = $etaRoot->name;
	$expectedHiddenSuppliers[] = $etaChild->name;

	$rowsByName = loadGlobalRfaRowsByName($db, $seedYear);

	out('Expected business rows in global RFA list:');
	foreach ($expectedBusinessRows as $supplierName => $expectedRow) {
		out(
			'- '.$supplierName
			.' => CA achats année HT='.price((float) $expectedRow['ca'], 0, '', 1, -1, -1, $conf->currency)
			.', taux %='.price((float) $expectedRow['rate'], 0, '', 1, -1, -1, '')
			.', remise='.price((float) $expectedRow['discount'], 0, '', 1, -1, -1, $conf->currency)
		);
	}

	out('');
	out('Expected hidden suppliers in global RFA list:');
	foreach ($expectedHiddenSuppliers as $hiddenSupplierName) {
		out('- '.$hiddenSupplierName);
	}

	out('');
	out('Current implementation rows returned by the service:');
	if (empty($rowsByName)) {
		out('- No rows returned');
	} else {
		foreach ($rowsByName as $supplierName => $row) {
			if (strpos($supplierName, $batchCode) === false) {
				continue;
			}

			out(
				'- '.$supplierName
				.' => CA achats année HT='.price((float) $row['ca_achats'], 0, '', 1, -1, -1, $conf->currency)
				.', taux %='.price((float) $row['taux_rfa'], 0, '', 1, -1, -1, '')
				.', remise='.price((float) $row['discount_amount_rfa'], 0, '', 1, -1, -1, $conf->currency)
			);
		}
	}

	out('');
	out('Check the page: /custom/clichaumeil/chaumeilrfa_list_fourn.php?search_year='.$seedYear);

	if (!$isCli) {
		llxHeader('', 'Seed RFA root aggregation');
		print renderSeedStyles();
		print '<div class="clichaumeil-seed">';
		print '<div class="seed-hero">';
		print '<h1>Seed RFA root aggregation exécuté</h1>';
		print '<p>Le batch a été créé. Vous pouvez maintenant contrôler la liste globale RFA et comparer les valeurs attendues ci-dessous.</p>';
		print '</div>';
		print '<div class="seed-actions">';
		print '<a class="button button-save" href="'.dol_buildpath('/custom/clichaumeil/chaumeilrfa_list_fourn.php?search_year='.$seedYear, 1).'">Ouvrir la liste RFA</a>';
		print '<a class="button button-cancel" href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">Relancer un autre batch</a>';
		print '</div>';
		print '<div class="seed-card" style="margin-top:18px;">';
		print '<div class="seed-title">Résultat</div>';
		print '<div class="seed-output">'.renderOutput($outputLines).'</div>';
		print '</div>';
		print '</div>';
		llxFooter();
	}
} catch (Throwable $exception) {
	if ($isCli) {
		out('ERROR: '.$exception->getMessage());
		exit(1);
	}

	llxHeader('', 'Seed RFA root aggregation');
	print renderSeedStyles();
	print '<div class="clichaumeil-seed">';
	print '<div class="seed-card">';
	print '<div class="seed-title">Erreur</div>';
	print '<div class="seed-output">'.dol_escape_htmltag($exception->getMessage()).'</div>';
	print '</div>';
	print '</div>';
	llxFooter();
	exit;
}
