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
 * Class ModuleThemeQuaranteDeuxMigrationV19_2_10 		Class to manage migration for version 19.2.10
 */
class ThemeQuaranteDeuxMigrationV19_2_10 extends AbstractMigration
{

	public $version = "19.2.10";
	public $description = 'Migration for ThemeQuaranteDeux version 19.2.10';
	public $name = 'themequarantedeux_19.2.10';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		global $conf, $db;

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "scriptsinject WHERE url LIKE '%xdebug-error.js' AND entity = " . $conf->entity);
		if ($resql->num_rows == 0)  $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "scriptsinject (url, date_creat, type, active, entity) VALUES ('" . dol_buildpath('/theme/quarantedeux/js/xdebug-error.js', 2) . "', '" . dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') . "', 'FOOTER', 1, " . $conf->entity . ")");
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
