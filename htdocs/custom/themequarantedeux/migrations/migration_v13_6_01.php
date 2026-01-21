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
 * Class ModuleQuaranteDeuxMigrationV13_6_01 		Class to manage migration for version 13.6.01
 */
class QuaranteDeuxMigrationV13_6_01 extends AbstractMigration
{

	public $version = "13.6.01";
	public $description = 'Migration for QuaranteDeux version 13.6.01';
	public $name = 'quarantedeux_13.6.01';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		global $conf, $db;

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "scriptsinject WHERE url LIKE '%shrink-menu.js' AND entity = " . $conf->entity);
		if ($resql->num_rows == 0)  $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "scriptsinject (url, date_creat, type, active, entity) VALUES ('" . dol_buildpath('/theme/quarantedeux/js/shrink-menu.js', 2) . "', '" . dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') . "', 'FOOTER', 1, " . $conf->entity . ")");
	}

	/**
	 * Method executed when we roll back the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
		// empty migration
	}
}
