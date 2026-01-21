<?php
/* Copyright (C) 2019 SuperAdmin
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

/**
 * \file    lareponse/css/lareponse.css.php
 * \ingroup lareponse
 * \brief   CSS file for module Lareponse.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/../main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

.commentBox {
	border-style: ridge;
	margin-top: 10px;
	min-height: 80px;
	padding-left: 10px;
	padding-right: 10px;
	margin-bottom: 10px;
}

.lareponse_article img {
	max-width: 100%;
	height: auto !important;
}

.lareponse_article img {
	max-width: 100%;
	height: auto !important;
}

.commentTitle {
	margin: 0;
	font-size: 1em;
	font-weight: bold;
}

.commentTitle a:link, .commentTitle a:visited, .commentTitle a:hover, .commentTitle a:active {
	font-weight: bold;
}

.tableComment p {
	padding: 0;
	margin-top: 5px;
}

.lareponse_tag {
	margin-right: 5px;
	margin-bottom: 5px;
	margin-top: 5px;
	padding: 5px;
	color: white !important;
	border-radius: 20px;
}

#commentLoader {
	display: none;
	color: rgb(0,0,120);
	width: 100%;
	text-align: center;
}

.noDataContainer, .noDataText {
	width: 100%;
	text-align: center;
}

.noDataIcon img {
	width: 10%;
	height: 10%;
}

.lr-cardfavorite {
	font-size: 1.2em;
	width: 4.5em;
	margin-right: 4.7em;
	display: inline;
}
.lr-cardfavorite p {
	float: left;
	margin-top: 0em;
}

.btn-lareponse-tag {
	width: auto;
	height: auto;
	font-size: 0.9em;
	margin-top: 10px;
	margin-left: 10px;
}

.btn-lareponse-tag:hover {
	cursor: pointer;
}

.btn-lareponse-tag i {
	margin-right: 5px;
}

.in-list-badge {
	padding: 0.4em 0.5em;
	font-size: 1em;
}

.noFavIcon img {
	height:7.85em;
}

ul .lr_onlinewebcard {
	float: left;
	margin-right: 1em;
	margin-top: -1em;
	text-align: center;
}

#clipboardcopytoken:hover {
	cursor: pointer;
}

form[action*="/lareponse/article_list.php"] {
	position: static !important;
}

/*#147*/
.tabs-article-multiselect {
	display: flex;
	padding: 0px !important;
}

.lareponse-article-hidden, .lareponse-tags-hidden {
	display: none !important;
}

#lareponse-article-icon, #lareponse-tags-icon {
	width: 20px;
	text-align: center;
}

#tags-section {
	font-size: 0.7em;
	display: flex;
	flex-wrap: wrap;
	grid-gap: 1em;
}

#comment-section {
	padding-top: 2px;
}

.lareponse-tabs-action-eldy  {
	display: flex;
	& > * {
		margin-bottom: 1.4em !important;
		margin-top: 0px !important;
	}
}

li.active-result, li.search-choice , li.result-selected {
	padding-left: 15px !important;
	padding-right: 10px !important;
}

.result-selected {
	display: none !important;
}

#iddivjstree {
	max-width: 50% !important;
}

#treetag {
	overflow-x: scroll;
}

table.border.centpercent.tableforfieldedit {
	border: var(--border-width) var(--border-style);
	border-radius: var(--border-radius--medium) !important;
	background: var(--colorbackbody) !important;
	padding: 1em !important;
	border-collapse: separate !important;
}

form#searchFormList[action$="lareponse/article_list.php"] > div.liste_titre.liste_titre_bydiv {
	display: inline-table;
}

form#requestForm[action$="lareponse/article_card.php"][method="POST"] {
    & > table {
        height: 100%;
    }

    #tr_wysiwyg_editor {
        height: 100%;

        > td > div {
            height: 100%;
        }
    }

    #cke_content > .cke_inner.cke_reset {
        height: 100%;
        display: flex;
        flex-direction: column;

        & .cke_contents {
            height: 100%;
        }
    }
}

/*FAVORITE ICON MANAGER*/

:root {
	--fav-star-color: #e09200
}

.LaReponse.fa-star {
	margin-right: 0.3em;
	color: var(--fav-star-color);
}

.isfavorite {
	animation: animationFavAddTurn 1000ms linear both;
	color: var(--fav-star-color);
}

@keyframes animationFavAddTurn {
	0% { transform: matrix3d(1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	3.2% { transform: matrix3d(0.763, 0.652, 0, 0, -0.501, 0.871, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	4.3% { transform: matrix3d(0.614, 0.797, 0, 0, -0.649, 0.768, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	6.41% { transform: matrix3d(0.325, 0.953, 0, 0, -0.853, 0.534, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	8.61% { transform: matrix3d(0.083, 1.002, 0, 0, -0.961, 0.296, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	12.71% { transform: matrix3d(-0.149, 0.992, 0, 0, -1.003, -0.001, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	12.91% { transform: matrix3d(-0.154, 0.991, 0, 0, -1.003, -0.01, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	17.22% { transform: matrix3d(-0.181, 0.984, 0, 0, -0.994, -0.111, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	18.92% { transform: matrix3d(-0.163, 0.987, 0, 0, -0.994, -0.116, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	25.23% { transform: matrix3d(-0.066, 0.998, 0, 0, -0.998, -0.067, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	28.33% { transform: matrix3d(-0.028, 1, 0, 0, -0.999, -0.037, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	31.43% { transform: matrix3d(-0.004, 1, 0, 0, -1, -0.015, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	39.44% { transform: matrix3d(0.013, 1, 0, 0, -1, 0.007, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	56.46% { transform: matrix3d(0, 1, 0, 0, -1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	61.66% { transform: matrix3d(-0.001, 1, 0, 0, -1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	81.48% { transform: matrix3d(0, 1, 0, 0, -1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	83.98% { transform: matrix3d(0, 1, 0, 0, -1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	90.00% { transform: matrix3d(0, 1, 0, 0, -1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
	100% { transform: matrix3d(1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1); }
}

div.fichecenter.c42-lr {
	display: flex;

	.lareponse_article {
		order: -1;
		width: 100% !important;
        min-width: 0;

		h1,h2,h3,h4,h5,h6 {
			scroll-margin-top: 55px !important;
		}
	}

    #ToC > * {
        top: 55px;
    }

	#ToC, .lareponse_article {
		margin: 10px 0px;
		padding: 10px;
		border: thin solid var(--border-color-light);
        border-left: hidden;
	}
}
