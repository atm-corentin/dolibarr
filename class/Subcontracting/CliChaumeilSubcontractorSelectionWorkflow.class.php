<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once __DIR__.'/../SupplierProposalService.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderConfig.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderFactory.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderRecipientResolver.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderMailService.class.php';
require_once __DIR__.'/CliChaumeilSupplierProposalGuard.class.php';

/**
 * Orchestrate the ST-6/ST-8 subcontractor selection workflow.
 */
class CliChaumeilSubcontractorSelectionWorkflow
{
	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Application configuration.
	 *
	 * @var Conf
	 */
	private Conf $conf;

	/**
	 * Translation handler.
	 *
	 * @var Translate
	 */
	private Translate $langs;

	/**
	 * Supplier order factory.
	 *
	 * @var CliChaumeilSupplierOrderFactory
	 */
	private CliChaumeilSupplierOrderFactory $supplierOrderFactory;

	/**
	 * Recipient resolver.
	 *
	 * @var CliChaumeilSupplierOrderRecipientResolver
	 */
	private CliChaumeilSupplierOrderRecipientResolver $recipientResolver;

	/**
	 * Mail service.
	 *
	 * @var CliChaumeilSupplierOrderMailService
	 */
	private CliChaumeilSupplierOrderMailService $mailService;

	/**
	 * Supplier proposal guard.
	 *
	 * @var CliChaumeilSupplierProposalGuard
	 */
	private CliChaumeilSupplierProposalGuard $supplierProposalGuard;

	/**
	 * Re-entrancy guard: prevents double execution when the PROPOSAL_SUPPLIER_CLOSE_SIGNED trigger
	 * fires synchronously inside markSelectedProposalAsSigned() → cloture() during a button-flow call.
	 * The second execute() call must return immediately without doing any work.
	 *
	 * @var bool
	 */
	private static bool $isRunning = false;

	/**
	 * Constructor.
	 *
	 * @param DoliDB                                       $db                    Database handler.
	 * @param Conf                                         $conf                  Application configuration.
	 * @param Translate                                    $langs                 Translation handler.
	 * @param CliChaumeilSupplierOrderFactory|null         $supplierOrderFactory  Optional supplier order factory.
	 * @param CliChaumeilSupplierOrderRecipientResolver|null $recipientResolver Optional recipient resolver.
	 * @param CliChaumeilSupplierOrderMailService|null     $mailService           Optional mail service.
	 * @param CliChaumeilSupplierProposalGuard|null        $supplierProposalGuard Optional supplier proposal guard.
	 */
	public function __construct(
		DoliDB $db,
		Conf $conf,
		Translate $langs,
		?CliChaumeilSupplierOrderFactory $supplierOrderFactory = null,
		?CliChaumeilSupplierOrderRecipientResolver $recipientResolver = null,
		?CliChaumeilSupplierOrderMailService $mailService = null,
		?CliChaumeilSupplierProposalGuard $supplierProposalGuard = null
	) {
		$this->db = $db;
		$this->conf = $conf;
		$this->langs = $langs;
		$this->supplierOrderFactory = $supplierOrderFactory ?: new CliChaumeilSupplierOrderFactory($db);
		$this->recipientResolver = $recipientResolver ?: new CliChaumeilSupplierOrderRecipientResolver();
		$this->mailService = $mailService ?: new CliChaumeilSupplierOrderMailService($db, $conf, $langs);
		$this->supplierProposalGuard = $supplierProposalGuard ?: new CliChaumeilSupplierProposalGuard();
	}

	/**
	 * Execute the subcontractor selection workflow.
	 *
	 * @param CommonObject $parent             Parent commercial document.
	 * @param int          $supplierProposalId Selected supplier proposal id.
	 * @param User         $user               Current user.
	 * @return array<string,mixed>
	 */
	public function execute(CommonObject $parent, int $supplierProposalId, User $user): array
	{
		if (self::$isRunning) {
			dol_syslog(__METHOD__.' re-entrancy guard triggered — skipping nested call for supplier_proposal #'.$supplierProposalId, LOG_DEBUG);
			return [];
		}

		self::$isRunning = true;

		try {
			return $this->executeInternal($parent, $supplierProposalId, $user);
		} finally {
			self::$isRunning = false;
		}
	}

