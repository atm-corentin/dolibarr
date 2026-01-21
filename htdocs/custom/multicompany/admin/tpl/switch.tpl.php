<?php
/* Copyright (C) 2017-2022	Regis Houssin	<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN MULTICOMPANY AJAX TEMPLATE -->
<div id="dialog-delete" title="<?php echo $langs->trans('DeleteAnEntity'); ?>" class="hideobject">
	<p><?php echo img_warning().' '.$langs->trans('ConfirmDeleteEntity'); ?></p>
</div>
<script type="text/javascript">
$(document).ready(function() {
	$("#multicompany_entity_list").on('click', '.multicompany-button-active-on, .multicompany-button-visible-on', function() {
		var button = $(this);
		var type = button.attr('id').match(/^[a-z]+/g);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$.post( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
			'action': 'setStatusDisable',
			'type': type[0],
			'id': id,
			'token': '<?php echo currentToken(); ?>'
			},
			function (result) {
				if (result == 1) {
					$('#multicompany_entity_list').dataTable().fnDraw();
				} else {
					$.jnotify("<?php echo $langs->trans("ErrorEntityStatusDisable"); ?>", "error", true);
				}
			}
		);
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-active-off, .multicompany-button-visible-off', function() {
		var button = $(this);
		var type = button.attr('id').match(/^[a-z]+/g);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$.post( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
			'action': 'setStatusEnable',
			'type': type[0],
			'id': id,
			'token': '<?php echo currentToken(); ?>'
			},
			function (result) {
				if (result == 1) {
					$('#multicompany_entity_list').dataTable().fnDraw();
				} else {
					$.jnotify("<?php echo $langs->trans("ErrorEntityStatusEnable"); ?>", "error", true);
				}
			}
		);
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-delete', function() {
		var button = $(this);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$('#dialog-delete').dialog({
			resizable: false,
			height: 170,
			width: 400,
			modal: true,
			open: function() {
				$('.ui-dialog-buttonset > button:last').focus();
			},
			buttons: {
				'<?php echo $langs->trans('Delete'); ?>': function() {
					$(this).dialog('close');
					$.pleaseBePatient("<?php echo $langs->trans('PleaseBePatient'); ?>");
					$.post( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
						"action" : "deleteEntity",
						"id" : id,
						"token": "<?php echo currentToken(); ?>"
						},
						function (result) {
							if (result == 1) {
								$('#multicompany_entity_list').dataTable().fnDraw();
								$.jnotify("<?php echo $langs->trans("ConfirmedEntityDeleted"); ?>", "ok");
							} else {
								$.jnotify("<?php echo $langs->trans("ErrorEntityDeleted"); ?>", "error", true);
							}
							$.unblockUI();
						}
					);
				},
				'<?php echo $langs->trans('Cancel'); ?>': function() {
					$(this).dialog('close');
				}
			}
		});
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-setup', function() {
		var button = $(this);
		var id = parseInt(button.attr('id').match(/([0-9]+)$/g));
		window.location.href = "<?php echo $_SERVER["PHP_SELF"].'?action=edit&id=' ?>" + id;
	});
});
</script>
<!-- END MULTICOMPANY AJAX TEMPLATE -->