<?php
/**
 * Subcontractor picker renderer.
 *
 * Expects:
 * - $buttonId
 * - $modalId
 * - $ajaxUrl
 * - $token
 * - $object
 * - $supplierProposals
 * - $langs
 */

?>
<a class="butAction clichaumeil-open-subcontractor"
	href="#"
	id="<?php echo dol_escape_htmltag($buttonId); ?>"
	data-modal-target="<?php echo dol_escape_htmltag($modalId); ?>">
	<?php echo $langs->trans('CliChaumeilChooseSubcontractor'); ?>
</a>
<?php
$modalTemplate = __DIR__ . '/subcontractor_modal.tpl.php';
if (file_exists($modalTemplate)) {
	include $modalTemplate;
}
