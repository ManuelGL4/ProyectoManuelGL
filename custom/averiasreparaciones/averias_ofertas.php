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
 *  \file       averias_seguimientos_note.php
 *  \ingroup    averiasreparaciones
 *  \brief      Tab for notes on Averias_seguimientos
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

dol_include_once('/averiasreparaciones/class/averias_seguimientos.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias_seguimientos.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/averiasreparaciones/class/averias.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("averiasreparaciones@averiasreparaciones", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$url = $_SERVER["PHP_SELF"]."?id=".$id; 

// Initialize technical objects
$object = new Averias($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->averiasreparaciones->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('averias_seguimientosnote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->averiasreparaciones->multidir_output[$object->entity]."/".$object->id;
}

$permissionnote = $user->rights->averiasreparaciones->averias_ofertas->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->averiasreparaciones->averias_ofertas->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->averiasreparaciones->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

if (isset($_POST['addLineaFinal'])) {

    extract($_POST);

	/*$consultaEquipo = " SELECT ae.codigo, ae.label, ae.qty, p.description FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ae ";
	$consultaEquipo.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ae.codigo ";
	$consultaEquipo.= " WHERE ae.rowid = ".$linea;

	$resultConsulta = $db->query($consultaEquipo);
	$equipo = $db->fetch_object($resultConsulta);

	//PARA SACAR EL DESCUENTO DEL CONTRATO (SI LO HUBIESE)
	$consultaDescuentoRep = " SELECT mc.spare_parts_discount FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
	$consultaDescuentoRep.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.contract_id = mc.rowid ";
	$consultaDescuentoRep.= " INNER JOIN ".MAIN_DB_PREFIX."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
	$consultaDescuentoRep.= " WHERE a.rowid = ".$id;

	$resultConsultaRep = $db->query($consultaDescuentoRep);
	$descuento = $db->fetch_object($resultConsultaRep);
	$descuento = $descuento->spare_parts_discount;

	if ($descuento == "") {
		$descuento = "NULL";
	}

    $sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_materiales ";
    $sqlInsert.= " (fk_averia, fK_equipo, codigo, articulo, descripcion, unidades, dto) ";
    $sqlInsert.= " VALUES ";
    $sqlInsert.= " ($id, $linea, $equipo->codigo, '".$equipo->label."', '".$equipo->description."', $equipo->qty, $descuento) ";

    $db->query($sqlInsert);*/

	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
	$sqlUpdate.= " SET added = 1 ";
	$sqlUpdate.= " WHERE fk_mo = ".$linea;

	$db->query($sqlUpdate);

}

if (isset($_POST['deleteLinea'])) {

    extract($_POST);

	$rowid = $_GET['rowid'];

	$sqlDelete = " UPDATE ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
	$sqlDelete.= " SET added = NULL ";
	$sqlDelete.= " WHERE fk_mo = ".$rowid;

	$db->query($sqlDelete);

}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Averias_ofertas'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = averiasPrepareHead($object);
	print dol_get_fiche_head($head, 'lineas oferta', $langs->trans("Workstation"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/averiasreparaciones/averias_card.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled))
	 {
	 $langs->load("projects");
	 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd)
	 {
	 if ($action != 'classify')
	 //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	 $morehtmlref.=' : ';
	 if ($action == 'classify') {
	 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref.='<input type="hidden" name="action" value="classin">';
	 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref.='</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	 $morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


    print dol_get_fiche_end();

	$arrayfields = array(
		'codigo_oferta' => array('label' => $langs->trans("Código de Oferta"), 'checked' => 1),
		'descripcion_oferta' => array('label' => $langs->trans("Descripción de Oferta"), 'checked' => 1),
		'cantidad_oferta' => array('label' => $langs->trans("Cantidad de Oferta"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'codigo_oferta' => array('label' => $langs->trans("Código de Oferta"), 'checked' => 0),
			'descripcion_oferta' => array('label' => $langs->trans("Descripción de Oferta"), 'checked' => 0),
			'cantidad_oferta' => array('label' => $langs->trans("Cantidad de Oferta"), 'checked' => 0),
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
	print_barre_liste($langs->trans("Oferta"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['codigo_oferta']['checked'])) {
		print "<th class='center liste_titre' title='Codigo de Oferta'>";
		print "<a class='reposition' href=''>Codigo de Oferta</a>";
		print "</th>";
	}

	if (!empty($arrayfields['descripcion_oferta']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion de Oferta'>";
		print "<a class='reposition' href=''>Descripcion de Oferta</a>";
		print "</th>";
	}

	if (!empty($arrayfields['cantidad_oferta']['checked'])) {
		print "<th class='center liste_titre' title='Cantidad de Oferta'>";
		print "<a class='reposition' href=''>Cantidad de Oferta</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	/*$sqlOfertas = " SELECT rowid, ref, nombre ";
	$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlOfertas.= " WHERE averia = ".$id;*/

	$sqlIdPresu = " SELECT fk_oferta FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
	$sqlIdPresu.= " WHERE fk_averia = ".$object->id." LIMIT 1";

	$resultIdPresu = $db->query($sqlIdPresu);
	$idPresu = $db->fetch_object($idPresu);

	if ($idPresu->fk_oferta != "") {

		$sqlOfertas = " SELECT p.* ";
		$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."propal p ";
		$sqlOfertas.= " WHERE rowid = ".$idPresu->fk_oferta." ";
		
		$resultOfertas = $db->query($sqlOfertas);
	
		$numOfertas = $db->num_rows($resultOfertas);
	
		while ($oferta = $db->fetch_object($resultOfertas)) {
	
			print '<tr class="oddeven">';
	
			if (!empty($arrayfields['codigo_oferta']['checked']))
				print "<td class='center' tdoverflowmax200'><a href='../../comm/propal/card.php?id=".$oferta->rowid."' target='_blank'>".$oferta->ref."</a></td>";
	
			if (!empty($arrayfields['descripcion_oferta']['checked']))
				print "<td class='center' tdoverflowmax200'>".$oferta->nombre."</td> ";
	
			if (!empty($arrayfields['cantidad_oferta']['checked']))
				print "<td class='center' tdoverflowmax200'>1</td> "; 
	
			print '<td class="center">';
			/*print '
				<table class="center">
					<tr>
						<td>
							<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editar&id=' . $object->id . '&rowid=' . $seguimiento->rowid . '">' . img_edit() . '</a>
						</td>
						<td>
							<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrar&id=' . $object->id . '&rowid=' . $seguimiento->rowid . '">' . img_delete() . '</a>		
						</td>
					</tr>
				</table>
				';
			print '</td>';
			print "</tr>";*/
	
			$i++;
			
		}

	}

	print "</table>";

	print '</div>';

	$sqlLineasOferta2 = " SELECT * ";
	$sqlLineasOferta2.= " FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
	$sqlLineasOferta2.= " WHERE fk_averia = ".$id." AND added = 1 ";

	$resultLineasOferta2 = $db->query($sqlLineasOferta2);

	$numLineas = $db->num_rows($resultLineasOferta2);

	if ($numOfertas == 0 && $numLineas > 0) {

		print '<div class="tabsAction">';
		//print '<a class="butAction" type="button" href="averias_ofertas_card.php?action=create&idaveria='.$id.'" target="_blank">Crear Oferta</a>';
		print '<a class="butAction" type="button" href="../../comm/propal/card.php?action=create&idaveria='.$id.'&socid='.$object->fk_cliente.'&projectid='.$object->fk_project.'" target="_blank">Crear Oferta</a>';
		print '</div>';

	}

	$arrayfields = array(
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
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
	print_barre_liste($langs->trans("Lineas Oferta"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['codigo']['checked'])) {
		print "<th class='center liste_titre' title='Codigo'>";
		print "<a class='reposition' href=''>Codigo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfields['cantidad']['checked'])) {
		print "<th class='center liste_titre' title='Cantidad'>";
		print "<a class='reposition' href=''>Cantidad</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	/*$sqlOfertas = " SELECT rowid, ref, nombre ";
	$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlOfertas.= " WHERE averia = ".$id;

	$resultOferta = $db->query($sqlOfertas);
	$numOfertas = $db->num_rows($resultOferta);
	$ofertalinea = $db->fetch_object($resultOferta);

	if ($numOfertas > 0) {*/

		$sqlLineasOferta = " SELECT m.rowid, m.ref, m.label, m.qty ";
		$sqlLineasOferta.= " FROM ".MAIN_DB_PREFIX."mrp_mo m ";
		$sqlLineasOferta.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ON en.fk_mo = m.rowid ";
		$sqlLineasOferta.= " WHERE en.fk_averia = ".$id." AND en.added = 1 ";
		
		$resultLineasOferta = $db->query($sqlLineasOferta);

	//}

    while ($linea = $db->fetch_object($resultLineasOferta)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['codigo']['checked']))
			print "<td class='center' tdoverflowmax200'><a href='../../mrp/mo_card.php?id=".$linea->rowid."'>".$linea->ref."</a></td>";

		if (!empty($arrayfields['descripcion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$linea->label."</td> ";

		if (!empty($arrayfields['cantidad']['checked']))
			print "<td class='center' tdoverflowmax200'>".$linea->qty."</td> "; 

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrarLinea&id=' . $object->id . '&rowid=' . $linea->rowid . '">' . img_delete() . '</a>		
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

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'.$_SERVER["PHP_SELF"].'?action=addLinea&id='.$id.'">Nueva Línea de Oferta</a>';
	print '</div>';
}

    print '</div>';

	print dol_get_fiche_end();


if ($action == "addLinea") {

	$sqlLineasOferta = " SELECT m.rowid, m.ref, m.label, m.qty ";
	$sqlLineasOferta.= " FROM ".MAIN_DB_PREFIX."mrp_mo m ";
	$sqlLineasOferta.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ON en.fk_mo = m.rowid ";
	$sqlLineasOferta.= " WHERE en.fk_averia = ".$id." AND (en.added IS NULL OR en.added = 0) ";

	$resultLineas = $db->query($sqlLineasOferta);
	$numLineas =  $db->num_rows($sqlLineasOferta);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir Línea de Oferta</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 120.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tr>
							<td>
								<label for="linea">Material: </label>
							</td>
							<td>';

								if ($numLineas > 0) {
									print '<select name="linea" class="select-linea">
									<option value=-1>&nbsp</option>';
									while ($material = $db->fetch_object($resultLineas)) {
										print '<option value='.$material->rowid.'>'.$material->ref.' - '.$material->label.'</option>';
									}
									print '</select>';
								} else {
									print '<span>No hay más líneas para añadir</span>';
								}

								/*while ($material2 = $db->fetch_object($resultMateriales2)) {
                                    print '<option value='.$material2->rowid.'>( ) - '.$material2->codhijo.' - '.$material2->label.'</option>';
                                }*/

                                print '
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">';
				if ($numLineas > 0) {
					print '<button type="submit" class="ui-button ui-corner-all ui-widget" name="addLineaFinal">
						Guardar
					</button>';
					}
					print '<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}


if ($action == "borrarLinea") {

	$id = $_GET['id'];
	$rowid = $_GET['rowid'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar trabajador</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 100.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tr>
							<td>
								<span class="field">¿Seguro que deseas eliminar esta línea de oferta?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteLinea">
						Eliminar
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

	$(".select-usuario").select2();
	$(".select-linea").select2();


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
