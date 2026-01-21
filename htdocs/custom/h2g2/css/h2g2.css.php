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
 */

/**
 * \file    h2g2/css/h2g2.css.php
 * \ingroup h2g2
 * \brief   CSS file for module H2G2.
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter('public');
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
if (empty($dolibarr_nocache)) { header('Cache-Control: max-age=10800, public, must-revalidate');
} else { header('Cache-Control: no-cache');
}

?>

<?php
// Used for Select a row on click function
if ((float) DOL_VERSION < 8.0) {
	echo '.highlight {
        background: #'.$conf->global->THEME_ELDY_USE_HOVER.' !important;
    }';
} ?>

.fa-beat {
	animation: fa-beat 5s ease infinite;
	color: #ed6b00;
}

#h2g2wizard {
	margin: 0 10px 10px 0;
	position: fixed;
	bottom: 0;
	right: 0;
	z-index: 1500;
	display: flex;
	align-items: center;
	flex-direction: column;
	cursor: pointer;
}

#h2g2wizard .bubble {
	transform: translatey(0px);
	-webkit-animation: float 3s ease-in-out infinite;
	animation: float 3s ease-in-out infinite;
	mix-blend-mode: multiply;
	text-align: center;
	/*text-transform: uppercase;*/
	font-weight: bold;
	letter-spacing: 3px;
	font-size: 15px;
	color: #fc8635;
	background-color: #FFF;
	opacity: 0.9;
	padding: 30px;
	border-radius: 11px;
	position: relative;
	box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
	font-family: "Baloo 2", cursive;
	max-width: 300px;
}

#h2g2wizard lottie-player {
	width: 200px;
	height: 200px;
}

.h2g2wizard__close {
	width: 100%;
	display: flex;
	justify-content: flex-end;
	padding: 10px;
	-webkit-animation: float 3s ease-in-out infinite;
	animation: float 3s ease-in-out infinite;
}

