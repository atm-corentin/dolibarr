<?php
/* Copyright (C) 2024 Thomas Bacheley <thomas@code42.fr>
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
 * Class ModuleSuperSearchMigrationV18_4_02 		Class to manage migration for version 18.4.02
 */
class SuperSearchMigrationV18_4_02 extends AbstractMigration
{

	public $version = "18.4.02";
	public $description = 'Migration for SuperSearch version 18.4.02';
	public $name = 'supersearch_18.4.02';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		$this->addQuery("DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "supersearch_customization");

		global $user;

		// index customization table #29
		$this->addQuery(
			"
				CREATE TABLE " . MAIN_DB_PREFIX . "supersearch_customization(
				rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
				fk_user_modif integer,
				index_name varchar(255) NOT NULL,
				url varchar(255) NOT NULL,
				picto varchar(255) NOT NULL,
				color varchar(32) DEFAULT '#000000' NOT NULL,
				position integer,
				tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
			)
		"
		);

		$indexConfig = file_get_contents(dol_buildpath('/supersearch/documents/customization_dolibarr.json'));
		$indexConfig = json_decode($indexConfig);

		foreach ($indexConfig as $index => $value) {
			$this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "supersearch_customization (`fk_user_modif`, `index_name`, `url`, `picto`, `color`, `position`) VALUES (
			'" . $user->id . "',
			'" . $index . "',
			'" . $value->url . "',
			'" . $value->picto . "',
			'" . $value->color . "',
			'" . $value->position . "')
			");
		}
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

