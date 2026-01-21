<?php
/* Copyright (C) 2024 Thomas BACHELEY thomas@code42.fr
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

include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

/**
 * \file    supersearch/lib/supersearch.lib.php
 * \ingroup supersearch
 * \brief   Library files with common functions for SuperSearch
 */

/**
 * Prepare admin pages header
 * @return array
 */
function supersearchAdminPrepareHead()
{
	global $langs, $conf, $user, $db;

	$langs->load("supersearch@supersearch");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/supersearch/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i> ' . $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/supersearch/admin/indexes.php", 1);
	$head[$h][1] = '<i class="fas fa-indent"></i> ' . $langs->trans("Index");
	$head[$h][2] = 'index';
	$h++;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

	if (isset($urlMeilisearch)) {
		$head[$h][0] = dol_buildpath("/supersearch/admin/tasks_manage.php", 1);
		$head[$h][1] = '<i class="fas fa-cog"></i> ' . $langs->trans("SuperSearchTasks");
		$head[$h][2] = 'tasks';
		$h++;

		$head[$h][0] = dol_buildpath("/supersearch/admin/data_migrations.php", 1);
		$head[$h][1] = '<i class="fas fa-file-import"></i> ' . $langs->trans("SuperSearchDataMigrations");
		$head[$h][2] = 'data_migrations';
		$h++;
	}

	if ($conf->h2g2->enabled) {
		dol_include_once('/h2g2/lib/h2g2.lib.php');
		$langs->load('h2g2@h2g2');
		if ($user->admin && isH2G2InstalledWithMinVersion('15.0.03')) {
			// H2G2 migration page only for admin
			$head[$h][0] = dol_buildpath('/h2g2/admin/migration_page.php?module=modSuperSearch&modulePath=/supersearch/core/modules/modSuperSearch.class.php', 1);
			$head[$h][1] = '<i class="fas fa-wrench"></i> ' . $langs->trans("SuperSearchMigrationPageTitle");
			$head[$h][2] = 'migration';
			$h++;

			// H2G2 information page
			$head[$h][0] = dol_buildpath('/h2g2/admin/information_page.php?module=modSuperSearch&modulePath=/supersearch/core/modules/modSuperSearch.class.php', 1);
			$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("SuperSearchInformationPageTitle");
			$head[$h][2] = 'information';
			$h++;

			// Changelog page
			$head[$h][0] = dol_buildpath("/supersearch/admin/changelog.php", 1);
			$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("SuperSearchChangelog");
			$head[$h][2] = 'changelog';
			$h++;
		}
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supersearch@supersearch');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supersearch@supersearch', 'remove');

	return $head;
}

/**
 * Setup curl
 * @param string  $endpoint endpoint without the first '/'
 * @param boolean $admin    if request need Masterkey (true) or not (by default false -> APIkey)
 * @return    CurlHandle|false             return curl initialized or -1 if error
 */
function supersearchCurlInit($endpoint, $admin = false)
{
	global $db;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

	if (!isset($urlMeilisearch)) return -1;
	else {
		if ($admin) {
			$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
			$masterKey = dolibarr_get_const($db, 'SUPERSEARCH_MASTERKEY'); // TODO : CHeck if need to be removed
			if (isset($adminKey)) $key = $adminKey;
			else $key = $masterKey;
		} else {
			$searchKey = dolibarr_get_const($db, 'SUPERSEARCH_APIKEY');
			$key = $searchKey;
		}

		$curl = curl_init($urlMeilisearch . '/' . $endpoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $key]);

		return $curl;
	}
}


/**
 * Create document in meilisearch
 * @param object $object              Dolibarr object to create in meilisearch
 * @return int                    	   1 => Success<br>
 *                                     0 => Meilisearch Url isn't set<br>
 *                                    -1 => curl Error<br>
 *                                    -2 => Unknow Error<br>
 *                                    -3 => Unauthorized<br>
 *                                    -4 => Payload Too Large<br>
 *                                	  -5 => Incorrect Class<br>
 *                                    -6 => rowid/id isn't find
 */
