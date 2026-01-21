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
 * \file        class/export.class.php
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
class LareponseExport extends CommonObject
{
	/**
	 * Generates json export files from listed articles
	 * @param   array       $articlesid     Array of articles id
	 *
	 * @return  boolean                     <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function generateJsonFile($articlesid)
	{
		global $db, $conf;

		$ret = true;
		// Prepare temporary directory
		$dir = $conf->lareponse->dir_temp;
		dol_mkdir($dir); // We don't want to check the success of this function as it do not need to succeed

		// Get all articles related to $articlesids
		$sql = 'SELECT t.date_creation, t.tms, t.title, t.content ';
		$sql.= 'FROM '.MAIN_DB_PREFIX.'lareponse_article AS t WHERE ';
		$sql.= 't.rowid IN('.implode(',', $articlesid).')';
		// Run query
		$resql = $db->query($sql);

		$sqlresults = array();
		$resindex = 0;
		if ($resql) {
			while ($rows = $db->fetch_object($resql)) {
				$sqlresults[$articlesid[$resindex]] = $rows;
				$resindex++;
			}
			foreach ($articlesid as $currid) {
				$filename = 'lareponse_article_'.$currid.'_content.json';
				// Create the temporary file
				$fp = fopen($dir.'/'.$filename, "wt");
				if ($fp >= 0) {
					$response = array('date_creation'=>$sqlresults[$currid]->date_creation,'tms'=>$sqlresults[$currid]->tms,'title'=>$sqlresults[$currid]->title,'content'=>$sqlresults[$currid]->content);
					// Fill the created file with the $sql query result
					$ret = (fwrite($fp, json_encode($response)) ? $ret : false);
					$ret = (fclose($fp) ? $ret : false);
				} else {
					$ret = false;
				}
			}
		} else {
			$ret = false;
		}
		return ($ret);
	}

	/**
	 * Download export file from lareponse/temp directory and deletes it
	 * @param   array       $articlesid     Array of articles id
	 *
	 * @return  boolean                     <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function downloadExportFile($articlesid)
	{
		global $conf;

		$ret = true;
		$archivename = 'lareponse_articles_exported.zip';
		// Creation of the zip archive with the given folder and ids
		$ret = ($this->loadFolderInZip($conf->lareponse->multidir_output[$conf->entity]."/article/", $archivename, $articlesid) ? $ret : false);
		if ($ret) {
			// Preparation of the download by headers
			while (ob_get_level()) {
				ob_end_clean();
			}

			ob_start();
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: Binary");
			header("Content-Length: ".filesize($archivename));
			header("Pragma: no-cache");
			header("Content-Disposition: attachment; filename=\"".basename($archivename)."\"");
			ob_flush();
			ob_clean();
			// The readfile functions actually downloads the zip archive
			if (!readfile($archivename)) {
				$ret = false;
			}
		}
		// Delete the temporary folders
		unlink($archivename);
		foreach ($articlesid as $currid) {
			unlink($conf->lareponse->dir_temp.'/lareponse_article_'.$currid.'_content.json');
		}
		exit($ret);
	}

	/**
	 * Load an entire folder into a zip archive
	 * @param   string      $folderpath     The path of the folder to zip
	 * @param   string      $zipname        The name
	 * @param   array       $articlesid     The articles id to search for
	 * @return  boolean                     <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	private function loadFolderInZip($folderpath, $zipname, $articlesid)
	{
		global $conf;

		$ret = true;
		$zip = new ZipArchive();
		if (file_exists($zipname)) {
			unlink($zipname);
		}
		if ($zip->open($zipname, ZIPARCHIVE::CREATE) !== true) {
			dol_syslog("INFO ZIP LAREPONSE zip couldn't be opened", LOG_WARNING);
			$ret = false;
		}
		// Add all articles
		if (is_dir($folderpath) && $ret) {
			foreach ($articlesid as $currid) {
				$filename = 'lareponse_article_'.$currid.'_content.json';
				$filepath = $conf->lareponse->dir_temp.'/'.$filename;
				$articleimgdirpath = $folderpath.dol_sanitizeFileName($currid).'/';
				// Copie des fichiers dans le zip basé sur l'id actuel
				if ($this->addDirFilesToZip($zip, $articleimgdirpath, 'article'.$currid) !== true) {
					dol_syslog("INFO ZIP LAREPONSE $articleimgdirpath dir couldn't be added to zip in article$currid", LOG_WARNING);
					$ret = false;
				}
				// Add json file to the zip
				$ret = ($zip->addFile($filepath, 'article'.$currid.'/'.$filename) ? $ret : false);
			}
		} else if (!is_dir($folderpath.dol_sanitizeFileName($articlesid[0]).'/')) {
			// Sometimes, dolibarr sets the article elements at the bas eof documents/lareponse/article/ wich shouldn't happen
			// Here we take that problem on account
			$ret = false;
			foreach ($articlesid as $currid) {
				$filename = 'lareponse_article_'.$currid.'_content.json';
				$filepath = $conf->lareponse->dir_temp.'/'.$filename;
				// Needed to separate all articles in the archive
				if ($zip->addEmptyDir('article'.$currid) !== true) {
					dol_syslog("INFO ZIP LAREPONSE article$currid dir couldn't be added to zip", LOG_WARNING);
				}
				// Add json file to the zip
				if ($zip->addFile($filepath, 'article'.$currid.'/'.$filename) !== true) {
					dol_syslog("INFO ZIP LAREPONSE $filepath file couldn't be added to zip in article$currid/$filename", LOG_WARNING);
				}
			}
		}
		$ret = ($zip->close() ? $ret : false);
		if ($ret !== true) {
			dol_syslog("INFO ZIP LAREPONSE couldn't be closed", LOG_WARNING);
		}
		$ret = $this->isArchiveComplete($zipname);
		return $ret;
	}

	/**
	 * Checks whenever the zip has an error or not
	 * @param  string       $zipname    The name of the archive
	 * @return bool
	 */
	private function isArchiveComplete($zipname)
	{
		$ret = true;
		$zip = new ZipArchive();

		$res = $zip->open($zipname, ZipArchive::CHECKCONS);
		if ($res !== true) {
			$ret = false;
			switch ($res) {
				case ZipArchive::ER_EXISTS:
					dol_syslog("INFO ZIP LAREPONSE file already exists", LOG_ERR);
				case ZipArchive::ER_INCONS :
					dol_syslog("INFO ZIP LAREPONSE consistency check failed", LOG_ERR);
				case ZipArchive::ER_INVAL :
					dol_syslog("INFO ZIP LAREPONSE argument check failed", LOG_ERR);
				case ZipArchive::ER_MEMORY :
					dol_syslog("INFO ZIP LAREPONSE not enough memory", LOG_ERR);
				case ZipArchive::ER_NOENT:
					dol_syslog("INFO ZIP LAREPONSE no such file", LOG_ERR);
				case ZipArchive::ER_NOZIP:
					dol_syslog("INFO ZIP LAREPONSE file is not a zip archive", LOG_ERR);
				case ZipArchive::ER_CRC :
					dol_syslog("INFO ZIP LAREPONSE checksum failed", LOG_ERR);
				default:
					dol_syslog("INFO ZIP LAREPONSE error $res", LOG_ERR);
			}
		}
		return $ret;
	}

