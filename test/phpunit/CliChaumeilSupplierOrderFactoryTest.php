<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSupplierOrderFactory.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Unit tests for CliChaumeilSupplierOrderFactory::mustSkipSourceLine().
 *
 * mustSkipSourceLine() is private — tested via ReflectionMethod.
 * Focus: subprice=0 must NOT be skipped (consistent with the ST-6 "0 is valid" contract).
 *
 * @backupGlobals disabled
 */
class CliChaumeilSupplierOrderFactoryTest extends CommonClassTest
{
	/**
	 * Invoke the private mustSkipSourceLine() via Reflection.
	 *
	 * @param object $line Source line stub.
	 * @return bool
	 */
	private function callMustSkipSourceLine(object $line): bool
	{
		global $db;
		$factory    = new CliChaumeilSupplierOrderFactory($db);
		$reflection = new ReflectionMethod($factory, 'mustSkipSourceLine');
		$reflection->setAccessible(true);
		return (bool) $reflection->invoke($factory, $line);
	}

	/**
	 * Build a minimal line stub with the given subprice.
	 *
	 * @param int|float|string|null $subprice Subprice value to set on the stub.
	 * @return object
	 */
	private function makeLine($subprice): object
	{
		$line           = new stdClass();
		$line->subprice = $subprice;
		$line->qty      = 1;
		return $line;
	}

	/**
	 * subprice=0 must not be skipped — zero is a valid buy price in the ST-6 flow.
	 *
	 * @return void
	 */
	public function testZeroSubpriceIsNotSkipped(): void
	{
		$this->assertFalse($this->callMustSkipSourceLine($this->makeLine(0)));
	}

	/**
	 * subprice=0.0 (float) must not be skipped.
	 *
	 * @return void
	 */
	public function testZeroFloatSubpriceIsNotSkipped(): void
	{
		$this->assertFalse($this->callMustSkipSourceLine($this->makeLine(0.0)));
	}

	/**
	 * subprice null must be skipped — no price set at all.
	 *
	 * @return void
	 */
	public function testNullSubpriceIsSkipped(): void
	{
		$this->assertTrue($this->callMustSkipSourceLine($this->makeLine(null)));
	}

	/**
	 * subprice empty string must be skipped — price was not filled in.
	 *
	 * @return void
	 */
	public function testEmptyStringSubpriceIsSkipped(): void
	{
		$this->assertTrue($this->callMustSkipSourceLine($this->makeLine('')));
	}

	/**
	 * subprice absent from the object must be skipped.
	 *
	 * @return void
	 */
	public function testMissingSubpriceIsSkipped(): void
	{
		$line = new stdClass();
		$line->qty = 1;
		$this->assertTrue($this->callMustSkipSourceLine($line));
	}

	/**
	 * A positive subprice must not be skipped.
	 *
	 * @return void
	 */
	public function testPositiveSubpriceIsNotSkipped(): void
	{
		$this->assertFalse($this->callMustSkipSourceLine($this->makeLine(99.5)));
	}
}
