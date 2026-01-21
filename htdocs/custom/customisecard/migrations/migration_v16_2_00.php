<?php
/* Copyright (C) 2023 Ravi Trébuchet <ravi@code42.fr>
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
 * Class Module42_LibrairieMigrationV16_2_00 		Class to manage migration for version 16.2.00
 */
class CustomiseCardMigrationV16_2_00 extends AbstractMigration
{

	public $version = "16.2.00";
	public $description = 'Migration for customisecard version 16.2.00';
	public $name = 'customisecard_16.2.00';

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		// Create llx_customisecard_customcard table
		$this->addQuery('
            CREATE TABLE llx_customisecard_customcard (
            rowid SERIAL PRIMARY KEY NOT NULL,
            entity integer DEFAULT 1 NOT NULL, 
            date_creation datetime NOT NULL, 
            tms timestamp, 
            fk_user_creat integer NOT NULL, 
            fk_user_modif integer, 
            import_key varchar(14), 
            cfield text, 
            action varchar(30) NOT NULL,
            celement varchar(30) NOT NULL
            );
        ');
		// llx_customisecard_customcard keys
		$this->addQuery('ALTER TABLE llx_customisecard_customcard ADD INDEX idx_customisecard_customcard_rowid (rowid);');
		$this->addQuery('ALTER TABLE llx_customisecard_customcard ADD INDEX idx_customisecard_customcard_entity (entity);');
		$this->addQuery('ALTER TABLE llx_customisecard_customcard ADD CONSTRAINT llx_customisecard_customcard_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);');
		// Create llx_customisecard_customcard_extrafields table
		$this->addQuery('
            create table llx_customisecard_customcard_extrafields (
            rowid                     integer AUTO_INCREMENT PRIMARY KEY,
            tms                       timestamp,
            fk_object                 integer NOT NULL,
            import_key                varchar(14)                          		-- import key
            ) ENGINE=innodb;
        ');
		// llx_customisecard_customcard_extrafields keys
		$this->addQuery('ALTER TABLE llx_customisecard_customcard_extrafields ADD INDEX idx_fk_object(fk_object);');
	}

	/**
	 * Method executed when we roll back the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
		// Empty query
	}
}
