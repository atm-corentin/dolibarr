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
 * \file    h2g2/js/clpbrd42.js.php
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

dol_include_once('/core/lib/admin.lib.php');

global $db, $conf;

$h2g2ClpBrdFunction = dolibarr_get_const($db, 'H2G2_CLPBRD_FUNCTION', $conf->entity);

if (empty($h2g2ClpBrdFunction)) $h2g2ClpBrdFunction = 0;

?>

let h2g2ClpBrdFunctionActivated = <?php echo $h2g2ClpBrdFunction; ?>;

document.addEventListener('DOMContentLoaded', function () {
	window.addEventListener('load', function () {
		if (h2g2ClpBrdFunctionActivated) CustomClipBoardCopyOnClick('div > .tableforfield > tbody > tr > td:nth-child(2):not(:empty)');
	});
});

function hasContent(element) {
	const textContent = element.textContent.replace(/\s|&nbsp;/g, '');
	return textContent.length > 0;
}

/**
 * Checks if the given element has a form element as an ancestor
 *
 * @param {HTMLElement} element - The DOM element to start the search from.
 * @return {boolean} - Returns true if a form element is found as an ancestor, otherwise returns false.
 */
function hasFormAsAncestorOrChild(element) {
	let currentElement = element;
	while (currentElement) {
		if (currentElement.tagName === 'FORM') return true; // there a form as parent
		currentElement = currentElement.parentElement; // we get the parent
	}

	const forms = element.getElementsByTagName('FORM');
	if (forms.length > 0) return true; // un form comme enfant

	return false; // no form found
}

/**
 * Adds a click-to-copy functionality to the elements selected by customQuery.
 * @param {string} customQuery - A custom selector to target specific elements to make copyable.
 */
function CustomClipBoardCopyOnClick(customQuery = '') {
	var allItemsCopy = document.querySelectorAll('.clpbrd42' + (customQuery ? ',' + customQuery : ''));

	allItemsCopy.forEach((element) => {
		if (!hasFormAsAncestorOrChild(element) && hasContent(element)) { // item with form -> edit mode
			element.innerHTML += '<span class="copy"></span>';
			element.classList.add('clpbrd42');
			element.addEventListener('click', function (e) {
				var copyText = this.innerText || this.textContent; // Exclure innerHtml

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(copyText);
				} else {
					console.error('Clipboard API not supported or not available');
					fallbackCopyTextToClipboard(copyText);
				}
			});
		}
	});
}

/**
 * Fallback method to copy text to the clipboard by creating a temporary textarea.
 * @param {string} text - The text to copy to the clipboard.
 */
function fallbackCopyTextToClipboard(text) {
	var textArea = document.createElement("textarea");
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.focus();
	textArea.select();

	try {
		var successful = document.execCommand('copy');
	} catch (err) {
		console.error('Fallback: Unable to copy', err);
	}

	document.body.removeChild(textArea);
}