<?php
/* Copyright (C) 2025      Open-DSI             <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/sirene/class/codenaf.class.php
 * \ingroup sirene
 * \brief
 */
dol_include_once('/sirene/lib/sirene.lib.php');


/**
 * Class CodeNaf
 *
 * Put here description of your class
 */
class CodeNaf
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load Code Naf CSV into table
	 *
	 * @param	string	$filepath	Filepath of the CSV
	 * @return  int					Result <0 if KO otherwise the number of code NAF added
	 */
	public function importCsv($filepath)
	{
		global $conf, $langs;

		$langs->load('sirene@sirene');

		$filepath = dol_osencode($filepath);
		$separator = getSireneDolGlobalString('CODENAF_CSV_SEPARATOR_TO_USE', ';');
		$enclosure = getSireneDolGlobalString('CODENAF_CSV_ENCLOSURE_TO_USE', '"');
		$escape = getSireneDolGlobalString('CODENAF_CSV_ESCAPE_TO_USE', '\\');

		if (!file_exists($filepath)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorFileNotFound', $filepath);
			return -1;
		}

		ini_set('auto_detect_line_endings', 1);    // For MAC compatibility

		$handle = fopen($filepath, "r");
		if ($handle === false) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorFileNotFound', $filepath);
			return -1;
		}

		$error = 0;
		$num_line = 0;
		$nb_added = 0;
		$this->db->begin();

		$sql = "TRUNCATE TABLE " . MAIN_DB_PREFIX . "c_codenaf";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			$error++;
		}

		// Insertion des entrées code NAF du fichier '/sirene/install/data/codenaf.csv'
		if (!$error) {
			while (($data = fgetcsv($handle, 4096, $separator, $enclosure, $escape)) !== false) {
				$num_line++;
				if (isset($data[0]) && !empty($data[0]) && isset($data[1]) && !empty($data[1])) {
					$sql = "INSERT INTO " . MAIN_DB_PREFIX . "c_codenaf(code, label, active)";
					$sql .= " VALUES('" . $this->db->escape(strtoupper($data[0])) . "', '" . $this->db->escape($data[1]) . "', 1)";
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->errors[] = $this->db->lasterror();
						$error++;
						break;
					} else {
						$nb_added++;
					}
				} else {
					$this->errors[] = $langs->trans('SireneErrorCodeNafFetchLine', $num_line, $filepath);
					$error++;
					break;
				}
			}
		}
		fclose($handle);

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $nb_added;
		}
	}

	/**
	 * Method to output saved errors
	 *
	 * @param	string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return $this->error . (is_array($this->errors) ? (!empty($this->error) ? $separator : '') . join($separator, $this->errors) : '');
	}
}
