<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024 Comercial ORTRAT <comercial@ortrat.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

dol_include_once('/holiday/class/holiday.class.php');



/**
 * \file    recursoshumanos/class/api_recursoshumanos.class.php
 * \ingroup recursoshumanos
 * \brief   File for API management of informacion_noticias.
 */

/**
 * API class for recursoshumanos informacion_noticias
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class HolidayApi extends DolibarrApi
{
	/**
	 * @var holiday $holiday {@type Holiday}
	 */
	public $holiday;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->holiday = new Holiday($this->db);
	}

	/**
	 * Get properties of a holiday object
	 *
	 * Return an array with holiday informations
	 *
	 * @param 	int 	$id ID of holiday
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET /{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id, 'holiday')) {
			throw new RestException(401, 'Access to instance id='.$this->holiday->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}


	/**
	 * List holiday
	 *
	 * Get a list of holiday
	 *
	 * @param string	       $sortfield	        Sort field
	 * @param string	       $sortorder	        Sort order
	 * @param int		       $limit		        Limit for list
	 * @param int		       $page		        Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 *
	 * @url	GET /
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
        
		$sql = "SELECT t.rowid";
		
		$sql .= " FROM `khns_holiday` as t";

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new Holiday($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, "$sql");
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No Noticias found');
		}
		return $obj_ret;
	}

	/**
	 * Create holiday object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of holiday
	 *
	 * @throws RestException
	 *
	 * @url	POST /
	 */
	public function post($request_data = null)
	{

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->holiday->$field = $this->_checkValForAPI($field, $value, $this->holiday);
		}


		if ($this->holiday->create(DolibarrApiAccess::$user)<0) {
			throw new RestException(500, "Error creating holiday", array_merge(array($this->holiday->error), $this->holiday->errors));
		}
		return $this->holiday->id;
	}

	/**
	 * Update holiday
	 *
	 * @param int   $id             Id of holiday to update
	 * @param array $request_data   Datas
	 * @return int
	 *
	 * @throws RestException
	 *
	 * @url	PUT holiday/{id}
	 */
	public function put($id, $request_data = null)
	{

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id, 'holiday')) {
			throw new RestException(401, 'Access to instance id='.$this->holiday->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->holiday->$field = $this->_checkValForAPI($field, $value, $this->holiday);
		}

		// Clean data
		// $this->holiday->abc = checkVal($this->holiday->abc, 'alphanohtml');

		if ($this->holiday->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->holiday->error);
		}
	}

	/**
	 * Delete holiday
	 *
	 * @param   int     $id   holiday ID
	 * @return  array
	 *
	 * @throws RestException
	 *
	 * @url	DELETE holiday/{id}
	 */
	public function delete($id)
	{

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'holiday not found');
		}

		if (!$this->holiday->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting holiday : '.$this->holiday->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'holiday deleted'
			)
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array		$data   Array of data to validate
	 * @return	array
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$holiday = array();
		foreach ($this->holiday->fields as $field => $propfield) {
			if (in_array($field, array('date_debut', 'date_fin')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$holiday[$field] = $data[$field];
		}
		return $holiday;
	}

    /**
     * Get holidays of a specific user
     *
     * Returns an array with holiday information for the specified user
     *
     * @param int $fk_user ID of the user
     * @return array Array of holiday objects
     *
     * @url GET /{fk_user}
     *
     * @throws RestException 401 Not allowed
     * @throws RestException 404 Not found
    */
    public function getHolidaysByUser($fk_user)
    {
        global $db;
    
        // Fetch holidays for the specified user
        $sql = "SELECT * FROM `khns_holiday` WHERE `fk_user` = $fk_user";
        $result = $this->db->query($sql);
    
        if (!$result) {
            throw new RestException(404, 'Holidays not found for user with ID ' . $fk_user.$sql);
        }
    
        $holidays = [];
        while ($row = $this->db->fetch_array($result)) {
			$holiday = [
				'rowid' => $row['rowid'],
				'fk_user' => $row['fk_user'],
				'date_debut' => $row['date_debut'],
				'date_fin' => $row['date_fin'],
				'description' => $row['description'],
				'halfday' => $row['halfday'],
				'statut' => $row['statut'],
				'fk_validator' => $row['fk_validator'],
				'date_valid' => $row['date_valid'],
				'fk_user_valid' => $row['fk_user_valid'],
				'date_refuse' => $row['date_refuse'],
				'fk_user_refuse' => $row['fk_user_refuse'],
				'date_cancel' => $row['date_cancel'],
				'fk_user_cancel' => $row['fk_user_cancel'],
				'detail_refuse' => $row['detail_refuse'],
				'note_private' => $row['note_private'],
				'note_public' => $row['note_public'],
				'fk_type' => $row['fk_type'],
				'tms' => $row['tms'],
				'entity' => $row['entity'],
				'ref' => $row['ref'],
				'ref_ext' => $row['ref_ext'],
				'import_key' => $row['import_key'],
				'extraparams' => $row['extraparams'],
				'fk_user_modif' => $row['fk_user_modif'],
			];
			
            $holidays[] = $holiday;
        }
    
        return $holidays;
    }
    
}
