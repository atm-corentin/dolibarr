<?php
/* Copyright (C) 2024 SuperAdmin <thomas@code42.fr>
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
 * \file    core/triggers/interface_99_modSuperSearch_SuperSearchTriggers.class.php
 * \ingroup supersearch
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modSuperSearch_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for SuperSearch module
 */
class InterfaceSuperSearchTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "SuperSearch triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'supersearch@supersearch';
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
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $db;

		$langs->load('supersearch@supersearch');
		if (function_exists('isModEnabled')) {
			if (!isModEnabled('supersearch')) return 0; // If module is not enabled, we do nothing
		} elseif (!$conf->global->MAIN_MODULE_SUPERSEARCH) return 0;

		require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

		$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
		$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

		if (empty($adminKey) || empty($urlMeilisearch)) {
			$pageSettingsLink = '<a href="' . dol_buildpath('/supersearch/admin/setup.php', 1) . '"> ' . $langs->trans('SuperSearchSetupPage') . ' </a>';
			$message = '';

			if (empty($adminKey)) $message = $langs->trans('SuperSearchAdminKeyMissing');

			if (empty($urlMeilisearch)) {
				if (empty($message)) $message = $langs->trans('SuperSearchURLMissing');
				else $message .= ', ' . $langs->trans('SuperSearchURLMissing');
			}

			if ($user->admin) setEventMessage('Supersearch:' . $message . ' -> '. $pageSettingsLink, 'warnings');
			return 0;
		}

		dol_include_once('/supersearch/lib/supersearch.lib.php');

		switch ($action) {
			// Products
			case 'PRODUCT_CREATE':
			case 'COMPANY_CREATE':
			case 'ARTICLE_CREATE':
			case 'DEVICE_CREATE':
			case 'APPLICATION_CREATE':
			case 'FICHINTER_CREATE':
			case 'PROPAL_CREATE':
			case 'CONTACT_CREATE':
			case 'PROJECT_CREATE':
				$ret = supersearchCreateDocument($object);
				if ($ret['code'] < 1) {
					if ($user->admin) {
						if ($ret['code'] == -5)  setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $langs->trans('SuperSearchIncorrectClass') . '<br />Code ' . $ret['code'], 'warnings');
						else setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $ret['message'] .'<br />Code ' . $ret['code'], 'warnings');
					}
					return 0;
				}
				break;
			case 'PRODUCT_MODIFY':
			case 'COMPANY_MODIFY':
			case 'ARTICLE_MODIFY':
			case 'DEVICE_MODIFY':
			case 'APPLICATION_MODIFY':
			case 'FICHINTER_MODIFY':
			case 'PROPAL_MODIFY':
			case 'CONTACT_MODIFY':
			case 'PROJECT_MODIFY':
				$ret = supersearchUpdateDocument($object);
				if ($ret['code'] < 1) {
					if ($user->admin) {
						if ($ret['code'] == -5)  setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $langs->trans('SuperSearchIncorrectClass') . '<br />Code ' . $ret['code'], 'warnings');
						else setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $ret['message'] .'<br />Code ' . $ret['code'], 'warnings');
					}
					return 0;
				}
				break;
			case 'PRODUCT_DELETE':
			case 'COMPANY_DELETE':
			case 'ARTICLE_DELETE':
			case 'DEVICE_DELETE':
			case 'APPLICATION_DELETE':
			case 'FICHINTER_DELETE':
			case 'PROPAL_DELETE':
			case 'CONTACT_DELETE':
			case 'PROJECT_DELETE':
				$ret = supersearchDeleteDocument($object);
				if ($ret['code'] < 1) {
					if ($user->admin) {
						if ($ret['code'] == -5)  setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $langs->trans('SuperSearchIncorrectClass') . '<br />Code ' . $ret['code'], 'warnings');
						else setEventMessage('Supersearch: ' . $langs->trans('SuperSearchError') . '<br />' . $ret['message'] .'<br />Code ' . $ret['code'], 'warnings');
					}
					return 0;
				}
				break;
			default:
				dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}

		return 1;
	}
}
