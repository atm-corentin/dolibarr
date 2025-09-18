<?php

declare(strict_types=1);

// Load Dolibarr environment
$res = @include_once __DIR__ . '/../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__ . '/../../../main.inc.php';
}

// Load required Dolibarr classes
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

global $conf, $langs;
if (isModEnabled('advancednotifier')) {
	$res = dol_include_once('/advancednotifier/class/advnotification.class.php');
	if ($res) {
		$langs->load('advancednotifier@advancednotifier');
	}
}

class CronJobUpdateContractRevision
{

	// --- Configuration Keys Constants ---
	private const CONF_YEARS_TO_ADD = 'CLICHAUMEIL_REVIEW_YEAR_DELAY';
	private const CONF_RESPONSIBLE_USERS = 'CLICHAUMEIL_PRICING_UPDATE_MANAGERS';
	private const CONF_EMAIL_TEMPLATE = 'CLICHAUMEIL_CRON_EMAIL_TEMPLATE';
	private const CONF_SUBSCRIBED_USERS = 'CLICHAUMEIL_CRON_NOTIF_USERS';

	// --- Extrafield Name ---
	private const EXTRAFIELD_REVISION_DATE = 'clichaumeil_reviewdate';
	private const EXTRAFIELD_REVISION_RATE = 'clichaumeilreviewrate';

	/** @var DoliDB Database handler */
	public DoliDB $db;
	/** @var Conf Global configuration object */
	public Conf $conf;
	/** @var Translate Language object */
	public Translate $langs;
	/** @var stdClass The job descriptor from module file */
	public stdClass $job;
	/** @var string Error message */
	public string $error = '';
	public array $warnings = [];
	public string $output;

