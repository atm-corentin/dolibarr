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
 * Class ModuleSuperSearchMigrationV18_2_00 		Class to manage migration for version 18.2.00
 */
class SuperSearchMigrationV18_2_00 extends AbstractMigration
{

	public $version = "18.2.00";
	public $description = 'Migration for SuperSearch version 18.2.00';
	public $name = 'supersearch_18.2.00';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{

		$this->addQuery('UPDATE ' . MAIN_DB_PREFIX . 'const SET name = REPLACE(name, "SEARCHPLUS_", "SUPERSEARCH_") WHERE name LIKE "SEARCHPLUS_%"'); // for the key,url and hit per page
		$this->addQuery('DELETE FROM ' . MAIN_DB_PREFIX . 'const WHERE name LIKE "MAIN_MODULE_SEARCHPLUS_%"');
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
