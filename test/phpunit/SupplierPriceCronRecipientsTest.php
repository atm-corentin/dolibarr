<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/SupplierPriceCronRecipientsTest.php
 * @brief   Unit tests for CronRecipients parsing/validation (ACHT-2-ANTALIS).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/SupplierPriceCronRecipientsTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/CronRecipients.php';

/**
 * Tests for CronRecipients.
 */
class SupplierPriceCronRecipientsTest extends CommonClassTest
{
	/**
	 * An empty raw parameter yields an empty, valid list.
	 *
	 * @return void
	 */
	public function testEmptyIsAllowed(): void
	{
		$recipients = CronRecipients::fromRaw('');

		$this->assertSame(array(), $recipients->all());
		$this->assertTrue($recipients->isEmpty());
	}

	/**
	 * Addresses are split, trimmed and de-duplicated while keeping order.
	 *
	 * @return void
	 */
	public function testSplitTrimDedupe(): void
	{
		$recipients = CronRecipients::fromRaw(' a@test.tld ; b@test.tld ;a@test.tld');

		$this->assertSame(array('a@test.tld', 'b@test.tld'), $recipients->all());
		$this->assertFalse($recipients->isEmpty());
	}

	/**
	 * A single invalid address makes the whole list invalid.
	 *
	 * @return void
	 */
	public function testInvalidThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);

		CronRecipients::fromRaw('a@test.tld;not-an-email');
	}
}
