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
	 * Known ANTALIS unit codes map to the expected Dolibarr labels (case/spaces tolerated).
	 *
	 * @return void
	 */
	public function testKnownUnits(): void
	{
		$mapper = new AntalisOrderUnitMapper();

		// Labels are aligned (singular) with Chaumeil's existing unit dictionary.
		$this->assertSame('Feuille', $mapper->dolibarrLabel('ZSH'));
		$this->assertSame('Ramette', $mapper->dolibarrLabel('ZRM'));
		$this->assertSame('Un', $mapper->dolibarrLabel('ST'));
		$this->assertSame('Un', $mapper->dolibarrLabel(' st '));
		$this->assertSame('Carton', $mapper->dolibarrLabel('KAR'));
		$this->assertSame('Palette', $mapper->dolibarrLabel('PAL'));
		$this->assertSame('Rouleau', $mapper->dolibarrLabel('ROL'));
		$this->assertSame('Kilo', $mapper->dolibarrLabel('KG'));
		// Commercial price units observed on real data (priceUnit axis).
		$this->assertSame('M2', $mapper->dolibarrLabel('M2'));
		$this->assertSame('Lot', $mapper->dolibarrLabel('ZBX'));
		$this->assertSame('Ramette', $mapper->dolibarrLabel('PAK'));
	}

	/**
	 * Unknown or empty codes return null (never guessed).
	 *
	 * @return void
	 */
	public function testUnknownReturnsNull(): void
	{
		$mapper = new AntalisOrderUnitMapper();

		$this->assertNull($mapper->dolibarrLabel('XYZ'));
		$this->assertNull($mapper->dolibarrLabel('ZZZ'));
		$this->assertNull($mapper->dolibarrLabel(''));
	}
}
