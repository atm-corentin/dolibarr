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
 */

/**
 * \file    lib/lareponse_favorites.lib.php
 * \ingroup lareponse
 * \brief   Library files with common functions for favorites
 */

/**
 * Print start of setup section (title, and open form / table)
 *
 * @param  string    $title    Translation key for section title
 * @param  string    $icon     Fontawesome icon to set before title
 * @return void
 */
function printSetupStartSection($title, $icon = "")
{
	global $langs;

	// Open form
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	// Print title with icon (or not)
	print '<h1>';
	if (!empty($icon)) print "<span class='$icon'></span> ";
	print $langs->trans($title) . '</h1>';
	// Open table
	print '<table class="noborder" width="100%"><tbody>';
	// Print table title
	print '<tr class="liste_titre">';
	print '<td style="width: 30% ;"><h4>' . $langs->trans('Parameter') . '</h4></td>';
	print '<td style="width: 70% ;" align="center">' . $langs->trans('Value') . '</td>';
	print '</tr>';
}

/**
 * Print end of setup section (close table and form)
 *
 * @return void
 */
function printSetupEndSection()
{
	global $langs;

	// Buttons
	print '<tr class="pair"><td colspan="3" align="center"><button type="submit" class="butAction">' . $langs->trans('Save') . '</button></td></tr>';
	// CLose table
	print '</tbody>';
	print '</table>';
	// Close form
	print '</form>';
}

/**
 * Print input for LaReponse setup
 *
 * @param  string    $label             Trans key for input label
 * @param  string    $const             Constant name
 * @param  string    $type              Type of input to print
 *                                      'switch' for a toggle switch button
 *                                      'int' for an integer input
 *                                      'string' for a string input
 *                                      'color' for a color picker input
 * @param  string    $icon              Fontawesome icon to set before title
 * @param  array     $moreAttributes    array of attributes to add
 *                                      help        => Translation key for text to show in help tooltip
 *                                      default        => Default value
 *                                      min (int)    => Minimum input value
 *                                      max (int)    => Maximum input value
 *                                      afterInput (string)    => This attribute will be print after input
 * @return void
 */
function printSetupInput($label, $const, $type = "switch", $icon = "", $moreAttributes = array())
{
	global $langs, $conf, $db;

	include_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
	$form = new Form($db);

	// Print input label
	print '<tr class="oddeven">';
	print '<td >' . $langs->trans($label);
	// Print help
	if (!empty($moreAttributes["help"])) print getHelpIcon($moreAttributes["help"]);
	print '</td><td align="center">';
	$action = "update_" . $const;
	switch ($type) {
		case "switch" :
			$status = (int) (empty($conf->global->$const));
			print '<a href="' . $_SERVER['PHP_SELF'] . '?' . $action . '=' . $status . '">';
			print img_picto($langs->trans(($status ? "Disabled" : "Activated")), 'switch_' . ($status ? "off" : "on"));
			print '</a>';
			break;
		case "int":
			$min = (!empty($moreAttributes['min']) ? 'min="' . $moreAttributes['min'] . '" ' : "");
			$max = (!empty($moreAttributes['max']) ? 'max="' . $moreAttributes['max'] . '" ' : "");
			print '<input class="width75" name="' . $action . '" type="number" pattern="^[0-9]*$" value="' . ($conf->global->$const ?? $moreAttributes["default"] ?? 0) . '" ' . $min . $max . '">';
			break;
		case "color":
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
			$formother = new Formother($db);
			$const = dolibarr_get_const($db, $const);
			print $formother->selectColor(colorArrayToHex(colorStringToArray((!empty($const) ? $const : ''), array()), ''), $action) . ' ';
			break;
		case "list":
			if (!empty($moreAttributes) && !empty($moreAttributes["list"]) && is_array($moreAttributes["list"])) {
				print $form->selectarray($action, $moreAttributes["list"], ($conf->global->$const ?? ''));
				break;
			}
			break;
		case "string":
		default:
			print '<td align="center"><input name="' . $action . '" value="' . ($conf->global->$const ?? "") . '" type="text">';
			break;
	}
	// Close table line
	if (!empty($moreAttributes["afterInput"])) print ' ' . $moreAttributes["afterInput"];
	print '</td></tr>';
}

/** Print update action for a given input
 *
 * @param  string    $const              Constant name
 * @param  string    $type               Type of constant. Deprecated, only strings are allowed for $value. Caller must json encode/decode to store other type of data.
 * @param  string    $getPostType        Type of check
 *                                       ''=no check (deprecated)
 *                                       'none'=no check (only for param that should have very rich content like passwords)
 *                                       'array', 'array:restricthtml' or 'array:aZ09' to check it's an array
 *                                       'int'=check it's numeric (integer or float)
 *                                       'intcomma'=check it's integer+comma ('1,2,3,4...')
 *                                       'alpha'=Same than alphanohtml since v13
 *                                       'alphawithlgt'=alpha with lgt
 *                                       'alphanohtml'=check there is no html content and no " and no ../
 *                                       'aZ'=check it's a-z only
 *                                       'aZ09'=check it's simple alpha string (recommended for keys)
 *                                       'aZ09arobase'=check it's a string for an element type
 *                                       'aZ09comma'=check it's a string for a sortfield or sortorder
 *                                       'san_alpha'=Use filter_var with FILTER_SANITIZE_STRING (do not use this for free text string)
 *                                       'nohtml'=check there is no html content
 *                                       'restricthtml'=check html content is restricted to some tags only
 *                                       'custom'= custom filter specify $filter and $options) * @param  int    $visible    Is constant visible in Setup->Other page (0 by default)
 * @param  int       $visible            Is constant visible in Setup->Other page (0 by default)
 * @return void
 */
function printSetupAction($const, $type = "chaine", $getPostType = "alphanohtml", $visible = 0)
{
	global $db, $conf;

	$getPostName = "update_" . $const;
	$value = GETPOST($getPostName, $getPostType);

	// GETPOST() function return empty string is variable isn't sent, so we have to check if value is null or if it is not sent
	if (empty($value) && !isset($_GET[$getPostName]) && !isset($_POST[$getPostName])) unset($value);

	// If value is sent, we update it
	if (isset($value)) dolibarr_set_const($db, $const, $value, $type, $visible, '', $conf->entity);
}

/**
 * Get HTML code for tooltip help icon
 *
 * @param  string    $text    Text to show in tooltip
 * @return string
 */
function getHelpIcon($text)
{
	$icon = '<span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="' . $text . '"><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>';
	return $icon;
}
