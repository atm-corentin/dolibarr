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
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once __DIR__.'/../SupplierProposalService.class.php';

/**
 * Resolve and backfill the persistent link between supplier proposal lines and their client parent line.
 *
 * The link is stored in two extrafields on supplier_proposaldet:
 *  - clichaumeil_source_element : 'propal' or 'commande'.
 *  - clichaumeil_source_line_id : parent line rowid.
 */
class CliChaumeilSupplierProposalLineLinkService
{
	private const EXTRAFIELD_ELEMENT = 'clichaumeil_source_element';
	private const EXTRAFIELD_LINE_ID = 'clichaumeil_source_line_id';
	private const OPTIONS_PREFIX     = 'options_';

	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Resolve the parent commercial document of a supplier proposal.
	 *
	 * Delegates to SupplierProposalService::resolveParentDocument which searches
	 * llx_element_element in both directions, and keeps only propal/commande matches.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal.
	 * @return CommonObject|null Propal or Commande when found, null otherwise.
	 */
	public function resolveParent(SupplierProposal $supplierProposal): ?CommonObject
	{
		$parent = SupplierProposalService::resolveParentDocument($supplierProposal);
		if ($parent === null) {
			return null;
		}
		if (!in_array($parent->element, array('propal', 'commande'), true)) {
			return null;
		}
		return $parent;
	}

	/**
	 * Backfill the persistent line link for every supplier proposal line.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal.
	 * @param CommonObject     $parent           Client parent document.
	 * @param User             $user             Current user.
	 * @return array{linked:int,already_linked:int,ambiguous:array<int,int>,missing:array<int,int>}
	 */
	public function backfillLinks(SupplierProposal $supplierProposal, CommonObject $parent, User $user): array
	{
		$diagnostics = array(
			'linked'         => 0,
			'already_linked' => 0,
			'ambiguous'      => array(),
			'missing'        => array(),
		);

		$supplierLines = $this->loadSupplierLines($supplierProposal);
		if (empty($supplierLines)) {
			return $diagnostics;
		}

		$parentLines = $this->loadParentLines($parent);
		if (empty($parentLines)) {
			foreach ($supplierLines as $supplierLine) {
				$diagnostics['missing'][] = (int) $supplierLine->id;
			}
			return $diagnostics;
		}

		foreach ($supplierLines as $supplierLine) {
			$supplierLineId = (int) $supplierLine->id;

			if ($this->hasPersistentLink($supplierLine)) {
				$diagnostics['already_linked']++;
				continue;
			}

			$matches = $this->findMatchingParentLines($supplierLine, $parentLines);
			if (count($matches) === 0) {
				$diagnostics['missing'][] = $supplierLineId;
				continue;
			}
			if (count($matches) > 1) {
				$diagnostics['ambiguous'][] = $supplierLineId;
				continue;
			}

			$targetLine = $matches[0];
			if (!$this->writeLink($supplierLine, $parent->element, (int) $targetLine->id, $user)) {
				$diagnostics['missing'][] = $supplierLineId;
				continue;
			}
			$diagnostics['linked']++;
		}

		return $diagnostics;
	}

	/**
	 * Find the parent line linked to a given supplier proposal line, classifying the result.
	 *
	 * Reads the persistent extrafield first; falls back to a deterministic match by
	 * fk_product + rang + special_code when the link is absent or points to a different
	 * element type than the current parent (e.g. a propal link on a commande parent).
	 *
	 * @param object             $supplierLine  Supplier proposal line.
	 * @param array<int,object>  $parentLines   Parent document lines.
	 * @param string             $parentElement Parent element type ('propal' or 'commande').
	 * @return array{line:?object,ambiguous:bool} Matched line (null on miss) and ambiguity flag.
	 */
	public function findParentLineMatch(object $supplierLine, array $parentLines, string $parentElement): array
	{
		$linkedElement = $this->getPersistentElement($supplierLine);
		$linkedLineId  = $this->getPersistentLineId($supplierLine);

		if ($linkedLineId > 0 && $linkedElement === $parentElement) {
			foreach ($parentLines as $parentLine) {
				if ((int) $parentLine->id === $linkedLineId) {
					return array('line' => $parentLine, 'ambiguous' => false);
				}
			}
			return array('line' => null, 'ambiguous' => false);
		}

		$matches = $this->findMatchingParentLines($supplierLine, $parentLines);
		if (count($matches) === 1) {
			return array('line' => $matches[0], 'ambiguous' => false);
		}
		return array('line' => null, 'ambiguous' => count($matches) > 1);
	}

	/**
	 * Load every line of a supplier proposal with extrafields preloaded.
	 *
	 * @param SupplierProposal $supplierProposal Supplier proposal.
	 * @return array<int,SupplierProposalLine>
	 */
	public function loadSupplierLines(SupplierProposal $supplierProposal): array
	{
		if (empty($supplierProposal->lines) || !is_array($supplierProposal->lines)) {
			$supplierProposal->getLinesArray();
		}
		if (empty($supplierProposal->lines)) {
			return array();
		}

		foreach ($supplierProposal->lines as $line) {
			if (method_exists($line, 'fetch_optionals')) {
				$line->fetch_optionals();
			}
		}

		return array_values($supplierProposal->lines);
	}

