<?php
/* Copyright (C) 2014-2024	Regis Houssin	<regis.houssin@inodbox.com>
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
 * 		\file       /multicompany/admin/import.php
 *		\ingroup    multicompany
 *		\brief      Page to setup import for Multicompany module
 */


$res=@include("../../main.inc.php");					// For root directory
if (empty($res) && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (empty($res)) $res=@include("../../../main.inc.php");		// For "custom" directory

dol_include_once('/multicompany/class/import.class.php');
dol_include_once('/multicompany/lib/multicompany.lib.php');
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

$langs->loadLangs(array('install', 'admin', 'multicompany@multicompany'));

// Security check
if (empty($user->admin) || !empty($user->entity)) {
	accessforbidden();
}

if (getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL') < 2) {
	accessforbidden();
}

$action	= GETPOST('action','alpha');
$step = (GETPOSTISSET('step') ? GETPOST('step','alpha') : 'database');
//var_dump($_POST); exit;

/*
 * Connect to database to import
 */

if ($action != "delvalue") {
	$dbimport = null;

	$dbname = getDolGlobalString('MULTICOMPANY_IMPORT_DB_NAME');
	$dbtype = getDolGlobalString('MULTICOMPANY_IMPORT_DB_TYPE');
	$dbhost = getDolGlobalString('MULTICOMPANY_IMPORT_DB_HOST');
	$dbport = getDolGlobalString('MULTICOMPANY_IMPORT_DB_PORT');
	$dbuser = getDolGlobalString('MULTICOMPANY_IMPORT_DB_USER');
	$dbpass = getDolGlobalString('MULTICOMPANY_IMPORT_DB_PASS');
	$toentity = getDolGlobalString('MULTICOMPANY_IMPORT_TO_ENTITY');
	$importkey = getDolGlobalString('MULTICOMPANY_IMPORT_KEY');

	define('MULTICOMPANY_IMPORT_DB_PREFIX', getDolGlobalString('MULTICOMPANY_IMPORT_DB_PREFIX'));

	if (!empty($dbname) && !empty($dbtype) && !empty($dbhost) && !empty($dbuser) && !empty($dbpass) && !empty($toentity) && !empty($importkey)) {
		$dbimport = getDoliDBInstance($dbtype, $dbhost, $dbuser, $dbpass, $dbname, (int) $dbport);
		if ($dbimport->error) {
			setEventMessages('ErrorConnectionToDatabaseFailed', null, 'errors');
		} elseif ($step == 'database') {
			setEventMessages('ConnectionToDatabaseWasSuccessful', null, 'mesgs');
		}

		$dbimport->prefix_db = constant('MULTICOMPANY_IMPORT_DB_PREFIX');
	}
}

/*
 * 	Action
 */

$error = 0;

// set database parameters
if ($action == "setvalue") {
	$res1 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_NAME', GETPOST('MULTICOMPANY_IMPORT_DB_NAME', 'alphanohtml'), 'chaine', 0, '', 0);
	$res2 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_TYPE', GETPOST('MULTICOMPANY_IMPORT_DB_TYPE', 'alphanohtml'), 'chaine', 0, '', 0);
	$res3 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_HOST', GETPOST('MULTICOMPANY_IMPORT_DB_HOST', 'alphanohtml'), 'chaine', 0, '', 0);
	$res4 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_PORT', GETPOST('MULTICOMPANY_IMPORT_DB_PORT', 'alphanohtml'), 'chaine', 0, '', 0);
	$res5 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_PREFIX', GETPOST('MULTICOMPANY_IMPORT_DB_PREFIX', 'alphanohtml'), 'chaine', 0, '', 0);
	$res6 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_USER', GETPOST('MULTICOMPANY_IMPORT_DB_USER', 'alphanohtml'), 'chaine', 0, '', 0);
	$res7 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_DB_PASS', GETPOST('MULTICOMPANY_IMPORT_DB_PASS', 'alphanohtml'), 'chaine', 0, '', 0);
	$res8 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_TO_ENTITY', GETPOST('MULTICOMPANY_IMPORT_TO_ENTITY', 'alphanohtml'), 'chaine', 0, '', 0);
	$res9 = dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_KEY', GETPOST('MULTICOMPANY_IMPORT_KEY', 'alphanohtml'), 'chaine', 0, '', 0);
	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0 || $res8 < 0 || $res9 < 0) {
		setEventMessages('ErrorFailedToSaveParameters', null, 'errors');
	} else {
		setEventMessages('ParametersSavedSuccessfully', null, 'mesgs');
	}
	Header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}
