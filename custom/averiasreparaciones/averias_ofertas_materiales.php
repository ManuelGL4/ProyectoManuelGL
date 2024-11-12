<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       averias_ofertas_agenda.php
 *  \ingroup    averiasreparaciones
 *  \brief      Tab of events on Averias_ofertas
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
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
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

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/averiasreparaciones/class/averias_ofertas.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias_ofertas.lib.php');


// Load translation files required by the page
$langs->loadLangs(array("averiasreparaciones@averiasreparaciones", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$consulta = " SELECT rowid, averia FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
$consulta.= " WHERE rowid = ".$id;

$resultConsulta = $db->query($consulta);
$idAveria = $db->fetch_object($resultConsulta);
$idAveria = $idAveria->averia;

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Initialize technical objects
$object = new Averias_ofertas($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->averiasreparaciones->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('averias_ofertasagenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->averiasreparaciones->multidir_output[$object->entity]."/".$object->id;
}

$permissiontoadd = $user->rights->averiasreparaciones->averias_ofertas->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->averiasreparaciones->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();




/*
 *  Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode = '';
		$search_agenda_label = '';
	}
}

if (isset($_POST['addMaterial'])) {

    $averia = $idAveria;

    $materialId = $_POST['material'];

    $consulta = " SELECT ae.*, p.ref, p.description, p.price_ttc FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ae";
    $consulta.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ae.codigo "; 
    $consulta.= " WHERE ae.rowid = ".$materialId;

    $resultConsulta = $db->query($consulta);
    $material = $db->fetch_object($resultConsulta);

    $consultaDtos = " SELECT rowid, dto_cliente, dto_oferta, dto_pp FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
    $consultaDtos.= " WHERE rowid = ".$id;

    $resultConsultaDtos = $db->query($consultaDtos);
    $dtos = $db->fetch_object($resultConsultaDtos);

    $descuentoTotal = 0;

    if ($dtos->dto_cliente != "") {
        $descuentoTotal+= $dtos->dto_cliente;
    }
    if ($dtos->dto_oferta != "") {
        $descuentoTotal+= $dtos->dto_oferta;
    }
    if ($dtos->dto_pp != "") {
        $descuentoTotal+= $dtos->dto_pp;
    }

    $descuentoNum = (($material->qty * $material->price_ttc) * $descuentoTotal) / 100;
    $baseImponible = ($material->qty * $material->price_ttc) - $descuentoNum;

    $sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_materiales ";
    $sqlInsert.= " (fk_averia, fk_equipo, fk_oferta, codigo, articulo, descripcion, unidades, precio, dto, base_imponible) ";
    $sqlInsert.= " VALUES ";
    $sqlInsert.= " ($averia, $materialId, $id, $material->codigo, '".$material->label."', '".$material->description."', $material->qty, $material->price_ttc, $descuentoTotal, $baseImponible) ";

    $db->query($sqlInsert);

}

if (isset($_POST['editMaterial'])) {

    $rowid = $_GET['rowid'];
	$id = $_GET['id'];
    $observaciones = $_POST['observaciones'];
	$plazo = $_POST['plazo'];

    $sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
    $sqlUpdate.= " SET observaciones = '".$observaciones."', plazo_entrega = '".$plazo."' ";
    $sqlUpdate.= " WHERE rowid = ".$id;

    $db->query($sqlUpdate);

}

if (isset($_POST['addDescuento'])) {

    $rowid = $_GET['rowid'];
	$id = $_GET['id'];
    $descuento = $_POST['descuento'];

	if ($descuento == "") {
		$descuento = 0;
	}

	$sqlDatosExt = " SELECT * FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
	$sqlDatosExt.= " WHERE rowid = ".$rowid;

	$resultDatosExt = $db->query($sqlDatosExt);
	$datosExt = $db->fetch_object($resultDatosExt);

	$cantDescuento = ($datosExt->gasto_repuestos * $descuento) / 100;

	//gasto_repuestos
	$totalRepuestos = $datosExt->gasto_repuestos - $cantDescuento;

	//gasto_teorico
	$totalTeorico = $datosExt->gasto_tiempos + $totalRepuestos;

	//gasto_actual
	$totalActual = $datosExt->gasto_transporte + $datosExt->gasto_instalacion + $datosExt->gasto_otros + $totalTeorico;

    /*$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
    $sqlUpdate.= " SET gasto_repuestos = ".$totalRepuestos.", gasto_teorico = ".$totalTeorico.", gasto_actual = ".$totalActual.", dto = ".$descuento." ";
    $sqlUpdate.= " WHERE rowid = ".$rowid;*/

	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
    $sqlUpdate.= " SET dto = ".$descuento." ";
    $sqlUpdate.= " WHERE fk_oferta = ".$id." AND added = 1 ";

    $db->query($sqlUpdate);

	//CANTIDAD DE IVA
	$cantIva = ($totalActual * 21) / 100;
	$baseFinal = $totalActual + $cantIva;

	/*$sqlUpdate2 = " UPDATE ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_datos ";
    $sqlUpdate2.= " SET coste_material = COALESCE(coste_material, 0) + ".$totalTeorico.", coste_total = COALESCE(coste_total, 0) + ".$totalActual.", ";
	$sqlUpdate2.= " base_imponible = COALESCE(base_imponible, 0) + ".$totalActual.", iva = COALESCE(iva, 0) + ".$cantIva.", total = COALESCE(total, 0) + ".$baseFinal." ";
    $sqlUpdate2.= " WHERE fk_oferta = ".$id;

	print $sqlUpdate2;

	//COALESCE(actual_cost, 0);

    $db->query($sqlUpdate2);*/

}

if (isset($_POST['addDatos'])) {

    $coste_transporte = $_POST['coste_transporte'];
	$coste_instalacion = $_POST['coste_instalacion'];
	$gastos = $_POST['gastos'];
	$beneficio = $_POST['beneficio'];

	$coste_transporte_sumar = $coste_transporte;
	if ($coste_transporte == "") {
		$coste_transporte = "NULL";
		$coste_transporte_sumar = 0;
	}

	$coste_instalacion_sumar = $coste_instalacion;
	if ($coste_instalacion == "") {
		$coste_instalacion = "NULL";
		$coste_instalacion_sumar = 0;
	}

	$gastos_sumar = $gastos;
	if ($gastos == "") {
		$gastos = "NULL";
		$gastos_sumar = 0;
	}

	$beneficio_sumar = $beneficio;
	if ($beneficio == "") {
		$beneficio = "NULL";
		$beneficio_sumar = 0;
	}

	//Para sacar costes totales
	$sqlCosteTotal = " SELECT coste_material FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_datos ";
	$sqlCosteTotal.= " WHERE fk_oferta = ".$id;

	$resultCosteTotal = $db->query($sqlCosteTotal);

	$coste = $db->fetch_object($resultCosteTotal);

	//PARA EL COSTE TOTAL
	$costeTotal = $coste->coste_material + $coste_transporte_sumar + $coste_instalacion_sumar + $gastos_sumar;

	//PARA BASE IMPONIBLE (QUE ES COSTE TOTAL + BENEFICIO)
	$base_imponible = $costeTotal + $beneficio_sumar;

	//PARA LA CANTIDAD DE IVA
	$cantidad_iva = ($base_imponible * 21) / 100;

	//PARA EL TOTAL
	$total = $base_imponible + $cantidad_iva;

	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_datos ";
	$sqlUpdate.= " SET coste_transporte = ".$coste_transporte.", coste_instalacion = ".$coste_instalacion.", ";
	$sqlUpdate.= " gastos = ".$gastos.", beneficio = ".$beneficio.", coste_total = ".$costeTotal.", base_imponible = ".$base_imponible.", iva = ".$cantidad_iva.", total = ".$total." ";
	$sqlUpdate.= " WHERE fk_oferta = ".$id;

	$db->query($sqlUpdate);

}



/*
 *	View
 */
$form = new Form($db);

if ($object->id > 0) {
	$title = $langs->trans("Materiales y Operaciones");
	//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
	$help_url = 'EN:Module_Agenda_En';
	llxHeader('', $title, $help_url);

	if (!empty($conf->notification->enabled)) {
		$langs->load("mails");
	}
	$head = averias_ofertasPrepareHead($object);


	print dol_get_fiche_head($head, 'materiales', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/averiasreparaciones/averias_ofertas_materiales.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	//dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '&origin='.urlencode($object->element.'@'.$object->module).'&originid='.urlencode($object->id);
	$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);
	$permok = $user->rights->agenda->myactions->create;
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode($objthirdparty->id);
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '').'&percentage=-1';
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}


	$arrayfields = array(
		'orden_trabajo' => array('label' => $langs->trans("Orden de Trabajo"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
        'precio' => array('label' => $langs->trans("Precio"), 'checked' => 1),
		'descuento' => array('label' => $langs->trans("DTO (%) A Aplicar"), 'checked' => 1),
		'base_imponible' => array('label' => $langs->trans("Base Imponible"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'orden_trabajo' => array('label' => $langs->trans("Orden de Trabajo"), 'checked' => 0),
            'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 0),
            'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
            'precio' => array('label' => $langs->trans("Precio"), 'checked' => 0),
            'descuento' => array('label' => $langs->trans("DTO (%) A Aplicar"), 'checked' => 0),
            'base_imponible' => array('label' => $langs->trans("Base Imponible"), 'checked' => 0),
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

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Materiales"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['orden_trabajo']['checked'])) {
		print "<th class='center liste_titre' title='Orden de Trabajo'>";
		print "<a class='reposition' href=''>Orden de Trabajo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['articulo']['checked'])) {
		print "<th class='center liste_titre' title='Articulo'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfields['unidades']['checked'])) {
		print "<th class='center liste_titre' title='Unidades'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

    if (!empty($arrayfields['precio']['checked'])) {
		print "<th class='center liste_titre' title='Precio'>";
		print "<a class='reposition' href=''>Precio</a>";
		print "</th>";
	}

	if ($idAveria != "") {

		if (!empty($arrayfields['descuento']['checked'])) {
			print "<th class='center liste_titre' title='DTO (%) A Aplicar'>";
			print "<a class='reposition' href=''>DTO (%) A Aplicar</a>";
			print "</th>";
		}

	}

	if (!empty($arrayfields['base_imponible']['checked'])) {
		print "<th class='center liste_titre' title='Base Imponible'>";
		print "<a class='reposition' href=''>Base Imponible</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	if ($idAveria != "") {

		$sqlDatos = " SELECT en.*, p.ref as refpro, p.label, mo.ref, mo.label as labelmo, mo.qty as qtymo ";
		$sqlDatos.= " FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo mo ON mo.rowid = en.fk_mo ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_production mop ON mop.fk_mo = mo.rowid ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mop.fk_product ";
		$sqlDatos.= " WHERE en.fk_averia = ".$idAveria." AND mop.role = 'toproduce' AND en.added = 1 ";

		$resultDatos = $db->query($sqlDatos);
		$numEquipos = $db->num_rows($resultDatos);

	} else {

		$sqlDatos = " SELECT en.*, p.ref as refpro, p.label, mo.ref, mo.label as labelmo, mo.qty as qtymo ";
		$sqlDatos.= " FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo mo ON mo.rowid = en.fk_mo ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_production mop ON mop.fk_mo = mo.rowid ";
		$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mop.fk_product ";
		$sqlDatos.= " WHERE en.fk_averia IS NULL AND mo.fk_project = ".$object->obra." AND mop.role = 'toproduce' ";

		$resultDatos = $db->query($sqlDatos);
		$numEquipos = $db->num_rows($resultDatos);

	}

	$totalCosteMaterial = 0;
	$totalCosteMaterial2 = 0;
	$totalCosteMaterial3 = 0;
	$totalCosteTransporte = 0;
	$totalCosteInstalacion = 0;
	$totalCosteOtros = 0;
	$dtocheck = true;

    while ($equipoF = $db->fetch_object($resultDatos)) {

		/*//Para sacar la OT de cada equipo
		$sqlOT = " SELECT o.rowid, o.ref FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo o ";
		$sqlOT.= " INNER JOIN ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_equipos oe ON oe.fk_order = o.rowid ";
		$sqlOT.= " INNER JOIN ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_materiales aom ON aom.codigo = oe.fk_product ";
		$sqlOT.= " WHERE o.fk_averia = ".$idAveria;

		$resultOT = $db->query($sqlOT);
		$OT = $db->fetch_object($resultOT);

		$baseImponible = "";
		if ($equipoF->price_ttc != 0) {

			if ($equipoF->dto != "") {
				$descuento = $equipoF->dto / 100;
				$adescontar = ($equipoF->price_ttc * $equipoF->unidades) * $descuento;
				$baseImponible = ($equipoF->price_ttc * $equipoF->unidades) - $adescontar;
			} else {
				$baseImponible = $equipoF->price_ttc * $equipoF->unidades;
			}

		}

		$precio = $equipoF->price;

		$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_materiales ";
		$sqlUpdate.= " SET fk_oferta = ".$id.", precio = ".$equipoF->price.", base_imponible = ".$baseImponible." ";
		$sqlUpdate.= " WHERE fk_equipo = ".$equipoF->fk_equipo." AND fk_averia = ".$idAveria;

		$db->query($sqlUpdate);*/

		print '<tr class="oddeven">';

		if (!empty($arrayfields['orden_trabajo']['checked']))
			print "<td class='center' tdoverflowmax200'><a href='../../mrp/mo_card.php?id=".$equipoF->fk_mo."'>".$equipoF->ref."</a></td>";

		if ($idAveria != "") {
			
			if (!empty($arrayfields['articulo']['checked']))
				print "<td class='center' tdoverflowmax200'>REPARACIÓN DE ".$equipoF->refpro."</td>";

		} else {

			if (!empty($arrayfields['articulo']['checked']))
			print "<td class='center' tdoverflowmax200'>PRODUCCIÓN / USO DE ".$equipoF->refpro."</td>";

		}

		if (!empty($arrayfields['descripcion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->labelmo."</td> ";

		if (!empty($arrayfields['unidades']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->qtymo."</td> ";

        if (!empty($arrayfields['precio']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($equipoF->gasto_actual,2),['.' => ',', ',' => '.'])."</td>";


		if ($idAveria != "") {

			if (!empty($arrayfields['descuento']['checked']))
				print "<td class='center' tdoverflowmax200'>".$equipoF->dto."</td> ";

				if ($equipoF->dto != "") {
					$disco = ($equipoF->gasto_repuestos * $equipoF->dto) / 100;
					$totalCosteMaterial3+= $equipoF->gasto_repuestos + $equipoF->gasto_tiempos - $disco;
				}

		}

		if (!empty($arrayfields['base_imponible']['checked']))
            print "<td class='center' tdoverflowmax200'>".strtr(number_format($equipoF->gasto_actual - $disco,2),['.' => ',', ',' => '.'])."</td> ";


		print '<td class="center">';
		print '
			<table class="center">
				<tr>
				<td>';
				
				if ($idAveria != "") {

					if ($equipoF->dto == "") {

						print '
							<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editarLinea&id=' . $id . '&rowid=' . $equipoF->rowid . '">' . img_edit() . '</a>
						';

					} 

				} else {

					print '
					<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=eliminarLinea&id=' . $id . '&rowid=' . $equipoF->rowid . '"></a>
				';

				}
					
				print '</td></tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;

		$totalCosteMaterial2+= $equipoF->gasto_teorico;
		$totalCosteMaterial+= $equipoF->gasto_actual;
		$totalCosteTransporte+= $equipoF->gasto_transporte;
		$totalCosteInstalacion+= $equipoF->gasto_instalacion;
		$totalCosteOtros+= $equipoF->gasto_otros;
		
	}

	print "</table>";

	if ($idAveria != "") {

		$sqlMateriales = " SELECT ae.rowid, ae.codigo_padre, ae.codigo, ae.label, ae.qty, p.ref as codpadre, pro.ref as codhijo FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ae";
		$sqlMateriales.= " INNER JOIN ". MAIN_DB_PREFIX ."product p ON p.rowid = ae.codigo_padre ";
		$sqlMateriales.= " INNER JOIN ". MAIN_DB_PREFIX ."product pro ON pro.rowid = ae.codigo ";
		$sqlMateriales.= " WHERE ae.fk_averia = ".$idAveria;

		$resultMateriales = $db->query($sqlMateriales);

		print '</div>';
		print '<br>';
		/*print '<div class="tabsAction">';
		print '<a class="butAction" type="button" href="#addEquipoModal" rel="modal:open">Nuevo material</a>';
		print '</div>';*/

		

		print '
		<div id="addEquipoModal" class="modal" role="dialog" aria-labelledby="addEquipoModal" aria-hidden="true">
			<form action="'.$url.'" method="POST" >
				<div class="modal-header">
					<a href="#" rel="modal:close">X</a>
					<h3 id="myModalLabel">Añadir material</h3>
				</div>
				<div class="modal-body">
					<table>
						<tbody>
							<tr>
								<label>(Código padre - Código producto - Producto)</label>
							</tr>
							<tr>
								<td>
									<label for="material">Material</label>
								</td>
								<td>
									<select name="material" class="select-material">
									<option value=-1>&nbsp</option>';

									while ($material = $db->fetch_object($resultMateriales)) {
										print '<option value='.$material->rowid.'>'.$material->codpadre.' - '.$material->codhijo.' - '.$material->label.'</option>';
									}

									print '</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<br>
				<div>
					<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
					<button type="submit" name="addMaterial" class="butAction">Añadir</button>
				</div>
			</form>
		</div>
		';
	}

	//COMPROBACION DE DESCUENTOS
	$comprDesc = " SELECT * FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
	$comprDesc.= " WHERE dto IS NOT NULL AND fk_oferta = ".$id;

	$resultCompr = $db->query($comprDesc);
	$numDesc = $db->num_rows($resultCompr);

	$hayDescuentos = false;
	$mostrarBoton = false;
	if ($numEquipos == $numDesc) {
		$hayDescuentos = true;
		$mostrarBoton = true;
	}

	if ($idAveria != "") {
		if (!$hayDescuentos && !$mostrarBoton) {
			print '<div style="display:flex; justify-content:center;color:red;font-weight:bold"><span>RELLENA LOS DESCUENTOS DE LOS MATERIALES PARA PODER MODIFICAR LOS DATOS INFERIORES</span></div>';
			print '<div style="text-align:center"><span>AÑADIR DESCUENTO</span><a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editarLinea&id=' . $id . '&rowid=1">' . img_edit() . '</a></div>';

		} else {
			//print '<div style="display:flex; justify-content:center;color:red;font-weight:bold"><span>RELLENADOS</span></div>';
		}
	}

}

print "<br>";

if ($totalCosteMaterial3 > 0) {
	$costeTotal = $totalCosteMaterial3 + $totalCosteTransporte + $totalCosteInstalacion + $totalCosteOtros;
	//$costeMaterial = $totalCosteMaterial3;
} else {
	$costeTotal = $totalCosteMaterial;
	//$costeMaterial = $totalCosteMaterial;
}

$baseImp = $costeTotal;
$ivaASumar = ($baseImp * 21) / 100;
$total = $baseImp + $ivaASumar;

$directory = "estados";
$filename = "estados/estado".$id.".txt";

if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
}

if ($idAveria != "") {

	if ($hayDescuentos && !file_exists($filename)) {
		
		//COMPROBACION
		$sqlUpdateConDescuento = " UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";

		if ($totalCosteMaterial3 > 0) {

			$sqlUpdateConDescuento.= " SET coste_material = ".$totalCosteMaterial3.", coste_total = ".$costeTotal.", base_imponible = ".$baseImp.", iva = ".$ivaASumar.", total = ".$total." ";

		} else {

			$sqlUpdateConDescuento.= " SET coste_material = ".$totalCosteMaterial2.", coste_total = ".$costeTotal.", base_imponible = ".$baseImp.", iva = ".$ivaASumar.", total = ".$total." ";

		}

		$sqlUpdateConDescuento.= " WHERE fk_oferta = ".$id;

		$db->query($sqlUpdateConDescuento);

		//print '<meta http-equiv="refresh" content="0; url="averias_ofertas_materiales.php?id='.$id.'&rowid='.$rowid.'&dto=false">';

		file_put_contents($filename, 'Ejecutado');

	}

} /*else {

	if (!file_exists($filename)) {
		
		//COMPROBACION
		$sqlUpdateConDescuento = " UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";

		if ($totalCosteMaterial3 > 0) {

			$sqlUpdateConDescuento.= " SET coste_material = ".$totalCosteMaterial3.", coste_total = ".$costeTotal.", base_imponible = ".$baseImp.", iva = ".$ivaASumar.", total = ".$total." ";

		} else {

			$sqlUpdateConDescuento.= " SET coste_material = ".$totalCosteMaterial2.", coste_total = ".$costeTotal.", base_imponible = ".$baseImp.", iva = ".$ivaASumar.", total = ".$total." ";

		}

		$sqlUpdateConDescuento.= " WHERE fk_oferta = ".$id;

		$db->query($sqlUpdateConDescuento);

		//print '<meta http-equiv="refresh" content="0; url="averias_ofertas_materiales.php?id='.$id.'&rowid='.$rowid.'&dto=false">';

		file_put_contents($filename, 'Ejecutado');

	}

}*/

$sqlComprobacion = " SELECT * FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
$sqlComprobacion.= " WHERE fk_oferta = ".$id;

$resultComprob = $db->query($sqlComprobacion);
$numDatos = $db->num_rows($resultComprob);

if (($numDatos == 0) && ($hayDescuentos)) {

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
	$sqlInsert.= " (fk_oferta, coste_material, coste_transporte, coste_instalacion, gastos, coste_total, base_imponible, iva, total) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $totalCosteMaterial3, $totalCosteTransporte, $totalCosteInstalacion, $totalCosteOtros, $costeTotal, $baseImp, $ivaASumar, $total) ";

	$db->query($sqlInsert);

	print '<script type="text/javascript">location.reload(true);</script>';

} else if ($numDatos == 0) {

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
	$sqlInsert.= " (fk_oferta, coste_material, coste_transporte, coste_instalacion, gastos, coste_total, base_imponible, iva, total) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $totalCosteMaterial2, $totalCosteTransporte, $totalCosteInstalacion, $totalCosteOtros, $costeTotal, $baseImp, $ivaASumar, $total) ";

	$db->query($sqlInsert);

	print '<script type="text/javascript">location.reload(true);</script>';

}


$ofertaDatos = $db->fetch_object($resultComprob);

print "
<div  class='tabBar tabBarWithBottom' >
<form method='POST' action='" . $_SERVER["PHP_SELF"] . "?id=" . $id . "'>
<table class='border centpercent'>
    <tbody>
        <tr>
            <td>
                <label class='field'>Coste Material:</label>
                <input readonly class='right' type='number' style='width:120px' step='0.01' name='coste_material' value='".number_format($ofertaDatos->coste_material,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field'>Coste Transporte:</label>
                <input class='right' readonly style='width:100px' type='number' step='0.01' name='codigo_delegacion' value='".number_format($ofertaDatos->coste_transporte,2)."'>
				<input type='submit' name='editarCoste' value='Editable'>";
				//<span class='fas fa-pencil-alt' style='color: #ccc !important;'></span>
            print" </td>
        </tr>
        <tr>
            <td>
                <label class='field'>Coste Instalación:</label>
                <input class='right' readonly style='width:100px' type='number' step='0.01' name='coste_instalacion' value='".number_format($ofertaDatos->coste_instalacion,2)."'>
				<input type='submit' name='editarCoste' value='Editable'>";
				//<span class='fas fa-pencil-alt' style='color: #ccc !important;'></span>
            print"
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Desarrollo:</label>
                <input readonly class='center' style='width:100px' type='text' name='coste_desarrollo' value='-'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Pruebas:</label>
                <input readonly class='center' style='width:110px' type='text' name='coste_pruebas' value='-'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Otros Gastos:</label>
                <input class='right' readonly style='width:122px' type='number' step='0.01' name='gastos'  value='".number_format($ofertaDatos->gastos,2)."'>
				<input type='submit' name='editarCoste' value='Editable'>";
				//<span class='fas fa-pencil-alt' style='color: #ccc !important;'></span>
            print"
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Total:</label>
                <input class='right' style='width:135px' readonly type='number' step='0.01' name='coste_total'  value='".number_format($ofertaDatos->coste_total,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Beneficio:</label>
                <input class='right' style='width:150px' readonly type='number' step='0.01' name='beneficio'  value='".number_format($ofertaDatos->beneficio,2)."'>
				<input type='submit' name='editarCoste' value='Editable'>";
				//<span class='fas fa-pencil-alt' style='color: #ccc !important;'></span>
            print"
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Base Imponible:</label>
                <input class='right' style='width:110px' readonly type='number' step='0.01' name='base_imponible'  value='".number_format($ofertaDatos->base_imponible,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Total:</label>
                <input class='right' style='width:180px' readonly type='number' step='0.01' name='total'  value='".number_format($ofertaDatos->total,2)."'>
            </td>
        </tr>
    </tbody>
	</table>";
	if ($idAveria != "") {
		if ($mostrarBoton) {
			print '<div class="tabsAction">';
			print '<a class="butAction" type="button" href="#editarDatos" rel="modal:open">Modificar datos</a>';
			print '</div>';
		}
	} else {
		print '<div class="tabsAction">';
		print '<a class="butAction" type="button" href="#editarDatos" rel="modal:open">Modificar datos</a>';
		print '</div>';
	}
	print"</form>
</div>
";

$sqlConsulta = " SELECT * FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
$sqlConsulta.= " WHERE fk_oferta = ".$id;

$resultConsulta = $db->query($sqlConsulta);

$datos = $db->fetch_object($resultConsulta);

print '
	<div id="editarDatos" class="modal" role="dialog" aria-labelledby="editarDatos" aria-hidden="true">
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
								<label class="field">Coste Transporte:</label>
								<input class="center" style="width:100px" type="number" step="0.01" name="coste_transporte" value="'.$datos->coste_transporte.'">
							</td>
						</tr>
						<tr>
							<td>
								<label class="field">Coste Instalacion:</label>
								<input class="center" style="width:100px" type="number" step="0.01" name="coste_instalacion" value="'.$datos->coste_instalacion.'">
							</td>
						</tr>
						<tr>
							<td>
								<label class="field">Gastos:</label>
								<input class="center" style="width:100px" type="number" step="0.01" name="gastos" value="'.$datos->gastos.'">
							</td>
						</tr>
						<tr>
							<td>
								<label class="field">Beneficios:</label>
								<input class="center" style="width:100px" type="number" step="0.01" name="beneficio" value="'.$datos->beneficio.'">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addDatos" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

    print '<div class="fichecenter">';
	//print '<div class="underbanner clearboth"></div>';

    //print dol_get_fiche_end();
    print '<br>';

	$arrayfields = array(
		'observaciones' => array('label' => $langs->trans("Observaciones"), 'checked' => 1),
		'plazo_entrega' => array('label' => $langs->trans("Plazo de Entrega"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'observaciones' => array('label' => $langs->trans("Observaciones"), 'checked' => 0),
            'plazo_entrega' => array('label' => $langs->trans("Plazo de Entrega"), 'checked' => 0),
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

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	//print_barre_liste($langs->trans("Actividades"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['observaciones']['checked'])) {
		print "<th class='center liste_titre' title='Observaciones'>";
		print "<a class='reposition' href=''>Observaciones</a>";
		print "</th>";
	}

	if (!empty($arrayfields['plazo_entrega']['checked'])) {
		print "<th class='center liste_titre' title='Plazo de Entrega'>";
		print "<a class='reposition' href=''>Plazo de Entrega</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	$sqlMaterial = " SELECT observaciones, plazo_entrega ";
	$sqlMaterial.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlMaterial.= " WHERE rowid = ".$id;
	
	$resultMaterial = $db->query($sqlMaterial);

    while ($material = $db->fetch_object($resultMaterial)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['observaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>".$material->observaciones."</td>";

		if (!empty($arrayfields['plazo_entrega']['checked']))
			print "<td class='center' tdoverflowmax200'>".$material->plazo_entrega."</td> ";


		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
                        <a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editar&id=' . $id . '">' . img_edit() . '</a>	
					</td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;
		
	}
	
print "</table>";

print '</div>';



print '</div>';

print dol_get_fiche_end();


if ($action == "editar") {

	$id = $_GET['id'];

	$sqlEquipos = " SELECT observaciones, plazo_entrega ";
	$sqlEquipos.= " FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
	$sqlEquipos.= " WHERE rowid = ".$id;

	$resultEquipos = $db->query($sqlEquipos);

	$material = $db->fetch_object($resultEquipos);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Equipo</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 200.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tr>
							<td>
								<label for="observaciones">Observaciones</label>
							</td>
							<td>
                            <textarea name="observaciones" style="width:300px;height:80px">'.$material->observaciones.'</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<label for="plazo">Plazo de Entrega</label>
							</td>
							<td>
								<textarea name="plazo" style="width:300px;height:80px">'.$material->plazo_entrega.'</textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editMaterial">
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


if ($action == "editarLinea") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sqlEquipos = " SELECT articulo ";
	$sqlEquipos.= " FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_materiales ";
	$sqlEquipos.= " WHERE rowid = ".$rowid;

	$resultEquipos = $db->query($sqlEquipos);

	$material = $db->fetch_object($resultEquipos);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Material de Oferta</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 80.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tr>
							<td>
								<span>Material: '.$material->articulo.'</span>
							</td>
						</tr>
						<tr>
							<td>
								<label for="descuento">Descuento (%)</label>
							</td>
							<td>
                            	<input type="number" name="descuento" step=1 max=100 min=0>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addDescuento">
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


if ($action == "editarCoste") {

	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Material de Oferta</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 80.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tr>
							<td>
								<span>Material: '.$material->articulo.'</span>
							</td>
						</tr>
						<tr>
							<td>
								<label for="descuento">Descuento (%)</label>
							</td>
							<td>
                            	<input type="number" name="descuento" step=1 max=100 min=0>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addDescuento">
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