function supersearchCreateDocument($object)
{
	global $db, $user, $conf, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

	$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;
	$dataToSend = new stdClass();

	$resql = $db->query('SHOW COLUMNS FROM ' . MAIN_DB_PREFIX . $object->table_element); // retrieve the columns from the DB to align with the data import.
	while ($res = $db->fetch_object($resql)) {
		$fieldKey = $res->Field;
		if (($res->Type == 'timestamp' || $res->Type == 'datetime')) $dataToSend->$fieldKey = setTimeObject($object, $fieldKey); // time process (mostly for datec and tms)
		else $dataToSend->$fieldKey = $object->$fieldKey ?? GETPOST($fieldKey);
	}

	if (empty($object->rowid)) {
		if (empty($object->id)) {
			return -6;
		} else {
			$dataToSend->rowid = $object->id;
		}
	} else {
		$dataToSend->rowid = $object->rowid;
	}

	$object->rowid = $dataToSend->rowid;
	$dataToSend->fk_user_creat = $user->id;
	$dataToSend->fk_user_modif = $user->id;
	$dataToSend->entity = $conf->entity;

	$objectClass = get_class($object);
	$index = array('product', 'societe', 'article', 'device', 'application', 'propal', 'contact', 'project');

	if (strpos(strtolower($objectClass), 'intervention') !== false) $objectClass = 'Fichinter';
	else {
		$invalidClass = 1;
		foreach ($index as $class) {
			if (strpos(strtolower($objectClass), $class) !== false && $invalidClass) {
				$objectClass = ucfirst(strtolower($class));
				$invalidClass = 0;
			}
		}
		if ($invalidClass) return array('code' => -5);
	}

	switch ($objectClass) {
		case 'Product':
			unset($dataToSend->fk_user_creat); // in db it's "fk_user_author" and not "fk_user_creat"
			$dataToSend->fk_user_author = $user->id;
			$dataToSend->fk_product_type = $object->type ?? GETPOST('type'); //Product or Service
			$dataToSend->description = GETPOST('desc');
			break;
		case 'Propal':
			$dataToSend->fk_soc = $object->socid;
			break;
		default:
			break;
	}

	$curl = supersearchCurlInit('indexes/' . $uniqueId . '_' . $objectClass);
	$response = curl_exec($curl);
	$response = json_decode($response, true);

	if (!isset($response['uid'])) initIndexes($objectClass);

	$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
	//?primaryKey=rowid -> make sure to create index with the good primaryKey
	return supersearchCurlRequest('indexes/' . $uniqueId . '_' . $objectClass . '/documents?primaryKey=rowid', 'POST', $crudKey, $dataToSend, $object, 'create');
}

/**
 * Update document in meilisearch
 * @param object $object           Dolibarr object to update in meilisearch
 * @return int                 1 => Success<br>
 *                                 0 => Meilisearch Url isn't set<br>
 *                                 -1 => curl Error<br>
 *                                 -2 => Unknow Error<br>
 *                                 -3 => Unauthorized<br>
 *                                 -4 => Payload Too Large<br>
 *                             	   -5 => Incorrect Class
 */
function supersearchUpdateDocument($object)
{
	global $user, $db, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

	$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
	$uniqueId = $dolibarr_main_instance_unique_id ?: $dolibarr_main_cookie_cryptkey;

	$dataToSend = new stdClass();

	$resql = $db->query('SELECT * FROM ' . MAIN_DB_PREFIX . $object->table_element . ' WHERE rowid = ' . ($object->rowid ?: $object->id)); // get information from DB because all fields aren't in getpost
	if ($resql) {
		$res = $db->fetch_object($resql);
		foreach ($res as $key => $value) {
			if (GETPOST($key) != '') $dataToSend->$key = GETPOST($key);
			else $dataToSend->$key = $value;
		}
	}

	$objectClass = get_class($object);

	$index = array('product', 'societe', 'article', 'device', 'application', 'propal', 'contact', 'project');

	if (strpos(strtolower($objectClass), 'intervention') !== false) $objectClass = 'Fichinter';
	else {
		$invalidClass = 1;
		foreach ($index as $class) {
			if (strpos(strtolower($objectClass), $class) !== false && $invalidClass) {
				$objectClass = ucfirst(strtolower($class));
				$invalidClass = 0;
			}
		}
		if ($invalidClass) return array('code' => -5);
	}

	switch ($objectClass) {
		case 'Product':
			$desc = GETPOST('desc');
			$dataToSend->description = $desc;
			break;
		default:
			break;
	}

	$dataToSend->rowid = $object->rowid ?: $object->id;
	$dataToSend->fk_user_modif = $user->id;

	return supersearchCurlRequest('indexes/' . $uniqueId . '_' . $objectClass . '/documents', 'PUT', $crudKey, $dataToSend, $object, 'update');
}

