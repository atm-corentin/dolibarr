<?php
/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
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
 * File Manager class for Supplier Proposal files
 * Handles all file operations (upload, move, delete, etc.)
 */
class SupplierProposalFileManager
{
	/** @var Conf */
	private $conf;

	/** @var DoliDB */
	private $db;

	/**
	 * Constructor
	 *
	 * @param Conf $conf Configuration object
	 * @param DoliDB $db Database handler
	 */
	public function __construct(Conf $conf, DoliDB $db)
	{
		$this->conf = $conf;
		$this->db = $db;
	}

	/**
	 * Upload file to session
	 *
	 * @param int $trackId Track ID for session key
	 * @return int >0 if OK, <0 if error
	 */
	public function uploadFileToSession(int $trackId) : int
	{
		dol_syslog("SupplierProposalFileManager::uploadFileToSession trackId=" . $trackId, LOG_DEBUG);

		if (empty($_FILES['addedfile']['name'])) {
			dol_syslog("SupplierProposalFileManager::uploadFileToSession No file uploaded (addedfile empty)", LOG_DEBUG);
			return 0;
		}

		dol_syslog("SupplierProposalFileManager::uploadFileToSession File name: " . $_FILES['addedfile']['name'], LOG_DEBUG);

		$uploadDir = $this->conf->admin->dir_temp ? $this->conf->admin->dir_temp : DOL_DATA_ROOT . '/admin/temp';
		dol_syslog("SupplierProposalFileManager::uploadFileToSession uploadDir=" . $uploadDir, LOG_DEBUG);

		$result = dol_add_file_process(
			$uploadDir,
			1,        // Allow overwrite in temp directory (files from previous uploads should be replaced)
			0,        // Store in session (not in DB)
			'addedfile',
			'',
			null,
			$trackId,
			1,        // Generate thumbnails
			null
		);

		dol_syslog("SupplierProposalFileManager::uploadFileToSession result=" . $result, LOG_DEBUG);

		if ($result > 0) {
			$keytoavoidconflict = '-' . $trackId;
			dol_syslog("SupplierProposalFileManager::uploadFileToSession Session check - listofnames" . $keytoavoidconflict . "=" . (isset($_SESSION["listofnames" . $keytoavoidconflict]) ? $_SESSION["listofnames" . $keytoavoidconflict] : 'NOT SET'), LOG_DEBUG);
		}

		return $result;
	}

	/**
	 * Remove file from session
	 *
	 * @param int $fileIndex File index to remove
	 * @param int $trackId Track ID for session key
	 * @return void  dol_remove_file_process returns void  on success, or on failure
	 */
	public function removeFileFromSession(int $fileIndex, int $trackId)
	{
		return dol_remove_file_process($fileIndex, 0, 0, $trackId);
	}

	/**
	 * Move session files to proposal directory
	 *
	 * @param SupplierProposal $object
	 * @return array Array with success/error info
	 */
	public function moveSessionFilesToProposal(SupplierProposal $object) : array
	{
		$result = array('success' => 0, 'errors' => array());
		$keytoavoidconflict = '-' . $object->id;

		if (empty($_SESSION["listofpaths" . $keytoavoidconflict]) || empty($_SESSION["listofnames" . $keytoavoidconflict])) {
			return $result;
		}

		$listofpaths = explode(';', $_SESSION["listofpaths" . $keytoavoidconflict]);
		$listofnames = explode(';', $_SESSION["listofnames" . $keytoavoidconflict]);

		$uploadDirProposal = $this->conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
		dol_mkdir($uploadDirProposal);

		foreach ($listofpaths as $key => $val) {
			$src = $val;
			$filename = $listofnames[$key];

			$uniqueFilename = $this->getUniqueFilename($uploadDirProposal, $filename);
			$destProposal = $uploadDirProposal . '/' . $uniqueFilename;

			if (dol_move($src, $destProposal)) {
				addFileIntoDatabaseIndex($uploadDirProposal, $uniqueFilename, '', 'uploaded', 0, $object);
				$result['success']++;
			} else {
				$result['errors'][] = 'Failed to move file: ' . $filename;
			}
		}

		// Clear session
		$this->clearSessionFiles($object->id);

		return $result;
	}

