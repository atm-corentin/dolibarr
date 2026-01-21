<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use h2g2\QueryBuilder;
use h2g2\QueryBuilderException;

dol_include_once('h2g2/class/querybuilder.class.php');

/**
 * \file    lib/lareponse_article.lib.php
 * \ingroup lareponse
 * \brief   Library files with common functions for article
 */

/**
 * Prepare array of tabs for article
 *
 * @param  article    $object    article
 * @return    array                    Array of tabs
 */
function articlePrepareHead($object)
{
	global $db, $langs, $conf;

	dol_include_once('/lareponse/class/comment.class.php');

	$langs->load("lareponse@lareponse");

	$h = 0;
	$head = array();

	$comment = new ArticleComment($db);
	$commentNbr = $comment->getCommentNbr($object->id);

	$head[$h][0] = dol_buildpath("/lareponse/article_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("ArticleOnglet");
	if ($commentNbr > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort classfortooltip" title="' . $commentNbr . ' ' . $langs->trans('Comments') . '">' . $commentNbr . '</span>';
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath('/lareponse/article_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbNote . '</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->lareponse->dir_output . "/article/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/lareponse/article_document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	$head[$h][2] = 'document';
	$h++;

	//Article Associated Tab
	dol_include_once('/lareponse/class/tag.class.php');

	$head[$h][0] = dol_buildpath("/lareponse/article_list.php", 1) . '?id=' . $object->id . '&type=article&search_category_tag_operator=1';
	$tag = new Tag($db);
	$associatedArticleList = array();
	$articleTagList = $tag->getArticleTag($object->id);
	foreach ($articleTagList as $articleTag) {
		$tagVal = intval($articleTag['id']);
		$object->fetch($object->id);
		$articles = $object->getArticles($tagVal, $object->id);
		foreach ($articles as $article) {
			$associatedArticleList[] = $article->id;
		}
	}
	$articlesAssociated = array_unique($associatedArticleList);
	$count = is_array($articlesAssociated) ? count($articlesAssociated) : 0;
	if ($count == 0) {
		if (!empty($head)) $head[$h][1] = $langs->trans("AssociatedArticles") . '<span style="color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort"> ' . $count . '</span>';
	} else {
		if (!empty($head)) $head[$h][1] = $langs->trans("AssociatedArticles") . '<span style="background: var(--colorbackhmenu1); color: var(--colortextbackhmenu);" class="badge marginleftonlyshort"> ' . $count . '</span>';
	}
	$head[$h][2] = 'article';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'article@lareponse');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'article@lareponse', 'remove');

	return $head;
}

/**
 * Get the $max last articles updated
 *
 * @param  int    $max     The max number of articles
 * @param  int    $user    The current user
 * @return  array               Last articles
 */
function getLastArticlesUpdated($max, $user)
{
	global $db, $conf;

	$returnValues = array();

	$sql = "SELECT rowid, title, private, tms as dateu, fk_user_creat as author, fk_user_modif as update_author, content FROM " . MAIN_DB_PREFIX . "lareponse_article";
	$sql .= " WHERE entity = " . $conf->entity . "";
	$sql .= " AND private != 2";
	$sql .= " AND rowid NOT IN (SELECT rowid FROM llx_lareponse_article lla WHERE lla.private = 1 AND fk_user_creat != $user->id)";
	$sql .= " ORDER BY tms DESC LIMIT " . $max;

	$resql = $db->query($sql);
	if ($resql) {
		while ($rows = $db->fetch_object($resql)) {
			//if (($rows->author != $user->id) && $rows->private == 1) // We only want to get public articles or the ones where the creator's id corresponds to the current user's
			$returnValues[] = $rows;
		}
	}
	return $returnValues;
}

/**
 * Get the $max last articles created
 *
 * @param  int    $max     The max number of articles
 * @param  int    $user    The current user
 * @return  array               Last articles
 */
function getLastArticlesCreated($max, $user)
{
	global $db, $conf;

	$returnValues = array();

	$sql = "SELECT rowid, title, private, date_creation as datec, fk_user_creat as author, content, entity FROM " . MAIN_DB_PREFIX . "lareponse_article";
	$sql .= " WHERE entity = " . $conf->entity . " AND rowid";
	$sql .= " NOT IN (SELECT rowid FROM llx_lareponse_article lla WHERE lla.private = 1 AND fk_user_creat != $user->id)";
	$sql .= " ORDER BY date_creation DESC LIMIT " . $max;
	$resql = $db->query($sql);

	if ($resql) {
		while ($rows = $db->fetch_object($resql)) {
			if (($rows->author != $user->id) && $rows->private == 1) // We only want to get public articles or the ones where the creator's id corresponds to the current user's
				continue;
			$returnValues[] = $rows;
		}
	}
	return $returnValues;
}

/**
 * Get the $max most active contributor on all article
 *
 * @param  int    $max    The max number of articles
 * @return  array               Last contributors
 */
function getMostActiveContributor($max)
{
	global $db, $conf;

	$returnValues = array();

	$sql = "SELECT COUNT(rowid) as nb_article, fk_user_creat as author FROM " . MAIN_DB_PREFIX . "lareponse_article ";
	$sql .= "WHERE entity = " . $conf->entity . " ";
	$sql .= "GROUP BY fk_user_creat ORDER BY nb_article DESC LIMIT " . $max;
	$resql = $db->query($sql);
	if ($resql) {
		while ($rows = $db->fetch_object($resql)) {
			$returnValues[] = $rows;
		}
	}
	return $returnValues;
}

/**
 * Check if the current article entity corresponds to the current entity
 *
 * @param  Article    $article    The current article
 * @return  boolean                     True or False
 */
function doArticleAndCurrentEntityMatches($article)
{
	global $db, $conf;

	$ret = 0;
	$sql = "SELECT entity FROM " . MAIN_DB_PREFIX . "lareponse_article WHERE rowid = " . $article->id;
	$resql = $db->query($sql);
	if ($resql) {
		$currentity = $db->fetch_row($resql);
		if (!is_array($currentity)) {
			$ret = 0;
		} else {
			$currentity = $currentity[0];
			if ($currentity == $conf->entity) {
				$ret = 1;
			}
		}
	}
	return $ret;
}

/**
 * Checks whenever an article is private, and if so, if the current user can have permissions to it
 *
 * @param  integer    $articleid    The current article's id
 * @param  string     $action       Current action
 * @return boolean                      True or False
 */
function isArticleAccessible($articleid, $action)
{
	global $db, $user;

	$ret = 0;
	if ($articleid) {
		$sql = "SELECT private, fk_user_creat FROM " . MAIN_DB_PREFIX . "lareponse_article WHERE rowid = " . $articleid;
		$resql = $db->query($sql);
	}

	// Define all actions linked to a permission
	$permissiontoread = $user->rights->lareponse->article->read ?? false;
	$permissiontoadd = $user->rights->lareponse->article->write ?? false; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = isset($user->rights->lareponse->article->delete) ? $user->rights->lareponse->article->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT) : false;
	$permissiontoeditother = $user->rights->lareponse->article->update_other ?? false;
	$permissiontoexport = isset($user->rights->lareponse->article->export) ? $user->rights->lareponse->article->export : false;
	$permissiontoclose = $user->rights->lareponse->article->close ?? false; // To close an article
	$permissiontoopen = $user->rights->lareponse->article->open ?? false; // To open an article
	$permissiontopublish = $user->rights->lareponse->article->publish ?? false; // To publish or unpublish an article

	// [#254][#256] User that created a comment can delete/modify it, but not another person
	$permissionModifyComment = 0;
	$commentId = GETPOST("comment_id", "int");
	if ($commentId > 0) {
		$comment = new ArticleComment($db);
		$comment->fetch($commentId);
		if ($user->id == $comment->fk_user_creat) $permissionModifyComment = 1;
	}

	$perms = array(
		'comment' => 1,
		'clone' => ($permissiontoadd ? 1 : 0),
		'create' => ($permissiontoadd || $permissiontoeditother ? 1 : 0),
		'add' => ($permissiontoadd || $permissiontoeditother ? 1 : 0),
		'delete' => ($permissiontodelete ? 1 : 0),
		'export' => ($permissiontoexport ? 1 : 0),
		'close' => ($permissiontoclose ? 1 : 0),
		'open' => ($permissiontoopen ? 1 : 0),
		'update' => ($permissiontoadd || $permissiontoeditother ? 1 : 0),
		'preexport' => ($permissiontoexport ? 1 : 0),
		'confirm_export' => ($permissiontoexport ? 1 : 0),
		'confirm_clone' => ($permissiontoadd ? 1 : 0),
		'confirm_close' => ($permissiontoclose ? 1 : 0),
		'confirm_open' => ($permissiontoopen ? 1 : 0),
		'edit' => ($permissiontoadd || $permissiontoeditother ? 1 : 0),
		'publish' => ($permissiontopublish ? 1 : 0),
		'confirm_publish' => ($permissiontopublish ? 1 : 0),
		'unpublish' => ($permissiontopublish ? 1 : 0),
		'confirm_unpublish' => ($permissiontopublish ? 1 : 0),
		'modify_com' => $permissionModifyComment,
		'modify_comment' => $permissionModifyComment,
		'delete_com' => $permissionModifyComment,
		'delete_comment' => $permissionModifyComment,
		'confirm_delete' => ($permissiontodelete ? 1 : 0)
	);

	if ($articleid && $resql) {
		$result = $db->fetch_array($resql);
		if (!is_array($result)) {
			$ret = 0;
		} else {
			$isprivate = ($result['private'] == 1 ? true : false);
			$usercreat = $result['fk_user_creat'];
			$iscreator = ($usercreat == $user->id ? true : false);
			$perms['edit'] = (($permissiontoadd && $iscreator) || $permissiontoeditother ? 1 : 0);
		}
	}
	if ((isset($action) && $action !== '' && isset($perms[$action])) && ($perms[$action]) || (!$action && $iscreator) || (!$action && !$isprivate)) {
		$ret = 1;
	} else {
		$ret = 0;
	}
	return $ret;
}