/**
 * Delete document in meilisearch
 * @param object $object      Dolibarr object to delete in meilisearch
 * @return int                1 => Success<br>
 *                            0 => Meilisearch Url isn't set<br>
 *                            -1 => curl Error<br>
 *                            -2 => Unknow Error<br>
 *                            -3 => Unauthorized<br>
 *                            -4 => Payload Too Large<br>
 *                            -5 => Incorrect Class<br>
 *                            see https://bump.sh/meilisearch/doc/meilisearch/operation/operation-indexes-list#operation-indexes-list-responses for more information about error code
 */
function supersearchDeleteDocument($object)
{
	global $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey, $db, $langs;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
	$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

	$object->rowid = $object->rowid ?: $object->id;
	$objectClass = get_class($object);
	$index = array('product', 'societe', 'article', 'device', 'application', 'propal', 'contact', 'project');

	if (strpos(strtolower($objectClass), 'intervention') !== false) $objectClass = 'Fichinter';
	else {
		$invalidClass = 1;
		foreach ($index as $class) {
			if (strpos(strtolower($objectClass), $class) !== false && $invalidClass) {
				$objectClass = ucfirst(strtolower($class));
				$invalidClass = 0;
			}
		}
		if ($invalidClass) return array('code' => -5);
	}

	if (!isset($urlMeilisearch)) {
		$error = array('code' => 0, 'message' => $langs->trans("SuperSearchURLMissing"));
		return $error;
	} else {
		$curl = curl_init($urlMeilisearch . '/indexes/' . $uniqueId . '_' . $objectClass . '/documents/' . $object->rowid); // delete by id
		$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $crudKey,
				'Content-Type: application/json'
			]
		]);

		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			$error = array('code' => -1, 'message' => curl_error($curl));
			return $error;
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$response = json_decode($response, true);
			if ($httpCode == 202) { // Accepted
				createLog('delete', $object);
				$error = array('code' => 1, 'message' => 'OK');
				return $error;
			} elseif ($httpCode == 401) { // Unauthorized
				dol_syslog('Meilisearch::fail when try to delete ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -3, 'message' => $response['message']);
				return $error;
			} elseif ($httpCode == 413) { // Payload Too Large
				dol_syslog('Meilisearch::fail when try to delete ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -4, 'message' => $response['message']);
				return $error;
			} else {
				dol_syslog('Meilisearch::fail when try to delete ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -2, 'message' => $response['message']);
				return $error;
			}
		}
	}
}

/**
 * Sets the time for the specified object field.
 *
 * @param object $object The Dolibarr object containing the field to set.
 * @param string $field  The name of the field to retrieve or update with the time.
 * @return string The formatted date/time as 'YYYY-MM-DD HH:MM:SS', based on the user's timezone.
 */
function setTimeObject($object, $field)
{
	$time = dol_now();
	if (isset($object->$field)) $time = $object->$field;
	elseif (GETPOST($field) != '') $time = GETPOST($field);
	return dol_print_date($time, '%Y-%m-%d %H:%M:%S', 'tzuserrel');
}

/**
 * Create ActionComm and Log based on the action passed to the function
 * @param string $action Must be create/update/delete
 * @param object $object The object concerned by the action
 * @return void
 */
