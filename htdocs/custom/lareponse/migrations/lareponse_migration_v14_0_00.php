<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV14_0_00		Class to manage migration for version 14.0.00
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV14_0_00 extends AbstractMigration
{
	public $version = "14.0.00"; // Version of execution
	public $description = "Migration for the version 14.0.00 of LaReponse module"; // Description
	public $name = "lareponseMigration_14.0.00"; // Migration name

	/**
	 * Create all LaReponse tables
	 *
	 * @return void
	 */
	/* @deprecated Name of this method must be in CamelCase */
    // @codingStandardsIgnoreStart
	public function _createAllTables()
	{
        // @codingStandardsIgnoreEnd
		$this->addQuery(
			"CREATE TABLE ".MAIN_DB_PREFIX."lareponse_article(
            rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
            date_creation datetime NOT NULL, 
            tms timestamp, 
            fk_user_creat integer NOT NULL, 
            fk_user_modif integer, 
            import_key varchar(14), 
            title varchar(255) NOT NULL,
            content text,
            private integer NOT NULL,
            publish_token varchar(35),
            entity integer DEFAULT 1 NOT NULL
            )"
		);


		$this->addQuery(
			"create table ".MAIN_DB_PREFIX."lareponse_article_extrafields(
              rowid                     integer AUTO_INCREMENT PRIMARY KEY,
              tms                       timestamp,
              fk_object                 integer NOT NULL,
              import_key                varchar(14),                          		
              entity                    integer DEFAULT 1 NOT NULL
            )"
		);

		$this->addQuery(
			"CREATE TABLE ".MAIN_DB_PREFIX."lareponse_article_tag(
            rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
            fk_article integer NOT NULL, 
            fk_tag integer NOT NULL,
            entity integer DEFAULT 1 NOT NULL		
            )"
		);

		$this->addQuery(
			"CREATE TABLE ".MAIN_DB_PREFIX."lareponse_comment(
            rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
            date_creation datetime NOT NULL,
            tms timestamp,
            fk_user_creat integer NOT NULL,
            fk_user_modif integer,
            import_key varchar(14),
            fk_article integer NOT NULL,
            content text,
            entity integer DEFAULT 1 NOT NULL
            )"
		);

		$this->addQuery(
			"CREATE TABLE ".MAIN_DB_PREFIX."lareponse_favorites(
            rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
            date_creation datetime NOT NULL,
            fk_article integer NOT NULL,
            fk_user integer NOT NULL,
            entity integer DEFAULT 1 NOT NULL
            )"
		);

		$this->addQuery(
			"create table ".MAIN_DB_PREFIX."lareponse_article_tag_extrafields(
              rowid integer AUTO_INCREMENT PRIMARY KEY,
              tms timestamp,
              fk_object integer NOT NULL,
              import_key varchar(14),
              entity integer DEFAULT 1 NOT NULL
            )"
		);

		$this->addQuery(
			"create table ".MAIN_DB_PREFIX."lareponse_comment_extrafields(
              rowid                     integer AUTO_INCREMENT PRIMARY KEY,
              tms                       timestamp,
              fk_object                 integer NOT NULL,
              import_key                varchar(14),
              entity                    integer DEFAULT 1 NOT NULL
            )"
		);
	}

	/**
	 * Create all lareponse indexes for tables
	 *
	 * @return void
	 */
	private function _createIndexes()
	{
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_extrafields ADD INDEX idx_fk_object(fk_object)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag ADD INDEX idx_lareponse_article_tag_status (status)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag ADD CONSTRAINT llx_lareponse_article_tag_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag ADD INDEX idx_lareponse_article_tag_status (status)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag_extrafields ADD INDEX idx_fk_object(fk_object)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD INDEX idx_lareponse_article_rowid (rowid)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD CONSTRAINT llx_lareponse_article_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD INDEX idx_lareponse_article_ref (ref)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_comment ADD INDEX idx_lareponse_comment_rowid (rowid)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_comment ADD CONSTRAINT llx_lareponse_comment_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_comment_extrafields ADD INDEX idx_fk_object(fk_object)");
	}

	/**
	 * Create all lareponse update for tables
	 *
	 * @return void
	 */
	private function _updateTables()
	{
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_extrafields ADD entity integer DEFAULT 1 NOT NULL;");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article_tag_extrafields ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_comment ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_comment_extrafields ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_favorites ADD entity integer DEFAULT 1 NOT NULL");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article ADD publish_token varchar(35)");
		$this->addQuery("ALTER TABLE ".MAIN_DB_PREFIX."lareponse_article CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	}


	/**
	 * Method executed when we up the migration
	 *
	 * @return 	void
	 */
	public function up()
	{
		// Create all tables
		$this->_createAllTables();

		// Create all indexes for tables
		$this->_createIndexes();

		// Update all tables
		$this->_updateTables();
	}

	/**
	 * Method executed when we rollback the migration
	 *
	 * @return 	void
	 */
	public function down()
	{
		// Empty because we don't need to clean datas
	}
}
