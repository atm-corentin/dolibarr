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
		$emailTemplate = getDolGlobalInt(self::CONF_EMAIL_TEMPLATE) ?? '';
		$subscribedUserIds = array_filter(array_map('intval', explode(',', getDolGlobalString(self::CONF_SUBSCRIBED_USERS) ?? '')));

		if (empty($emailTemplate) && !empty($responsibleUserIds)) {
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

		// --- 3. Process each line and collect detailed data for the report ---
		$this->db->begin();
		$processedDetails = []; // Array to store verbose details for the output
		$cachedContracts = [];

		try {
			foreach ($linesToUpdateData as $lineData) {
				$contractLine = new ContratLigne($this->db);
				if ($contractLine->fetch($lineData['line_id']) <= 0) {
					$warningMsg = $langs->trans("CliChaumeilWarningSkipLine", $lineData['line_id']);
					$this->warnings[] = $warningMsg;
					dol_syslog(__METHOD__ . "::warning - " . $warningMsg, LOG_WARNING);
					continue;
				}

				$contractId = $contractLine->fk_contrat;
				$contractUrl = '';

				// We check if we have already processed this contract to avoid reloading it
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

				// Store old values for the report
				$oldPrice = (float)$lineData['subprice'];
				$oldRevisionDate = new DateTime($lineData['date_revision']);

				// Calculate new values
				$newPrice = $oldPrice * (1 + ((float)$lineData['taux_revision'] / 100));
				$newRevisionDate = (clone $oldRevisionDate)->add(new DateInterval('P' . $revisionYearToAdd . 'Y'));

				// Update the Dolibarr object
				$contractLine->subprice = $newPrice;
				$contractLine->array_options['options_' . self::EXTRAFIELD_REVISION_DATE] = $newRevisionDate->format('Y-m-d H:i:s');
				$result = $contractLine->update($user);
				if ($result < 0) {
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

			// --- 4. Send notifications ---
			// We prepare a unique associative array [ref => url] for notifications.
			$modifiedContractsInfo = [];
			foreach ($processedDetails as $detail) {
				if (!isset($modifiedContractsInfo[$detail['contract_ref']])) {
					$modifiedContractsInfo[$detail['contract_ref']] = $detail['contract_url'];
				}
			}

			if (!empty($modifiedContractsInfo)) {
				$modifiedContractsRefs = array_keys($modifiedContractsInfo);
				if (!empty($responsibleUserIds) && !empty($emailTemplate)) {
					$this->sendRecapEmail($responsibleUserIds, $emailTemplate, $modifiedContractsRefs);
				}
				if (!empty($subscribedUserIds)) {
					$this->sendAdvancedNotification($subscribedUserIds, $modifiedContractsInfo);
				}
			}

			// Build the verbose output string
			$this->output = $this->buildVerboseOutput($processedDetails, $responsibleUserIds, $subscribedUserIds);
			dol_syslog(__METHOD__ . "::end - Cron job finished.", LOG_INFO);
			return 0;

		} catch (Exception $e) {
			$this->db->rollback();
			$this->error = $e->getMessage();
			$this->output = $langs->trans("CliChaumeilCronError") . ': ' . $this->error;
			dol_syslog(__METHOD__ . "::error - " . $this->error, LOG_ERR);
			return -1;
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
		$reportLines[] = "--- RAPPORT DE MISE À JOUR TARIFAIRE ---";
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
	 * @param int[]    $userIds         Array of user IDs to notify.
	 * @param string   $emailTemplate   The name of the email template.
	 * @param string[] $contractsRefs   Array of modified contract references.
	 * @return void
	 */
	private function sendRecapEmail(array $userIds, int $templateCode, array $contractsRefs): void
	{
		global $langs, $conf, $user;

		$contractList = '';
		$contract = new Contrat($this->db);
		foreach ($contractsRefs as $ref) {
			if ($contract->fetch(null, $ref) > 0) {
				$contractList .= "- " . $contract->getNomUrl(1) . "\n";
			} else {
				$contractList .= "- " . $ref . " (contract not found)\n";
			}
		}
		$substitutions = ['__CONTRACTS_LIST__' => $contractList];

		$formmail = new FormMail($this->db);
		$template = $formmail->getEMailTemplate($this->db, 'contract', $user, $langs, $templateCode);
		if ($template <= 0) {
			$this->warnings[] = $langs->trans("CliChaumeilWarningTemplateNotFound", $templateCode);
			return;
		}

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
	 * Envoie UNE notification (push) contenant la liste des contrats modifiés (liens HTML).
	 * $contractsInfo doit être de la forme [ 'REF1' => '<a href="...">REF1</a>', ... ].
	 */
	private function sendAdvancedNotification(array $userIds, array $contractsInfo): void
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
		$icon       = 'advancednotifier/img/notifpic/order_warn.png';
		$expireTs   = time() + 3600;

		// Construire le body en mode liste
		$lines = [];
		foreach ($contractsInfo as $ref => $linkHtml) {
			$lines[] = '- ' . $linkHtml; // Chaque contrat en puce
		}
		$body  = $langs->trans('CliChaumeilNotifBodyIntro') ;

		$title = $langs->trans('CliChaumeilNotifTitle', count($contractsInfo));
		$url   = dol_buildpath('contrat/list.php', 2);


		foreach ($userIds as $uid) {
			$uid = (int) $uid;
			if ($uid <= 0) continue;

			$notif = new AdvNotification($this->db);
			$notif->entity      = (int) $conf->entity;
			$notif->fk_user     = $uid;      // destinataire
			$notif->fk_trigger  = $triggerCode;
			$notif->fk_object   = 0;         // récap global
			$notif->fk_element  = 'contrat';
			$notif->send_method = 'push';    // bulle/cloche
			$notif->title       = $title;
			$notif->body        = $body;     // HTML autorisé (liens cliquables)
			$notif->url         = $url;
			$notif->icon        = $icon;
			$notif->expire      = $expireTs;

			$resCreate = $notif->create($user);
			if ($resCreate <= 0) {
				$this->warnings[] = $langs->trans("CliChaumeilWarningNotifFailed", $uid);
				dol_syslog(__METHOD__ . " - Failed to create notification for user #$uid: " . $notif->error, LOG_WARNING);
			}
		}
	}

}
