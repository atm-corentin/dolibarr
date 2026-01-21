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

/**
 * \file    lib/lareponse_tag.lib.php
 * \ingroup lareponse
 * \brief   Library files with common functions for tag
 */

dol_include_once('/lareponse/class/tag.class.php');

/**
 * Prepare array of tabs for tag
 *
 * @param  tag    $object    tag
 * @return    array                    Array of tabs
 */
function tagPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("lareponse@lareponse");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/lareponse/tag_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath('/lareponse/tag_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbNote . '</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	/*$upload_dir = $conf->lareponse->dir_output . "/tag/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/lareponse/tag_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;*/

	/*$head[$h][0] = dol_buildpath("/lareponse/tag_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;*/

	$head[$h][0] = dol_buildpath("/lareponse/tag_links.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("LinkedArticles");
	$head[$h][2] = 'linkedarticles';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@lareponse:/lareponse/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@lareponse:/lareponse/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'tag@lareponse');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'tag@lareponse', 'remove');

	return $head;
}

/**
 * Check if the current tag entity corresponds to the current entity
 *
 * @param  Tag    $tag    The current tag
 * @return  boolean                     True or False
 */
function doTagAndCurrentEntityMatches($tag)
{
	global $db, $conf;

	$ret = 0;
	$sql = "SELECT entity FROM " . MAIN_DB_PREFIX . "categorie WHERE rowid = " . $tag->id;
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
 * Checks whenever a tag is accessible with current permissions
 *
 * @param  string    $action    Current action
 * @return boolean                      True or False
 */
function isTagAccessible($action)
{
	global $db, $user;

	$ret = 0;

	// Define all actions linked to a permission
	$permissiontoread = $user->rights->lareponse->article->read;
	$permissiontoadd = $user->rights->lareponse->tag->write;
	$permissiontodelete = $user->rights->lareponse->tag->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

	$perms = array(
		'create' => ($permissiontoadd ? 1 : 0),
		'add' => ($permissiontoadd? 1 : 0),
		'delete' => ($permissiontodelete ? 1 : 0),
		'update' => ($permissiontoadd ? 1 : 0),
		'edit' => ($permissiontoadd ? 1 : 0)
	);

	if (($perms[$action]) || (!$action && $permissiontoread)) {
		$ret = 1;
	} else {
		$ret = 0;
	}
	return $ret;
}

/**
 * Get number of tags that are linked to articles
 *
 * @param  array    $ids    ids of selected tags
 * @return array
 */
function getAllArticleLinked($ids)
{
	global $db;
	$artcilesLinked = array();
	if ($ids) {
		foreach ($ids as $id) {
			$sql = 'SELECT count(fk_article) as count from ' . MAIN_DB_PREFIX . 'lareponse_article_tag WHERE fk_tag = ' . $id;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$artcilesLinked[] = $obj;
			}
		}
		return $artcilesLinked;
	}
}

/**
 *  Delete tag from db
 *
 * @return void
 */
function deleteTag()
{
	global $langs, $db;
	$sql = 'DELETE ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'categorie ';
	$sql .= 'WHERE rowid IN (';
	$list_tag = GETPOST('toselect', 'array');
	if ($list_tag) {
		$i = false;
		foreach ($list_tag as $tag) {
			if ($i == true) $sql .= ', ' . $tag;
			else $sql .= $tag;
			$i = true;
		}
		$sql .= ');';
		$db->query($sql);
		setEventMessages($langs->trans('TagDelete'), '', 'mesgs');
		exit(header('Location: ' . $_SERVER['PHP_SELF']));
	}
}

/**
 * Check whenever gestionparc and/or categorie tags may be displayed
 *
 * @param  string    $tagname    'gestionparc' to check gestionparc's tags, or 'categorie' to check categorie's tags
 * @return    boolean                    true if may be displayed, false if not
 */
function mayTagBeDisplayed($tagname)
{
	global $conf, $user;

	$iscategorieenabled = $conf->categorie->enabled;
	if (isset($conf->gestionparc->enabled)) $isgestionparcenabled = $conf->gestionparc->enabled;
	$isgestionparctagsactive = $conf->global->LAREPONSE_TAG_GESTIONPARC_ACTIVE;
	$iscategoriestagsactive = $conf->global->LAREPONSE_TAG_CATEGORIES_ACTIVE;
	$retValue = false;

	if ($tagname == 'categorie') {
		if ($iscategorieenabled && $iscategoriestagsactive && isset($user->rights->categorie->lire) && $user->rights->categorie->lire)
			$retValue = true;
		else $retValue = false;
	} else if ($tagname == 'gestionparc') {
		if ($iscategorieenabled && $isgestionparctagsactive && isset($isgestionparcenabled) && isset($user->rights->categorie->lire) && $user->rights->categorie->lire)
			$retValue = true;
		else $retValue = false;
	}
	return $retValue;
}

/**
 * Get list of enabled tags to display in LaReponse
 *
 * @param  array       $tagMapList     Lareponse tag object
 * @param  string    $type    return type, "array" by default. It can be "array" ou "sql"
 * @return string|array
 */
function getLareponseTagIdList($tagMapList, $type = "array")
{

	$tagIdList = array();

	$otherTagsEnabled = mayTagBeDisplayed('categorie');
	$gpTagsEnabled = mayTagBeDisplayed('gestionparc');

	if ($otherTagsEnabled) {
		$tagIdList = $tagMapList;
		if (!$gpTagsEnabled) unset($tagIdList[array_search("GestionParc", $tagMapList)]);
	} else {
		if (!empty(array_search("Lareponse", $tagMapList))) $tagIdList[array_search("Lareponse", $tagMapList)] = "Lareponse";
		if ($gpTagsEnabled) $tagIdList[array_search("GestionParc", $tagMapList)] = "GestionParc";
	}

	if ($type == "sql") {
		return "(" . implode(", ", array_flip($tagIdList)) . ")";
	} else {
		// If ($type == "array") or other
		return $tagIdList;
	}
}
