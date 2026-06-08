<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../class/actions_clichaumeil.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Unit tests for the formConfirm warning injection helper of ActionsClichaumeil.
 *
 * Only the pure HTML injection helper is tested, not the full Dolibarr hook
 * lifecycle (GETPOST/globals/popup JS), which would be fragile.
 *
 * @backupGlobals disabled
 */
class ActionsClichaumeilMinimumMarginFormConfirmTest extends CommonClassTest
{
	/**
	 * Invoke the private injection helper through reflection.
	 *
	 * @param string $html        Native formconfirm HTML.
	 * @param string $warningHtml Warning fragment to inject.
	 * @return string Modified HTML.
	 */
	private function invokeInjection(string $html, string $warningHtml): string
	{
		global $db;
		$actions = new ActionsClichaumeil($db);
		$method = new ReflectionMethod(ActionsClichaumeil::class, 'injectMinimumMarginWarningIntoFormConfirm');
		$method->setAccessible(true);

		return (string) $method->invoke($actions, $html, $warningHtml);
	}

	/**
	 * With the AJAX popup marker present, the warning must be inserted inside the
	 * dialog container, before the closing div preceding the popup code.
	 *
	 * @return void
	 */
	public function testWarningInsertedInsideModalWhenMarkerPresent(): void
	{
		$warning = '<div class="confirmmessage center"><strong class="error">WARNING</strong></div>';
		$html = <<<'HTML'
<div id="dialog-confirm" title="Validate" style="display: none;"><div class="confirmmessage">Confirm validation?</div></div>

<!-- begin code of popup for formconfirm page=/comm/propal/card.php -->
<script>/* popup */</script>
HTML;

		$result = $this->invokeInjection($html, $warning);

		// Warning is present and located before the popup code marker (i.e. inside the modal).
		$this->assertStringContainsString($warning, $result);
		$this->assertLessThan(
			strpos($result, '<!-- begin code of popup for formconfirm'),
			strpos($result, $warning)
		);
		// Warning must come after the native confirm message (appended inside the dialog).
		$this->assertGreaterThan(
			strpos($result, 'Confirm validation?'),
			strpos($result, $warning)
		);
	}

	/**
	 * Without the expected marker, the helper must fall back to a non-destructive
	 * prepend (the original HTML is preserved).
	 *
	 * @return void
	 */
	public function testFallbackPrependWhenMarkerMissing(): void
	{
		$warning = '<div class="confirmmessage center"><strong class="error">WARNING</strong></div>';
		$html = '<div class="someothermodal">No marker here</div>';

		$result = $this->invokeInjection($html, $warning);

		$this->assertStringContainsString($warning, $result);
		$this->assertStringContainsString($html, $result);
		$this->assertStringStartsWith($warning, $result);
	}
}
