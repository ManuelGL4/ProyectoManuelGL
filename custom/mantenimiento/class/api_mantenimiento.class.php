<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024 Comercial ORTRAT <prueba@deltanet.es>
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
include_once("curl.php");

dol_include_once('/mantenimiento/class/contratos.class.php');
dol_include_once('/mantenimiento/class/informes.class.php');
dol_include_once('/mantenimiento/class/informes_equipos.class.php');
dol_include_once('./product/class/product.class.php');
dol_include_once('/mantenimiento/class/informes_equipos.class.php');
dol_include_once('/mantenimiento/class/informes_sustituciones.class.php');
dol_include_once('/mantenimiento/class/contratos_equipos.class.php');

/**
 * \file    mantenimiento/class/api_mantenimiento.class.php
 * \ingroup mantenimiento
 * \brief   File for API management of contratos.
 */

/**
 * API class for mantenimiento contratos
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class MantenimientoApi extends DolibarrApi
{
	/**
	 * @var Contratos $contratos {@type Contratos}
	 */
	public $contratos;
	public $informe;
	public $contratos_equipos;
	public $informessustituciones;
	public $informes_equipos;
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
        $this->informe = new Informes($this->db);

		$this->contratos = new Contratos($this->db);
		$this->contratos_equipos = new Contratos_equipos($this->db);
		$this->informessustituciones=new Informes_sustituciones($this->db);
		$this->informes_equipos=new Informes_equipos($this->db);
		
	}

	/**
	 * Get properties of a contratos object
	 *
	 * Return an array with contratos informations
	 *
	 * @param 	int 	$id ID of contratos
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET contratoss/{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
			throw new RestException(401);
		}

		$result = $this->contratos->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contratos not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contratos', $this->contratos->id, 'mantenimiento_contratos')) {
			throw new RestException(401, 'Access to instance id='.$this->contratos->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->contratos);
	}


	/**
	 * List contratoss
	 *
	 * Get a list of contratoss
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
	 * @url	GET /contratos/
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new Contratos($this->db);

		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
			throw new RestException(401);
		}

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}
		$sql .= " WHERE 1 = 1";

		// Example of use $mode
		//if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
		//if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

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
				$tmp_object = new Contratos($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving contratos list: '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No contratos found');
		}
		return $obj_ret;
	}

	/**
	 * Create contratos object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of contratos
	 *
	 * @throws RestException
	 *
	 * @url	POST contratos/
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->write) {
			throw new RestException(401);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->contratos->$field = $this->_checkValForAPI($field, $value, $this->contratos);
		}

		// Clean data
		// $this->contratos->abc = checkVal($this->contratos->abc, 'alphanohtml');

		if ($this->contratos->create(DolibarrApiAccess::$user)<0) {
			throw new RestException(500, "Error creating Contratos", array_merge(array($this->contratos->error), $this->contratos->errors));
		}
		return $this->contratos->id;
	}

	/**
	 * Update contratos
	 *
	 * @param int   $id             Id of contratos to update
	 * @param array $request_data   Datas
	 * @return int
	 *
	 * @throws RestException
	 *
	 * @url	PUT contratos/{id}
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->write) {
			throw new RestException(401);
		}

		$result = $this->contratos->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contratos not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contratos', $this->contratos->id, 'mantenimiento_contratos')) {
			throw new RestException(401, 'Access to instance id='.$this->contratos->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->contratos->$field = $this->_checkValForAPI($field, $value, $this->contratos);
		}

		// Clean data
		// $this->contratos->abc = checkVal($this->contratos->abc, 'alphanohtml');

		if ($this->contratos->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->contratos->error);
		}
	}

	/**
	 * Delete contratos
	 *
	 * @param   int     $id   Contratos ID
	 * @return  array
	 *
	 * @throws RestException
	 *
	 * @url	DELETE contratos/{id}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->delete) {
			throw new RestException(401);
		}
		$result = $this->contratos->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contratos not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contratos', $this->contratos->id, 'mantenimiento_contratos')) {
			throw new RestException(401, 'Access to instance id='.$this->contratos->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->contratos->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting Contratos : '.$this->contratos->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contratos deleted'
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

		/*unset($object->name);
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
		*/

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
		$contratos = array();
		foreach ($this->contratos->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$contratos[$field] = $data[$field];
		}
		return $contratos;
	}
	/**
	 * List informes by contract_id
	 *
	 * Get a list of informes associated with a specific contract
	 *
	 * @param int $contract_id ID of the contract
	 * @param string $sortfield Sort field
	 * @param string $sortorder Sort order
	 * @param int $limit Limit for list
	 * @param int $page Page number
	 * @param string $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return array Array of informe objects
	 *
	 * @throws RestException
	 *
	 * @url GET informes/contratos/{contract_id}
	 */
	public function getInformesByContract($contract_id, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new Informes($this->db);

		if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
			throw new RestException(401);
		}

		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";
		$sql .= " WHERE t.contract_id = ".((int) $contract_id);

		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

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
				$tmp_object = new Informes($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving informes list: '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No informes found');
		}
		return $obj_ret;
	}
	/**
 * Get product name by product ID
 *
 * @param int $product_id ID of the product
 * @return string Name of the product
 *
 * @throws RestException
 */