	/**
	 * Copy session files to both proposal and action directories
	 *
	 * @param SupplierProposal $object
	 * @param int $actionId Action ID
	 * @return array Array with success/error info
	 */
	public function copySessionFilesToProposalAndAction(SupplierProposal $object, int $actionId) : array
	{
		$result = array('success' => 0, 'errors' => array());
		$keytoavoidconflict = '-' . $object->id;

		if (empty($_SESSION["listofpaths" . $keytoavoidconflict]) || empty($_SESSION["listofnames" . $keytoavoidconflict])) {
			return $result;
		}

		$listofpaths = explode(';', $_SESSION["listofpaths" . $keytoavoidconflict]);
		$listofnames = explode(';', $_SESSION["listofnames" . $keytoavoidconflict]);

		// Prepare directories
		$uploadDirProposal = $this->conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
		$uploadDirAction = $this->conf->agenda->dir_output . '/' . $actionId;
		dol_mkdir($uploadDirProposal);
		dol_mkdir($uploadDirAction);

		foreach ($listofpaths as $key => $val) {
			$src = $val;
			$filename = $listofnames[$key];

			// Copy to proposal directory
			$uniqueFilenameProposal = $this->getUniqueFilename($uploadDirProposal, $filename);
			$destProposal = $uploadDirProposal . '/' . $uniqueFilenameProposal;
			if (dol_copy($src, $destProposal)) {
				addFileIntoDatabaseIndex($uploadDirProposal, $uniqueFilenameProposal, '', 'uploaded', 0, $object);
			}

			// Move to action directory
			$uniqueFilenameAction = $this->getUniqueFilename($uploadDirAction, $filename);
			$destAction = $uploadDirAction . '/' . $uniqueFilenameAction;
			if (dol_move($src, $destAction)) {
				$result['success']++;
			} else {
				$result['errors'][] = 'Failed to move file to action: ' . $filename;
			}
		}

		// Clear session
		$this->clearSessionFiles($object->id);

		return $result;
	}

	/**
	 * Generate unique filename if file already exists
	 *
	 * @param string $directory Directory path
	 * @param string $filename Original filename
	 * @return string Unique filename
	 */
	private function getUniqueFilename(string $directory, string $filename) : string
	{
		$filename = dol_sanitizeFileName(dol_string_nohtmltag(basename($filename)));
		$dest = $directory . '/' . $filename;

		if (!file_exists($dest)) {
			return $filename;
		}

		$pathinfo = pathinfo($filename);
		$basename = $pathinfo['filename'];
		$extension = !empty($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';

		$counter = 1;
		while (file_exists($directory . '/' . $basename . '_' . $counter . $extension)) {
			$counter++;
		}

		return $basename . '_' . $counter . $extension;
	}

	/**
	 * Clear session files
	 *
	 * @param int $objectId
	 * @return void
	 */
	private function clearSessionFiles(int $objectId) : void
	{
		$keytoavoidconflict = '-' . $objectId;
		unset($_SESSION["listofpaths" . $keytoavoidconflict]);
		unset($_SESSION["listofnames" . $keytoavoidconflict]);
		unset($_SESSION["listofmimes" . $keytoavoidconflict]);
	}

	/**
	 * Get proposal upload directory
	 *
	 * @param SupplierProposal $object
	 * @return string
	 */
	public function getProposalUploadDir(SupplierProposal $object) : string
	{
		return $this->conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($object->ref);
	}

	/**
	 * Check if files exist in directory
	 *
	 * @param string $directory
	 * @return bool
	 */
	public function hasFilesInDirectory(string $directory) : bool
	{
		if (is_dir($directory)) {
			$files = dol_dir_list($directory, 'files', 0, '', null, 'date', SORT_DESC);
			return !empty($files);
		}
		return false;
	}
}
