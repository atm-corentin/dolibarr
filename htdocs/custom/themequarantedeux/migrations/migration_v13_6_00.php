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
 * Class ModuleThemeQuaranteDeuxMigrationV13_6_00 		Class to manage migration for version 13.6.00
 */
class ThemeQuaranteDeuxMigrationV13_6_00 extends AbstractMigration
{

	public $version = "13.6.00";
	public $description = 'Migration for ThemeQuaranteDeux version 13.6.00';
	public $name = 'themequarantedeux_13.6.00';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		global $conf, $db;

		$this->addQuery("CREATE TABLE " . MAIN_DB_PREFIX . "scriptsinject(
                rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
                url varchar(255) NOT NULL,
                date_creat datetime NOT NULL,
                tms timestamp,
                type varchar(255) NOT NULL DEFAULT 'HEADER',
                active integer NOT NULL DEFAULT 1,
                entity integer NOT NULL default 1
            )");

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "scriptsinject WHERE url LIKE '%scroll-reposition.js' AND entity = " . $conf->entity);
		if ($resql->num_rows == 0)  $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "scriptsinject (url, date_creat, type, active, entity) VALUES ('" . dol_buildpath('/theme/quarantedeux/js/scroll-reposition.js', 2) . "', '" . dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') . "', 'FOOTER', 1, " . $conf->entity . ")");

		$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "scriptsinject WHERE url LIKE '%makeorder-popup.js' AND entity = " . $conf->entity);
		if ($resql->num_rows == 0) $this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "scriptsinject (url, date_creat, type, active, entity) VALUES ('" . dol_buildpath('/theme/quarantedeux/js/makeorder-popup.js', 2) . "', '" . dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') . "', 'FOOTER', 1, " . $conf->entity . ")");
	}

	/**
	 * Method executed when we roll back the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
	}
}