public function getProductNameByProductID($product_id)
{
    global $db;

    $product_name = '';
    $product = new Product($this->db);

    if ($product->fetch($product_id)) {
        $product_name = $product->label;
    }

    return $product_name;
}

/**
 * Get informes_equipos by informe ID
 *
 * Get a list of informes_equipos associated with a specific informe ID
 *
 * @param int $informe_id ID of the informe
 * @return array Array of informes_equipos objects
 *
 * @throws RestException
 */
public function getInformesEquiposByInforme($informe_id)
{
    global $db, $conf;

    $obj_ret = array();
    $tmpobject = new Informes_equipos($this->db);

    // Check if user has the necessary permissions
    if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
        throw new RestException(401);
    }

    $sql = "SELECT t.* FROM " . MAIN_DB_PREFIX . $tmpobject->table_element . " as t";
    $sql .= " WHERE t.fk_report = " . ((int)$informe_id);

    // Execute the query
    $result = $this->db->query($sql);

    // Fetch results
    if ($result) {
		
        while ($obj = $this->db->fetch_object($result)) {
            // Get product name separately
            $product_name = $this->getProductNameByProductID($obj->fk_product);

            // Prepare informe_equipo object
            $tmp_object = new Informes_equipos($this->db);
            $tmp_object->fetch($obj->rowid);

            // Add product_name as a property of the informe_equipo object
            $tmp_object->product_name = $product_name;

            // Add the object to the result array
            $obj_ret[] = $this->_cleanObjectDatas($tmp_object);
        }
    } else {
        throw new RestException(503, 'Error when retrieving informes_equipos list: ' . $this->db->lasterror());
    }

    // Check if any informes_equipos found
    if (!count($obj_ret)) {
        throw new RestException(404, 'No informes_equipos found');
    }

    return $obj_ret;
}


/**
 * Get all products
 *
 * Get a list of all products in the system
 *
 * @return array Array of product objects
 *
 * @throws RestException
 */
public function getAllProducts()
{
    global $db, $conf;

    $obj_ret = array();

    if (!DolibarrApiAccess::$user->rights->produit->lire) { 
        throw new RestException(401);
    }

    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "product";

    $result = $this->db->query($sql);

    // Fetch results
    if ($result) {
        while ($obj = $this->db->fetch_object($result)) {
            $obj_ret[] = $obj;
        }
    } else {
        throw new RestException(503, 'Error when retrieving products list: ' . $this->db->lasterror());
    }

    if (!count($obj_ret)) {
        throw new RestException(404, 'No products found');
    }

    return $obj_ret;
}
/**
 * Create contrato_equipo object
 *
 * @param array $request_data   Request data
 * @return int  ROWID of contrato_equipo
 *
 * @throws RestException
 *
 * @url    POST contratos_equipos/
 */
