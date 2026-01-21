<?php

/* Copyright (C) 2022 Clément DA-PURIFICACAO <clement@code42.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       set_news_viewed_user.php
 *        \ingroup    h2g2
 *        \brief      Page to set latest news viewed by user
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');                    // Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');                // Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');                // Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');                // Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');        // Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');        // Do not check injection attack on POST parameters
if (! defined('NOCSRFCHECK')) {              define('NOCSRFCHECK', '1');                    // Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
}
if (! defined('NOTOKENRENEWAL')) {           define('NOTOKENRENEWAL', '1');                // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
}
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');                // Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');                // If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');                // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');                 // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');                        // If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');                    // Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');                    // Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');        // Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);        // The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');                    // Disable all Content Security Policies


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) { $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php";
}
if (!$res) { die("Include of main fails");
}

global $langs, $user, $db;

$langs->load('h2g2@h2g2');

// Load variables
$user_id = GETPOST('id', 'alphanohtml');
$news_id = GETPOST('news_id', 'alphanohtml');
// #55 -> check if user have "news", if not -> insert
$res = $db->query('SELECT rowid FROM ' . MAIN_DB_PREFIX . 'user_extrafields WHERE fk_object = ' . $user_id);
if ($db->num_rows($res) == 0) $db->query("INSERT INTO " . MAIN_DB_PREFIX . "user_extrafields (fk_object, tms, news_viewed) VALUES (" . $user_id . ",'" . $db->escape($db->idate(dol_now())) . "', 0)");
// Set latest news viewed by user
$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'user_extrafields SET news_viewed = 1 WHERE fk_object=' . $user_id;

$res = $db->query($sql);

// Set latest news viewed by user
$sql_news = 'UPDATE ' . MAIN_DB_PREFIX . 'actioncomm SET percent = 100 WHERE id=' . $news_id;

$res_news = $db->query($sql_news);

if ($res > 0 && $res_news > 0) {
	$ret = array(
		'status' => 200,
		'success' => true
	);
} else {
	$ret = array(
		'status' => 500,
		'success' => false
	);
}

echo json_encode($ret);
