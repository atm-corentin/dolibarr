<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
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
 * \file    h2g2/admin/news.php
 * \ingroup h2g2
 * \brief   News page of module H2G2.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) { $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php";
}
if (!$res) { die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once '../lib/h2g2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

global $langs, $db, $user, $conf;

// Translations
$langs->loadLangs(array("errors", "admin", "h2g2@h2g2"));

// Access control
if (!$user->admin && empty($user->rights->h2g2->news->access)) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
	'Nouveauté' => array('type' => 'text', 'enabled' => 1, 'title' => '', 'content' => '')
);

// number of h2g2 module (448300) + 12.. +13.. see @modH2G2.class.php
$codes = array(
	448312 => $langs->trans("H2G2NewsLevelSuccess"),
	448313 => $langs->trans("H2G2NewsLevelInformation"),
	448314 => $langs->trans("H2G2NewsLevelWarning"),
	448315 => $langs->trans("H2G2NewsLevelImportant"),
);

/*
 * Actions
 */

if ($action == 'create_popup') {
	// Remove " character added by json_encode on the beginning and end of the strings
	$title = substr(json_encode(GETPOST('title', 'none')), 1, -1);
	$popupContent = substr(json_encode(html_entity_decode(GETPOST('popup_content', 'none'))), 1, -1);
	$articleId = GETPOST('article_popup', 'int');

	if (empty($title) && empty($popupContent) && $articleId == -1 ) {
		setEventMessage($langs->trans('H2G2NewsNotAdded'), 'errors');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}

	$actioncomm = new ActionComm($db);

	$actioncomm->userownerid = $user->id;
	$actioncomm->label = $title;
	$actioncomm->note = $popupContent;
	$actioncomm->type_code = 448310;
	if ($articleId > 0) {
		$actioncomm->fk_element = $articleId;
		$actioncomm->elementtype = 'article@lareponse';
	}

	$res = $actioncomm->create($user);

	// Request to set all users news viewed at 0
	$sql = "UPDATE " . MAIN_DB_PREFIX . "user_extrafields SET news_viewed = 0";
	$sql .= " WHERE news_viewed = 1";
	$sql .= " AND !isnull (news_viewed)";
	$resql_users = $db->query($sql, 0, 'ddl');

	if ($res > 0 && $resql_users) {
		setEventMessage($langs->trans('H2G2NewsAdded'), 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessage($langs->trans('H2G2NewsNotAdded'), 'errors');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
} elseif ($action == 'create_topbar') {
	$title = GETPOST('title', 'none');
	$topBarContent = GETPOST('topbar_content', 'none');
	$actioncomm_code = GETPOST('actioncomm_code', 'none');

	if (empty($title) && empty($topBarContent)) {
		setEventMessage($langs->trans('H2G2NewsNotAdded'), 'errors');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}


	// News Date Start
	$dateStartHours = GETPOST('datestarthour');
	$dateStartMinutes = GETPOST('datestartmin');
	$dateStartDay = GETPOST('datestartday');
	$dateStartMonth = GETPOST('datestartmonth');
	$dateStartYear = GETPOST('datestartyear');

	$dateStart = dol_mktime($dateStartHours, $dateStartMinutes, 0, $dateStartMonth, $dateStartDay, $dateStartYear, 'tzuserrel'); // to start at xx:xx:00

	// News Date End
	$dateEndHours = GETPOST('dateendhour');
	$dateEndMinutes = GETPOST('dateendmin');
	$dateEndDay = GETPOST('dateendday');
	$dateEndMonth = GETPOST('dateendmonth');
	$dateEndYear = GETPOST('dateendyear');
	$dateEnd = dol_mktime($dateEndHours, $dateEndMinutes, 59, $dateEndMonth, $dateEndDay, $dateEndYear, 'tzuserrel'); // to end at xx:xx:59

	$actioncomm = new ActionComm($db);
	$actioncomm->userownerid = $user->id;
	$actioncomm->label = $title;
	$actioncomm->note = $topBarContent;
	$actioncomm->type_code = $actioncomm_code;
	$actioncomm->datep = $dateStart;
	$actioncomm->datef = $dateEnd;

	$res = $actioncomm->create($user);

	if ($res > 0) {
		setEventMessage($langs->trans('H2G2NewsAdded'));
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessage($langs->trans('H2G2NewsNotAdded'), 'errors');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
} elseif ($action == 'set_article_mainmenu_page') {
	$articleId = GETPOST('article_page', 'int');
	$h2g2MainMenu = GETPOST('h2g2mainmenu', 'string');

	if ($articleId > 0) {
		if (dolibarr_set_const($db, 'H2G2_ARTICLE_INDEX_PAGE', $articleId, 'chaine', 1, '', $conf->entity) > 0) {
			setEventMessage($langs->trans('H2G2ArticlePageAdded'));
		} else {
			setEventMessage($langs->trans('H2G2ArticlePageNotAdded'), 'errors');
		}
	} else {
		dolibarr_del_const($db, 'H2G2_ARTICLE_INDEX_PAGE', $conf->entity);
		setEventMessage($langs->trans('H2G2ArticlePageRemove'));
	}

	if (dolibarr_set_const($db, 'H2G2_MAINMENU', $h2g2MainMenu, 'chaine', 1, '', $conf->entity) > 0) {
		setEventMessage($langs->trans('H2G2MainMenuPageSet'));
	} else {
		setEventMessage($langs->trans('H2G2MainMenuPageNotSet', 'errors'));
	}

	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}

$form = new Form($db);

if ($conf->lareponse->enabled) {
	$articles = array();
	$sql = "SELECT rowid, title, type FROM " . MAIN_DB_PREFIX . "lareponse_article WHERE private <> 1 AND entity IN (0, " . $conf->entity . ") ORDER BY type ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ($article = $db->fetch_object($resql)) {
			$articles[$article->rowid] = dol_trunc('[' . ($article->type ? 'Iframe' : 'Article') . '] ' .$article->title);
		}
	}
}

/*
 * View
 */

$page_name = "H2G2NewsTab";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_h2g2@h2g2');

// Configuration header
$head = h2g2AdminPrepareHead();
dol_fiche_head($head, 'news', '', 0, 'h2g2@h2g2');

dol_include_once('/h2g2/core/modules/modH2G2.class.php');
$tmpmodule = new modH2G2($db);

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("H2G2SetupPage") . '</span><br><br>';

print '<h4>' . $langs->trans('H2G2TopBarTitle') . '</h4>';
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?action=create_topbar">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
print '<td>' . $langs->trans('H2G2NewsLevel') . '</td>';
print '<td>' . $form->selectarray('actioncomm_code', $codes) . ' ';
print '<span class="badge badge-status" style="margin: 0 1em; background: var(--top-info-success); color: var(--top-info-success--text)">' . $langs->trans("H2G2NewsLevelSuccess") .'</span>';
print '<span class="badge badge-status" style="margin-right: 1em; background: var(--top-info-utility); color: var(--top-info-utility--text)">' . $langs->trans("H2G2NewsLevelInformation") .'</span>';
print '<span class="badge badge-status" style="margin-right: 1em; background: var(--top-info-warning); color: var(--top-info-warning--text)">' . $langs->trans("H2G2NewsLevelWarning") .'</span>';
print '<span class="badge badge-status" style="margin-right: 1em; background: var(--top-info-danger); color: var(--top-info-danger--text)">' . $langs->trans("H2G2NewsLevelImportant") .'</span>';
print '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>' . $langs->trans('H2G2NewsTitle') . '</td>';
print '<td><input type="text" name="title" maxlength="128" style="width: 50%" class="flat"></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>' . $langs->trans('DateAndHour') . '</td>';
print '<td>';
print $langs->trans('From') . $form->selectDate(dol_now(), 'datestart', 1, 1, 0, '', -1, 0, 0, '', '', '', '', '', '', '', 'tzuserrel') . '&nbsp;&nbsp;&nbsp;';
print $langs->trans('To') . $form->selectDate(strtotime('+48 hours', dol_now()), 'dateend', 1, 1, 0, '', -1, 0, 0, '', '', '', '', '', '', '', 'tzuserrel');
print '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>' . $langs->trans('H2G2NewsContent') . '</td>';
print '<td>';
$doleditor = new DolEditor('topbar_content', '', '', '142', 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td>';
print '</tr>';
print '<tr><td colspan="2"><input class="button button-save" type="submit" value="' . $langs->trans("Save") . '"></td></tr>';
print '</table>';
print '</form>';
print '<br>';

print '<h4>' . $langs->trans('H2G2PopupTitle') . '</h4>';
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?action=create_popup">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<table class="noborder centpercent">';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("H2G2NewsTitle") . '</td>';
print '<td><input type="text" name="title" maxlength="128" style="width: 50%" class="flat"></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>' . $langs->trans("H2G2NewsContent") . '</td>';
print '<td>';
$doleditor = new DolEditor('popup_content', '', '', '142', 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td>';
print '</tr>';
if ($conf->lareponse->enabled) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("H2G2NewsArticle") . '</td>';
	print '<td>' . $form->selectarray('article_popup', $articles, '', -1, 0, 0, '', 0, 0, 0, '', 'minwidth200') . '</td>';
	print '</tr>';
}
print '<tr><td colspan="2"><input class="button button-save" type="submit" value="' . $langs->trans("Save") . '"></td></tr>';
print '</table>';
print '</form>';

if ($conf->lareponse->enabled) {
	$articleId = getDolGlobalString("H2G2_ARTICLE_INDEX_PAGE") ?:'';
	$h2g2MainMenu = getDolGlobalString("H2G2_MAINMENU") ?: 'lareponse';
	print '<br>';
	print '<h4>' . $langs->trans('H2G2NewsPageTitle') . '</h4>';
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?action=set_article_mainmenu_page">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<table class="noborder centpercent">';
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("Article") . '</td>';
	print '<td>' . $form->selectarray('article_page', $articles, $articleId, -1, 0, 0, '', 0, 0, 0, '', 'minwidth200') . '</td>';
	print '</tr>';
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("H2G2MainMenuLabel") . '</td>';
	print '<td><input type="text" name="h2g2mainmenu" value="' . $h2g2MainMenu . '"></td>';
	print '</tr>';
	print '<tr><td colspan="2"><input class="button button-save" type="submit" value="' . $langs->trans("Save") . '"></td></tr>';
	print '</table>';
	print '</form>';
}

print '<div style="padding: 1em 0px">';
// [#41] Link to check events of news
print '<b>' . $langs->trans("H2G2NewsEvents") . '</b> : ';
print '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?contextpage=actioncommlist&search_actioncode=c42_news">' . $langs->trans("H2G2NewsPopup") . '</a>';
print '<span style="border-right: 3px solid var(--colortopbordertitle1); margin: 0px 6px"></span>';
print '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?contextpage=actioncommlist&search_actioncode=H2G2NewsSucc">' . $langs->trans("H2G2NewsSucc") . '</a>';
print '<span style="border-right: 3px solid var(--colortopbordertitle1); margin: 0px 6px"></span>';
print '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?contextpage=actioncommlist&search_actioncode=H2G2NewsInfo">' . $langs->trans("H2G2NewsInfo") . '</a>';
print '<span style="border-right: 3px solid var(--colortopbordertitle1); margin: 0px 6px"></span>';
print '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?contextpage=actioncommlist&search_actioncode=H2G2NewsWarn">' . $langs->trans("H2G2NewsWarn") . '</a>';
print '<span style="border-right: 3px solid var(--colortopbordertitle1); margin: 0px 6px"></span>';
print '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?contextpage=actioncommlist&search_actioncode=H2G2NewsDang">' . $langs->trans("H2G2NewsDang") . '</a>';
print '</div>';
// Page end
dol_fiche_end();
llxFooter();
$db->close();