public function postContratoEquipo($request_data = null)
{


    foreach ($request_data as $field => $value) {
        $this->contratos_equipos->$field = $this->_checkValForAPI($field, $value, $this->contratos_equipos);
    }

    if ($this->contratos_equipos->create(DolibarrApiAccess::$user) < 0) {
        $field_values = [];
        foreach ($request_data as $field => $value) {
            $field_values[$field] = $value;
        }

        throw new RestException(500, "Error creating ContratoEquipo", [
            "error" => $this->contratos_equipos->error,
            "errors" => $this->contratos_equipos->errors,
            "field_values" => $field_values
        ]);
    }
    
    return $this->contratos_equipos->id;
}

/**
 * Create informes object
 *
 * @param array $request_data   Request data
 * @return int  ID of informe
 *
 * @throws RestException
 *
 * @url    POST informes/
 */
public function postInforme($request_data = null)
{
    if (!DolibarrApiAccess::$user->rights->mantenimiento->informes->write) {
        throw new RestException(401);
    }


    foreach ($request_data as $field => $value) {
        $this->informe->$field = $this->_checkValForAPI($field, $value, $this->informe);
    }

    if ($this->informe->create(DolibarrApiAccess::$user) < 0) {
        $field_values = [];
        foreach ($request_data as $field => $value) {
            $field_values[$field] = $value;
        }

        throw new RestException(500, "Error creating Informe", [
            "error" => $this->informe->error,
            "errors" => $this->informe->errors,
            "field_values" => $field_values
        ]);
    }
    
    return $this->informe->id;
}
/**
 * Create informes_sustituciones object
 *
 * @param array $request_data   Request data
 * @return int  ID of informessustituciones
 *
 * @throws RestException
 *
 * @url    POST informessustituciones/
 */
public function postInformeSustituciones($request_data = null)
{
    foreach ($request_data as $field => $value) {
        $this->informessustituciones->$field = $this->_checkValForAPI($field, $value, $this->informessustituciones);
    }

    if ($this->informessustituciones->create(DolibarrApiAccess::$user) < 0) {
        $field_values = [];
        foreach ($request_data as $field => $value) {
            $field_values[$field] = $value;
        }

        throw new RestException(500, "Error creating informessustituciones", [
            "error" => $this->informessustituciones->error,
            "errors" => $this->informessustituciones->errors,
            "field_values" => $field_values
        ]);
    }
    
    return $this->informessustituciones->id;
}

/**
 * Create informes_equipos object
 *
 * @param array $request_data   Request data
 * @return int  ID of informes_equipos
 *
 * @throws RestException
 *
 * @url    POST informes_equipos/
 */
public function postInformesEquipos($request_data = null)
{
    foreach ($request_data as $field => $value) {
        $this->informes_equipos->$field = $this->_checkValForAPI($field, $value, $this->informes_equipos);
    }

    if ($this->informes_equipos->create(DolibarrApiAccess::$user) < 0) {
        $field_values = [];
        foreach ($request_data as $field => $value) {
            $field_values[$field] = $value;
        }

        throw new RestException(500, "Error creating informes_equipos", [
            "error" => $this->informes_equipos->error,
            "errors" => $this->informes_equipos->errors,
            "field_values" => $field_values
        ]);
    }
    
    return $this->informes_equipos->id;
}

/**
 * Get informes_sustituciones by informe ID
 *
 * Get a list of informes_sustituciones associated with a specific informe ID
 *
 * @param int $informe_id ID of the informe
 * @return array Array of informes_sustituciones objects
 *
 * @throws RestException
 */