.h2g2wizard__close-circle {
	background-color: #FFF;
	opacity: 0.9;
	width: 30px;
	height: 30px;
	border-radius: 50%;
	display: flex;
	justify-content: center;
	align-items: center;
	box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

.h2g2wizard__close-circle:hover {
	background-color: #eaebee;
}

.h2g2wizard__close-icon {

}

@-webkit-keyframes float {
	0% {
		transform: translatey(0px);
	}
	50% {
		transform: translatey(-20px);
	}
	100% {
		transform: translatey(0px);
	}
}

@keyframes float {
	0% {
		transform: translatey(0px);
	}
	50% {
		transform: translatey(-20px);
	}
	100% {
		transform: translatey(0px);
	}
}
@-webkit-keyframes float2 {
	0% {
		line-height: 30px;
		transform: translatey(0px);
	}
	55% {
		transform: translatey(-20px);
	}
	60% {
		line-height: 10px;
	}
	100% {
		line-height: 30px;
		transform: translatey(0px);
	}
}
@keyframes float2 {
	0% {
		line-height: 30px;
		transform: translatey(0px);
	}
	55% {
		transform: translatey(-20px);
	}
	60% {
		line-height: 10px;
	}
	100% {
		line-height: 30px;
		transform: translatey(0px);
	}
}

/*
 * Multiselect button
 */
.h2g2multiselect {
	position: relative;
	display: flex;
}

.h2g2multiselect .h2g2multiselect_create {
	background: #8DB600;
}

.h2g2multiselect .h2g2multiselect_delete {
	background: #8c4446;
}

.h2g2multiselect .butAction.h2g2multiselect__button,
.h2g2multiselect .butActionRefused.h2g2multiselect__button {
	margin-right: 1px !important;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
	display: flex;
	align-items: center;
}

.h2g2multiselect .butAction.h2g2multiselect__button.without-action,
.h2g2multiselect .butActionRefused.h2g2multiselect__button.without-action {
	border-radius: 3px;
}

.h2g2multiselect .h2g2multiselect__button span,
.h2g2multiselect .h2g2multiselect__button i {
	margin-right: .2rem;
}

.h2g2multiselect .h2g2multiselect__chevron {
	margin-left: 0;
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}

.h2g2multiselect .without-action .h2g2multiselect__chevron {
	margin-left: 1rem;
}

.h2g2multiselect .h2g2multiselect__chevron i:first-child {
	transform: rotate(0deg);
	transition: transform .2s linear;
}

.h2g2multiselect .h2g2multiselect__chevron.up i:first-child {
	transform: rotate(180deg);
	transition: transform .2s linear;
}

.h2g2multiselect .h2g2multiselect__chevron.down i:first-child {
	transform: rotate(180deg);
	transition: transform .2s linear;
}
.h2g2multiselect .h2g2multiselect__options {
	z-index: 2;
	position: absolute;
	/*right: 0;*/
	left: 0;
	margin-right: 1rem;

	min-width: 95%;
	width: max-content;
	max-width: 300px;

	display: flex;
	flex-direction: column;
	box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
	border-radius: 3px;
	background: white;
	visibility: visible;
	padding: 1rem;
	opacity: 1;
	font-size: 1rem !important;
	cursor: pointer;
}

.h2g2multiselect .h2g2multiselect__options.optionsup {
	bottom: 45px;
	transition: bottom .5s, opacity .2s linear;
}

.h2g2multiselect .h2g2multiselect__options.optionsup.hidden {
	visibility: hidden;
	height: 90px;
	opacity: 0;
	bottom: 0;
}

.h2g2multiselect .h2g2multiselect__options.optionsdown {
	top: 45px;
	transition: top .5s, opacity .2s linear;
}

.h2g2multiselect .h2g2multiselect__options.optionsdown.hidden {
	visibility: hidden;
	height: 90px;
	opacity: 0;
	top: 0;
}

.h2g2multiselect .h2g2multiselect__options-entry {
	display: flex;
	margin: .5rem 0;
	align-items: center;
	text-align: left;
}

.h2g2multiselect .h2g2multiselect__options-entry_label {
	flex: 1;
	font-size: 1rem;
	display: flex;
	justify-content: flex-start;
	align-items: center;
	padding-left: 0.5em !important;
	white-space: collapse;
	& > a > i {
		margin-right: 1em;
	}
}

.h2g2multiselect .h2g2multiselect__options-entry_label a {
	text-decoration: none;
	color: black;
}

/* Disable state */
.h2g2multiselect .h2g2multiselect__options-entry.disabled {
	opacity: .5;
}

.h2g2multiselect .disabled .h2g2multiselect__options-entry_label a:hover {
	cursor: not-allowed;
}

.h2g2multiselect__options > div:hover {
	background-color: var(--colorbackhmenu1);
	border-radius: 0.25em;
	a {
		color: var(--colortextbackhmenu);
	}
}

.swal2-content.h2g2-news-content:not(.swal2-html-container) {
	max-height: 50vh;
	overflow-x: auto;
	background: color-mix(in srgb, currentColor 5%, transparent);
	border-radius: 5px;
	padding: 1rem;
	text-align: unset;

	img {
		max-width: 100%;
		max-height: 50vh;
		overflow-y: hidden;
	}
}

/* #120 */
@media ( (hover: none) and (pointer: coarse) ), (orientation: portrait) {
    .scroll-btn {
        bottom: 60px !important;
    }
}

.scroll-btn {
	position: fixed;
	bottom: 20px;
	right: 20px;
	background-color: var(--colorbackhmenu1);
	color: white;
	padding: 10px;
	border-radius: 5px;
	cursor: pointer;
	z-index: 1000;
	display: none;
}
.scroll-btn:hover {
	filter: brightness(1.2);
}

div.h2g2-news-content > div.div-btn-scroll {
	position: absolute;
	bottom: 80px;
	right: 20px;
	display:flex;
	flex-direction: column;

	& > * {
		background-color: var(--colorbackhmenu1);
		opacity: 0.4;
	}

	& > *:hover {
		opacity: 1;
	}
}

.h2g2tooltip {
    position: relative;
    width: fit-content !important;
}

.h2g2tooltip .h2g2tooltiptext {
    visibility: hidden;
    opacity: 0;
    transition: visibility 0.3s linear, opacity 0.3s linear;
    position: absolute;
    z-index: 1000;
    background-color: #fff;
    border-radius: 5px;
    padding: 0.8rem;
    text-align: center;

    &.h2g2top {
        left: 50%;
        bottom: 150%;
        transform: translate(-50%, 0);

        &::before,
        &::after {
            content: "";
            position: absolute;
            bottom: -7px;
            left: 50%;
            margin-left: -8px;
            border-top: 8px solid #fff;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 0;
        }

        &::before {
            border-top-color: #ccc;
            bottom: -8px;
        }
    }

    &.h2g2bottom {
        left: 50%;
        top: 150%;
        transform: translate(-50%, 0);

        &::before,
        &::after {
            content: "";
            position: absolute;
            top: -7px;
            left: 50%;
            margin-left: -8px;
            border-bottom: 8px solid #fff;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 0;
        }

        &::before {
            border-bottom-color: #ccc;
            top: -8px;
        }
    }

    &.h2g2left {
        top: 50%;
        right: 150%;
        transform: translate(0, -50%);

        &::before,
        &::after {
            content: "";
            position: absolute;
            right: -7px;
            top: 50%;
            margin-top: -8px;
            border-left: 8px solid #fff;
            border-right: 0;
            border-bottom: 8px solid transparent;
            border-top: 8px solid transparent;
        }

        &::before {
            border-left-color: #ccc;
            right: -8px;
        }
    }

    &.h2g2right {
        top: 50%;
        left: 150%;
        transform: translate(0, -50%);

        &::before,
        &::after {
            content: "";
            position: absolute;
            left: -7px;
            top: 50%;
            margin-top: -8px;
            border-right: 8px solid #fff;
            border-left: 0;
            border-bottom: 8px solid transparent;
            border-top: 8px solid transparent;
        }

        &::before {
            border-right-color: #ccc;
            left: -8px;
        }
    }
}

.h2g2tooltip:hover > .h2g2tooltiptext {
    visibility: visible;
    opacity: 1;
}