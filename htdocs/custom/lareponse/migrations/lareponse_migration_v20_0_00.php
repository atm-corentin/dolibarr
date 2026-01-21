<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV20_0_00       Class to manage migration for version 20.0.00
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV20_0_00 extends AbstractMigration
{
	public $version = "20.0.00"; // Version of execution
	public $description = "Migration for the version 20.0.00 of LaReponse module"; // Description
	public $name = "lareponseMigration_20.0.00"; // Migration name

	/**
	 * Method executed when we up the migration
	 *
	 * @return    void
	 */
	public function up()
	{
		$this->addQuery("ALTER TABLE " . MAIN_DB_PREFIX . "lareponse_article ADD COLUMN type tinyint DEFAULT 0");
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
