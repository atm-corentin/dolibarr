<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/CliChaumeilProductCost.class.php';

/**
 * Dedicated renderer for the supplier-price cost breakdown UI.
 */
class CliChaumeilProductCostViewRenderer
{
	/**
	 * Prevent duplicate script inclusion during the same request.
	 *
	 * @var bool
	 */
	private static $costBreakdownScriptLoaded = false;

	/**
	 * Cost breakdown separator field.
	 *
	 * @var string
	 */
	private const SEPARATOR_FIELD = 'clichaumeil_prc_separator';

	/**
	 * @var DoliDB
	 */
	private $db;

	/**
	 * @var string[]
	 */
	private $fields;

	/**
	 * @var string
	 */
	private $readonlyField;

	/**
	 * @param DoliDB   $db            Database handler.
	 * @param string[] $fields        Breakdown field names.
	 * @param string   $readonlyField Read-only field name.
	 */
	public function __construct(DoliDB $db, array $fields, string $readonlyField)
	{
		$this->db = $db;
		$this->fields = $fields;
		$this->readonlyField = $readonlyField;
	}

	/**
	 * Render CliChaumeil cost breakdown rows on the supplier price tab.
	 *
	 * @param array<string,mixed> $parameters Hook parameters.
	 * @param mixed               $object     Current object.
	 * @param string              $action     Current action.
	 * @param User                $user       Current user.
	 * @return void
	 */
	public function renderSupplierCostBreakdownRows(array $parameters, $object, string $action, User $user): void
	{
		$context = (string) ($parameters['context'] ?? ($parameters['currentcontext'] ?? ''));
		if (strpos($context, 'pricesuppliercard') === false) {
			return;
		}

		if (!$user->hasRight('clichaumeil', 'product', 'read_cost_composition')) {
			return;
		}

		$productId = GETPOSTINT('id');
		if (!$productId && is_object($object) && property_exists($object, 'id')) {
			$productId = (int) $object->id;
		}
		if (!$productId && !empty($parameters['id_prod'])) {
			$productId = (int) $parameters['id_prod'];
		}
		if ($productId <= 0) {
			return;
		}

		$product = new Product($this->db);
		if ($product->fetch($productId) <= 0 || !CliChaumeilProductCostCalculator::isSupportedProduct($product)) {
			return;
		}

		$product->fetch_optionals($productId);
		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('product');
		$separatorConfig = $this->buildSeparatorConfig($extrafields, $product);

		$rowsHtml = $this->buildSupplierCostRows($product, $extrafields, $action, GETPOST('attr', 'aZ09'));
		if ($rowsHtml === '') {
			return;
		}

		print '<div id="clichaumeil-cost-breakdown" style="display:none;"><table><tbody>' . $rowsHtml . '</tbody></table></div>';
		print '<script type="application/json" id="clichaumeil-cost-breakdown-data">'
			. json_encode(
				array(
					'separatorId' => (string) $separatorConfig['separatorId'],
					'collapseClass' => (string) $separatorConfig['collapseClass'],
					'cookieName' => (string) $separatorConfig['cookieName'],
					'cookiePath' => (string) $separatorConfig['cookiePath'],
					'expanded' => !empty($separatorConfig['expanded']),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
			)
			. '</script>';
		$this->renderCostBreakdownScriptTag();
	}

	/**
	 * Hide cost breakdown extrafields on product card to avoid duplicate display.
	 *
	 * @return void
	 */
	public function hideCostBreakdownOnProductCard(): void
	{
		print '<script type="application/json" id="clichaumeil-cost-breakdown-hide-data">'
			. json_encode(
				array('fields' => array_values($this->fields)),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
			)
			. '</script>';
		$this->renderCostBreakdownScriptTag();
	}

	/**
	 * Load the external JS that manages cost-breakdown DOM behavior.
	 *
	 * @return void
	 */
	private function renderCostBreakdownScriptTag(): void
	{
		if (self::$costBreakdownScriptLoaded) {
			return;
		}

		self::$costBreakdownScriptLoaded = true;
		print '<script src="' . dol_buildpath('/clichaumeil/js/cost_breakdown.js', 1) . '" defer></script>';
	}

	/**
	 * Build HTML rows for the cost breakdown.
	 *
	 * @param Product     $product     Product object.
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param string      $action      Current action.
	 * @param string      $currentAttr Current field in edition.
	 * @return string
	 */
	private function buildSupplierCostRows(Product $product, ExtraFields $extrafields, string $action, string $currentAttr): string
	{
		global $langs;

		$rows = '';
		$form = new Form($this->db);
		$editMode = ($action === 'edit_extrafields' && in_array($currentAttr, $this->fields, true));
		$token = newToken();
		$baseUrl = dol_buildpath('/product/price_suppliers.php', 1) . '?id=' . ((int) $product->id);
		$breakdown = CliChaumeilProductCostCalculator::getComputedBreakdown($product);
		$collapseClass = $this->buildCollapseClass($product);

		foreach ($this->fields as $field) {
			if (!$this->extrafieldExists($extrafields, $field)) {
				continue;
			}

			$labelKey = (string) $extrafields->attributes['product']['label'][$field];
			$label = $langs->trans($labelKey);
			$labelHtml = $this->buildFieldLabelHtml($form, $extrafields, $field, $label);

			if ($field === self::SEPARATOR_FIELD) {
				$rows .= $this->buildSeparatorRow($extrafields, $product);
				continue;
			}

			$value = $product->array_options[CliChaumeilProductCostCalculator::EXTRA_PREFIX . $field] ?? '';
			$isReadonly = ($field === $this->readonlyField);

			if ($editMode && $currentAttr === $field && !$isReadonly) {
				$rows .= $this->buildEditableRow($product, $extrafields, $field, $labelHtml, $value, $baseUrl, $token, $collapseClass);
				if ($field === CliChaumeilProductCostCalculator::TRANSPORT_PERCENT_FIELD) {
					$rows .= $this->buildVirtualTotalCostsRow($form, $breakdown->totalCosts, $collapseClass);
				}
				continue;
			}

			$outputValue = $this->formatCostBreakdownOutput($extrafields, $product, $field, $value);
			$rows .= '<tr class="field_' . $field . ' clichaumeil-cost-row ' . $collapseClass . '">';
			$rows .= '<td class="titlefield">' . $labelHtml;
			if (!$isReadonly) {
				$rows .= ' ' . $this->buildCostBreakdownEditLink($baseUrl, $field, $token);
			}
			$rows .= '</td>';
			$rows .= '<td>' . $outputValue . '</td></tr>';

			if ($field === CliChaumeilProductCostCalculator::TRANSPORT_PERCENT_FIELD) {
				$rows .= $this->buildVirtualTotalCostsRow($form, $breakdown->totalCosts, $collapseClass);
			}
		}

		return $rows;
	}

	/**
	 * Build the native separator row for the supplier price tab.
	 *
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param Product     $product     Product object.
	 * @return string
	 */
	private function buildSeparatorRow(ExtraFields $extrafields, Product $product): string
	{
		$html = $extrafields->showSeparator(self::SEPARATOR_FIELD, $product, 2, 'card', 'view');

		return (string) preg_replace('/<script\b[^>]*>.*?<\/script>\s*/is', '', $html);
	}

	/**
	 * Build one editable breakdown row.
	 *
	 * @param Product     $product     Product object.
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param string      $field       Field name.
	 * @param string      $labelHtml   Label HTML with optional tooltip.
	 * @param mixed       $value       Raw value.
	 * @param string      $baseUrl     Form action URL.
	 * @param string      $token       CSRF token.
	 * @param string      $collapseClass Native Dolibarr collapse-group class.
	 * @return string
	 */
	private function buildEditableRow(Product $product, ExtraFields $extrafields, string $field, string $labelHtml, $value, string $baseUrl, string $token, string $collapseClass): string
	{
		global $langs;

		$currency = $this->getCurrencyCode();
		$inputField = $extrafields->showInputField($field, $value, '', '', '', '', $product, 'product');
		if (CliChaumeilProductCostCalculator::isPercentField($field)) {
			$inputField .= ' %';
		} else {
			$inputField .= ' ' . $langs->getCurrencySymbol($currency);
		}

		$row = '<tr class="field_' . $field . ' clichaumeil-cost-row ' . $collapseClass . '">';
		$row .= '<td class="titlefield">' . $labelHtml . '</td>';
		$row .= '<td>';
		$row .= '<form method="POST" action="' . dol_escape_htmltag($baseUrl) . '">';
		$row .= '<input type="hidden" name="token" value="' . $token . '">';
		$row .= '<input type="hidden" name="action" value="update_extrafields">';
		$row .= '<input type="hidden" name="attr" value="' . $field . '">';
		$row .= '<input type="hidden" name="clichaumeil_cost_breakdown" value="1">';
		$row .= $inputField;
		$row .= '<div class="center marginstop marginbottomonly">';
		$row .= '<input type="submit" class="button button-save small" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
		$row .= '<input type="submit" class="button button-cancel small" name="cancel" value="' . dol_escape_htmltag($langs->trans('Cancel')) . '">';
		$row .= '</div>';
		$row .= '</form>';
		$row .= '</td></tr>';

		return $row;
	}

	/**
	 * Render formatted value with currency/percent suffixes.
	 *
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param Product     $product     Product object.
	 * @param string      $field       Field name.
	 * @param mixed       $value       Raw value.
	 * @return string
	 */
	private function formatCostBreakdownOutput(ExtraFields $extrafields, Product $product, string $field, $value): string
	{
		global $langs;

		$currency = $this->getCurrencyCode();
		$output = $extrafields->showOutputField($field, $value, '', 'product', $langs, $product);
		if (CliChaumeilProductCostCalculator::isPercentField($field)) {
			if ($output === '' && ($value !== '' && $value !== null)) {
				$output = price((float) $value, 0, $langs, 0, 0, -2, '');
			}

			return ($output === '' ? '' : $output . ' %');
		}

		if ($output === '' && ($value !== '' && $value !== null)) {
			$output = price((float) $value, 0, $langs, 0, 0, -2, $currency);
		}

		if ($output === '') {
			return '';
		}

		return $output . ' ' . $langs->getCurrencySymbol($currency);
	}

	/**
	 * Return edit link with pencil icon.
	 *
	 * @param string $baseUrl Base URL.
	 * @param string $field   Field name.
	 * @param string $token   CSRF token.
	 * @return string
	 */
	private function buildCostBreakdownEditLink(string $baseUrl, string $field, string $token): string
	{
		$url = $baseUrl . '&action=edit_extrafields&attr=' . $field . '&token=' . $token;

		return ' <a class="editfielda" href="' . dol_escape_htmltag($url) . '">' . img_edit() . '</a>';
	}

	/**
	 * Check extrafield availability.
	 *
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param string      $field       Field name.
	 * @return bool
	 */
	private function extrafieldExists(ExtraFields $extrafields, string $field): bool
	{
		return isset($extrafields->attributes['product']['label'][$field]);
	}

	/**
	 * Build the virtual row used to display total costs.
	 *
	 * @param Form   $form          Form helper used to render the tooltip.
	 * @param float  $totalCosts    Computed total costs.
	 * @param string $collapseClass Native Dolibarr collapse-group class.
	 * @return string
	 */
	private function buildVirtualTotalCostsRow(Form $form, float $totalCosts, string $collapseClass): string
	{
		global $langs;

		$currency = $this->getCurrencyCode();
		$value = price($totalCosts, 0, $langs, 0, 0, -2, '');
		$label = $langs->trans('CLICHAUMEIL_TOTAL_COSTS');
		$help = $langs->trans('CLICHAUMEIL_TOTAL_COSTS_HELP');
		$labelHtml = ($help !== 'CLICHAUMEIL_TOTAL_COSTS_HELP' && $help !== '') ? $form->textwithpicto($label, $help) : dol_escape_htmltag($label);

		return '<tr class="field_' . CliChaumeilProductCostCalculator::VIRTUAL_TOTAL_COSTS_FIELD . ' clichaumeil-cost-row ' . $collapseClass . '"><td class="titlefield">'
			. $labelHtml . '</td><td>' . $value . ' ' . $langs->getCurrencySymbol($currency) . '</td></tr>';
	}

	/**
	 * Return the currency code used to render monetary cost fields.
	 *
	 * @return string
	 */
	private function getCurrencyCode(): string
	{
		global $conf;

		return (!empty($conf->currency) ? (string) $conf->currency : 'EUR');
	}

	/**
	 * Build the native collapse-group class used by Dolibarr separators.
	 *
	 * @param Product $product Product object.
	 * @return string
	 */
	private function buildCollapseClass(Product $product): string
	{
		return 'trextrafields_collapse' . self::SEPARATOR_FIELD . (!empty($product->id) ? '_' . ((int) $product->id) : '');
	}

	/**
	 * Build client-side configuration for the native separator behavior.
	 *
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param Product     $product     Product object.
	 * @return array<string,mixed>
	 */
	private function buildSeparatorConfig(ExtraFields $extrafields, Product $product): array
	{
		$params = $extrafields->attributes['product']['param'][self::SEPARATOR_FIELD] ?? array();
		$collapseDisplayValue = 1;
		if (is_array($params) && !empty($params['options']) && is_array($params['options'])) {
			$paramKeys = array_keys($params['options']);
			if (!empty($paramKeys)) {
				$collapseDisplayValue = (int) $paramKeys[0];
			}
		}

		$cookieName = 'DOLUSER_COLLAPSE_product_extrafields_' . self::SEPARATOR_FIELD;
		$cookieValue = $_COOKIE[$cookieName] ?? null;
		$expanded = isset($_COOKIE[$cookieName]) ? !empty($cookieValue) : ($collapseDisplayValue !== 2);

		return array(
			'separatorId' => 'trextrafieldseparator' . self::SEPARATOR_FIELD . (!empty($product->id) ? '_' . ((int) $product->id) : ''),
			'collapseClass' => $this->buildCollapseClass($product),
			'cookieName' => $cookieName,
			'cookiePath' => $this->getCookiePath(),
			'expanded' => $expanded,
		);
	}

	/**
	 * Return the path scope used when persisting UI cookies.
	 *
	 * @return string
	 */
	private function getCookiePath(): string
	{
		$path = (defined('DOL_URL_ROOT') ? (string) DOL_URL_ROOT : '');

		if ($path === '' || $path === '/') {
			return '/';
		}

		return rtrim($path, '/');
	}

	/**
	 * Build a field label with native Dolibarr tooltip rendering when help exists.
	 *
	 * @param Form        $form        Form helper.
	 * @param ExtraFields $extrafields Extrafields handler.
	 * @param string      $field       Field name.
	 * @param string      $label       Translated label.
	 * @return string
	 */
	private function buildFieldLabelHtml(Form $form, ExtraFields $extrafields, string $field, string $label): string
	{
		global $langs;

		$helpKey = (string) ($extrafields->attributes['product']['help'][$field] ?? '');
		if ($helpKey === '') {
			return dol_escape_htmltag($label);
		}

		$help = $langs->trans($helpKey);
		if ($help === '' || $help === $helpKey) {
			return dol_escape_htmltag($label);
		}

		return $form->textwithpicto($label, $help);
	}
}