function createLog($action, $object)
{
	global $db, $langs, $user;

	$actioncomm = new ActionComm($db);
	$actioncomm->type_code = 'AC_OTH_AUTO';

	$actioncomm->datep = dol_now();
	$actioncomm->datef = dol_now();
	$actioncomm->percentage = -1; // Not applicable
	$actioncomm->authorid = $user->id; // User saving action
	$actioncomm->userownerid = $user->id; // Owner of action

	$actioncomm->fk_element = $object->rowid;
	$actioncomm->elementtype = strtolower(get_class($object));

	switch ($action) {
		case 'create':
			$actioncomm->label = $langs->trans('SuperSearchInsertMeilisearch', $langs->trans(get_class($object)));
			$actioncomm->note_private = $langs->trans('SuperSearchInsertMeilisearch', $langs->trans(get_class($object)));
			break;
		case 'update':
			$actioncomm->label = $langs->trans('SuperSearchUpdateMeilisearch', $langs->trans(get_class($object)));
			$actioncomm->note_private = $langs->trans('SuperSearchUpdateMeilisearch', $langs->trans(get_class($object)));
			break;
		case 'delete':
			$actioncomm->label = $langs->trans('SuperSearchDeleteMeilisearch', $langs->trans(get_class($object)), $object->rowid);
			$actioncomm->note_private = $langs->trans('SuperSearchDeleteMeilisearch', $langs->trans(get_class($object)), $object->rowid);
			break;
		default:
			break;
	}

	$actioncomm->create($user);
	dol_syslog('Meilisearch::' . $action . ':' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
}


/**
 * Send Request to Meilisearch
 * @param string $endpoint    endpoint without the first '/'
 * @param string $method      POST or PUT
 * @param string $key         API key who perform (Mostly APIKEY or CRUDKEY)
 * @param object $dataToSend  Dolibarr object transform to fit with meilisearch
 * @param object $object      Dolibarr Object
 * @param string $action      action in progress (example : 'create', 'update'...)
 * @return array              return a message and a code based on<br>
 *                            1 => Success<br>
 *                            0 => Meilisearch Url isn't set<br>
 *                            -1 => curl Error<br>
 *                            -2 => Unknow Error<br>
 *                            -3 => Unauthorized<br>
 *                            -4 => Payload Too Large<br><br>
 *                            see https://bump.sh/meilisearch/doc/meilisearch/operation/operation-indexes-list#operation-indexes-list-responses for more information about error code
 */
function supersearchCurlRequest($endpoint, $method, $key, $dataToSend, $object, $action)
{
	global $db, $langs;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

	if (!isset($urlMeilisearch)) {
		$error = array('code' => 0, 'message' => $langs->trans("SuperSearchURLMissing"));
		return $error;
	} else {
		$curl = curl_init($urlMeilisearch . '/' . $endpoint);
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $key,
				'Content-Type: application/json'
			],
			CURLOPT_POSTFIELDS => json_encode($dataToSend, JSON_PRETTY_PRINT),
		]);

		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			$error = array('code' => -1, 'message' => curl_error($curl));
			return $error;
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$response = json_decode($response, true);
			if ($httpCode == 202) { // Accepted
				createLog($action, $object);
				$error = array('code' => 1, 'message' => 'OK');
				return $error;
			} elseif ($httpCode == 401) { // Unauthorized
				dol_syslog('Meilisearch::fail when try to ' . $action . ' ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -3, 'message' => $response['message']);
				return $error;
			} elseif ($httpCode == 413) { // Payload Too Large
				dol_syslog('Meilisearch::fail when try to ' . $action . ' ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -4, 'message' => $response['message']);
				return $error;
			} else {
				dol_syslog('Meilisearch::fail when try to ' . $action . ' ' . get_class($object) . ' in meilisearch rowid : ' . $object->rowid, LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				$error = array('code' => -2, 'message' => $response['message']);
				return $error;
			}
		}
	}
}

/**
 * Import Data from Dolibarr in Meilisearch based on what contain DataTable
 * @param string $index                   the name of index (ex: 'Product', 'Societe',....)
 * @param string $indexTable              the name of table (without llx) from object
 * @return int                            1 => Success<br>
 *                                        0 => No resql<br>
 *                                        -1 => Meilisearch Url isn't set<br>
 *                                        -2 => curl Error<br>
 *                                        -3 => Unknow Error<br>
 *                                        -4 => Unauthorized<br>
 *                                        -5 => Payload Too Large<br><br>
 *                                        see https://bump.sh/meilisearch/doc/meilisearch/operation/operation-indexes-list#operation-indexes-list-responses for more information about error code
 */
