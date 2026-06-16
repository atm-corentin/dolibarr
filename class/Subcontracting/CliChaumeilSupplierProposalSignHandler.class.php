<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once __DIR__ . '/CliChaumeilSubcontractorSelectionWorkflow.class.php';
require_once __DIR__ . '/CliChaumeilSupplierProposalGuard.class.php';
require_once __DIR__ . '/CliChaumeilSupplierOrderConfig.class.php';
require_once __DIR__ . '/../SupplierProposalService.class.php';

/**
 * Handles the PROPOSAL_SUPPLIER_CLOSE_SIGNED trigger for the ST-8 subcontracting workflow.
 *
 * Orchestrates: parent resolution → guard check → workflow execution → user notification.
 *
 * Returns -1 on a critical workflow failure (RESULT_ERROR) so that cloture() rolls back
 * the status transition. Returns 0 in all other cases (success, warning, skip).
 */
class CliChaumeilSupplierProposalSignHandler
{
	/** @var DoliDB */
	private DoliDB $db;

	/**
	 * Optional pre-built workflow used for testing. When null (default), the handler
	 * creates a fresh CliChaumeilSubcontractorSelectionWorkflow on each handle() call.
	 *
	 * @var CliChaumeilSubcontractorSelectionWorkflow|null
	 */
	private ?CliChaumeilSubcontractorSelectionWorkflow $workflow;

	/**
	 * @param DoliDB                                          $db       Database handler.
	 * @param CliChaumeilSubcontractorSelectionWorkflow|null  $workflow Optional pre-built workflow (testing only).
	 */
	public function __construct(DoliDB $db, ?CliChaumeilSubcontractorSelectionWorkflow $workflow = null)
	{
		$this->db       = $db;
		$this->workflow = $workflow;
	}

	/**
	 * Handle the supplier proposal sign event.
	 *
	 * Returns -1 on critical workflow error so that cloture() rolls back the supplier
	 * proposal status transition. cloture() checks call_trigger() < 0 to decide whether
	 * to commit or rollback, so this is the only way to keep the object consistent when
	 * ST-6/ST-8 fails — all writes from the workflow are nested inside cloture()'s
	 * transaction and will be rolled back along with the status update.
	 *
	 * @param CommonObject $object Signed supplier proposal.
	 * @param User         $user   Current user.
	 * @param Translate    $langs  Translation handler.
	 * @param Conf         $conf   Application configuration.
	 * @return int 0 on success/warning/skip, -1 on critical error.
	 */
	public function handle(CommonObject $object, User $user, Translate $langs, Conf $conf): int
	{
		if (!($object instanceof SupplierProposal)) {
			return 0;
		}

		$parent = SupplierProposalService::resolveParentDocument($object);
		if ($parent === null) {
			dol_syslog(__METHOD__.' supplier_proposal #'.((int) $object->id).' has no linked propal/commande — skipping ST-8 workflow', LOG_DEBUG);
			return 0;
		}

		// Reload with fresh data: cloture() may have altered in-memory state.
		$freshProposal = new SupplierProposal($this->db);
		$fetchResult   = $freshProposal->fetch((int) $object->id);
		if ($fetchResult === 0) {
			dol_syslog(__METHOD__.' supplier_proposal #'.((int) $object->id).' not found in database', LOG_WARNING);
			return 0;
		}
		if ($fetchResult < 0) {
			dol_syslog(__METHOD__.' SQL error reloading supplier_proposal #'.((int) $object->id).' — '.$this->db->lasterror(), LOG_ERR);
			return 0;
		}

		$guard = new CliChaumeilSupplierProposalGuard();
		if ($guard->isSupplierProposalProcessed($freshProposal)) {
			dol_syslog(__METHOD__.' supplier_proposal #'.((int) $object->id).' already processed — skipping', LOG_DEBUG);
			return 0;
		}

		$workflow = $this->workflow ?? new CliChaumeilSubcontractorSelectionWorkflow($this->db, $conf, $langs);
		$result   = $workflow->execute($parent, (int) $object->id, $user);

		// [] means execute() was skipped by the re-entrancy guard (button flow in progress) — silent skip.
		if ($result === []) {
			dol_syslog(__METHOD__.' re-entrancy guard returned [] — workflow handled by button flow', LOG_DEBUG);
			return 0;
		}

		$langs->load('clichaumeil@clichaumeil');
		$status = $result['status'] ?? '';
		if ($status === CliChaumeilSupplierOrderConfig::RESULT_SUCCESS) {
			setEventMessages($langs->trans('CliChaumeil_St8WorkflowSuccess'), null, 'mesgs');
			return 0;
		}
		if ($status === CliChaumeilSupplierOrderConfig::RESULT_WARNING) {
			setEventMessages($langs->transnoentitiesnoconv('CliChaumeil_St8WorkflowWarning', (string) ($result['message'] ?? '')), null, 'warnings');
			return 0;
		}

		// RESULT_ERROR: return -1 so cloture() rolls back the status transition.
		// The workflow's nested transaction is inside cloture()'s transaction; the real
		// SQL ROLLBACK only fires when the outermost caller (cloture) issues it.
		dol_syslog(__METHOD__.' ST-8 workflow failed — blocking cloture() commit. supplier_proposal_id='.((int) $object->id).' status='.$status.' message='.($result['message'] ?? ''), LOG_ERR);
		setEventMessages($langs->transnoentitiesnoconv('CliChaumeil_St8WorkflowError', (string) ($result['message'] ?? '')), null, 'errors');
		return -1;
	}
}
