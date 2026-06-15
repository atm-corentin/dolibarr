<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../class/chaumeilrfa.class.php';
require_once dirname(__FILE__).'/../../class/Rfa/RfaSummarySourceRepository.php';
require_once dirname(__FILE__).'/../../class/Rfa/RfaClientSummaryBuilder.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration tests for RfaClientSummaryBuilder.
 * Uses real DB wrapped in begin/rollback.
 *
 * @backupGlobals disabled
 */
class RfaClientSummaryBuilderTest extends CommonClassTest
{
	/** @var int */
	private int $testYear = 2025;

	/** @var array<int,int> */
	private array $createdSocIds = array();

	/** @var array<int,int> */
	private array $createdInvoiceIds = array();

	/** @var array<int,int> */
	private array $createdRfaIds = array();

	/**
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();
		global $db;
		$db->begin();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void
	{
		global $db;
		$db->rollback();
		parent::tearDown();
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * @param string $nom         Company name prefix.
	 * @param int    $client      Client flag value.
	 * @param int    $fournisseur Supplier flag value.
	 * @return int
	 */
	private function createClientSociete(string $nom, int $client = 1, int $fournisseur = 0): int
	{
		global $db, $conf;
		$sql = 'INSERT INTO '.$db->prefix().'societe (nom, entity, client, fournisseur, status, datec, tms)';
		$sql .= " VALUES ('".$db->escape($nom.'-'.uniqid())."', ".(int) $conf->entity.", ".(int) $client.", ".(int) $fournisseur.", 1, NOW(), NOW())";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Cannot create test societe: '.$db->lasterror());
		$id = $db->last_insert_id($db->prefix().'societe');
		$this->createdSocIds[] = $id;
		return $id;
	}

	/**
	 * @param int   $socId   Thirdparty id.
	 * @param float $totalHt Invoice total excluding tax.
	 * @param int   $year    Invoice year.
	 * @return int
	 */
	private function createPaidClientInvoice(int $socId, float $totalHt, int $year): int
	{
		global $db, $user, $conf;
		$date = dol_mktime(12, 0, 0, 6, 1, $year);
		$sql = 'INSERT INTO '.$db->prefix().'facture (ref, entity, fk_soc, datef, total_ht, fk_statut, type, fk_user_author, datec, tms)';
		$sql .= " VALUES ('TEST-CL-".uniqid()."', ".(int) $conf->entity.", ".(int) $socId.", '".$db->idate($date)."', ".(float) $totalHt.", 2, 0, ".(int) $user->id.", NOW(), NOW())";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Cannot create test invoice: '.$db->lasterror());
		$id = $db->last_insert_id($db->prefix().'facture');
		$this->createdInvoiceIds[] = $id;
		return $id;
	}

	/**
	 * @param int   $socId   Thirdparty id.
	 * @param float $palier  Threshold amount.
	 * @param float $ratePct Rate percentage.
	 * @param int   $year    RFA year.
	 * @return int
	 */
	private function createClientRfa(int $socId, float $palier, float $ratePct, int $year): int
	{
		global $db, $user, $conf;
		$datestart = $db->idate(dol_mktime(0, 0, 0, 1, 1, $year));
		$dateend = $db->idate(dol_mktime(23, 59, 59, 12, 31, $year));
		$ref = 'TEST-RFA-'.uniqid();
		$sql = 'INSERT INTO '.$db->prefix().'clichaumeil_chaumeilrfa';
		$sql .= ' (ref, label, fk_soc, datestart, dateend, palier, raterfa, status, rfa_type, date_creation, fk_user_creat)';
		$sql .= " VALUES ('".$db->escape($ref)."', 'Test RFA', ".(int) $socId;
		$sql .= ", '".$datestart."', '".$dateend."', ".(float) $palier.", ".(float) $ratePct;
		$sql .= ', '.ChaumeilRfa::STATUS_DRAFT.', '.ChaumeilRfa::TYPE_CLIENT.", NOW(), ".(int) $user->id.')';
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Cannot create test RFA: '.$db->lasterror());
		$id = $db->last_insert_id($db->prefix().'clichaumeil_chaumeilrfa');
		$this->createdRfaIds[] = $id;
		return $id;
	}

