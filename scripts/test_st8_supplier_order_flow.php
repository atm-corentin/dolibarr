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

if (PHP_SAPI !== 'cli') {
	print "This script must be executed from CLI.\n";
	exit(1);
}

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

/**
 * Print a minimal usage block before Dolibarr bootstrap.
 *
 * @return void
 */
function printBootstrapUsage(): void
{
	print "Usage:\n";
	print "  php custom/clichaumeil/scripts/test_st8_supplier_order_flow.php --parent-type=propal|commande --parent-id=ID --supplier-proposal-id=ID --user-id=ID --confirm=1 [--case=success|mail_failure|agenda_failure|no_recipient|no_validate_right] [--mail-mode=simulate_success|simulate_failure|simulate_agenda_failure|real] [--dry-run=1] [--show-debug=1]\n";
}

if (in_array('--help', $argv, true) || in_array('--h', $argv, true)) {
	printBootstrapUsage();
	exit(0);
}
if ($argc === 1) {
	printBootstrapUsage();
	exit(1);
}

require __DIR__.'/../../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once __DIR__.'/../class/SupplierProposalService.class.php';
require_once __DIR__.'/../class/Subcontracting/CliChaumeilSupplierOrderConfig.class.php';
require_once __DIR__.'/../class/Subcontracting/CliChaumeilSubcontractorSelectionWorkflow.class.php';
require_once __DIR__.'/../class/Subcontracting/CliChaumeilSupplierOrderMailService.class.php';

/**
 * Test mail service allowing precise simulation of transport/agenda failures
 * while still executing the standard template and substitution pipeline.
 */
class CliChaumeilSt8TestMailService extends CliChaumeilSupplierOrderMailService
{
	/**
	 * Mail execution mode.
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * Whether the public send() method was reached.
	 *
	 * @var bool
	 */
	private bool $sendCalled = false;

	/**
	 * Whether the underlying mail transport was reached.
	 *
	 * @var bool
	 */
	private bool $transportCalled = false;

	/**
	 * Whether the agenda trigger step was reached.
	 *
	 * @var bool
	 */
	private bool $triggerCalled = false;

	/**
	 * Constructor.
	 *
	 * @param DoliDB    $db    Database handler.
	 * @param Conf      $conf  Application configuration.
	 * @param Translate $langs Translation handler.
	 * @param string    $mode  Mail execution mode.
	 */
	public function __construct(DoliDB $db, Conf $conf, Translate $langs, string $mode)
	{
		parent::__construct($db, $conf, $langs);
		$this->mode = $mode;
	}

	/**
	 * Execute the full mail service and record whether it was reached.
	 *
	 * @param CommandeFournisseur       $supplierOrder        Supplier order.
	 * @param array{emails:array<int,string>,contact_ids:array<int,int>,source_used:string} $recipientResolution Resolved recipients.
	 * @param User                      $user                 Current user.
	 * @return array{sent:bool,warning_message:string,subject:string,to:string,log_message:string}
	 */
	public function send(CommandeFournisseur $supplierOrder, array $recipientResolution, User $user): array
	{
		$this->sendCalled = true;

		return parent::send($supplierOrder, $recipientResolution, $user);
	}

	/**
	 * Simulate or execute the real mail transport.
	 *
	 * @param CMailFile $mailFile Mail sender.
	 * @return bool
	 */
	protected function sendMailFile(CMailFile $mailFile): bool
	{
		$this->transportCalled = true;

		if ($this->mode === 'simulate_mail_failure') {
			$mailFile->error = 'Simulated mail transport failure';

			return false;
		}
		if ($this->mode === 'simulate_success' || $this->mode === 'simulate_agenda_failure') {
			return true;
		}

		return parent::sendMailFile($mailFile);
	}

	/**
	 * Simulate or execute the real agenda trigger.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @param User                $user          Current user.
	 * @return int
	 */
	protected function triggerSupplierOrderSentEvent(CommandeFournisseur $supplierOrder, User $user): int
	{
		$this->triggerCalled = true;

		if ($this->mode === 'simulate_agenda_failure') {
			$supplierOrder->error = 'Simulated agenda trigger failure';

			return -1;
		}

		return parent::triggerSupplierOrderSentEvent($supplierOrder, $user);
	}

