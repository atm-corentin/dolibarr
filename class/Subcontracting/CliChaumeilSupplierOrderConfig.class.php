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

/**
 * Shared configuration and constants for the ST-8 supplier order workflow.
 */
class CliChaumeilSupplierOrderConfig
{
	/**
	 * Global constant key storing the default supplier order email template id.
	 *
	 * @var string
	 */
	public const SUPPLIER_ORDER_EMAIL_TEMPLATE_KEY = 'CLICHAUMEIL_SUPPLIER_ORDER_EMAIL_TEMPLATE';

	/**
	 * Dolibarr email template type for supplier order sending.
	 *
	 * @var string
	 */
	public const MAIL_TEMPLATE_TYPE = 'order_supplier_send';

	/**
	 * Trigger name used when a supplier order has been sent by email.
	 *
	 * @var string
	 */
	public const SUPPLIER_ORDER_SENT_TRIGGER = 'ORDER_SUPPLIER_SENTBYMAIL';

	/**
	 * External contact code used for supplier order follow-up contacts.
	 *
	 * @var string
	 */
	public const SUPPLIER_ORDER_CONTACT_CODE = 'CUSTOMER';

	/**
	 * External contact source used for supplier order follow-up contacts.
	 *
	 * @var string
	 */
	public const SUPPLIER_ORDER_CONTACT_SOURCE = 'external';

	/**
	 * Agenda action type code used by supplier order email events.
	 *
	 * @var string
	 */
	public const MAIL_ACTION_TYPE_CODE = 'AC_OTH_AUTO';

	/**
	 * Prefix used for outgoing email tracking ids.
	 *
	 * @var string
	 */
	public const TRACK_ID_PREFIX = 'st8sord';

	/**
	 * Workflow status for a fully successful execution.
	 *
	 * @var string
	 */
	public const RESULT_SUCCESS = 'completed';

	/**
	 * Workflow status for a successful order creation with a non-blocking warning.
	 *
	 * @var string
	 */
	public const RESULT_WARNING = 'completed_with_warning';

	/**
	 * Workflow status for a blocking failure.
	 *
	 * @var string
	 */
	public const RESULT_ERROR = 'failed';

	/**
	 * Return the configured supplier order email template id.
	 *
	 * @return int
	 */
	public static function getConfiguredEmailTemplateId(): int
	{
		return (int) getDolGlobalString(self::SUPPLIER_ORDER_EMAIL_TEMPLATE_KEY);
	}

	/**
	 * Build the email tracking id for one supplier order.
	 *
	 * @param int $supplierOrderId Supplier order id.
	 * @return string
	 */
	public static function buildTrackId(int $supplierOrderId): string
	{
		return self::TRACK_ID_PREFIX.$supplierOrderId;
	}

	/**
	 * Build the output language instance for one supplier.
	 *
	 * @param Conf      $conf        Application configuration.
	 * @param Translate $langs       Current translation helper.
	 * @param string    $defaultLang Supplier default language.
	 * @return Translate
	 */
	public static function createOutputLangs(Conf $conf, Translate $langs, string $defaultLang = ''): Translate
	{
		$outputlangs = $langs;
		if (getDolGlobalInt('MAIN_MULTILANGS') && $defaultLang !== '') {
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($defaultLang);
		}

		$outputlangs->loadLangs(array('main', 'orders', 'suppliers', 'companies', 'products'));

		return $outputlangs;
	}

	/**
	 * Build the standard AJAX payload returned by the workflow.
	 *
	 * @param string               $status       Workflow status.
	 * @param string               $message      User-facing message.
	 * @param bool                 $success      Success flag.
	 * @param bool                 $shouldReload Tells the frontend whether a reload is required.
	 * @param array<string,mixed>  $debug        Debug payload.
	 * @param array<string,mixed>  $extra        Extra payload keys.
	 * @return array<string,mixed>
	 */
	public static function buildAjaxResponse(string $status, string $message, bool $success, bool $shouldReload, array $debug = array(), array $extra = array()): array
	{
		return array_merge(
			array(
				'success' => $success,
				'status' => $status,
				'should_reload' => $shouldReload,
				'message' => $message,
				'debug' => $debug,
			),
			$extra
		);
	}
}