function importDataToMeilisearch($index, $indexTable)
{
	global  $db;

	$resql = $db->query('SELECT * FROM ' . MAIN_DB_PREFIX . $indexTable);
	if (!$resql) return 0;
	else {
		$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

		$dataToSend = array();
		while ($res = $db->fetch_object($resql)) {
			$dataToSend[] = $res;
		}

		if (!isset($urlMeilisearch)) return -1;
		else {
			$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
			$curl = supersearchCurlInit('indexes/' . $index . '/documents');
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'PUT',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $crudKey,
					'Content-Type: application/json'
				],
				CURLOPT_POSTFIELDS => json_encode($dataToSend, JSON_PRETTY_PRINT),
			]);

			$response = curl_exec($curl);

			if (curl_errno($curl)) return -2;
			else {
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$response = json_decode($response, true);
				if ($httpCode == 202) { // Accepted
					supersearchCurlClose($curl);
					return 1;
				} elseif ($httpCode == 401) { // Unauthorized
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -4;
				} elseif ($httpCode == 413) { // Payload Too Large
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -5;
				} else {
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -3;
				}
			}
		}
	}
}

/**
 * Delete Data in Meilisearch, then re-import data from dolibarr in Meilisearch
 * @param string $index                   the name of index (ex: 'Product', 'Societe',....)
 * @param string $indexTable              the name of table (without llx) from object (ex: 'product', 'societe')
 * @return int                            1 => Success<br>
 *                                        0 => Meilisearch Url isn't set<br>
 *                                        -1 => No response from DB<br>
 *                                        -2 => curl Error<br>
 *                                        -3 => Unknow Error<br>
 *                                        -4 => Unauthorized<br>
 *                                        -5 => Payload Too Large<br><br>
 *                                        see https://bump.sh/meilisearch/doc/meilisearch/operation/operation-indexes-list#operation-indexes-list-responses for more information about error code
 */
function deleteDataToMeilisearch($index, $indexTable)
{
	global $db;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

	if (!isset($urlMeilisearch)) return 0;
	else {
		$resql = $db->query('SELECT * FROM ' . MAIN_DB_PREFIX . $indexTable);
		if (!$resql) return -1;
		else {
			$dataToSend = array();
			while ($res = $db->fetch_object($resql)) {
				$dataToSend[] = $res;
			}

			$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
			// Clean Index
			$curl = supersearchCurlInit('indexes/' . $index . '/documents');
			curl_setopt_array($curl, [
				CURLOPT_CUSTOMREQUEST => 'DELETE',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $crudKey,
				]
			]);

			curl_exec($curl);
			if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 202) {
				supersearchCurlClose($curl);
				return -3;
			}

			// Add Documents
			$curl = supersearchCurlInit('indexes/' . $index . '/documents');
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $crudKey,
					'Content-Type: application/json'
				],
				CURLOPT_POSTFIELDS => json_encode($dataToSend, JSON_PRETTY_PRINT),
			]);
			$response = curl_exec($curl);

			if (curl_errno($curl)) {
				return -1;
			} else {
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$response = json_decode($response, true);
				if ($httpCode == 202) { // Accepted
					supersearchCurlClose($curl);
					return 1;
				} elseif ($httpCode == 401) { // Unauthorized
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -3;
				} elseif ($httpCode == 413) { // Payload Too Large
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -4;
				} else {
					dol_syslog('Meilisearch::fail when try to update ' . $index . '::message::' . $response['message'], LOG_DEBUG);
					dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
					supersearchCurlClose($curl);
					return -2;
				}
			}
		}
	}
}

/**
 * Empty the data present in the index in question
 * @param string $index                   the name of index (ex: 'Product', 'Societe',....)
 * @return int                            1 => Success<br>
 *                                        0 => Meilisearch Url isn't set<br>
 *                                        -1 => curl Error<br>
 *                                        -2 => Unknow Error<br>
 *                                        -3 => Unauthorized<br>
 *                                        -4 => Not Found<br><br>
 *                                        see https://bump.sh/meilisearch/doc/meilisearch/operation/operation-indexes-list#operation-indexes-list-responses for more information about error code
 */
