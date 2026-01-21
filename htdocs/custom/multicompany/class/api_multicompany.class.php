<?php
/* Copyright (C) 2017-2025	Regis Houssin	<regis.houssin@inodbox.com>
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

use Luracast\Restler\RestException;

dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');

/**
 * API class for multicompany
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Multicompany extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'label'
    );

    /**
     * Constructor
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;
    }

    /**
     * Get properties of an entity
     *
     * Return an array with entity informations
     *
     * @param     int     $id ID of entity
     * @return    array|mixed data without useless information
     *
     * @throws    RestException
     */
    function get($id)
    {
        if (! DolibarrApiAccess::$user->rights->multicompany->read) {
            throw new RestException(401);
        }

        // The DaoMulticompany::fetch() method uses the global variable $user.
        global $user;
        $user = DolibarrApiAccess::$user;

        $multicompany = new DaoMulticompany($this->db);
        $result = $multicompany->fetch($id);
        if (empty($result)) {
            throw new RestException(404, 'entity not found');
        }

        return $this->_cleanObjectDatas($multicompany);
    }

    /**
     * List entities
     *
     * Get a list of entities
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%') and (t.visible:=:'1')"
     * @param string	$properties Restrict the data returned to theses properties. Ignored if empty. Comma separated list of properties names
     * @return array                Array of entities objects
     *
     * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '', $properties = '') {
        global $db, $conf;

        $obj_ret = array();

        if (! DolibarrApiAccess::$user->rights->multicompany->read) {
            throw new RestException(401);
        }

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."entity as t";
        $sql.= ' WHERE t.active = 1';

        // Add sql filters
        if (!empty($sqlfilters)) {
            if (! DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit > 0) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result) {
        	// The DaoMulticompany::fetch() method uses the global variable $user.
        	global $user;
        	$user = DolibarrApiAccess::$user;

            $i=0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min) {
            	$obj = $db->fetch_object($result);
            	$multicompany= new DaoMulticompany($this->db);
            	if ($multicompany->fetch($obj->rowid) > 0) {
            		$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($multicompany), $properties);
                }
                $i++;
            }
        } else {
            throw new RestException(503, 'Error when retrieve entities list : '.$db->lasterror());
        }
        if (!count($obj_ret)) {
            throw new RestException(404, 'No entities found');
        }

        return $obj_ret;
    }

    /**
     * Create entity object
     *
     * @param array $request_data   Request data
     * @return int  ID of entity
     */
    function post($request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->multicompany->write) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $multicompany = new DaoMulticompany($this->db);
        foreach($request_data as $field => $value) {
            $multicompany->$field = $value;
        }
        if ($multicompany->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating multicompany', array_merge(array($multicompany->error), $multicompany->errors));
        }
        return $multicompany->id;
    }

    /**
     * Update entity
     *
     * @param int   $id             ID of entity to update
     * @param array $request_data   Datas
     * @return int
     */
    function put($id, $request_data = null)
    {
        dol_include_once('core/lib/admin.lib.php');
        if (! DolibarrApiAccess::$user->rights->multicompany->write) {
            throw new RestException(401);
        }

        $multicompany = new DaoMulticompany($this->db);
        $result = $multicompany->fetch($id);
        if (empty($result)) {
            throw new RestException(404, 'entity not found');
        }

        $UPDATE_FIELDS = ['label', 'description', 'options'];
        $this->db->begin();

        $CONST_FIELDS = [
            'name' => 'MAIN_INFO_SOCIETE_NOM',
            'address' => 'MAIN_INFO_SOCIETE_ADDRESS',
            'town' => 'MAIN_INFO_SOCIETE_TOWN',
            'zip' => 'MAIN_INFO_SOCIETE_ZIP',
            'currency_code' => 'MAIN_MONNAIE',
            'lang' => 'MAIN_LANG_DEFAULT',
        ];

        foreach($request_data as $field => $value) {
            if (in_array($field, $UPDATE_FIELDS)) {
                $multicompany->$field = $value;
            } elseif (array_key_exists($field, $CONST_FIELDS)) {
                dolibarr_set_const($this->db, $CONST_FIELDS[$field], $value,'chaine',0,'',$id);
            }
        }

        // Special case for country id
        if (isset($request_data['country_id'])) {
            $country_id = $request_data['country_id'];
            $country = getCountry($country_id, 'all');
            $country_code = $country['code'];
            $country_label = $country['label'];
            dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$id);
        }

        // If there is no error, update() returns the number of affected rows
        // so if the update is a no op, the return value is zero.
        if ($multicompany->update(0, DolibarrApiAccess::$user) >= 0) {
            $this->db->commit();
            return $this->get($id);
        }
        $this->db->rollback();

        return false;
    }

    /**
     * Delete entity
     *
     * @param int $id   entity ID
     * @return array
     */
    /*function delete($id)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }
        $membertype = new AdherentType($this->db);
        $result = $membertype->fetch($id);
        if (empty($result)) {
            throw new RestException(404, 'member type not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('member',$membertype->id,'adherent_type')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (!$membertype->delete($membertype->id)) {
            throw new RestException(401,'error when deleting member type');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member type deleted'
            )
        );
    }*/

    /**
     * Validate fields before creating an object
     *
     * @param array|null    $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
	function _validate($data)
	{
		$multicompany = array();
		$FIELDS = ['label', 'active', 'visible'];

		foreach ($FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}

			$multicompany[$field] = $data[$field];
		}

		return $multicompany;
	}

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
	function _cleanObjectDatas($object)
	{
		$object = parent::_cleanObjectDatas($object);

		// Remove constants
		foreach($object as $key => $value) {
			if (preg_match('/^MAIN_/', $key)) {
				unset($object->$key);
			}
		}

		unset($object->language);
		unset($object->fk_tables);
		unset($object->import_key);
		unset($object->linkedObjectsIds);
		unset($object->context);
		unset($object->canvas);
		unset($object->fk_project);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->thirdparty);
		unset($object->user);
		unset($object->origin);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->fk_delivery_address);
		unset($object->shipping_method_id);
		unset($object->modelpdf);
		unset($object->fk_account);
		unset($object->note_public);
		unset($object->note_private);
		unset($object->fk_incoterms);
		unset($object->libelle_incoterms);
		unset($object->location_incoterms);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->ref);
		unset($object->statut);
		unset($object->note);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->shipping_method);
		unset($object->fk_multicurrency);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->last_main_doc);
		unset($object->lines);
		unset($object->actiontypecode);
		unset($object->specimen);
		unset($object->totalpaid);
		unset($object->product);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->warehouse_id);

		return $object;
	}
}
