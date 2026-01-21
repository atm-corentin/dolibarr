<?php
/* Copyright (C) 2023 Thomas Bacheley <thomas@code42.fr>
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

dol_include_once('h2g2/class/abstractmigration.class.php');

/**
 * Class ModuleQuaranteDeuxMigrationV19_0_01 		Class to manage migration for version 19.0.01
 */
class QuaranteDeuxMigrationV19_0_01 extends AbstractMigration
{

	public $version = "19.0.01";
	public $description = 'Migration for QuaranteDeux version 19.0.01';
	public $name = 'quarantedeux_19.0.01';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		global $conf, $db, $langs;

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "overwrite_trans WHERE transkey = 'PrintContentArea'");
		if ($resql->num_rows > 0) $db->query("UPDATE " . MAIN_DB_PREFIX . "overwrite_trans SET transvalue='" . $db->escape($langs->trans('LaReponsePrintContentArea')) . "' WHERE transkey = 'PrintContentArea'");
		else $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "overwrite_trans (entity, lang, transkey, transvalue) VALUES (" . $conf->entity . ", 'fr_FR', 'PrintContentArea', '" . $db->escape($langs->trans('LaReponsePrintContentArea')) . "')");

		// #287
		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "scriptsinject WHERE url LIKE '%ipad-OS.js' AND entity = " . $conf->entity);
		if ($resql->num_rows == 0)  $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "scriptsinject (url, date_creat, type, active, entity) VALUES ('" . dol_buildpath('/theme/quarantedeux/js/ipad-OS.js', 2) . "', '" . dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') . "', 'FOOTER', 1, " . $conf->entity . ")");
	}

	/**
	 * Method executed when we roll back the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
		global $db;

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "overwrite_trans WHERE transkey = 'PrintContentArea'");
		if ($resql->num_rows > 0) $db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'overwrite_trans WHERE transkey = "PrintContentArea"');
	}
}
