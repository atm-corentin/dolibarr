<?php
/* Copyright (C) 2020 SuperAdmin
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

if (!defined('NOREQUIREUSER')) {  define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {    define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {   define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {  define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {    define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {        define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {  define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {  define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {  define('NOREQUIREAJAX', '1');
}


/**
 * \file    h2g2/js/h2g2.js.php
 * \ingroup h2g2
 * \brief   JavaScript file for module H2G2.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) { $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) { $res=@include substr($tmp, 0, ($i+1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) { $res=@include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php")) { $res=@include "../../../main.inc.php";
}
if (! $res) { die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) { header('Cache-Control: max-age=3600, public, must-revalidate');
} else { header('Cache-Control: no-cache');
}

?>

$( function() {
	/*
	Documentation about KeyCode here -> https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/keyCode
	 */

	const macros = [
		{
			keyCode: 72, // h
			name: 'Macro list',
			description: 'Display list of macros available',
			script: `Swal.fire({
						title: 'Macros',
						html: macros.map((macro) => '<strong>' + macro.name + '</strong> → option/alt + ' + String.fromCharCode(macro.keyCode) + ' → "' + macro.description + '"<br>').join('')
					});`
		},
		{
			keyCode: 80, // p
			name: 'Prev Item',
			description: 'Previous Item',
			script: `let prevLink = $('a[accesskey="p"]')[0]; if (prevLink) { window.location.href = prevLink.href; } else { console.error('Previous link not found'); }`
		},
		{
			keyCode: 78, // n,
			name: 'Next Item',
			description: 'Next Item',
			script: `let nextLink = $('a[accesskey="n"]')[0]; if (nextLink) { window.location.href = nextLink.href; } else { console.error('Next link not found'); }`
		}
	];

	$(document).on("keydown", function(e) {
		macros.forEach(macro => {
			if (e.altKey && e.keyCode === macro.keyCode) {
				e.preventDefault();
				eval(macro.script);
			}
		});
	});
})
