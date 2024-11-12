<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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

/**
 *  \file       informes_note.php
 *  \ingroup    mantenimiento
 *  \brief      Tab for notes on Informes
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/custom/averiasreparaciones/class/averias.class.php';
dol_include_once('/mantenimiento/class/informes.class.php');
dol_include_once('/mantenimiento/lib/mantenimiento_informes.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mantenimiento@mantenimiento", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$url = $_SERVER["PHP_SELF"]."?id=".$id; 
// Initialize technical objects
$object = new Informes($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->mantenimiento->multidir_output[$object->entity] . "/" . $object->id;
}

$permissionnote = $user->rights->mantenimiento->informes->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->mantenimiento->informes->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->mantenimiento->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once













if (isset($_POST['addRetired'])) {

	extract($_POST);

	if ($fk_product_root == "") {
		$fk_product_root = 0;
	}

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
	$sqlInsert.= " ( fk_report, id_fase_khonos, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned ) ";
	$sqlInsert.= " VALUES ";

	if ((isset($fk_product) && ($fk_product != -1)) && (empty($fk_service))) {

		$sqlInsert.= " (".$fk_report.", '".$observaciones."', ".$fk_product_root.", ".$fk_product.", ".$quantity.", ".$is_future.", 1, 0)";

		$resultInsert = $db->query($sqlInsert);

		$message = ($resultInsert) ? "Equipamiento de repuestos añadido con éxito" : "Error al añadir el equipamiento de repuestos";
		$type = ($resultInsert) ? "mesgs" : "errors";

	} else if (isset($fk_service) && ($fk_product == -1)) {

		$sqlInsert.= " (".$fk_report.", '".$observaciones."', ".$fk_product_root.", ".$fk_service.", ".$quantity.", ".$is_future.", 1, 0)";

		$resultInsert = $db->query($sqlInsert);

		$message = ($resultInsert) ? "Equipamiento de repuestos añadido con éxito" : "Error al añadir el equipamiento de repuestos";
		$type = ($resultInsert) ? "mesgs" : "errors";

	} else {

		$message = "Error al añadir el equipamiento de repuestos";
		$type = "errors";

	}

	setEventMessage($message, $type);

}

if (isset($_POST['addEquipment'])) {

	extract($_POST);

	if ($piezas == -1) {
		$piezas = "";
	}

	if ($reparaciones == -1) {
		$reparaciones = "";
	}

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_equipos ";
	$sqlInsert.= " ( fk_report, fk_product, advance, remarks, future_remarks, location, failure, repairs, qty_failure, qty_repairs ) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " (".$id.", ".$fk_product.", ".$advance.", '".$remarks."', '".$future_remarks."', '".$location."', '".$piezas."', '".$reparaciones."', '".$cant_piezas."', '".$cant_reparaciones."')";

	$resultInsert = $db->query($sqlInsert);

	$message = ($resultInsert) ? "Equipamiento añadido con éxito" : "Error al añadir el equipamiento";
	$type = ($resultInsert) ? "mesgs" : "errors";

	if ($piezas != "") {

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_piezasreparaciones ";
		$sqlInsert.= " ( fk_report, fk_product_root, fk_product, qty, is_future ) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " (".$id.", ".$fk_product.", ".$piezas.", ".$cant_piezas.", ".$tipo_pieza.")";

		$resultInsert = $db->query($sqlInsert);
	}

	if ($reparaciones != "") {

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_piezasreparaciones ";
		$sqlInsert.= " ( fk_report, fk_product_root, fk_product, qty, is_future ) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " (".$id.", ".$fk_product.", ".$reparaciones.", ".$cant_reparaciones.", ".$tipo_reparacion.")";

		$resultInsert = $db->query($sqlInsert);
	}

	setEventMessage($message, $type);

}elseif(isset($_POST['addReplacements'])){

	extract($_POST);

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
	$sqlInsert.= " ( fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned ) ";
	$sqlInsert.= " VALUES ";

	if ((isset($fk_product) && ($fk_product != -1)) && (empty($fk_service))) {

		$sqlInsert.= " (".$fk_report.", ".$fk_product_root.", ".$fk_product.", ".$quantity.", ".$is_future.", 0, 0)";

		$resultInsert = $db->query($sqlInsert);

		$message = ($resultInsert) ? "Equipamiento de repuestos añadido con éxito" : "Error al añadir el equipamiento de repuestos";
		$type = ($resultInsert) ? "mesgs" : "errors";

	} else if (isset($fk_service) && ($fk_product == -1)) {

		$sqlInsert.= " (".$fk_report.", ".$fk_product_root.", ".$fk_service.", ".$quantity.", ".$is_future.", 0, 0)";

		$resultInsert = $db->query($sqlInsert);

		$message = ($resultInsert) ? "Equipamiento de repuestos añadido con éxito" : "Error al añadir el equipamiento de repuestos";
		$type = ($resultInsert) ? "mesgs" : "errors";

	} else {

		$message = "Error al añadir el equipamiento de repuestos";
		$type = "errors";

	}


	setEventMessage($message, $type);

}elseif (isset($_POST['editEquipment'])) {
	
	$id = $_GET['id'];
	$equipo = $_POST['rowid'];
	$producto = $_GET['product'];
	$observaciones = $_POST['observ'];
	$observaciones_sig = $_POST['observ_sig'];
	$location = $_POST['location'];
	$avance = $_POST['avanc'];
	$piezas = $_POST['piezas'];
	$reparaciones = $_POST['reparaciones'];
	$cant_piezas = $_POST['cant_piezas'];
	$cant_reparaciones = $_POST['cant_reparaciones'];
	$tipo_pieza = $_POST['tipo_pieza'];
	$tipo_reparacion = $_POST['tipo_reparacion'];

	if ($piezas == -1) {
		$piezas = "NULL";
	}

	if ($reparaciones == -1) {
		$reparaciones = "NULL";
	}

	if ($cant_piezas == "") {
		$cant_piezas = "NULL";
	}

	if ($cant_reparaciones == "") {
		$cant_reparaciones = "NULL";
	}

	$consulta = " SELECT failure, repairs, qty_failure, qty_repairs FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
	$consulta.= " WHERE rowid = ".$equipo;

	$datos = $db->query($consulta);
	$datos = $db->fetch_object($datos);

	$piezasAnt = $datos->failure;
	$reparacionesAnt = $datos->repairs;
	$qtyPiezasAnt = $datos->qty_failure;
	$qtyReparacionesAnt = $datos->qty_repairs;

	$piezasVacias = false;
	$repVacias = false;
	$cantPiezasVacias = false;
	$cantRepVacias = false;

	if ($piezasAnt == "") {
		$piezasVacias = true;
	}

	if ($reparacionesAnt == "") {
		$repVacias = true;
	}

	if ($qtyPiezasAnt == "") {
		$cantPiezasVacias = true;
	}

	if ($qtyReparacionesAnt == "") {
		$cantRepVacias = true;
	}

	//if (($piezasVacias) || ($repVacias)) {

		if ($piezasVacias) {
			$piezasAnt = $piezas;
		} else {
			if ($piezas != "") {
				$piezasAnt = $piezasAnt.", ".$piezas;
			}
		}

		if ($repVacias) {
			$reparacionesAnt = $reparaciones;
		} else {
			if ($reparaciones != "") {
				$reparacionesAnt = $reparacionesAnt.", ".$reparaciones;
			}
		}

		if ($cantPiezasVacias) {
			$qtyPiezasAnt = $cant_piezas;
		} else {
			if ($cant_piezas != "") {
				$qtyPiezasAnt = $qtyPiezasAnt.", ".$cant_piezas;
			}
		}

		if ($cantRepVacias) {
			$qtyReparacionesAnt = $cant_reparaciones;
		} else {
			if ($cant_reparaciones != "") {
				$qtyReparacionesAnt = $qtyReparacionesAnt.", ".$cant_reparaciones;
			}
		}

	if ($piezasAnt == "NULL") {
		$piezasAnt = "";
	}

	if ($reparacionesAnt == "NULL") {
		$reparacionesAnt = "";
	}

	if ($qtyPiezasAnt == "NULL") {
		$qtyPiezasAnt = "";
	}

	if ($qtyReparacionesAnt == "NULL") {
		$qtyReparacionesAnt = "";
	}

	$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
	$sql.= " SET advance = ".$avance.", remarks = '".$observaciones."', ";
	$sql.= " future_remarks = '".$observaciones_sig."', location = '".$location."' ";

	if ($piezas != "NULL") {
		$sql.= ", failure = '".$piezasAnt."', qty_failure = '".$qtyPiezasAnt."' ";
	}

	if ($reparaciones != "NULL") {
		$sql.= ", repairs = '".$reparacionesAnt."', qty_repairs = '".$qtyReparacionesAnt."' ";
	}

	$sql.= " WHERE rowid = ".$equipo."";
	
	$resultInsert = $db->query($sql);

	/*$consulta = " SELECT rowid, fk_product_root, fk_product, qty, is_future FROM ".MAIN_DB_PREFIX."mantenimiento_informes_piezasreparaciones ";
	$consulta.= " WHERE fk_report = ".$id." AND fk_product_root = ".$producto."";

	print $consulta;

	$resultConsulta = $db->query($consulta);

	$numFilas = $db->num_rows($resultConsulta);

		while ($product = $db->fetch_object($resultConsulta)) {

			$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_piezasreparaciones ";
			$sqlUpdate.= " SET fk_product = ".$product->fk_product.", qty = ".$product->qty.", is_future = ".$product->is_future."";
			$sqlUpdate.= " WHERE rowid = ".$product->rowid."";

			$db->query($sqlUpdate);

		}*/

	if ($piezas != "NULL") {

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_piezasreparaciones ";
		$sqlInsert.= " ( fk_report, fk_product_root, fk_product, qty, is_future ) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " (".$id.", ".$producto.", ".$piezas.", ".$cant_piezas.", ".$tipo_pieza.")";

		$resultInsert = $db->query($sqlInsert);

		if ($tipo_pieza == 1) {
			$sqlInsert2 = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
			$sqlInsert2.= " ( fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned, id_fase_khonos ) ";
			$sqlInsert2.= " VALUES ";
			$sqlInsert2.= " (".$id.", ".$producto.", ".$piezas.", ".$cant_piezas.", ".$tipo_pieza.", 0, 0, '".$equipo."')";
	
			$resultInsert2 = $db->query($sqlInsert2);
		} else {
			$sqlInsert2 = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
			$sqlInsert2.= " ( fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned ) ";
			$sqlInsert2.= " VALUES ";
			$sqlInsert2.= " (".$id.", ".$producto.", ".$piezas.", ".$cant_piezas.", ".$tipo_pieza.", 0, 0)";
	
			$resultInsert2 = $db->query($sqlInsert2);
		}

	}

	if ($reparaciones != "NULL") {

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_piezasreparaciones ";
		$sqlInsert.= " ( fk_report, fk_product_root, fk_product, qty, is_future ) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " (".$id.", ".$producto.", ".$reparaciones.", ".$cant_reparaciones.", ".$tipo_reparacion.")";

		$resultInsert = $db->query($sqlInsert);

		if ($tipo_reparacion == 1) {
			$sqlInsert2 = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
			$sqlInsert2.= " ( fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned, id_fase_khonos ) ";
			$sqlInsert2.= " VALUES ";
			$sqlInsert2.= " (".$id.", ".$producto.", ".$reparaciones.", ".$cant_reparaciones.", ".$tipo_reparacion.", 0, 0, '".$equipo."')";

			$resultInsert2 = $db->query($sqlInsert2);
		} else {
			$sqlInsert2 = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
			$sqlInsert2.= " ( fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned ) ";
			$sqlInsert2.= " VALUES ";
			$sqlInsert2.= " (".$id.", ".$producto.", ".$reparaciones.", ".$cant_reparaciones.", ".$tipo_reparacion.", 0, 0)";

			$resultInsert2 = $db->query($sqlInsert2);
		}
	}
	
	$message = ($resultInsert) ? "Equipamiento editado con éxito" : "Error al editar el equipamiento";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);
	
}elseif (isset($_POST['editReplacementEquipment'])) {
	
	extract($_POST);

	$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones ";
	$sql.= " SET quantity = ".$quantity." ";
	$sql.= " WHERE rowid = ".$rowid."";
	
	$resultInsert = $db->query($sql);
	
	$message = ($resultInsert) ? "Equipamiento de repuesto editado con éxito" : "Error al editar el equipamiento de repuesto";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);
}

