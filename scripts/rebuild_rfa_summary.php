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

require_once __DIR__.'/../class/chaumeilrfa.class.php';
require_once __DIR__.'/../class/Rfa/RfaSummarySourceRepository.php';
require_once __DIR__.'/../class/Rfa/RfaSummaryBuilder.php';
require_once __DIR__.'/../class/Rfa/RfaClientSummaryBuilder.php';
require_once __DIR__.'/../class/Rfa/RfaSummaryPersister.php';
require_once __DIR__.'/../class/Rfa/RfaSummaryStorageManager.php';

/**
 * Return one CLI option.
 *
 * @param array<int,string> $argv CLI arguments.
 * @param string $name Option name.
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
 * Render browser styles.
 *
 * @return string
 */
function renderRfaSummaryStyles(): string
{
	return '<style>
	.clichaumeil-summary-tool{max-width:980px;margin:24px auto 40px;padding:0 16px;color:#222}
	.summary-card{background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:18px 20px}
	.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:18px}
	.summary-title{font-weight:600;margin-bottom:8px;font-size:14px;text-transform:uppercase;letter-spacing:.8px;color:#666}
	.summary-output{background:#1c1c1c;color:#f1f1f1;border-radius:8px;padding:14px;font-family:"Courier New",Courier,monospace;font-size:12.5px;line-height:1.6;white-space:pre-wrap}
	.summary-actions{margin-top:18px;display:flex;gap:12px;align-items:center}
	</style>';
}

/**
 * Output one line.
 *
 * @param string $message Message.
 * @return void
 */
function printSummaryLine(string $message): void
{
	global $isCli, $summaryOutputLines;

	$summaryOutputLines[] = $message;
	if ($isCli) {
		print $message."\n";
	}
}

/**
 * Render output lines for browser mode.
 *
 * @param array<int,string> $lines Output lines.
 * @return string
 */
function renderSummaryOutput(array $lines): string
{
	$escapedLines = array();

	foreach ($lines as $line) {
		$escapedLines[] = dol_escape_htmltag($line);
	}

	return implode("\n", $escapedLines);
}

/**
 * Send one JSON response and stop execution.
 *
 * @param int $httpCode HTTP status code.
 * @param array<string,mixed> $payload JSON payload.
 * @return never
 */
function sendJsonResponse(int $httpCode, array $payload): void
{
	if (!headers_sent()) {
		http_response_code($httpCode);
		header('Content-Type: application/json; charset=UTF-8');
	}

	print json_encode($payload);
	exit;
}

/**
 * Check the browser CSRF token.
 *
 * @return void
 * @throws RuntimeException When the token is invalid.
 */
function assertValidBrowserToken(): void
{
	$submittedToken = GETPOST('token', 'alpha');
	if ($submittedToken === '' || !hash_equals(currentToken(), $submittedToken)) {
		throw new RuntimeException('Invalid browser token.');
	}
}

/**
 * Check whether the exception is caused by a missing summary storage.
 *
 * @param Throwable $exception Exception to inspect.
 * @return bool
 */
function isMissingSummaryStorageException(Throwable $exception): bool
{
	return $exception->getMessage() === RfaSummaryStorageManager::ERROR_SUMMARY_STORAGE_MISSING
		|| strpos($exception->getMessage(), RfaSummaryStorageManager::ERROR_SUMMARY_STORAGE_MISSING) !== false;
}

try {
	global $summaryOutputLines;
	$summaryOutputLines = array();
	$langs->loadLangs(array('clichaumeil@clichaumeil', 'main'));
	$requestMode = $isCli ? 'cli' : GETPOST('mode', 'aZ');
	$isAjax = (!$isCli && $requestMode === 'ajax');

	if (!$isCli) {
		if (empty($user) || empty($user->id) || !$user->hasRight('clichaumeil', 'chaumeilrfa', 'write')) {
			if ($isAjax) {
				sendJsonResponse(403, array(
					'success' => false,
					'message' => $langs->transnoentitiesnoconv('ErrorForbidden'),
				));
			}
			accessforbidden();
		}
	}

	$rfaType = $isCli ? (int) getCliOption($argv, 'rfa_type', (string) ChaumeilRfa::TYPE_SUPPLIER) : GETPOSTINT('rfa_type');
	$isClient = ($rfaType === ChaumeilRfa::TYPE_CLIENT);
	$langPrefix = $isClient ? 'CliChaumeil_RfaClientSummary' : 'CliChaumeil_RfaSummary';
	$listPage = $isClient ? 'chaumeilrfa_list_fourn.php?rfa_type=1&yearid=' : 'chaumeilrfa_list_fourn.php?yearid=';

	$targetYear = $isCli ? (int) getCliOption($argv, 'year', dol_print_date(dol_now(), '%Y')) : GETPOSTINT('yearid');
	if ($targetYear <= 0) {
		$targetYear = (int) dol_print_date(dol_now(), '%Y');
	}

	$backToPage = $isCli ? '' : GETPOST('backtopage', 'alphanohtml');
	$isConfirmed = (!$isCli && GETPOST('confirm', 'alpha') === 'yes');
	if (!$isCli && !$isConfirmed) {
		llxHeader('', $langs->trans($langPrefix.'RebuildTitle'));
		print renderRfaSummaryStyles();
		print '<div class="clichaumeil-summary-tool">';
		print '<div class="summary-card">';
		print '<h1>'.$langs->trans($langPrefix.'RebuildTitle').'</h1>';
		print '<p>'.$langs->trans($langPrefix.'RebuildIntro').'</p>';
		print '<div class="summary-grid">';
		print '<div class="summary-card">';
		print '<div class="summary-title">'.$langs->trans('ByYear').'</div>';
		print '<div>'.dol_escape_htmltag((string) $targetYear).'</div>';
		print '</div>';
		print '</div>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="confirm" value="yes">';
		print '<input type="hidden" name="yearid" value="'.((int) $targetYear).'">';
		print '<input type="hidden" name="rfa_type" value="'.$rfaType.'">';
		print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($backToPage).'">';
		print '<div class="summary-actions">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans($langPrefix.'RebuildAction').'">';
		if ($backToPage !== '') {
			print '<a class="button button-cancel" href="'.dol_escape_htmltag($backToPage).'">'.$langs->trans('Back').'</a>';
		}
		print '</div>';
		print '</form>';
		print '</div>';
		print '</div>';
		llxFooter();
		exit;
	}
	if ($isConfirmed) {
		assertValidBrowserToken();
	}

	$repository = new RfaSummarySourceRepository($db);
	$builder = $isClient ? new RfaClientSummaryBuilder($repository) : new RfaSummaryBuilder($repository);
	$persister = new RfaSummaryPersister($db, $builder);
	$rowCount = $persister->rebuildYear($targetYear, $rfaType);

	printSummaryLine($langs->trans($langPrefix.'RebuildTitle'));
	printSummaryLine($langs->trans('ByYear').': '.$targetYear);
	printSummaryLine($langs->trans($langPrefix.'RebuildResult', $rowCount));

	if ($isAjax) {
		sendJsonResponse(200, array(
			'success' => true,
			'message' => $langs->transnoentitiesnoconv($langPrefix.'RebuildSuccessMessage', $targetYear, $rowCount),
			'row_count' => $rowCount,
			'year' => $targetYear,
		));
	}

	if (!$isCli) {
		llxHeader('', $langs->trans($langPrefix.'RebuildTitle'));
		print renderRfaSummaryStyles();
		print '<div class="clichaumeil-summary-tool">';
		print '<div class="summary-card">';
		print '<h1>'.$langs->trans($langPrefix.'RebuildTitle').'</h1>';
		print '<p>'.$langs->trans($langPrefix.'RebuildSuccessMessage', $targetYear, $rowCount).'</p>';
		print '<div class="summary-actions">';
		if ($backToPage !== '') {
			print '<a class="button button-save" href="'.dol_escape_htmltag($backToPage).'">'.$langs->trans('Back').'</a>';
		}
		print '<a class="button" href="'.dol_buildpath('/clichaumeil/'.$listPage.$targetYear, 1).'">'.$langs->trans('CliChaumeil_RfaSummaryOpenList').'</a>';
		print '</div>';
		print '<div class="summary-card" style="margin-top:18px;">';
		print '<div class="summary-title">'.$langs->trans('CliChaumeil_RfaSummaryRebuildOutput').'</div>';
		print '<div class="summary-output">'.renderSummaryOutput($summaryOutputLines).'</div>';
		print '</div>';
		print '</div>';
		print '</div>';
		llxFooter();
	}
} catch (Throwable $exception) {
	dol_syslog(__FILE__.' '.$exception->getMessage(), LOG_ERR);

	if ($isCli) {
		print 'ERROR: '.$exception->getMessage()."\n";
		exit(1);
	}

	$errorMessage = isMissingSummaryStorageException($exception) ? $langs->transnoentitiesnoconv('CliChaumeil_RfaSummaryStorageMissing') : $langs->transnoentitiesnoconv('CliChaumeil_RfaSummaryRebuildError');
	if (!empty($isAjax)) {
		sendJsonResponse(500, array(
			'success' => false,
			'message' => $errorMessage,
		));
	}
	setEventMessages($errorMessage, null, 'errors');
	llxHeader('', $langs->trans($langPrefix.'RebuildTitle'));
	print renderRfaSummaryStyles();
	print '<div class="clichaumeil-summary-tool">';
	print '<div class="summary-card">';
	print '<h1>'.$langs->trans($langPrefix.'RebuildTitle').'</h1>';
	print '<div class="summary-output">'.dol_escape_htmltag($errorMessage).'</div>';
	print '</div>';
	print '</div>';
	llxFooter();
	exit;
}
