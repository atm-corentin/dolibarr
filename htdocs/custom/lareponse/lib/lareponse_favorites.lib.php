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
 * \file    lib/lareponse_favorites.lib.php
 * \ingroup lareponse
 * \brief   Library files with common functions for favorites
 */

/**
 * Returns an array of the current user's favorites
 *
 * @param	fk_user	$fk_user		    Current User
 * @return 	array					    Array of favorite articles
 */
function getUserFavoriteArticles($fk_user)
{
	global $db, $conf;
	$articles = array();
	$articles_ids = '';
	// Get articles rowids that corresponds to the user's favorites
	$sql = "SELECT fk_article FROM ".MAIN_DB_PREFIX."lareponse_favorites WHERE fk_user = ".$fk_user." AND entity = ".$conf->entity;
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		for ($i = 0; $i < $num; $i++) {
			$articles_ids .= $db->fetch_object($resql)->fk_article;
			if ($i + 1 != $num) $articles_ids .= ' OR rowid = ';
		}
	}
	// Get articles that matches articles rowids found firstly
	$sql = "SELECT rowid, title, private, tms as dateu, fk_user_creat as author, content FROM ".MAIN_DB_PREFIX."lareponse_article WHERE rowid = ".$articles_ids;
	$resql = $db->query($sql);
	if ($resql) {
		while ($row = $db->fetch_object($resql)) {
			if (($row->author != $fk_user) && $row->private == 1) // We only want to get public articles or the ones where the creator's id corresponds to the current user's
				continue;
			$articles[] = $row;
		}
		$db->free($resql);
	}
	return $articles;
}

/**
 * Returns True False or Error if a favorite exists for the current user and the current article
 *
 * @param	fk_user	$fk_user		        Current User
 * @param   fk_article $fk_article          Current Article
 * @return  integer                         The function successes => 1, The favorite already exists => 0, The function fails => -1
 */
function favoriteExists($fk_user, $fk_article)
{
	global $db, $conf;

	$sql = "SELECT fk_article, fk_user FROM ".MAIN_DB_PREFIX."lareponse_favorites WHERE fk_user = ".$fk_user." AND fk_article = ".$fk_article." AND entity = ".$conf->entity;
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		return -1;
	}
	if ($db->num_rows($resql) > 0)
		return 1;
	return 0;
}

/**
 * Create new favorite element into lareponse_favorites table
 *
 * @param	fk_user	$fk_user		        Current User
 * @param   fk_article $fk_article          Current Article
 * @return  integer                         The function successes => 1, The favorite already exists => 0, The function fails => -1
 */
function createFavorite($fk_user, $fk_article)
{
	global $db, $conf;

	if (favoriteExists($fk_user, $fk_article) == 1)
		return 0;
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."lareponse_favorites (date_creation, fk_article, fk_user, entity) values ('".$db->idate(dol_now())."', ".$fk_article.", ".$fk_user.", ".$conf->entity.")";
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		return -1;
	}
	return 1;
}

/**
 *  Delete the favorite element into lareponse_favorites table
 *
 * @param	fk_user	$fk_user		        Current User
 * @param   fk_article $fk_article          Current Article
 * @return  integer                         The function successes => 1, The favorite doesn't exist => 0, The function fails => -1
 */
function deleteFavorite($fk_user, $fk_article)
{
	global $db;

	if (!favoriteExists($fk_user, $fk_article))
		return 0;
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."lareponse_favorites WHERE fk_user = ".$fk_user." AND fk_article = ".$fk_article;
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		return -1;
	}
	return 1;
}
