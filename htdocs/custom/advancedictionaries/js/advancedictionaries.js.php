<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/js/datepicker.js.php
 * \brief      File that include javascript functions for datepickers
 */

if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define javascript type
header('Content-type: text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

$get_select_options_url = dol_escape_js(dol_buildpath('/advancedictionaries/ajax/get_select_options.php', 1));

?>

/***************************************************************************************
 * Update functions
 ***************************************************************************************/

function advanced_dictionaries_watch_input(dictionary_module, dictionary_name, dictionary_root_path, default_values, fields_to_watch, key_prefix, key_suffix) {
	advanced_dictionaries_update_list_values(dictionary_module, dictionary_name, dictionary_root_path, default_values, '', fields_to_watch, key_prefix, key_suffix);

	$.map(fields_to_watch, function (field_name) {
		$('#' + key_prefix + field_name + key_suffix).on('change keyup', function () {
			advanced_dictionaries_update_list_values(dictionary_module, dictionary_name, dictionary_root_path, {}, field_name, fields_to_watch, key_prefix, key_suffix);
		});
	});
}

function advanced_dictionaries_update_list_values(dictionary_module, dictionary_name, dictionary_root_path, default_values, field_updated, fields_to_watch, key_prefix, key_suffix) {
	var data_send = {
		dictionary_module: dictionary_module, dictionary_name: dictionary_name, dictionary_root_path: dictionary_root_path,
		field_updated: field_updated, key_prefix: key_prefix, key_suffix: key_suffix, token: '<?php print currentToken() ?>'
	};
	$.map(fields_to_watch, function (field_name) {
		data_send = advanced_dictionaries_update_data_send(data_send, field_name, key_prefix, key_suffix);
	});

	$.ajax('<?php print $get_select_options_url ?>', {
		method: "POST",
		data: data_send,
		dataType: "json"
	}).done(function(response) {
		if (typeof response.error === 'string') {
			advanced_dictionaries_display_message(response.error, 'error');
		} else if (typeof response.values === 'object') {
			$.map(response.values, function (input_values, field_name) {
				var input = $('#' + key_prefix + field_name + key_suffix);

				if (input.length > 0) {
					var selected = input.val();
					if (field_name in default_values) selected = default_values[field_name];
					input.empty();
					input.html(input_values);
					input.val(selected);
					input.change();
				}
			});
		}
	}).fail(function(jqxhr, textStatus, error) {
		advanced_dictionaries_display_message(textStatus + " - " + error, 'error');
	});
}

/***************************************************************************************
 * Tool functions
 ***************************************************************************************/

/**
 * Get data to send for update
 *
 * @param data_send		object		Data sent by Ajax for get new options of the select inputs
 * @param field_name	string		Field name
 * @param key_prefix	string		Html name prefix
 * @param key_suffix	string		Html name suffix
 */
function advanced_dictionaries_update_data_send(data_send, field_name, key_prefix, key_suffix) {
	var htmlname = key_prefix + field_name + key_suffix;
	var input_field = $('#' + htmlname);

	if (typeof CKEDITOR === "object" && typeof CKEDITOR.instances !== "undefined" && typeof CKEDITOR.instances[htmlname] !== "undefined") {
		data_send[htmlname] = CKEDITOR.instances[htmlname].getData();
	} else if (input_field.length > 0) {
		data_send[htmlname] = input_field.val();
	}

	input_field = $('#' + htmlname + 'hour');
	if (input_field.length > 0) {
		data_send[htmlname + 'hour'] = input_field.val();
	}
	input_field = $('#' + htmlname + 'min');
	if (input_field.length > 0) {
		data_send[htmlname + 'min'] = input_field.val();
	}
	input_field = $('#' + htmlname + 'day');
	if (input_field.length > 0) {
		data_send[htmlname + 'day'] = input_field.val();
	}
	input_field = $('#' + htmlname + 'month');
	if (input_field.length > 0) {
		data_send[htmlname + 'month'] = input_field.val();
	}
	input_field = $('#' + htmlname + 'year');
	if (input_field.length > 0) {
		data_send[htmlname + 'year'] = input_field.val();
	}

	return data_send;
}

/**
 *  Show a message
 *
 * @param message	string		Message to show
 * @param type		string		Type of the message ('success', 'warning', 'error'. Default: 'success')
 */
function advanced_dictionaries_display_message(message, type = 'success') {
	if (typeof message === 'string' && message.length > 0) {
		/* jnotify(message, preset of message type, keepmessage) */
		if (type == 'warning') {
			$.jnotify(message, "warning", false);
		} else if (type == 'error') {
			$.jnotify(message, 'error', true, { remove: function() {} });
		} else {
			$.jnotify(message, "ok");
		}
	}
}
