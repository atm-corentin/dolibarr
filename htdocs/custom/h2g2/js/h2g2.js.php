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

/* Javascript library of module H2G2 */

const initMultiSelectButton = () => {
	const btns = document.querySelectorAll('.h2g2multiselect');

	if (btns && btns.length > 0) {
		btns.forEach(elem => {
			if (elem.classList.contains('without-action')) { // Without action buttons, chevron is not seperated
				const toggler = elem.querySelector('.h2g2multiselect__button');

				if (toggler) {
					toggler.addEventListener('click', e => {
						e.preventDefault();
						if (!toggler.classList.contains('butActionRefused')) { // Handle disabled button
							const chevron = toggler.querySelector('.h2g2multiselect__chevron');
							const options = elem.querySelector('.h2g2multiselect__options');
							if (options) {
								options.classList.toggle('hidden');
								chevron.classList.toggle('up');
							}
						}
					})
				}
			} else { // With action buttons, chevron is separated
				const toggler = elem.querySelector('.h2g2multiselect__chevron');

				if (toggler) {
					toggler.addEventListener('click', e => {
						e.preventDefault();
						let options = toggler.parentNode.querySelector('.h2g2multiselect__options.optionsup');

						if (options) {
							options.classList.toggle('hidden');
							toggler.classList.toggle('down');

							const togglerUp = document.querySelectorAll('.h2g2multiselect__chevron:not(.down)');
							togglerUp.forEach(elem => {
								if (elem !== toggler) {
									options = elem.parentNode.querySelector('.h2g2multiselect__options.optionsup');
									if (options) {
										options.classList.toggle('hidden');
										elem.classList.toggle('down');
									}
								}
							})
						} else {
							let options = toggler.parentNode.querySelector('.h2g2multiselect__options.optionsdown');
							options.classList.toggle('hidden');
							toggler.classList.toggle('up');
						}
					})
				}
			}
		})
	}
}

$( function() {
	initMultiSelectButton();

	document.addEventListener("click", (e) => {
		const isChevron = e.target.classList.contains('h2g2multiselect__chevron');
		const isFontAwesome = e.target.classList.contains('fas');

		if (!isChevron && !isFontAwesome) {
			const togglerUp = document.querySelectorAll('.h2g2multiselect__chevron:not(.down)');
			togglerUp.forEach(elem => {
				const options = elem.parentNode.querySelector('.h2g2multiselect__options.optionsup');
				if (options) {
					options.classList.toggle('hidden');
					elem.classList.toggle('down');
				}
			});
		}
	});

	/*
	 * Select a row on click
	 */
	var rows = document.querySelectorAll('.row-selectable');

	// Add click listener on all td of a row
	rows.forEach(function (elem) {
		var columns = elem.querySelectorAll('td:not(.nowrap)');
		var cb = elem.querySelector('.checkforselect');
		columns.forEach(function (value) {
			value.addEventListener('click', function(e) {
				if (e.target !== value)
					return

				var checked = cb.checked;
				if (!elem.classList.contains('not-selectable')) {
					if (checked) {
						cb.checked = false;
						elem.classList.remove('highlight');
					} else {
						cb.checked = true;
						elem.classList.add('highlight');
					}
					initCheckForSelect(1);
				}
			});
		})
		if (cb.checked) {
			elem.classList.add('highlight');
		} else
			elem.classList.remove('highlight');
	});
} );