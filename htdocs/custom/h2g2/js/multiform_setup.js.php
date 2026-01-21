<?php
/* Copyright (C) 2024 Ravi Trébuchet <ravi@code42.fr>
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

// Impossible to call file's function if this is not set
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);

/**
 * \file    h2g2/js/h2g2_dictionary.js.php
 * \ingroup h2g2
 * \brief   JavaScript file for module h2g2. */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
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

global $langs;

$langs->loadLangs(array("h2g2@h2g2"));
?>

function activeForm(form) {
	// Hide all forms
	$('div.H2G2-form').each(function (index) {
		$(this).addClass("H2G2-hidden");
	})

	// Enable form that we want
	form.removeClass("H2G2-hidden");
	// Select button associated to form
	$("#enable-form-buttons > ul > li.selected").each(function (index) {
		$(this).removeClass("selected")
	})
	let buttonId = form.attr("id").replace("form", "button")

	$("#" + buttonId).addClass("selected")
	$("#form-name").attr("value", form.find("h2").attr("data-title"))
	$("#h2g2-form-name").attr("value", form.find("h2").attr("data-title"))
	$("#" + buttonId).append($(".follow"))
}

/**
 * Display help or go on a next step
 *
 * @param elementSelector
 */
function displayHelp(elementSelector) {
	let helpFrame = document.getElementById('tutorial-overlay');
	let highlight = document.getElementById('H2G2-highlight');
	let element = document.querySelector(elementSelector);
	let helpText = $('#H2G2-help-text');
	let next = $('#H2G2-button-help-next');

	if (!element) return;

	const rect = element.getBoundingClientRect();

	// Positionner le "trou"
	highlight.style.width = rect.width + 'px';
	highlight.style.height = rect.height + 'px';
	highlight.style.top = rect.top + window.scrollY + 'px';
	highlight.style.left = rect.left + window.scrollX + 'px';
	highlight.style.zIndex = 1000;

	// Set up steps
	let steps = [
		'#H2G2-admin-tabs', 											// Show tab header
		'#enable-form-buttons div:not(.H2G2-hidden) .H2G2-value',		// Show inputs
		'div:not(.H2G2-hidden) .tabsAction'		// Show save button
	];

	const step = (steps.indexOf(elementSelector) + 1);

	if (steps[step] !== undefined) next.attr("onclick", 'displayHelp("' + steps[step] + '")');

	if (step >= 3) next.attr("class", "H2G2-hidden")
	else next.attr("class", "butAction")

	// Increase steps
	helpText.html($("#admin-help-button").attr("data-help" + step))

	// Display help
	helpFrame.classList.remove('H2G2-hidden');
}

/**
 * Hide help
 */
function hideTutorialOverlay() {
	const helpFrame = document.getElementById('tutorial-overlay');
	helpFrame.classList.add('H2G2-hidden');
}

/**
 * Call ajax to save const
 *
 * @param type
 * @param constantName
 */
function saveConstant(type, constantName, table = "") {
	// Get element
	let selector = constantName;
	if (type === "html") selector = "#cke_" + constantName
	let elem = $(selector);
	if (type === "html") elem = $(selector).find("body.cke_editable")
	// Load ajax url an token
	let ajax = "<?php echo dol_buildpath('/h2g2/ajax/ajax_save_constant.php', 1)?>";
	let tok = "<?php echo newToken();?>"
	// Get const value
	if (elem !== null) {
		let constantValue = elem.html()
		if (type === "html") constantValue = $(selector + ' iframe.cke_wysiwyg_frame')[0].contentWindow.document.querySelector('body').innerHTML
		console.log(constantValue)

		$.ajax({
			url: ajax,
			type: "POST",
			data: {
				const: constantName,
				value: constantValue,
				table: table,
				token: tok
			},
			success: function (result) {
				result = JSON.parse(result);
				if (result.success) {
					const Toast = Swal.mixin({
						toast: true,
						position: "top-end",
						showConfirmButton: false,
						timer: 2000,
						timerProgressBar: true,
						didOpen: (toast) => {
							toast.onmouseenter = Swal.stopTimer;
							toast.onmouseleave = Swal.resumeTimer;
						}
					});
					Toast.fire({
						icon: "success",
						title: "<?php print $langs->trans("H2G2SavedSuccessfully"); ?>"
					});
				} else {
					Swal.fire("<?php print $langs->trans('H2G2Error');?>" + result.statut, "<?php echo $langs->trans('H2G2ErrorElementNotSaved');?>", "error");
				}
			},
			error: function () {
				Swal.fire("<?php print $langs->trans('H2G2Error');?>", "<?php echo $langs->trans('H2G2ErrorElementNotSaved');?>", "error");
			}
		});
	} else {
		console.warn("Element '" + constantName + "' not found : impossible to save")
		Swal.fire("<?php echo $langs->trans('H2G2Error');?>", "<?php echo $langs->trans('H2G2ErrorElementNotSaved');?>", "error")
	}
}

$(document).ready(function () {

	$('.H2G2-form').each(function (index) {
		// Crée un bouton
		const button = $('<li>');
		const icon = $('<span>');
		let buttonId = $(this).attr("id").replace("form", "button")
		let iconText = $(this).find("h2").attr("data-icon")
		let iconStyle = $(this).find("h2").attr("data-icon-style")
		if (iconText !== "") icon.attr("class", iconText + " H2G2-icon")
		if (iconStyle !== "") icon.attr("style", iconStyle)
		button.text($(this).find("h2").text())
		button.attr("id", buttonId)
		button.attr("class", "H2G2-button")
		button.prepend(icon)
		if (!$(this).hasClass("H2G2-hidden")) button.addClass("selected")

		// Ajoute un écouteur d'événement au bouton pour ajouter la classe quand on clique
		button.on('click', () => activeForm($(this)));

		// Ajoute le bouton dans le DOM, juste après chaque formulaire
		$("ul.flex-center").append(button);
	});
	// Add follow indicator to the right section
	const follow = $('<span>');
	follow.attr("class", "follow")
	$(".H2G2-button.selected").append(follow)
	// Add help button
	let helpButton = $("#admin-help-button")
	helpButton.on("click", () => displayHelp("#H2G2-admin-tabs"));
	$("#H2G2-admin-tabs").append(helpButton)


});