// delete database parameters
elseif ($action == "delvalue") {
	$res1 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_NAME', 0);
	$res2 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_TYPE', 0);
	$res3 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_HOST', 0);
	$res4 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_PORT', 0);
	$res5 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_PREFIX', 0);
	$res6 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_USER', 0);
	$res7 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_DB_PASS', 0);
	$res8 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_TO_ENTITY', 0);
	$res9 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_KEY', 0);
	$res10 = dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_USERS_READY', 0);
	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0 || $res8 < 0 || $res9 < 0 || $res10 < 0) {
		setEventMessages('ErrorFailedToDeleteParameters', null, 'errors');
	} else {
		setEventMessages('ParametersDeletedSuccessfully', null, 'mesgs');
	}
	Header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}
// set users id matching
elseif ($action == "setusers") {
	$oldusers = GETPOST('oldusers', 'array');
	$toselect = GETPOST('toselect','array');
	$toentity = (getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE') ? 1 : $toentity);

	if (!empty($oldusers)) {

		$db->begin();

		$import = new Import($db, $dbimport);
		$import->tablename = 'user';
		$ret = $import->delete();
		if ($ret < 0) {
			$error++;
		}

		if (empty($error)) {
			dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_USERS_READY', 0);

			foreach($oldusers as $olduserid) {

				if (!GETPOSTISSET('newuser_'.$olduserid)) {
					if (in_array($olduserid, $toselect)) {
						$ret = $import->copyTable($toentity, $importkey, $olduserid);
						if ($ret > 0) {
							$newuserid = $import->rows[$olduserid];
						} else {
							$error++;
							break;
						}
					}
				} else {
					$newuserid = GETPOST('newuser_'.$olduserid, 'int');
				}

				if (!empty($newuserid)) {
					$import->oldid = ((int) $olduserid);
					$import->newid = ((int) $newuserid);
					$ret = $import->create();
					if ($ret < 0) {
						$error++;
					}
				}
			}
		}

		if (empty($error)) {
			$db->commit();
			dolibarr_set_const($db, 'MULTICOMPANY_IMPORT_USERS_READY', 1, 'chaine', 0, '', 0);
			setEventMessages('UsersMatchingSavedSuccessfully', null, 'mesgs');
		} else {
			$db->rollback();
			setEventMessages('ErrorFailedToSaveUsersMatching', null, 'errors');
		}
	}
}
// delete users id matching
elseif ($action == "delusers") {
	$import = new Import($db, $dbimport);
	$import->tablename = 'user';
	$ret = $import->delete();
	if ($ret < 0) {
		$error++;
	}

	if (empty($error)) {
		dolibarr_del_const($db, 'MULTICOMPANY_IMPORT_USERS_READY', 0);
		setEventMessages('UsersMatchingDeletedSuccessfully', null, 'mesgs');
	} else {
		setEventMessages('ErrorFailedToDeleteUsersMatching', null, 'errors');
	}
}

/*
 *	View
 */

$form = new Form($db);

$arrayofcss = array(
	'/multicompany/css/import.css.php'
);

$arrayofjs = array(
	'/multicompany/core/js/lib_head.js'
);

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySetup"), $help_url, '', '', '', $arrayofjs, $arrayofcss);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"), $linkback, 'fa-globe', 0, 'multicompany_title');

$head = multicompany_prepare_head();
print dol_get_fiche_head($head, 'import', $langs->trans("ModuleSetup"), -1);

if (!empty($dbimport)) {
	$subhead = mcImportPrepareHead($dbimport);
	print dol_get_fiche_head($subhead, $step, $langs->trans("ImportStep"), -1);
}

if ($step == 'database') {
	dol_include_once('/multicompany/admin/tpl/import/database.tpl.php');
} elseif ($step == 'parameters') {
	dol_include_once('/multicompany/admin/tpl/import/parameters.tpl.php');
} elseif ($step == 'users') {
	dol_include_once('/multicompany/admin/tpl/import/users.tpl.php');
} elseif ($step == 'thirdparties' && getDolGlobalInt('MULTICOMPANY_IMPORT_USERS_READY')) {
	dol_include_once('/multicompany/admin/tpl/import/thirdparties.tpl.php');
} elseif ($step == 'products' && getDolGlobalInt('MULTICOMPANY_IMPORT_USERS_READY')) {
	dol_include_once('/multicompany/admin/tpl/import/products.tpl.php');
}

print dol_get_fiche_end();

llxFooter();
$db->close();
if (is_object($dbimport)) {
	$dbimport->close();
}
