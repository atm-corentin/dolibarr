<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Thomas Bacheley <thomas@code42.fr>
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
 *    \file       article_card.php
 *        \ingroup    lareponse
 *        \brief      Page to create/edit/view article
 */

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/lareponse/class/article.class.php');
dol_include_once('/lareponse/class/comment.class.php');
dol_include_once('/lareponse/class/tag.class.php');
dol_include_once('/lareponse/class/export.class.php');
dol_include_once('/lareponse/lib/lareponse_favorites.lib.php');
dol_include_once('/lareponse/lib/lareponse_article.lib.php');
dol_include_once('/h2g2/lib/h2g2.lib.php');

global $langs, $conf, $user, $db, $hookmanager;

// Load translation files required by the page
$langs->loadLangs(array("lareponse@lareponse", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'articlecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
//$lineid   = GETPOST('lineid', 'int');
$tagIds = GETPOST('tag_ids', 'alpha');

$order = GETPOST('order');
if ($order == 'ASC' || $order == 'DESC')
	$_SESSION['order'] = $order;

// Initialize technical objects
$object = new Article($db);
$tag = new Tag($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->lareponse->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('articlecard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) $search[$key] = GETPOST('search_' . $key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
$accessForbidden = ((isset($user->socid) && $user->socid > 0) || (!($user->rights->lareponse->article->read) ?? false));
if ($accessForbidden) accessforbidden();

$permissiontoread = $user->rights->lareponse->article->read ?? false;
$permissiontoadd = $user->rights->lareponse->article->write ?? false; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
if (isset($user->rights->lareponse->article->delete)) {
	$permissiontodelete = ($user->rights->lareponse->article->delete ?? false) || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
}
$permissionnote = $user->rights->lareponse->article->write ?? false; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->lareponse->article->write ?? false; // Used by the include of actions_dellink.inc.php
$permissiontoeditother = $user->rights->lareponse->article->update_other ?? false;
$permissiontoexport = $user->rights->lareponse->article->export ?? false;
$permissiontopublish = $user->rights->lareponse->article->publish ?? false;
$permissiontoclose = $user->rights->lareponse->article->close ?? false;
$permissiontoopen = $user->rights->lareponse->article->open ?? false;
$upload_dir = $conf->lareponse->multidir_output[isset($object->entity) ? $object->entity : 1];

if ($permissiontoeditother) $permissiontoadd = 1;


if ((!doArticleAndCurrentEntityMatches($object) && $id) || !isArticleAccessible($id, $action)) accessforbidden();
if ((!doArticleAndCurrentEntityMatches($object) && $id)) accessforbidden();

if ($action == 'create' || $action == 'edit') {
	print '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>';
}

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($action == 'comment' && $_POST['comment_content']) {
	$newComment = new ArticleComment($db);
	$newComment->content = $_POST['comment_content'];
	$newComment->fk_article = $id;
	$newComment->entity = $conf->entity;
	$commentId = $newComment->create($user);
	if ($commentId > 0) setEventMessages($langs->trans('CommentPost'), '', 'mesgs');
	else setEventMessages($langs->trans('ErrorCommentPost'), '', 'errors');
	$action = '';
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

if ($action == 'delete_comment' && GETPOST('confirm') == 'yes') {
	$commentId = GETPOST('comment_id', 'int');
	if ($commentId > 0) {
		$deleteComment = new ArticleComment($db);
		$deleteComment->fetch($commentId);
		$res = $deleteComment->delete($user);
	} else $res = -1;
	if ($res > 0) setEventMessages($langs->trans('CommentDelete'), '', 'mesgs');
	else setEventMessages($langs->trans('ErrorCommentDelete'), '', 'errors');
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

// [#256] User can modify its comment
if ($action == 'modify_comment' && empty(GETPOST("cancel", "alphanohtml"))) {
	$commentId = GETPOST('comment_id', 'int');
	$commentContent = GETPOST('edit_comment_content', 'none');
	if ($commentId > 0 && !empty($commentContent)) {
		$modifyComment = new ArticleComment($db);
		$modifyComment->fetch($commentId);
		$modifyComment->content = $commentContent;
		$modifyComment->tms = dol_now();
		$res = $modifyComment->update($user);
	} else $res = -1;
	if ($res > 0) setEventMessages($langs->trans('LaReponseCommentModified'), '', 'mesgs');
	else setEventMessages($langs->trans('LaReponseCommentModifiedError'), '', 'errors');
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

if ($action == 'export' && $permissiontoexport) {
	$export = new LareponseExport();

	$ids = array((string) $id);
	if ($export->generateJsonFile($ids)) {
		if (!$export->downloadExportFile($ids)) {
			setEventMessages($langs->trans('ArticleFailureExport'), '', 'errors');
		}
	} else {
		setEventMessages($langs->trans('ArticleFailureExport'), '', 'errors');
	}
	$action = '';
}

if ($action == 'publish' && $permissiontopublish) {
	if ($object->generateArticleToken()) {
		setEventMessages('PublishSuccess', '', 'mesgs');
	} else {
		setEventMessages('PublishFail', '', 'errors');
	}
	$action = '';
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

if ($action == 'close' && $permissiontoadd) {
	if (GETPOST('id', 'int') > 0) {
		$object->fetch($id);
		$object->private = 2;
		$object->tms = dol_now();
		$object->update($user);
		setEventMessages('CloseArticleSuccess', '', 'mesgs');
	} else {
		setEventMessages('CloseArticleFailed', '', 'errors');
	}
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

if ($action == 'open' && $permissiontoadd) {
	if (GETPOST('id', 'int') > 0) {
		$object->fetch($id);
		$object->private = 0;
		$object->tms = dol_now();
		$object->update($user);
		setEventMessages('OpenArticleSuccess', '', 'mesgs');
	} else {
		setEventMessages('OpenArticleFailed', '', 'errors');
	}
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

if ($action == 'unpublish' && $permissiontopublish) {
	if ($object->removeArticleToken()) {
		setEventMessages('UnPublishSuccess', '', 'mesgs');
	} else {
		setEventMessages('UnPublishFail', '', 'errors');
	}
	$action = '';
	exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
}

//add an element into lareponse_favorites database

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/lareponse/article_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/lareponse/article_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');
		} else {
			exit(header('Location: ' . $backurlforlist));
		}
	} else if ($cancel && !empty($id)) {
		exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
		$action = '';
	}
	if ((float) DOL_VERSION < 9.0)
		$backtopage = $backurlforlist;

	$triggermodname = 'LAREPONSE_ARTICLE_MO'; // Name of trigger action code to execute when we modify record

	// Action add
	if ($action == 'add' && !empty($permissiontoadd)) {
		foreach ($object->fields as $key => $val) {
			if ($object->fields[$key]['type'] == 'duration') {
				if (GETPOST($key . 'hour') == '' && GETPOST($key . 'min') == '') continue; // The field was not submited to be edited
			} else {
				if (!GETPOSTISSET($key)) continue; // The field was not submited to be edited
			}
			// Ignore special fields
			if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;

			// Set value to insert
			if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
				$value = GETPOST($key, 'none');
			} elseif ($object->fields[$key]['type'] == 'date') {
				$value = dol_mktime(12, 0, 0, GETPOST($key . 'month', 'int'), GETPOST($key . 'day', 'int'), GETPOST($key . 'year', 'int'));
			} elseif ($object->fields[$key]['type'] == 'datetime') {
				$value = dol_mktime(GETPOST($key . 'hour', 'int'), GETPOST($key . 'min', 'int'), 0, GETPOST($key . 'month', 'int'), GETPOST($key . 'day', 'int'), GETPOST($key . 'year', 'int'));
			} elseif ($object->fields[$key]['type'] == 'duration') {
				$value = 60 * 60 * GETPOST($key . 'hour', 'int') + 60 * GETPOST($key . 'min', 'int');
			} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
				$value = price2num(GETPOST($key, 'none')); // To fix decimal separator according to lang setup
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
			if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value = ''; // This is an implicit foreign key field
			if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') $value = ''; // This is an explicit foreign key field

			$object->$key = $value;
			if ($val['notnull'] > 0 && $object->$key == '' && !is_null($val['default']) && $val['default'] == '(PROV)') {
				$object->$key = '(PROV)';
			}
			if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
			}
		}

		// Set Entity value
		$object->entity = $conf->entity;

		if (!$error) {
			$result = $object->create($user);
			if ($result > 0) {
				// Creation OK
				$tag->linkArticleTag($object->id, GETPOST('list_tag', 'array'));
				if ($tagIds == '' || $tagIds == null) {
					$urlToGo = dol_buildpath('/lareponse/article_card.php?id=' . $object->id . '&action=edit', 1); // Redirect to edit mode if creation was a success
				} else {
					$urlToGo = dol_buildpath('/lareponse/article_card.php?id=' . $object->id . '&action=edit&tag_ids=' . urlencode($tagIds), 1); // Redirect to edit mode if creation was a success
				}
				header("Location: " . $urlToGo);
				exit;
			} else {
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	}

	//Action update
	if ($action == 'update' && !empty($permissiontoadd)) {
		foreach ($object->fields as $key => $val) {
			$object->tms = dol_now();
			// Check if field was submited to be edited
			if ($object->fields[$key]['type'] == 'duration') {
				if (!GETPOSTISSET($key . 'hour') || !GETPOSTISSET($key . 'min')) continue; // The field was not submited to be edited
			} else {
				if (!GETPOSTISSET($key)) continue; // The field was not submited to be edited
			}
			// Ignore special fields
			//if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;

			// Set value to update
			if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
				$value = GETPOST($key, 'none');
				if ($object->type && !preg_match('/https?:\/\/(?:www\.)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?/', $value)) {
					setEventMessage($langs->trans('LaReponseContentNotAnURL'), 'errors');
					$error++;
				}
			} elseif ($object->fields[$key]['type'] == 'date') {
				$value = dol_mktime(12, 0, 0, GETPOST($key . 'month'), GETPOST($key . 'day'), GETPOST($key . 'year'));
			} elseif ($object->fields[$key]['type'] == 'datetime') {
				$value = dol_mktime(GETPOST($key . 'hour'), GETPOST($key . 'min'), 0, GETPOST($key . 'month'), GETPOST($key . 'day'), GETPOST($key . 'year'));
			} elseif ($object->fields[$key]['type'] == 'duration') {
				if (GETPOST($key . 'hour', 'int') != '' || GETPOST($key . 'min', 'int') != '') {
					$value = 60 * 60 * GETPOST($key . 'hour', 'int') + 60 * GETPOST($key . 'min', 'int');
				} else {
					$value = '';
				}
			} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
				$value = price2num(GETPOST($key, 'none'));    // To fix decimal separator according to lang setup
			} else {
				$value = GETPOST($key, 'alpha');
			}
			if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value = ''; // This is an implicit foreign key field
			if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') $value = ''; // This is an explicit foreign key field

			$object->$key = $value;
			if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
			}
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result > 0) {
				// Update OK
				$error = $tag->updateArticleTag($object->id, GETPOST('list_tag', 'array'));
				if ($error < 0) setEventMessages($langs->trans('ErrorArticleUpdate'), '', 'errors');
				else setEventMessages($langs->trans('ArticleUpdate'), '', 'mesgs');
				$action = 'view';
				exit(header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id));
			} else {
				// Creation KO
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'edit';
			}
		} else {
			$action = 'edit';
		}
	}

	// Actions cancel, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include dol_buildpath('/lareponse/lareponse_actions.php');

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'ARTICLE_MO');
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'ARTICLE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_ARTICLE_TO';
	$trackid = 'article' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);

$arrayofjs = array(
	"/lareponse/js/lareponse.js.php",
	"/lareponse/js/lareponse_global.js.php",
	"/lareponse/js/chosen.jquery.min.js",
	"/lareponse/js/mermaid.min.js"
);
$arrayofcss = array(
	"/lareponse/css/chosen.min.css",
	"/lareponse/css/wysiwyg.css",
    "/lareponse/css/lareponse_global.css.php"
);

llxHeader('', $langs->trans('ArticleOnglet') . ' ' . $object->title, '', '', 0, 0, $arrayofjs, $arrayofcss);

// Part to create
if ($action == 'create') {
	$title = '<i class="fa fa-newspaper-o" style="color:black;" aria-hidden="true"></i> ' . $langs->trans('lareponseNewArticle');
	print load_fiche_titre($title, '', '');
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ((float) DOL_VERSION >= 11) {
		print '<input type="hidden" name="token" value="' . newToken() . '">';
	} else {
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	}
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="bac	wyktopageforcancel" value="' . $backtopageforcancel . '">';
	print '<input type="hidden" name="tag_ids" value="' . $tagIds . '">';

	dol_fiche_head();
	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	foreach ($object->fields as $key => $val) {
		if (in_array($key, array('content', 'private'))) continue;

		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) continue;

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) continue;    // We don't want this field

		print '<tr id="field_' . $key . '">';
		print '<td';
		print ' class="titlefieldcreate';
		if ($val['notnull'] > 0) print ' fieldrequired';
		if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
		print '"';
		print '>';
		if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		else print $langs->trans($val['label']);
		print '</td>';
		print '<td>';
		if (in_array($val['type'], array('int', 'integer'))) $value = GETPOST($key, 'int');
		elseif ($val['type'] == 'text' || $val['type'] == 'html') $value = GETPOST($key, 'none');
		else $value = GETPOST($key, 'alpha');
		print $object->showInputField($val, $key, $value, '', '', '', 0);
		print '</td>';
		print '</tr>';
	}


	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div>' . $conf->global->LAREPONSE_WIZARD_ARTICLE_ASSISTANT . '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="javascript:history.go(-1)"') . '>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	$title = '<i class="fa fa-newspaper-o" style="color:black;" aria-hidden="true"></i> ' . $langs->trans("ArticleCard");
	print load_fiche_titre($title, '', '');
	print '<form id="requestForm" name="requestForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ((float) DOL_VERSION >= 11) {
		print '<input type="hidden" name="token" value="' . newToken() . '">';
	} else {
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	}
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
	$tagIdsArray = explode(',', $tagIds);

	print '<table class="border centpercent tableforfieldedit">' . "\n";

	// Common attributes
	// Common attributes
	//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	foreach ($object->fields as $key => $val) {
		if ($key == 'content') continue;
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) continue;
		if ($key === 'type') continue;
		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) continue;    // We don't want this field

		print '<tr><td';
		print ' class="titlefieldcreate';
		if ($val['notnull'] > 0) print ' fieldrequired';
		if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
		print '">';
		if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		else print $langs->trans($val['label']);
		print '</td>';
		print '<td>';
		if (in_array($val['type'], array('int', 'integer'))) $value = GETPOSTISSET($key) ? GETPOST($key, 'int') : $object->$key;
		elseif ($val['type'] == 'text' || $val['type'] == 'html') $value = GETPOSTISSET($key) ? GETPOST($key, 'none') : $object->$key;
		else $value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
		if ($key == 'private' && $object->fk_user_creat != $user->id) {
			print ($object->private == 0 ? $langs->trans('Public') : $langs->trans('Private'));
		} elseif (isset($val['noteditable']) && $val['noteditable']) print $object->showOutputField($val, $key, $value, '', '', '', 0);
		else print $object->showInputField($val, $key, $value, '', '', '', 0);
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';
	// Tag
	print '<tr>';
	print '<td class="fieldrequired">Tag</td>';
	$placeholder = $langs->trans('PlaceholderTags');
	$tag->printSelectTag('list_tag', $tagIdsArray, $placeholder, $id);
	print '</tr>';

	// Content
	if (!empty($object->type) && $object->type > 0) { // 1 -> 'iframe'
		print '<tr id="field_content">';
		print '<td class="titlefieldcreate fieldrequired tdtop">' . $langs->trans('LaReponseIframeUrlLabel') . '</td>';
		print '<td><input type="text" class="flat" style="min-width: 600px" name="content" id="content" maxlength="255" value="' . $object->content . '"></td>';
		print '</tr>';
	} else {
		print '<script src="' . dol_buildpath("/lareponse/js/cke_templates.js.php", 1) . '" type="text/javascript"></script>';
		print '<tr id="field_content">';
		print '<td class="titlefieldcreate fieldrequired tdtop">' . $langs->trans('LaReponseContent') . '</td>';
		print '</tr>';

		print '<tr id="tr_wysiwyg_editor">';
		print '<td colspan="2">';
		print '<div class="wysiwyg_' . $conf->global->LAREPONSE_WYSIWYG_MODE . '">';
		$fckEditorEnabled = isset($conf->fckeditor) ? $conf->fckeditor->enabled : false;
		$doleditor = new DolEditor('content', $object->content, '', 0, 'Full', 'In', true, true, $fckEditorEnabled, ROWS_7, '90%');
		$doleditor->Create();
		dol_fiche_end();
		print '</div>';
		print '</td></tr>';
	}

	print '</table>';

	$mainBtnSave = array(
		'href' => 'javascript:saveArticle()',
		'label' => $langs->trans('LaReponseContinue'),
		'picto' => '<i class="fas fa-save"></i>',
		'disabled' => !$permissiontoadd,
		'title' => $langs->trans('LaReponseContinueTooltip')
	);

	$entriesSave = array(
		array(
			'href' => 'javascript:saveArticle()',
			'label' => $langs->trans('LaReponseContinue'),
			'picto' => '<i class="fas fa-save"></i>',
			'disabled' => !$permissiontoadd,
			'title' => $langs->trans('LaReponseContinueTooltip')
		),
		array(
			'href' => 'javascript:$(`#requestForm`).submit()',
			'label' => $langs->trans('LaReponseSaveCard'),
			'picto' => '<i class="fas fa-eye"> </i>',
			'disabled' => !$permissiontoadd,
			'title' => $langs->trans('LaReponseSaveCardTooltip')
		),
		array(
			'href' => 'javascript:saveArticle(1)',
			'label' => $langs->trans('LaReponseSaveList'),
			'picto' => '<i class="fas fa-list"> </i>',
			'disabled' => !$permissiontoadd,
			'title' => $langs->trans('LaReponseSaveListTooltip')
		)
	);

	print '<div class="tabsAction ' . ($conf->theme == 'eldy' ? 'lareponse-tabs-action-eldy' : '') . '">';
	print buildMultiEntriesButton($mainBtnSave, $entriesSave);

	print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';

	if (($permissiontodelete ?? false) || ($object->fk_user_creat == $user->id)) {
		print '<a class="butActionDelete" href="javascript:callToAction(\'delete\', \'' . addslashes($langs->trans('ConfirmDeleteObject')) . '\', \'#d33\')">' . $langs->trans('Delete') . '</a>' . "\n";
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Delete') . '</a>' . "\n";
	}

	if ($object->private == 2) {
		if ($permissiontoopen) {
			print '<a class="butAction" href="javascript:callToAction(\'open\', \'' . addslashes($langs->trans('ConfirmOpenArticle')) . '\')">' . $langs->trans('Open') . '</a>' . "\n";
		} else {
			print '<a class="butAction classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Open') . '</a>' . "\n";
		}
	} else {
		if ($permissiontoclose) {
			print '<a class="butActionDelete" href="javascript:callToAction(\'close\', \'' . addslashes($langs->trans('ConfirmCloseArticle')) . '\')">' . $langs->trans('Close') . '</a>' . "\n";
		} else {
			print '<a class="butAction classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Close') . '</a>' . "\n";
		}
	}
	print '</div></div>';
	print '</form>';
	print "<script>
    $(document).ready(function () {
        $('#requestForm').on( 'keypress', function(event) { // disable #requestForm submit  (but not wysiwyg)
            if (event.keyCode === 13) event.preventDefault(); // 13 <-> enter key
           });
        })
    </script>";
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	// Basic display if we're not in print mode
	if ($optioncss !== 'print') {
		$head = articlePrepareHead($object);
		dol_fiche_head($head, 'card', $langs->trans("article"), -1, $object->picto);

		$formconfirm = '';

		// Call Hook formConfirm
		if (isset($lineid) && isset($formconfirm)) {
			$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
		}
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;

		// Object card
		// ------------------------------------------------------------
		$linkback = '<a href="' . dol_buildpath('/lareponse/article_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref = '<div class="refidno">';

		$morehtmlref .= '</div>';

		// Tag for banner tab
		$tagArticleList = $tag->getArticleTag($id);
		$tagList = $tag->getAllTag();

		// #214
		$tagDiv = '<span class="badge marginleftonlyshort ';
		switch ($object->private) {
			case 1:
				$tagDiv .= 'badge-status1">' . $langs->trans('Private');
				break;
			case 2:
				$tagDiv .= '" style="background: grey; color: var(--colortextbackhmenu);">' . $langs->trans('ArticleClosed');
				break;
			default:
				$tagDiv .= 'badge-status4">' . $langs->trans('Public');
				break;
		}
		$tagDiv .= '</span>';

		if (count($tagArticleList) > 6) $tagDiv .= '<span id="lareponse-tags-icon" class="fas fa-chevron-up"></span>';

		$tagDiv .= '<br><br><div id="tags-section">';

		foreach ($tagArticleList as $tagVal) {
			$tagId = intval($tagVal['id']);
			$tag->fetch($tagId);
			switch ($tagVal['type']) {
				case 0:
					$type = $langs->trans('product');
					break;
				case 1:
					$type = $langs->trans('supplier');
					break;
				case 2:
					$type = 'Tiers';
					break;
				case 3:
					$type = $langs->trans('member');
					break;
				case 4:
					$type = $langs->trans('contact');
					break;
				case 5:
					$type = $langs->trans('bank_account');
					break;
				case 6:
					$type = $langs->trans('project');
					break;
				case 7:
					$type = $langs->trans('user');
					break;
				case 12:
					$type = $langs->trans('Ticket');
					break;
				case 43:
					$type = 'LaReponse';
					break;
				default:
					$type = 'GestionParc';
			}
			$ways = $tag->print_all_ways(' &gt;&gt; <span class="fa fa-tag"></span> ', dol_buildpath('/lareponse/article_list.php', 1));
			$tagData = $ways[0];
			// Some colors added by categories module may be empty or missing a '#'. We're here changing the string so that it matches a #XXXXXX pattern or becomes black if empty
			mb_substr($tagVal['color'], 0, 1) == '#' ? $currcolor = $tagVal['color'] : $currcolor = '#' . $tagVal['color'];
			if (strlen($currcolor) == 1) {
				$currcolor .= '000000';
			}

			$sql = "SELECT fk_parent FROM " . MAIN_DB_PREFIX . "categorie";
			$sql .= " WHERE rowid = " . $tagVal['id'];
			$res = $db->query($sql);
			$obj = '';
			if ($res) $obj = $db->fetch_object($res);
			$db->free($res);

			$tagDiv .= '<div><a style="background-color: ' . $currcolor . '; color:' . (colorIsLight($currcolor) ? 'black' : 'white') . ' !important;" class="lareponse_tag" href="' . dol_buildpath('/lareponse/article_list.php', 1) . '?search_category_tag_list[]=' . $tagVal['id'] . ($obj->fk_parent > 0 ? '&search_category_tag_list[]=' . $obj->fk_parent . '&search_category_tag_operator=1' : '') . '"><span class="fa fa-tag"></span> ' . $tagData . ' (' . $type . ')</a></div>';
		}
		$tagDiv .= '</div>';

		if (!empty($conf->lareponse->enabled) && $user->rights->lareponse->article->read) {
			$obj = new Article($db);
			$obj->fetch($id);
			$createdBy = '';
			$createdBy .= '<table>';
			$createdBy .= '<tr><td>';
			$createdBy .= $langs->trans('CreatedBy');
			$commentUser = new User($db);
			$commentUser->fetch($obj->fk_user_creat);
			$createdBy .= activeContributorUrl($commentUser, 1);
			$createdBy .= '</td>';
			$createdBy .= '<td>' . $langs->trans('The') . ' ' . dol_print_date($obj->date_creation, 'dayhour', 'tzuserrel') . '</td>';
			$createdBy .= '</tr>';
			$createdBy .= '<tr><td>';
			$createdBy .= $langs->trans('UpdatedBy');
			$commentUser = new User($db);
			if ($obj->fk_user_modif > 0) $updatedContributorId = $obj->fk_user_modif;
			else $updatedContributorId = $obj->fk_user_creat;
			$commentUser->fetch($updatedContributorId);
			$createdBy .= activeContributorUrl($commentUser, 1);
			$createdBy .= '</td>';
			$createdBy .= '<td>' . $langs->trans('The') . ' ' . dol_print_date($obj->tms, 'dayhour', 'tzuserrel') . ' </td></tr></table>';
		}

		// [#106] Bigger image for article in banner tab
		$object->picto = 'article_50@lareponse';
		$object->next_prev_filter = 'entity= ' . $conf->entity;
		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'title', $tagDiv, '', 0, '', $createdBy);
		$object->picto = 'lareponse@lareponse';


		print'</div>';


		print '<div class="fichecenter c42-lr">';
		print '<div style="border: none" class="underbanner clearboth"></div>';

        // Table Of Contents with collapser
        // The actual ToC is generated afterward via javascript
        print '<div id="ToC">';
        print '<button id="lareponse-toc-collapse-button">';
        print '<span class="fas fa-chevron-right fa-lg"></span>';
        print '<span>' . $langs->trans("LaReponseToc") . '</span>';
        print '</button>';
        print '</div>';

		print '<div class="lareponse_article">';
		// This functions replaces all <br /> by ''
		if (!empty($object->type) && $object->type > 0) print '<iframe src="' . $object->content . '" frameborder="0" width="100%" height="800" allowtransparency ></iframe>';
		else print changeContentForMermaid($object->content);
		print '</div>';

		// Integrate mermaid on lareponse article
		print '<script src="' . dol_buildpath('lareponse/js/mermaid.js', 1) . '"></script>';
		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '<div class="clearboth"></div>';

		dol_fiche_end();


		/*
		 * Lines
		 */

		if ($action == 'delete_com') {
			print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id . '&comment_id=' . GETPOST('comment_id', 'int'), $langs->trans('DeleteComment'), $langs->trans('DeleteCommentMsg'), "delete_comment", null, 'no', 2, 370, 500, 0);
		}

		if (!empty($object->table_element_line)) {
			// Show object lines
			$result = $object->getLinesArray();

			print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid', 'int')) . '" method="POST">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
    	<input type="hidden" name="mode" value="">
    	<input type="hidden" name="id" value="' . $object->id . '">
    	';

			if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
				include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
			}

			print '<div class="div-table-responsive-no-min">';
			if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
				print '<table id="tablelines" class="noborder noshadow" width="100%">';
			}

			if (!empty($object->lines)) {
				$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
			}

			// Form to add new line
			if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
				if ($action != 'editline') {
					// Add products/services form
					$object->formAddObjectLine(1, $mysoc, $soc);

					$parameters = array();
					$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				}
			}

			if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
				print '</table>';
			}
			print '</div>';

			print "</form>\n";
		}

		// Comment
		$comment = new ArticleComment($db);

		print '<div class="underrefbanner clearboth"></div>';

		print '<div class="fichecenter">';

		print '<div class="titre"><h2 style="margin: 0.25em 0"><span id="lareponse-article-icon" class="fas fa-chevron-up"></span> ' . $langs->trans('CommentSpace') . '</h2></div>';

		$commentNbr = $comment->getCommentNbr($id);
		print '<div id="comment-section" class="tabBar">';
		print '<h4 style="margin: 0.5em 0px"><i class="far fa-comment-alt fa-1x"></i>&nbsp;&nbsp;' . $commentNbr . ' ' . $langs->trans('Comments') . '</h4>';

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="comment">';
		print '<div class="wysiwyg_' . $conf->global->LAREPONSE_WYSIWYG_MODE . '">';
		$fckEditorEnabled = isset($conf->fckeditor) ? $conf->fckeditor->enabled : false;
		$doleditor = new DolEditor('comment_content', $comment->content, '', 200, 'Full', 'In', true, true, $fckEditorEnabled, ROWS_7, '90%');
		$doleditor->Create();
		print '</div>';
		print '<br>';
		print '<input type="submit" class="button" name="post" value="Poster" style="float: right; z-index: var(--z_index80);">';
		print '<br>';
		print '</form>';

		// Comment List section
		if ($commentNbr > 0) {
			// Init comment index
			$commentLimit = getDolGlobalInt("LAREPONSE_COMMENT_NUMBER");
			if (empty($commentLimit) || $commentLimit < 3) $commentLimit = 5;
			$totalPage = (int) ceil($commentNbr / $commentLimit);
			$commentPage = GETPOST('comment_page', 'int');
			if (empty($commentPage) || $commentPage < 1) $commentPage = 1;
			if ($commentPage > ($commentNbr / $totalPage)) $commentPage = $totalPage;
			$commentOffset = ($commentLimit * $commentPage) - $commentLimit;
			$order = isset($_SESSION['order']) ? $_SESSION['order'] : 'ASC';
			$comments = $comment->getCommentWithPagination($id, $order, $commentOffset, $commentLimit);
			// Construct buttons to change page
			$commentIndex = "<form method='post' action='" . dol_buildpath($_SERVER['PHP_SELF'], 1) . "'>";
			$commentIndex .= "<input type='hidden' name='token' value='" . newToken() . "'>";
			$commentIndex .= "<input type='hidden' name='id' value='$id'>";

			$commentIndex .= "<div title='" . $langs->trans("LaReponseCommentPageSelectorInfo") . "'>";
			// Previous page
			if ($commentPage > 1) {
				$commentIndex .= "<a href='" . dol_buildpath($_SERVER['PHP_SELF'], 1) . "?id=$id&comment_page=" . ($commentPage - 1) . "'><span style='margin: 0 5px;' class='fa fa-chevron-left'></span></a> ";
			}
			$commentIndex .= "<input type='number' style='width: 40px;' name='comment_page' value='$commentPage' class='maxwidth20'>";
			$commentIndex .= " / " . $totalPage;

			if ($commentPage < $totalPage) {
				$commentIndex .= " <a href='" . dol_buildpath($_SERVER['PHP_SELF'], 1) . "?id=$id&comment_page=" . ($commentPage + 1) . "'><span style='margin: 0 5px;' class='fa fa-chevron-right'></span></a>";
			}
			$commentIndex .= "</div>";
			$commentIndex .= "</form>";

			// Print comments
			print '<div id="comments" data-limit="' . $commentLimit . '" data-total-comment="' . $commentNbr . '" data-order="' . ($_SESSION['order'] ?? "") . '" data-article-id="' . $id . '">';
			if (!empty($_SESSION['order']) && $_SESSION['order'] == 'DESC') print '<div>' . $langs->trans('FilterByDate') . '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&order=ASC"><i class="fa fa-sort-amount-up fa-rotate-180 fa-flip-vertical" aria-hidden="true"></i></a></div>';
			else print '<div>' . $langs->trans('FilterByDate') . '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&order=DESC"><i class="fa fa-sort-amount-down" aria-hidden="true"></i></i></a></div>';

			$commentCounter = 0;
			foreach ($comments as $res) {
				if ($action == "modify_com") $modifyCommentId = GETPOST("comment_id", "int");
				$commentUser = new User($db);
				$commentUser->fetch($res['author']);
				print '<div id="commentBox' . ($commentOffset + $commentCounter) . '" class="commentBox">';
				print '<table class="tableComment" style="width: 100%; margin: 0; padding: 0"><tbody>';
				print '<tr><td class="commentAuthor"><p>' . $commentUser->getNomUrl(-1, '', '', '', '', '', '', 'commentTitle') . '</p></td>';
				print '<td class="commentDate"><p style="text-align: end;">' . dol_print_date($db->jdate($res['date']), 'dayhour', 'tzuserrel');
				if (!empty($res['tms']) && ($res['tms'] != $res['date'])) print '<br><span style="text-align: end; color: #afafaf;">' . $langs->trans("LaReponseModified") . " - " . dol_print_date($db->jdate($res['tms']), 'dayhour', 'tzuserrel') . '</span>';
				print '</p></td></tr>';
				print '<tr><td class="commentContent"><p>';
				$modify = !empty($modifyCommentId) && $modifyCommentId == $res["rowid"];
				if ($modify) {
					print "<form  name='form-modify-comment' method='post' action='" . dol_buildpath($_SERVER['PHP_SELF'], 1) . "'>";
					print '<input type="hidden" name="token" value="' . newToken() . '">';
					print '<input type="hidden" name="action" value="modify_comment">';
					print '<input type="hidden" name="comment_id" value="' . $modifyCommentId . '">';
					print '<input type="hidden" name="id" value="' . $id . '">';
					$dolEditor = new DolEditor('edit_comment_content', $res['content'], '', 200, 'Full', 'In', true, true, $fckEditorEnabled, ROWS_7, '100%');
					$dolEditor->Create();
				} else {
					print changeContentForMermaid($res['content']);
				}
				print '</p></td></tr>';
				if ($modify) {
					print '<tr><td colspan="2">';
					print "<input type='submit' class='butAction' name='confirm' value='" . $langs->trans("Save") . "'> ";
					print "<input type='submit' class='butAction' name='cancel' value='" . $langs->trans("Cancel") . "'>";
					print "</td></tr></form>";
				}
				if ($res['author'] == $user->id && !$modify) {
					print '<tr><td></td><td style="text-align: end" class="commentDeleteLink">';
					print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=modify_com&comment_id=' . $res['rowid'] . '" style="color: grey;"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a> ';
					print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=delete_com&comment_id=' . $res['rowid'] . '" style="color: red;"><i class="fas fa-trash-alt" aria-hidden="true"></i></a></td></tr>';
				}
				print '</tbody></table>';
				print '</div>';

				$commentCounter++;
			}
			print "<div style='float: right'>" . $commentIndex . "</div>";
			print '</div>';
		}

		print '<div id="commentLoader"><i class="fa fa-spinner fa-3x fa-spin"></i></div>';

		print '</div>';
		print '</div>';
		print '<div class="tabsAction tabs-article-multiselect">'; // [#147]
		// Buttons for actions
		if ($action != 'presend' && $action != 'editline' && ($object->fk_user_creat == $user->id || $permissiontoeditother)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			if (empty($reshook)) {
				// Send
				//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>'."\n";

				// Back to draft
				if ($object->status == $object::STATUS_VALIDATED) {
					if ($permissiontoadd) {
						print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_setdraft&confirm=yes">' . $langs->trans("SetToDraft") . '</a>';
					}
				}
				// Multiselect buttons
				displayMultiSelectButtonModify($object, $permissiontoadd);
				displayMultiSelectButtonPublish($object, $permissiontoread, $permissiontoexport, $user);
			}
		} else if ($permissiontoexport) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=export">' . $langs->trans('Export') . '</a>' . "\n";
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Export') . '</a>' . "\n";
		}

		// [#128] Display on the same line all buttons @Favorite
		print'<br><div>';


		print '<div style="width:100px;" href="#" id="cardFavStar" class="butAction" user-id="' . $user->id . '" article-id="' . $object->id . '">' . $langs->trans('LaReponseFavorite') . ' <i class="LaReponse fa fa-star ' . (favoriteExists($user->id, $object->id) == 1 ? 'isfavorite' : '') . '">';

		print'</i></div>'; // If the star icon is clicked, run the action "put_in_fav"
		print'</div>';

		print '</div>';
	} else {
		// We are in print mode, we only show the content if the user can read
		if ($permissiontoread) {
			print '<style>
                    div#id-right.open:not(.is-mobile) {
                        width: 100% !important;
                        & > .fiche {
                            display: flex;
                            justify-content: center;
                        }
                    }
                   </style>';

			print '<div class="fichecenter">';

			if (!empty($object->type) && $object->type > 0) print '<iframe src="' . $object->content . '" frameborder="0" width="100%" height="800" allowtransparency ></iframe>';
			else print changeContentForMermaid($object->content);

			// Integrate mermaid on lareponse article
			print '<script src="' . dol_buildpath('lareponse/js/mermaid.js', 1) . '"></script>';

			print '</div>';
		} else {
			accessforbidden();
		}
	}
}

// End of page
llxFooter();
$db->close();