/**
 * In article_list, returns the content to display without table elements in it - See issue #62
 *
 * @param  string    $content    The article content
 * @return  string                  The string to display
 */
function contentWithoutTable($content)
{
	if (($needleindex = strpos($content, '<table')) === 0) {
		return '';
	} else if ($needleindex > 0) {
		return dol_trunc($content, $needleindex);
	}
	return $content;
}

/**
 * Replaces all <br /> to '' for Mermaid compatibility
 *
 * @param  string    $content    The content of the article/comment
 * @return  string
 */
function changeContentForMermaid($content)
{
	$res = '';

	if (strpos($content, '<div class="mermaid">') !== false) {
		// Content is a mermaid div, we need to change the content
		$classes = explode('<div class="mermaid">', $content);
		foreach ($classes as &$class) {
			if (strpos($class, '</div>')) {
				$temparray = explode('</div>', $class);
				$class = str_replace('<br />', '', $temparray[0]) . '</div>' . $temparray[1];
				$res .= '<div class="mermaid">' . $class;
			} else {
				$res .= $class;
			}
		}
	} else {
		$res = $content;
	}

	return $res;
}

/**
 * Create article event
 *
 * @param  int   	 	   $objectId       Article object
 * @param  string          $eventName      Name of the event
 * @param  string          $description    description of the event
 * @return int
 */