function emptyIndex($index)
{

	global $db;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');

	if (!isset($urlMeilisearch)) return 0;
	else {
		$crudKey = dolibarr_get_const($db, 'SUPERSEARCH_CRUDKEY');
		// Clean Index
		$curl = supersearchCurlInit('indexes/' . $index . '/documents');
		curl_setopt_array($curl, [
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $crudKey,
			]
		]);

		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			return -1;
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$response = json_decode($response, true);
			if ($httpCode == 202) { // Accepted
				supersearchCurlClose($curl);
				return 1;
			} elseif ($httpCode == 401) { // Unauthorized
				dol_syslog('Meilisearch::fail when try to empty ' . $index . '::message::' . $response['message'], LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				return -3;
			} elseif ($httpCode == 404) { // Not Found
				dol_syslog('Meilisearch::fail when try to empty ' . $index . '::message::' . $response['message'], LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				return -4;
			} else {
				dol_syslog('Meilisearch::fail when try to empty ' . $index . '::message::' . $response['message'], LOG_DEBUG);
				dol_syslog('Meilisearch::error ' . $httpCode . '::message::' . $response['message'], LOG_DEBUG);
				supersearchCurlClose($curl);
				return -2;
			}
		}
	}
}



/**
 * Initialize indexes
 * Checks if each index exists. If not, creates the index
 *
 * @param 	string 	$indexName 		name of index (className of object), default is null
 * @return void
 */
function initIndexes($indexName = '')
{
	global $db, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

	$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
	$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

	if (empty($indexName)) {
		$indexes = array(
			'Product',
			'Societe',
			'Article',
			'Device',
			'Application',
			'Fichinter',
			'Propal',
			'Contact',
			'Project'
		);
	} else {
		$indexes = array($indexName);
	}

	foreach ($indexes as $index) {
		$curl = supersearchCurlInit('indexes/' . $uniqueId . '_' . $index); // check if index exists
		$response = curl_exec($curl);
		$response = json_decode($response, true);
		supersearchCurlClose($curl);

		if (!isset($response['uid'])) { // index doesn't exist
			$dataToSend = array('uid' => $uniqueId . '_' . $index, 'primaryKey' => 'rowid');
			$curl = supersearchCurlInit('indexes', 1); // create/inject index on meilisearch
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $adminKey,
					'Content-Type: application/json'
				],
				CURLOPT_POSTFIELDS => json_encode($dataToSend, JSON_PRETTY_PRINT),
			]);
			curl_exec($curl);
			supersearchCurlClose($curl);
		}
	}
}

/**
 * Initialize API keys for different permission levels (Admin, CRUD, Search).
 *
 * Checks if keys exist. If not, or if they are invalid,
 * it creates or regenerates the keys and stores them in $conf.
 *
 * @return int			< 0 if K0, > 0 if OK
 */
function initKeys()
{
	global $conf, $db, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

	$masterKey = dolibarr_get_const($db, 'SUPERSEARCH_MASTERKEY');
	$uniqueId = $dolibarr_main_instance_unique_id ?: $dolibarr_main_cookie_cryptkey;

	$keys = array(
		array(
			'dolConst' => 'SUPERSEARCH_ADMINKEY',
			'settings' => array(
				'name' => $uniqueId . '_AdminKey',
				'actions' => array('*'),
				'indexes' => array($uniqueId . '_*'),
				'description' => "CRUD Key for " . $conf->global->MAIN_INFO_SOCIETE_NOM,
				'expiresAt' => null
			)
		),
		array(
			'dolConst' => 'SUPERSEARCH_CRUDKEY',
			'settings' => array(
				'name' => $uniqueId . '_CRUDKey',
				'actions' => array('documents.*', 'indexes.*'),
				'indexes' => array($uniqueId . '_*'),
				'description' => "CRUD Key for " . $conf->global->MAIN_INFO_SOCIETE_NOM,
				'expiresAt' => null
			)
		),
		array(
			'dolConst' => 'SUPERSEARCH_APIKEY',
			'settings' => array(
				'name' => $uniqueId . '_SearchKey',
				'actions' => array('search', 'indexes.get'),
				'indexes' => array($uniqueId . '_*'),
				'description' => "Search Key for " . $conf->global->MAIN_INFO_SOCIETE_NOM,
				'expiresAt' => null
			)
		),
	);

	foreach ($keys as $key) {
		$keySettings = $key['settings'];
		$constName = $key['dolConst'];

		if (isset($conf->global->$constName)) {
			$curl = supersearchCurlInit('keys/' . $conf->global->$constName, 1);

			$response = curl_exec($curl);
			supersearchCurlClose($curl);
			$response = json_decode($response, true);
			if (!isset($response['key'])) {
				$curl = supersearchCurlInit('keys', 1);
				curl_setopt_array($curl, [
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_HTTPHEADER => [
						'Authorization: Bearer ' . $masterKey,
						'Content-Type: application/json'
					],
					CURLOPT_POSTFIELDS => json_encode($keySettings, JSON_PRETTY_PRINT),
				]);

				$response = curl_exec($curl);
				$response = json_decode($response, true);

				dolibarr_set_const($db, $constName, $response['key']); // Set const after meilisearch sent it
				supersearchCurlClose($curl);
			}
		} else {
			$curl = supersearchCurlInit('keys', 1);
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $masterKey,
					'Content-Type: application/json'
				],
				CURLOPT_POSTFIELDS => json_encode($keySettings, JSON_PRETTY_PRINT),
			]);

			$response = curl_exec($curl);
			$response = json_decode($response, true);
			supersearchCurlClose($curl);

			if (!isset($response['key'])) return -1;

			dolibarr_set_const($db, $constName, $response['key']);
		}
	}

	$ret = dolibarr_del_const($db, 'SUPERSEARCH_MASTERKEY');

	return $ret;
}

