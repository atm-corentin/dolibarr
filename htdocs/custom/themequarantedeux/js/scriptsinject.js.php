<?php
/* Copyright (C) 2022 SuperAdmin
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
 *
 * Library javascript to enable Browser notifications
 */


/**
 * \file    themequarantedeux/js/scriptsinject.js.php
 * \ingroup themequarantedeux
 * \brief   JavaScript file for module ThemeQuaranteDeux.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');
global $langs, $db;

$langs->load('themequarantedeux@themequarantedeux');
?>

/* Javascript library of module ScriptsInject */
/**
 * Launch the modal to add a script
 */
function launchAddurlModal(e) {
	e.click(function () {
		Swal.fire({
			title: '<?php echo $langs->trans('PSIAddUrl'); ?>',
			showCancelButton: true,
			confirmButtonText: '<?php echo $langs->trans('Add'); ?>',
			cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>',
			width: '70%',
			html: '<input id="addurl" name="add-url" style="width: 500px;">',
			}).then((result) => {
			if (result.isConfirmed) {
				// Do the ajax call and reload after
				$.ajax({
					url: '<?php echo dol_buildpath('/themequarantedeux/ajax/ajax_add_url.php', 1)?>',
					type: 'POST',
					data: {
						token: '<?php echo newToken();?>',
						action: 'addurl',
						url: $('#addurl').val()
					},
					success: function (res) {
						res = JSON.parse(res);
						if (res.success) {
							document.location.reload();
						} else {
							Swal.fire({
								icon: 'error',
								text: res.message
							})
						}
					}
				})
			}
		})
	});
}

/**
 * Launch the modal to delete a script
 */
function launchDeleteurlModal() {
	let lineId = document.querySelectorAll('#scriptsinjecturls .deleteurl');
	lineId.forEach(elem => {
		elem.addEventListener('click', function (r) {
			Swal.fire({
				title: '<?php echo $langs->trans('PSIDeleteUrl'); ?>',
				showCancelButton: true,
				confirmButtonText: '<?php echo $langs->trans('Delete'); ?>',
				cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>',
			}).then((result) => {
				if (result.isConfirmed) {
					// Do the ajax call and reload after
					$.ajax({
						url: '<?php echo dol_buildpath('/themequarantedeux/ajax/ajax_delete_url.php', 1)?>',
						type: 'POST',
						data: {
							token: '<?php echo newToken();?>',
							action: 'deleteurl',
							rowid: r.target.dataset.key
						},
						success: function (res) {
							res = JSON.parse(res);

							if (res.success) {
								document.location.reload();
							} else {
								Swal.fire({
									icon: 'error',
									text: res.message
								})
							}
						}
					})
				}
			})
		});
	});
}

/**
 * Launch the modal to edit a script
 */
function launchEditurlModal() {
	let lineId = document.querySelectorAll('#scriptsinjecturls .editurl');
	lineId.forEach(elem => {
		elem.addEventListener('click', function (r) {
			Swal.fire({
				title: '<?php echo $langs->trans('PSIEditUrl'); ?>',
				showCancelButton: true,
				confirmButtonText: '<?php echo $langs->trans('Update'); ?>',
				cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>',
				html: '<input value='+r.target.dataset.url+' id="editurl" name="edit-url" style="width: 60%;"></br></br>'+
					  '<div><h3 class="swal2-title">Sélectionner le type</h3></div>'+
					  '<select name="type" id="type-select">' +
					(r.target.dataset.type === 'HEADER' ? '<option selected value="HEADER">En tête</option>'+'<option value="FOOTER" id="footer">Bas de page</option>' : '<option value="HEADER" id="header">En tête</option>' + '<option value="FOOTER" selected id="footer">Bas de page</option>') +
					  '</select>',
				width: '70%'
			}).then((result) => {
				if (result.isConfirmed) {
					// Do the ajax call and reload after
					$.ajax({
						url: '<?php echo dol_buildpath('/themequarantedeux/ajax/ajax_edit_url.php', 1)?>',
						type: 'POST',
						data: {
							token: '<?php echo newToken();?>',
							action: 'editurl',
							id: r.target.dataset.id,
							url: $('#editurl').val(),
							type: $('#type-select').val(),
						},
						success: function (res) {
							res = JSON.parse(res);
							if (res.success) {
								document.location.reload();
							} else {
								Swal.fire({
									icon: 'error',
									text: res.message
								})
							}
						}
					})
				}
			})
		});
	});
}
/**
 * Change status of a script to active or inactive
 */
function changeStatusModal() {
	let lineId = document.querySelectorAll('#scriptsinjecturls .status');
	lineId.forEach(elem => {
		elem.addEventListener('click', function (r) {
			$.ajax({
				url: '<?php echo dol_buildpath('/themequarantedeux/ajax/ajax_edit_url.php', 1)?>',
				type: 'POST',
				data: {
					token: '<?php echo newToken();?>',
					action: 'editstatus',
					id: elem.dataset.statusurl,
				},
				success: function (res) {
					res = JSON.parse(res);
					if (res.success) {
						document.location.reload();
					} else {
						Swal.fire({
							icon: 'error',
							text: res.message
						})
					}
				}
			})
		})
	})
}

$(document).ready(function() {
	launchAddurlModal($('.btnTitlePlus'));
	launchDeleteurlModal();
	launchEditurlModal();
	changeStatusModal();
})