	/**
	 * Internal execution — called exclusively by execute() after the re-entrancy guard.
	 *
	 * @param CommonObject $parent             Parent commercial document.
	 * @param int          $supplierProposalId Selected supplier proposal id.
	 * @param User         $user               Current user.
	 * @return array<string,mixed>
	 */
	private function executeInternal(CommonObject $parent, int $supplierProposalId, User $user): array
	{
		$debug = array(
			'parent_type' => (string) $parent->element,
			'parent_id' => (int) $parent->id,
			'supplier_proposal_id' => $supplierProposalId,
		);
		$transactionOpened = false;
		$orderPersisted = false;

		try {
			if (!$this->hasSupplierProposalSelectionRight($user)) {
				dol_syslog(__METHOD__.' supplier proposal selection forbidden for user #'.((int) $user->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8SupplierProposalPermissionsMissing'),
					false,
					false,
					$debug
				);
			}
			if (!$this->hasParentAccess($parent, $user)) {
				dol_syslog(__METHOD__.' parent access forbidden for user #'.((int) $user->id).' on '.$parent->element.' #'.((int) $parent->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8ParentAccessForbidden'),
					false,
					false,
					$debug
				);
			}
			if (!$this->hasSupplierOrderCreationRight($user) || !$this->hasSupplierOrderValidationRight($user)) {
				dol_syslog(__METHOD__.' supplier order creation/validation forbidden for user #'.((int) $user->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8SupplierOrderPermissionsMissing'),
					false,
					false,
					$debug
				);
			}

			$allLinkedSupplierProposals = SupplierProposalService::loadLinkedSupplierProposals($parent, $this->db);
			$allLinkedSupplierProposals = SupplierProposalService::preloadThirdparties($allLinkedSupplierProposals, $this->db);
			if (empty($allLinkedSupplierProposals)) {
				dol_syslog(__METHOD__.' no linked supplier proposal found for parent '.$parent->element.' #'.((int) $parent->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeilNoSupplierProposal'),
					false,
					false,
					$debug
				);
			}

			$allLinkedIds = $this->extractLinkedProposalIds($allLinkedSupplierProposals);
			$visibleLinkedSupplierProposals = $this->supplierProposalGuard->filterAccessibleSupplierProposals($allLinkedSupplierProposals, $user);
			$visibleLinkedIds = $this->extractLinkedProposalIds($visibleLinkedSupplierProposals);
			$debug['linked_supplier_proposal_ids'] = $allLinkedIds;
			$debug['visible_supplier_proposal_ids'] = $visibleLinkedIds;

			if ($this->supplierProposalGuard->hasProcessedSupplierProposal($allLinkedSupplierProposals)) {
				dol_syslog(__METHOD__.' subcontractor selection already processed for parent '.$parent->element.' #'.((int) $parent->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8AlreadyProcessed'),
					false,
					false,
					$debug
				);
			}

			if (empty($visibleLinkedSupplierProposals)) {
				dol_syslog(__METHOD__.' no accessible supplier proposal found for user #'.((int) $user->id).' on parent '.$parent->element.' #'.((int) $parent->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8NoAccessibleSupplierProposal'),
					false,
					false,
					$debug
				);
			}

			if (!in_array($supplierProposalId, $allLinkedIds, true)) {
				dol_syslog(__METHOD__.' selected supplier proposal #'.$supplierProposalId.' is not linked to parent '.$parent->element.' #'.((int) $parent->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeilProposalNotLinked'),
					false,
					false,
					$debug
				);
			}

			if (!in_array($supplierProposalId, $visibleLinkedIds, true)) {
				dol_syslog(__METHOD__.' selected supplier proposal #'.$supplierProposalId.' is not accessible to user #'.((int) $user->id), LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$this->langs->transnoentitiesnoconv('CliChaumeil_St8SelectedProposalAccessForbidden'),
					false,
					false,
					$debug
				);
			}

			$selectedSupplierProposal = $this->loadSupplierProposal($supplierProposalId);
			$selectedProposalProcessingError = $this->getSelectedSupplierProposalProcessingError($selectedSupplierProposal);
			if ($selectedProposalProcessingError !== '') {
				dol_syslog(__METHOD__.' selected supplier proposal #'.((int) $selectedSupplierProposal->id).' cannot be processed: '.$selectedProposalProcessingError, LOG_WARNING);
				return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
					CliChaumeilSupplierOrderConfig::RESULT_ERROR,
					$selectedProposalProcessingError,
					false,
					false,
					$debug
				);
			}

			$this->db->begin();
			$transactionOpened = true;

			$this->markSelectedProposalAsSigned($selectedSupplierProposal, $user);
			$supplierOrder = $this->supplierOrderFactory->createValidatedOrderFromProposal($selectedSupplierProposal, $user);
			$this->markOtherProposalsAsRefused($allLinkedIds, (int) $selectedSupplierProposal->id, $user);
			$this->markSelectedProposalAsProcessed($selectedSupplierProposal, $user);

			$this->db->commit();
			$transactionOpened = false;
			$orderPersisted = true;

			$debug['supplier_order_id'] = (int) $supplierOrder->id;
			$pdfResult = $this->generateSupplierOrderPdf($supplierOrder);
			if ($pdfResult['generated'] === false) {
				$logMessage = $pdfResult['log_message'] !== '' ? $pdfResult['log_message'] : $pdfResult['message'];
				dol_syslog(__METHOD__.' PDF generation warning for supplier order #'.((int) $supplierOrder->id).' reason='.$logMessage, LOG_WARNING);
				return $this->buildWarningResponse($pdfResult['message'], $debug);
			}

			$recipientResolution = $this->recipientResolver->resolve($supplierOrder);
			$debug['recipient_source'] = $recipientResolution['source_used'];
			$debug['recipient_count'] = count($recipientResolution['emails']);
			if (empty($recipientResolution['emails'])) {
				dol_syslog(__METHOD__.' no supplier email recipient found for supplier order #'.((int) $supplierOrder->id), LOG_WARNING);
				return $this->buildWarningResponse($this->langs->transnoentitiesnoconv('CliChaumeil_St8NoEmailRecipient'), $debug);
			}

			$mailResult = $this->mailService->send($supplierOrder, $recipientResolution, $user);
			if ($mailResult['sent'] === false) {
				$logMessage = $mailResult['log_message'] !== '' ? $mailResult['log_message'] : $mailResult['warning_message'];
				dol_syslog(__METHOD__.' supplier order email warning for supplier order #'.((int) $supplierOrder->id).' reason='.$logMessage, LOG_WARNING);
				return $this->buildWarningResponse((string) $mailResult['warning_message'], $debug);
			}
			if ($mailResult['warning_message'] !== '') {
				dol_syslog(__METHOD__.' supplier order email post-send warning for supplier order #'.((int) $supplierOrder->id).' reason='.$mailResult['warning_message'], LOG_WARNING);
				return $this->buildWarningResponse((string) $mailResult['warning_message'], $debug);
			}

			return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
				CliChaumeilSupplierOrderConfig::RESULT_SUCCESS,
				$this->langs->transnoentitiesnoconv('CliChaumeil_St8WorkflowSuccess'),
				true,
				true,
				$debug
			);
		} catch (Throwable $exception) {
			if ($transactionOpened) {
				$this->db->rollback();
			}

			dol_syslog(__METHOD__.' failed: '.$exception->getMessage(), LOG_ERR);

			if ($orderPersisted) {
				return $this->buildWarningResponse($this->langs->transnoentitiesnoconv('CliChaumeil_St8UnexpectedError'), $debug);
			}

			return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
				CliChaumeilSupplierOrderConfig::RESULT_ERROR,
				$this->langs->transnoentitiesnoconv('CliChaumeil_St8UnexpectedError'),
				false,
				false,
				$debug
			);
		}
	}

	/**
	 * Tell whether the user can select one supplier proposal.
	 *
	 * @param User $user Current user.
	 * @return bool
	 */
	private function hasSupplierProposalSelectionRight(User $user): bool
	{
		return $user->hasRight('supplier_proposal', 'creer') || $user->hasRight('supplier_proposal', 'cloturer');
	}

	/**
	 * Tell whether the user can create supplier orders.
	 *
	 * @param User $user Current user.
	 * @return bool
	 */
	private function hasSupplierOrderCreationRight(User $user): bool
	{
		return $user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer');
	}

	/**
	 * Tell whether the user can validate supplier orders.
	 *
	 * @param User $user Current user.
	 * @return bool
	 */
	private function hasSupplierOrderValidationRight(User $user): bool
	{
		if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
			return $this->hasSupplierOrderCreationRight($user);
		}

		return (bool) $user->hasRight('fournisseur', 'supplier_order_advance', 'validate');
	}

	/**
	 * Tell whether the current user can access the parent commercial document.
	 *
	 * @param CommonObject $parent Parent commercial document.
	 * @param User         $user   Current user.
	 * @return bool
	 */
	private function hasParentAccess(CommonObject $parent, User $user): bool
	{
		if (empty($parent->id)) {
			return false;
		}
		if ($parent->element === 'propal') {
			return (bool) restrictedArea($user, 'propal', (int) $parent->id, '', '', 'fk_soc', 'rowid', 0, 1);
		}
		if ($parent->element === 'commande') {
			return (bool) restrictedArea($user, 'commande', (int) $parent->id, '', '', 'fk_soc', 'rowid', 0, 1);
		}

		return false;
	}

	/**
	 * Extract the linked supplier proposal ids from the loader payload.
	 *
	 * @param array<int,SupplierProposal> $linkedSupplierProposals Linked supplier proposals.
	 * @return array<int,int>
	 */
	private function extractLinkedProposalIds(array $linkedSupplierProposals): array
	{
		$linkedIds = array();
		foreach ($linkedSupplierProposals as $linkedSupplierProposal) {
			if (!empty($linkedSupplierProposal->id)) {
				$linkedIds[] = (int) $linkedSupplierProposal->id;
			}
		}

		return $linkedIds;
	}

	/**
	 * Load one supplier proposal with its thirdparty and its extrafields.
	 *
	 * @param int $supplierProposalId Supplier proposal id.
	 * @return SupplierProposal
	 */
	private function loadSupplierProposal(int $supplierProposalId): SupplierProposal
	{
		$supplierProposal = new SupplierProposal($this->db);
		$result = $supplierProposal->fetch($supplierProposalId);
		if ($result <= 0) {
			throw new RuntimeException($this->langs->transnoentitiesnoconv('ErrorRecordNotFound'));
		}

		$supplierProposal->fetch_optionals();
		$supplierProposal->fetch_thirdparty();

		return $supplierProposal;
	}

	/**
	 * Return the processability error for one selected supplier proposal.
	 *
	 * @param SupplierProposal $supplierProposal Selected supplier proposal.
	 * @return string
	 */
	private function getSelectedSupplierProposalProcessingError(SupplierProposal $supplierProposal): string
	{
		if ($this->supplierProposalGuard->isSupplierProposalProcessed($supplierProposal)) {
			return $this->langs->transnoentitiesnoconv('CliChaumeil_St8AlreadyProcessed');
		}

		$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
		if (!in_array($currentStatus, array(SupplierProposal::STATUS_VALIDATED, SupplierProposal::STATUS_SIGNED), true)) {
			return $this->langs->transnoentitiesnoconv('CliChaumeil_St8SelectedProposalStatusInvalid');
		}

		return '';
	}

	/**
	 * Mark the selected supplier proposal as signed if needed.
	 *
	 * @param SupplierProposal $supplierProposal Selected supplier proposal.
	 * @param User             $user             Current user.
	 * @return void
	 */
	private function markSelectedProposalAsSigned(SupplierProposal $supplierProposal, User $user): void
	{
		$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
		if ($currentStatus === SupplierProposal::STATUS_SIGNED || $currentStatus === SupplierProposal::STATUS_CLOSE) {
			return;
		}

		$result = $supplierProposal->cloture($user, SupplierProposal::STATUS_SIGNED, '');
		if ($result <= 0) {
			throw new RuntimeException($this->buildObjectErrorMessage($supplierProposal, $this->langs->transnoentitiesnoconv('CliChaumeil_St8ProposalStatusUpdateFailed')));
		}
	}

	/**
	 * Mark all non-selected linked supplier proposals as refused.
	 *
	 * @param array<int,int> $linkedIds              Linked supplier proposal ids.
	 * @param int            $selectedProposalId     Selected supplier proposal id.
	 * @param User           $user                   Current user.
	 * @return void
	 */
	private function markOtherProposalsAsRefused(array $linkedIds, int $selectedProposalId, User $user): void
	{
		foreach ($linkedIds as $linkedId) {
			if ($linkedId === $selectedProposalId) {
				continue;
			}

			$linkedSupplierProposal = $this->loadSupplierProposal($linkedId);
			$currentStatus = isset($linkedSupplierProposal->status) ? (int) $linkedSupplierProposal->status : (int) $linkedSupplierProposal->statut;
			if ($currentStatus === SupplierProposal::STATUS_NOTSIGNED) {
				continue;
			}

			$result = $linkedSupplierProposal->cloture($user, SupplierProposal::STATUS_NOTSIGNED, '');
			if ($result <= 0) {
				throw new RuntimeException($this->buildObjectErrorMessage($linkedSupplierProposal, $this->langs->transnoentitiesnoconv('CliChaumeil_St8ProposalStatusUpdateFailed')));
			}
		}
	}

	/**
	 * Mark the selected supplier proposal as processed.
	 *
	 * @param SupplierProposal $supplierProposal Selected supplier proposal.
	 * @param User             $user             Current user.
	 * @return void
	 */
	private function markSelectedProposalAsProcessed(SupplierProposal $supplierProposal, User $user): void
	{
		$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
		if ($currentStatus === SupplierProposal::STATUS_CLOSE) {
			return;
		}

		$result = $supplierProposal->cloture($user, SupplierProposal::STATUS_CLOSE, '');
		if ($result <= 0) {
			throw new RuntimeException($this->buildObjectErrorMessage($supplierProposal, $this->langs->transnoentitiesnoconv('CliChaumeil_St8ProposalStatusUpdateFailed')));
		}
	}

	/**
	 * Generate the supplier order PDF.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @return array{generated:bool,message:string,log_message:string}
	 */
	private function generateSupplierOrderPdf(CommandeFournisseur $supplierOrder): array
	{
		$outputlangs = CliChaumeilSupplierOrderConfig::createOutputLangs(
			$this->conf,
			$this->langs,
			(string) ($supplierOrder->thirdparty->default_lang ?? '')
		);

		$pdfModel = trim((string) ($supplierOrder->model_pdf ?? ''));
		if ($pdfModel === '') {
			$pdfModel = getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF');
		}

		$result = $supplierOrder->generateDocument($pdfModel, $outputlangs, 0, 0, 0);
		$documentPath = DOL_DATA_ROOT.'/'.ltrim((string) $supplierOrder->last_main_doc, '/');
		if ($result <= 0 || empty($supplierOrder->last_main_doc) || !dol_is_file($documentPath)) {
			$message = $this->langs->transnoentitiesnoconv('CliChaumeil_St8PdfGenerationFailed');
			$rawError = $this->buildObjectErrorMessage($supplierOrder, '');

			return array(
				'generated' => false,
				'message' => trim($message),
				'log_message' => $rawError,
			);
		}

		return array(
			'generated' => true,
			'message' => '',
			'log_message' => '',
		);
	}

	/**
	 * Build one warning AJAX response.
	 *
	 * @param string              $reason Warning reason.
	 * @param array<string,mixed> $debug  Debug payload.
	 * @return array<string,mixed>
	 */
	private function buildWarningResponse(string $reason, array $debug): array
	{
		return CliChaumeilSupplierOrderConfig::buildAjaxResponse(
			CliChaumeilSupplierOrderConfig::RESULT_WARNING,
			$this->langs->transnoentitiesnoconv('CliChaumeil_St8WorkflowWarning', $reason),
			true,
			true,
			$debug
		);
	}

	/**
	 * Build a normalized Dolibarr business-object error message.
	 *
	 * @param CommonObject $object        Business object.
	 * @param string       $fallbackError Fallback error message.
	 * @return string
	 */
	private function buildObjectErrorMessage(CommonObject $object, string $fallbackError): string
	{
		if (!empty($object->error)) {
			return (string) $object->error;
		}
		if (!empty($object->errors) && is_array($object->errors)) {
			return implode(' | ', $object->errors);
		}
		if ($this->db->lasterror() !== '') {
			return $this->db->lasterror();
		}
		return $fallbackError !== '' ? $fallbackError : $this->langs->transnoentitiesnoconv('CliChaumeil_St8UnexpectedError');
	}
}