/**
 * Convert value from array for meilisearch format
 *
 * @param $array Array with value to convert
 * @return mixed value converted
 */
function convertValues($array)
{
	if (is_null($array)) return null;
	if (is_array($array)) {
		foreach ($array as $key => $val) {
			if ($key === 'displayedAttributes' && !in_array("rowid", $val)) {
				$val[] = "rowid";
				$array[$key] = convertValues($val);
			} elseif ($key === 'searchCutoffMs' && (is_null($val) || $val === "")) unset($array[$key]); // Remove 'searchCutoffMs' if null or empty, as it causes issues but is default to null
			else $array[$key] = convertValues($val);
		}
	} elseif (is_object($array)) {
		foreach ($array as $key => $val) {
			if ($key === 'searchCutoffMs' && (is_null($val) || $val === "")) unset($array->$key); // Remove 'searchCutoffMs' if null or empty, as it causes issues but is default to null
			else $array->$key = convertValues($val);
		}
	} elseif (is_string($array)) {
		if (strtolower($array) === 'true') return true;
		elseif (strtolower($array) === 'false') return false;
		if (is_numeric($array)) return (int) $array;
	}

	return $array;
}

/**
 * Update settings for a index
 *
 * @param string $indexName The name of the index to update
 * @param array $settings An associative array of settings to update with new values
 * @return array An array containing the status, message, and any Meilisearch error (if applicable)
 */
function updateSettings($indexName, $settings)
{
	global $db, $langs, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;
	$uniqueId = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

	$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
	if (!isset($urlMeilisearch)) {
		return array('statut' => 400, 'message' => $langs->trans('SuperSearchErrorNotProvided', 'URL'), 'meilisearch_error' => null);
	} else {
		$curl = curl_init();
		$settings = convertValues($settings);
		$settings = json_encode($settings, JSON_PRETTY_PRINT);

		$urlMeilisearch = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
		$adminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');

		if (strpos($indexName, $uniqueId) === false) $indexName = $uniqueId . '_' . $indexName;

		curl_setopt_array($curl, [
			CURLOPT_URL => $urlMeilisearch . '/indexes/' . $indexName . '/settings',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'PATCH',
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $adminKey,
				'Content-Type: application/json'
			],
			CURLOPT_POSTFIELDS => $settings,
		]);

		$response = curl_exec($curl);
		$response = json_decode($response, true);
		supersearchCurlClose($curl);

		if (isset($response['taskUid'])) return array('statut' => 200, 'message' => $langs->trans('SuperSearchSettingsUpdated'), 'meilisearch_error'=> null);
		else return array('statut' => 400, 'message' => $response['message'], 'meilisearch_error' => $response);
	}
}

/**
 * Needed because in php7->curl = ressource / in php8 curl->CurlHandle
 *
 * @param curlHandle|Ressource $curl curl to close
 * @return void
 */
