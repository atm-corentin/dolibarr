<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV19_2_01       Class to manage migration for version 19.2.01
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV19_2_01 extends AbstractMigration
{
	public $version = "19.2.01"; // Version of execution
	public $description = "Migration for the version 19.2.01 of LaReponse module"; // Description
	public $name = "lareponseMigration_19.2.01"; // Migration name

	/**
	 * Method executed when we up the migration
	 *
	 * @return    void
	 */
	public function up()
	{
		// Empty query
	}

	/**
	 * Method executed when we rollback the migration
	 *
	 * @return    void
	 */
	public function down()
	{
		// Empty query
	}
}
