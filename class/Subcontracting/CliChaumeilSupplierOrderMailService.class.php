<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderConfig.class.php';

/**
 * Send the supplier order email and trigger the agenda traceability event.
 */
class CliChaumeilSupplierOrderMailService
{
	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Application configuration.
	 *
	 * @var Conf
	 */
	private Conf $conf;

	/**
	 * Translation handler.
	 *
	 * @var Translate
	 */
	private Translate $langs;

	/**
	 * Constructor.
	 *
	 * @param DoliDB    $db    Database handler.
	 * @param Conf      $conf  Application configuration.
	 * @param Translate $langs Translation handler.
	 */
	public function __construct(DoliDB $db, Conf $conf, Translate $langs)
	{
		$this->db = $db;
		$this->conf = $conf;
		$this->langs = $langs;
	}

	/**
	 * Send one supplier order email.
	 *
	 * @param CommandeFournisseur       $supplierOrder        Supplier order.
	 * @param array{emails:array<int,string>,contact_ids:array<int,int>,source_used:string} $recipientResolution Resolved recipients.
	 * @param User                      $user                 Current user.
	 * @return array{sent:bool,warning_message:string,subject:string,to:string,log_message:string}
	 */
	public function send(CommandeFournisseur $supplierOrder, array $recipientResolution, User $user): array
	{
		$recipientList = implode(',', $recipientResolution['emails']);
		if ($recipientList === '') {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8NoEmailRecipient'),
				'subject' => '',
				'to' => '',
				'log_message' => '',
			);
		}

