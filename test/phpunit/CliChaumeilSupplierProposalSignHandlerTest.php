<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSupplierProposalSignHandler.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSupplierOrderConfig.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration tests for CliChaumeilSupplierProposalSignHandler.
 *
 * Verifies that a critical workflow failure (RESULT_ERROR) causes handle() to return -1,
 * so that cloture()'s transaction is rolled back and the supplier proposal status
 * transition is not committed.
 *
 * Uses a failing workflow stub injected via the handler's optional constructor parameter.
 * The stub simulates a ST-6 propagation failure (ambiguous lines, missing lines, etc.)
 * without needing the full line fixture setup.
 *
 * @backupGlobals disabled
 */
class CliChaumeilSupplierProposalSignHandlerTest extends CommonClassTest
{
	/**
	 * Return the first available thirdparty rowid.
	 *
	 * @return int
	 */
	private function ensureTestThirdparty(): int
	{
		global $db;
		$resql = $db->query('SELECT rowid FROM '.$db->prefix().'societe ORDER BY rowid ASC LIMIT 1');
		$this->assertNotFalse($resql);
		$row = $db->fetch_object($resql);
		$this->assertNotEmpty($row, 'No usable thirdparty for fixture.');
		$db->free($resql);
		return (int) $row->rowid;
	}

	/**
	 * Insert a validated propal and a validated supplier proposal linked to it.
	 *
	 * The supplier proposal has fk_statut=1 (validated) — not yet signed — so handle()
	 * will not skip it on the isSupplierProposalProcessed guard.
	 *
	 * @return array{propal:Propal,sp:SupplierProposal}
	 */
	private function insertPropalWithLinkedSupplierProposal(): array
	{
		global $db;
		$socId = $this->ensureTestThirdparty();

		$sql = 'INSERT INTO '.$db->prefix().'propal (entity, ref, ref_client, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'TEST_SIGN_P_".uniqid()."', '', NOW(), ".$socId.', 1)';
		$this->assertTrue((bool) $db->query($sql), 'Insert propal: '.$db->lasterror());
		$propalId = (int) $db->last_insert_id($db->prefix().'propal');

		$sql = 'INSERT INTO '.$db->prefix().'supplier_proposal (entity, ref, ref_supplier, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'TEST_SIGN_SP_".uniqid()."', '', NOW(), ".$socId.', 1)';
		$this->assertTrue((bool) $db->query($sql), 'Insert supplier_proposal: '.$db->lasterror());
		$spId = (int) $db->last_insert_id($db->prefix().'supplier_proposal');

		$sql = 'INSERT INTO '.$db->prefix().'element_element (fk_source, sourcetype, fk_target, targettype)';
		$sql .= " VALUES (".$propalId.", 'propal', ".$spId.", 'supplier_proposal')";
		$this->assertTrue((bool) $db->query($sql), 'Insert element_element: '.$db->lasterror());

		$propal = new Propal($db);
		$this->assertGreaterThan(0, $propal->fetch($propalId));

		$sp = new SupplierProposal($db);
		$this->assertGreaterThan(0, $sp->fetch($spId));

		return array('propal' => $propal, 'sp' => $sp);
	}

	/**
	 * When the workflow returns RESULT_ERROR (e.g. ST-6 propagation fails on ambiguous lines),
	 * handle() must return -1 so that cloture() rolls back the status transition.
	 *
	 * @return void
	 */
	public function testHandleReturnsMinus1WhenWorkflowReturnsError(): void
	{
		global $db, $user, $langs, $conf;

		$context = $this->insertPropalWithLinkedSupplierProposal();

		$failingWorkflow = new class($db, $conf, $langs) extends CliChaumeilSubcontractorSelectionWorkflow {
			/**
			 * Always return RESULT_ERROR to simulate ST-6 propagation failure.
			 *
			 * @param CommonObject $parent             Parent document.
			 * @param int          $supplierProposalId Supplier proposal id.
			 * @param User         $user               Current user.
			 * @return array<string,mixed>
			 */
			public function execute(CommonObject $parent, int $supplierProposalId, User $user): array
			{
				return array(
					'status'  => CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					'message' => 'Forced test failure: propagation ambiguous',
				);
			}
		};

		$handler = new CliChaumeilSupplierProposalSignHandler($db, $failingWorkflow);
		$result  = $handler->handle($context['sp'], $user, $langs, $conf);

		$this->assertSame(-1, $result, 'handle() must return -1 on RESULT_ERROR so cloture() rolls back the status transition');
	}

	/**
	 * When the workflow returns RESULT_SUCCESS, handle() returns 0 (cloture() commits).
	 *
	 * @return void
	 */
	public function testHandleReturnsZeroOnSuccess(): void
	{
		global $db, $user, $langs, $conf;

		$context = $this->insertPropalWithLinkedSupplierProposal();

		$successWorkflow = new class($db, $conf, $langs) extends CliChaumeilSubcontractorSelectionWorkflow {
			/**
			 * Always return RESULT_SUCCESS.
			 *
			 * @param CommonObject $parent             Parent document.
			 * @param int          $supplierProposalId Supplier proposal id.
			 * @param User         $user               Current user.
			 * @return array<string,mixed>
			 */
			public function execute(CommonObject $parent, int $supplierProposalId, User $user): array
			{
				return array(
					'status'  => CliChaumeilSupplierOrderConfig::RESULT_SUCCESS,
					'message' => '',
				);
			}
		};

		$handler = new CliChaumeilSupplierProposalSignHandler($db, $successWorkflow);
		$result  = $handler->handle($context['sp'], $user, $langs, $conf);

		$this->assertSame(0, $result);
	}

	/**
	 * When the workflow returns RESULT_WARNING (e.g. PDF failed), handle() returns 0
	 * — the status transition is committed, the user gets a warning.
	 *
	 * @return void
	 */
	public function testHandleReturnsZeroOnWarning(): void
	{
		global $db, $user, $langs, $conf;

		$context = $this->insertPropalWithLinkedSupplierProposal();

		$warningWorkflow = new class($db, $conf, $langs) extends CliChaumeilSubcontractorSelectionWorkflow {
			/**
			 * Always return RESULT_WARNING.
			 *
			 * @param CommonObject $parent             Parent document.
			 * @param int          $supplierProposalId Supplier proposal id.
			 * @param User         $user               Current user.
			 * @return array<string,mixed>
			 */
			public function execute(CommonObject $parent, int $supplierProposalId, User $user): array
			{
				return array(
					'status'  => CliChaumeilSupplierOrderConfig::RESULT_WARNING,
					'message' => 'PDF generation failed',
				);
			}
		};

		$handler = new CliChaumeilSupplierProposalSignHandler($db, $warningWorkflow);
		$result  = $handler->handle($context['sp'], $user, $langs, $conf);

		$this->assertSame(0, $result);
	}
}