	/**
	 * Tell whether the public send() method was reached.
	 *
	 * @return bool
	 */
	public function wasSendCalled(): bool
	{
		return $this->sendCalled;
	}

	/**
	 * Tell whether the underlying mail transport was reached.
	 *
	 * @return bool
	 */
	public function wasTransportCalled(): bool
	{
		return $this->transportCalled;
	}

	/**
	 * Tell whether the agenda trigger step was reached.
	 *
	 * @return bool
	 */
	public function wasTriggerCalled(): bool
	{
		return $this->triggerCalled;
	}
}

/**
 * Read one CLI option.
 *
 * @param array<int,string> $argv    CLI arguments.
 * @param string            $name    Option name.
 * @param string            $default Default value.
 * @return string
 */
function getCliOption(array $argv, string $name, string $default = ''): string
{
	foreach ($argv as $argument) {
		if (strpos($argument, '--'.$name.'=') === 0) {
			return (string) substr($argument, strlen($name) + 3);
		}
	}

	return $default;
}

/**
 * Tell whether one CLI flag is present.
 *
 * @param array<int,string> $argv CLI arguments.
 * @param string            $name Flag name without leading dashes.
 * @return bool
 */
function hasCliFlag(array $argv, string $name): bool
{
	return in_array('--'.$name, $argv, true);
}

/**
 * Read one CLI boolean option.
 *
 * @param array<int,string> $argv    CLI arguments.
 * @param string            $name    Option name.
 * @param bool              $default Default value.
 * @return bool
 */
function getCliBoolOption(array $argv, string $name, bool $default = false): bool
{
	$defaultValue = $default ? '1' : '0';
	$value = strtolower(getCliOption($argv, $name, $defaultValue));

	return in_array($value, array('1', 'true', 'yes', 'y'), true);
}

/**
 * Normalize one mail mode, including legacy aliases.
 *
 * @param string $mailMode Raw CLI mail mode.
 * @return string
 */
function normalizeMailMode(string $mailMode): string
{
	$normalizedMode = strtolower(trim($mailMode));
	if ($normalizedMode === 'simulate_failure') {
		return 'simulate_mail_failure';
	}
	if ($normalizedMode === 'simulate_success' || $normalizedMode === '') {
		return 'simulate_success';
	}

	return $normalizedMode;
}

/**
 * Build the execution profile for one named test case.
 *
 * @param string $caseName Requested case name.
 * @param string $mailMode Requested mail mode.
 * @return array{
 *   name:string,
 *   is_case_mode:bool,
 *   mail_mode:string,
 *   expected_status:string,
 *   expected_success:bool,
 *   expected_should_reload:bool,
 *   expect_order:bool,
 *   expect_send_called:bool,
 *   expect_transport_called:bool,
 *   expect_trigger_called:bool,
 *   expect_agenda_event:bool
 * }
 */