public function getInformesSustitucionesByInforme($informe_id)
{
    global $db, $conf;

    $obj_ret = array();
    $tmpobject = new Informes_sustituciones($this->db);

    // Check if user has the necessary permissions
    if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
        throw new RestException(401);
    }

    $sql = "SELECT t.* FROM " . MAIN_DB_PREFIX . $tmpobject->table_element . " as t";
    $sql .= " WHERE t.fk_report = " . ((int)$informe_id);

    // Execute the query
    $result = $this->db->query($sql);

    // Fetch results
    if ($result) {
		
        while ($obj = $this->db->fetch_object($result)) {
            // Get product name separately
            $product_name = $this->getProductNameByProductID($obj->fk_product);

            // Prepare informe_equipo object
            $tmp_object = new Informes_sustituciones($this->db);
            $tmp_object->fetch($obj->rowid);

            // Add product_name as a property of the informe_equipo object
            $tmp_object->product_name = $product_name;

            // Add the object to the result array
            $obj_ret[] = $this->_cleanObjectDatas($tmp_object);
        }
    } else {
        throw new RestException(503, 'Error when retrieving informes_equipos list: ' . $this->db->lasterror());
    }

    // Check if any informes_equipos found
    if (!count($obj_ret)) {
        throw new RestException(404, 'No informes_equipos found');
    }

    return $obj_ret;
}	

	
	/**
 * Get informes_sustituciones by informe ID
 *
 * Get a list of informes_sustituciones futuras associated with a specific informe ID
 *
 * @param int $informe_id ID of the informe
 * @return array Array of informes_sustituciones objects
 *
 * @throws RestException
 */
public function getInformesSustitucionesByInformeFutura($informe_id)
{
    global $db, $conf;

    $obj_ret = array();
    $tmpobject = new Informes_sustituciones($this->db);

    // Check if user has the necessary permissions
    if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
        throw new RestException(401);
    }

    $sql = "SELECT t.* FROM " . MAIN_DB_PREFIX . $tmpobject->table_element . " as t";
    $sql .= " WHERE t.fk_report = " . ((int)$informe_id)." AND t.is_future=1";

    // Execute the query
    $result = $this->db->query($sql);

    // Fetch results
    if ($result) {
		
        while ($obj = $this->db->fetch_object($result)) {
            // Get product name separately
            $product_name = $this->getProductNameByProductID($obj->fk_product);

            // Prepare informe_equipo object
            $tmp_object = new Informes_sustituciones($this->db);
            $tmp_object->fetch($obj->rowid);

            // Add product_name as a property of the informe_equipo object
            $tmp_object->product_name = $product_name;

            // Add the object to the result array
            $obj_ret[] = $this->_cleanObjectDatas($tmp_object);
        }
    } else {
        throw new RestException(503, 'Error when retrieving informes_equipos list: ' . $this->db->lasterror());
    }

    // Check if any informes_equipos found
    if (!count($obj_ret)) {
        throw new RestException(404, 'No informes_equipos found');
    }

    return $obj_ret;
}	

	
	
	
/**
 * postInformePRUEBA
 *
 * @param string $ref
 * @param string $description
 * @param int $technician_id
 * @param int $storage_id
 * @param string $maintenance_date
 * @param string $real_date
 * @param int $contract_id
 * @param string $observations
 * @param string $date_creation
 * @param string $tms
 * @param int $fk_user_creat
 * @param int $status
 * @param int|null $last_technician_id
 * @param int|null $hours_spent
 * @param string|null $start_date
 * @param string|null $end_date
 * @param int|null $futures_inherited
 * @param int|null $id_fase
 * @param int|null $id_khonos
 * @param string|null $note_public
 * @param string|null $note_private
 * @param int|null $fk_user_modif
 * @param string|null $last_main_doc
 * @param string|null $import_key
 * @param string|null $model_pdf
 * @return array                Datos de la respuesta JSON
 * @throws RestException        Excepción en caso de error
 *
 * @url    POST informes/:ref/:description/:technician_id/:storage_id/:maintenance_date/:real_date/:contract_id/:observations/:date_creation/:tms/:fk_user_creat/:status/:last_technician_id/:hours_spent/:start_date/:end_date/:futures_inherited/:id_fase/:id_khonos/:note_public/:note_private/:fk_user_modif/:last_main_doc/:import_key/:model_pdf
 */
