<?php

declare(strict_types=1);

/**
 * Value object for the RFA negotiation reminder cron configuration.
 */
class RfaNegotiationReminderConfig
{
	/**
	 * @var string Parameter name for recipients.
	 */
	public const PARAM_RECIPIENTS = 'recipients';

	/**
	 * @var string Parameter name for the email template code.
	 */
	public const PARAM_TEMPLATE_CODE = 'email_template_code';

	/**
	 * @var string Separator used in the recipients parameter.
	 */
	private const RECIPIENT_SEPARATOR = ';';

	/**
	 * @var array<int,string>
	 */
	private array $recipients;

	/**
	 * @var string
	 */
	private string $templateCode;

	/**
	 * Build a validated configuration object from cron method arguments.
	 *
	 * @param string $recipients Raw recipients string passed by the cron engine.
	 * @param string $templateCode Email template code passed by the cron engine.
	 * @return self
	 * @throws InvalidArgumentException When parameters are missing or invalid.
	 */
	public static function fromMethodArguments(string $recipients, string $templateCode): self
	{
		$normalizedRecipients = self::normalizeRecipients($recipients);
		$templateCode = trim($templateCode);

		if (empty($normalizedRecipients)) {
			throw new InvalidArgumentException('CliChaumeil_RfaReminderErrorRecipientsRequired');
		}

		if ($templateCode === '') {
			throw new InvalidArgumentException('CliChaumeil_RfaReminderErrorTemplateCodeRequired');
		}

		return new self($normalizedRecipients, $templateCode);
	}

	/**
	 * Constructor.
	 *
	 * @param array<int,string> $recipients Normalized recipient list.
	 * @param string            $templateCode Email template code.
	 */
	private function __construct(array $recipients, string $templateCode)
	{
		$this->recipients = $recipients;
		$this->templateCode = $templateCode;
	}

	/**
	 * Return the normalized recipients.
	 *
	 * @return array<int,string>
	 */
	public function getRecipients(): array
	{
		return $this->recipients;
	}

	/**
	 * Return the email template code.
	 *
	 * @return string
	 */
	public function getTemplateCode(): string
	{
		return $this->templateCode;
	}

	/**
	 * Normalize and validate the recipient list.
	 *
	 * @param string $rawRecipients Raw recipient string.
	 * @return array<int,string>
	 * @throws InvalidArgumentException When at least one email address is invalid.
	 */
	private static function normalizeRecipients(string $rawRecipients): array
	{
		$rawEntries = explode(self::RECIPIENT_SEPARATOR, $rawRecipients);
		$normalizedRecipients = array();

		foreach ($rawEntries as $rawEntry) {
			$email = trim($rawEntry);
			if ($email === '') {
				continue;
			}

			if (!isValidEmail($email)) {
				throw new InvalidArgumentException('CliChaumeil_RfaReminderErrorInvalidRecipient');
			}

			$normalizedRecipients[] = $email;
		}

		$normalizedRecipients = array_values(array_unique($normalizedRecipients));

		return $normalizedRecipients;
	}
}
