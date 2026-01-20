<?php
/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once __DIR__.'/SupplierProposalFactory.class.php';
require_once __DIR__.'/SupplierProposalService.class.php';

/**
 * View dedicated to the supplier proposal list (external access)
 */
class SupplierProposalListView
{
	/** @var Translate */
	private $langs;

	/** @var Conf */
	private $conf;

	/** @var DoliDB */
	private $db;

	/** @var Context */
	private $context;

	/** @var SupplierProposalFactory */
	private $factory;

	/** @var SupplierProposalService */
	private $service;

	/**
	 * @param Translate $langs
	 * @param Conf      $conf
	 * @param DoliDB    $db
	 * @param Context   $context
	 */
	public function __construct(Translate $langs, Conf $conf, DoliDB $db, Context $context)
	{
		$this->langs = $langs;
		$this->conf = $conf;
		$this->db = $db;
		$this->context = $context;
		$this->factory = new SupplierProposalFactory($db);
		$this->service = new SupplierProposalService($db, $conf, $langs);
	}

	/**
	 * Render the supplier proposal table
	 *
	 * @param array $tableItems Raw rows fetched from database
	 * @param array $extraFields List of configured extra fields
	 * @param string $tableId Table DOM id
	 * @return string
	 */
	public function renderTable(array $tableItems, array $extraFields = array(), string $tableId = 'supplier-propal-list') : string
	{
		if (empty($tableItems)) {
			return '<div class="info clearboth text-center">' . $this->langs->trans('EACCESS_Nothing') . '</div>';
		}

		$extraFieldManager = null;
		if (!empty($extraFields)) {
			$extraFieldManager = new ExtraFields($this->db);
			$extraFieldManager->fetch_name_optionals_label('supplier_proposal');
		}

		$out = '<table id="' . dol_escape_htmltag($tableId) . '" class="table table-striped">';
		$out .= $this->renderTableHeader($extraFields, $extraFieldManager);
		$out .= '<tbody>';
		foreach ($tableItems as $item) {
			// Fetch extrafields data from service if needed
			$arrayOptions = array();
			if (!empty($extraFields)) {
				$arrayOptions = $this->service->fetchExtrafields($item->rowid, $extraFields);
			}
			// Create object using factory with pre-fetched data
			$object = $this->factory->createFromDatabaseRow($item, $arrayOptions);
			$out .= $this->renderTableRow($object, $extraFields, $extraFieldManager);
		}
		$out .= '</tbody>';
		$out .= '</table>';
		$out .= $this->renderDataTableInitScript($tableId);

		return $out;
	}

	/**
	 * Build table header
	 *
	 * @param array $extraFields
	 * @param ExtraFields|null $manager
	 * @return string
	 */
	private function renderTableHeader(array $extraFields, ?ExtraFields $manager) : string
	{
		$out = '<thead><tr>';
		$out .= '<th class="text-center">' . $this->langs->trans('Ref') . '</th>';
		$out .= '<th class="text-center">' . $this->langs->trans('Label') . '</th>';
		$out .= '<th class="text-center">' . $this->langs->trans('CLICHAUMEIL_DATEDELIVERYPLANNED') . '</th>';

		if (!empty($extraFields)) {
			foreach ($extraFields as $field) {
				if (property_exists('SupplierProposal', $field)) {
					$out .= '<th class="text-center">' . $this->langs->trans($field) . '</th>';
				} elseif (strpos($field, 'EXTRAFIELD_') === 0) {
					$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
					$label = $extrafieldName;
					if ($manager && !empty($manager->attributes['supplier_proposal']['label'][$extrafieldName])) {
						$label = $manager->attributes['supplier_proposal']['label'][$extrafieldName];
					}
					$out .= '<th class="text-center">' . $label . '</th>';
				}
			}
		}

		$out .= '<th class="text-center">' . $this->langs->trans('TotalHT') . '</th>';
		$out .= '<th class="text-center">' . $this->langs->trans('TotalVAT') . '</th>';
		$out .= '<th class="text-center">' . $this->langs->trans('Status') . '</th>';
		$out .= '</tr></thead>';

		return $out;
	}

	/**
	 * Build a row for a supplier proposal
	 *
	 * @param SupplierProposal $object
	 * @param array $extraFields
	 * @param ExtraFields|null $manager
	 * @return string
	 */
	private function renderTableRow(SupplierProposal $object, array $extraFields, ?ExtraFields $manager) : string
	{
		$out = '<tr>';

		$url = $this->context->getControllerUrl('supplier_proposal_card', '&id=' . $object->id);
		$ref = dol_escape_htmltag($object->ref);
		$out .= '<td data-search="' . $ref . '" data-order="' . $ref . '"><a href="' . $url . '">' . $ref . '</a></td>';

		$label = dol_escape_htmltag($object->ref_supplier);
		$out .= '<td data-search="' . $label . '" data-order="' . $label . '">' . $label . '</td>';

		$dateFormatted = dol_print_date($object->delivery_date);
		$out .= '<td data-search="' . $dateFormatted . '" data-order="' . intval($object->delivery_date) . '">' . $dateFormatted . '</td>';

		if (!empty($extraFields)) {
			foreach ($extraFields as $field) {
				if (property_exists('SupplierProposal', $field)) {
					$value = $object->{$field};
					$clean = strip_tags($value);
					$out .= '<td data-search="' . $clean . '" data-order="' . $clean . '">' . $value . '</td>';
					continue;
				}

				if (strpos($field, 'EXTRAFIELD_') === 0) {
					$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
					$extrafieldValue = $object->array_options['options_' . $extrafieldName] ?? '';

					if ($manager) {
						$output = $manager->showOutputField($extrafieldName, $extrafieldValue, '', 'supplier_proposal');
						$clean = strip_tags($output);
						$out .= '<td data-search="' . $clean . '" data-order="' . $clean . '">' . $output . '</td>';
					} else {
						$clean = strip_tags($extrafieldValue);
						$out .= '<td data-search="' . $clean . '" data-order="' . $clean . '">' . $extrafieldValue . '</td>';
					}
				}
			}
		}

		$out .= '<td data-search="' . $object->total_ht . '" data-order="' . $object->total_ht . '">' . price($object->total_ht) . '</td>';
		$out .= '<td data-search="' . $object->total_tva . '" data-order="' . $object->total_tva . '">' . price($object->total_tva) . '</td>';
		$out .= '<td class="text-center">' . $object->getLibStatut(0) . '</td>';

		$out .= '</tr>';

		return $out;
	}

	/**
	 * Render the DataTables initialization script
	 *
	 * @param string $tableId
	 * @return string
	 */
	private function renderDataTableInitScript(string $tableId) : string
	{
		$languageUrl = $this->context->getControllerUrl() . 'vendor/data-tables/french.json';
		$out = '<script type="text/javascript">';
		$out .= 'initSupplierProposalDataTable("' . dol_escape_js($tableId) . '", "' . dol_escape_js($languageUrl) . '", 2);';
		$out .= '</script>';

		return $out;
	}
}