public function postInformePRUEBA(
    $ref, $description, $technician_id, $storage_id, $maintenance_date, $real_date, 
    $contract_id, $observations, $date_creation, $tms, $fk_user_creat, $status=1,
    $last_technician_id = null, $hours_spent = null, $start_date = null, $end_date = null,
    $futures_inherited = null, $id_fase = null, $id_khonos = null, $note_public = null,
    $note_private = null, $fk_user_modif = null, $last_main_doc = null, $import_key = null,
    $model_pdf = null
) {
    $url = "https://erp.ortrat.es/api/index.php/mantenimientoapi/$ref/$description/$technician_id/$storage_id/$maintenance_date/$real_date/$contract_id/$observations/$date_creation/$tms/$fk_user_creat/$status/$last_technician_id/$hours_spent/$start_date/$end_date/$futures_inherited/$id_fase/$id_khonos/$note_public/$note_private/$fk_user_modif/$last_main_doc/$import_key/$model_pdf";
    
    $campos = [
        "ref" => $ref,
        "description" => $description,
        "technician_id" => $technician_id,
        "last_technician_id" => $last_technician_id,
        "storage_id" => $storage_id,
        "maintenance_date" => $maintenance_date,
        "real_date" => $real_date,
        "contract_id" => $contract_id,
        "observations" => $observations,
        "hours_spent" => $hours_spent,
        "start_date" => $start_date,
        "end_date" => $end_date,
        "futures_inherited" => $futures_inherited,
        "id_fase" => $id_fase,
        "id_khonos" => $id_khonos,
        "note_public" => $note_public,
        "note_private" => $note_private,
        "date_creation" => $date_creation,
        "tms" => $tms,
        "fk_user_creat" => $fk_user_creat,
        "fk_user_modif" => $fk_user_modif,
        "last_main_doc" => $last_main_doc,
        "import_key" => $import_key,
        "model_pdf" => $model_pdf,
        "status" => $status
    ];
    
    $data = json_encode($campos);
    
    $ch = curl_init("https://erp.ortrat.es/api/index.php/mantenimientoapi/informes");
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'HTTP_DOLAPIKEY: c4c4c8582b90f4058abb1cb75d90442c41374219' 
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RestException(500, "Error: $error");
    }
    
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if ($responseData === null || !is_array($responseData)) {
        throw new RestException(500, "Error: Respuesta no válida del servidor");
    }
    
    return $responseData;
}
	
	/**
 * Create informes object PRUEBA
 *
 * @param array|null $request_data Datos para enviar en la solicitud POST
 * @return string Respuesta de la solicitud cURL
 */
function postInforme2($request_data = null) {
    $ch = curl_init();
    $url = 'https://erp.ortrat.es/api/index.php/mantenimientoapi/informes';

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);

    if ($request_data !== null && is_array($request_data)) {
        $urlEncodedData = http_build_query($request_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $urlEncodedData);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'HTTP_DOLAPIKEY: c4c4c8582b90f4058abb1cb75d90442c41374219',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error en la solicitud cURL: ' . curl_error($ch);
    }

    curl_close($ch);

    return $response;
}
/**
 * List contratos by date range and maintenance date
 *
 * Get a list of contratos based on a date range and maintenance date
 *
 * @param string    $date_start     Start date of the date range in 'YYYY-MM-DD' format
 * @param string    $date_end       End date of the date range in 'YYYY-MM-DD' format
 * @param string    $sortfield      Sort field
 * @param string    $sortorder      Sort order
 * @param int       $limit          Limit for list
 * @param int       $page           Page number
 * @param string    $sqlfilters     Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
 * @return array                    Array of contrato objects
 *
 * @throws RestException
 *
 * @url GET /contratos/listbydaterange/
 */