function createArticleEvent($objectId, $eventName, $description = "")
{
	global $conf, $user, $db;

	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	// Create a request event
	$newEvent = new ActionComm($db);
	$newEvent->datep = dol_now();
	$newEvent->datec = dol_now();
	$newEvent->tms = dol_now();
	$newEvent->type_code = $eventName;
	$newEvent->entity = $conf->entity;
	$newEvent->percentage = 100;
	$newEvent->fk_element = $objectId;
	$newEvent->elementtype = 'article@lareponse';
	$newEvent->authorid = $user->id;
	$newEvent->type_picto = 'article@lareponse';
	$newEvent->picto = 'article@lareponse';
	$newEvent->userownerid = $user->id;
	$label = (getLRLabelOfEventCode($eventName) ?? $eventName);
	$newEvent->label = $label;
	$newEvent->type_label = $label;
	if (!empty($description)) $newEvent->note_private = $description;

	return $newEvent->create($user);
}

/**
 * Get the label that corresponds to the event code passed in parameter
 *
 * @param  string    $code    The event code
 * @return string Return the label that corresponds to the event code
 */
function getLRLabelOfEventCode($code)
{
	global $langs;

	try {
		$code = QueryBuilder::table('c_actioncomm')
			->select('libelle')
			->where('code', '=', $code)
			->disableEntityCheck()
			->get();
	} catch (Exception $e) {
		print $e->getMessage();
	}

	$code = (!empty($code[0]) ? $langs->trans($code[0]->libelle) : "");
	return $code;
}

/**
 * Delete all article comments (used when we delete article)
 *
 * @param int $articleId Article id
 * @return int
 */
function deleteArticleComment($articleId)
{
	global $db, $conf;

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "lareponse_comment WHERE fk_article = $articleId AND entity = $conf->entity";

	$resql = $db->query($sql);

	if (!empty($resql)) return 1;
	else return -1;
}
