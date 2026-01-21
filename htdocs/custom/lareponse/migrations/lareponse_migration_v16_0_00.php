<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV16_0_00		Class to manage migration for version 16.0.00
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV16_0_00 extends AbstractMigration
{
	public $version = "16.0.00"; // Version of execution
	public $description = "Migration for the version 16.0.00 of LaReponse module"; // Description
	public $name = "lareponseMigration_16.0.00"; // Migration name

	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		// Update all tables
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD FULLTEXT index_lareponse_title_content (title, content)");
	}

	/**
	 * Method executed when we rollback the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
		// Empty because we don't need to clean datas
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article DROP INDEX `index_lareponse_title_content`");
	}
}
