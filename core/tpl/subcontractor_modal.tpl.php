<?php
/**
 * Subcontractor selection modal template.
 *
 * Expects:
 * - $modalId
 * - $ajaxUrl
 * - $token
 * - $object
 * - $supplierProposals
 * - $langs
 */
?>
<div id="<?php echo dol_escape_htmltag($modalId); ?>" class="clichaumeil-subcontractor-modal" data-ajax-url="<?php echo dol_escape_htmltag($ajaxUrl); ?>" data-token="<?php echo dol_escape_htmltag($token); ?>" data-parent-type="<?php echo dol_escape_htmltag($object->element); ?>" data-parent-id="<?php echo (int) $object->id; ?>" data-error-message="<?php echo dol_escape_htmltag($langs->trans('CliChaumeilSelectError')); ?>">
	<div class="clichaumeil-subcontractor-modal__body">
		<p class="clichaumeil-subcontractor-modal__intro"><?php echo $langs->trans('CliChaumeilSubcontractorModalIntro'); ?></p>
		<div class="clichaumeil-subcontractor-modal__message" aria-live="polite"></div>
		<div class="scrolling-table-container">
			<table class="noborder centpercent">
				<thead>
				<tr class="liste_titre">
					<th><?php echo $langs->trans('Supplier'); ?></th>
					<th><?php echo $langs->trans('Ref'); ?></th>
					<th class="right"><?php echo $langs->trans('AmountHT'); ?></th>
					<th class="center"><?php echo $langs->trans('Status'); ?></th>
					<th class="center"></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($supplierProposals as $supplierProposal) : ?>
					<?php
					if (empty($supplierProposal->id)) {
						continue;
					}
					$currentStatus = isset($supplierProposal->status) ? (int) $supplierProposal->status : (int) $supplierProposal->statut;
					$isSelected = ($currentStatus === SupplierProposal::STATUS_SIGNED);
					$statusLabel = method_exists($supplierProposal, 'LibStatut') ? dol_escape_htmltag($supplierProposal->LibStatut($currentStatus, 0)) : '';
					$thirdpartyUrl = '';
					if ($supplierProposal->thirdparty) {
						$thirdpartyHref = dol_buildpath('/societe/card.php', 1).'?socid='.(int) $supplierProposal->thirdparty->id;
						$thirdpartyLabel = dol_escape_htmltag($supplierProposal->thirdparty->name);
						$thirdpartyPicto = img_object('', 'company');
						$thirdpartyUrl = '<a tabindex="-1" href="'.$thirdpartyHref.'">'.$thirdpartyPicto.' '.$thirdpartyLabel.'</a>';
					}
					$proposalHref = dol_buildpath('/supplier_proposal/card.php', 1).'?id='.(int) $supplierProposal->id;
					$proposalLabel = dol_escape_htmltag($supplierProposal->ref);
					$proposalPicto = img_object('', $supplierProposal->picto ?: 'supplier_proposal');
					$proposalUrl = '<a tabindex="-1" href="'.$proposalHref.'">'.$proposalPicto.' '.$proposalLabel.'</a>';
					?>
					<tr data-supplier-proposal-id="<?php echo (int) $supplierProposal->id; ?>" class="clichaumeil-subcontractor-row<?php echo $isSelected ? ' is-selected' : ''; ?>">
						<td><?php echo $thirdpartyUrl; ?></td>
						<td><?php echo $proposalUrl; ?></td>
						<td class="right"><?php echo price($supplierProposal->total_ht); ?></td>
						<td class="center nowraponall"><?php echo $statusLabel; ?></td>
						<td class="center">
							<button
								type="button"
								class="button smallpaddingimp clichaumeil-select-subcontractor"
								title="<?php echo dol_escape_htmltag($langs->trans('CliChaumeilSelectSubcontractor')); ?>"
								data-supplier-proposal-id="<?php echo (int) $supplierProposal->id; ?>"
							><?php echo dol_escape_htmltag($langs->trans('Validate')); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