public function listContractsByDateRangeAndMaintenanceDate($date_start, $date_end, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '')
{
    global $db, $conf;

    $obj_ret = array();
    $tmpobject = new Contratos($this->db);

    if (!DolibarrApiAccess::$user->rights->mantenimiento->contratos->read) {
        throw new RestException(401);
    }

    $socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

    $restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

    // If the internal user must only see his customers, force searching by him
    $search_sale = 0;
    if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
        $search_sale = DolibarrApiAccess::$user->id;
    }

    $sql = "SELECT t.*";
    if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
    }
    $sql .= " FROM " . MAIN_DB_PREFIX . $tmpobject->table_element . " AS t";

    if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux AS sc"; // We need this table joined to the select in order to filter by sale
    }
    $sql .= " WHERE 1 = 1";

    if ($tmpobject->ismultientitymanaged) {
        $sql .= ' AND t.entity IN (' . getEntity($tmpobject->element) . ')';
    }
    if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        $sql .= " AND t.fk_soc = sc.fk_soc";
    }
    if ($restrictonsocid && $socid) {
        $sql .= " AND t.fk_soc = " . ((int) $socid);
    }
    if ($restrictonsocid && $search_sale > 0) {
        $sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
    }
    // Insert sale filter
    if ($restrictonsocid && $search_sale > 0) {
        $sql .= " AND sc.fk_user = " . ((int) $search_sale);
    }

    // Additional filters based on date range
    $sql .= " AND (";
    $sql .= " (t.date_start <= '" . $date_end . "' AND t.date_end >= '" . $date_start . "')";
    $sql .= " )";

    // Join with khns_mantenimiento_informes table and filter by maintenance_date
    $sql .= " AND t.rowid IN (
        SELECT c.rowid
        FROM " . MAIN_DB_PREFIX . "mantenimiento_contratos AS c
        JOIN " . MAIN_DB_PREFIX . "mantenimiento_informes AS i ON c.rowid = i.contract_id
        WHERE i.maintenance_date BETWEEN '" . $date_start . "' AND '" . $date_end . "'
    )";

    if ($sqlfilters) {
        if (!DolibarrApi::_checkFilters($sqlfilters)) {
            throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
        }
        $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
        $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
    }

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
            $tmp_object = new Contratos($this->db);
            if ($tmp_object->fetch($obj->rowid)) {
                $obj_ret[] = $this->_cleanObjectDatas($tmp_object);
            }
            $i++;
        }
    } else {
        throw new RestException(503, 'Error when retrieving contratos list: ' . $this->db->lasterror());
    }
    if (!count($obj_ret)) {
        throw new RestException(404, 'No contratos found');
    }
    return $obj_ret;
}	
	
	/**
 * Create informes, informessustituciones, informesequipos y firma objects
 *
 * @throws RestException
 *
 * @url    POST postNuevo/
 */
