<?php
/* Copyright (C) 2024	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       /multicompany/core/ajax/redirect.php
 *       \brief      File to switch in new browser tab
 */

session_destroy();

$res = @include("../../../main.inc.php");	// For root directory
if (empty($res) && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php")) {
	$res = @include($_SERVER['DOCUMENT_ROOT']."/main.inc.php");	// Use on dev env only
}
if (empty($res)) {
	$res = @include("../../../../main.inc.php");	// For "custom" directory
}

global $db, $conf;

if (empty($conf->multicompany->enabled)) {
	$db->close();
	http_response_code(403);
}
if (!getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL')) {
	$db->close();
	http_response_code(403);
}

$newentity = GETPOST('entity', 'int');

if (!empty($newentity)) {

	$_SESSION["dol_entity"] = (int) $newentity;

    $object = new ActionsMulticompany($db);
    $object->getInfo($newentity);
    $object->switchEntity((int) $newentity);

    $_SESSION["dol_company"] = getDolGlobalString("MAIN_INFO_SOCIETE_NOM");
    $_SESSION["dol_screenwidth"] = '1920';
    $_SESSION["dol_screenheight"] = '929';
    $_SESSION["mainmenu"] = 'home';
    $_SESSION["leftmenuopened"] = 'home';
    $_SESSION["leftmenu"] = 'home';

    header('Location: '.$_SERVER['REQUEST_SCHEME'].'://'.$object->url.'/');

    exit;
} else {
	$db->close();
	http_response_code(403);
}
