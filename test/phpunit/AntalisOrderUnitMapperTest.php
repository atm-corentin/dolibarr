<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/AntalisOrderUnitMapperTest.php
 * @brief   Unit tests for AntalisOrderUnitMapper (ACHT-2-ANTALIS).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/AntalisOrderUnitMapperTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisOrderUnitMapper.php';

/**
 * Tests for AntalisOrderUnitMapper.
 */
class AntalisOrderUnitMapperTest extends CommonClassTest
{
	/**
	 * Known labels map to the expected ANTALIS codes (accents/case/spaces tolerated).
	 *
	 * @return void
	 */
	public function testKnownUnits(): void
	{
		$mapper = new AntalisOrderUnitMapper();

		$this->assertSame('ZSH', $mapper->map('Feuilles'));
		$this->assertSame('ST', $mapper->map('Pièce'));
		$this->assertSame('ST', $mapper->map(' pieces '));
		$this->assertSame('ZRM', $mapper->map('Ramette'));
		$this->assertSame('KAR', $mapper->map('Cartons'));
		$this->assertSame('PAL', $mapper->map('palette'));
		$this->assertSame('ROL', $mapper->map('Rouleaux'));
		$this->assertSame('ZBL', $mapper->map('Bundle'));
	}

	/**
	 * Unknown or empty labels return null (never guessed).
	 *
	 * @return void
	 */
	public function testUnknownReturnsNull(): void
	{
		$mapper = new AntalisOrderUnitMapper();

		$this->assertNull($mapper->map('Lot'));
		$this->assertNull($mapper->map('M2'));
		$this->assertNull($mapper->map(''));
	}
}