public function postInformeEnUno() {
    // Obtener los datos del cuerpo de la solicitud
    $postData = file_get_contents('php://input');

    // Decodificar los datos JSON del cuerpo de la solicitud
    $requestData = json_decode($postData, true);

    // Verificar si se pudo decodificar el JSON correctamente
    if ($requestData === null && json_last_error() !== JSON_ERROR_NONE) {
		throw new RestException(500, "Error al intentar procesar el json");
    }

    // Variables a devolver para saber si se ha creado o no
    $informeCreado = false;
    $sustitucionesCreadas = false;
    $equiposCreados = false;
    $firmaCreada = false;

    // Extraer datos del informe
    if (isset($requestData['informe'])) {
        $informeData = $requestData['informe'];
        $dataInforme = json_encode($informeData);

        // URL del endpoint para el informe
        $url_informe = 'https://erp.ortrat.es/api/index.php/mantenimientoapi/informes?DOLAPIKEY=c4c4c8582b90f4058abb1cb75d90442c41374219';

        // Inicializar sesión curl para el primer informe
        $ch_informe = curl_init($url_informe);

        // Configurar opciones de curl para hacer una solicitud POST para el primer informe
        curl_setopt($ch_informe, CURLOPT_POST, 1);
        curl_setopt($ch_informe, CURLOPT_POSTFIELDS, $dataInforme);
        curl_setopt($ch_informe, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataInforme),
        ));
        curl_setopt($ch_informe, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud para el primer informe
        $response_informe = curl_exec($ch_informe);

        // Verificar si ocurrió algún error
        if (!curl_errno($ch_informe)) {
            $fk_report = trim($response_informe); // El ID es el contenido de la respuesta del primer curl
            $informeCreado = true;
        } else {
            throw new RestException('Error en la solicitud del informe: ' . curl_error($ch_informe), 500);
        }

        // Cerrar sesion curl para informe
        curl_close($ch_informe);
    } else {
        throw new RestException('No se encontraron datos válidos para informe', 400);
    }

    // Verificar si 'informessustituciones' esta en el json enviado
    if (isset($requestData['informessustituciones']) && is_array($requestData['informessustituciones'])) {
        // Recorrer cada objeto 'informessustituciones'
        foreach ($requestData['informessustituciones'] as $sustitucionData) {
            // Agregar fk_report al objeto 'informessustituciones'
            $sustitucionData['fk_report'] = $fk_report;

            // Convertir cada objeto a JSON
            $dataSustitucion = json_encode($sustitucionData);

            // URL del endpoint para informes_sustituciones
            $url_sustituciones = 'https://erp.ortrat.es/api/index.php/mantenimientoapi/informessustituciones?DOLAPIKEY=c4c4c8582b90f4058abb1cb75d90442c41374219';

            // Inicializar sesión curl para informes_sustituciones
            $ch_sustituciones = curl_init($url_sustituciones);

            // Configurar opciones de curl para hacer una solicitud POST para informes_sustituciones
            curl_setopt($ch_sustituciones, CURLOPT_POST, 1);
            curl_setopt($ch_sustituciones, CURLOPT_POSTFIELDS, $dataSustitucion);
            curl_setopt($ch_sustituciones, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataSustitucion),
            ));
            curl_setopt($ch_sustituciones, CURLOPT_RETURNTRANSFER, true);

            // Ejecutar la solicitud para informes_sustituciones
            $response_sustituciones = curl_exec($ch_sustituciones);

            // Verificar si ocurrió algún error
            if (!curl_errno($ch_sustituciones)) {
                $sustitucionesCreadas = true;
            } else {
                throw new RestException('Error en la solicitud de sustituciones: ' . curl_error($ch_sustituciones), 500);
            }

            // Obtener el código de respuesta HTTP
            $httpCode = curl_getinfo($ch_sustituciones, CURLINFO_HTTP_CODE);

            // Cerrar sesión curl para informes_sustituciones
            curl_close($ch_sustituciones);
        
            // Verificar si hubo un error 500
            if ($httpCode === 500) {
                // Retornar el JSON de informessustituciones en caso de error 500
                return $dataSustitucion;
            }
        }
    }

    // Verificar si 'informesequipos' esta en el json enviado
    if (isset($requestData['informesequipos']) && is_array($requestData['informesequipos'])) {
        // Recorrer cada objeto 'informesequipos'
        foreach ($requestData['informesequipos'] as $equipoData) {
            // Agregar fk_report al objeto 'informesequipos'
            $equipoData['fk_report'] = $fk_report;

            // Convertir cada objeto a JSON
            $dataEquipo = json_encode($equipoData);

            // URL del endpoint para informes_equipos
            $url_equipos = 'https://erp.ortrat.es/api/index.php/mantenimientoapi/informes_equipos?DOLAPIKEY=c4c4c8582b90f4058abb1cb75d90442c41374219';

            // Inicializar sesión curl para informes_equipos
            $ch_equipos = curl_init($url_equipos);

            // Configurar opciones de curl para hacer una solicitud POST para informes_equipos
            curl_setopt($ch_equipos, CURLOPT_POST, 1);
            curl_setopt($ch_equipos, CURLOPT_POSTFIELDS, $dataEquipo);
            curl_setopt($ch_equipos, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataEquipo),
            ));
            curl_setopt($ch_equipos, CURLOPT_RETURNTRANSFER, true);

            // Ejecutar la solicitud para informes_equipos
            $response_equipos = curl_exec($ch_equipos);

            // Verificar si ocurrió algún error
            if (!curl_errno($ch_equipos)) {
                $equiposCreados = true;
            } else {
                throw new RestException('Error en la solicitud de equipos: ' . curl_error($ch_equipos), 500);
            }

            // Obtener el código de respuesta HTTP
            $httpCode = curl_getinfo($ch_equipos, CURLINFO_HTTP_CODE);

            // Cerrar sesión curl para informes_equipos
            curl_close($ch_equipos);
        
            // Verificar si hubo un error 500
            if ($httpCode === 500) {
                // Retornar el JSON de informesequipos en caso de error 500
                return $dataEquipo;
            }
        }
    }



    // Verificar si 'firma' está en el json enviado y es un array
    if (isset($requestData['firma']) && is_array($requestData['firma'])) {
        // Tomar el primer elemento del array (asumiendo que solo hay uno)
        $firmaData = $requestData['firma'][0];

        // Construir el valor de subdir según la lógica especificada
        $informeRef = $requestData['informe']['ref'];
        $firmaData['subdir'] = "/informes/{$informeRef}";
		$firmaData['modulepart'] = "mantenimiento";
		$firmaData['fileencoding'] = "base64";
		
        // Convertir la firmaData a JSON
        $dataFirma = json_encode($firmaData);

        // URL del endpoint para la firma
        $url_firma = 'https://erp.ortrat.es/api/index.php/documents/upload?DOLAPIKEY=c4c4c8582b90f4058abb1cb75d90442c41374219';

        // Inicializar sesión curl para la firma
        $ch_firma = curl_init($url_firma);

        // Configurar opciones de curl para hacer una solicitud POST para la firma
        curl_setopt($ch_firma, CURLOPT_POST, 1);
        curl_setopt($ch_firma, CURLOPT_POSTFIELDS, $dataFirma);
        curl_setopt($ch_firma, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataFirma),
        ));
        curl_setopt($ch_firma, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud para la firma
        $response_firma = curl_exec($ch_firma);

        // Verificar si ocurrió algún error en la solicitud de firma
        if (!curl_errno($ch_firma)) {
            $firmaCreada = true;
        } else {
            throw new RestException('Error en la solicitud de creación de firmas: ' . curl_error($ch_firma), 500);
        }

        // Obtener el código de respuesta HTTP de la solicitud de firma
        $httpCode = curl_getinfo($ch_firma, CURLINFO_HTTP_CODE);

        // Cerrar sesión curl para la firma
        curl_close($ch_firma);

        // Verificar si hubo un error 500 en la solicitud de firma
        if ($httpCode === 500) {
			throw new RestException(500, "Error al intentar procesar al procesar la firma,comprueba el nombre del archivo");
        }
    }

    // Retornar un JSON indicando si las operaciones se realizaron con éxito
    return array(
        'informeCreado' => $informeCreado,
        'sustitucionesCreadas' => $sustitucionesCreadas,
        'equiposCreados' => $equiposCreados,
        'firmaCreada' => $firmaCreada,
        'sustitucionesPresente' => isset($requestData['informessustituciones']),
        'equiposPresente' => isset($requestData['informesequipos'])
    );
}
}