function buildExecutionProfile(string $caseName, string $mailMode): array
{
	$normalizedCaseName = strtolower(trim($caseName));
	$normalizedMailMode = normalizeMailMode($mailMode);

	if ($normalizedCaseName === '') {
		return array(
			'name' => 'ad_hoc',
			'is_case_mode' => false,
			'mail_mode' => $normalizedMailMode,
			'expected_status' => '',
			'expected_success' => false,
			'expected_should_reload' => false,
			'expect_order' => false,
			'expect_send_called' => false,
			'expect_transport_called' => false,
			'expect_trigger_called' => false,
			'expect_agenda_event' => false,
		);
	}

	$profiles = array(
		'success' => array(
			'mail_mode' => 'simulate_success',
			'expected_status' => CliChaumeilSupplierOrderConfig::RESULT_SUCCESS,
			'expected_success' => true,
			'expected_should_reload' => true,
			'expect_order' => true,
			'expect_send_called' => true,
			'expect_transport_called' => true,
			'expect_trigger_called' => true,
			'expect_agenda_event' => true,
		),
		'mail_failure' => array(
			'mail_mode' => 'simulate_mail_failure',
			'expected_status' => CliChaumeilSupplierOrderConfig::RESULT_WARNING,
			'expected_success' => true,
			'expected_should_reload' => true,
			'expect_order' => true,
			'expect_send_called' => true,
			'expect_transport_called' => true,
			'expect_trigger_called' => false,
			'expect_agenda_event' => false,
		),
		'agenda_failure' => array(
			'mail_mode' => 'simulate_agenda_failure',
			'expected_status' => CliChaumeilSupplierOrderConfig::RESULT_WARNING,
			'expected_success' => true,
			'expected_should_reload' => true,
			'expect_order' => true,
			'expect_send_called' => true,
			'expect_transport_called' => true,
			'expect_trigger_called' => true,
			'expect_agenda_event' => false,
		),
		'no_recipient' => array(
			'mail_mode' => 'simulate_success',
			'expected_status' => CliChaumeilSupplierOrderConfig::RESULT_WARNING,
			'expected_success' => true,
			'expected_should_reload' => true,
			'expect_order' => true,
			'expect_send_called' => false,
			'expect_transport_called' => false,
			'expect_trigger_called' => false,
			'expect_agenda_event' => false,
		),
		'no_validate_right' => array(
			'mail_mode' => $normalizedMailMode,
			'expected_status' => CliChaumeilSupplierOrderConfig::RESULT_ERROR,
			'expected_success' => false,
			'expected_should_reload' => false,
			'expect_order' => false,
			'expect_send_called' => false,
			'expect_transport_called' => false,
			'expect_trigger_called' => false,
			'expect_agenda_event' => false,
		),
	);

	if (!isset($profiles[$normalizedCaseName])) {
		throw new RuntimeException('Unsupported --case value: '.$caseName);
	}

	return array_merge(
		array(
			'name' => $normalizedCaseName,
			'is_case_mode' => true,
		),
		$profiles[$normalizedCaseName]
	);
}

/**
 * Print one usage block.
 *
 * @return void
 */
function printUsage(): void
{
	print "Usage:\n";
	print "  php custom/clichaumeil/scripts/test_st8_supplier_order_flow.php --parent-type=propal|commande --parent-id=ID --supplier-proposal-id=ID --user-id=ID --confirm=1 [--case=success|mail_failure|agenda_failure|no_recipient|no_validate_right] [--mail-mode=simulate_success|simulate_failure|simulate_agenda_failure|real] [--dry-run=1] [--show-debug=1]\n";
	print "\n";
	print "Notes:\n";
	print "  - The script executes the real ST-8 workflow.\n";
	print "  - Dry-run is enabled by default and rolls back SQL changes only.\n";
	print "  - Generated files, triggers and real emails are not rolled back.\n";
	print "  - Named cases are preferred because they assert post-conditions.\n";
}

/**
 * Load one parent object from CLI options.
 *
 * @param DoliDB $db         Database handler.
 * @param string $parentType Parent element type.
 * @param int    $parentId   Parent id.
 * @return CommonObject
 */
function loadParentObject(DoliDB $db, string $parentType, int $parentId): CommonObject
{
	$parentMap = array(
		'propal' => 'Propal',
		'commande' => 'Commande',
	);

	if (!isset($parentMap[$parentType])) {
		throw new RuntimeException('Unsupported parent type: '.$parentType);
	}

	$parentClass = $parentMap[$parentType];
	$parent = new $parentClass($db);
	$result = $parent->fetch($parentId);
	if ($result <= 0) {
		throw new RuntimeException('Unable to fetch parent object #'.$parentId.' of type '.$parentType.'.');
	}

	return $parent;
}

/**
 * Print one response line.
 *
 * @param string $label   Response label.
 * @param string $message Response message.
 * @return void
 */
