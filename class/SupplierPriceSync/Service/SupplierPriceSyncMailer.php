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
 * \file    class/SupplierPriceSync/Service/SupplierPriceSyncMailer.php
 * \ingroup clichaumeil
 * \brief   Sends the synchronisation report by email (secondary channel).
 */

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../ValueObject/CronRecipients.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncReport.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncIssue.php';

/**
 * Sends the synchronisation report email.
 *
 * The mail is a secondary channel: a sending failure never breaks the run; it
 * only adds an extra issue to the report.
 */
final class SupplierPriceSyncMailer
{
	/**
	 * Send the report to the configured recipients.
	 *
	 * @param SupplierPriceSyncReport $report     Run report.
	 * @param CronRecipients          $recipients Validated recipients.
	 * @param Translate               $langs      Translator (module file loaded).
	 * @return void
	 */
	public function send(SupplierPriceSyncReport $report, CronRecipients $recipients, Translate $langs): void
	{
		if ($recipients->isEmpty()) {
			return;
		}

		$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
		if ($from === '' || !isValidEmail($from)) {
			dol_syslog('SupplierPriceSyncMailer::send invalid MAIN_MAIL_EMAIL_FROM', LOG_ERR);
			$report->addIssue(new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_WARNING,
				SupplierPriceSyncConstants::ISSUE_MISSING_CONFIGURATION,
				$langs->trans('CliChaumeil_SupplierPriceSync_MISSING_CONFIGURATION')
			));

			return;
		}

		$subject = $report->buildMailSubject($langs);
		$body = $report->buildMailBody($langs);
		$to = implode(',', $recipients->all());

		$mail = new CMailFile($subject, $to, $from, $body);
		$result = $mail->sendfile();
		if (!$result) {
			dol_syslog('SupplierPriceSyncMailer::send failed: ' . $mail->error, LOG_ERR);
			$report->addIssue(new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_WARNING,
				SupplierPriceSyncConstants::ISSUE_MAIL_FAILED,
				$mail->error
			));
		}
	}
}
