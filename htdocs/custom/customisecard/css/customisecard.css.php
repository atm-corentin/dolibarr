<?php
/*
 * Copyright (C) 2020-2021 Hugo Allegaert   <hugo@code42.fr>
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
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');    // Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');    // Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC')) {    define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');    // Not disabled because need to do translations
if (! defined('NOCSRFCHECK')) {     define('NOCSRFCHECK', 1);
}
if (! defined('NOTOKENRENEWAL')) {  define('NOTOKENRENEWAL', 1);
}
if (! defined('NOLOGIN')) {         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML')) {   define('NOREQUIREHTML', 1);
}
if (! defined('NOREQUIREAJAX')) {   define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter(false);

// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) { header('Cache-Control: max-age=3600, public, must-revalidate');
} else { header('Cache-Control: no-cache');
}

?>

/* First one is not read */
.useless {}

/* [#20] We can't use the property "display: none" because separate extra fields checks is sub-fields are hidden to register if it is closed or open */
.PSHidden {
	position: absolute !important;
	visibility: hidden !important;
}

.PSNotReallyHide {
	opacity:0.5;
	color:#f44336 !important;
	text-decoration:line-through !important;
}

.PSBolder td, tr>td.PSBolder, tr>th.PSBolder {
	font-weight: bold;
}

div.PSActions {
	position:absolute;
	top:0;
	left:0;
	white-space: nowrap;
}

div.PSActionsButton, div.PSActionsLines {
	top:0;
	left:0;
	white-space: nowrap;
}

.PSTable td, .PSTableActions {
	position:relative;
}

.PSCanEdit {
	padding:10px 5px 5px 10px;
	border-top:1px;
	border-right:1px;
	border-left:1px;
	background: #3b5999;
	height:40px;
	color: white;
	white-space: nowrap;
	position:relative;
}
.PSCanEdit div.buttons {
	float:right;
}

tr.PSTooltip {
}

.PSTitle {
}

tr.PSTitleEdited>td:first-child {
	background-image:url("../img/edit-small.png");
	background-repeat:no-repeat;
	background-position: right 5px center;
}

.PSSmallSpace {
	 margin-right: 2px;
 }

.PSBigSpace {
	margin-right: 15px;
}

.tooltip {
	position: relative;
	display: inline-block;
	margin-left: 10px;
	cursor: help;
}

.tooltip .tooltiptext {
	width: max-content;
	visibility: hidden;
	background-color: white;
	color: black;
	padding: 10px;
	box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
	/* Position the tooltip */
	position: absolute;
	z-index: 1;
}

.tooltip:hover .tooltiptext {
	visibility: visible;
}

.customisecard-no-link {
	text-decoration: none !important;
	color: var(--colortexttitlenotab) !important;
}
.customisecard-no-link:hover {
	cursor: pointer;
}
