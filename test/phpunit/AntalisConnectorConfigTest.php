<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/AntalisConnectorConfigTest.php
 * @brief   Unit tests for AntalisConnectorConfig (ACHT-2-ANTALIS).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/AntalisConnectorConfigTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/SupplierPriceSyncConstants.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisConnectorConfig.php';

/**
 * Tests for AntalisConnectorConfig.
 */
class AntalisConnectorConfigTest extends CommonClassTest
{
	/**
	 * Set all mandatory ANTALIS constants to valid values.
	 *
	 * @return void
	 */
	private function setupValidConsts(): void
	{
		global $conf;
		$conf->global->{SupplierPriceSyncConstants::CONST_BASE_URL} = 'https://x/y';
		$conf->global->{SupplierPriceSyncConstants::CONST_HTTP_LOGIN} = 'login';
		$conf->global->{SupplierPriceSyncConstants::CONST_HTTP_PASSWORD} = 'pwd';
		$conf->global->{SupplierPriceSyncConstants::CONST_THIRDPARTY_ID} = '185';
		$conf->global->{SupplierPriceSyncConstants::CONST_CUSTOMER_ID} = '351379';
		$conf->global->{SupplierPriceSyncConstants::CONST_USER_CODE} = 'FR_351379_x';
	}

	/**
	 * Remove the ANTALIS constants set during a test.
	 *
	 * @return void
	 */
	private function clearConsts(): void
	{
		global $conf;
		foreach (array(
			SupplierPriceSyncConstants::CONST_BASE_URL,
			SupplierPriceSyncConstants::CONST_HTTP_LOGIN,
			SupplierPriceSyncConstants::CONST_HTTP_PASSWORD,
			SupplierPriceSyncConstants::CONST_THIRDPARTY_ID,
			SupplierPriceSyncConstants::CONST_CUSTOMER_ID,
			SupplierPriceSyncConstants::CONST_USER_CODE,
			SupplierPriceSyncConstants::CONST_DELIVERY_ADDRESS_ID,
		) as $name) {
			unset($conf->global->$name);
		}
	}

	/**
	 * A complete configuration builds and exposes the expected values.
	 *
	 * @return void
	 */
	public function testValidConfig(): void
	{
		$this->setupValidConsts();

		$config = AntalisConnectorConfig::fromGlobals();

		$this->assertSame('ANTALIS', $config->getCode());
		$this->assertSame(185, $config->getSupplierThirdpartyId());
		$this->assertSame('351379', $config->getCustomerId());
		$this->assertSame('FR_351379_x', $config->getUserCode());
		$this->assertSame('', $config->getDeliveryAddressId());

		$this->clearConsts();
	}

	/**
	 * A missing mandatory constant throws.
	 *
	 * @return void
	 */
	public function testMissingRequiredThrows(): void
	{
		$this->setupValidConsts();
		global $conf;
		unset($conf->global->{SupplierPriceSyncConstants::CONST_CUSTOMER_ID});

		try {
			$this->expectException(RuntimeException::class);
			AntalisConnectorConfig::fromGlobals();
		} finally {
			$this->clearConsts();
		}
	}
}
