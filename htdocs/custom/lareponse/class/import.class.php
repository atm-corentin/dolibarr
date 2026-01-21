<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020  Arthur Croix        <arthur@code42.fr>
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
 * \file        class/import.class.php
 * \ingroup     lareponse
 * \brief       This file is a CRUD class file for article (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for import
 */
class LareponseImport extends CommonObject
{
	/**
	 * Importation of an article export json file
	 * @param   string      $filepath       Path of the file like directory/lareponse_article_$id_content.json
	 *
	 * @return  int                         Returns the new article's id or <0 in case of problems
	 */
	public function importJsonFile($filepath)
	{
		global $user, $conf, $db;
		$problems = 0;
		if (file_exists($filepath)) {
			$fd = fopen($filepath, "r");
			if ($fd >= 0) {
				// Read input file contents
				$contents = fread($fd, filesize($filepath));
				// True in json decode returns an array instead of an stdClass
				$filecontent = json_decode($contents, true);
				if ($filecontent) {
					// Prepare sql query
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lareponse_article ';
					// Register all values types we need to insert into the datatable
					$sql .= '(date_creation, tms, title, content, fk_user_creat, entity, private, import_key) ';
					// Register all values from the import file we need for the insertion
					$sql .= 'VALUES ';
					$import_key_token = openssl_random_pseudo_bytes(7);
					$import_key_token = bin2hex($import_key_token);
					$sql .= '(\''.$filecontent['date_creation'].'\', \''.$filecontent['tms'].'\', \''.$db->escape($filecontent['title']).'\', \'not formated content\', '.$user->id.', '.$conf->entity.', 0, \''.$import_key_token.'\')';
					if (!$db->query($sql)) {
						$problems++;
					}
				}
				fclose($fd);
				// Delete the json file
				unlink($filepath);
				$sql = 'SELECT t.rowid FROM '.MAIN_DB_PREFIX.'lareponse_article as t ';
				$sql.= 'WHERE t.import_key = \''.$import_key_token.'\'';
				$resql = $db->query($sql);
				$ret = '';
				if ($resql) {
					// As we work with one article at a time, the query result only has one element
					$ret = (int) $db->fetch_row($resql)[0];
				} else {
					$problems++;
				}
				if ($filecontent && $ret) {
					// Replace images paths if images are listed into the content. Because we changed the article's id
					$this->replaceContentArticleId($filecontent['content'], $ret);
					// Replace all ' by \' and " by \" to avoid sql confusion
					$filecontent['content'] = str_replace('\'', '\\\'', $filecontent['content']);
					$filecontent['content'] = str_replace('"', '\\"', $filecontent['content']);
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'lareponse_article ';
					$sql.= 'SET content = \''.$filecontent['content'].'\', import_key = null ';
					$sql.= 'WHERE import_key = \''.$import_key_token.'\'';
					if (!$db->query($sql)) {
						$problems++;
					}
				}
			} else {
				$ret = -1;
			}
		}
		if ($problems != 0) {
			$ret = -2;
		}
		return $ret;
	}

	/**
	 * Replaces the links in the content with the new article's id
	 * @param   string      $content        The article's content
	 * @param   int         $newid          The article's new id
	 * @return  void
	 */
	private function replaceContentArticleId(&$content, $newid)
	{
		global $conf;
		$security = 0;

		// Replace all occurences of the old server name by the new one
		$currentpos = strpos($content, 'http'); // Go to the next http protocol
		if (!$currentpos) {
			// If no link was found, we do not need to change the content
			return;
		}
		$currentpos = strpos($content, '//', $currentpos) + 2; // We add 2 to pass the // position
		$serverlen = strpos($content, '/', $currentpos) - $currentpos;
		$oldservername = substr($content, $currentpos, $serverlen);
		if ($oldservername != $_SERVER['HTTP_HOST']) {
			$content = str_replace($oldservername, $_SERVER['HTTP_HOST'], $content);
		}
		$tempcontent = $content;
		$currentpos = 0;
		/* @DEPRECATED */
		/*while (($currentpos = strpos($content, 'viewimage.php', $currentpos)) !== false) { // Get to the next viewimage
			$currentpos = strpos($content, 'viewimage.php', $currentpos); // Return to the viewimage element
			$currentpos+= 13; // 13 is the size of 'viewimage.php'
			$tempcontent = substr($tempcontent, $currentpos);
			// Get to the next entity id
			$nextpos = strpos($tempcontent, 'entity=') + 7; // 7 is the size of 'entity='
			$currentpos+= $nextpos;
			$tempcontent = substr($tempcontent, $nextpos);
			// Replace the last entity number with the current entity
			$content = substr_replace($content, (string) $conf->entity, $currentpos, strlen((string) $this->getNextNbr($tempcontent)));
			// Get to the next element id
			$nextpos = strpos($tempcontent, 'file=');
			$tempcontent = substr($tempcontent, $nextpos);
			$currentpos+= $nextpos;
			$nextpos = strpos($tempcontent, '%2F') + 3; // 3 is the size of '%2F'
			$tempcontent = substr($tempcontent, $nextpos);
			$currentpos+= $nextpos;
			$content = substr_replace($content, (string) $newid, $currentpos, strlen((string) $this->getNextNbr($tempcontent))); // Here we replace the last article id with the current id
			$tempcontent = $content;
			$security++;
			if ($security == 10) {
				break;
			}
		}*/
	}

	/**
	 * Gets the next integer out of a string
	 * @param    string        $string      The string to search in
	 * @return   int                        The first integer found. 0 if not found
	 */
	private function getNextNbr($string)
	{
		$nbr = '';
		$foundanum = false;

		for ($i = 0; ($string[$i] < 48 || $string[$i] > 57) && !$foundanum && $i < strlen($string); $i++) {
			for ($i = 0; ($string[$i] >= 48 || $string[$i] <= 57) && $i < strlen($string); $i++) {
				$nbr.= $string[$i];
				$foundanum = true;
			}
		}
		$nbr = (int) $nbr;
		return $nbr;
	}

	/**
	 * Importation of an exported article's folders into its documents/ folder with its new id
	 * @param   string   $articlefolder     Article folder to copy
	 * @param   int      $articleid         Article id to link to
	 *
	 * @return  int                         False on Failure, True on Success
	 */
	public function importFolders($articlefolder, $articleid)
	{
		global $conf;
		$ret = true;

		$importdir = $conf->lareponse->multidir_output[$conf->entity]."/article";
		@mkdir($importdir);
		$importdir.= '/'.$articleid;
		$ret = ($this->recurseFolderCopy($articlefolder, $importdir) ? $ret : false);
		return $ret;
	}

	/**
	 * Cut and paste an entire folder into the destination one
	 * @param       string          $source         The source directory
	 * @param       string          $destination    The destination directory
	 *
	 * @return  int                         False on Failure, True on Success
	 */
	private function recurseFolderCopy($source, $destination)
	{
		$ret = true;

		$ret = (($dir = opendir($source)) ? $ret : false);
		$ret = (mkdir($destination) ? $ret : false);
		while (($file = readdir($dir)) !== false) {
			if (($file != '.') && ($file != '..')) {
				if ( is_dir($source.'/'.$file)) {
					$ret = ($this->recurseFolderCopy($source.'/'.$file, $destination.'/'.$file) ? $ret : false);
				} else {
					$ret = (copy($source.'/'.$file, $destination.'/'.$file) ? $ret : false);
					$ret = (unlink($source.'/'.$file) ? $ret : false);
				}
			}
		}
		closedir($dir);
		$ret = (rmdir($source) ? $ret : false);
		return $ret;
	}
}
