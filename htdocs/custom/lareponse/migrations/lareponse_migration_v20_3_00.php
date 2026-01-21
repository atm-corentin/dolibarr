<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV20_3_00       Class to manage migration for version 20.3.00
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV20_3_00 extends AbstractMigration
{
	public $version = "20.3.00"; // Version of execution
	public $description = "Migration for the version 20.3.00 of LaReponse module"; // Description
	public $name = "lareponseMigration_20.3.00"; // Migration name

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
		// Empty because we don't need to clean datas
	}
}
