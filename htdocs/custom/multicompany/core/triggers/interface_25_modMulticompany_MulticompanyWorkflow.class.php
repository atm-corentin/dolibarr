<?php
/* Copyright (C) 2010-2023	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       /multicompany/core/triggers/interface_25_modMulticompany_MulticompanyWorkflow.class.php
 *      \ingroup    multicompany
 *      \brief      Trigger file for create multicompany data
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *      \class      InterfaceMulticompanyWorkflow
 *      \brief      Classe des fonctions triggers des actions personnalisees du module multicompany
 */

class InterfaceMulticompanyWorkflow extends DolibarrTriggers
{
    public $family = 'multicompany';

    public $description = "Triggers of this module allows to create multicompany data";

    /**
     * Version of the trigger
     *
     * @var string
     */
    public $version = self::VERSION_DOLIBARR;

    /**
     *
     * @var string Image of the trigger
     */
    public $picto = 'fa-globe';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/core/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 * $object->id (id of entity)
	 * $object->element (element type of object)
	 *
	 * 	@param		string		$action		Event action code
	 * 	@param		Object		$object		Object
	 * 	@param		User		$user		Object user
	 * 	@param		Translate	$langs		Object langs
	 * 	@param		conf		$conf		Object conf
	 * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('multicompany')) {
			return 0; // Module not active, we do nothing
		}
		if (!getDolGlobalInt('MULTICOMPANY_SHARINGS_ENABLED') || !getDolGlobalInt('MULTICOMPANY_SHARING_BYELEMENT_ENABLED')) {
			return 0; // Global sharing or sharing by element not active, we do nothing
		}

		dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
			if ($action == 'COMPANY_CREATE' && !empty($user->admin) && empty($user->entity)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$entities = GETPOST('thirdparty_to', 'array', 2); // limit to POST
				if (isSharingAllByDefault('thirdparty')) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST('thirdparty_to', 'array', 2); // limit to POST
				}
				if (is_array($entities) && !empty($entities) && !empty($object->id)) {
					$objEntity = new DaoMulticompany($this->db);
					return $objEntity->setSharingsByElement('thirdparty', $object->id, $entities);
				}
			} elseif ($action == 'COMPANY_MODIFY' && !empty($user->admin) && empty($user->entity)) {
				// We can define the granularity of the sharing of the element only in the origin entity of this element
				if (!empty($object->entity) && $object->entity != $conf->entity) {
					return 0;
				}
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (isSharingAllByDefault('thirdparty')) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST('thirdparty_to', 'array', 2); // limit to POST
				}
				$oldsharedwith = GETPOST('oldsharedwith', 'aZ09', 2); // limit to POST
				if ($oldsharedwith != base64_encode(json_encode($entities))) {
					$objEntity = new DaoMulticompany($this->db);
					// remove sharings from contacts
					$contactlist = $object->contact_array();
					if (!empty($contactlist)) {
						if (isSharingAllByDefault('thirdparty')) {
							$removeentities = GETPOST('thirdparty_to', 'array', 2); // limit to POST
						} else {
							$removeentities = GETPOST('from', 'array', 2); // limit to POST
						}
						foreach($contactlist as $key => $name) {
							$currententities = $objEntity->getListOfSharingsByElement('contact', $key);
							if (!is_array($currententities[$key])) {
								$currententities[$key] = array();
							}
							// remove unshare third party entities from contacts
							$onlyselected = array_diff($currententities[$key], $removeentities);
							$oldsharedwitharray = json_decode(base64_decode($oldsharedwith));
							if (!is_array($oldsharedwitharray)) {
								$oldsharedwitharray = array();
							}
							// add only new sharings from contacts
							$onlynew = array_unique(array_merge(array_diff($entities, $oldsharedwitharray), $onlyselected));
							$ret = $objEntity->setSharingsByElement('contact', $key, $onlynew);
							if ($ret < 0) {
								break;
								return -2;
							}
						}
					}
					return $objEntity->setSharingsByElement('thirdparty', $object->id, $entities);
				}
			} elseif ($action == 'COMPANY_DELETE' && !empty($user->rights->societe->supprimer)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$objEntity = new DaoMulticompany($this->db);
				return $objEntity->setSharingsByElement('thirdparty', $object->id);

			} elseif ($action == 'CONTACT_CREATE' && !empty($user->rights->societe->contact->creer)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (!empty($object->id)) {
					if (empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)) {
						if (!empty($object->socid)) {
							$objEntity = new DaoMulticompany($this->db);
							// Set the same sharings of thirdparty
							$entities = $objEntity->getListOfSharingsByElement('thirdparty', $object->socid);
							if (is_array($entities[$object->socid]) && !empty($entities[$object->socid])) {
								return $objEntity->setSharingsByElement($object->element, $object->id, $entities[$object->socid]);
							}
						}
					} else {
						$checkentity = $conf->entity;
						if (!empty($object->socid)) {
							$staticthirdparty = new Societe($this->db);
							$ret = $staticthirdparty->fetch($object->socid);
							if ($ret > 0) {
								$checkentity = $staticthirdparty->entity;
							} else {
								$this->errors[] = $staticthirdparty->errors;
								return -1;
							}
						}
						if ($checkentity == $conf->entity) {
							if (!empty($user->admin) && empty($user->entity)) {
								if (isSharingAllByDefault($object->element)) {
									$entities = GETPOST('from', 'array', 2); // limit to POST
								} else {
									$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
								}
								if (is_array($entities) && !empty($entities)) {
									$objEntity = new DaoMulticompany($this->db);
									return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
								}
							} elseif (!empty($object->socid)) {
								$objEntity = new DaoMulticompany($this->db);
								// Set the same sharings of thirdparty
								$entities = $objEntity->getListOfSharingsByElement('thirdparty', $object->socid);
								if (is_array($entities[$object->socid]) && !empty($entities[$object->socid])) {
									return $objEntity->setSharingsByElement($object->element, $object->id, $entities[$object->socid]);
								}
							}
						} elseif (!empty($staticthirdparty->id)) {
							// Share only origin entity of creation if different of third party entity
							$objEntity = new DaoMulticompany($this->db);
							$entities = array($staticthirdparty->id => array($conf->entity));
							return $objEntity->setSharingsByElement($object->element, $object->id, $entities[$staticthirdparty->id]);
						}
					}
				}
			} elseif ($action == 'CONTACT_MODIFY' && !empty($user->admin) && empty($user->entity)) {
				// We can define the granularity of the sharing of the element only in the origin entity of this element
				if (!empty($object->entity) && $object->entity != $conf->entity) {
					return 0;
				}
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)) {
					// TODO it's change if sharings of thirdparty change
					if (!empty($object->socid)) {
						$objEntity = new DaoMulticompany($this->db);
						// Set the same sharings of thirdparty
						$entities = $objEntity->getListOfSharingsByElement('thirdparty', $object->socid);
						if (is_array($entities[$object->socid]) && !empty($entities[$object->socid])) {
							$oldsharedwith = GETPOST('oldsharedwith', 'aZ09', 2); // limit to POST
							if ($oldsharedwith != base64_encode(json_encode($entities[$object->socid]))) {
								return $objEntity->setSharingsByElement($object->element, $object->id, $entities[$object->socid]);
							}
						} else {
							// Delete shares only if old values exists
							$oldsharedwith = json_decode(base64_decode($oldsharedwith), true);
							if (is_array($oldsharedwith) && !empty($oldsharedwith)) {
								return $objEntity->setSharingsByElement($object->element, $object->id);
							}
						}
					}
				} else {
					if (isSharingAllByDefault($object->element)) {
						$entities = GETPOST('from', 'array', 2); // limit to POST
					} else {
						$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
					}
					$oldsharedwith = GETPOST('oldsharedwith', 'aZ09', 2); // limit to POST
					if ($oldsharedwith != base64_encode(json_encode($entities))) {
						$objEntity = new DaoMulticompany($this->db);
						return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
					}
				}
			} elseif ($action == 'CONTACT_DELETE' && !empty($user->rights->societe->contact->supprimer)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$objEntity = new DaoMulticompany($this->db);
				return $objEntity->setSharingsByElement($object->element, $object->id);
			}
		}

		if (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)) {
			if ($action == 'PRODUCT_CREATE' && !empty($user->admin) && empty($user->entity)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (isSharingAllByDefault($object->element)) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
				}
				if (is_array($entities) && !empty($entities) && !empty($object->id)) {
					$objEntity = new DaoMulticompany($this->db);
					return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
				}
			} elseif ($action == 'PRODUCT_MODIFY' && !empty($user->admin) && empty($user->entity)) {
				// We can define the granularity of the sharing of the element only in the origin entity of this element
				if (!empty($object->entity) && $object->entity != $conf->entity) {
					return 0;
				}
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (isSharingAllByDefault($object->element)) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
				}
				$oldsharedwith = GETPOST('oldsharedwith', 'aZ09', 2); // limit to POST
				if ($oldsharedwith != base64_encode(json_encode($entities))) {
					$objEntity = new DaoMulticompany($this->db);
					return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
				}
			} elseif ($action == 'PRODUCT_DELETE' && !empty($user->rights->produit->supprimer)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$objEntity = new DaoMulticompany($this->db);
				return $objEntity->setSharingsByElement($object->element, $object->id);
			}
		}

		if (!empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_ENABLED) && !empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_BYELEMENT_ENABLED)) {
			if ($action == 'CATEGORY_CREATE' && !empty($user->admin) && empty($user->entity)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (isSharingAllByDefault($object->element)) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
				}
				if (is_array($entities) && !empty($entities) && !empty($object->id)) {
					$objEntity = new DaoMulticompany($this->db);
					return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
				}
			} elseif ($action == 'CATEGORY_MODIFY' && !empty($user->admin) && empty($user->entity)) {
				// We can define the granularity of the sharing of the element only in the origin entity of this element
				if (!empty($object->entity) && $object->entity != $conf->entity) {
					return 0;
				}
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (isSharingAllByDefault($object->element)) {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST($object->element.'_to', 'array', 2); // limit to POST
				}
				$oldsharedwith = GETPOST('oldsharedwith', 'aZ09', 2); // limit to POST
				if ($oldsharedwith != base64_encode(json_encode($entities))) {
					$objEntity = new DaoMulticompany($this->db);
					return $objEntity->setSharingsByElement($object->element, $object->id, $entities);
				}
			} elseif ($action == 'CATEGORY_DELETE' && !empty($user->rights->produit->supprimer)) {
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$objEntity = new DaoMulticompany($this->db);
				return $objEntity->setSharingsByElement($object->element, $object->id);
			}
		}

		return 0;
	}

}
