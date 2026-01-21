<?php

/* Copyright (C) 20225    Thomas BACHELEY  <thomas@code42.fr>
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
 *       \file       h2g2newsindex.php
 *        \ingroup    h2g2
 *        \brief      page to get article content if defined
 */

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

dol_include_once('/core/lib/admin.lib.php');

global $db, $langs;

/*
 * Actions
 */

$h2g2MainMenu = dolibarr_get_const($db, 'H2G2_MAINMENU');
$mainMenu = GETPOST('mainmenu');

if (!empty($h2g2MainMenu) && $h2g2MainMenu != $mainMenu) {
	header("Location: ".$_SERVER["PHP_SELF"] . '?mainmenu=' . $h2g2MainMenu);
	exit;
}

/*
 * View
 */

if ($articleId = getDolGlobalString("H2G2_ARTICLE_INDEX_PAGE")) {
	$resql = $db->query("SELECT title, content, type FROM " . MAIN_DB_PREFIX . "lareponse_article WHERE rowid = " . $articleId);
	if ($resql) {
		llxHeader("", $langs->trans("LareponseArea"));
		$article = $db->fetch_object($resql);

		$title = '<span>' . $article->title . '</span>&nbsp;<a target="_blank" class="fa fa-external-link-alt" href="' . dol_buildpath('/lareponse/article_card.php?id=' . $articleId, 1) . '"></a>';

		print load_fiche_titre($title, '', 'object_lareponse62@lareponse');

		print '<div class="fichecenter" style="-webkit-box-shadow: 2px 2px 5px 2px #cdcdcd; padding: 2px 20px; border-radius: 10px;">';
		if ($article->type) print '<iframe src="' . $article->content . '" frameborder="0" width="100%" height="800" allowtransparency ></iframe>';
		else print $article->content;
		print '</div>';
	}
} else {
	header('Location: ' . dol_buildpath('/comm/action/list.php?contextpage=actioncommlist&search_actioncode=c42_news&idmenu=66&mainmenu=agenda&leftmenu=', 2));
	exit;
}


// End of page
llxFooter();
$db->close();
