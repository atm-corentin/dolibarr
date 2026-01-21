<?php
/* Copyright (C) 2014-2024	Regis Houssin	<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       multicompany/core/login/functions_mc.php
 *      \ingroup    multicompany
 *      \brief      Authentication functions for Multicompany mode when combobox in login page is disabled
 */


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_mc($usertotest, $passwordtotest, $entitytotest = 1)
{
	global $db, $conf, $langs;
	global $mc;

	dol_syslog("functions_mc::check_user_password_mc usertotest=".$usertotest);

	$login = '';

	if (!empty($conf->multicompany->enabled)) {
		$langs->loadLangs(array('main','errors','multicompany@multicompany'));

		// For the Multicompany option to open new session in new tab after switching entity
		$securitycode = GETPOST('securitycode', 'alphanohtml', 1);
		$opennewsession = (getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL') && getDolGlobalInt('MULTICOMPANY_OPEN_NEW_SESSION_AFTER_SWITCHING'));

		if (!empty($usertotest) || (!empty($opennewsession) && !empty($securitycode))) {
			// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko

			$cryptkey = (!empty($conf->file->instance_unique_id) ? $conf->file->instance_unique_id : $conf->file->cookie_cryptkey);

			$sql = "SELECT rowid, login, entity, pass, pass_crypted";
			$sql.= " FROM ".MAIN_DB_PREFIX."user";
			if (!empty($opennewsession) && !empty($securitycode)) {
				$sql.= " WHERE pass_temp = '".dolDecrypt(base64_decode($securitycode), $cryptkey)."'";
			} else {
				$sql.= " WHERE login = '".$db->escape($usertotest)."'";
			}
			$sql.= " AND statut = 1";

			dol_syslog("functions_mc::check_user_password_mc sql=" . $sql);
			$resql = $db->query($sql);
			if (!empty($resql)) {
				$obj = $db->fetch_object($resql);
				if (!empty($obj)) {
					// For the Multicompany option to open new session in new tab after switching entity
					if (!empty($opennewsession) && !empty($securitycode)) {
						require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
						$objectstatic = new GenericObject($db);
						$objectstatic->setValueFrom('pass_temp', '', 'user', $obj->rowid);
						$passok = true;
					} else {
						$passclear = $obj->pass;
						$passcrypted = $obj->pass_crypted;
						$passtyped = $passwordtotest;

						$passok = false;

						// Check crypted password
						$cryptType = getDolGlobalString('DATABASE_PWD_ENCRYPTED', '');

						// By default, we use default setup for encryption rule
						if (!in_array($cryptType, array('auto'))) {
							$cryptType = 'auto';
						}
						// Check encrypted password according to encryption algorithm
						if ($cryptType == 'auto') {
							if (!empty($passcrypted) && dol_verifyHash($passtyped, $passcrypted, '0')) {
								$passok = true;
								dol_syslog("functions_mc::check_user_password_mc Authentification ok - hash " . $cryptType . " of pass is ok");
							}
						}
						// For compatibility with old versions
						if (empty($passok))	{
							if ((empty($passcrypted) || !empty($passtyped)) && (!empty($passclear) && ($passtyped == $passclear))) {
								$passok = true;
								dol_syslog("functions_mc::check_user_password_mc Authentification ok - found old pass in database", LOG_WARNING);
							}
						}
					}

					if (!empty($passok) && !empty($obj->entity)) {
						global $entitytotest;

						$entitytotest = $obj->entity;

						if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
							$sql = "SELECT uu.entity";
							$sql.= " FROM " . MAIN_DB_PREFIX . "usergroup_user as uu";
							$sql.= ", " . MAIN_DB_PREFIX . "entity as e";
							$sql.= " WHERE uu.entity = e.rowid AND e.visible < 2"; // Remove template of entity
							$sql.= " AND uu.fk_user = " . $obj->rowid;

							dol_syslog("functions_mc::check_user_password_mc sql=" . $sql, LOG_DEBUG);
							$result = $db->query($sql);
							if (!empty($result)) {
								while($array = $db->fetch_array($result)) { // user allowed if at least in one group
									$entitytotest = $array['entity'];
									break; // stop in first entity
								}
							}
						}

						$ret = $mc->switchEntity($entitytotest, $obj->rowid);

						if ($ret < 0) {
							$passok = false;
						}
					}

					// Password ok ?
					if (!empty($passok)) {
						$login = $obj->login;
					} else {
						dol_syslog("functions_mc::check_user_password_mc Authentification ko bad password pour '".$usertotest."'", LOG_ERR);
						$_SESSION["dol_loginmesg"] = $langs->trans("ErrorBadLoginPassword");
					}
				} else {
					dol_syslog("functions_mc::check_user_password_mc Authentification ko user not found for '".$usertotest."'", LOG_ERR);
					$_SESSION["dol_loginmesg"] = $langs->trans("ErrorBadLoginPassword");
				}
			} else {
				dol_syslog("functions_mc::check_user_password_mc Authentification ko db error for '".$usertotest."' error=".$db->lasterror(), LOG_ERR);
				$_SESSION["dol_loginmesg"] = $db->lasterror();
			}
		} else {
			dol_syslog("functions_mc::check_user_password_mc Authentification ko, the drop-down list of entities on the login page must be hidden", LOG_ERR);
			$_SESSION["dol_loginmesg"]=$langs->trans("ErrorDropDownListOfEntitiesMustBeHidden");
		}
	}

	return $login;
}
