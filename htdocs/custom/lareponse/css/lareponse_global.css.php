<?php
/* Copyright (C) 2019 SuperAdmin
 * Copyright (C) 2025 Arthur Croix <arthur@code42.fr>
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
 * \file    lareponse/css/lareponse_global.css.php
 * \ingroup lareponse
 * \brief   CSS file for module Lareponse. This file can be used for lareponse articles as well as public articles
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

// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

@media only screen and (max-width: 1024px) {
    #ToC {
        display: none !important;
    }
}

#ToC {
    display: flex;

    > * {
        position: sticky;
        align-self: flex-start
    }

    &.lareponse-toc-closed {
        & #lareponse-toc-collapse-button span:first-child {
            transform: rotate(180deg);
        }

        & > ul {
            display: none;
        }
    }

    #lareponse-toc-collapse-button {
        margin-top: 1rem;
        padding: 1rem 0.5rem;
        height: fit-content;
        background-color: inherit;
        cursor: pointer;
        border: none;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        align-items: center;
        opacity: 0.5;

        span:first-child {
            transform: rotate(0deg);
            transition: 0.5s;
        }

        span:last-child {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            letter-spacing: 0.1rem;
            text-transform: uppercase;
        }
    }
    * {
        margin: 0;
        transition: all 0.5s;
        box-sizing: border-box;
    }
    a {
        &.current {
            border-left: solid 2px;
            opacity: 1;
        }
        position: relative;
        color: inherit;
        text-decoration: none;
        line-height: 1.25em;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0.25em;
        padding-left: 0.75em;
        opacity: 0.5;
        border-left: solid 2px;
        &:hover {
            opacity: 1;
            text-decoration: underline;
        }

        span {
            display: initial;
        }
    }
    ul {
        list-style: none;

        li {
            margin: 1px 0;
            position: relative;
        }
    }
    & > ul {
        padding: 1em;
        border-radius: 0.5em;
        backdrop-filter: blur(5px);
        width: clamp(250px, 15vw, 300px);
        ul {
            padding-left: 1em;
            border-left: solid 1px color-mix(in srgb, currentColor 20%, transparent);
            &:hover {
                border-left: solid 2px color-mix(in srgb, currentColor 100%, transparent);
            }
        }
    }
}