if (isset($_POST['editRetiredEquipment'])) {
	
	extract($_POST);

	$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones ";
	$sql.= " SET quantity = ".$quantity.", ";
	$sql.= " is_returned = ".$devuelto."";

	if ($devuelto == 1) {

		$sql.= ", is_retired = 0";

	}

	if ($observaciones != "") {

		$sql.= ", id_fase_khonos = '".$observaciones."'";

	}

	$sql.= " WHERE rowid = ".$rowid."";
	
	$resultInsert = $db->query($sql);
	
	$message = ($resultInsert) ? "Equipamiento de repuesto editado con éxito" : "Error al editar el equipamiento de repuesto";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);
}

if (isset($_POST['editReturnedEquipment'])) {
	
	extract($_POST);

	$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones ";
	$sql.= " SET quantity = ".$quantity." ";

	if ($devuelto == 0) {

		$sql.= ", is_returned = 0, is_retired = 1";

	}

	if ($observaciones != "") {

		$sql.= ", id_fase_khonos = '".$observaciones."'";

	}

	$sql.= " WHERE rowid = ".$rowid."";
	
	$resultInsert = $db->query($sql);
	
	$message = ($resultInsert) ? "Equipamiento de repuesto editado con éxito" : "Error al editar el equipamiento de repuesto";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);
}


if (isset($_POST['addHeredado'])) {

	$heredados = $_POST['heredado'];
	$padres = $_POST['padre'];
	$cantidades = $_POST['cantidad'];

	$listaPadres = array();
	foreach ($padres as $clave => $valor) {
		$listaPadres [] = $valor;
	}

	$listaCantidades = array();
	foreach ($cantidades as $clave => $valor) {
		$listaCantidades [] = $valor;
	}

	$i = 0;
	foreach ($heredados as $clave => $valor) {

		$sqlBorrado = " DELETE FROM ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
		$sqlBorrado.= " WHERE fk_report = ".$id." AND fk_product = ".$clave." AND is_future = 1 ";

		$db->query($sqlBorrado);

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_sustituciones ";
		$sqlInsert.= " (fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " ($id, ".$listaPadres[$i].", $valor, ".$listaCantidades[$i].", 0, 0, 0) ";

		$db->query($sqlInsert);

		//AHORA HAY QUE METERLO TODO EN LA TABLA EQUIPOS
		//Comprobamos si el producto es un equipo o un servicio
		$consultaTipo = " SELECT fk_product_type FROM ". MAIN_DB_PREFIX ."product p ";
		$consultaTipo.= " WHERE rowid = ".$valor;

		$resultTipo = $db->query($consultaTipo);
		$tipo = $db->fetch_object($resultTipo);
		$tipo = $tipo->fk_product_type;

		//Sacamos su ROWID de EQUIPO
		$sqlEquipo = " SELECT rowid FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
		$sqlEquipo.= " WHERE fk_report = ".$id." AND fk_product = ".$listaPadres[$i];

		$resultEquipo = $db->query($sqlEquipo);
		$equipo = $db->fetch_object($resultEquipo);
		$equipo = $equipo->rowid;

		//HACEMOS LA CONSULTA PARA SACAR LOS DATOS DE EQUIPOS
		$consulta = " SELECT failure, repairs, qty_failure, qty_repairs FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
		$consulta.= " WHERE rowid = ".$equipo;
	
		$datos = $db->query($consulta);
		$datos = $db->fetch_object($datos);
	
		$piezasAnt = $datos->failure;
		$reparacionesAnt = $datos->repairs;
		$qtyPiezasAnt = $datos->qty_failure;
		$qtyReparacionesAnt = $datos->qty_repairs;
	
		$piezasVacias = false;
		$repVacias = false;
		$cantPiezasVacias = false;
		$cantRepVacias = false;
	
		if ($piezasAnt == "") {
			$piezasVacias = true;
		}
	
		if ($reparacionesAnt == "") {
			$repVacias = true;
		}
	
		if ($qtyPiezasAnt == "") {
			$cantPiezasVacias = true;
		}
	
		if ($qtyReparacionesAnt == "") {
			$cantRepVacias = true;
		}
	
		//ES UNA PIEZA
		if ($tipo == 0) {

			if ($piezasVacias) {

				$piezasAnt = $valor;
			} else {

				$piezasAnt = $piezasAnt.", ".$valor;
			}
	
			if ($cantPiezasVacias) {

				$qtyPiezasAnt = $listaCantidades[$i];
			} else {

				$qtyPiezasAnt = $qtyPiezasAnt.", ".$listaCantidades[$i];
			}


			$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
			$sql.= " SET failure = '".$piezasAnt."', qty_failure = '".$qtyPiezasAnt."' ";
			$sql.= " WHERE rowid = ".$equipo."";
			
			$db->query($sql);

		} else {

			if ($repVacias) {

				$reparacionesAnt = $valor;
			} else {

				$reparacionesAnt = $reparacionesAnt.", ".$valor;
			}
	
			if ($cantRepVacias) {

				$qtyReparacionesAnt = $listaCantidades[$i];
			} else {

				$qtyReparacionesAnt = $qtyReparacionesAnt.", ".$listaCantidades[$i];
			}


			$sql = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes_equipos ";
			$sql.= " SET repairs = '".$reparacionesAnt."', qty_repairs = '".$qtyReparacionesAnt."' ";
			$sql.= " WHERE rowid = ".$equipo."";

			$db->query($sql);

		}









		$sqlInsert2 = " INSERT INTO ". MAIN_DB_PREFIX ."mantenimiento_informes_piezasreparaciones ";
		$sqlInsert2.= " (fk_report, fk_product_root, fk_product, qty, is_future) ";
		$sqlInsert2.= " VALUES ";
		$sqlInsert2.= " ($id, ".$listaPadres[$i].", $valor, ".$listaCantidades[$i].", 0) ";

		$db->query($sqlInsert2);
		
		$i++;
	}

	$sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."mantenimiento_informes ";
	$sqlUpdate.= " SET futures_inherited = 2 ";
	$sqlUpdate.= " WHERE rowid = ".$id;

	//die;

	$db->query($sqlUpdate);

}


