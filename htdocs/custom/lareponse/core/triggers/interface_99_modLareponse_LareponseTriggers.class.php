<?php
/* Copyright (C) 2024 SuperAdmin <ravi@code42.fr>
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

/**
 * \file    core/triggers/interface_99_modLareponse_LareponseTriggers.class.php
 * \ingroup lareponse
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modLareponse_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/lareponse/lib/lareponse_article.lib.php');

/**
 *  Class of triggers for Lareponse module
 */
class InterfaceLareponseTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param  DoliDB    $db    Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Lareponse triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'lareponse@lareponse';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param  string          $action    Event action code
	 * @param  CommonObject    $object    Object
	 * @param  User            $user      Object user
	 * @param  Translate       $langs     Object langs
	 * @param  Conf            $conf      Object conf
	 * @return int                    Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->lareponse->enabled)) return 0; // If module is not enabled, we do nothing

		dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);

		// Or you can execute some code here
		$description = "";
		$eventName = "";
		$objectId = $object->id;
		switch ($action) {
			case "ARTICLE_DELETE":
				if (deleteArticleComment($objectId) > 0) dol_syslog("Trigger '" . $this->name . "' - Article comments deleted for id = " . $object->id, LOG_DEBUG);
				else {
					dol_syslog("Trigger '" . $this->name . "' - Article comments deleted for id = " . $object->id, LOG_ERR);
					return -1;
				}
				$eventName = "ARTICLE_DE";
			case "ARTICLECOMMENT_CREATE":
				if (empty($eventName)) $eventName = "COMMENT_CR";
				if (get_class($object) == "ArticleComment") {
					$description = $object->content;
					$objectId = $object->fk_article;
				}
			case "ARTICLE_CREATE":
				if (empty($eventName)) $eventName = "ARTICLE_CR";
			case "ARTICLE_MODIFY":
				if (empty($eventName)) $eventName = "ARTICLE_MO";
				if (empty($description)) $description = $object->title;
				if (!empty($eventName)) createArticleEvent($objectId, $eventName, $description);
				break;
			default:
				break;
		}

		return 0;
	}
}
