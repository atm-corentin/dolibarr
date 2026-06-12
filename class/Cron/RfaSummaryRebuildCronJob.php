<?php
declare(strict_types=1);

$res = @include_once __DIR__.'/../../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__.'/../../../../main.inc.php';
}

require_once __DIR__.'/../chaumeilrfa.class.php';
require_once __DIR__.'/../Rfa/RfaSummarySourceRepository.php';
require_once __DIR__.'/../Rfa/RfaSummaryBuilder.php';
require_once __DIR__.'/../Rfa/RfaClientSummaryBuilder.php';
require_once __DIR__.'/../Rfa/RfaSummaryPersister.php';

/**
 * Cron job used to rebuild the yearly RFA summary cache.
 *
 * Accepts an optional parameter string with the following format:
 *   [year] [rfa_type=N]
 * Examples: "", "2025", "rfa_type=1", "2025 rfa_type=1"
 * rfa_type defaults to ChaumeilRfa::TYPE_SUPPLIER when omitted.
 */
class RfaSummaryRebuildCronJob
{
	/**
	 * @var DoliDB
	 */
	public DoliDB $db;

	/**
	 * @var Translate
	 */
	public Translate $langs;

	/**
	 * @var string
	 */
	public string $error = '';

	/**
	 * @var string
	 */
	public string $output = '';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;
		$this->langs = $langs;
		$this->langs->loadLangs(array('clichaumeil@clichaumeil'));
	}

	/**
	 * Execute the rebuild.
	 *
	 * @param string $params Space-separated params: optional year and/or rfa_type=N.
	 * @return int 0 on success, -1 on failure.
	 */
	public function run(string $params = ''): int
	{
		try {
			$rfaType = ChaumeilRfa::TYPE_SUPPLIER;
			$yearStr = '';
			foreach (explode(' ', trim($params)) as $token) {
				if (strpos($token, 'rfa_type=') === 0) {
					$rfaType = (int) substr($token, 9);
				} elseif ($token !== '') {
					$yearStr = $token;
				}
			}

			$isClient = ($rfaType === ChaumeilRfa::TYPE_CLIENT);
			$repository = new RfaSummarySourceRepository($this->db);
			$builder = $isClient ? new RfaClientSummaryBuilder($repository) : new RfaSummaryBuilder($repository);
			$persister = new RfaSummaryPersister($this->db, $builder);
			$targetYears = ($yearStr !== '') ? array((int) $yearStr) : $repository->fetchRelevantSummaryYears();

			if (empty($targetYears)) {
				$noYearKey = $isClient ? 'CliChaumeil_RfaClientSummaryCronNoYear' : 'CliChaumeil_RfaSummaryCronNoYear';
				$this->output = $this->langs->trans($noYearKey);
				return 0;
			}

			$successKey = $isClient ? 'CliChaumeil_RfaClientSummaryCronSuccess' : 'CliChaumeil_RfaSummaryCronSuccess';
			$outputLines = array();
			foreach ($targetYears as $targetYear) {
				$count = $persister->rebuildYear((int) $targetYear, $rfaType);
				$outputLines[] = $this->langs->trans($successKey, (int) $targetYear, $count);
			}

			$this->output = implode("\n", $outputLines);
			return 0;
		} catch (Throwable $exception) {
			$this->error = $exception->getMessage();
			$this->output = $this->langs->trans('CliChaumeil_RfaSummaryCronError', $this->error);
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}
	}
}