	/**
	 * @param int $year Target year.
	 * @return array<int,array<string,mixed>>
	 */
	private function buildSummary(int $year): array
	{
		global $db;
		$repository = new RfaSummarySourceRepository($db);
		$builder = new RfaClientSummaryBuilder($repository);
		return $builder->buildYearSummary($year);
	}

	// ---------------------------------------------------------------
	// Tests
	// ---------------------------------------------------------------

	/**
	 * @return void
	 */
	public function testBuildYearSummaryReturnsEmptyWhenNoClientRfa(): void
	{
		$rows = $this->buildSummary(9999);
		$this->assertSame([], $rows);
	}

	/**
	 * @return void
	 */
	public function testBuildYearSummaryWithClientAboveThreshold(): void
	{
		$socId = $this->createClientSociete('TestClientRfa_Above');
		$this->createPaidClientInvoice($socId, 15000.0, $this->testYear);
		$this->createClientRfa($socId, 10000.0, 3.0, $this->testYear);

		$rows = $this->buildSummary($this->testYear);

		$row = null;
		foreach ($rows as $r) {
			if ((int) $r['fk_soc'] === $socId) {
				$row = $r;
				break;
			}
		}
		$this->assertNotNull($row, 'Expected a summary row for the test client thirdparty');
		$this->assertEquals(ChaumeilRfa::TYPE_CLIENT, $row['rfa_type']);
		$this->assertEqualsWithDelta(15000.0, $row['ca_achats'], 0.01);
		$this->assertEqualsWithDelta(3.0, $row['taux_rfa'], 0.01);
		$this->assertEqualsWithDelta(450.0, $row['discount_amount_rfa'], 0.01);
	}

	/**
	 * @return void
	 */
	public function testBuildYearSummaryBelowThresholdProducesNoRow(): void
	{
		$socId = $this->createClientSociete('TestClientRfa_Below');
		$this->createPaidClientInvoice($socId, 5000.0, $this->testYear);
		$this->createClientRfa($socId, 10000.0, 3.0, $this->testYear);

		$rows = $this->buildSummary($this->testYear);

		foreach ($rows as $row) {
			$this->assertNotEquals($socId, (int) $row['fk_soc'], 'Should not have a summary row for client below threshold');
		}
	}

	/**
	 * @return void
	 */
	public function testBuildYearSummaryExcludesSupplierOnlyThirdParty(): void
	{
		$socId = $this->createClientSociete('TestSupplierOnly', 0, 1);

		$this->createPaidClientInvoice($socId, 50000.0, $this->testYear);
		$this->createClientRfa($socId, 1000.0, 2.0, $this->testYear);

		$rows = $this->buildSummary($this->testYear);

		foreach ($rows as $row) {
			$this->assertNotEquals($socId, (int) $row['fk_soc'], 'Supplier-only thirdparty must be excluded from client RFA summary');
		}
	}

	/**
	 * @return void
	 */
	public function testBuildYearSummaryDoesNotIncludeSupplierTypeRfaRows(): void
	{
		global $db, $user, $conf;
		$socId = $this->createClientSociete('TestClientRfa_TypeMix');
		$this->createPaidClientInvoice($socId, 20000.0, $this->testYear);

		$datestart = $db->idate(dol_mktime(0, 0, 0, 1, 1, $this->testYear));
		$dateend = $db->idate(dol_mktime(23, 59, 59, 12, 31, $this->testYear));
		$sql = 'INSERT INTO '.$db->prefix().'clichaumeil_chaumeilrfa';
		$sql .= ' (ref, label, fk_soc, datestart, dateend, palier, raterfa, status, rfa_type, date_creation, fk_user_creat)';
		$sql .= " VALUES ('TEST-FOURN-".uniqid()."', 'Test', ".(int) $socId;
		$sql .= ", '".$datestart."', '".$dateend."', 10000.0, 2.0";
		$sql .= ', '.ChaumeilRfa::STATUS_DRAFT.', '.ChaumeilRfa::TYPE_SUPPLIER.", NOW(), ".(int) $user->id.')';
		$resql = $db->query($sql);
		$rfaId = $db->last_insert_id($db->prefix().'clichaumeil_chaumeilrfa');
		$db->free($resql);
		$this->assertGreaterThan(0, $rfaId);

		$rows = $this->buildSummary($this->testYear);

		foreach ($rows as $row) {
			$this->assertNotEquals($socId, (int) $row['fk_soc'], 'TYPE_SUPPLIER RFA must not appear in client summary');
		}
	}
}