if (isset($_POST['descartarHeredado'])) {

	$sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."mantenimiento_informes ";
	$sqlUpdate.= " SET futures_inherited = 3 ";
	$sqlUpdate.= " WHERE rowid = ".$id;

	$db->query($sqlUpdate);

}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Equipos'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();
	$object->fetch($id);
	
	$head = informesPrepareHead($object);

	print dol_get_fiche_head($head, 'equipos', '', -1, $object->picto);

	//COMIENZO
	//Para saber si hay otro informe anterior en el MISMO CONTRATO
	$sqlInformes = " SELECT * FROM ".MAIN_DB_PREFIX."mantenimiento_informes ";
	$sqlInformes.= " WHERE contract_id = ".$object->contract_id." AND rowid < ".$id." ";
	$sqlInformes.= " ORDER BY rowid DESC LIMIT 1";

	$resultInformes = $db->query($sqlInformes);
	$numInformes = $db->num_rows($resultInformes);
	$informe = $db->fetch_object($resultInformes);

	$sqlFuturos = " SELECT * FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones ";
	$sqlFuturos.= " WHERE is_future = 1 AND fk_report = ".$informe->rowid." ";

	$resultFuturos = $db->query($sqlFuturos);
	$numFuturos = $db->num_rows($resultFuturos);

	if (($numInformes > 0) && ($object->futures_inherited == "")) {

		if ($numFuturos > 0) {

			$listaHeredados = array();

			while ($futuro = $db->fetch_object($resultFuturos)) {

				$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones ";
				$sqlInsert.= " (fk_report, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned) ";
				$sqlInsert.= " VALUES ";
				$sqlInsert.= " ($id, $futuro->fk_product_root, $futuro->fk_product, $futuro->quantity, $futuro->is_future, $futuro->is_retired, $futuro->is_returned) ";

				$db->query($sqlInsert);

				$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mantenimiento_informes ";
				$sqlUpdate.= " SET futures_inherited = 1 ";
				$sqlUpdate.= " WHERE rowid = ".$id;

				$db->query($sqlUpdate);

				print '<meta http-equiv="refresh" content="0; url="informes_equipos.php?id='.$id.'">';

			}

		}

	}

	if ($numFuturos > 0) {

		$sqlHeredados = " SELECT mis.rowid, mis.fk_product, mis.quantity, p.ref, p.description, p.rowid as proid, pr.ref as rootref, pr.description as rootref, pr.rowid as rootid FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
		$sqlHeredados.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
		$sqlHeredados.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
		$sqlHeredados.= " WHERE fk_report = ".$id." AND is_future = 1 LIMIT ".$numFuturos." ";

		$resultHere = $db->query($sqlHeredados);

	}

	$sqlProducts = " SELECT p.rowid, p.label, p.ref FROM ".MAIN_DB_PREFIX."product p";
	$sqlProducts.= " WHERE p.fk_product_type = 0 ";
	//$sqlProducts.= " WHERE p.rowid IN ";
	//$sqlProducts.= " (SELECT mce.fk_product FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos mce WHERE mce.fk_contract = ".$object->contract_id.") ";
	
	$resultProducts = $db->query($sqlProducts);
	$productsList = [];
	while ($product = $db->fetch_object($resultProducts)) {							
		$productsList[] = $product;
	}

	$sqlServicios = "SELECT p.rowid, p.label, p.ref FROM ".MAIN_DB_PREFIX."product p WHERE fk_product_type = 1";
	$servicios = $db->query($sqlServicios);
	$servicios2 = $db->query($sqlServicios);
	//$servicios3 = $db->query($sqlServicios);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/mantenimiento/informes_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	print dol_get_fiche_end();

	$arrayfields = array(
		'cod' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'avance' => array('label' => $langs->trans("Avance"), 'checked' => 1),
		'observaciones' => array('label' => $langs->trans("Observaciones"), 'checked' => 1),
		'observacionessigvisita' => array('label' => $langs->trans("Observaciones sig visita"), 'checked' => 1),
		'ubicacion' => array('label' => $langs->trans("Ubicacion"), 'checked' => 1),
		'piezas' => array('label' => $langs->trans("Piezas"), 'checked' => 1),
		'cant_piezas' => array('label' => $langs->trans("Cant. Piezas"), 'checked' => 1),
		'reparaciones' => array('label' => $langs->trans("Reparaciones"), 'checked' => 1),
		'cant_reparaciones' => array('label' => $langs->trans("Cant. Rep."), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'cod' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
			'avance' => array('label' => $langs->trans("Avance"), 'checked' => 0),
			'observaciones' => array('label' => $langs->trans("Observaciones"), 'checked' => 0),
			'observacionessigvisita' => array('label' => $langs->trans("Observaciones sig visita"), 'checked' => 0),
			'ubicacion' => array('label' => $langs->trans("Ubicacion"), 'checked' => 0),
			'piezas' => array('label' => $langs->trans("Piezas"), 'checked' => 0),
			'cant_piezas' => array('label' => $langs->trans("Cant. Piezas"), 'checked' => 0),
			'reparaciones' => array('label' => $langs->trans("Reparaciones"), 'checked' => 0),
			'cant_reparaciones' => array('label' => $langs->trans("Cant. Rep."), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	//$newcardbutton = '';
	//$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo parte'), '', 'fa fa-plus-circle', '#addEquipmentModal');

	$sqlProducts = "WITH RECURSIVE product_hierarchy AS (
		SELECT p.rowid, p.ref, p.label, p.description, 1 as level
		FROM ".MAIN_DB_PREFIX."product p
		INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ON mie.fk_product = p.rowid
		INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mie.fk_report
		WHERE mi.rowid = ".$id."
		UNION ALL
		SELECT p.rowid, p.ref, p.label, p.description, ph.level + 1 as level
		FROM product_hierarchy ph
		INNER JOIN ".MAIN_DB_PREFIX."bom_bom bb ON ph.rowid = bb.fk_product
		INNER JOIN ".MAIN_DB_PREFIX."bom_bomline bbl ON bb.rowid = bbl.fk_bom
		INNER JOIN ".MAIN_DB_PREFIX."product p ON bbl.fk_product = p.rowid
		WHERE ph.level < 4 
	)
	SELECT rowid, ref, label, description
	FROM (
		SELECT ph.*, ROW_NUMBER() OVER (PARTITION BY ref ORDER BY rowid) as row_num
		FROM product_hierarchy ph
	) as numbered_rows
	WHERE row_num = 1";

	$resultProducts = $db->query($sqlProducts);

	$sqlServices = " SELECT rowid, ref, label ";
	$sqlServices.= " FROM ".MAIN_DB_PREFIX."product ";
	$sqlServices.= " WHERE fk_product_type = 1";

	$resultServices = $db->query($sqlServices);

	print '<a class="butAction" type="button" href="#addEquipmentModal" rel="modal:open">Nuevo parte</a>';
	if ($object->futures_inherited == 1) {
		print '<a class="butAction" type="button" href="#addEquipmentModal2" rel="modal:open">Incorporar equipos heredados</a>';
	}
	print '
	<div id="addEquipmentModal" class="modal" role="dialog" aria-labelledby="addMaterialModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Producto</label>
							</td>
							<td>
								<select name="fk_product" style="width: 250px;" class="select-products">
								<option value="-1" selected disabled>Selecciona un producto</option>
								';
	
								foreach ($productsList as $key => $product) {
			
									print ' <option value="'.$product->rowid.'">'.$product->ref.' - '.$product->label.'</option>';
								}
			
								print '
								</select>
								<a href="../../product/card.php?action=create"><span class="fa fa-plus-circle valignmiddle paddingleft" title="Crear producto"></span></a>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Avance</label>
							</td>
							<td>
								<select name="advance" class="select-advance">
									<option value="0">No</option>
									<option value="1">Si</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Observaciones</label>
							</td>
							<td>
								<textarea name="remarks" >
								</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Observaciones sig visita</label>
							</td>
							<td>
								<textarea name="future_remarks" >
								</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<span>Ubicación</span>
							</td>
							<td>
								<input name="location" type="text">
							</td>
						</tr>
						<tr>
							<td>
								<span>Piezas</span>
							</td>
							<td>
								<select class="select-piezas" name="piezas">
								<option value=-1>&nbsp;</option>';
								
								while ($productF = $db->fetch_object($resultProducts)) {

									print ' <option value="'.$productF->rowid.'">'.$productF->ref.' - '.$productF->label.'</option>';

								}

							print '

								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad Piezas</label>
							</td>
							<td>
								<input type="number" name="cant_piezas">
							</td>
						</tr>
						<tr>
							<td>
								<span>Tipo de Pieza</span>
							</td>
							<td>
								<select class="select-tipoPieza" name="tipo_pieza">
									<option value="0" selected>Actual</option>
									<option value="1">Futura</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span>Reparaciones</span>
							</td>
							<td>
								<select class="select-reparaciones" name="reparaciones">

								<option value=-1>&nbsp;</option>';
								
								while ($serviceF = $db->fetch_object($resultServices)) {

									print ' <option value="'.$serviceF->rowid.'">'.$serviceF->ref.' - '.$serviceF->label.'</option>';

								}

							print '

								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad Reparaciones</label>
							</td>
							<td>
								<input type="number" name="cant_reparaciones">
							</td>
						</tr>
						<tr>
							<td>
								<span>Tipo de Reparación</span>
							</td>
							<td>
								<select class="select-tipoReparacion" name="tipo_reparacion">
									<option value="0" selected>Actual</option>
									<option value="1">Futura</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addEquipment" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	//SEGUNDO MODAL
	print '
	<div id="addEquipmentModal2" class="modal" role="dialog" aria-labelledby="addMaterialModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material2</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Productos a heredar</label>
							</td>
						</tr>	
								';

								while ($here = $db->fetch_object($resultHere)) {
									print '<tr>';
										print '<td>';
											print '<input style="width:400px !important" type="text" readonly value="'.$here->ref.' - '.$here->description.'">';
										print '</td>';
											print '<input type="hidden" name="heredado['.$here->proid.']" value="'.$here->proid.'">';
											print '<input type="hidden" name="padre['.$here->rootid.']" value="'.$here->rootid.'">';
											print '<input type="hidden" name="cantidad['.$here->quantity.']" value="'.$here->quantity.'">';
									print '</tr>';
								}
			
								print '
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addHeredado" class="butAction">Añadir</button>
				<button type="submit" name="descartarHeredado" class="butAction">Descartar</button>
			</div>
		</form>
	</div>
	';

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Equipos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['cod']['checked'])) {
		print "<th class='center liste_titre' title='Código'>";
		print "</th>";
	}

	if (!empty($arrayfields['cod']['checked'])) {
		print "<th class='center liste_titre' title='Código'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfields['articulo']['checked'])) {
		print "<th class='center liste_titre' title='Articulo'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['avance']['checked'])) {
		print "<th class='center liste_titre' title='Avance'>";
		print "<a class='reposition' href=''>Avance</a>";
		print "</th>";
	}

	if (!empty($arrayfields['observaciones']['checked'])) {
		print "<th class='center liste_titre' title='Observaciones'>";
		print "<a class='reposition' href=''>Observaciones</a>";
		print "</th>";
	}

	if (!empty($arrayfields['observacionessigvisita']['checked'])) {
		print "<th class='center liste_titre' title='Observaciones sig visita'>";
		print "<a class='reposition' href=''>Observaciones sig visita</a>";
		print "</th>";
	}

	if (!empty($arrayfields['ubicacion']['checked'])) {
		print "<th class='center liste_titre' title='Ubicacion'>";
		print "<a class='reposition' href=''>Ubicación</a>";
		print "</th>";
	}

	if (!empty($arrayfields['piezas']['checked'])) {
		print "<th class='center liste_titre' title='Piezas'>";
		print "<a class='reposition' href=''>Piezas</a>";
		print "</th>";
	}

	if (!empty($arrayfields['cant_piezas']['checked'])) {
		print "<th class='center liste_titre' title='Cantidad de Piezas'>";
		print "<a class='reposition' href=''>Cant. Piezas</a>";
		print "</th>";
	}

	if (!empty($arrayfields['reparaciones']['checked'])) {
		print "<th class='center liste_titre' title='Reparaciones'>";
		print "<a class='reposition' href=''>Reparaciones</a>";
		print "</th>";
	}

	if (!empty($arrayfields['cant_reparaciones']['checked'])) {
		print "<th class='center liste_titre' title='Cantidad de Reparaciones'>";
		print "<a class='reposition' href=''>Cant. Rep.</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	$sqlMaterials = " SELECT mie.rowid, mie.advance, mie.remarks, mie.future_remarks, mie.location, mie.failure, mie.repairs, mie.qty_failure, mie.qty_repairs, ";
	$sqlMaterials.= " p.rowid as product_id, p.ref, p.description, p.label ";
	$sqlMaterials.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ";
	$sqlMaterials.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mie.fk_product ";
	$sqlMaterials.= " WHERE mie.fk_report =".$id;
	
	$result = $db->query($sqlMaterials);

	$equipments = [];
	while ($equipment = $db->fetch_object($result)) {
		$equipments[] = $equipment;
	}

	$i = 0;
	foreach ($equipments as $key => $equipment ) {

		$advance = $equipment->advance == 1 ? "Si" : "No";

		print '<tr class="oddeven">';

		print "<td class='center' tdoverflowmax200'>";
			print '<a class="butAction" type="button" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=showMaterials&fk_product='.$equipment->product_id.'">+</a>';
		print "</td>";

		if (!empty($arrayfields['cod']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->ref."</td>";

		if (!empty($arrayfields['articulo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->description."</td> ";

		if (!empty($arrayfields['avance']['checked']))
			print "<td class='center' tdoverflowmax200'>".$advance."</td> ";

		if (!empty($arrayfields['observaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->remarks."</td> ";

		if (!empty($arrayfields['observacionessigvisita']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->future_remarks."</td> ";	

		if (!empty($arrayfields['ubicacion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->location."</td> ";

		if (!empty($arrayfields['piezas']['checked']))

			if (($equipment->failure == "") || ($equipment->failure == NULL)) {
				print "<td class='center' tdoverflowmax200'>".$equipment->failure."</td> ";
			} else {
				/*$sqlPiezas = " SELECT ref FROM ".MAIN_DB_PREFIX."product ";
				$sqlPiezas.= " WHERE rowid = ".$equipment->failure;*/

				$sqlPiezas = " SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product ";
				$sqlPiezas.= " WHERE rowid IN (".$equipment->failure.") ORDER BY FIELD (rowid, ".$equipment->failure.")";

				$resultado = $db->query($sqlPiezas);

				print "<td class='center' tdoverflowmax200'>";

				$cadena = "";
				while ($producto = $db->fetch_object($resultado)) {
					if ($cadena == "") {
						$cadena = $producto->ref;
					} else {
						$cadena = ", ".$producto->ref;
					}

					print $cadena;
				}

				print "</td>";

			}

		if (!empty($arrayfields['cant_piezas']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->qty_failure."</td> ";
		
		
		if (!empty($arrayfields['reparaciones']['checked']))

			if (($equipment->repairs == "") || ($equipment->repairs == NULL)) {
				print "<td class='center' tdoverflowmax200'>".$equipment->repairs."</td> ";
			} else {
				/*$sqlReparaciones = " SELECT ref FROM ".MAIN_DB_PREFIX."product ";
				$sqlReparaciones.= " WHERE rowid = ".$equipment->repairs;*/

				$sqlReparaciones = " SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product ";
				$sqlReparaciones.= " WHERE rowid IN (".$equipment->repairs.") ORDER BY FIELD (rowid, ".$equipment->repairs.")";

				$resultado = $db->query($sqlReparaciones);

				print "<td class='center' tdoverflowmax200'>";

				$cadena = "";
				while ($servicio = $db->fetch_object($resultado)) {
					if ($cadena == "") {
						$cadena = $servicio->ref;
					} else {
						$cadena = ", ".$servicio->ref;
					}

					print $cadena;
				}

				print "</td>";

			}

		if (!empty($arrayfields['cant_reparaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipment->qty_repairs."</td> ";

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="informes_equipos.php?action=editar&id=' . $object->id . '&rowid=' . $equipment->rowid . '&product='.$equipment->product_id.'">' . img_edit() . '</a>
					</td>
					<td>
						<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrar&id=' . $object->id . '&rowid=' . $equipment->rowid . '&product='.$equipment->product_id.'">' . img_delete() . '</a>		
					</td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;
		
	}
	print "</table>";
	//print '</div>';//Tocar por aqui
	

	$arrayfields2 = array(
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfields2 = array(
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 0),
			'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfields2[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfields2, $varpage2); // This also change content of $arrayfields

	$id_usuario = $object->id;

	$nbtotalofrecords = $num;

	$i = 0;

	//MATERIALES DE REPUESTO

	$sqlReplacements = " SELECT mis.rowid, mis.quantity, mis.fk_product, p.rowid as product_id, p.ref, p.description, pr.ref as pr_ref ";
	$sqlReplacements.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sqlReplacements.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sqlReplacements.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sqlReplacements.= " WHERE mis.is_future = 0 AND mis.is_retired = 0 AND mis.is_returned = 0 AND mis.fk_report =".$id;
	
	$resultReplacements = $db->query($sqlReplacements);

	$sqlPiezasRep = "SELECT mip.rowid, pp.ref as padre, p.ref, p.label, p.description, mip.qty FROM ".MAIN_DB_PREFIX."mantenimiento_informes_piezasreparaciones mip INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mip.fk_report INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mip.fk_product INNER JOIN ".MAIN_DB_PREFIX."product pp on mip.fk_product_root = pp.rowid WHERE mi.rowid = 7 AND mip.is_future = 0 ";
	
	$resultPiezasRep = $db->query($sqlPiezasRep);

	print "<br>";
	print "<br>";
	print_barre_liste($langs->trans("Materiales de sustitución y servicios de reparación"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);
	
	//print '<a class="butAction" type="button" href="#addReplacementModal" rel="modal:open">Nuevo material o servicio de repuesto</a>';
	print '
	<div id="addReplacementModal" class="modal" role="dialog" aria-labelledby="addMaterialModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<input type="hidden" name="fk_report" value="'.$id.'">
			<input type="hidden" name="is_future" value="0">
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Producto raiz</label>
							</td>
							<td>
								<select name="fk_product_root" id="fk_product_root" style="width: 250px;" class="select-products">
								<option value="-1" selected disabled>Selecciona un producto</option>
								';
	
								foreach ($equipments as $key => $equipment) {
			
									print ' <option value="'.$equipment->product_id.'">'.$equipment->ref.' - '.$equipment->label.'</option>';
								}
			
								print '
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Producto a sustituir</label>
							</td>
							<td>
								<select id="fk_product_components" name="fk_product" style="width: 250px;" class="select-products">
									<option value="-1" selected disabled>Selecciona un producto</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Servicio</label>
							</td>
							<td>
								<select name="fk_service" id="fk_service" style="width: 250px;" class="select-service">
								<option value="-1" selected disabled>Selecciona un servicio</option>
								';

									while ($servicio = $db->fetch_object($servicios)) {	

										print ' <option value="'.$servicio->rowid.'">'.$servicio->ref.' - '.$servicio->label.'</option>';

									}
			
								print '
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad</label>
							</td>
							<td>
								<input type="number" name="quantity">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addReplacements" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';


	print '<table class="tagtable liste">';
		print "<thead>";
			print "<tr class='liste_titre'>";
				print "<th class='center liste_titre'>Código padre</th>";
				print "<th class='center liste_titre'>Código hijo</th>";
				print "<th class='center liste_titre'>Articulo</th>";
				print "<th class='center liste_titre'>Cantidad</th>";
				print "<th class='center liste_titre'>Acciones</th>";
			print "</tr>";
		print "</thead>";
		print "<tbody>";
		while ($replacement = $db->fetch_object($resultReplacements)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $replacement->pr_ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->quantity . "</td> ";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $replacement->rowid . '&product='.$replacement->fk_product.'">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td>";
			print "</tr>";
		}

		/*while ($replacement2 = $db->fetch_object($resultPiezasRep)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->padre . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->qty . "</td> ";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $replacement2->rowid . '">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td>";
			print "</tr>";
		}*/
		print "</tbody>";
	print '</table>';

	//MATERIALES DE REPUESTO FUTUROS

	$sqlReplacementsFuture = " SELECT mis.rowid, mis.quantity, mis.fk_product, p.rowid as product_id, p.ref, p.description, pr.ref as pr_ref ";
	$sqlReplacementsFuture.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sqlReplacementsFuture.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sqlReplacementsFuture.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sqlReplacementsFuture.= " WHERE mis.is_future = 1 AND mis.fk_report =".$id;

	$resultReplacementsFuture = $db->query($sqlReplacementsFuture);

	$sqlPiezasRepFuture = "SELECT mip.rowid, pp.ref as padre, p.ref, p.label, p.description, mip.qty FROM ".MAIN_DB_PREFIX."mantenimiento_informes_piezasreparaciones mip INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mip.fk_report INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mip.fk_product INNER JOIN ".MAIN_DB_PREFIX."product pp on mip.fk_product_root = pp.rowid WHERE mi.rowid = 7 AND mip.is_future = 1 ";
	
	$resultPiezasRepFuture = $db->query($sqlPiezasRepFuture);
	
	print "<br>";
	print "<br>";

	print_barre_liste($langs->trans("Materiales de sustitución y servicios de reparación futuros"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);
	
	//print '<a class="butAction" type="button" href="#addReplacementFutureModal" rel="modal:open">Nuevo material o servicio de repuesto futuro</a>';
	print '
	<div id="addReplacementFutureModal" class="modal" role="dialog" aria-labelledby="addMaterialModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<input type="hidden" name="fk_report" value="'.$id.'">
			<input type="hidden" name="is_future" value="1">
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material o servicio</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Producto raiz</label>
							</td>
							<td>
								<select name="fk_product_root" id="fk_product_root_future" style="width: 250px;" class="select-products">
								<option value="-1" selected disabled>Selecciona un producto</option>
								';
	
								foreach ($equipments as $key => $equipment) {
			
									print ' <option value="'.$equipment->product_id.'">'.$equipment->ref.' - '.$equipment->label.'</option>';
								}
			
								print '
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Producto a sustituir</label>
							</td>
							<td>
								<select id="fk_product_components_future" name="fk_product" style="width: 250px;" class="select-products">
									<option value="-1" selected disabled>Selecciona un producto</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Servicio</label>
							</td>
							<td>
								<select name="fk_service" id="fk_service" style="width: 250px;" class="select-service">
								<option value="-1" selected disabled>Selecciona un servicio</option>
								';

									while ($servicio = $db->fetch_object($servicios2)) {	

										print ' <option value="'.$servicio->rowid.'">'.$servicio->ref.' - '.$servicio->label.'</option>';

									}
			
								print '
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad</label>
							</td>
							<td>
								<input type="number" name="quantity">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addReplacements" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	print '<table class="tagtable liste">';
		print "<thead>";
			print "<tr class='liste_titre'>";
				print "<th class='center liste_titre'>Código padre</th>";
				print "<th class='center liste_titre'>Código hijo</th>";
				print "<th class='center liste_titre'>Articulo</th>";
				print "<th class='center liste_titre'>Cantidad</th>";
				print "<th class='center liste_titre'>Acciones</th>";
			print "</tr>";
		print "</thead>";
		print "<tbody>";
		while ($replacement = $db->fetch_object($resultReplacementsFuture)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $replacement->pr_ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->quantity . "</td> ";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $replacement->rowid . '&product='.$replacement->fk_product.'">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td> ";
			print "</tr>";
		}

		/*while ($replacement2 = $db->fetch_object($resultPiezasRepFuture)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->padre . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement2->qty . "</td> ";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $replacement2->rowid . '">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td> ";
			print "</tr>";
		}*/
		print "</tbody>";
	print '</table>';

	print '</div>';


	//MATERIALES RETIRADOS

	$sqlReplacementsReturned = " SELECT mis.id_fase_khonos as observ, mis.rowid, mis.quantity, p.rowid as product_id, p.ref, p.description, pr.ref as pr_ref ";
	$sqlReplacementsReturned.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sqlReplacementsReturned.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sqlReplacementsReturned.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sqlReplacementsReturned.= " WHERE mis.is_retired = 1 AND mis.is_returned = 0 AND mis.fk_report =".$id;

	$resultReplacementsReturned = $db->query($sqlReplacementsReturned);
	$numRetirados = $db->num_rows($resultReplacementsReturned);
	
	print "<br>";
	print "<br>";

	print_barre_liste($langs->trans("Materiales retirados"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	/*$sqlReplacements2 = " SELECT mis.rowid, mis.quantity, p.rowid as product_id, p.ref, p.description, pr.ref as pr_ref ";
	$sqlReplacements2.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sqlReplacements2.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sqlReplacements2.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sqlReplacements2.= " WHERE mis.is_future = 0 AND mis.fk_report =".$id;
	
	$resultReplacements2 = $db->query($sqlReplacements2);*/

	$sqlProductos = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";
	$sqlProductos.= " WHERE fk_product_type = 0";

	$resultProductos = $db->query($sqlProductos);
	
	print '<a class="butAction" type="button" href="#addRetiredModal" rel="modal:open">Nuevo material retirado</a>';
	print '
	<div id="addRetiredModal" class="modal" role="dialog" aria-labelledby="addRetiredModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<input type="hidden" name="fk_report" value="'.$id.'">
			<input type="hidden" name="is_future" value="0">
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material o servicio</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Producto a retirar</label>
							</td>
							<td>
								<select id="fk_product_retired" name="fk_product" style="width: 250px;" class="select-products">
									<option value="-1" selected disabled>Selecciona un producto</option>';

									while ($prod = $db->fetch_object($resultProductos)) {
										print '<option value="'.$prod->rowid.'">'.$prod->ref.' - '.$prod->label.'</option>';
									}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad</label>
							</td>
							<td>
								<input type="number" name="quantity">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Observaciones</label>
							</td>
							<td>
								<input type="text" name="observaciones">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addRetired" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	print '<table class="tagtable liste">';
		print "<thead>";
			print "<tr class='liste_titre'>";
				print "<th class='center liste_titre'>Código</th>";
				print "<th class='center liste_titre'>Articulo</th>";
				print "<th class='center liste_titre'>Cantidad</th>";
				print "<th class='center liste_titre'>Devuelto</th>";
				print "<th class='center liste_titre'>Observaciones</th>";
				print "<th class='center liste_titre'>Acciones</th>";
			print "</tr>";
		print "</thead>";
		print "<tbody>";
		while ($replacement = $db->fetch_object($resultReplacementsReturned)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $replacement->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->quantity . "</td> ";
				print "<td class='center' tdoverflowmax200'>No</td> ";
				print "<td class='center' tdoverflowmax200'>" . $replacement->observ . "</td>";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editRetired&id=' . $object->id . '&rowid=' . $replacement->rowid . '">' . img_edit() . '</a>
							</td>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $replacement->rowid . '">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td> ";
			print "</tr>";
		}
		print "</tbody>";
	print '</table>';

	if ($numRetirados > 0) {
		print '<div class="tabsAction" style="margin-bottom:-20px">';
		print '<a class="butAction" type="button" href="informes_equipos.php?action=crearAveria&id='.$id.'">Crear Avería con Retirados</a>';
		print '</div>';
	}

	//MATERIALES DEVUELTOS

	$sqlReplacementsDevueltos = " SELECT mis.rowid, mis.id_fase_khonos as observ, mis.quantity, p.rowid as product_id, p.ref, p.description, pr.ref as pr_ref ";
	$sqlReplacementsDevueltos.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sqlReplacementsDevueltos.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sqlReplacementsDevueltos.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sqlReplacementsDevueltos.= " WHERE mis.is_returned = 1 AND mis.fk_report =".$id;

	$resultReplacementsDevueltos = $db->query($sqlReplacementsDevueltos);
	
	print "<br>";
	print "<br>";

	print_barre_liste($langs->trans("Materiales devueltos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<table class="tagtable liste">';
		print "<thead>";
			print "<tr class='liste_titre'>";
				print "<th class='center liste_titre'>Código</th>";
				print "<th class='center liste_titre'>Articulo</th>";
				print "<th class='center liste_titre'>Cantidad</th>";
				print "<th class='center liste_titre'>Devuelto</th>";
				print "<th class='center liste_titre'>Observaciones</th>";
				print "<th class='center liste_titre'>Acciones</th>";
			print "</tr>";
		print "</thead>";
		print "<tbody>";
		while ($devuelto = $db->fetch_object($resultReplacementsDevueltos)) {

			print "<tr class='oddeven'>";
				print "<td class='center' tdoverflowmax200'>" . $devuelto->ref . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $devuelto->description . "</td> ";
				print "<td class='center' tdoverflowmax200'>" . $devuelto->quantity . "</td> ";
				print "<td class='center' tdoverflowmax200'>Si</td> ";
				print "<td class='center' tdoverflowmax200'>" . $devuelto->observ . "</td> ";
				print "<td class='center' tdoverflowmax200'>";
					print '
					<table class="center">
						<tr>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editReturned&id=' . $object->id . '&rowid=' . $devuelto->rowid . '">' . img_edit() . '</a>
							</td>
							<td>
								<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=deleteReplacement&id=' . $object->id . '&rowid=' . $devuelto->rowid . '">' . img_delete() . '</a>		
							</td>
						</tr>
					</table>
					';
				print "</td> ";
			print "</tr>";
		}
		print "</tbody>";
	print '</table>';

	print '</div>';

}


if ($action == 'editar') {

	$equipo = $_GET['rowid'];
	$id = $_GET['id'];
	$producto = $_GET['product'];

	$sql = " SELECT mie.remarks, mie.future_remarks, mie.advance, mie.location, mie.failure, mie.repairs, mie.qty_failure, mie.qty_repairs, p.ref, p.description ";
	$sql.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mie.fk_product ";
	$sql.= " WHERE mie.rowid = " . $equipo . "";
	
	$resul = $db->query($sql);
	$equipoF = $db->fetch_object($resul);

	$piezas = $equipoF->failure;
	$reparaciones = $equipoF->repairs;

	$sqlProducts = "WITH RECURSIVE product_hierarchy AS (
		SELECT p.rowid, p.ref, p.label, p.description, 1 as level
		FROM ".MAIN_DB_PREFIX."product p
		INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ON mie.fk_product = p.rowid
		INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mie.fk_report
		WHERE mi.rowid = ".$id."
		UNION ALL
		SELECT p.rowid, p.ref, p.label, p.description, ph.level + 1 as level
		FROM product_hierarchy ph
		INNER JOIN ".MAIN_DB_PREFIX."bom_bom bb ON ph.rowid = bb.fk_product
		INNER JOIN ".MAIN_DB_PREFIX."bom_bomline bbl ON bb.rowid = bbl.fk_bom
		INNER JOIN ".MAIN_DB_PREFIX."product p ON bbl.fk_product = p.rowid
		WHERE ph.level < 4 
	)
	SELECT rowid, ref, label, description
	FROM (
		SELECT ph.*, ROW_NUMBER() OVER (PARTITION BY ref ORDER BY rowid) as row_num
		FROM product_hierarchy ph
	) as numbered_rows
	WHERE row_num = 1";

	$resultProducts = $db->query($sqlProducts);

	$sqlServices = " SELECT rowid, ref, label ";
	$sqlServices.= " FROM ".MAIN_DB_PREFIX."product ";
	$sqlServices.= " WHERE fk_product_type = 1";

	$resultServices = $db->query($sqlServices);

	if ($piezas == "") {
		$piezas = -1;
	} else {
		$sqlPiezas = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";
		$sqlPiezas.= " WHERE rowid IN (".$equipoF->failure.") ORDER BY FIELD (rowid, ".$equipoF->failure.")";

		$resultado = $db->query($sqlPiezas);
		$resultado = $db->fetch_object($resultado);
		$piezas = $resultado->rowid;
	}

	if ($reparaciones == "") {
		$reparaciones = -1;
	} else {
		$sqlReparaciones = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";
		$sqlReparaciones.= " WHERE rowid IN (".$equipoF->repairs.") ORDER BY FIELD (rowid, ".$equipoF->repairs.")";

		$resultado = $db->query($sqlReparaciones);
		$resultado = $db->fetch_object($resultado);
		$reparaciones = $resultado->rowid;
	}

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&product='.$producto.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Equipo</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 460.928px;" class="ui-dialog-content ui-widget-content">
				
				<div>
					<input type="hidden" value="'.$equipo.'" name="rowid">
					<table>
						<tr>
							<td>
								<label class="field">Equipo: '.$equipoF->ref.' - '.$equipoF->description.'</label>
							</td>
						</tr>
						<tr>
							<td>
								<span>Observaciones</span>
							</td>
							<td>
								<textarea name="observ" rows=3 cols=30>' . $equipoF->remarks . '</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<span>Observaciones sig visita</span>
							</td>
							<td>
								<textarea name="observ_sig" rows=3 cols=30>' . $equipoF->future_remarks . '</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<span>Ubicación</span>
							</td>
							<td>
								<input name="location" type="text" value="' . $equipoF->location . '">
							</td>
						</tr>
						<tr>
							<td>
								<span>Avance</span>
							</td>
							<td>
								<select class="select-avanc" name="avanc">';

							if ($equipoF->advance == 0) {

								print ' <option selected value="0">No</option>';
							} else {

								print ' <option value="0">No</option>';
							}

							if ($equipoF->advance == 1) {

								print ' <option selected value="1">Si</option>';
							} else {

								print ' <option value="1">Si</option>';
							}

							print '

								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span>Piezas</span>
							</td>
							<td>
								<select class="select-piezas" name="piezas">
								<option value=-1>&nbsp;</option>';
								
								while ($productF = $db->fetch_object($resultProducts)) {

									print ' <option value="'.$productF->rowid.'">'.$productF->ref.' - '.$productF->label.'</option>';

								}

							print '

								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad Piezas</label>
							</td>
							<td>
								<input type="number" name="cant_piezas">
							</td>
						</tr>
						<tr>
							<td>
								<span>Tipo de Pieza</span>
							</td>
							<td>
								<select class="select-tipoPieza" name="tipo_pieza">
									<option value="0" selected>Actual</option>
									<option value="1">Futura</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span>Reparaciones</span>
							</td>
							<td>
								<select class="select-reparaciones" name="reparaciones">

								<option value=-1>&nbsp;</option>';
								
								while ($serviceF = $db->fetch_object($resultServices)) {

									print ' <option value="'.$serviceF->rowid.'">'.$serviceF->ref.' - '.$serviceF->label.'</option>';

								}

							print '

								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad Reparaciones</label>
							</td>
							<td>
								<input type="number" name="cant_reparaciones">
							</td>
						</tr>
						<tr>
							<td>
								<span>Tipo de Reparación</span>
							</td>
							<td>
								<select class="select-tipoReparacion" name="tipo_reparacion">
									<option value="0" selected>Actual</option>
									<option value="1">Futura</option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editEquipment">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}elseif($action == 'editReplacement'){

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sql = " SELECT mis.quantity, mis.fk_product_root, mis.fk_product, ";
	$sql.= " pr.ref as pr_ref, pr.description as pr_desc, p.ref as p_ref, p.description as p_desc, p.rowid ";
	$sql.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sql.= " WHERE mis.rowid = " . $rowid . "";
	
	$resul = $db->query($sql);
	$equipoF = $db->fetch_object($resul);

	$sqlServices = " SELECT rowid, ref, label ";
	$sqlServices.= " FROM ".MAIN_DB_PREFIX."product ";
	$sqlServices.= " WHERE fk_product_type = 1";

	$resultServices = $db->query($sqlServices);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar equipo</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 80.928px;" class="ui-dialog-content ui-widget-content">
				
				<div>
					<input type="hidden" value="'.$rowid.'" name="rowid">
					<table>
						<tbody>
							<tr>
								<td>
									<label for="">Cantidad</label>
								</td>
								<td>
									<input type="number" name="quantity" value="'.$equipoF->quantity.'">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editReplacementEquipment">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}

if ($action == 'editRetired') {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sql = " SELECT mis.quantity, mis.id_fase_khonos as observ, mis.fk_product_root, mis.fk_product, ";
	$sql.= " p.ref as p_ref, p.description as p_desc ";
	$sql.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sql.= " WHERE mis.rowid = " . $rowid . "";
	
	$resul = $db->query($sql);
	$equipoF = $db->fetch_object($resul);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar equipo retirado</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 100.928px;" class="ui-dialog-content ui-widget-content">
				
				<div>
					<input type="hidden" value="'.$rowid.'" name="rowid">
					<table>
						<tbody>
							<tr>
								<td>
									<label for="">Cantidad</label>
								</td>
								<td>
									<input type="number" name="quantity" value="'.$equipoF->quantity.'">
								</td>
							</tr>
							<tr>
								<td>
									<label for="">Devuelto</label>
								</td>
								<td>
									<select name="devuelto" class="select-devuelto">
										<option value="0" selected>No</option>
										<option value="1">Si</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<label for="">Observaciones</label>
								</td>
								<td>
									<input type="text" name="observaciones" value="'.$equipoF->observ.'">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editRetiredEquipment">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}

if ($action == 'editReturned') {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sql = " SELECT mis.quantity, mis.fk_product_root, mis.fk_product, mis.id_fase_khonos as observ, ";
	$sql.= " p.ref as p_ref, p.description as p_desc ";
	$sql.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
	$sql.= " WHERE mis.rowid = " . $rowid . "";
	
	$resul = $db->query($sql);
	$equipoF = $db->fetch_object($resul);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar equipo devuelto</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 100.928px;" class="ui-dialog-content ui-widget-content">
				
				<div>
					<input type="hidden" value="'.$rowid.'" name="rowid">
					<table>
						<tbody>
							<tr>
								<td>
									<label for="">Cantidad</label>
								</td>
								<td>
									<input type="number" name="quantity" value="'.$equipoF->quantity.'">
								</td>
							</tr>
							<tr>
								<td>
									<label for="">Devuelto</label>
								</td>
								<td>
									<select name="devuelto" class="select-devuelto">
										<option value="0">No</option>
										<option value="1" selected>Si</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<label for="">Observaciones</label>
								</td>
								<td>
									<input type="text" name="observaciones" value="'.$equipoF->observ.'">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editReturnedEquipment">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}

if ($action == 'borrar') {

	$equipo = $_GET['rowid'];
	$id = $_GET['id'];
	$product = $_GET['product'];

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_equipos ";
	$sql .= "WHERE rowid = " . $equipo . "";

	$resul = $db->query($sql);

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_piezasreparaciones ";
	$sql .= "WHERE fk_product_root = " . $product . " AND fk_report = ".$id;

	$resul = $db->query($sql);

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_sustituciones ";
	$sql .= "WHERE fk_product_root = " . $product . " AND fk_report = ".$id;

	$resul = $db->query($sql);

	$destination_url = 'informes_equipos.php?id=' . $id . '';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}elseif($action == 'deleteReplacement'){

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];
	$product = $_GET['product'];

	//BORRAMOS AHORA DE EQUIPOS
	//Cogemos el product_root de la sustitucion, que es el producto del equipo
	$sqlRoot = " SELECT fk_product_root FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_sustituciones ";
	$sqlRoot.= " WHERE rowid = ".$rowid;

	$resultRoot = $db->query($sqlRoot);
	$root = $db->fetch_object($resultRoot);
	$root = $root->fk_product_root;

	//Seleccionamos el equipo de ese producto
	$sqlEquipo = " SELECT * FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_equipos ";
	$sqlEquipo.= " WHERE fk_report = ".$id." AND fk_product = ".$root;

	$resultEquipo = $db->query($sqlEquipo);
	$equipo = $db->fetch_object($resultEquipo);

	//Sacamos ahora el id de todos los productos/servicios, para comprobar de qué tipo es
	$esProducto = false;

	$sqlTotal = " SELECT rowid FROM " . MAIN_DB_PREFIX . "product p ";
	$sqlTotal.= " WHERE fk_product_type = 0 ";

	$resultTotal = $db->query($sqlTotal);

	while ($pro = $db->fetch_object($resultTotal)) {

		if ($product == $pro->rowid) {

			$esProducto = true;

		}

	}

	//Si es un producto
	if ($esProducto) {

		$productos = explode (", ", $equipo->failure);
		$cantProductos = explode (", ", $equipo->qty_failure);

		print_r($productos);
		print_r($cantProductos);

		for ($i = 0; $i < count($productos); $i++) {
			if ($productos[$i] == $product) {
				unset($productos[$i]);
				unset($cantProductos[$i]);
			}
		}

		$productosFinal = implode (", ", $productos);
		$cantProductosFinal = implode (", ", $cantProductos);

		$sqlUpdate = " UPDATE " . MAIN_DB_PREFIX . "mantenimiento_informes_equipos ";

		if ($productosFinal == "") {
			$productosFinal = "NULL";
			$sqlUpdate.= " SET failure = ".$productosFinal.", ";
		} else {
			$sqlUpdate.= " SET failure = '".$productosFinal."', ";
		}

		if ($cantProductosFinal == "") {
			$cantProductosFinal = "NULL";
			$sqlUpdate.= " qty_failure = ".$cantProductosFinal." ";
		} else {
			$sqlUpdate.= " qty_failure = '".$cantProductosFinal."' ";
		}

		$sqlUpdate.= " WHERE fk_report = ".$id." AND fk_product = ".$root;

		//print $sqlUpdate;
		//die;
		$db->query($sqlUpdate);

		$sql = " DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_sustituciones";
		$sql.= " WHERE rowid = ".$rowid;
	
		$resul = $db->query($sql);
	
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_piezasreparaciones ";
		$sql .= "WHERE rowid = " . $rowid . "";
	
		$resul = $db->query($sql);

	//Si es un servicio
	} else {

		$servicios = explode (", ", $equipo->repairs);
		$cantServicios = explode (", ", $equipo->qty_repairs);

		print_r($productos);
		print_r($cantProductos);

		for ($i = 0; $i < count($servicios); $i++) {
			if ($servicios[$i] == $product) {
				unset($servicios[$i]);
				unset($cantServicios[$i]);
			}
		}

		$serviciosFinal = implode (", ", $servicios);
		$cantServiciosFinal = implode (", ", $cantServicios);

		$sqlUpdate = " UPDATE " . MAIN_DB_PREFIX . "mantenimiento_informes_equipos ";

		if ($serviciosFinal == "") {
			$serviciosFinal = "NULL";
			$sqlUpdate.= " SET repairs = ".$serviciosFinal.", ";
		} else {
			$sqlUpdate.= " SET repairs = '".$serviciosFinal."', ";
		}

		if ($cantServiciosFinal == "") {
			$cantServiciosFinal = "NULL";
			$sqlUpdate.= " qty_repairs = ".$cantServiciosFinal." ";
		} else {
			$sqlUpdate.= " qty_repairs = '".$cantServiciosFinal."' ";
		}

		$sqlUpdate.= " WHERE fk_report = ".$id." AND fk_product = ".$root;

		//print $sqlUpdate;
		//die;
		$db->query($sqlUpdate);

		$sql = " DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_sustituciones";
		$sql.= " WHERE rowid = ".$rowid;
	
		$resul = $db->query($sql);
	
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_informes_piezasreparaciones ";
		$sql .= "WHERE rowid = " . $rowid . "";
	
		$resul = $db->query($sql);

	}

	$destination_url = 'informes_equipos.php?id=' . $id . '';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}elseif($action == 'showMaterials'){

	$id = $_GET["id"];
	$fk_product = $_GET["fk_product"];

	$sqlEquipment = " SELECT p.label, p.ref, p.description, FORMAT(bbl.qty,2) as qty  FROM " . MAIN_DB_PREFIX . "bom_bom bb ";
	$sqlEquipment.= " INNER JOIN ".MAIN_DB_PREFIX."bom_bomline bbl ON  bbl.fk_bom = bb.rowid ";
	$sqlEquipment.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = bbl.fk_product ";
	$sqlEquipment.= " WHERE bb.fk_product = ".$fk_product;
	
	$resultEquipment = $db->query($sqlEquipment);

	if ($resultEquipment) {
		
		print '
		<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
			<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 700px; top: 230.503px; left: 600.62px; z-index: 101;">
				<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
					<span id="ui-id-1" class="ui-dialog-title">Listado materiales</span>
					<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
						<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
						<span class="ui-button-icon-space"> </span>
						Close
					</button>
				</div>
				<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 260.928px;" class="ui-dialog-content ui-widget-content">
					
					<div>
						<table id="materialsTable" class="tagtable liste">
							<thead>
								<tr>
									<th>Etiqueta</th>
									<th>Referencia</th>
									<th>Descripción</th>
									<th>Cantidad</th>
								</tr>
							</thead>
							<tbody>';
							while ($line = $db->fetch_object($resultEquipment)) {
								print '<tr>';
									print '<td>'.$line->label.'</td>';
									print '<td>'.$line->ref.'</td>';
									print '<td>'.$line->description.'</td>';
									print '<td>'.$line->qty.'</td>';
								print '</tr>';
							}
							print '
							</tbody>
						</table>
					</div>
				</div>
				<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
					<div class="ui-dialog-buttonset">
						<button type="submit" class="ui-button ui-corner-all ui-widget">
							Salir
						</button>
					</div>
				</div>
			</div>
		</form>';			
		
	}

}


if ($action == 'crearAveria') {

	$id = $_GET['id'];
	
	$averia = new Averias($db);

	$averia->fecha_averia = date('Y-m-d H:i:s', dol_now());

	$consultaIdRef = " SELECT rowid FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias a ";
	$consultaIdRef.= " ORDER BY rowid DESC LIMIT 1 ";

	$resultIdRef = $db->query($consultaIdRef);
	$IdRef = $db->fetch_object($resultIdRef);
	$idRef = $IdRef->rowid;
	$idRef++;

	$averia->ref = "(PROV_RETIRADOS".$idRef.")";

	$sqlContrato = " SELECT mc.client_id, mc.project_id FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
	$sqlContrato.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.contract_id = mc.rowid ";
	$sqlContrato.= " WHERE mi.rowid = ".$id;

	$resultContrato = $db->query($sqlContrato);
	$contrato = $db->fetch_object($resultContrato);
	$cliente = $contrato->client_id;

	$sqlCliente = " SELECT s.nom, s.siren, s.address, s.zip, s.town, s.phone, s.fax, s.email, d.nom as provincia, de.id FROM ".MAIN_DB_PREFIX."societe s ";
	$sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."c_departements d ON d.rowid = s.fk_departement ";
	$sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."delegacion de ON de.fk_tercero = s.rowid ";
	$sqlCliente.= " WHERE s.rowid = ".$cliente." LIMIT 1";

	$resultCliente = $db->query($sqlCliente);
	$clienteDatos = $db->fetch_object($resultCliente);

	$proyecto = $contrato->project_id;

	$averia->fk_cliente = $cliente;
	$averia->descripcion = "Avería creada automáticamente";
	$averia->fk_delegacion = $clienteDatos->id;
	$averia->razon_social = $clienteDatos->nom;
	$averia->cif = $clienteDatos->siren;
	$averia->direccion = $clienteDatos->address;
	$averia->poblacion = $clienteDatos->town;
	$averia->codigo_postal = $clienteDatos->zip;
	$averia->provincia = $clienteDatos->provincia;
	$averia->contacto = $cliente;
	$averia->telefono = $clienteDatos->phone;
	$averia->email = $clienteDatos->email;
	$averia->fax = $clienteDatos->fax;
	$averia->fk_project = $proyecto;
	$averia->fk_informe = $id;
	$averia->direccion_envio = "Direccion Temporal";
	$averia->estado_averia = 0;
	$averia->control = 1;
	$averia->status = 0;

	$resultadoCrear = $averia->create($user);

	if ($resultadoCrear != -1) {

		//Cogemos todos los retirados
		$sqlRetirados = " SELECT mis.rowid, mis.quantity, p.rowid as product_id, p.ref, p.description, p.label, pr.ref as pr_ref ";
		$sqlRetirados.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
		$sqlRetirados.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
		$sqlRetirados.= " LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root ";
		$sqlRetirados.= " WHERE mis.is_retired = 1 AND mis.is_returned = 0 AND mis.fk_report =".$id;
	
		$resultRetirados = $db->query($sqlRetirados);

		while ($retirado = $db->fetch_object($resultRetirados)) {

			$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ";
			$sqlInsert.= " (fk_averia, codigo_padre, codigo, label, qty, ot, sc) ";
			$sqlInsert.= " VALUES ";
			$sqlInsert.= " ($resultadoCrear, 0, $retirado->product_id, '".$retirado->label."', $retirado->quantity, 0, 0) ";

			$db->query($sqlInsert);

		}

	}



}




print  '
<script>

	$(".select-avanc").select2();
	$(".select-advance").select2();

	$(".select-products").select2({
        width: "100%" // Esto hará que el campo de selección ocupe todo el ancho disponible
    });

	$(".select-avanc").select2();
	$(".select-devuelto").select2();
	
	$(".select-piezas").select2({
        width: "100%" // Esto hará que el campo de selección ocupe todo el ancho disponible
    });

	$(".select-reparaciones").select2({
        width: "100%" // Esto hará que el campo de selección ocupe todo el ancho disponible
    });

	$(".select-tipoPieza").select2();
	$(".select-tipoReparacion").select2();

	jQuery(document).ready(function () {

		let table = new DataTable("#materialsTable");
		
	})


	function toggleTable(id){

		if($("#btn_toggle_"+id).text() == "-"){

			$("#btn_toggle_"+id).text("+");
		}else{
			$("#btn_toggle_"+id).text("-");
		}

		$(".table_row_"+id).toggle();
	}

	function getProductById(idProduct,idPrice,idMaterials){
		
		let url = "projectController.php?id="+idProduct.value;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResult(data,idPrice,idMaterials));
	}

	function printResult(response,idPrice,idMaterials){
		
		if(response["status"] == 200){
			document.getElementById(idPrice).value = response["priceProduct"];
			document.getElementById(idMaterials).value = response["priceMaterials"];
		} 
	}

	//Create

	$("#fk_product_root").on("input", function() {

		let id = $("#fk_product_root").val();
		
		let url = "findProductComponents.php?id="+id;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResultComponents(data));

	});

	function printResultComponents(response){
		
		if(response["status"] == 200){

			var parent = $("#fk_product_components");

			parent.empty();

			let option1 = document.createElement("option");
			option1.value = -1;
			option1.textContent = "Selecciona una opción";
			parent.append(option1);

			response.data.forEach(product => {
				let option = document.createElement("option");
				option.value = product.rowid;
				option.textContent = product.ref+ " - "+ product.description;

				parent.append(option);

			});
			
		} 
	}

	//Edit replacement

	$("#fk_product_root_replacement_edit").on("input", function() {

		let id = $("#fk_product_root_replacement_edit").val();
		
		let url = "findProductComponents.php?id="+id;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResultComponentsEdit(data));

	});

	function printResultComponentsEdit(response){
		
		if(response["status"] == 200){

			var parent = $("#fk_product_replacement_edit");

			parent.empty();

			let option1 = document.createElement("option");
			option1.value = -1;
			option1.textContent = "Selecciona una opción";
			parent.append(option1);

			response.data.forEach(product => {
				let option = document.createElement("option");
				option.value = product.rowid;
				option.textContent = product.ref+ " - "+ product.description;

				parent.append(option);

			});
			
		} 
	}

	//Add replacement future

	$("#fk_product_root_future").on("input", function() {

		let id = $("#fk_product_root_future").val();
		
		let url = "findProductComponents.php?id="+id;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResultComponentsFuture(data));

	});

	function printResultComponentsFuture(response){
		
		if(response["status"] == 200){

			var parent = $("#fk_product_components_future");

			parent.empty();

			let option1 = document.createElement("option");
			option1.value = -1;
			option1.textContent = "Selecciona una opción";
			parent.append(option1);

			response.data.forEach(product => {
				let option = document.createElement("option");
				option.value = product.rowid;
				option.textContent = product.ref+ " - "+ product.description;

				parent.append(option);

			});
			
		} 
	}

	// Add retired

	$("#fk_product_root_retired").on("input", function() {

		let id = $("#fk_product_root_retired").val();
		
		let url = "findProductComponents.php?id="+id;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResultComponents2(data));

	});

	function printResultComponents2(response){
		
		if(response["status"] == 200){

			var parent = $("#fk_product_components_retired");

			parent.empty();

			let option1 = document.createElement("option");
			option1.value = -1;
			option1.textContent = "Selecciona una opción";
			parent.append(option1);

			response.data.forEach(product => {
				let option = document.createElement("option");
				option.value = product.rowid;
				option.textContent = product.ref+ " - "+ product.description;

				parent.append(option);

			});
			
		} 
	}

</script>';

//Modals
print '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />';
//Datatables
print '<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>';
print '<script src="https://code.jquery.com/jquery-3.7.0.js"></script>';
print '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />';


// End of page
llxFooter();
$db->close();