function printResultLine(string $label, string $message): void
{
	print '['.$label.'] '.$message."\n";
}

/**
 * Load the current statuses of all supplier proposals linked to the parent.
 *
 * @param CommonObject $parent Parent commercial document.
 * @param DoliDB       $db     Database handler.
 * @return array<int,int>
 */
function loadLinkedSupplierProposalStatuses(CommonObject $parent, DoliDB $db): array
{
	$linkedSupplierProposals = SupplierProposalService::loadLinkedSupplierProposals($parent, $db);
	$statuses = array();

	foreach ($linkedSupplierProposals as $linkedSupplierProposal) {
		if (empty($linkedSupplierProposal->id)) {
			continue;
		}

		$statuses[(int) $linkedSupplierProposal->id] = isset($linkedSupplierProposal->status)
			? (int) $linkedSupplierProposal->status
			: (int) $linkedSupplierProposal->statut;
	}

	return $statuses;
}

/**
 * Load one supplier proposal and return its current status.
 *
 * @param DoliDB $db                 Database handler.
 * @param int    $supplierProposalId Supplier proposal id.
 * @return int
 */
function loadSupplierProposalStatus(DoliDB $db, int $supplierProposalId): int
{
	$supplierProposal = new SupplierProposal($db);
	$result = $supplierProposal->fetch($supplierProposalId);
	if ($result <= 0) {
		throw new RuntimeException('Unable to fetch supplier proposal #'.$supplierProposalId.'.');
	}

	return isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
}

/**
 * Fetch one supplier order with its supplier and lines.
 *
 * @param DoliDB $db              Database handler.
 * @param int    $supplierOrderId Supplier order id.
 * @return CommandeFournisseur
 */
function fetchSupplierOrder(DoliDB $db, int $supplierOrderId): CommandeFournisseur
{
	$supplierOrder = new CommandeFournisseur($db);
	$result = $supplierOrder->fetch($supplierOrderId);
	if ($result <= 0) {
		throw new RuntimeException('Unable to fetch supplier order #'.$supplierOrderId.'.');
	}

	$supplierOrder->fetch_thirdparty();
	if (empty($supplierOrder->lines) && method_exists($supplierOrder, 'fetch_lines')) {
		$supplierOrder->fetch_lines();
	}

	return $supplierOrder;
}

/**
 * Count agenda events created for one supplier order send-by-mail event.
 *
 * @param DoliDB $db              Database handler.
 * @param int    $supplierOrderId Supplier order id.
 * @return int
 */
function countSupplierOrderAgendaEvents(DoliDB $db, int $supplierOrderId): int
{
	$sql = 'SELECT COUNT(a.id) AS nb';
	$sql .= ' FROM '.$db->prefix().'actioncomm AS a';
	$sql .= ' WHERE a.fk_element = '.((int) $supplierOrderId);
	$sql .= " AND a.elementtype = 'order_supplier'";
	$sql .= " AND a.code = 'AC_ORDER_SUPPLIER_SENTBYMAIL'";

	$resql = $db->query($sql);
	if ($resql === false) {
		throw new RuntimeException('Unable to count supplier order agenda events: '.$db->lasterror());
	}

	$obj = $db->fetch_object($resql);
	$count = $obj ? (int) $obj->nb : 0;
	$db->free($resql);

	return $count;
}

/**
 * Count the direct element link between one supplier proposal and one supplier order.
 *
 * @param DoliDB $db                 Database handler.
 * @param int    $selectedProposalId Supplier proposal id.
 * @param int    $supplierOrderId    Supplier order id.
 * @return int
 */
function countSupplierProposalToOrderLinks(DoliDB $db, int $selectedProposalId, int $supplierOrderId): int
{
	$sql = 'SELECT COUNT(ee.rowid) AS nb';
	$sql .= ' FROM '.$db->prefix().'element_element AS ee';
	$sql .= ' WHERE ee.fk_source = '.((int) $selectedProposalId);
	$sql .= " AND ee.sourcetype = 'supplier_proposal'";
	$sql .= ' AND ee.fk_target = '.((int) $supplierOrderId);
	$sql .= " AND ee.targettype = 'order_supplier'";

	$resql = $db->query($sql);
	if ($resql === false) {
		throw new RuntimeException('Unable to count supplier proposal to supplier order links: '.$db->lasterror());
	}

	$obj = $db->fetch_object($resql);
	$count = $obj ? (int) $obj->nb : 0;
	$db->free($resql);

	return $count;
}

