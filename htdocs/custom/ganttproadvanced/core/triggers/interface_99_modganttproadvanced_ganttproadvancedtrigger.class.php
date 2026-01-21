<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \defgroup   ganttproadvanced     Module ganttproadvanced
 *  \brief      Example of a module descriptor.
 *              Such a file must be copied into htdocs/ganttproadvanced/core/modules directory.
 *  \file       htdocs/ganttproadvanced/core/modules/modganttproadvanced.class.php
 *  \ingroup    ganttproadvanced
 *  \brief      Description and activation file for module ganttproadvanced
 */

/**
 * Trigger class
 */
class Interfaceganttproadvancedtrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "others";
        $this->description = "Triggers of this module are empty functions.";
        $this->version  = 'development';
        $this->picto = 'glpitodolibarr@glpitodolibarr';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf){

        global $newtask;

        dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
        $ganttproadvanced = new ganttproadvanced($this->db);

        if($action == 'TASK_CREATE'){
            $newtask = $object->id;
        }
        if($action == 'PROJECT_TASK_ADD_CONTACT' && $object->id == $newtask){
            $TListeContacts = $object->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
            foreach ($TListeContacts as $key => $type) {
                $id_type_contact = $ganttproadvanced->t_typecontact;
                $res = $object->update_contact($type['rowid'], $type['status'], $id_type_contact);
                $newtask = '';
                break;
            }
        }
    }

}