		$sender = $this->buildSender($user);
		if ($sender === '') {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8SenderEmailMissing'),
				'subject' => '',
				'to' => $recipientList,
				'log_message' => '',
			);
		}

		$templateId = CliChaumeilSupplierOrderConfig::getConfiguredEmailTemplateId();
		if ($templateId <= 0) {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8MailTemplateMissingConfig'),
				'subject' => '',
				'to' => $recipientList,
				'log_message' => '',
			);
		}

		$outputlangs = CliChaumeilSupplierOrderConfig::createOutputLangs(
			$this->conf,
			$this->langs,
			(string) ($supplierOrder->thirdparty->default_lang ?? '')
		);

		$formMail = new FormMail($this->db);
		$template = $formMail->getEMailTemplate(
			$this->db,
			CliChaumeilSupplierOrderConfig::MAIL_TEMPLATE_TYPE,
			$user,
			$outputlangs,
			$templateId
		);
		if (!($template instanceof ModelMail) || (int) $template->id <= 0) {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8MailTemplateNotFound'),
				'subject' => '',
				'to' => $recipientList,
				'log_message' => 'Configured supplier order email template #'.$templateId.' was not found.',
			);
		}

		$formMail->setSubstitFromObject($supplierOrder, $outputlangs);
		$substitutionarray = is_array($formMail->substit) ? $formMail->substit : array();
		$substitutionarray['__SENDEREMAIL_SIGNATURE__'] = (string) $user->signature;
		$substitutionarray['__EMAIL__'] = $recipientList;
		$substitutionarray['__CHECK_READ__'] = (!empty($supplierOrder->thirdparty) && !empty($supplierOrder->thirdparty->id))
			? '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag=undefined&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY').'-undefined', 'md5').'" width="1" height="1" style="width:1px;height:1px" border="0"/>'
			: '';
		$substitutionarray['__LINES__'] = $this->buildLinesSubstitutionPayload(
			(string) $template->content_lines,
			is_array($formMail->substit_lines) ? $formMail->substit_lines : array(),
			$outputlangs
		);

		$parameters = array('mode' => 'formemail');
		complete_substitutions_array($substitutionarray, $outputlangs, $supplierOrder, $parameters);

		$subjectTemplate = (string) $template->topic;
		if ($subjectTemplate === '') {
			$subjectTemplate = $outputlangs->transnoentitiesnoconv('SendOrderRef');
		}

		$messageTemplate = (string) $template->content;
		if ($messageTemplate === '') {
			$messageTemplate = $outputlangs->transnoentitiesnoconv('PredefinedMailContentSendSupplierOrder');
		}
		$messageTemplate = $this->normalizeMessageTemplate($messageTemplate, $substitutionarray);

		$subject = make_substitutions($subjectTemplate, $substitutionarray, $outputlangs, 1);
		$message = make_substitutions($messageTemplate, $substitutionarray, $outputlangs, 1);

		if (method_exists($supplierOrder, 'makeSubstitution')) {
			$subject = $supplierOrder->makeSubstitution($subject);
			$message = $supplierOrder->makeSubstitution($message);
		}

		$documentPath = $this->buildMainDocumentPath($supplierOrder);
		if (!dol_is_file($documentPath)) {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8PdfGenerationFailed'),
				'subject' => $subject,
				'to' => $recipientList,
				'log_message' => 'Main supplier order document is missing at path '.$documentPath,
			);
		}

		$emailCc = $this->applySubstitutionsToHeader((string) $template->email_tocc, $substitutionarray, $outputlangs);
		$emailBcc = $this->applySubstitutionsToHeader((string) $template->email_tobcc, $substitutionarray, $outputlangs);
		$trackId = CliChaumeilSupplierOrderConfig::buildTrackId((int) $supplierOrder->id);
		$attachedFiles = array(
			'paths' => array($documentPath),
			'names' => array(basename($documentPath)),
			'mimes' => array(dol_mimetype($documentPath)),
		);

		$mailFile = $this->buildMailFile(
			$subject,
			$recipientList,
			$sender,
			$message,
			$attachedFiles,
			$emailCc,
			$emailBcc,
			$trackId
		);

		if ($mailFile->error !== '' || !empty($mailFile->errors)) {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8MailSendFailedGeneric'),
				'subject' => $subject,
				'to' => $recipientList,
				'log_message' => $this->buildMailErrorMessage($mailFile),
			);
		}

		$result = $this->sendMailFile($mailFile);
		if (!$result) {
			return array(
				'sent' => false,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8MailSendFailedGeneric'),
				'subject' => $subject,
				'to' => $recipientList,
				'log_message' => $this->buildMailErrorMessage($mailFile),
			);
		}

		$this->hydrateOrderMailContext(
			$supplierOrder,
			$recipientResolution['contact_ids'],
			$sender,
			$subject,
			$recipientList,
			$emailCc,
			$emailBcc,
			$message,
			$trackId,
			$mailFile,
			$attachedFiles
		);

		$triggerResult = $this->triggerSupplierOrderSentEvent($supplierOrder, $user);
		if ($triggerResult < 0) {
			return array(
				'sent' => true,
				'warning_message' => $this->langs->transnoentitiesnoconv('CliChaumeil_St8AgendaCreationFailed'),
				'subject' => $subject,
				'to' => $recipientList,
				'log_message' => $this->buildSupplierOrderErrorMessage($supplierOrder, 'Unable to trigger supplier order sent-by-mail event.'),
			);
		}

		return array(
			'sent' => true,
			'warning_message' => '',
			'subject' => $subject,
			'to' => $recipientList,
			'log_message' => '',
		);
	}

	/**
	 * Build the CMailFile instance used to send the supplier order.
	 *
	 * @param string                                                         $subject       Mail subject.
	 * @param string                                                         $recipientList Mail recipients.
	 * @param string                                                         $sender        Mail sender.
	 * @param string                                                         $message       Mail body.
	 * @param array{paths:array<int,string>,names:array<int,string>,mimes:array<int,string>} $attachedFiles Attached files.
	 * @param string                                                         $emailCc       Carbon-copy recipients.
	 * @param string                                                         $emailBcc      Blind carbon-copy recipients.
	 * @param string                                                         $trackId       Tracking id.
	 * @return CMailFile
	 */
	protected function buildMailFile(
		string $subject,
		string $recipientList,
		string $sender,
		string $message,
		array $attachedFiles,
		string $emailCc,
		string $emailBcc,
		string $trackId
	): CMailFile {
		return new CMailFile(
			$subject,
			$recipientList,
			$sender,
			$message,
			$attachedFiles['paths'],
			$attachedFiles['mimes'],
			$attachedFiles['names'],
			$emailCc,
			$emailBcc,
			0,
			-1,
			'',
			'',
			$trackId,
			'',
			'standard',
			''
		);
	}

	/**
	 * Execute the actual mail transport.
	 *
	 * @param CMailFile $mailFile Mail sender.
	 * @return bool
	 */
	protected function sendMailFile(CMailFile $mailFile): bool
	{
		return (bool) $mailFile->sendfile();
	}

	/**
	 * Trigger the standard supplier-order sent-by-mail event.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @param User                $user          Current user.
	 * @return int
	 */
	protected function triggerSupplierOrderSentEvent(CommandeFournisseur $supplierOrder, User $user): int
	{
		return (int) $supplierOrder->call_trigger(CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_SENT_TRIGGER, $user);
	}

	/**
	 * Build the sender header from the current user.
	 *
	 * @param User $user Current user.
	 * @return string
	 */
	private function buildSender(User $user): string
	{
		$email = trim((string) $user->email);
		if ($email === '' || !isValidEmail($email)) {
			return '';
		}

		return dol_string_nospecial($user->getFullName($this->langs), ' ', array(',')) . ' <' . $email . '>';
	}

	/**
	 * Build the repeated lines payload for the email template.
	 *
	 * @param string                           $contentLines Template content for one line.
	 * @param array<int,array<string,string>>  $substitLines Line substitutions.
	 * @param Translate                        $outputlangs  Output translations.
	 * @return string
	 */
	private function buildLinesSubstitutionPayload(string $contentLines, array $substitLines, Translate $outputlangs): string
	{
		if ($contentLines === '' || empty($substitLines)) {
			return '';
		}

		$lines = array();
		foreach ($substitLines as $substitutionLine) {
			$lines[] = make_substitutions($contentLines, $substitutionLine, $outputlangs, 1);
		}

		return implode("\n", $lines);
	}

	/**
	 * Normalize the message template according to its HTML/text content.
	 *
	 * @param string               $messageTemplate   Raw message template.
	 * @param array<string,string> $substitutionarray Substitution payload.
	 * @return string
	 */
	private function normalizeMessageTemplate(string $messageTemplate, array &$substitutionarray): string
	{
		$messageTemplate = str_replace('\n', "\n", $messageTemplate);

		$containsHtml = dol_textishtml($messageTemplate)
			|| (!empty($substitutionarray['__SENDEREMAIL_SIGNATURE__']) && dol_textishtml((string) $substitutionarray['__SENDEREMAIL_SIGNATURE__']))
			|| (!empty($substitutionarray['__LINES__']) && dol_textishtml((string) $substitutionarray['__LINES__']));

		if (!$containsHtml) {
			return $messageTemplate;
		}

		if (!empty($substitutionarray['__SENDEREMAIL_SIGNATURE__']) && !dol_textishtml((string) $substitutionarray['__SENDEREMAIL_SIGNATURE__'])) {
			$substitutionarray['__SENDEREMAIL_SIGNATURE__'] = dol_nl2br((string) $substitutionarray['__SENDEREMAIL_SIGNATURE__']);
		}
		if (!empty($substitutionarray['__LINES__']) && !dol_textishtml((string) $substitutionarray['__LINES__'])) {
			$substitutionarray['__LINES__'] = dol_nl2br((string) $substitutionarray['__LINES__']);
		}
		if (!dol_textishtml($messageTemplate)) {
			$messageTemplate = dol_nl2br($messageTemplate);
		}

		return $messageTemplate;
	}

	/**
	 * Apply substitutions to a mail header value.
	 *
	 * @param string               $value             Raw header value.
	 * @param array<string,string> $substitutionarray Substitutions.
	 * @param Translate            $outputlangs       Output translations.
	 * @return string
	 */
	private function applySubstitutionsToHeader(string $value, array $substitutionarray, Translate $outputlangs): string
	{
		if ($value === '') {
			return '';
		}

		return make_substitutions($value, $substitutionarray, $outputlangs, 1);
	}

	/**
	 * Build the full path of the current main supplier order document.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @return string
	 */
	private function buildMainDocumentPath(CommandeFournisseur $supplierOrder): string
	{
		return DOL_DATA_ROOT.'/'.ltrim((string) $supplierOrder->last_main_doc, '/');
	}

	/**
	 * Copy the sent-mail context onto the business object before firing the trigger.
	 *
	 * @param CommandeFournisseur       $supplierOrder Supplier order.
	 * @param array<int,int>            $contactIds    Linked contact ids.
	 * @param string                    $sender        Mail sender.
	 * @param string                    $subject       Mail subject.
	 * @param string                    $recipientList Mail recipients.
	 * @param string                    $emailCc       Carbon-copy recipients.
	 * @param string                    $emailBcc      Blind carbon-copy recipients.
	 * @param string                    $message       Mail message.
	 * @param string                    $trackId       Tracking id.
	 * @param CMailFile                 $mailFile      Mail file object.
	 * @param array{paths:array<int,string>,names:array<int,string>,mimes:array<int,string>} $attachedFiles Attached files.
	 * @return void
	 */
	private function hydrateOrderMailContext(
		CommandeFournisseur $supplierOrder,
		array $contactIds,
		string $sender,
		string $subject,
		string $recipientList,
		string $emailCc,
		string $emailBcc,
		string $message,
		string $trackId,
		CMailFile $mailFile,
		array $attachedFiles
	): void {
		$eventLabel = $this->langs->transnoentitiesnoconv(
			'MailSentByTo',
			CMailFile::getValidAddress($sender, 4, 0, 1),
			CMailFile::getValidAddress($recipientList, 4, 0, 1)
		);

		$supplierOrder->socid = !empty($supplierOrder->thirdparty->id) ? (int) $supplierOrder->thirdparty->id : (int) $supplierOrder->fourn_id;
		$supplierOrder->sendtoid = $contactIds;
		$supplierOrder->actiontypecode = CliChaumeilSupplierOrderConfig::MAIL_ACTION_TYPE_CODE;
		$supplierOrder->actionmsg = $message;
		$supplierOrder->actionmsg2 = getDolGlobalString('MAIN_MAIL_REPLACE_EVENT_TITLE_BY_EMAIL_SUBJECT') ? $subject : $eventLabel;
		$supplierOrder->trackid = $trackId;
		$supplierOrder->fk_element = $supplierOrder->id;
		$supplierOrder->elementtype = $supplierOrder->element;
		$supplierOrder->attachedfiles = $attachedFiles;
		$supplierOrder->email_msgid = $mailFile->msgid;
		$supplierOrder->email_from = $sender;
		$supplierOrder->email_subject = $subject;
		$supplierOrder->email_to = $recipientList;
		$supplierOrder->email_tocc = $emailCc;
		$supplierOrder->email_tobcc = $emailBcc;
	}

	/**
	 * Build one normalized warning message from the mail sender.
	 *
	 * @param CMailFile $mailFile Mail sender.
	 * @return string
	 */
	private function buildMailErrorMessage(CMailFile $mailFile): string
	{
		$messages = array();
		if ($mailFile->error !== '') {
			$messages[] = $mailFile->error;
		}
		if (!empty($mailFile->errors)) {
			$messages = array_merge($messages, $mailFile->errors);
		}

		if (empty($messages) && getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
			$messages[] = 'MAIN_DISABLE_ALL_MAILS';
		}

		return !empty($messages) ? implode(' | ', $messages) : 'Unknown error';
	}

	/**
	 * Build one normalized error message from the supplier order object.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @param string              $fallbackError Fallback error.
	 * @return string
	 */
	private function buildSupplierOrderErrorMessage(CommandeFournisseur $supplierOrder, string $fallbackError): string
	{
		if (!empty($supplierOrder->error)) {
			return (string) $supplierOrder->error;
		}
		if (!empty($supplierOrder->errors) && is_array($supplierOrder->errors)) {
			return implode(' | ', $supplierOrder->errors);
		}
		if ($this->db->lasterror() !== '') {
			return $this->db->lasterror();
		}

		return $fallbackError;
	}
}
