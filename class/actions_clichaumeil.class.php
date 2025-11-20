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

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once __DIR__.'/../lib/CliChaumeilProductCost.lib.php';

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
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("ClichaumeilMassAction").'</option>';
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
	 * @param array<string,mixed> $parameters
	 * @param CommonObject        $object
	 * @param string              $action
	 * @param HookManager         $hookmanager
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

		static $scriptInjected = false;
		if (!$scriptInjected) {
			$this->resprints .= '<script>
				document.addEventListener("DOMContentLoaded", function () {
					const field = document.querySelector(\'input[name="options_pa_fg"]\');
					if (field) {
						field.setAttribute("readonly", "readonly");
						field.classList.add("readonly");
						field.closest("tr")?.classList.add("clichaumeil-pa-fg");
					}
				});
				</script>';
			$scriptInjected = true;
		}

		return 0;
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

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
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
	 * @param Product $product
	 * @return void
	 */
	private function populateDefaultOverheadRateOnCreate(Product $product): void
	{
		if (!empty($product->id) || (int) $product->type !== Product::TYPE_PRODUCT) {
			return;
		}

		$key = 'options_fg_percent';
		$postedValue = GETPOST($key, 'alphanohtml');
		if ($postedValue !== '') {
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
	 * @param array<string,mixed> $parameters
	 * @param CommonObject        $object
	 * @param string              $action
	 * @param HookManager         $hookmanager
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
		if (!$this->hasFgPercentValueInRecord($values)) {
			$langs->load('clichaumeil@clichaumeil');
			setEventMessages($langs->trans('CliChaumeilErrorMissingFgPercent', $values['p.ref'] ?? ''), null, 'errors');
			return -1;
		}

		$product = $this->loadProductFromImport($values);
		if (!$product || !CliChaumeilProductCostCalculator::isSupportedProduct($product)) {
			return 0;
		}

		$this->applyFgPercentFromImport($product, $values['extra.fg_percent'], $user);

		$result = CliChaumeilProductCostCalculator::calculateAndUpdateProductCostPriceFromExtrafields($product, $user);
		return ($result < 0) ? -1 : 0;
	}

	/**
	 * @param array<string,mixed> $parameters
	 * @return Product|null
	 */
	private function loadProductFromImport(array $values): ?Product
	{
		$id = !empty($values['p.rowid']) ? (int) $values['p.rowid'] : 0;
		$ref = $values['p.ref'] ?? '';

		$product = new Product($this->db);
		$result = $product->fetch($id, $ref);
		if ($result <= 0) {
			return null;
		}

		return $product;
	}

	/**
	 * @param array<string,mixed> $parameters
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
			$index = ((int) $position) - 1;
			if ($index < 0 || !isset($records[$index]['val'])) {
				continue;
			}

			$values[$target] = $records[$index]['val'];
		}

		return $values;
	}

	/**
	 * Check if fg_percent extrafield has value on product.
	 *
	 * @param Product $product
	 * @return bool
	 */
	private function applyFgPercentFromImport(Product $product, string $rawValue, User $user): void
	{
		$formatted = CliChaumeilProductCostCalculator::normalizeDecimal($rawValue);
		$product->array_options['options_fg_percent'] = $formatted;
		$product->updateExtraField('fg_percent', 'CLICHAUMEIL_PRODUCT_COST', $user);
	}

	private function hasFgPercentValueInRecord(array $values): bool
	{
		if (!array_key_exists('extra.fg_percent', $values)) {
			return false;
		}

		$value = $values['extra.fg_percent'];
		return !($value === null || $value === '');
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
			if ($element == 'societe' && $user->hasRight('clichaumeil', 'chaumeilrfa', 'read')){
				$datacount = 0;

				//SQL COUNT RFA by socid
				$rfaCountsql = "SELECT COUNT(*) as count FROM ".$this->db->prefix()."clichaumeil_chaumeilrfa WHERE fk_soc = ".(int)$id;

				$resql = $this->db->query($rfaCountsql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$datacount = $obj->count;
				} else {
					dol_print_error($this->db);
				}

				if ($object->fournisseur && $this->rfa_tab_added == false ) {
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
		// If there's no data to send, do nothing
		if (empty(self::$lineData)) {
			return 0;
		}

		// 1️⃣ Prepare the data payload for JS
		$dataForJs = ['lines' => self::$lineData];

		// 2️⃣ Output the JSON payload in a <script> tag
		echo '<script type="application/json" id="margins-pagedata">'
			. json_encode($dataForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
			. '</script>';

		// 3️⃣ Build the URL of your JS file
		$jsUrl = dol_buildpath('/clichaumeil/js/margin_check_warning.js', 1);

		// 4️⃣ Load the JS file
		echo '<script src="' . $jsUrl . '" defer></script>';

		// 5️⃣ Reset static data to prevent leakage
		self::$lineData = [];

		return 0;
	}


	/**
	 * Overload the printObjectLine method to prepare margin-related data for each line.
	 *
	 * This hook collects necessary pricing information (unit price and cost price)
	 * for each object line (propal, order, invoice...) and stores them in a static array.
	 * The data will later be used by the JavaScript file `margin_check_warning.js`
	 * to check for negative margins and display a warning icon when needed.
	 *
	 * @param array         $parameters   Hook metadata (context, current line, etc.)
	 * @param CommonObject  $object       The business object being processed (proposal, order, invoice...)
	 * @param string        $action       Current action (e.g., 'create', 'edit', or '')
	 * @param HookManager   $hookmanager  Hook manager instance
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
				$costPrice = (float)$line->pa_ht;
			}

			// 💸 Get unit price (PU HT)
			$pu_ht = (float) $line->subprice;

			// ⚠️ Prepare the warning icon HTML
			$warningIcon = img_warning(
				$langs->trans("WarningNegativeMargin")
			);

			// 📦 Store the data for the JS script
			self::$lineData[$line->id] = [
				'pu_ht'        => $pu_ht,
				'cost_price'   => $costPrice,
				'warning_icon' => $warningIcon,
			];
		}

		return 0;
	}

	public function calculateCostsBomAfter($parameters, &$object, &$action, $hookmanager):int
	{
		$action = GETPOST('action', 'alphanohtml');
		if($action == 'update_extras' || $action == 'update') {
			$generalExpenses = GETPOSTFLOAT('options_clichaumeil_generalexpenses');
		}
		else {
			$generalExpenses = $object->array_options['options_clichaumeil_generalexpenses'];
		}
		$object->total_cost = $object->total_cost * (1+(float) $generalExpenses / 100);

		return 0;
	}

}
