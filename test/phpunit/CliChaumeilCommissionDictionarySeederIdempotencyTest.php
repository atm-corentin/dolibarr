<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../class/CliChaumeilCommissionConfig.class.php';
require_once dirname(__FILE__).'/../../class/Service/CliChaumeilCommissionDictionarySeeder.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration tests for CliChaumeilCommissionDictionarySeeder idempotency guard.
 *
 * The constants->dictionary migration is a one-shot operation. Once the dictionary
 * already holds rows for the entity (first activation done, possibly with user-customized
 * codes/tags through the editable dictionary), re-activation must NOT re-insert default
 * rows, otherwise it collides with the (entity, role_code, customer_tag) unique key
 * (regression: "Duplicate entry '1-commercial-sous_traitance'").
 *
 * Runs inside the suite transaction opened by CommonClassTest::setUpBeforeClass(),
 * rolled back in tearDownAfterClass(): no test row survives.
 *
 * @backupGlobals disabled
 */
class CliChaumeilCommissionDictionarySeederIdempotencyTest extends CommonClassTest
{
	/**
	 * Sentinel entity id with no existing dictionary rows, to avoid touching real data.
	 */
	private const TEST_ENTITY = 909090;

	/**
	 * Count dictionary rows for the sentinel entity.
	 *
	 * @return int
	 */
	private function countRows(): int
	{
		global $db;

		$sql = 'SELECT COUNT(*) as nb FROM '.$db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE;
		$sql .= ' WHERE entity = '.((int) self::TEST_ENTITY);
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Dictionary table must be queryable (module installed in test DB).');
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		return (int) $obj->nb;
	}

	/**
	 * Re-activation over an already populated dictionary must skip seeding, leaving the
	 * existing (possibly user-customized) row untouched and never colliding on the unique key.
	 *
	 * @return void
	 */
	public function testMigrateSkipsWhenDictionaryAlreadyPopulated(): void
	{
		global $db;

		// Simulate a dictionary already migrated, with a user-customized short code and the
		// legacy 'marche' tag, mirroring the production data that triggered the bug.
		$sql = 'INSERT INTO '.$db->prefix().CliChaumeilCommissionConfig::DICTIONARY_TABLE;
		$sql .= ' (entity, code, role_code, customer_tag, label, coefficient, active) VALUES (';
		$sql .= ((int) self::TEST_ENTITY).", 'C_ST', 'commercial', 'sous_traitance', 'Sous-traitance', '1.5', 1)";
		$this->assertNotFalse($db->query($sql), 'Seed row insertion must succeed.');

		$this->assertSame(1, $this->countRows());

		$seeder = new CliChaumeilCommissionDictionarySeeder($db, self::TEST_ENTITY);
		$result = $seeder->migrate();

		$this->assertSame(1, $result, 'Migration must be a successful no-op over a populated dictionary.');
		$this->assertSame('', $seeder->getError());
		$this->assertSame(1, $this->countRows(), 'No default row must be inserted on re-activation.');

		// A second re-activation must remain stable.
		$this->assertSame(1, $seeder->migrate());
		$this->assertSame(1, $this->countRows());
	}
}
