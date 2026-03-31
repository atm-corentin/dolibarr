<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    clichaumeil/class/actions_clichaumeil.class.php
 * \ingroup clichaumeil
 * \brief   Example hook overload.
 *
 * TODO: Write detailed description here.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once __DIR__ . '/CliChaumeilProductCost.class.php';
require_once __DIR__ . '/CliChaumeilProductCostImportService.class.php';
require_once __DIR__ . '/CliChaumeilProductCostViewRenderer.class.php';
require_once __DIR__ . '/../lib/clichaumeil.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once __DIR__ . '/SupplierProposalService.class.php';

/**
 * Class ActionsClichaumeil
 */
class ActionsClichaumeil extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();

	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;

	private static $lineData = [];

	/** @var bool */
	private $subcontractorAssetsLoaded = false;


	private const COST_BREAKDOWN_FIELDS = array(
		'clichaumeil_prc_separator',
		'clichaumeil_pa_support',
		'clichaumeil_pa_sav',
		'clichaumeil_pa_machine',
		'clichaumeil_pa_encre',
		'clichaumeil_pa_mo',
		'clichaumeil_conditionnement_percent',
		'clichaumeil_transport_percent',
		'clichaumeil_fg_percent',
		'clichaumeil_pa_fg',
	);

	private const READONLY_FIELD = 'clichaumeil_pa_fg';

	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	public $rfa_tab_added = false;

	/**
	 * Execute action
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonObject		$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}


	/**
	 * Add a button to pick a subcontractor from linked supplier proposals on propal/order cards.
	 *
	 * @param array<string,mixed> $parameters Hook metadata (context, etc...)
	 * @param CommonObject        $object     Current object
	 * @param string              $action     Current action
	 * @param HookManager         $hookmanager Hook manager instance
	 * @return int
	 */
	public function addMoreActionsButtons(array $parameters, CommonObject &$object, string &$action, HookManager $hookmanager)
	{
		global $langs, $user, $conf;
		$langs->loadLangs(array('clichaumeil@clichaumeil', 'supplier_proposal', 'companies', 'main'));

		require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

		if (!$this->shouldShowSubcontractorPicker($parameters, $object, $user)) {
			return 0;
		}

		$supplierProposals = SupplierProposalService::loadLinkedSupplierProposals($object, $this->db);
		$supplierProposals = SupplierProposalService::preloadThirdparties($supplierProposals, $this->db);
		if (!$this->hasSelectableSupplierProposal($supplierProposals)) {
			return 0;
		}

		$this->renderSubcontractorPicker($object, $supplierProposals, $langs);

		return 0;
	}

	/**
	 * Check if the subcontractor picker should be displayed in the current context.
	 *
	 * @param array<string,mixed> $parameters Hook parameters.
	 * @param CommonObject        $object     Current business object.
	 * @param User                $user       Current user.
	 * @return bool
	 */
	private function shouldShowSubcontractorPicker(array $parameters, CommonObject $object, User $user): bool
	{
		$contexts = isset($parameters['context']) ? explode(':', (string) $parameters['context']) : array();
		$allowedContexts = array('propalcard', 'ordercard');
		if (empty(array_intersect($allowedContexts, $contexts))) {
			return false;
		}

		if (empty($object->id) || !$user->hasRight('supplier_proposal', 'creer')) {
			return false;
		}

		$status = isset($object->status) ? (int) $object->status : (int) $object->statut;
		if ($object->element === 'propal' && in_array($status, array(Propal::STATUS_NOTSIGNED, Propal::STATUS_CANCELED), true)) {
			return false;
		}
		if ($object->element === 'commande' && $status === Commande::STATUS_CANCELED) {
			return false;
		}

		return true;
	}

	/**
	 * Ensure there are selectable supplier proposals and none is already signed.
	 *
	 * @param SupplierProposal[] $supplierProposals Supplier proposals linked to the source object.
	 * @return bool
	 */
	private function hasSelectableSupplierProposal(array $supplierProposals): bool
	{
		if (empty($supplierProposals)) {
			return false;
		}

		foreach ($supplierProposals as $supplierProposal) {
			$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
			if ($currentStatus === SupplierProposal::STATUS_SIGNED) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Render button, modal and required assets for subcontractor selection.
	 *
	 * @param CommonObject       $object            Current business object.
	 * @param SupplierProposal[] $supplierProposals Supplier proposals to expose in the picker.
	 * @param Translate          $langs             Translation helper.
	 * @return void
	 */
	private function renderSubcontractorPicker(CommonObject $object, array $supplierProposals, Translate $langs): void
	{
		$buttonId = 'clichaumeil-open-subcontractor-modal-' . $object->id;
		$modalId = 'clichaumeil-subcontractor-modal-' . $object->id;
		$ajaxUrl = dol_buildpath('/clichaumeil/script/interface.php', 1);
		$token = newToken();

		$templatePath = __DIR__ . '/../core/tpl/subcontractor_picker.tpl.php';
		if (file_exists($templatePath)) {
			include $templatePath;
		}

		$this->printSubcontractorAssets();
	}

	/**
	 * Load JS/CSS for subcontractor selection once.
	 *
	 * @return void
	 */
	private function printSubcontractorAssets(): void
	{
		if ($this->subcontractorAssetsLoaded) {
			return;
		}

		print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath('/clichaumeil/css/subcontractor.css', 1) . '" />';
		print '<script src="' . dol_buildpath('/clichaumeil/js/choose_subcontractor.js', 1) . '" defer></script>';
		$this->subcontractorAssetsLoaded = true;
	}


	/**
	 * Overload the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters     Hook metadata (context, etc...)
	 * @param	CommonObject		$object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string	$action						Current action (if set). Generally create or edit or null
	 * @param	HookManager	$hookmanager			Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("ClichaumeilMassAction") . '</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Pre-fill product extrafields and lock computed fields when applicable.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters.
	 * @param CommonObject        $object      Current object.
	 * @param string              $action      Current action.
	 * @param HookManager         $hookmanager Hook manager.
	 * @return int
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		$context = $parameters['context'] ?? ($parameters['currentcontext'] ?? '');
		if (strpos((string) $context, 'productcard') === false) {
			return 0;
		}

		if (!$object instanceof Product || !CliChaumeilProductCostCalculator::isSupportedProduct($object)) {
			return 0;
		}

		$this->populateDefaultOverheadRateOnCreate($object);

		return 0;
	}

	/**
	 * Handle cost breakdown extrafields updates from supplier price tab.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters.
	 * @param CommonObject        $object      Current object.
	 * @param string              $action      Current action.
	 * @param HookManager         $hookmanager Hook manager.
	 * @return int
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		$context = (string) ($parameters['context'] ?? ($parameters['currentcontext'] ?? ''));
		if (strpos($context, 'pricesuppliercard') === false) {
			return 0;
		}

		if (GETPOST('cancel', 'alpha')) {
			$action = '';
			return 0;
		}

		$attr = GETPOST('attr', 'aZ09');
		$isCostBreakdown = (int) GETPOST('clichaumeil_cost_breakdown', 'int') === 1;
		if ($action !== 'update_extrafields' || !$isCostBreakdown || !in_array($attr, self::COST_BREAKDOWN_FIELDS, true) || $attr === self::READONLY_FIELD) {
			return 0;
		}

		if (!$this->isCsrfTokenValid(GETPOST('token', 'alphanohtml'))) {
			accessforbidden();
		}

		if (!$user->hasRight('clichaumeil', 'product', 'read_cost_composition')) {
			accessforbidden();
		}

		$productId = GETPOSTINT('id');
		if (!$productId && !empty($parameters['id_prod'])) {
			$productId = (int) $parameters['id_prod'];
		}

		if ($productId <= 0) {
			return 0;
		}

		$product = new Product($this->db);
		if ($product->fetch($productId) <= 0) {
			setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
			return -1;
		}

		// Apply only to supported products
		if (!CliChaumeilProductCostCalculator::isSupportedProduct($product)) {
			return 0;
		}

		$product->fetch_optionals($productId);

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('product');

		$result = $this->handleCostUpdate($product, $extrafields, $attr, $user, $langs);
		if ($result < 0) {
			return -1;
		}

		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = '';
		$this->redirectToSupplierPriceTab((int) $product->id);

		return 1;
	}


	/**
	 * Persist extrafields and recompute cost.
	 *
	 * @param Product     $product     Product being updated.
	 * @param ExtraFields $extrafields Extrafields manager.
	 * @param string      $attr        Updated extrafield name.
	 * @param User        $user        Current user.
	 * @param Translate   $langs       Translation helper.
	 * @return int
	 */
	private function handleCostUpdate(Product $product, ExtraFields $extrafields, string $attr, User $user, Translate $langs): int
	{
		$this->db->begin();

		$result = $extrafields->setOptionalsFromPost(null, $product, $attr);
		if ($result < 0) {
			dol_syslog(__METHOD__ . ' failed in setOptionalsFromPost for product #' . (int) $product->id, LOG_ERR);
			$this->db->rollback();
			setEventMessages($extrafields->error, $extrafields->errors, 'errors');
			return -1;
		}

		$result = $product->insertExtraFields();
		if ($result < 0) {
			dol_syslog(__METHOD__ . ' failed in insertExtraFields for product #' . (int) $product->id, LOG_ERR);
			$this->db->rollback();
			setEventMessages($product->error, $product->errors, 'errors');
			return -1;
		}

		$result = CliChaumeilProductCostCalculator::calculateAndUpdateProductCostPriceFromExtrafields($product, $user);
		if ($result < 0) {
			$this->db->rollback();
			setEventMessages($langs->trans('Error'), null, 'errors');
			return -1;
		}

		$breakdown = CliChaumeilProductCostCalculator::getComputedBreakdown($product);
		if (!$breakdown->isFinalComputable) {
			setEventMessages($langs->trans('CLICHAUMEIL_INFO_MISSING_FG_PERCENT'), null, 'warnings');
		}

		$this->db->commit();
		return 0;
	}

	/**
	 * Check CSRF token validity against current and previous token.
	 *
	 * @param string $token Submitted CSRF token.
	 * @return bool
	 */
	private function isCsrfTokenValid(string $token): bool
	{
		if ($token === '') {
			return false;
		}

		$current = (string) newToken();
		$previous = function_exists('currentToken') ? (string) currentToken() : '';

		return (hash_equals($current, (string) $token))
			|| ($previous !== '' && hash_equals($previous, (string) $token));
	}

	/**
	 * Redirect to the supplier price tab after a successful POST update.
	 *
	 * @param int $productId Product identifier.
	 * @return void
	 */
	private function redirectToSupplierPriceTab(int $productId): void
	{
		$url = dol_buildpath('/product/price_suppliers.php', 1) . '?id=' . $productId;
		header('Location: ' . $url);
		exit;
	}

	/**
	 * Overload the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	?string				$action 		Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager    Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $langs;

		$langs->load("clichaumeil@clichaumeil");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'clichaumeil') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("Clichaumeil");
			$this->results['picto'] = 'clichaumeil@clichaumeil';
		}

		$head[$h][0] = 'customreports.php?objecttype=' . $parameters['objecttype'] . (empty($parameters['tabfamily']) ? '' : '&tabfamily=' . $parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		$arrayoftypes = array();
		//$arrayoftypes['clichaumeil_myobject'] = array('label' => 'MyObject', 'picto'=>'myobject@clichaumeil', 'ObjectClassName' => 'MyObject', 'enabled' => isModEnabled('clichaumeil'), 'ClassPath' => "/clichaumeil/class/myobject.class.php", 'langs'=>'clichaumeil@clichaumeil')

		$this->results['arrayoftype'] = $arrayoftypes;

		return 0;
	}

	/**
	 * Pre-fill FG percent extrafield on new simple product when empty.
	 *
	 * @param Product $product Product under creation.
	 * @return void
	 */
	private function populateDefaultOverheadRateOnCreate(Product $product): void
	{
		if (!empty($product->id)) {
			return;
		}

		$key = 'options_clichaumeil_fg_percent';
		$postedValue = GETPOST($key, 'alphanohtml');
		if ($postedValue !== null && $postedValue !== '') {
			return;
		}

		$currentValue = $product->array_options[$key] ?? null;
		if ($currentValue !== null && $currentValue !== '') {
			return;
		}

		$product->array_options[$key] = CliChaumeilProductCostCalculator::getDefaultOverheadRate();
	}

	/**
	 * Overload the restrictedArea function : check permission on an object
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->hasRight('clichaumeil', 'myobject', 'read')) {
				$this->results['result'] = 1;
				return 1;
			}

			$this->results['result'] = 0;
			return 1;
		}

		return 0;
	}

	/**
	 * Apply CliChaumeil price calculation after an import finishes.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters.
	 * @param CommonObject        $object      Current object.
	 * @param string              $action      Current action.
	 * @param HookManager         $hookmanager Hook manager.
	 * @return int
	 */
	public function afterImportInsert($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		if ((int) ($parameters['step'] ?? 0) !== 6) {
			return 0;
		}

		$code = (string) ($parameters['datatoimport'] ?? '');
		if (strpos($code, 'produit_') !== 0) {
			return 0;
		}

		$values = $this->extractImportValues($parameters);
		$service = new CliChaumeilProductCostImportService($this->db);
		$result = $service->syncImportedProductCost($values, $user);
		return ($result < 0) ? -1 : 0;
	}

	/**
	 * Extract import values keyed by their target database field names.
	 *
	 * @param array<string,mixed> $parameters Hook parameters containing import mappings and row values.
	 * @return array<string,mixed>
	 */
	private function extractImportValues(array $parameters): array
	{
		$values = array();
		$match = $parameters['array_match_file_to_database'] ?? array();
		$records = $parameters['arrayrecord'] ?? array();

		if (!is_array($match) || !is_array($records)) {
			return $values;
		}

		foreach ($match as $position => $target) {
			$positionInt = (int) $position;
			$candidateIndexes = array($positionInt, $positionInt - 1);
			$found = false;

			foreach ($candidateIndexes as $index) {
				if ($index < 0 || !isset($records[$index]['val'])) {
					continue;
				}

				$values[$target] = $records[$index]['val'];
				$found = true;
				break;
			}

			if (!$found) {
				continue;
			}
		}

		return $values;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param	array<string,mixed>	$parameters		Array of parameters
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			'add', 'update', 'view'
	 * @param	Hookmanager			$hookmanager	Hookmanager
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if ($this->rfa_tab_added == true) {
			return 0; // déjà passé une fois
		}

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('clichaumeil@clichaumeil');
			// used when we want to add some tabs
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if ($element == 'societe' && $user->hasRight('clichaumeil', 'chaumeilrfa', 'read')) {
				$datacount = 0;

				//SQL COUNT RFA by socid
				$rfaCountsql = "SELECT COUNT(*) as count FROM " . $this->db->prefix() . "clichaumeil_chaumeilrfa WHERE fk_soc = " . (int) $id;

				$resql = $this->db->query($rfaCountsql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$datacount = $obj->count;
					$this->db->free($resql);
				} else {
					dol_print_error($this->db);
				}

				if ($object->fournisseur && $this->rfa_tab_added == false) {
					$parameters['head'][$counter][0] = dol_buildpath('/clichaumeil/chaumeilrfa_list.php', 1) . '?socid=' . $id;
					$parameters['head'][$counter][1] = $langs->trans('ClichaumeilTabRfa');
					$this->rfa_tab_added = true;
				}

				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'clichaumeilrfa';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {  // @phpstan-ignore-line
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// From V14 onwards, $parameters['head'] is modifiable by reference
				return 0;
			}
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array('clichaumeil_chaumeilrfa');

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Inject margin data into the page footer and load the JS script.
	 *
	 * This hook outputs a `<script>` tag containing JSON data used by
	 * `margin_check_warning.js` to detect negative margins. It also includes
	 * the external JS file automatically.
	 *
	 * @param array         $parameters   Hook metadata (context, etc.)
	 * @param CommonObject  $object       The business object being processed (propal, order, invoice...)
	 * @param string        $action       Current action
	 * @param HookManager   $hookmanager  Hook manager instance
	 *
	 * @return int Returns 0 on success, <0 on error
	 */
	public function llxFooter($parameters, &$object, &$action, $hookmanager): int
	{
		global $langs, $user;

		$langs->load('clichaumeil@clichaumeil');

		$context = (string) ($parameters['context'] ?? ($parameters['currentcontext'] ?? ''));
		if (strpos($context, 'pricesuppliercard') !== false) {
			$this->renderSupplierCostBreakdownRows($parameters, $object, $action);
		}

		// Hide moved extrafields on product card to avoid duplicate display
		if (strpos($context, 'productcard') !== false && strpos($context, 'pricesuppliercard') === false) {
			$this->hideCostBreakdownOnProductCard();
		}

		/* --------------------------------------------------------------------
		 * 1) Récupération catégorie cible + produits
		 * -------------------------------------------------------------------- */

		$targetCatIds = array();
		$confValue = getDolGlobalString('CLICHAUMEIL_PRODUCT_TARGET_CATEGORY');
		if (!empty($confValue)) {
			$targetCatIds = explode(',', $confValue);
		}

		$allowedElements = array('propal', 'commande');

		if (!empty($object) && in_array($object->element, $allowedElements, true) && !empty($targetCatIds)) {
			$targetProducts = $this->getTargetProducts($targetCatIds);
			if (!empty($targetProducts)) {
				$context = $object->element;
				$productCategories = $this->mapProductCategories($object);
				$lineVisibilities = $this->buildLineVisibilities($object->lines, $targetProducts, $targetCatIds, $context, $productCategories);

				$config = array(
					'targetProducts' => $targetProducts,
					'lines' => $lineVisibilities,
				);

				print '<script type="application/json" id="clichaumeil-extrafields-data">'
					. json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
					. '</script>';

				$jsUrl = dol_buildpath('/clichaumeil/js/extrafields_visibility.js', 1);
				echo '<script src="' . $jsUrl . '" defer></script>';
			}
		}


		/* --------------------------------------------------------------------
		 * 4) Passage des données à margin_check_warning.js
		 * -------------------------------------------------------------------- */

		if (!empty(self::$lineData)) {
			echo '<script type="application/json" id="margins-pagedata">'
				. json_encode(self::$lineData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
				. '</script>';
		}

		$jsUrl = dol_buildpath('/clichaumeil/js/margin_check_warning.js', 1);
		echo '<script src="' . $jsUrl . '" defer></script>';

		self::$lineData = [];

		return 0;
	}


	/**
	 * Render CliChaumeil cost breakdown fields on supplier price tab.
	 *
	 * @param array<string,mixed> $parameters Hook parameters.
	 * @param mixed               $object     Current object.
	 * @param string              $action     Current action.
	 * @return void
	 */
	private function renderSupplierCostBreakdownRows(array $parameters, $object, string $action): void
	{
		global $user;

		$renderer = new CliChaumeilProductCostViewRenderer($this->db, self::COST_BREAKDOWN_FIELDS, self::READONLY_FIELD);
		$renderer->renderSupplierCostBreakdownRows($parameters, $object, $action, $user);
	}

	/**
	 * Hide cost breakdown extrafields on product card to avoid duplicate display.
	 *
	 * @return void
	 */
	private function hideCostBreakdownOnProductCard(): void
	{
		$renderer = new CliChaumeilProductCostViewRenderer($this->db, self::COST_BREAKDOWN_FIELDS, self::READONLY_FIELD);
		$renderer->hideCostBreakdownOnProductCard();
	}

	/**
	 * Overload the printObjectLine method to prepare margin-related data for each line.
	 *
	 * This hook collects necessary pricing information (unit price and cost price)
	 * for each object line (propal, order, invoice...) and stores them in a static array.
	 * The data will later be used by the JavaScript file `margin_check_warning.js`
	 * to check for negative margins and display a warning icon when needed.
	 *
	 * @param array<string,mixed> $parameters  Hook metadata (context, current line, etc.).
	 * @param CommonObject        $object      The business object being processed (proposal, order, invoice...).
	 * @param string              $action      Current action (e.g., 'create', 'edit', or '').
	 * @param HookManager         $hookmanager Hook manager instance.
	 *
	 * @return int Returns < 0 on error, 0 on success, 1 to bypass standard code
	 */
	public function printObjectLine($parameters, &$object, &$action, $hookmanager): int
	{
		global $db, $langs;

		$TContexts = explode(':', $parameters['context']);
		$TAllowedContexts = ['propalcard', 'ordercard', 'invoicecard'];
		$commonContexts = array_intersect($TContexts, $TAllowedContexts);

		if (!empty($commonContexts)) {
			$line = $parameters['line'];
			$costPrice = 0;
			if (!empty($line->pa_ht)) {
				$costPrice = (float) $line->pa_ht;
			}

			// 💸 Get unit price (PU HT)
			$pu_ht = (float) $line->subprice;

			// ⚠️ Prepare the warning icon HTML
			$warningIcon = img_warning(
				$langs->trans("WarningNegativeMargin")
			);

			// 📦 Store the data for the JS script
			self::$lineData[$line->id] = [
				'pu_ht' => $pu_ht,
				'cost_price' => $costPrice,
				'warning_icon' => $warningIcon,
			];
		}

		return 0;
	}

	/**
	 * Apply general expenses on BOM total cost after BOM update.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters.
	 * @param CommonObject        $object      BOM object.
	 * @param string              $action      Current action.
	 * @param HookManager         $hookmanager Hook manager.
	 * @return int
	 */
	public function calculateCostsBomAfter($parameters, &$object, &$action, $hookmanager): int
	{
		$action = GETPOST('action', 'alphanohtml');
		if ($action == 'update_extras' || $action == 'update') {
			$generalExpenses = GETPOSTFLOAT('options_clichaumeil_generalexpenses');
		} else {
			$generalExpenses = $object->array_options['options_clichaumeil_generalexpenses'];
		}
		$object->total_cost = $object->total_cost * (1 + (float) $generalExpenses / 100);

		return 0;
	}

	/**
	 * Hook to add more options to a setup form.
	 *
	 * @param   array<string,mixed> $parameters  Hook context parameters.
	 * @param   CommonObject        $object      The object hooked (often $this, but context varies).
	 * @param   string              $action      Current action.
	 * @param   HookManager         $hookmanager Hook manager.
	 * @return  int                               <0 if KO, 0 if no action, >0 if OK
	 */
	public function formMoreOptions($parameters, &$object, &$action, $hookmanager)
	{
		return 0;
	}

	/**
	 * Hook to add more services to the externalaccess home page.
	 *
	 * @param   array<string,mixed> $parameters  Hook context parameters.
	 * @param   CommonObject        $object      The object hooked (in this case, the $context from the calling file).
	 * @param   string              $action      Current action.
	 * @param   HookManager         $hookmanager Hook manager.
	 * @return  int                               <0 if KO, 0 if no action/no block, >0 if block
	 */
	public function PrintServices($parameters, &$object, &$action, $hookmanager) // phpcs:ignore PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	{
		global $conf, $user, $langs;

		$langs->load("clichaumeil@clichaumeil");

		// $object is the $context passed from the calling file
		$context = $object;

		// Check if our specific service (Supplier Proposal) is activated
		if (getDolGlobalInt("CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL") && isModEnabled('supplier_proposal')) { // We assume this new right
			// Get the URL for our controller
			$link = $context->getControllerUrl('supplier_proposal');

			// Start output buffering
			ob_start();

			// Call the function to print our new service
			printService($langs->trans('CLICHAUMEIL_SUPPLIERPROPOSALS'), 'fa-handshake-o', $link);

			// Add the captured HTML to the hook manager's output buffer
			$this->resprints .= ob_get_clean();

			// Return 0 to signal "OK" but DO NOT block the default services
			return 0;
		}

		return 0; // No action
	}

	/**
	 * Inject CliChaumeil settings into FormSetup rendering for ExternalAccess.
	 *
	 * This runs inside FormSetup::generateOutput(), so items are added before the
	 * ExternalAccess setup page renders, avoiding any duplicate blocks.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters (editMode, ...).
	 * @param FormSetup           $formSetup   FormSetup instance.
	 * @param string              $action      Current action.
	 * @param HookManager         $hookmanager Hook manager.
	 * @return int
	 */
	public function formSetupBeforeGenerateOutput($parameters, &$formSetup, &$action, $hookmanager)
	{
		global $langs;

		$TContexts = explode(':', $parameters['context']);

		if (!in_array('externalaccesssetup', $TContexts)) {
			return 0;
		}

		if (!is_object($formSetup) || !method_exists($formSetup, 'newItem')) {
			return 0;
		}

		$langs->load('clichaumeil@clichaumeil');

		static $added = false;
		if ($added || !empty($formSetup->items['CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL'])) {
			return 0;
		}
		$added = true;

		$formSetup->newItem('CLICHAUMEIL_SPE_CUSTOMER')->setAsTitle();
		$formSetup->newItem('CLICHAUMEIL_ACTIVATE_SUPPLIER_PROPOSAL')->setAsYesNo();
		$formSetup->newItem('CLICHAUMEIL_MANDATORY_ATTACHED_FILES_SUPPLIER_PROPOSAL')->setAsYesNo();

		return 0;
	}

	/**
	 * Overloading the PrintPageView function : replacing the parent's function with the one below
	 *
	 * @param   array<string,mixed> $parameters  Hook metadatas (context, etc...).
	 * @param   CommonObject        $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...).
	 * @param   string              $action      Current action (if set). Generally create or edit or null.
	 * @param   HookManager         $hookmanager Hook manager propagated to allow calling another hook.
	 * @return  int                              < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function PrintPageView($parameters, &$object, &$action, $hookmanager) // phpcs:ignore PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	{
		global $conf, $user, $langs;
		$error = 0; // Error counter

		if (in_array('externalaccesspage', explode(':', $parameters['context']))) {
			$context = Context::getInstance();

			// Note: supplier_proposal_card is now handled by a dedicated controller
			// See www/controllers/supplierProposalCard.controller.php
		}

		return 0;
	}

	/**
	 * Return product ids that belong to the target categories.
	 *
	 * @param int[] $targetCatIds Target category identifiers.
	 * @return int[]
	 */
	private function getTargetProducts(array $targetCatIds): array
	{
		$targetProducts = array();

		foreach ($targetCatIds as $targetCatId) {
			$cat = new Categorie($this->db);
			if ($cat->fetch($targetCatId) > 0 && $cat->id > 0) {
				foreach ($cat->getObjectsInCateg('product') as $p) {
					$targetProducts[] = (int) $p->id;
				}
			}
		}

		return array_unique($targetProducts);
	}

	/**
	 * Build a map productId => array of category ids for all products present in object lines.
	 *
	 * @param CommonObject $object Source document object.
	 * @return array<int,int[]>
	 */
	private function mapProductCategories(CommonObject $object): array
	{
		$productIds = array();
		foreach ((array) $object->lines as $line) {
			if (!empty($line->fk_product)) {
				$productIds[] = (int) $line->fk_product;
			}
		}
		$productIds = array_values(array_unique(array_filter($productIds)));

		if (empty($productIds)) {
			return array();
		}

		$sql = 'SELECT cp.fk_product, cp.fk_categorie';
		$sql .= ' FROM ' . $this->db->prefix() . 'categorie_product cp';
		$sql .= ' INNER JOIN ' . $this->db->prefix() . 'categorie c ON c.rowid = cp.fk_categorie AND c.type = ' . (int) Categorie::TYPE_PRODUCT;
		$sql .= ' WHERE cp.fk_product IN (' . implode(',', $productIds) . ')';

		$productCategories = array();
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$pid = (int) $obj->fk_product;
				$cid = (int) $obj->fk_categorie;
				if (!isset($productCategories[$pid])) {
					$productCategories[$pid] = array();
				}
				$productCategories[$pid][] = $cid;
			}
			$this->db->free($resql);
		}

		return $productCategories;
	}

	/**
	 * Prepare visibility payload for JS.
	 *
	 * @param array<int,mixed>         $lines             Document lines.
	 * @param int[]                    $targetProducts    Target product identifiers.
	 * @param int[]                    $targetCatIds      Target category identifiers.
	 * @param string                   $context           Current context.
	 * @param array<int,array<int,int>> $productCategories Map of product ids to category ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function buildLineVisibilities(array $lines, array $targetProducts, array $targetCatIds, string $context, array $productCategories): array
	{
		$lineVisibilities = array();

		foreach ((array) $lines as $line) {
			$lineElement = !empty($line->element) ? $line->element : $context . 'det';

			$isInCat = false;
			if (!empty($line->fk_product)) {
				$isInCat = in_array((int) $line->fk_product, $targetProducts, true);

				if (!$isInCat && isset($productCategories[$line->fk_product])) {
					$isInCat = !empty(array_intersect($targetCatIds, $productCategories[$line->fk_product]));
				}
			}

			$lineVisibilities[] = array(
				'element' => $lineElement,
				'id' => (int) $line->id,
				'show' => $isInCat,
				'selectors' => array(
					'length' => '#extrarow-' . $lineElement . '_clichaumeil_length_' . (int) $line->id,
					'height' => '#extrarow-' . $lineElement . '_clichaumeil_height_' . (int) $line->id,
				),
			);
		}

		return $lineVisibilities;
	}
}
