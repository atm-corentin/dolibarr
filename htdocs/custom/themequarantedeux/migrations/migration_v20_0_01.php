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
 * Class ModuleThemeQuaranteDeuxMigrationV20_0_01 		Class to manage migration for version 20.0.01
 */
class ThemeQuaranteDeuxMigrationV20_0_01 extends AbstractMigration
{

	public $version = "20.0.01";
	public $description = 'Migration for ThemeQuaranteDeux version 20.0.01';
	public $name = 'themequarantedeux_20.0.01';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		// empty migration
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
