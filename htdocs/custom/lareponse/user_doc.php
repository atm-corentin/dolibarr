<?php
/*
 * Copyright (C) 2018-2019 David Moyon              <david@code42.fr>
 * Copyright (C) 2018-2019 Adam Gendre              <adam@code42.fr>
 * Copyright (C) 2019-2020 Fabien Fernandes Alves   <fabien@code42.fr>
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
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) { $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) { $res=@include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php")) { $res=@include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php")) { $res=@include "../../../main.inc.php";
}
if (! $res) { die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/lareponse/lib/lareponse.lib.php');

llxHeader();

$head = generateLareponseDocumentationHeader();

global $langs;

dol_fiche_head($head, 'userdoc', $langs->trans('Lareponse'), 0, 'lareponse_black@lareponse');

// Define path to file USER_DOC.md.
// First check USER_DO-la_LA.md then USER_DO.md
$filefound = false;
$pathoffile = dol_buildpath('/lareponse/USER_DOC'.$langs->defaultlang.'.md', 0);
if (dol_is_file($pathoffile)) {
	$filefound = true;
}
if (!$filefound) {
	$pathoffile = dol_buildpath('/lareponse/USER_DOC.md', 0);
	if (dol_is_file($pathoffile)) {
		$filefound = true;
	}
}

if ($filefound) {     // Mostly for external modules
	$content = file_get_contents($pathoffile);

	if ((float) DOL_VERSION >= 6.0) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';
		$content = dolMd2Html($content, 'parsedown', array('doc/'=>dol_buildpath('/lareponse/doc/', 1)));
	} else {
		$content = nl2br($content);
	}
}

print load_fiche_titre($langs->trans('UserDocTitle'), '', 'title_setup');
print '<div class="tabBar forceListStyle">';
if (isset($content)) echo $content;
echo '</div></div>';

llxFooter();