function supersearchCurlClose($curl)
{
	if (is_resource($curl) || $curl instanceof CurlHandle) curl_close($curl);
}

/**
 * Get index Customization
 *
 * @param string $index name of the index to get customization (optional).
 * @return Object index customization
 */
function getIndexCustomization($index = '')
{
	global $db;

	$config = new stdClass();

	$sql = "SELECT index_name, url, picto, color, position, disabled FROM " . MAIN_DB_PREFIX . "supersearch_customization " . (!empty($index) ? "WHERE index_name LIKE '%" . $index . "%' " : "") . "ORDER by position ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$indexname = $obj->index_name;
			$config->$indexname['url'] = $obj->url;
			$config->$indexname['picto'] = $obj->picto;
			$config->$indexname['color'] = $obj->color;
			$config->$indexname['position'] = $obj->position;
			$config->$indexname['disabled'] = $obj->disabled;
		}
	}

	return $config;
}

/**
 * Get index
 *
 * @return Object return all index
 */
function getIndex()
{
	global $db;

	$config = array();

	$sql = "SELECT index_name FROM " . MAIN_DB_PREFIX . "supersearch_customization ORDER by position ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$config[] = $obj->index_name;
		}
	}

	return $config;
}

/**
 * Get index position
 *
 * @param string $index name of the index to get position (optional).
 * @return Object index position
 */
function getIndexPosition($index = '')
{
	global $db;

	$config = new stdClass();

	$sql = "SELECT index_name, position FROM " . MAIN_DB_PREFIX . "supersearch_customization " . (!empty($index) ? "WHERE index_name LIKE '%" . $index . "%' " : "") . "ORDER by position ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$indexname = $obj->index_name;
			$config->$indexname['position'] = $obj->position;
		}
	}

	return $config;
}

/**
 * Update in DB all the index customization
 *
 * @param Object $customization object contains the customization for each index
 * @return int                >0 if OK, <0 if KO
 */
function updateIndexCustomization($customization)
{
	global $db, $user;

	$error = 1;

	foreach ($customization as $index => $val) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "supersearch_customization SET";
		$sql .= " url = '" . $val['url'] . "'";
		$sql .= ", picto = '" . $val['picto'] . "'";
		$sql .= ", color = '" . $val['color'] . "'";
		$sql .= ", position = '" . $val['position'] . "'";
		$sql .= ", disabled = '" . $val['disabled'] . "'";
		$sql .= ", fk_user_modif = '" . $user->id . "'";
		$sql .= " WHERE index_name = '" . $index . "'";
		$resql = $db->query($sql);
		if (!$resql) {
			$error = -1;
		}
	}

	return $error;
}

/**
 * Reset displayed fields for each index or specific index if provided
 *
 * @param string 	$index 		Optional. Name of the specific index to reset. If empty, all indexes will be reset.
 * @return void
 */
function resetAttributes($index = '')
{
	global $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

	$dolUID = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;

	$settingsDirPath = '/supersearch/documents/settings_meilisearch';

	if (!empty($index)) {
		$settings = json_decode(file_get_contents(dol_buildpath($settingsDirPath . '/' . strtolower($index) . '.json'), 1));
		updateSettings($dolUID . '_' . $index, $settings);
	} else {
		$handle = opendir(dol_buildpath($settingsDirPath));
		while (($entry = readdir($handle)) !== false) { // $entry can be '.', '..', 'application.json'
			if ($entry != "." && $entry != "..") {
				$index = ucfirst(str_replace('.json', '', $entry));
				$settings = json_decode(file_get_contents(dol_buildpath($settingsDirPath . '/' . $entry)));
				updateSettings($dolUID . '_' . $index, $settings);
			}
		}
	}
}


/**
 * Disable Supersearch on every entity
 *
 *	@return     void                    <0 if KO, >0 if OK
 */
function disableSuperSearch()
{
	global $mc, $conf, $db;

	if (!empty($conf->multicompany->enabled) && $conf->multicompany->enabled) {
		$arrayofentities = $mc->getEntitiesList();
		foreach ($arrayofentities as $id => $name) {
			$conf->entity = $id;
			$conf->global = new stdClass();
			$conf->setValues($db);
			unActivateModule("modSuperSearch");
		}
	} else unActivateModule("modSuperSearch");
}
