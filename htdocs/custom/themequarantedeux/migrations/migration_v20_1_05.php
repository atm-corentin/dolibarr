<?php
/* Copyright (C) 2025 Arthur Croix <arthur@code42.fr>
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
 * Class ModuleThemeQuaranteDeuxMigrationV20_1_05 		Class to manage migration for version 20.1.05
 */
class ThemeQuaranteDeuxMigrationV20_1_05 extends AbstractMigration
{

	public $version = "20.1.05";
	public $description = 'Migration for ThemeQuaranteDeux version 20.1.05';
	public $name = 'themequarantedeux_20.1.05';

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
