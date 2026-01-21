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

$(document).ready(function() {

	// Test Connection between dolibarr and meilisearch
	$('#api-test-connection-button').click(function(e) {
		e.preventDefault();
		$.ajax({
			url: '<?php echo dol_buildpath('/supersearch/ajax/call_api.php', 1) ?>',
			type: 'GET',
			success: function(response) {
				response = JSON.parse(response);
				$('#api-response').empty();
				if (response.statut === 200) $('#api-response').html('<span style="color: green;" class="fas fa-check-circle"></span> Connexion OK');
				else {
					console.error(response);
					$('#api-response').html('<span style="color: red;" class="fas fa-exclamation-circle"></span> ' + response.error);
				}
			},
			error: function() { $('#api-response').text('<span style="color: red;" class="fas fa-exclamation-circle"></span> <?php echo $langs->trans('SuperSearchError'); ?>');}
		});
	});

	$('#reset-const-button').click(function(e) {
		e.preventDefault();
		$.ajax({
			url: '<?php echo dol_buildpath('/supersearch/ajax/reset_const.php', 1) ?>',
			type: 'GET',
			success: function(response) {
				response = JSON.parse(response);
				$('#api-response').empty();
				if (response.statut === 200 && response.result === 4) window.location.href = '<?php echo $_SERVER['HTTP_REFERER']; ?>?reset=1';
				else {
					console.error(response);
					$('#api-response').html('<span style="color: red;" class="fas fa-exclamation-circle"></span> ' + response.error);
				}
			},
			error: function() { $('#api-response').text('<span style="color: red;" class="fas fa-exclamation-circle"></span> <?php echo $langs->trans('SuperSearchError'); ?>');}
		});
	});

	$('#restore-key').click(function(e) {
		e.preventDefault();
		$.ajax({
			url: '<?php echo dol_buildpath('/supersearch/ajax/restore_key.php', 1) ?>',
			type: 'GET',
			success: function(response) {
				response = JSON.parse(response);
				if (response.statut === 200) window.location.href = '<?php echo $_SERVER['HTTP_REFERER']; ?>';
				else {
					console.error(response);
				}
			}
		});
	});

});