	/**
	 * Constructor
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;
		$this->db = $db;
		$langs->load("clichaumeil@clichaumeil");
	}

	/**
	 * Main execution method for the cron job.
	 *
	 * @return int 0 if OK, <0 if KO
	 */
	public function run(): int
	{
		global $user, $langs;
		dol_syslog(__METHOD__ . "::start - Starting cron job: Update Contract Revision (Object-Oriented)", LOG_INFO);

		// --- 1. Load configuration ---
		$revisionYearToAdd = (int) (getDolGlobalInt(self::CONF_YEARS_TO_ADD) ?? 1);
		$responsibleUserIds = array_filter(array_map('intval', explode(',', getDolGlobalString(self::CONF_RESPONSIBLE_USERS) ?? '')));
		$emailTemplateId = getDolGlobalInt(self::CONF_EMAIL_TEMPLATE) ?? 0;
		$subscribedUserIds = array_filter(array_map('intval', explode(',', getDolGlobalString(self::CONF_SUBSCRIBED_USERS) ?? '')));

		if (empty($emailTemplateId) && !empty($responsibleUserIds)) {
			$warningMsg = $langs->trans("CliChaumeilWarningNoEmailTemplate");
			$this->warnings[] = $warningMsg;
			dol_syslog(__METHOD__ . "::warning - " . $warningMsg, LOG_WARNING);
		}

		// --- 2. Retrieve all contract lines to be updated ---
		$linesToUpdateData = $this->getLinesToUpdateData();
		if (empty($linesToUpdateData)) {
			dol_syslog(__METHOD__ . "::end - No contract lines to update.", LOG_INFO);
			$this->output = $langs->trans("CliChaumeilCronNoLinesToUpdate");
			return 0;
		}

		try {
			// --- 3. Process lines (Core logic is now in its own method) ---
			$processedDetails = $this->processContractLines($linesToUpdateData, $revisionYearToAdd);

			// --- 4. Send notifications (Notification logic is now in its own method) ---
			$this->sendAllNotifications($processedDetails, $responsibleUserIds, $emailTemplateId, $subscribedUserIds);

			// --- 5. Build the verbose output string ---
			$this->output = $this->buildVerboseOutput($processedDetails, $responsibleUserIds, $subscribedUserIds);
			dol_syslog(__METHOD__ . "::end - Cron job finished.", LOG_INFO);
			return 0;

		} catch (Exception $e) {
			$this->error = $e->getMessage();
			$this->output = $langs->trans("CliChaumeilCronError") . ': ' . $this->error;
			dol_syslog(__METHOD__ . "::error - " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Processes each contract line, updates it in the database, and returns detailed results.
	 * This method handles the database transaction.
	 *
	 * @param array $linesToUpdateData Array of lines to process from getLinesToUpdateData().
	 * @param int   $revisionYearToAdd Number of years to add to the revision date.
	 * @return array Array of processed line details.
	 * @throws Exception if a database update fails.
	 */
	private function processContractLines(array $linesToUpdateData, int $revisionYearToAdd): array
	{
		global $user;
		$processedDetails = [];
		$cachedContracts = [];

		$this->db->begin();
		try {
			foreach ($linesToUpdateData as $lineData) {
				$contractLine = new ContratLigne($this->db);
				if ($contractLine->fetch($lineData['line_id']) <= 0) {
					$warningMsg = $this->langs->trans("CliChaumeilWarningSkipLine", $lineData['line_id']);
					$this->warnings[] = $warningMsg;
					dol_syslog(__METHOD__ . "::warning - " . $warningMsg, LOG_WARNING);
					continue;
				}

				$contractId = $contractLine->fk_contrat;
				$contractUrl = '';

				if (isset($cachedContracts[$contractId])) {
					$contractUrl = $cachedContracts[$contractId];
				} else {
					$contract = new Contrat($this->db);
					if ($contract->fetch($contractId) > 0) {
						$contractUrl = $contract->getNomUrl(1);
						$cachedContracts[$contractId] = $contractUrl;
					}
				}

				if (empty($contractUrl)) {
					$contractUrl = $lineData['contract_ref'];
				}

				$oldPrice = (float)$lineData['subprice'];
				$oldRevisionDate = new DateTime($lineData['date_revision']);

				$newPrice = $oldPrice * (1 + ((float)$lineData['taux_revision'] / 100));
				$newRevisionDate = (clone $oldRevisionDate)->add(new DateInterval('P' . $revisionYearToAdd . 'Y'));

				$contractLine->subprice = $newPrice;
				$contractLine->array_options['options_' . self::EXTRAFIELD_REVISION_DATE] = $newRevisionDate->format('Y-m-d H:i:s');

				if ($contractLine->update($user) < 0) {
					throw new Exception("Failed to update contract line ID " . $lineData['line_id'] . ". Error: " . $contractLine->errorsToString());
				}

				$processedDetails[] = [
					'contract_id' => $contractId,
					'contract_ref' => $lineData['contract_ref'],
					'contract_url' => $contractUrl,
					'line_id' => $lineData['line_id'],
					'old_price' => $oldPrice,
					'new_price' => $newPrice,
					'old_date' => $oldRevisionDate,
					'new_date' => $newRevisionDate
				];
			}
			$this->db->commit();
			return $processedDetails;

		} catch (Exception $e) {
			$this->db->rollback();
			// Re-throw the exception to be caught by the run() method
			throw $e;
		}
	}

	/**
	 * Prepares and sends all required notifications (email, push) based on processed details.
	 *
	 * @param array $processedDetails   Array of details from processContractLines().
	 * @param array $responsibleUserIds Array of user IDs for email notifications.
	 * @param int   $emailTemplateId    ID of the email template to use.
	 * @param array $subscribedUserIds  Array of user IDs for push notifications.
	 * @return void
	 */
	private function sendAllNotifications(array $processedDetails, array $responsibleUserIds, int $emailTemplateId, array $subscribedUserIds): void
	{
		global $conf;

		// Group processed details by contract
		$modifiedContracts = [];
		foreach ($processedDetails as $detail) {
			if (!isset($modifiedContracts[$detail['contract_id']])) {
				$modifiedContracts[$detail['contract_id']] = [
					'ref' => $detail['contract_ref'],
					'url' => $detail['contract_url']
				];
			}
		}

		if (empty($modifiedContracts)) {
			return;
		}

		// Send recap email to responsible users
		if (!empty($responsibleUserIds) && !empty($emailTemplateId)) {
			$this->sendRecapEmail($responsibleUserIds, $emailTemplateId, $modifiedContracts);
		}

		// Send push notification ONLY IF the module is enabled and class exists
		if (!empty($conf->advancednotifier->enabled) && class_exists('AdvNotification')) {
			if (!empty($subscribedUserIds)) {
				foreach ($modifiedContracts as $contractId => $contractData) {
					$this->sendAdvancedNotification($subscribedUserIds, $contractId, $contractData['ref'], $contractData['url']);
				}
			}
		}
	}

	/**
	 * Builds a verbose and clear output string for the cron job log.
	 *
	 * @param array $details         Array of processed line details.
	 * @param array $responsibleIds  Array of user IDs for email notifications.
	 * @param array $subscribedIds   Array of user IDs for advanced notifications.
	 * @return string                The formatted report string.
	 */
	private function buildVerboseOutput(array $details, array $responsibleIds, array $subscribedIds): string
	{
		global $langs;
		$lineCount = count($details);
		$contractCount = count(array_unique(array_column($details, 'contract_ref')));

		$reportLines = [];
		$reportLines[] = $langs->trans("CliChaumeilCronReportTitle");
		$reportLines[] = "";

		// --- Summary Section ---
		$reportLines[] = $langs->trans("CliChaumeilCronSectionSummary");
		$reportLines[] = $langs->trans("CliChaumeilCronSuccessSummary", $contractCount, $lineCount);
		$reportLines[] = "";

		// --- Details Section ---
		$reportLines[] = $langs->trans("CliChaumeilCronSectionDetails");

		// Group lines by contract for better readability
		$detailsByContract = [];
		foreach ($details as $detail) {
			$detailsByContract[$detail['contract_ref']][] = $detail;
		}

		foreach ($detailsByContract as $contractDetails) {
			$contractUrl = $contractDetails[0]['contract_url'];
			$reportLines[] = $langs->trans("CliChaumeilCronContractHeader", $contractUrl);
			foreach ($contractDetails as $detail) {
				$reportLines[] = "- " .
						$langs->trans("CliChaumeilCronLineDetail",
						$detail['line_id'],
						price($detail['old_price']),
						price($detail['new_price']),
						$detail['old_date']->format('Y-m-d') .' -> ' 	. $detail['new_date']->format('Y-m-d'));
			}
		}
		$reportLines[] = "";

		// --- Warnings Section ---
		if (!empty($this->warnings)) {
			$reportLines[] = "";
			$reportLines[] = $langs->trans("CliChaumeilCronSectionWarnings");
			foreach ($this->warnings as $warning) {
				$reportLines[] = "- " . $warning;
			}
		}

		// --- Notifications Section ---
		$reportLines[] = $langs->trans("CliChaumeilCronSectionNotifications");
		if (!empty($responsibleIds)) {
			$reportLines[] = "- " . $langs->trans("CliChaumeilCronEmailSent", count($responsibleIds));
		} else {
			$reportLines[] = "- " . $langs->trans("CliChaumeilCronEmailNotConfigured");
		}
		if (!empty($subscribedIds)) {
			$reportLines[] = "- " . $langs->trans("CliChaumeilCronNotifSent", count($subscribedIds));
		} else {
			$reportLines[] = "- " . $langs->trans("CliChaumeilCronNotifNotConfigured");
		}

		return implode("\n", $reportLines);
	}

	/**
	 * Fetches data for all contract lines that require a price revision.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getLinesToUpdateData(): array
	{
		global $conf;

		$lines = [];
		$now = new DateTime();

		$sql = "SELECT cd.rowid as line_id, c.ref as contract_ref, cd.subprice, ce.".self::EXTRAFIELD_REVISION_RATE." as taux_revision, cde." . self::EXTRAFIELD_REVISION_DATE . " as date_revision";
		$sql .= " FROM " . $this->db->prefix() . "contratdet as cd";
		$sql .= " INNER JOIN " . $this->db->prefix() . "contrat as c ON c.rowid = cd.fk_contrat";
		$sql .= " INNER JOIN " . $this->db->prefix() . "contrat_extrafields as ce ON c.rowid = ce.fk_object";
		$sql .= " INNER JOIN " . $this->db->prefix() . "contratdet_extrafields as cde ON cd.rowid = cde.fk_object";
		$sql .= " WHERE cd.statut = " . ContratLigne::STATUS_OPEN;
		$sql .= " AND cde." . self::EXTRAFIELD_REVISION_DATE . " <= '" . $this->db->escape($now->format('Y-m-d H:i:s')) . "'";
		$sql .= " AND cde." . self::EXTRAFIELD_REVISION_DATE . " IS NOT NULL";
		$sql .= " AND ce." . self::EXTRAFIELD_REVISION_RATE . " IS NOT NULL";
		$sql .= " AND c.entity = " . $conf->entity;

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__ . ' - ' . $this->db->lasterror(), LOG_ERR);
			return [];
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$lines[] = (array) $obj;
		}

		return $lines;
	}

	/**
	 * Sends a summary email to responsible users.
	 *
	 * @param int[]    $userIds           Array of user IDs to notify.
	 * @param int      $templateCode      The code of the email template.
	 * @param array    $modifiedContracts Array of modified contracts with their 'ref' and 'url'.
	 * @return void
	 */
	private function sendRecapEmail(array $userIds, int $templateCode, array $modifiedContracts): void
	{
		global $langs, $conf, $user;

		$contractList = '';
		foreach ($modifiedContracts as $contractData) {
			$contractList .= "- " . $contractData['url'] . "\n";
		}

		$formmail = new FormMail($this->db);
		$template = $formmail->getEMailTemplate($this->db, 'contract', $user, $langs, $templateCode);
		if ($template <= 0) {
			$this->warnings[] = $langs->trans("CliChaumeilWarningTemplateNotFound", $templateCode);
			return;
		}

		$substitutions = getCommonSubstitutionArray($langs);
		complete_substitutions_array($substitutions, $langs);
		$substitutions['__CONTRACTS_LIST__'] = $contractList;

		$body    = make_substitutions($template->content, $substitutions, $langs);

		$recipientEmails = [];
		foreach ($userIds as $userId) {
			$u = new User($this->db);
			if ($u->fetch($userId) > 0 && !empty($u->email)) {
				$recipientEmails[] = $u->email;
			} else {
				$this->warnings[] = $langs->trans("CliChaumeilWarningUserNotFoundOrNoEmail", $userId);
			}
		}

		if (!empty($recipientEmails)) {

			$recipients = implode(',', $recipientEmails);

			$mail = new CMailFile(
				$template->topic,
				$recipients,
				$user->email,
				$body,
				[], [], [],
				'',
				'',
				0,
				1
			);

			if (!$mail->sendfile()) {
				$this->warnings[] = $langs->trans("CliChaumeilWarningEmailFailed", '(group)', $mail->error);
			}
		}
	}

	/**
	 * Sends a push notification for ONE specific contract.
	 *
	 * @param int[]  $userIds      Array of user IDs to notify.
	 * @param int    $contractId   The contract ID.
	 * @param string $contractRef  The contract reference.
	 * @param string $contractLink HTML link to the contract.
	 * @return void
	 */
	private function sendAdvancedNotification(array $userIds, int $contractId, string $contractRef, string $contractLink): void
	{
		global $langs, $conf, $user;

		$res = dol_include_once('/advancednotifier/class/advnotification.class.php');
		if (!$res) {
			$warningMsg = $langs->trans("CliChaumeilWarningAdvancedNotifierMissing");
			$this->warnings[] = $warningMsg;
			dol_syslog(__METHOD__ . " - " . $warningMsg, LOG_WARNING);
			return;
		}
		$langs->load('advancednotifier@advancednotifier');

		$triggerCode = 'CLICHAUMEIL_CONTRACT_REVISION';
		$icon        = 'advancednotifier/img/notifpic/order_warn.png';
		$expireTs    = time() + 3600;

		$title = $langs->trans('CliChaumeilNotifTitleSingle', $contractRef);
		$body  = $langs->trans('CliChaumeilNotifBodySingle',$contractRef) ;
		$url   = dol_buildpath('/contrat/card.php', 2) . '?id=' . $contractId;

		foreach ($userIds as $uid) {
			$uid = (int) $uid;
			if ($uid <= 0) continue;

			$notif = new AdvNotification($this->db);
			$notif->entity      = (int) $conf->entity;
			$notif->fk_user     = $uid;
			$notif->fk_trigger  = $triggerCode;
			$notif->fk_object   = $contractId;
			$notif->fk_element  = 'contrat';
			$notif->send_method = 'push';
			$notif->title       = $title;
			$notif->body        = $body;
			$notif->url         = $url;
			$notif->icon        = $icon;
			$notif->expire      = $expireTs;

			$resCreate = $notif->create($user);
			if ($resCreate <= 0) {
				$this->warnings[] = $langs->trans("CliChaumeilWarningNotifFailed", $uid, $contractRef);
				$errorMsg = $langs->trans("CliChaumeilErrorNotifCreationFailed", $uid, $contractId);
				dol_syslog(__METHOD__ . " - " . $errorMsg . ": " . $notif->error, LOG_WARNING);
			}
		}
	}

}
