<?php
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

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * Test-only trigger capturing LINEPROPAL_MODIFY / LINEORDER_MODIFY events emitted by the
 * R2-ST-6 propagation service. Strictly idle outside PHPUnit: the capture runs only when
 * the CLICHAUMEIL_TEST_LINE_TRIGGER_CAPTURE global flag is set, which the targeted tests
 * enable in setUp and unset in tearDown.
 */
class InterfaceClichaumeilTestLineTriggerCapture extends DolibarrTriggers
{
	/**
	 * In-memory capture of the events received while the flag is enabled.
	 *
	 * Each entry: ['action' => string, 'class' => string, 'line_id' => int, 'buy_price_ht' => float, 'old_buy_price_ht' => float|null].
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public static $captures = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family      = "demo";
		$this->description = "Clichaumeil R2-ST-6 line trigger capture (test only).";
		$this->version     = self::VERSIONS['dev'];
		$this->picto       = 'clichaumeil@clichaumeil';
	}

	/**
	 * Reset the in-memory capture.
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		self::$captures = array();
	}

	/**
	 * Capture a line-modify event whenever the test flag is enabled.
	 *
	 * @param string       $action Event action code.
	 * @param CommonObject $object Object received by the trigger.
	 * @param User         $user   Current user.
	 * @param Translate    $langs  Translation handler.
	 * @param Conf         $conf   Application configuration.
	 * @return int Always 0 (never blocks the workflow).
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->global->CLICHAUMEIL_TEST_LINE_TRIGGER_CAPTURE)) {
			return 0;
		}
		if ($action !== 'LINEPROPAL_MODIFY' && $action !== 'LINEORDER_MODIFY') {
			return 0;
		}

		self::$captures[] = array(
			'action'           => $action,
			'class'            => get_class($object),
			'line_id'          => (int) ($object->id ?? $object->rowid ?? 0),
			'buy_price_ht'     => isset($object->buy_price_ht) ? (float) $object->buy_price_ht : null,
			'old_buy_price_ht' => (isset($object->oldline) && isset($object->oldline->buy_price_ht)) ? (float) $object->oldline->buy_price_ht : null,
		);

		return 0;
	}
}
