<?php

declare(strict_types=1);

$res = @include_once __DIR__ . '/../../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__ . '/../../../../main.inc.php';
}

require_once __DIR__ . '/../Service/RfaNegotiationReminderConfig.php';
require_once __DIR__ . '/../Service/RfaNegotiationReminderMailer.php';

/**
 * Cron job that sends the yearly RFA negotiation reminder.
 */
class RfaNegotiationReminderCronJob
{
	/**
	 * @var string Job code used in logs and setup.
	 */
	public const CRON_CODE = 'CLICHAUMEIL_RFA_NEGOTIATION_REMINDER';

	/**
	 * @var string Default relative URL to the RFA supplier list.
	 */
	private const DEFAULT_RFA_LIST_URL = '/custom/clichaumeil/chaumeilrfa_list_fourn.php?yearid=__YEAR__';

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
	 * @var array<int,string>
	 */
	public array $warnings = array();

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
		global $conf, $langs;

		$this->db = $db;
		$this->langs = $langs;
		$this->langs->loadLangs(array('clichaumeil@clichaumeil'));
	}

	/**
	 * Execute the cron job.
	 *
	 * @param string $recipients Raw comma-separated recipients passed by the cron engine.
	 * @param string $templateCode Mail template code passed by the cron engine.
	 * @return int 0 on success, -1 on failure.
	 */
	public function run(string $recipients = '', string $templateCode = ''): int
	{
		try {
			$config = $this->buildConfig($recipients, $templateCode);
			$year = (int) dol_print_date(dol_now(), '%Y');
			$rfaListUrl = $this->buildFinalUrl($year);

			$mailer = new RfaNegotiationReminderMailer($this->db, $this->langs);
			$mailer->send($config->getRecipients(), $config->getTemplateCode(), $year, $rfaListUrl);

			$this->output = $this->langs->trans(
				'CliChaumeil_RfaReminderOutputSuccess',
				$year,
				count($config->getRecipients()),
				$config->getTemplateCode(),
				$rfaListUrl
			);

			return 0;
		} catch (Throwable $exception) {
			$this->error = $this->translateErrorMessage($exception->getMessage());
			$this->output = $this->langs->trans('CliChaumeil_RfaReminderOutputFailure', $this->error);
			dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);

			return -1;
		}
	}

	/**
	 * Build the validated cron configuration.
	 *
	 * @param string $recipients Raw recipients string from cron params.
	 * @param string $templateCode Template code from cron params.
	 * @return RfaNegotiationReminderConfig
	 */
	private function buildConfig(string $recipients, string $templateCode): RfaNegotiationReminderConfig
	{
		return RfaNegotiationReminderConfig::fromMethodArguments($recipients, $templateCode);
	}

	/**
	 * Build the final absolute URL sent in the email.
	 *
	 * @param int    $year Target year.
	 * @return string
	 */
	private function buildFinalUrl(int $year): string
	{
		$resolvedUrl = str_replace(RfaNegotiationReminderMailer::TEMPLATE_TOKEN_YEAR, (string) $year, self::DEFAULT_RFA_LIST_URL);
		if (preg_match('/^https?:\/\//i', $resolvedUrl)) {
			return $resolvedUrl;
		}

		return dol_buildpath($resolvedUrl, 2);
	}

	/**
	 * Translate an exception message when it is a language key.
	 *
	 * @param string $message Raw exception message.
	 * @return string
	 */
	private function translateErrorMessage(string $message): string
	{
		$translatedMessage = $this->langs->trans($message);
		if ($translatedMessage !== $message) {
			return $translatedMessage;
		}

		return $message;
	}
}
