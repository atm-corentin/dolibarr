<?php

dol_include_once("/h2g2/class/abstractmigration.class.php");

/**
 * Class LaReponseMigrationV19_2_00       Class to manage migration for version 19.2.00
 *
 * This class will create all tables previously created through .sql files.
 * This will be the new initialisation file.
 */
class LaReponseMigrationV19_2_00 extends AbstractMigration
{
	public $version = "19.2.00"; // Version of execution
	public $description = "Migration for the version 19.2.00 of LaReponse module"; // Description
	public $name = "lareponseMigration_19.2.00"; // Migration name

	/**
	 * Method executed when we up the migration
	 *
	 * @return    void
	 */
	public function up()
	{
		global $db, $conf;

		// Create email template
		$mailTemplate = array(
			'label' => '(LaReponseSendArticles)',
			'type' => 'article',
			'topic' => '__(LaReponseMailArticlesUpdated)__',
			'content' => '__(LaReponseHello)__, <br />
							<br />
							__(LaReponseEmailTemplateArticleUpdated)__<br />
							<br />
							__LAREPONSE_ARTICLE_LIST_LINKS__'
		);

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE module = 'lareponse' AND label = '" . $db->escape($mailTemplate['label']) . "' AND entity = " . $conf->entity;

		$resql = $db->query($sql);

		if ($resql && !empty($db->fetch_object($resql))) {
			// Add mail templates

			$this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "c_email_templates (module,label,topic,content,type_template,position,enabled,entity) VALUES
			('lareponse',
			'" . $db->escape($mailTemplate['label']) . "',
			'" . $db->escape($mailTemplate['topic']) . "',
			'" . $db->escape($mailTemplate['content']) . "',
			'" . $db->escape($mailTemplate['type']) . "',
			 20,
			'\$conf->lareponse->enabled',
			 " . $conf->entity . "
			)");
		}

		// Add constants into actioncomm dictionary : MAIN_DB_PREFIX_c_actioncomm
		$codes = array(
			"ARTICLE_CR" => 'LaReponseEventArticleCreated',
			"ARTICLE_MO" => 'LaReponseEventArticleModified',
			"ARTICLE_DE" => 'LaReponseEventArticleDeleted',
			"COMMENT_CR" => 'LaReponseEventArticleCommentCreated'
		);
		$pos = 448075; // position of codes into the dictionary
		foreach ($codes as $code => $libelle) {
			$this->addQuery("INSERT INTO " . MAIN_DB_PREFIX . "c_actioncomm (id, code, type, module, libelle, active, position) VALUES
				(" . $pos . ",
				'" . $code . "',
				'module',
				'lareponse',
				'" . $libelle . "',
				1,
				" . $pos . ")"
			);
			$pos++;
		}
	}

	/**
	 * Method executed when we rollback the migration
	 *
	 * @return    void
	 */
	public function down()
	{
		global $db;

		// Remove old actioncomm types if v19.2.00 was set on a dolibarr V14+
		$codesToRename = array(
			"ARTICLE_CREATE",
			"ARTICLE_MODIFY",
			"ARTICLE_DELETE",
			"COMMENT_CREATE"
		);
		foreach ($codesToRename as $old) {
			$sql = "SELECT code FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE code = '" . $db->escape($old) . "'";

			$resql = $db->query($sql);

			if ($resql && !empty($db->fetch_object($resql))) $this->addQuery("DELETE FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE code = '" . $db->escape($old) . "'");
		}
	}
}
