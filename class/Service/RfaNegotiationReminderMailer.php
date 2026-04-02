<?php

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * Service responsible for preparing and sending the RFA reminder email.
 */
class RfaNegotiationReminderMailer
{
	/**
	 * @var string Template type used for the reminder.
	 */
	public const TEMPLATE_TYPE = 'thirdparty';

	/**
	 * @var string Substitution token for the current year.
	 */
	public const TEMPLATE_TOKEN_YEAR = '__YEAR__';

	/**
	 * @var string Substitution token for the final list URL.
	 */
	public const TEMPLATE_TOKEN_RFA_LIST_URL = '__RFA_LIST_URL__';

	/**
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * @var Translate
	 */
	private Translate $langs;

	/**
	 * Constructor.
	 *
	 * @param DoliDB     $db Database handler.
	 * @param Translate  $langs Translation object.
	 */
	public function __construct(DoliDB $db, Translate $langs)
	{
		$this->db = $db;
		$this->langs = $langs;
	}

	/**
	 * Send the reminder email.
	 *
	 * @param array<int,string> $recipients Recipient list.
	 * @param string            $templateCode Email template code.
	 * @param int               $year Target year.
	 * @param string            $rfaListUrl Final RFA list URL.
	 * @return void
	 * @throws RuntimeException When template loading or email sending fails.
	 */
	public function send(array $recipients, string $templateCode, int $year, string $rfaListUrl): void
	{
		$user = $this->getExecutionUser();
		$template = $this->fetchTemplateByCode($templateCode, $user);
		$substitutions = $this->buildSubstitutions($year, $rfaListUrl);

		$subject = make_substitutions((string) $template->topic, $substitutions, $this->langs, 1);
		$content = make_substitutions((string) $template->content, $substitutions, $this->langs, 1);
		$from = $this->resolveSenderAddress($template);

		$mail = new CMailFile(
			$subject,
			implode(',', $recipients),
			$from,
			$content,
			array(),
			array(),
			array(),
			(string) $template->email_tocc,
			(string) $template->email_tobcc,
			0,
			1,
			getDolGlobalString('MAIN_MAIL_ERRORS_TO')
		);

		if (!$mail->sendfile()) {
			$mailError = !empty($mail->error) ? $mail->error : 'Unknown email sending error';
			throw new RuntimeException($this->langs->trans('CliChaumeil_RfaReminderErrorEmailSendFailed', $mailError));
		}
	}

	/**
	 * Load the email template by its code.
	 *
	 * @param string $templateCode Template code stored in c_email_templates.label.
	 * @param User   $user Execution user.
	 * @return ModelMail
	 * @throws RuntimeException When the template cannot be found.
	 */
	private function fetchTemplateByCode(string $templateCode, User $user): ModelMail
	{
		$formmail = new FormMail($this->db);
		$template = $formmail->getEMailTemplate($this->db, self::TEMPLATE_TYPE, $user, $this->langs, 0, 1, $templateCode);

		if (!($template instanceof ModelMail) || empty($template->id)) {
			throw new RuntimeException($this->langs->trans('CliChaumeil_RfaReminderErrorTemplateNotFound', $templateCode));
		}

		return $template;
	}

	/**
	 * Build the substitutions used in the email content.
	 *
	 * @param int    $year Target year.
	 * @param string $rfaListUrl Final RFA list URL.
	 * @return array<string,string>
	 */
	private function buildSubstitutions(int $year, string $rfaListUrl): array
	{
		$substitutions = getCommonSubstitutionArray($this->langs);
		complete_substitutions_array($substitutions, $this->langs);
		$substitutions[self::TEMPLATE_TOKEN_YEAR] = (string) $year;
		$substitutions[self::TEMPLATE_TOKEN_RFA_LIST_URL] = $rfaListUrl;

		return $substitutions;
	}

	/**
	 * Resolve the sender address.
	 *
	 * @param ModelMail $template Email template.
	 * @return string
	 * @throws RuntimeException When no sender address is configured.
	 */
	private function resolveSenderAddress(ModelMail $template): string
	{
		$from = trim((string) $template->email_from);
		if ($from === '') {
			$from = trim(getDolGlobalString('MAIN_MAIL_EMAIL_FROM'));
		}

		if ($from === '' || !isValidEmail($from, 0, 1)) {
			throw new RuntimeException($this->langs->trans('CliChaumeil_RfaReminderErrorMissingSender'));
		}

		return $from;
	}

	/**
	 * Get the execution user required by Dolibarr APIs.
	 *
	 * @return User
	 * @throws RuntimeException When the fallback admin user cannot be loaded.
	 */
	private function getExecutionUser(): User
	{
		global $user;

		if ($user instanceof User && !empty($user->id)) {
			return $user;
		}

		$fallbackUser = new User($this->db);
		if ($fallbackUser->fetch(1) <= 0) {
			throw new RuntimeException($this->langs->trans('CliChaumeil_RfaReminderErrorExecutionUser'));
		}

		return $fallbackUser;
	}
}
