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
 * \file    class/SupplierPriceSync/Cron/AbstractSupplierPriceSyncCronJob.php
 * \ingroup clichaumeil
 * \brief   Generic cron job orchestrating a supplier price synchronisation.
 */

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../Contract/SupplierConfigInterface.php';
require_once __DIR__ . '/../Contract/SupplierPriceConnectorInterface.php';
require_once __DIR__ . '/../Service/SupplierPriceSyncService.php';
require_once __DIR__ . '/../Service/SupplierPriceSyncMailer.php';
require_once __DIR__ . '/../ValueObject/CronRecipients.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

/**
 * Generic supplier price sync cron job.
 *
 * Concrete connectors only provide their configuration and connector instances.
 */
abstract class AbstractSupplierPriceSyncCronJob
{
	/** @var DoliDB Database handler. */
	public DoliDB $db;

	/** @var Translate Translator. */
	public Translate $langs;

	/** @var int Entity set by the cron scheduler before run(). */
	public int $entity = 0;

	/** @var string Last error message. */
	public string $error = '';

	/** @var string Cron textual output. */
	public string $output = '';

	/**
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
	 * Build the supplier configuration.
	 *
	 * @return SupplierConfigInterface
	 * @throws RuntimeException When the configuration is incomplete.
	 */
	abstract protected function buildConfig(): SupplierConfigInterface;

	/**
	 * Build the supplier connector.
	 *
	 * @param SupplierConfigInterface $config Supplier configuration.
	 * @return SupplierPriceConnectorInterface
	 */
	abstract protected function buildConnector(SupplierConfigInterface $config): SupplierPriceConnectorInterface;

	/**
	 * Execute the synchronisation.
	 *
	 * @param string $recipientsRaw Raw cron parameter listing error-report recipients.
	 * @return int 0 on success, -1 on failure.
	 */
	public function run(string $recipientsRaw = ''): int
	{
		$executionUser = $this->resolveUser();

		// Recipients come from the admin config constant; the cron parameter is kept
		// as a back-compatible fallback when the constant is empty.
		$configuredRecipients = getDolGlobalString(SupplierPriceSyncConstants::CONST_MAIL_RECIPIENTS);
		$rawRecipients = trim($configuredRecipients) !== '' ? $configuredRecipients : $recipientsRaw;

		try {
			$recipients = CronRecipients::fromRaw($rawRecipients);
		} catch (InvalidArgumentException $exception) {
			$this->error = $exception->getMessage();
			$this->output = $this->langs->trans('CliChaumeil_SupplierPriceSync_INVALID_CRON_RECIPIENT');
			dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);

			return -1;
		}

		try {
			$config = $this->buildConfig();
			$connector = $this->buildConnector($config);
		} catch (RuntimeException $exception) {
			$this->error = $exception->getMessage();
			$this->output = $this->langs->trans('CliChaumeil_SupplierPriceSync_MISSING_CONFIGURATION');
			dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);

			return -1;
		}

		try {
			$dryRun = getDolGlobalInt(SupplierPriceSyncConstants::CONST_DRY_RUN) === 1;
			$service = new SupplierPriceSyncService($this->db);
			$report = $service->run($config, $connector, $executionUser, $dryRun);

			$this->persistLastRun($config, $report, $dryRun);

			// Send the mail before building the output so mail-sending issues
			// (invalid sender, send failure) are reflected in the cron output.
			$mailPolicy = getDolGlobalString(
				SupplierPriceSyncConstants::CONST_MAIL_POLICY,
				SupplierPriceSyncConstants::DEFAULT_MAIL_POLICY
			);
			if ($report->shouldNotify($mailPolicy) && !$recipients->isEmpty()) {
				$mailer = new SupplierPriceSyncMailer();
				$mailer->send($report, $recipients, $this->langs);
			}

			$this->output = $report->buildCronOutput($this->langs);

			return $report->hasFailures() ? -1 : 0;
		} catch (Throwable $exception) {
			$this->error = $exception->getMessage();
			$this->output = $exception->getMessage();
			dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);

			return -1;
		}
	}

	/**
	 * Persist a JSON snapshot of the last run as a per-supplier constant.
	 *
	 * Written on success and on failure so the admin page always shows the latest
	 * outcome without digging into the cron logs.
	 *
	 * @param SupplierConfigInterface $config Supplier configuration.
	 * @param SupplierPriceSyncReport $report Run report.
	 * @param bool                    $dryRun Whether the run was a dry-run.
	 * @return void
	 */
	private function persistLastRun(SupplierConfigInterface $config, SupplierPriceSyncReport $report, bool $dryRun): void
	{
		$name = SupplierPriceSyncConstants::CONST_LASTRUN_PREFIX . strtoupper($config->getCode());
		$payload = json_encode(array(
			'date' => (int) dol_now(),
			'dryRun' => $dryRun,
			'summary' => $report->summaryLine(),
		));
		dolibarr_set_const($this->db, $name, $payload, 'chaine', 0, '', $this->entity);
	}

	/**
	 * Resolve the execution user (current user, else admin id 1).
	 *
	 * @return User
	 */
	private function resolveUser(): User
	{
		global $user;

		if ($user instanceof User && $user->id > 0) {
			return $user;
		}

		$fallback = new User($this->db);
		$fallback->fetch(1);

		return $fallback;
	}
}
