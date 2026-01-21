<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020      Arthur Croix         <arthur@code42.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/modulebuilder/template/myobject_document.php
 *  \ingroup    mymodule
 *  \brief      Tab for documents linked to MyObject
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/lareponse/class/import.class.php');

// Get parameters
$sended = GETPOST('sendit');

// Load traductions files requiredby by page
$langs->loadLangs(array("mymodule@mymodule","companies","other"));

if (!$user->rights->lareponse->article->export) accessforbidden();

llxHeader('', $langs->trans("NewImport"));

print load_fiche_titre($langs->trans($langs->transnoentitiesnoconv("NewImport")), '', 'object_article_50.png@lareponse');

dol_fiche_head(array(), '');

print '<div class="fichecenter">';

print '<table width="100%" class="border">';

print '<div class="titre">'.$langs->trans('AskUserToInputImportFile').'</div><br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
if ((float) DOL_VERSION >= 11) {
	print '<input type="hidden" name="token" value="' . newToken() . '">';
} else {
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
}
print '<input type="file" name="userfile" class="btn-lareponse-article-import">';
print '<br><br>';
$out = (empty($conf->global->MAIN_UPLOAD_DOC)?' disabled':'');
print '<input type="submit" class="button" value="'.$langs->trans("AddFile").'"'.$out.' name="sendit">';

$uploaddir = $conf->lareponse->dir_temp;
if (isset($_FILES["userfile"])) {
	$uploadfile = $uploaddir.'/'.basename($_FILES["userfile"]["name"]);
}
$uploadsuccess = false;
$zip = new ZipArchive();
$import = new LareponseImport();

// Upload input file
if ($sended) {
	if ($_FILES["userfile"]["name"]) {
		// Verify if the input file is a zip one
		if (substr($_FILES["userfile"]["name"], -4) == '.zip') {
			dol_mkdir($uploaddir);
			// Upload and move the file to the temporary directory
			if (move_uploaded_file($_FILES["userfile"]["tmp_name"], $uploadfile)) {
				$uploadsuccess = true;
			} else {
				setEventMessages($langs->trans('FileNotSupported'), '', 'errors');
			}
		} else {
			setEventMessages($langs->trans('WrongImportFileZip'), '', 'errors');
		}
		$filetoimport = '';
		$sended = '';
	}
}

// Decode and load in database uploaded file
if ($uploadsuccess == true) {
	$filename = $_FILES["userfile"]["name"];
	$filecontent = array();
	$zip->open($uploadfile);
	$zip->extractTo($uploaddir);
	// Delete the temporary zip folder
	unlink($uploadfile);

	// Retrieve all files without hidden ones
	$dirs = preg_grep('/^([^.])/', scandir($uploaddir));
	foreach ($dirs as $dir) {
		// Get article id from folder's name
		$articleid = (int) filter_var($dir, FILTER_SANITIZE_NUMBER_INT);
		$newid = $import->importJsonFile($uploaddir.'/'.$dir.'/lareponse_article_'.$articleid.'_content.json');
		if ($newid > 0) {
			if (($res = $import->importFolders($uploaddir.'/'.$dir, $newid))) {
				setEventMessages($langs->trans('SuccessfullyUploaded', $_FILES["userfile"]["name"]), '', 'mesgs');
			}
		} else if ($newid == -1) {
			setEventMessages($langs->trans('FailedToReadImport'), '', 'errors');
		}
		if ($newid == -2 || !$res) {
			setEventMessages($langs->trans('FailedToUpload', $_FILES["userfile"]["name"]), '', 'errors');
		}
	}
}
print '</form></div>';


llxFooter();
$db->close();