	/**
	 * Add subdirectories files for a new zip directory
	 * Recursive function
	 * @param   ZipArchive      $zip                The archive to fill
	 * @param   string          $articleimgdirpath  Path of the actual directory
	 * @param   string          $archivepath        Current path of the archive
	 *
	 * @return  boolean                             <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	private function addDirFilesToZip($zip, $articleimgdirpath, $archivepath)
	{
		$ret = true;

		$ret = ($zip->addEmptyDir($archivepath) === true ? $ret : false);
		if ($ret != true) {
			dol_syslog("INFO ZIP LAREPONSE empty dir $archivepath couldn't be added", LOG_WARNING);
		}
		$files = $this->getFilesFromFolder($articleimgdirpath);
		if ($files) {
			foreach ($files as $file) {
				$currentfolder = $articleimgdirpath.$file;
				if (is_dir($currentfolder)) {
					$ret = ($this->addDirFilesToZip($zip, "$currentfolder/", "$archivepath/$file") === true ? $ret : false);
				} else {
					$ret = ($zip->addFile($currentfolder, "$archivepath/$file") === true ? $ret : false);
					if ($ret != true) {
						dol_syslog("INFO ZIP LAREPONSE file $archivepath/$file from $currentfolder couldn't be added", LOG_WARNING);
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Returns an array of the a folder's content
	 * @param   string          $folderpath     The path to the folder to open
	 *
	 * @return  array|false                     Content of the folder or <b>FALSE</b> if not a folder
	 */
	private function getFilesFromFolder($folderpath)
	{
		$ret = false;
		$files = array();

		if (is_dir($folderpath)) {
			if ($handle = opendir($folderpath)) {
				while (($entry = readdir($handle)) !== false) {
					// Hidden files must be excluded
					if ($entry != "." && $entry != "..") {
						$files[] = $entry;
						$ret = true;
					} else if (!$entry) {
						$ret = false;
					}
				}
				closedir($handle);
			} else {
				$ret = false;
			}
		} else {
			$ret = false;
		}
		return ($ret == false ? false : $files);
	}
}