	/**
	 * Load every line of the parent commercial document.
	 *
	 * If $parent->lines is already populated it is returned as-is — the caller is responsible
	 * for passing a freshly fetched parent when an up-to-date snapshot is required.
	 *
	 * @param CommonObject $parent Parent document (Propal or Commande).
	 * @return array<int,object>
	 */
	public function loadParentLines(CommonObject $parent): array
	{
		if (empty($parent->lines) || !is_array($parent->lines)) {
			$result = method_exists($parent, 'fetch_lines') ? $parent->fetch_lines() : -1;
			if ($result < 0) {
				dol_syslog(__METHOD__.' fetch_lines failed on '.$parent->element.' #'.((int) $parent->id), LOG_ERR);
				return array();
			}
		}
		return is_array($parent->lines) ? array_values($parent->lines) : array();
	}

	/**
	 * Match a supplier line against the parent line list using fk_product + rang + special_code.
	 *
	 * @param object             $supplierLine Supplier proposal line.
	 * @param array<int,object>  $parentLines  Parent lines.
	 * @return array<int,object> All parent lines matching the supplier line.
	 */
	private function findMatchingParentLines(object $supplierLine, array $parentLines): array
	{
		$supplierProduct     = (int) ($supplierLine->fk_product ?? 0);
		$supplierRang        = (int) ($supplierLine->rang ?? 0);
		$supplierSpecialCode = (int) ($supplierLine->special_code ?? 0);

		$matches = array();
		foreach ($parentLines as $parentLine) {
			if ((int) ($parentLine->fk_product ?? 0) !== $supplierProduct) {
				continue;
			}
			if ((int) ($parentLine->rang ?? 0) !== $supplierRang) {
				continue;
			}
			if ((int) ($parentLine->special_code ?? 0) !== $supplierSpecialCode) {
				continue;
			}
			$matches[] = $parentLine;
		}
		return $matches;
	}

	/**
	 * Tell whether the supplier proposal line already holds a persistent link.
	 *
	 * @param object $supplierLine Supplier proposal line.
	 * @return bool
	 */
	private function hasPersistentLink(object $supplierLine): bool
	{
		return $this->getPersistentLineId($supplierLine) > 0;
	}

	/**
	 * Read the persistent parent line id stored in extrafields.
	 *
	 * @param object $supplierLine Supplier proposal line.
	 * @return int Line id, 0 when not set.
	 */
	private function getPersistentLineId(object $supplierLine): int
	{
		if (!isset($supplierLine->array_options) || !is_array($supplierLine->array_options)) {
			return 0;
		}
		$key = self::OPTIONS_PREFIX.self::EXTRAFIELD_LINE_ID;
		if (!isset($supplierLine->array_options[$key]) || $supplierLine->array_options[$key] === '') {
			return 0;
		}
		return (int) $supplierLine->array_options[$key];
	}

	/**
	 * Read the persistent parent element type stored in extrafields.
	 *
	 * @param object $supplierLine Supplier proposal line.
	 * @return string Element type ('propal', 'commande') or empty string when not set.
	 */
	private function getPersistentElement(object $supplierLine): string
	{
		if (!isset($supplierLine->array_options) || !is_array($supplierLine->array_options)) {
			return '';
		}
		$key = self::OPTIONS_PREFIX.self::EXTRAFIELD_ELEMENT;
		if (!isset($supplierLine->array_options[$key]) || $supplierLine->array_options[$key] === '') {
			return '';
		}
		return (string) $supplierLine->array_options[$key];
	}

	/**
	 * Persist the link extrafields on one supplier proposal line.
	 *
	 * @param SupplierProposalLine $supplierLine    Supplier proposal line.
	 * @param string               $parentElement   Parent element type.
	 * @param int                  $parentLineId    Parent line id.
	 * @param User                 $user            Current user.
	 * @return bool True on success, false on failure (failure already logged).
	 */
	private function writeLink(SupplierProposalLine $supplierLine, string $parentElement, int $parentLineId, User $user): bool
	{
		if (!is_array($supplierLine->array_options)) {
			$supplierLine->array_options = array();
		}
		$supplierLine->array_options[self::OPTIONS_PREFIX.self::EXTRAFIELD_ELEMENT] = $parentElement;
		$supplierLine->array_options[self::OPTIONS_PREFIX.self::EXTRAFIELD_LINE_ID] = $parentLineId;

		if (!method_exists($supplierLine, 'insertExtraFields')) {
			dol_syslog(__METHOD__.' SupplierProposalLine #'.((int) $supplierLine->id).' has no insertExtraFields method', LOG_ERR);
			return false;
		}

		$result = $supplierLine->insertExtraFields('', $user);
		if ($result <= 0) {
			$error = !empty($supplierLine->error) ? (string) $supplierLine->error : $this->db->lasterror();
			dol_syslog(__METHOD__.' insertExtraFields failed on supplier_proposaldet #'.((int) $supplierLine->id).' — '.$error, LOG_ERR);
			return false;
		}
		return true;
	}
}
