<?php
/* Copyright (C) 2018 SuperAdmin
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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
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

global $langs, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;
$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;
$langs->load("supersearch@supersearch");
?>

async function updateIndexesCustomization(indexes) {
	$.ajax({
		url: '<?php echo dol_buildpath('/supersearch/ajax/update_index_customization.php', 1); ?>',
		type: 'POST',
		data: {
			'indexes': indexes
		},
		success: function (response) {
			response = JSON.parse(response);
			if (response.statut === 200) {
				window.location.href = '<?php echo $_SERVER['HTTP_REFERER']; ?>?action=update_index_customization';
				return 1;
			} else {
				console.error(response);
				Swal.fire({
					icon: 'error',
					html: response.message,
					showConfirmButton: false,
					timer: 4000,
					timerProgressBar: true
				});
				return -1;
			}
		}
	});
	return 0;
}

$(document).ready(function() {
	[
		'Product',
		'Societe',
		'Article',
		'Device',
		'Application',
		'Fichinter',
		'Propal',
		'Contact',
		'Project'
	].map((idx) => {
		$.ajax({
			url: '<?php echo dol_buildpath('/supersearch/ajax/get_index_settings.php', 1) ?>',
			type: 'GET',
			data: {'indexName': idx},
			success: function (response) {
				response = JSON.parse(response);
				if (response.statut === 200) {
					let txtarea = $('textarea#settings-' + idx);
					txtarea.val(JSON.stringify(JSON.parse(response.results), null, 2));
				} else {
					console.error(response);
					Swal.fire({
						icon: 'error',
						html: response.error.message,
						showConfirmButton: false,
						timer: 4000,
						timerProgressBar: true
					});
				}
			}
		});
	});

	$('td.linecoldropdown > a').on( "click", function() {
		if ($(this).hasClass('fa-chevron-down')) $('tr#settings-' + $(this).data('index')).css('display', 'table-row');
		else $('tr#settings-' + $(this).data('index')).css('display', 'none');
		$(this).toggleClass('fa-chevron-down').toggleClass('fa-chevron-up');
	});

	$('td.linecolsave > a').on("click", function () {
		var items = $('tbody[data-index-group]');
		var indexes = [];

		$(items).each(function() {
			index = $(this).data('index-group');
			indexes.push({
				'index': index,
				'url': $('input[name*="' + index + '_url"]').val(),
				'color': $('input[name*="' + index + '_color"]').css('background-color'),
				'picto': $('input[name*="' + index + '_picto"]').val(),
				'position': $(this).index(),
				'disabled': $('td[name*="' + index + '_disabled"] > a').hasClass('fa-eye-slash')
			})
		});

		updateIndexesCustomization(indexes);
	});

	$('td.linecoldisable > a').on("click", function () {
		let disabled = $(this).hasClass('fa-eye-slash');
		let items = $('tbody[data-index-group]');
		let indexes = [];
		let currentIndex = $(this).data('index');

		$(items).each(function() {
			index = $(this).data('index-group');
			indexes.push({
				'index': index,
				'url': $('input[name*="' + index + '_url"]').val(),
				'color': $('input[name*="' + index + '_color"]').css('background-color'),
				'picto': $('input[name*="' + index + '_picto"]').val(),
				'position': $(this).index(),
				'disabled': index === currentIndex
					? !disabled
					: $('td[name*="' + index + '_disabled"] > a').hasClass('fa-eye-slash')
			});
		});

		updateIndexesCustomization(indexes);
	})

	$('a.butAction[data-action*="reset"]').on("click", function() {
		Swal.fire({
			title: '<?php echo $langs->trans('SuperSearchResetIndexAttributesTitle') ?>',
			icon: "warning",
			confirmButtonText: '<?php echo $langs->trans('Yes') ?>',
			cancelButtonText: '<?php echo $langs->trans('Cancel') ?>',
			showCancelButton: true,
			confirmButtonColor: "var(--btn-color-danger)",
		}).then((result) => {
			if (result.isConfirmed) {
				window.location.href = '<?php echo $_SERVER['HTTP_REFERER']; ?>?action=index_attributes_reset&index=' + $(this).data('index');
			} // if no we do nothing and we close the swal
		});
	})


	$('tr[id*="settings-"] > td > a[data-action*="save"]').on("click", function() {
		$.ajax({
			url: '<?php echo dol_buildpath('/supersearch/ajax/update_index_settings.php', 1); ?>',
			type: 'POST',
			data: {
				'settings' : JSON.parse($('textarea#settings-' + $(this).data('index')).val()),
				'indexName': $(this).data('index')
			},
			success: function(response) {
				response = JSON.parse(response);
				console.log(response);
				if (response.statut === 200) {
					Swal.fire({
						icon: 'success',
						html: response.message,
						showConfirmButton: false,
						timer: 2000,
						timerProgressBar: true
					});
				}
				else {
					Swal.fire({
						icon: 'error',
						html: response.meilisearch_error,
						showConfirmButton: false,
						timer: 4000,
						timerProgressBar: true
					});
				}
			},
			error: function() {
				console.log(' <?php echo $langs->trans('SuperSearchError'); ?> when try to get index settings');
			}
		});

	});
});
