<?php

/* Copyright (C) 2022 Fabien FERNANDES ALVES <fabien@code42.fr>
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

if (!defined('NOREQUIREUSER')) {  define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {   define('NOREQUIRESOC', '1');
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
 * \file    h2g2/js/documentation.js.php
 * \ingroup h2g2
 * \brief   Generic javaScript file to manage h2g2 dev documentation.
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

global $langs, $db;

$langs->load('h2g2@h2g2');

?>

/**
 * Copy a string to the clipboard
 *
 * @param   string      str         String to copy
 * @returns {Promise<never>|Promise<void>}      Promise
 */
const copyToClipboard2 = str => {
	if (navigator && navigator.clipboard && navigator.clipboard.writeText)
		return navigator.clipboard.writeText(str);
	return Promise.reject('The Clipboard API is not available.');
};

$( function() {
	const examples = document.querySelectorAll('.example__code pre');

	if (examples) {
		examples.forEach(elem => {

			// Add click listener
			elem.addEventListener('click', async () => {
				const code = elem.querySelector('code');
				const btn = elem.querySelector('.example__code-header_copy button');

				if (btn && code) {
					copyToClipboard2(code.innerText).then(() => {
						btn.innerHTML = '<?php echo $langs->trans('H2G2Copied')?>';
						btn.classList.remove('error');
					}).catch(() => {
						btn.innerHTML = '<?php echo $langs->trans('H2G2NotCopied')?>';
						btn.classList.add('error');
					})
				}
			});

			// Add hover listener
			elem.addEventListener('mouseenter', () => {
				const container = elem.querySelector('.example__code-header_copy');

				if (container) {
					container.style.visibility = 'visible';
				}
			})

			// Add unhover listener
			elem.addEventListener('mouseleave', () => {
				const container = elem.querySelector('.example__code-header_copy');
				const btn = elem.querySelector('.example__code-header_copy button');

				if (container && btn) {
					container.style.visibility = 'hidden';
					btn.innerHTML = '<?php echo $langs->trans('H2G2Copy')?>';
					btn.classList.remove('error');
				}
			})
		})
	}
} );