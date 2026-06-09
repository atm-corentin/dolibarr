<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../../../societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../class/CliChaumeilMinimumMarginWarningGuard.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Unit tests for CliChaumeilMinimumMarginWarningGuard.
 *
 * The proposal/order lines are built in memory (no persistence) so the margin
 * logic can be exercised deterministically through the public entry point.
 *
 * @backupGlobals disabled
 */
class CliChaumeilMinimumMarginWarningGuardTest extends CommonClassTest
{
	/**
	 * @var array<string,mixed> Backup of the Discountrules globals altered by tests.
	 */
	private $globalsBackup = array();

	/**
	 * @var bool Backup of the discountrules module enablement flag.
	 */
	private $discountrulesModuleBackup = false;

	/**
	 * Enable discountrules and snapshot the globals before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf;
		parent::setUp();

		$this->discountrulesModuleBackup = !empty($conf->modules['discountrules']);
		$conf->modules['discountrules'] = 1;

		foreach ($this->managedGlobalKeys() as $key) {
			$this->globalsBackup[$key] = getDolGlobalString($key);
		}
	}

	/**
	 * Restore globals and module flag after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		foreach ($this->globalsBackup as $key => $value) {
			$conf->global->$key = $value;
		}

		if ($this->discountrulesModuleBackup) {
			$conf->modules['discountrules'] = 1;
		} else {
			unset($conf->modules['discountrules']);
		}

		parent::tearDown();
	}

	/**
	 * Discountrules globals managed (saved/restored) by these tests.
	 *
	 * @return string[]
	 */
	private function managedGlobalKeys(): array
	{
		return array(
			'DISCOUNTRULES_USE_MARKUP_MARGIN_RATE',
			'DISCOUNTRULES_MARKUP_MARGIN_RATE',
			'DISCOUNTRULES_MINIMUM_RATE',
		);
	}

	/**
	 * Configure the Discountrules globals for a test.
	 *
	 * @param int    $useMarkupMargin  Enable flag (1 to activate).
	 * @param int    $markupMarginRate Rate type (0=mark, 1=margin).
	 * @param string $minimumRate      Global minimum rate.
	 * @return void
	 */
	private function configureDiscountrules(int $useMarkupMargin, int $markupMarginRate, string $minimumRate): void
	{
		global $conf;
		$conf->global->DISCOUNTRULES_USE_MARKUP_MARGIN_RATE = $useMarkupMargin;
		$conf->global->DISCOUNTRULES_MARKUP_MARGIN_RATE = $markupMarginRate;
		$conf->global->DISCOUNTRULES_MINIMUM_RATE = $minimumRate;
	}

	/**
	 * Build an in-memory proposal line.
	 *
	 * @param array<string,mixed> $values Line property overrides.
	 * @return PropaleLigne
	 */
	private function makeLine(array $values): PropaleLigne
	{
		global $db;
		$line = new PropaleLigne($db);
		$line->fk_product = 0;
		$line->product_type = 0;
		$line->qty = 1;
		$line->tva_tx = 20;
		$line->localtax1_tx = 0;
		$line->localtax2_tx = 0;
		$line->fk_fournprice = 0;
		$line->remise_percent = 0;
		$line->special_code = 0;
		$line->fk_remise_except = 0;

		foreach ($values as $key => $value) {
			$line->$key = $value;
		}

		return $line;
	}

	/**
	 * Build an in-memory proposal carrying the provided lines.
	 *
	 * @param PropaleLigne[]    $lines             Proposal lines.
	 * @param float|string|null $thirdpartyMinRate Thirdparty extrafield value (null when unset).
	 * @return Propal
	 */
	private function makeProposal(array $lines, $thirdpartyMinRate = null): Propal
	{
		global $db;
		$proposal = new Propal($db);
		$proposal->id = 999999;
		$proposal->element = 'propal';
		$proposal->socid = 0;
		$proposal->lines = $lines;

		$thirdparty = new Societe($db);
		$thirdparty->id = 888888;
		$thirdparty->array_options = array();
		if ($thirdpartyMinRate !== null) {
			$thirdparty->array_options['options_discountrules_min_markup_margin_percent'] = $thirdpartyMinRate;
		}
		$proposal->thirdparty = $thirdparty;

		return $proposal;
	}

	/**
	 * A line below the global mark rate must trigger the warning.
	 *
	 * @return void
	 */
	public function testGlobalMarkRateBelowThresholdTriggersWarning(): void
	{
		global $db;
		$this->configureDiscountrules(1, 0, '5');

		// PA 100, PV 102 => mark rate ~1.96% < 5%.
		$proposal = $this->makeProposal(array($this->makeLine(array('pa_ht' => 100, 'subprice' => 102, 'total_ht' => 102))));

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertTrue($guard->hasLineBelowMinimumRate($proposal));
	}

	/**
	 * A line whose rate exceeds the threshold must not trigger the warning.
	 *
	 * @return void
	 */
	public function testRateAboveThresholdDoesNotTrigger(): void
	{
		global $db;
		$this->configureDiscountrules(1, 0, '5');

		// PA 100, PV 200 => mark rate 50% > 5%.
		$proposal = $this->makeProposal(array($this->makeLine(array('pa_ht' => 100, 'subprice' => 200, 'total_ht' => 200))));

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertFalse($guard->hasLineBelowMinimumRate($proposal));
	}

	/**
	 * A line with no cost price must be ignored even if below the threshold.
	 *
	 * @return void
	 */
	public function testZeroCostPriceLineIsIgnored(): void
	{
		global $db;
		$this->configureDiscountrules(1, 0, '5');

		$proposal = $this->makeProposal(array($this->makeLine(array('pa_ht' => 0, 'subprice' => 1, 'total_ht' => 1))));

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertFalse($guard->hasLineBelowMinimumRate($proposal));
	}

	/**
	 * A subtotal/title line must be excluded from the control.
	 *
	 * @return void
	 */
	public function testSubtotalLineIsIgnored(): void
	{
		global $db;
		$this->configureDiscountrules(1, 0, '5');

		$proposal = $this->makeProposal(array($this->makeLine(array(
			'pa_ht' => 100,
			'subprice' => 102,
			'total_ht' => 102,
			'special_code' => 104777,
			'product_type' => 9,
		))));

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertFalse($guard->hasLineBelowMinimumRate($proposal));
	}

	/**
	 * The thirdparty threshold must take priority over the global one.
	 *
	 * @return void
	 */
	public function testThirdpartyThresholdTakesPriorityOverGlobal(): void
	{
		global $db;
		// Global = 1 (line mark rate ~1.96% > 1% => no trigger on global)
		// Thirdparty = 40 (line mark rate ~1.96% < 40% => trigger when priority is honored).
		$this->configureDiscountrules(1, 0, '1');

		$proposal = $this->makeProposal(
			array($this->makeLine(array('pa_ht' => 100, 'subprice' => 102, 'total_ht' => 102))),
			40
		);

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertTrue($guard->hasLineBelowMinimumRate($proposal));
	}

	/**
	 * When the rate control is disabled, the guard must always return false.
	 *
	 * @return void
	 */
	public function testRateControlDisabledReturnsFalse(): void
	{
		global $db;
		$this->configureDiscountrules(0, 0, '5');

		$proposal = $this->makeProposal(array($this->makeLine(array('pa_ht' => 100, 'subprice' => 102, 'total_ht' => 102))));

		$guard = new CliChaumeilMinimumMarginWarningGuard($db);
		$this->assertFalse($guard->hasLineBelowMinimumRate($proposal));
	}
}
