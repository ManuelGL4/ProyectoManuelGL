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
 *  \file       orden_de_trabajo_note.php
 *  \ingroup    produccion
 *  \brief      Tab for notes on Orden_de_Trabajo
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

dol_include_once('/produccion/class/orden_de_trabajo.class.php');
dol_include_once('/produccion/lib/produccion_orden_de_trabajo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("produccion@produccion", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$consulta = " SELECT fk_averia FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
$consulta.= " WHERE rowid = ".$id;

$resultConsulta = $db->query($consulta);
$idAveria = $db->fetch_object($resultConsulta);
$idAveria = $idAveria->fk_averia;

// Initialize technical objects
$object = new Orden_de_Trabajo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->produccion->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('orden_de_trabajonote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->produccion->multidir_output[$object->entity]."/".$object->id;
}

$permissionnote = $user->rights->produccion->orden_de_trabajo->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->produccion->orden_de_trabajo->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->produccion->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

if (isset($_POST['addArticuloFinal'])) {

	$material1 = $_POST['material'];

	$consulta = " SELECT codigo_padre, codigo, version, qty, indicaciones FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ";
	$consulta.= " WHERE rowid = ".$material1;

	$resultConsulta = $db->query($consulta);

	$material = $db->fetch_object($resultConsulta);

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_equipos ";
	$sqlInsert.= " (fk_order, fk_product_root, fk_product, fk_version, fk_qty, fk_indicaciones) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $material->codigo_padre, $material->codigo, '".$material->version."', $material->qty, '".$material->indicaciones."') ";

	$db->query($sqlInsert);

	//INSERTAMOS LA FASE
	$sqlPro = " SELECT p.ref, p.label, p.description FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ae ";
	$sqlPro.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ae.codigo ";
	$sqlPro.= " WHERE ae.rowid = ".$material1;
	
	$resultPro = $db->query($sqlPro);
	$prod = $db->fetch_object($resultPro);

	$lastId = " SELECT rowid FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_equipos ";
	$lastId.= " ORDER BY rowid DESC LIMIT 1 ";

	$resultId = $db->query($lastId);
	$lastId = $db->fetch_object($resultId);

	$sqlInsertFase = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlInsertFase.= " (fk_orden, fk_equipo, codigo, descripcion, articulo, unidades) ";
	$sqlInsertFase.= " VALUES ";
	$sqlInsertFase.= " ($id, $lastId->rowid, '".$prod->ref."', 'REPARACIÓN DE: ".$prod->ref."', '".$prod->label."', $material->qty) ";

	$db->query($sqlInsertFase);






	//PARA SACAR EL DESCUENTO DEL CONTRATO (SI LO HUBIESE)
	$consultaDescuentoRep = " SELECT mc.spare_parts_discount FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
	$consultaDescuentoRep.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.contract_id = mc.rowid ";
	$consultaDescuentoRep.= " INNER JOIN ".MAIN_DB_PREFIX."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
	$consultaDescuentoRep.= " WHERE a.rowid = ".$idAveria;

	$resultConsultaRep = $db->query($consultaDescuentoRep);
	$descuento = $db->fetch_object($resultConsultaRep);
	$descuento = $descuento->spare_parts_discount;

	if ($descuento == "") {
		$descuento = "NULL";
	}

	//PARA INSERTAR EN OFERTAS MATERIALES Y QUE ME APAREZCA EN LA LINEA DE LA OFERTA
	$material1; //equipo
	$idAveria; //averia
	$material->codigo; //codigo
	$prod->label; //articulo
	$prod->description; //desc
	$material->qty; //cantidad

	$sqlInsertOm = " INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_materiales ";
	$sqlInsertOm.= " (fk_averia, fk_equipo, codigo, articulo, descripcion, unidades, dto) ";
	$sqlInsertOm.= " VALUES ";
	$sqlInsertOm.= " ($idAveria, $material1, $material->codigo, '".$prod->label."', '".$prod->description."', $material->qty, ".$descuento.") ";

	$db->query($sqlInsertOm);

}

if ($action == "borrar") {

	$rowid = $_GET['rowid'];

	//Sacamos primero los datos para borrar de ofertas materiales
	$sqlDatos = " SELECT fk_product, fk_qty FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_equipos ";
	$sqlDatos.= " WHERE rowid = ".$rowid;

	$resultDatos = $db->query($sqlDatos);
	$datos = $db->fetch_object($resultDatos);

	$sqlEquipo = " SELECT rowid FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_materiales ";
	$sqlEquipo.= " WHERE fk_averia = ".$idAveria." AND codigo = ".$datos->fk_product." AND unidades = ".$datos->fk_qty." ";

	$resultEquipo = $db->query($sqlEquipo);
	$equipo = $db->fetch_object($resultEquipo);
	$equipo = $equipo->rowid;

	$sqlDelete3 = " DELETE FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_materiales ";
	$sqlDelete3.= " WHERE rowid = ".$equipo;

	$db->query($sqlDelete3);

	$sqlDelete2 = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_equipos ";
	$sqlDelete2.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete2);

	$sqlDelete3 = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlDelete3.= " WHERE fk_orden = ".$id." AND fk_equipo = ".$rowid;

	$db->query($sqlDelete3);


}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Orden_de_Trabajo'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = orden_de_trabajoPrepareHead($object);

	print dol_get_fiche_head($head, 'lineas_ot', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/produccion/orden_de_trabajo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	$arrayfieldsFases = array(
		'codigo' => array('label' => $langs->trans("codigo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("articulo"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("unidades"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsFases = array(
			'codigo' => array('label' => $langs->trans("codigo"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 0),
			'articulo' => array('label' => $langs->trans("articulo"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("unidades"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsFases[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsFases, $varpage); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	$sqlConsulta = " SELECT * FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_fases ";
	$sqlConsulta.= " WHERE fk_orden = ".$id;

	$resultConsulta = $db->query($sqlConsulta);
	$numFases = $db->num_rows($resultConsulta);

	$num = $numFases;
	$nbtotalofrecords = $num;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Fases"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsFases['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_tarea_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_trabajador_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['articulo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fecha">';
		print '</td>';
	}

	if (!empty($arrayfieldsFases['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_tiempo'>";
		print '</td>';
	}

	print "
		<td class='liste_titre middle'>
			<div class='nowrap'>
				<button type='submit' class='liste_titre button_search' name='button_search' value='x'>
					<span class='fa fa-search'></span>
				</button>
				<button type='submit' class='liste_titre button_removefilter' name='button_removefilter' value='x'>
					<span class='fa fa-remove'></span>
				</button>
			</div>
		</td>
		</tr>
		</form>
		";

	print '<tr class="liste_titre">';

	if (!empty($arrayfieldsFases['codigo']['checked'])) {
		print "<th class='center liste_titre' title='id_asoc'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='nombre'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['articulo']['checked'])) {
		print "<th class='center liste_titre' title='fecha'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['unidades']['checked'])) {
		print "<th class='center liste_titre' title='tiempo'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
			$idbusqueda = "" . $_POST['search_codigo'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$nombre = "" . $_POST['search_descripcion'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_articulo']) && ($_POST['search_articulo']) != "") {
			$apellidos = "" . $_POST['search_articulo'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
			$fecha = "'" . $_POST['search_unidades'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';

	while ($fase = $db->fetch_object($resultConsulta)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfieldsFases['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->codigo . "</td> ";

		if (!empty($arrayfieldsFases['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>".$fase->descripcion."</td> ";

		if (!empty($arrayfieldsFases['articulo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->articulo . "</td> ";

		if (!empty($arrayfieldsFases['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->unidades . "</td> ";

		//if ($user->rights->adherent->configurer) {
			//print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
		//} else {
			print '<td class="center">&nbsp;</td>';
		//}
		print "</tr>";

		//INSERTAMOS CADA FASE EN SU TABLA CORRESPONDIENTE
		
	}

	print "</table>";

	print '</form>';

	/*print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=addFase&id='.$id.'">Nueva fase</a>';
	print '</div>';*/

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsTareas = array(
		'ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
		'etiqueta' => array('label' => $langs->trans("Etiqueta"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'fecha_inicio' => array('label' => $langs->trans("Fecha de Inicio"), 'checked' => 1),
		'fecha_limite' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'tiempo_dedicado' => array('label' => $langs->trans("Tiempo Dedicado"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsTareas = array(
			'ref' => array('label' => $langs->trans("Ref"), 'checked' => 0),
			'etiqueta' => array('label' => $langs->trans("Etiqueta"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 0),
			'fecha_inicio' => array('label' => $langs->trans("Fecha de Inicio"), 'checked' => 0),
			'fecha_limite' => array('label' => $langs->trans("fecha Límite"), 'checked' => 0),
			'tiempo_dedicado' => array('label' => $langs->trans("Tiempo Dedicado"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsTareas[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsTareas, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	$sqlConsulta = " SELECT fk_task FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo ";
	$sqlConsulta.= " WHERE rowid = ".$id;

	$resultConsulta = $db->query($sqlConsulta);
	$tarea = $db->fetch_object($resultConsulta);
	$tareaID = $tarea->fk_task;

	if ($object->fk_task != "") {
		$sqlTarea = " SELECT * FROM ".MAIN_DB_PREFIX."projet_task ";
		$sqlTarea.= " WHERE rowid = ".$tareaID;
		
		$resultTarea = $db->query($sqlTarea);
		$tarea = $db->fetch_object($resultTarea);
		$num = $db->num_rows($resultTarea);
		$nbtotalofrecords = $num;
	}

		$i = 0;

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;	

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		print_barre_liste($langs->trans("Tareas"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

		//print '<div class="div-table-responsive">';
		print '<table class="tagtable liste">' . "\n";

		print "
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
		if (!empty($arrayfieldsTareas['ref']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="number" name="search_ref">';
			print '</td>';
		}
		if (!empty($arrayfieldsTareas['etiqueta']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_etiqueta">';
			print '</td>';
		}
		if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
			print '</td>';
		}
		if (!empty($arrayfieldsTareas['fecha_inicio']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_fecha_inicio">';
			print '</td>';
		}
		if (!empty($arrayfieldsTareas['fecha_limite']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_fecha_limite">';
			print '</td>';
		}
		if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_tiempo_dedicado">';
			print '</td>';
		}

		print "
			<td class='liste_titre middle'>
				<div class='nowrap'>
					<button type='submit' class='liste_titre button_search' name='button_search' value='x'>
						<span class='fa fa-search'></span>
					</button>
					<button type='submit' class='liste_titre button_removefilter' name='button_removefilter' value='x'>
						<span class='fa fa-remove'></span>
					</button>
				</div>
			</td>
			</tr>
			</form>
			";

		print '<tr class="liste_titre">';

	if (!empty($arrayfieldsTareas['ref']['checked'])) {
		print "<th class='center liste_titre' title='Ref'>";
		print "<a class='reposition' href=''>Ref</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['etiqueta']['checked'])) {
		print "<th class='center liste_titre' title='Etiqueta'>";
		print "<a class='reposition' href=''>Etiqueta</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['fecha_inicio']['checked'])) {
		print "<th class='center liste_titre' title='Fecha de Inicio'>";
		print "<a class='reposition' href=''>Fecha de Inicio</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['fecha_limite']['checked'])) {
		print "<th class='center liste_titre' title='Fecha Límite'>";
		print "<a class='reposition' href=''>Fecha Límite</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked'])) {
		print "<th class='center liste_titre' title='Tiempo Dedicado'>";
		print "<a class='reposition' href=''>Tiempo Dedicado</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_ref']) && ($_POST['search_ref']) != "") {
			$idbusqueda = "" . $_POST['search_id'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$nombre = "" . $_POST['search_nombre'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
			$apellidos = "" . $_POST['search_apellidos'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_tipo']) && ($_POST['search_tipo']) != "") {
			$nombre = "" . $_POST['search_nombre'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_coste']) && ($_POST['search_coste']) != "") {
			$apellidos = "" . $_POST['search_apellidos'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	/*$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;*/

	//while ($i < $num) {

		//$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		if (!empty($arrayfieldsTareas['ref']['checked']))	print "<td class='center' tdoverflowmax200'><a href='../../projet/tasks/task.php?id=".$tarea->rowid."'>" . $tarea->ref . "</a></td> ";

		if (!empty($arrayfieldsTareas['etiqueta']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->label . "</td> ";

		if (!empty($arrayfieldsTareas['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->description . "</td> ";

		if (!empty($arrayfieldsTareas['fecha_inicio']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->dateo . "</td> ";

		if (!empty($arrayfieldsTareas['fecha_limite']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->datee . "</td> ";

		if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked']))	print "<td class='center' tdoverflowmax200'>" . (($tarea->duration_effective / 60) / 60) . "</td> ";

		if ($user->rights->adherent->configurer) {
			//print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
			print '<td class="center">&nbsp;</td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";
		//$i++;
	//}
	print "</table>";

	print '</form>';

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsArticulos = array(
		'codigo_padre' => array('label' => $langs->trans("Código Padre"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'version' => array('label' => $langs->trans("Versión"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsArticulos = array(
			'codigo_padre' => array('label' => $langs->trans("Código Padre"), 'checked' => 0),
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 0),
			'version' => array('label' => $langs->trans("Versión"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
			'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsArticulos[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsArticulos, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	$sqlArticulos = " SELECT ote.rowid, p.ref as codpadre, p.label, p.description as descpadre, pr.ref as codhijo, pr.label, pr.description as deschijo, pr.price, ote.fk_version, ote.fk_qty, ote.fk_indicaciones FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_equipos ote";
	$sqlArticulos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ote.fk_product_root ";
	$sqlArticulos.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = ote.fk_product ";
	$sqlArticulos.= " WHERE ote.fk_order = ".$id;

	$resultArticulos = $db->query($sqlArticulos);

	$sqlArticulos2 = " SELECT ote.rowid, pr.ref as codhijo, pr.label, pr.description as deschijo, pr.price, ote.fk_version, ote.fk_qty, ote.fk_indicaciones ";
	$sqlArticulos2.= " FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_equipos ote INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = ote.fk_product ";
	$sqlArticulos2.= " WHERE ote.fk_order = ".$id." AND fk_product_root = 0";

	$resultArticulos2 = $db->query($sqlArticulos2);

	//$result = $db->query($sql);
	//if ($result) {
		$num1 = $db->num_rows($resultArticulos);
		$num2 = $db->num_rows($resultArticulos2);
		$nbtotalofrecords = $num1 + $num2;

		$i = 0;

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

		$newcardbutton = '';
		
		$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo artículo'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/don/card.php?action=create');
		

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		print_barre_liste($langs->trans("Articulos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

		//print '<div class="div-table-responsive">';
		print '<table class="tagtable liste">' . "\n";

		print "
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
		if (!empty($arrayfieldsArticulos['codigo_padre']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="number" name="search_codigo_padre">';
			print '</td>';
		}
		if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_codigo">';
			print '</td>';
		}
		if (!empty($arrayfieldsArticulos['articulo']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_articulo">';
			print '</td>';
		}
		if (!empty($arrayfieldsArticulos['version']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_version">';
			print '</td>';
		}
		if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_unidades">';
			print '</td>';
		}
		if (!empty($arrayfieldsArticulos['indicaciones']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_indicaciones">';
			print '</td>';
		}

		print "
			<td class='liste_titre middle'>
				<div class='nowrap'>
					<button type='submit' class='liste_titre button_search' name='button_search' value='x'>
						<span class='fa fa-search'></span>
					</button>
					<button type='submit' class='liste_titre button_removefilter' name='button_removefilter' value='x'>
						<span class='fa fa-remove'></span>
					</button>
				</div>
			</td>
			</tr>
			</form>
			";

		print '<tr class="liste_titre">';

		if (!empty($arrayfieldsArticulos['codigo_padre']['checked'])) {
			print "<th class='center liste_titre' title='Codigo Padre'>";
			print "<a class='reposition' href=''>Codigo Padre</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
			print "<th class='center liste_titre' title='Código'>";
			print "<a class='reposition' href=''>Código</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsArticulos['articulo']['checked'])) {
			print "<th class='center liste_titre' title='Artículo'>";
			print "<a class='reposition' href=''>Artículo</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsArticulos['version']['checked'])) {
			print "<th class='center liste_titre' title='Versión'>";
			print "<a class='reposition' href=''>Versión</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
			print "<th class='center liste_titre' title='Unidades'>";
			print "<a class='reposition' href=''>Unidades</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsArticulos['indicaciones']['checked'])) {
			print "<th class='center liste_titre' title='Indicaciones'>";
			print "<a class='reposition' href=''>Indicaciones</a>";
			print "</th>";
		}

		print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
		print "
			
			</tr>
			";

		// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
		// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
		// $sql .= " WHERE id_asoc = '$id_usuario'";

		if (isset($_POST['button_search'])) {
			$activity = array();

			if (isset($_POST['search_fase']) && ($_POST['search_fase']) != "") {
				$fase = "" . $_POST['search_fase'] . "";
				$sql .= ' and id_asoc =' . $idbusqueda;
			}

			if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
				$codigo = "" . $_POST['search_codigo'] . "";
				$sql .= ' and firstname like "%' . $nombre . '%"';
			}

			if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
				$descripcion = "" . $_POST['search_descripcion'] . "";
				$sql .= ' and lastname like "%' . $apellidos . '%"';
			}

			if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
				$unidades = "" . $_POST['search_unidades'] . "";
				$sql .= ' and firstname like "%' . $nombre . '%"';
			}

			if (isset($_POST['search_stock']) && ($_POST['search_stock']) != "") {
				$stock = "" . $_POST['search_stock'] . "";
				$sql .= ' and lastname like "%' . $apellidos . '%"';
			}

			if (isset($_POST['search_tipo']) && ($_POST['search_tipo']) != "") {
				$tipo = "" . $_POST['search_tipo'] . "";
				$sql .= ' and firstname like "%' . $nombre . '%"';
			}

			if (isset($_POST['search_coste']) && ($_POST['search_coste']) != "") {
				$coste = "" . $_POST['search_coste'] . "";
				$sql .= ' and lastname like "%' . $apellidos . '%"';
			}

		}

		print '<form method="POST" action="" name="formfilter" autocomplete="off">';

		$totalMateriales = 0;

		while ($articulo = $db->fetch_object($resultArticulos)) {

			print '<tr class="oddeven">';

			if (!empty($arrayfieldsArticulos['codigo_padre']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->codpadre . "</td> ";

			if (!empty($arrayfieldsArticulos['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->codhijo . "</td> ";

			if (!empty($arrayfieldsArticulos['articulo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->deschijo . "</td> ";

			if (!empty($arrayfieldsArticulos['version']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->fk_version . "</td> ";

			if (!empty($arrayfieldsArticulos['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->fk_qty . "</td> ";

			if (!empty($arrayfieldsArticulos['indicaciones']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo->fk_indicaciones . "</td> ";

			if ($user->rights->adherent->configurer) {
				print '<td class="center"><a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=borrar&id='.$id.'&rowid=' . $articulo->rowid . '">' . img_delete() . '</a></td>';
			} else {
				print '<td class="center">&nbsp;</td>';
			}
			print "</tr>";

			$totalMateriales+= $articulo->price * $articulo->fk_qty;

		}

		while ($articulo2 = $db->fetch_object($resultArticulos2)) {

			print '<tr class="oddeven">';

			if (!empty($arrayfieldsArticulos['codigo_padre']['checked']))	print "<td class='center' tdoverflowmax200'></td> ";

			if (!empty($arrayfieldsArticulos['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo2->codhijo . "</td> ";

			if (!empty($arrayfieldsArticulos['articulo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo2->deschijo . "</td> ";

			if (!empty($arrayfieldsArticulos['version']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo2->fk_version . "</td> ";

			if (!empty($arrayfieldsArticulos['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo2->fk_qty . "</td> ";

			if (!empty($arrayfieldsArticulos['indicaciones']['checked']))	print "<td class='center' tdoverflowmax200'>" . $articulo2->fk_indicaciones . "</td> ";

			if ($user->rights->adherent->configurer) {
				print '<td class="center"><a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=borrar&id='.$id.'&rowid=' . $articulo2->rowid . '">' . img_delete() . '</a></td>';
			} else {
				print '<td class="center">&nbsp;</td>';
			}
			print "</tr>";

			$totalMateriales+= $articulo2->price * $articulo2->fk_qty;

		}
		print "</table>";

		print '</form>';

		print '<div class="tabsAction">';
		print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=addArticulo&id='.$id.'">Nuevo articulo</a>';
		print '</div>';
		print '</div>';

		//APLICAMOS EL DESCUENTO DEL CONTRATO (SI LO HUBIERA)
		if ($object->fk_averia != "") {

			//Comprobamos que tenga informe y contrato la avería
			$sqlInforme = " SELECT fk_informe FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias ";
			$sqlInforme.= " WHERE rowid = ".$object->fk_averia;

			$resultInforme = $db->query($sqlInforme);
			$informe = $db->fetch_object($resultInforme);
			$informe = $informe->fk_informe;

			//Si hay contrato, sacamos su descuento
			if ($informe != "") {

				$sqlDescuento = " SELECT mc.spare_parts_discount FROM ". MAIN_DB_PREFIX ."mantenimiento_contratos mc ";
				$sqlDescuento.= " INNER JOIN ". MAIN_DB_PREFIX ."mantenimiento_informes mi ON mi.contract_id = mc.rowid ";
				$sqlDescuento.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
				$sqlDescuento.= " INNER JOIN ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo o ON o.fk_averia = a.rowid ";
				$sqlDescuento.= " WHERE a.rowid = ".$object->fk_averia;

				$resultDescuento = $db->query($sqlDescuento);
				$descuento = $db->fetch_object($resultDescuento);
				$descuento = $descuento->spare_parts_discount;

				//Si el descuento no es vacío y es mayor que 0, lo aplicamos
				if (($descuento != "") && ($descuento > 0)) {

					$aDescontar = ($totalMateriales * $descuento) / 100;
					$totalMateriales = $totalMateriales - $aDescontar;

				}

			}

		}


		if ($object->fk_task != "") {

			$totalTiempos = 0;
				
			$sqlTiempos = " SELECT task_duration, thm FROM ". MAIN_DB_PREFIX ."projet_task_time ";
			$sqlTiempos.= " WHERE fk_task = ".$object->fk_task;

			$resultTiempos = $db->query($sqlTiempos);

			while ($tiempos = $db->fetch_object($resultTiempos)) {

				$totalTiempos+= (($tiempos->task_duration / 60) / 60) * number_format($tiempos->thm,2);

			}

			$total = $totalMateriales + $totalTiempos;

			$sqlUpdateTeorico = " UPDATE ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
			$sqlUpdateTeorico.= " SET teoric_cost = ".$total;
			$sqlUpdateTeorico.= " WHERE rowid = ".$id;

			$db->query($sqlUpdateTeorico);

			$sqlCoste = " SELECT teoric_cost, actual_cost FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
			$sqlCoste.= " WHERE rowid = ".$id;

			$resultCoste = $db->query($sqlCoste);
			$costeTeorico = $db->fetch_object($resultCoste);

			$totalFinal = $costeTeorico->teoric_cost + $costeTeorico->actual_cost;

		}

		//print '<meta http-equiv="refresh" content="0; url="orden_de_trabajo_lineas_ot.php?id='.$id.'">';

		print "
		<div  class='tabBar tabBarWithBottom' >
		<table class='border centpercent'>
			<tbody>
				<tr>
					<td>
						<label class='field' >Dto. Repuestos (del Contrato):</label>
						<input class='center' style='width:80px' readonly type='text' step='0.01' value='".$descuento." %'>
					</td>
				</tr>
				<tr>
					<td>
						<label class='fieldrequired' >Costes:</label>
					</td>
				</tr>
				<tr>
					<td>
						<label class='field' >Teórico:</label>
						<input class='center' style='width:80px' readonly type='text' step='0.01' value='".$costeTeorico->teoric_cost."'>
					</td>
				</tr>
				<tr>
					<td>
						<label class='field' >Actual:</label>
						<input class='center' style='width:85px' readonly type='text' step='0.01' value='".$totalFinal."'>
					</td>
				</tr>
			</tbody>
		</table>";

}

if ($action == "addArticulo") {

	$id = $_GET['id'];

	$sqlMateriales = " SELECT ae.rowid, ae.codigo_padre, ae.codigo, ae.label, ae.qty, p.ref as codpadre, pro.ref as codhijo FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ae";
	$sqlMateriales.= " INNER JOIN ". MAIN_DB_PREFIX ."product p ON p.rowid = ae.codigo_padre ";
	$sqlMateriales.= " INNER JOIN ". MAIN_DB_PREFIX ."product pro ON pro.rowid = ae.codigo ";
	$sqlMateriales.= " WHERE ae.fk_averia = ".$idAveria;

	$resultMateriales = $db->query($sqlMateriales);
	
	$sqlMateriales2.= " SELECT ae.rowid, ae.codigo_padre, ae.codigo, ae.label, ae.qty, pro.ref as codhijo FROM khns_averiasreparaciones_averias_equipos ae ";
	$sqlMateriales2.= " INNER JOIN khns_product pro ON pro.rowid = ae.codigo ";
	$sqlMateriales2.= " WHERE ae.fk_averia = ".$idAveria." AND codigo_padre = 0";

	$resultMateriales2 = $db->query($sqlMateriales2);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir artículo</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 200.928px;" class="ui-dialog-content ui-widget-content">
				<div>
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

									while ($material2 = $db->fetch_object($resultMateriales2)) {
										print '<option value='.$material2->rowid.'>( ) - '.$material2->codhijo.' - '.$material2->label.'</option>';
									}

									print '</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addArticuloFinal">
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

print  '
<script>

    
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
