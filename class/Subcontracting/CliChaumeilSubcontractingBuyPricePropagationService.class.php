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
require_once __DIR__.'/CliChaumeilSupplierProposalLineLinkService.class.php';
require_once __DIR__.'/CliChaumeilSubcontractingMinimumRateResolver.class.php';

/**
 * Propagate supplier proposal buy prices to the parent client document lines.
 *
 * Behavior depends on the parent status:
 *  - Draft: full updateline() call to refresh subprice via discountrules minimum rate.
 *  - Validated/Signed/etc: direct SQL UPDATE limited to buy_price_ht. Margin is recomputed by Dolibarr at read time.
 */
class CliChaumeilSubcontractingBuyPricePropagationService
{
	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Line link service.
	 *
	 * @var CliChaumeilSupplierProposalLineLinkService
	 */
	private CliChaumeilSupplierProposalLineLinkService $lineLinkService;

	/**
	 * Discountrules minimum rate resolver.
	 *
	 * @var CliChaumeilSubcontractingMinimumRateResolver
	 */
	private CliChaumeilSubcontractingMinimumRateResolver $minimumRateResolver;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db                  = $db;
		$this->lineLinkService     = new CliChaumeilSupplierProposalLineLinkService($db);
		$this->minimumRateResolver = new CliChaumeilSubcontractingMinimumRateResolver($db);
	}

	/**
	 * Propagate the supplier buy prices to the parent client lines.
	 *
	 * @param CommonObject     $parent           Parent client document (Propal or Commande).
	 * @param SupplierProposal $supplierProposal Selected supplier proposal.
	 * @param User             $user             Current user.
	 * @return array{updated:int,missing:array<int,int>}
	 *
	 * @throws RuntimeException When the parent type is not supported or a supplier line cannot be matched unambiguously.
	 */
	public function propagate(CommonObject $parent, SupplierProposal $supplierProposal, User $user): array
	{
		if (!in_array($parent->element, array('propal', 'commande'), true)) {
			throw new RuntimeException('Unsupported parent element: '.$parent->element);
		}

		$report = array(
			'updated' => 0,
			'missing' => array(),
		);

		$supplierLines = $this->lineLinkService->loadSupplierLines($supplierProposal);
		if (empty($supplierLines)) {
			return $report;
		}

		$parentLines = $this->lineLinkService->loadParentLines($parent);
		if (empty($parentLines)) {
			foreach ($supplierLines as $supplierLine) {
				$report['missing'][] = (int) $supplierLine->id;
			}
			return $report;
		}

		// backfillLinks() mutates the supplier lines in memory (insertExtraFields keeps array_options
		// in sync on the same objects), so reusing the existing $supplierLines reflects the persistent
		// links without a second SQL roundtrip.
		$this->lineLinkService->backfillLinks($supplierProposal, $parent, $user);

		$isDraft = (int) ($parent->status ?? $parent->statut ?? 0) === 0;

		foreach ($supplierLines as $supplierLine) {
			$supplierLineId = (int) $supplierLine->id;
			$match          = $this->lineLinkService->findParentLineMatch($supplierLine, $parentLines);

			if ($match['ambiguous'] === true) {
				throw new RuntimeException('Ambiguous supplier line #'.$supplierLineId.' on '.$parent->element.' #'.((int) $parent->id).' — multiple candidate parent lines match.');
			}
			if ($match['line'] === null) {
				$report['missing'][] = $supplierLineId;
				continue;
			}

			$targetLine = $match['line'];
			$buyPrice   = $this->readSupplierBuyPrice($supplierLine);

			if ($isDraft) {
				// Resolve per line — product override is line-scoped, so a cached resolution would leak the first
				// line's product rate onto subsequent lines.
				$resolution = $this->minimumRateResolver->resolve($parent, $targetLine);
				$this->updateDraftLine($parent, $targetLine, $buyPrice, $resolution, $user);
			} else {
				$this->updateValidatedLine($parent->element, (int) $targetLine->id, $buyPrice);
			}
			$report['updated']++;
		}

		return $report;
	}

	/**
	 * Read the supplier buy price from a supplier proposal line.
	 *
	 * On a supplier proposal line the supplier-quoted unit price is stored in `subprice`
	 * (the form input "Prix unitaire HT" the supplier sets). It is what becomes our
	 * purchase cost. The `pa_ht` / `buy_price_ht` columns carry a separate, often empty,
	 * reference cost and are used only as a defensive fallback. Both 0 and decimals are valid.
	 *
	 * @param object $supplierLine Supplier proposal line.
	 * @return float Buy price (0 is a valid value to propagate).
	 */
	private function readSupplierBuyPrice(object $supplierLine): float
	{
		if (isset($supplierLine->subprice) && $supplierLine->subprice !== '') {
			return (float) $supplierLine->subprice;
		}
		if (isset($supplierLine->pa_ht) && $supplierLine->pa_ht !== '') {
			return (float) $supplierLine->pa_ht;
		}
		if (isset($supplierLine->buy_price_ht) && $supplierLine->buy_price_ht !== '') {
			return (float) $supplierLine->buy_price_ht;
		}
		return 0.0;
	}

	/**
	 * Update a draft parent line: recompute subprice via discountrules when enabled.
	 *
	 * @param CommonObject                                                              $parent     Parent document.
	 * @param object                                                                    $targetLine Parent line.
	 * @param float                                                                     $buyPrice   Buy price to propagate.
	 * @param array{enabled:bool,rate:float,type:string,source:string}                  $resolution Discountrules resolution.
	 * @param User                                                                      $user       Current user.
	 * @return void
	 *
	 * @throws RuntimeException When updateline fails.
	 */
	private function updateDraftLine(CommonObject $parent, object $targetLine, float $buyPrice, array $resolution, User $user): void
	{
		$newSubprice = (float) ($targetLine->subprice ?? 0);
		if ($resolution['enabled'] === true && $resolution['type'] !== '') {
			$newSubprice = $this->minimumRateResolver->computeSellingPrice($buyPrice, $resolution['rate'], $resolution['type']);
		}

		if ($parent->element === 'propal') {
			$result = $parent->updateline(
				(int) $targetLine->id,
				$newSubprice,
				(float) ($targetLine->qty ?? 0),
				(float) ($targetLine->remise_percent ?? 0),
				(float) ($targetLine->tva_tx ?? 0),
				(float) ($targetLine->localtax1_tx ?? 0),
				(float) ($targetLine->localtax2_tx ?? 0),
				(string) ($targetLine->desc ?? $targetLine->description ?? ''),
				'HT',
				(int) ($targetLine->info_bits ?? 0),
				(int) ($targetLine->special_code ?? 0),
				(int) ($targetLine->fk_parent_line ?? 0),
				0,
				(int) ($targetLine->fk_fournprice ?? 0),
				$buyPrice,
				(string) ($targetLine->label ?? ''),
				(int) ($targetLine->product_type ?? 0),
				$targetLine->date_start ?? '',
				$targetLine->date_end ?? '',
				is_array($targetLine->array_options ?? null) ? $targetLine->array_options : array(),
				$targetLine->fk_unit ?? null,
				(float) ($targetLine->multicurrency_subprice ?? 0),
				0,
				(int) ($targetLine->rang ?? 0)
			);
		} else {
			$result = $parent->updateline(
				(int) $targetLine->id,
				(string) ($targetLine->desc ?? $targetLine->description ?? ''),
				$newSubprice,
				(float) ($targetLine->qty ?? 0),
				(float) ($targetLine->remise_percent ?? 0),
				(float) ($targetLine->tva_tx ?? 0),
				(float) ($targetLine->localtax1_tx ?? 0),
				(float) ($targetLine->localtax2_tx ?? 0),
				'HT',
				(int) ($targetLine->info_bits ?? 0),
				$targetLine->date_start ?? '',
				$targetLine->date_end ?? '',
				(int) ($targetLine->product_type ?? 0),
				(int) ($targetLine->fk_parent_line ?? 0),
				0,
				(int) ($targetLine->fk_fournprice ?? 0),
				$buyPrice,
				(string) ($targetLine->label ?? ''),
				(int) ($targetLine->special_code ?? 0),
				is_array($targetLine->array_options ?? null) ? $targetLine->array_options : array(),
				$targetLine->fk_unit ?? null,
				(float) ($targetLine->multicurrency_subprice ?? 0),
				0,
				(string) ($targetLine->ref_ext ?? ''),
				(int) ($targetLine->rang ?? 0)
			);
		}

		if ($result <= 0) {
			$errorMessage = !empty($parent->error) ? (string) $parent->error : $this->db->lasterror();
			throw new RuntimeException('updateline failed on '.$parent->element.'det #'.((int) $targetLine->id).': '.$errorMessage);
		}
	}

	/**
	 * Update a validated parent line: direct SQL UPDATE on buy_price_ht only.
	 *
	 * @param string $parentElement Parent element type ('propal' or 'commande').
	 * @param int    $parentLineId  Parent line rowid.
	 * @param float  $buyPrice      Buy price to write.
	 * @return void
	 *
	 * @throws RuntimeException When the SQL update fails.
	 */
	private function updateValidatedLine(string $parentElement, int $parentLineId, float $buyPrice): void
	{
		$table = $parentElement === 'propal' ? 'propaldet' : 'commandedet';
		$sql   = 'UPDATE '.$this->db->prefix().$table;
		$sql  .= ' SET buy_price_ht = '.(float) price2num($buyPrice, 'MT');
		$sql  .= ' WHERE rowid = '.((int) $parentLineId);

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException('SQL update failed on '.$table.' #'.$parentLineId.': '.$this->db->lasterror());
		}
		$this->db->free($resql);
	}
}