/**
 * Assert one condition.
 *
 * @param bool   $condition Condition to assert.
 * @param string $message   Failure message.
 * @return void
 */
function assertCondition(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/**
 * Assert that the workflow branch really executed as expected.
 *
 * @param array<string,mixed>         $response     Workflow response.
 * @param array<string,mixed>         $profile      Expected execution profile.
 * @param CliChaumeilSt8TestMailService $mailService Test mail service.
 * @return int
 */
function assertWorkflowResponse(array $response, array $profile, CliChaumeilSt8TestMailService $mailService): int
{
	$status = (string) ($response['status'] ?? '');
	$success = !empty($response['success']);
	$shouldReload = !empty($response['should_reload']);
	$debug = isset($response['debug']) && is_array($response['debug']) ? $response['debug'] : array();
	$supplierOrderId = !empty($debug['supplier_order_id']) ? (int) $debug['supplier_order_id'] : 0;

	assertCondition($status === (string) $profile['expected_status'], 'Unexpected workflow status: '.$status);
	assertCondition($success === (bool) $profile['expected_success'], 'Unexpected workflow success flag.');
	assertCondition($shouldReload === (bool) $profile['expected_should_reload'], 'Unexpected workflow reload flag.');
	assertCondition($mailService->wasSendCalled() === (bool) $profile['expect_send_called'], 'Unexpected mail-service send() execution path.');
	assertCondition($mailService->wasTransportCalled() === (bool) $profile['expect_transport_called'], 'Unexpected mail transport execution path.');
	assertCondition($mailService->wasTriggerCalled() === (bool) $profile['expect_trigger_called'], 'Unexpected agenda trigger execution path.');

	if (!empty($profile['expect_order'])) {
		assertCondition($supplierOrderId > 0, 'The workflow did not return a created supplier order id.');
	} else {
		assertCondition($supplierOrderId === 0, 'The workflow unexpectedly created a supplier order.');
	}

	return $supplierOrderId;
}

/**
 * Assert that linked supplier proposals are either unchanged or fully processed.
 *
 * @param DoliDB         $db                   Database handler.
 * @param array<int,int> $initialStatuses      Initial statuses indexed by proposal id.
 * @param int            $selectedProposalId   Selected supplier proposal id.
 * @param bool           $expectProcessedState True to assert ST-8 final statuses.
 * @return void
 */
function assertSupplierProposalStatuses(DoliDB $db, array $initialStatuses, int $selectedProposalId, bool $expectProcessedState): void
{
	foreach ($initialStatuses as $proposalId => $initialStatus) {
		$currentStatus = loadSupplierProposalStatus($db, (int) $proposalId);

		if ($expectProcessedState) {
			if ((int) $proposalId === $selectedProposalId) {
				assertCondition($currentStatus === SupplierProposal::STATUS_CLOSE, 'The selected supplier proposal was not moved to STATUS_CLOSE.');
			} else {
				assertCondition($currentStatus === SupplierProposal::STATUS_NOTSIGNED, 'A non-selected supplier proposal was not moved to STATUS_NOTSIGNED.');
			}

			continue;
		}

		assertCondition($currentStatus === (int) $initialStatus, 'A supplier proposal status changed even though the workflow should have failed before persistence.');
	}
}

/**
 * Assert the created supplier order state.
 *
 * @param DoliDB $db                 Database handler.
 * @param int    $supplierOrderId    Supplier order id.
 * @param int    $selectedProposalId Selected supplier proposal id.
 * @return void
 */
function assertSupplierOrderState(DoliDB $db, int $supplierOrderId, int $selectedProposalId): void
{
	$supplierOrder = fetchSupplierOrder($db, $supplierOrderId);
	assertCondition((int) $supplierOrder->statut === CommandeFournisseur::STATUS_VALIDATED, 'The supplier order was not validated.');
	assertCondition(!empty($supplierOrder->thirdparty->id), 'The supplier order supplier is not loaded.');
	assertCondition(countSupplierProposalToOrderLinks($db, $selectedProposalId, $supplierOrderId) > 0, 'The supplier proposal is not linked to the created supplier order.');

	$documentPath = DOL_DATA_ROOT.'/'.ltrim((string) $supplierOrder->last_main_doc, '/');
	assertCondition((string) $supplierOrder->last_main_doc !== '', 'The supplier order main PDF path is empty.');
	assertCondition(dol_is_file($documentPath), 'The supplier order main PDF does not exist at '.$documentPath.'.');

	$supplierContacts = $supplierOrder->thirdparty->getContacts(0, CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE, 'order_supplier');
	if ($supplierContacts === -1) {
		throw new RuntimeException('Unable to load supplier follow-up contacts while asserting the supplier order state.');
	}
	if (!is_array($supplierContacts)) {
		throw new RuntimeException('Unexpected supplier follow-up contact payload while asserting the supplier order state.');
	}
	if (!empty($supplierContacts)) {
		$orderContacts = $supplierOrder->liste_contact(
			-1,
			CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_SOURCE,
			0,
			CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE,
			1
		);
		if (!is_array($orderContacts)) {
			throw new RuntimeException('Unable to load supplier order contacts while asserting the supplier order state.');
		}

		assertCondition(!empty($orderContacts), 'Supplier follow-up contacts were not attached to the supplier order.');
	}
}

/**
 * Validate one named case end to end.
 *
 * @param DoliDB                       $db              Database handler.
 * @param array<int,int>               $initialStatuses Initial proposal statuses.
 * @param int                          $selectedProposalId Selected supplier proposal id.
 * @param array<string,mixed>          $response        Workflow response.
 * @param array<string,mixed>          $profile         Expected execution profile.
 * @param CliChaumeilSt8TestMailService $mailService     Test mail service.
 * @return void
 */
function validateCaseExecution(
	DoliDB $db,
	array $initialStatuses,
	int $selectedProposalId,
	array $response,
	array $profile,
	CliChaumeilSt8TestMailService $mailService
): void {
	$supplierOrderId = assertWorkflowResponse($response, $profile, $mailService);
	$expectOrder = !empty($profile['expect_order']);

	assertSupplierProposalStatuses($db, $initialStatuses, $selectedProposalId, $expectOrder);

	if (!$expectOrder) {
		return;
	}

	assertSupplierOrderState($db, $supplierOrderId, $selectedProposalId);

	$agendaEventCount = countSupplierOrderAgendaEvents($db, $supplierOrderId);
	if (!empty($profile['expect_agenda_event'])) {
		assertCondition($agendaEventCount > 0, 'No agenda event was created for the supplier order sent-by-mail workflow.');
	} else {
		assertCondition($agendaEventCount === 0, 'An agenda event was created even though the case should not create one.');
	}
}

$langs->loadLangs(array('clichaumeil@clichaumeil', 'main'));

if (hasCliFlag($argv, 'help') || hasCliFlag($argv, 'h')) {
	printUsage();
	exit(0);
}

$parentType = strtolower(trim(getCliOption($argv, 'parent-type')));
$parentId = (int) getCliOption($argv, 'parent-id', '0');
$supplierProposalId = (int) getCliOption($argv, 'supplier-proposal-id', '0');
$userId = (int) getCliOption($argv, 'user-id', '0');
$confirm = getCliOption($argv, 'confirm', '0');
$caseName = getCliOption($argv, 'case');
$mailMode = getCliOption($argv, 'mail-mode', 'simulate_success');
$dryRun = getCliBoolOption($argv, 'dry-run', true);
$showDebug = getCliBoolOption($argv, 'show-debug', false);

if ($parentType === '' || $parentId <= 0 || $supplierProposalId <= 0 || $userId <= 0 || $confirm !== '1') {
	printUsage();
	exit(1);
}

$executionProfile = buildExecutionProfile($caseName, $mailMode);
$normalizedMailMode = (string) $executionProfile['mail_mode'];
if (!in_array($normalizedMailMode, array('simulate_success', 'simulate_mail_failure', 'simulate_agenda_failure', 'real'), true)) {
	print "Invalid --mail-mode value.\n";
	printUsage();
	exit(1);
}

$exitCode = 1;
$resultLines = array();
$debugPayload = array();
$dryRunTransactionOpened = false;

try {
	$executionUser = new User($db);
	$userFetchResult = $executionUser->fetch($userId);
	if ($userFetchResult <= 0) {
		throw new RuntimeException('Unable to fetch execution user #'.$userId.'.');
	}
	$executionUser->loadRights();

	$parent = loadParentObject($db, $parentType, $parentId);
	$initialStatuses = loadLinkedSupplierProposalStatuses($parent, $db);
	if (empty($initialStatuses)) {
		throw new RuntimeException('No linked supplier proposal found for the selected parent object.');
	}
	if (!isset($initialStatuses[$supplierProposalId])) {
		throw new RuntimeException('The selected supplier proposal is not linked to the selected parent object.');
	}

	if ($dryRun) {
		$db->begin();
		$dryRunTransactionOpened = true;
	}

	$mailService = new CliChaumeilSt8TestMailService($db, $conf, $langs, $normalizedMailMode);
	$workflow = new CliChaumeilSubcontractorSelectionWorkflow($db, $conf, $langs, null, null, $mailService);

	$response = $workflow->execute($parent, $supplierProposalId, $executionUser);
	$debugPayload = array(
		'execution_profile' => $executionProfile,
		'response' => $response,
		'mail_service' => array(
			'send_called' => $mailService->wasSendCalled(),
			'transport_called' => $mailService->wasTransportCalled(),
			'trigger_called' => $mailService->wasTriggerCalled(),
		),
	);

	if (!empty($executionProfile['is_case_mode'])) {
		validateCaseExecution($db, $initialStatuses, $supplierProposalId, $response, $executionProfile, $mailService);
		$resultLines[] = array('label' => 'OK', 'message' => 'Case `'.$executionProfile['name'].'` validated successfully.');
		$exitCode = 0;
	} else {
		$status = (string) ($response['status'] ?? CliChaumeilSupplierOrderConfig::RESULT_ERROR);
		$message = (string) ($response['message'] ?? 'No response message');

		if ($status === CliChaumeilSupplierOrderConfig::RESULT_SUCCESS) {
			$resultLines[] = array('label' => 'OK', 'message' => $message);
			$exitCode = 0;
		} elseif ($status === CliChaumeilSupplierOrderConfig::RESULT_WARNING) {
			$resultLines[] = array('label' => 'WARN', 'message' => $message);
			$exitCode = 2;
		} else {
			$resultLines[] = array('label' => 'FAIL', 'message' => $message);
			$exitCode = 1;
		}
	}
} catch (Throwable $exception) {
	$resultLines[] = array('label' => 'FAIL', 'message' => $exception->getMessage());
	$exitCode = 1;
}

if ($dryRunTransactionOpened) {
	$db->rollback();
	$resultLines[] = array(
		'label' => 'INFO',
		'message' => 'Dry-run rollback executed. SQL changes were reverted, but generated files, triggers and real emails are not rolled back.'
	);
}

foreach ($resultLines as $resultLine) {
	printResultLine((string) $resultLine['label'], (string) $resultLine['message']);
}

if ($showDebug) {
	$encodedDebugPayload = json_encode($debugPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if ($encodedDebugPayload === false) {
		printResultLine('INFO', 'Unable to encode debug payload as JSON.');
	} else {
		print $encodedDebugPayload."\n";
	}
}

exit($exitCode);
