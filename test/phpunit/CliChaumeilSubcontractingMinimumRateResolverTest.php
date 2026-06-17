<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../../../societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../../../product/class/product.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSubcontractingMinimumRateResolver.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Unit tests for CliChaumeilSubcontractingMinimumRateResolver.
 *
 * Verifies the discountrules-aligned override resolution order (thirdparty > product > global)
 * and the markup/margin selling-price formulas.
 *
 * @backupGlobals disabled
 */
class CliChaumeilSubcontractingMinimumRateResolverTest extends CommonClassTest
{
	/**
	 * @var array<string,string> Backup of the discountrules globals altered by tests.
	 */
	private $globalsBackup = array();

	/**
	 * Snapshot the managed globals before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		foreach ($this->managedGlobalKeys() as $key) {
			$this->globalsBackup[$key] = (string) getDolGlobalString($key);
		}
	}

	/**
	 * Restore globals after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;
		foreach ($this->globalsBackup as $key => $value) {
			$conf->global->$key = $value;
		}
		parent::tearDown();
	}

	/**
	 * Discountrules globals managed by these tests.
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
	 * Configure the discountrules globals.
	 *
	 * @param int    $use         Enable flag.
	 * @param int    $type        0 = MarkRate, 1 = MarginRate.
	 * @param string $minimumRate Global minimum rate.
	 * @return void
	 */
	private function configureGlobals(int $use, int $type, string $minimumRate): void
	{
		global $conf;
		$conf->global->DISCOUNTRULES_USE_MARKUP_MARGIN_RATE = $use;
		$conf->global->DISCOUNTRULES_MARKUP_MARGIN_RATE     = $type;
		$conf->global->DISCOUNTRULES_MINIMUM_RATE           = $minimumRate;
	}

	/**
	 * Build a parent Propal with optional thirdparty override.
	 *
	 * @param float|null $thirdpartyOverride Override rate on the thirdparty extrafield, null to skip.
	 * @return Propal
	 */
	private function makeParent(?float $thirdpartyOverride): Propal
	{
		global $db;
		$parent             = new Propal($db);
		$parent->id         = 0;
		$parent->element    = 'propal';
		$parent->status     = 0;
		$parent->socid      = 1;
		$thirdparty         = new Societe($db);
		$thirdparty->id     = 1;
		$thirdparty->array_options = array();
		if ($thirdpartyOverride !== null) {
			$thirdparty->array_options['options_discountrules_min_markup_margin_percent'] = (string) $thirdpartyOverride;
		}
		$parent->thirdparty = $thirdparty;
		return $parent;
	}

	/**
	 * Build a parent line referencing an optional product.
	 *
	 * @param int $productId Product id (0 for a free line).
	 * @return PropaleLigne
	 */
	private function makeLine(int $productId): PropaleLigne
	{
		global $db;
		$line             = new PropaleLigne($db);
		$line->id         = 0;
		$line->fk_product = $productId;
		$line->rang       = 1;
		$line->special_code = 0;
		return $line;
	}

	/**
	 * Persist a temporary product carrying the discountrules override extrafield.
	 *
	 * @param float $overrideRate Override rate to set on the product.
	 * @return int Created product id.
	 */
	private function createProductWithOverride(float $overrideRate): int
	{
		global $db, $user;
		$product             = new Product($db);
		$product->ref        = 'CLITESTST6_'.uniqid();
		$product->label      = 'Test ST-6';
		$product->type       = 0;
		$product->status     = 1;
		$product->status_buy = 1;
		$productId           = $product->create($user);
		$this->assertGreaterThan(0, $productId, 'Test product creation failed.');

		$product->array_options['options_discountrules_min_markup_margin_percent'] = (string) $overrideRate;
		$this->assertGreaterThan(0, $product->insertExtraFields(), 'Test product extrafield write failed.');

		return $productId;
	}

	/**
	 * Resolver returns disabled when the discountrules global is off.
	 *
	 * @return void
	 */
	public function testReturnsDisabledWhenGlobalOff(): void
	{
		global $db;
		$this->configureGlobals(0, 0, '20');
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(null), $this->makeLine(0));

		$this->assertFalse($result['enabled']);
	}

	/**
	 * Resolver returns the global rate when no override is set.
	 *
	 * @return void
	 */
	public function testReturnsGlobalRate(): void
	{
		global $db;
		$this->configureGlobals(1, 0, '20');
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(null), $this->makeLine(0));

		$this->assertTrue($result['enabled']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::TYPE_MARK, $result['type']);
		$this->assertSame(20.0, $result['rate']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::SOURCE_GLOBAL, $result['source']);
	}

	/**
	 * Thirdparty override wins over the global rate.
	 *
	 * @return void
	 */
	public function testThirdpartyOverrideWinsOverGlobal(): void
	{
		global $db;
		$this->configureGlobals(1, 0, '20');
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(35.0), $this->makeLine(0));

		$this->assertSame(35.0, $result['rate']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::SOURCE_THIRDPARTY, $result['source']);
	}

	/**
	 * A line without product never queries the product override.
	 *
	 * @return void
	 */
	public function testFreeLineIgnoresProductOverride(): void
	{
		global $db;
		$this->configureGlobals(1, 0, '20');
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(null), $this->makeLine(0));

		$this->assertSame(20.0, $result['rate']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::SOURCE_GLOBAL, $result['source']);
	}

	/**
	 * Markup formula: subprice = pa_ht / (1 - rate/100).
	 *
	 * @return void
	 */
	public function testMarkupFormula(): void
	{
		global $db;
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->computeSellingPrice(100.0, 20.0, CliChaumeilSubcontractingMinimumRateResolver::TYPE_MARK);

		$this->assertSame(125.0, $result);
	}

	/**
	 * Margin formula: subprice = pa_ht * (1 + rate/100).
	 *
	 * @return void
	 */
	public function testMarginFormula(): void
	{
		global $db;
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->computeSellingPrice(100.0, 20.0, CliChaumeilSubcontractingMinimumRateResolver::TYPE_MARGIN);

		$this->assertSame(120.0, $result);
	}

	/**
	 * computeSellingPrice returns 0 on degenerate markup denominator.
	 *
	 * @return void
	 */
	public function testMarkupGuardAgainstFullMarkup(): void
	{
		global $db;
		$resolver = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->computeSellingPrice(100.0, 100.0, CliChaumeilSubcontractingMinimumRateResolver::TYPE_MARK);

		$this->assertSame(0.0, $result);
	}

	/**
	 * Product override is applied when no thirdparty override exists.
	 *
	 * @return void
	 */
	public function testProductOverrideAppliesWhenNoThirdpartyOverride(): void
	{
		global $db;
		$this->configureGlobals(1, 0, '20');
		$productId = $this->createProductWithOverride(40.0);
		$resolver  = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(null), $this->makeLine($productId));

		$this->assertSame(40.0, $result['rate']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::SOURCE_PRODUCT, $result['source']);
	}

	/**
	 * Thirdparty override is preferred over product override.
	 *
	 * @return void
	 */
	public function testThirdpartyOverrideWinsOverProductOverride(): void
	{
		global $db;
		$this->configureGlobals(1, 0, '20');
		$productId = $this->createProductWithOverride(40.0);
		$resolver  = new CliChaumeilSubcontractingMinimumRateResolver($db);

		$result = $resolver->resolve($this->makeParent(15.0), $this->makeLine($productId));

		$this->assertSame(15.0, $result['rate']);
		$this->assertSame(CliChaumeilSubcontractingMinimumRateResolver::SOURCE_THIRDPARTY, $result['source']);
	}
}
