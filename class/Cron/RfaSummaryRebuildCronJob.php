<?php
declare(strict_types=1);

$res = @include_once __DIR__.'/../../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__.'/../../../../main.inc.php';
}

require_once __DIR__.'/../Rfa/RfaSummarySourceRepository.php';
require_once __DIR__.'/../Rfa/RfaSummaryBuilder.php';
require_once __DIR__.'/../Rfa/RfaSummaryPersister.php';

/**
 * Cron job used to rebuild the yearly RFA summary cache.
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
	 * @param string $year Raw year parameter passed by the cron engine.
	 * @return int 0 on success, -1 on failure.
	 */
	public function run(string $year = ''): int
	{
		try {
			$repository = new RfaSummarySourceRepository($this->db);
			$builder = new RfaSummaryBuilder($repository);
			$persister = new RfaSummaryPersister($this->db, $builder);
			$targetYears = ($year !== '') ? array((int) $year) : $repository->fetchRelevantSummaryYears();
			if (empty($targetYears)) {
				$this->output = $this->langs->trans('CliChaumeil_RfaSummaryCronNoYear');

				return 0;
			}

			$outputLines = array();
			foreach ($targetYears as $targetYear) {
				$count = $persister->rebuildYear((int) $targetYear);
				$outputLines[] = $this->langs->trans('CliChaumeil_RfaSummaryCronSuccess', (int) $targetYear, $count);
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
