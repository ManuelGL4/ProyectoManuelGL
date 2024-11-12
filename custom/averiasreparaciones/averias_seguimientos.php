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

const TIPO_ORDEN = [
    '1' => 'Producción',
    '2' => 'Instalación',
    '3' => 'Reparación',
    '4' => 'Diseño y desarrollo',
];

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

$permissionnote = $user->rights->averiasreparaciones->averias_seguimientos->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->averiasreparaciones->averias_seguimientos->write; // Used by the include of actions_addupdatedelete.inc.php

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

if (isset($_POST['addSeguimiento'])) {

    extract($_POST);

    $sqlInsert = "INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_seguimientos ";
    $sqlInsert.= " (fk_averia, observacion, fecha, fk_user_creat) ";
    $sqlInsert.= " VALUES ";
    $sqlInsert.= " ($id, '".$observacion."', '".$fecha."', $usuario) ";

    $db->query($sqlInsert);

}

if (isset($_POST['editSeguimiento'])) {

    $id = $_GET['id'];
	$idusuario = $_GET['idusuario'];
	$rowid = $_GET['rowid'];
	$observacion = $_POST['observacion'];

	$fecha = dol_now();

	$datetime = dol_print_date($fecha, '%Y-%m-%d %H:%M:%S');

    $sqlEdit = "UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_seguimientos ";
    $sqlEdit.= " SET observacion =  '".$observacion."', fk_user_modif = ".$idusuario.", fecha_modif = '".$datetime."' ";
    $sqlEdit.= " WHERE rowid = ".$rowid;

    $db->query($sqlEdit);

}

if (isset($_POST['deleteSeguimiento'])) {

	$rowid = $_GET['rowid'];

    $sqlDelete = "DELETE FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_seguimientos ";
    $sqlDelete.= " WHERE rowid = ".$rowid;

    $db->query($sqlDelete);

}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Averias_seguimientos'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = averiasPrepareHead($object);
	print dol_get_fiche_head($head, 'seguimiento', $langs->trans("Workstation"), -1, $object->picto);

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
		'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
		'observacion' => array('label' => $langs->trans("Observación"), 'checked' => 1),
		'usuario' => array('label' => $langs->trans("Usuario"), 'checked' => 1),
		'fecha_modi' => array('label' => $langs->trans("Fecha de modificación"), 'checked' => 1),
		'usuario_modi' => array('label' => $langs->trans("Modificado por"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 0),
            'observacion' => array('label' => $langs->trans("Observación"), 'checked' => 0),
            'usuario' => array('label' => $langs->trans("Usuario"), 'checked' => 0),
			'fecha_modi' => array('label' => $langs->trans("Fecha de modificación"), 'checked' => 0),
			'usuario_modi' => array('label' => $langs->trans("Modificado por"), 'checked' => 0),
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

	$sqlUsuarios = "SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";

	$resultUsuarios = $db->query($sqlUsuarios);

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Seguimiento"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['fecha']['checked'])) {
		print "<th class='center liste_titre' title='Fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

	if (!empty($arrayfields['observacion']['checked'])) {
		print "<th class='center liste_titre' title='Observacion'>";
		print "<a class='reposition' href=''>Observacion</a>";
		print "</th>";
	}

	if (!empty($arrayfields['usuario']['checked'])) {
		print "<th class='center liste_titre' title='Usuario'>";
		print "<a class='reposition' href=''>Usuario</a>";
		print "</th>";
	}

	if (!empty($arrayfields['fecha_modi']['checked'])) {
		print "<th class='center liste_titre' title='Fecha de Modificación'>";
		print "<a class='reposition' href=''>Fecha de Modificación</a>";
		print "</th>";
	}

	if (!empty($arrayfields['usuario_modi']['checked'])) {
		print "<th class='center liste_titre' title='Editado Por'>";
		print "<a class='reposition' href=''>Editado Por</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	$sqlSeguimientos = " SELECT aas.rowid, aas.fk_averia, aas.observacion, aas.fecha, aas.fk_user_creat, aas.fecha_modif, aas.fk_user_modif, u.firstname, u.lastname ";
	$sqlSeguimientos.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_seguimientos aas ";
	$sqlSeguimientos.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = aas.fk_user_creat ";
	$sqlSeguimientos.= " WHERE aas.fk_averia =".$id;
	
	$resultSeguimientos = $db->query($sqlSeguimientos);

    while ($seguimiento = $db->fetch_object($resultSeguimientos)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['fecha']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->fecha."</td>";

		if (!empty($arrayfields['observacion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->observacion."</td> ";

		if (!empty($arrayfields['usuario']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->firstname." ".$seguimiento->lastname."</td> ";

		if ($seguimiento->fecha_modif != NULL) {

			if (!empty($arrayfields['fecha_modi']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->fecha_modif."</td> ";

		} else {

			if (!empty($arrayfields['fecha_modi']['checked']))
			print "<td class='center' tdoverflowmax200'></td> ";

		}

			
		if ($seguimiento->fk_user_modif != NULL) {

			$sqlUsuario = " SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$seguimiento->fk_user_modif." ";

			$usuario = $db->query($sqlUsuario);
	
			$usuarioF = $db->fetch_object($usuario);

			if (!empty($arrayfields['usuario_modi']['checked']))
			print "<td class='center' tdoverflowmax200'>".$usuarioF->firstname." ".$usuarioF->lastname."</td> ";

		} else {

			if (!empty($arrayfields['usuario_modi']['checked']))
			print "<td class='center' tdoverflowmax200'></td> ";

		}


		print '<td class="center">';
		print '
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
		print "</tr>";

		$i++;
		
	}
	print "</table>";

    //SEPARACION DE CAMPO

    print '<div class="fichecenter">';
	//print '<div class="underbanner clearboth"></div>';

    print dol_get_fiche_end();

	$arrayfields = array(
		'ref' => array('label' => $langs->trans("ref"), 'checked' => 1),
		'tipo' => array('label' => $langs->trans("tipo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 1),
		'fecha_emision' => array('label' => $langs->trans("fecha_emision"), 'checked' => 1),
        'fecha_finalizacion' => array('label' => $langs->trans("fecha_finalizacion"), 'checked' => 1),
		'cerrada' => array('label' => $langs->trans("cerrada"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'ref' => array('label' => $langs->trans("ref"), 'checked' => 0),
			'tipo' => array('label' => $langs->trans("tipo"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 0),
			'fecha_emision' => array('label' => $langs->trans("fecha_emision"), 'checked' => 0),
			'fecha_finalizacion' => array('label' => $langs->trans("fecha_finalizacion"), 'checked' => 0),
			'cerrada' => array('label' => $langs->trans("cerrada"), 'checked' => 0),
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

	$sqlUsuarios = "SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";

	$resultUsuarios = $db->query($sqlUsuarios);

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Actividades"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['ref']['checked'])) {
		print "<th class='center liste_titre' title='Ref'>";
		print "<a class='reposition' href=''>Ref</a>";
		print "</th>";
	}

	if (!empty($arrayfields['tipo']['checked'])) {
		print "<th class='center liste_titre' title='Tipo'>";
		print "<a class='reposition' href=''>Tipo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

    if (!empty($arrayfields['fecha_emision']['checked'])) {
		print "<th class='center liste_titre' title='Fecha de Emisión'>";
		print "<a class='reposition' href=''>Fecha de Emisión</a>";
		print "</th>";
	}

	if (!empty($arrayfields['fecha_finalizacion']['checked'])) {
		print "<th class='center liste_titre' title='Fecha de Finalización'>";
		print "<a class='reposition' href=''>Fecha de Finalización</a>";
		print "</th>";
	}

	if (!empty($arrayfields['cerrada']['checked'])) {
		print "<th class='center liste_titre' title='Cerrada'>";
		print "<a class='reposition' href=''>Cerrada</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	$sqlActividades = " SELECT m.rowid, m.ref, m.label, m.date_start_planned, m.date_end_planned, me.closed, m.status";
	$sqlActividades.= " FROM ".MAIN_DB_PREFIX."mrp_mo m ";
	$sqlActividades.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new me ON me.fk_mo = m.rowid ";
	$sqlActividades.= " WHERE me.fk_averia = ".$id;
	
	$resultActividades = $db->query($sqlActividades);

    while ($actividad = $db->fetch_object($resultActividades)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['ref']['checked']))
			print "<td class='center' tdoverflowmax200'><a href='../../mrp/mo_card.php?id=".$actividad->rowid."'>".$actividad->ref."</a></td>";

		if (!empty($arrayfields['tipo']['checked']))
			print "<td class='center' tdoverflowmax200'>Reparación</td> ";

		if (!empty($arrayfields['descripcion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$actividad->label."</td> ";

        if (!empty($arrayfields['fecha_emision']['checked']))
        	print "<td class='center' tdoverflowmax200'>".$actividad->date_start_planned."</td> ";

		if (!empty($arrayfields['fecha_finalizacion']['checked']))
        	print "<td class='center' tdoverflowmax200'>".$actividad->date_end_planned."</td> ";

		if (!empty($arrayfields['cerrada']['checked']))

			if ($actividad->status == 3) {
				print "<td class='center' tdoverflowmax200'>Si</td> ";
			} else {
				print "<td class='center' tdoverflowmax200'>No</td> ";
			}


		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>';

					print '</td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;
		
	}
	print "</table>";

    //SEPARACION DE CAMPO

	print '</div>';
    print '<div class="tabsAction">';
    print '<a class="butAction" type="button" href="#addSeguimientoModal" rel="modal:open">Nuevo seguimiento</a>';
    //print '<a class="butAction" type="button" href="#addSeguimientoModal" rel="modal:open">Cerrar avería</a>';
    print '</div>';
	print '
	<div id="addSeguimientoModal" class="modal" role="dialog" aria-labelledby="addSeguimientoModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir seguimiento</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="fecha">Fecha</label>
							</td>
							<td>
                                <input type="datetime-local" name="fecha">
							</td>
						</tr>
						<tr>
							<td>
								<label for="observacion">Observacion</label>
							</td>
							<td>
								<textarea name="observacion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
						<tr>
							<td>
                                <label for="usuario">Usuario</label>
							</td>
							<td>
								<select class="select-usuario" name="usuario">';

                                while ($usuario = $db->fetch_object($resultUsuarios)) {

                                    if ($usuario->rowid == $user->id) {

                                        print '<option value='.$usuario->rowid.' selected>'.$usuario->firstname." ".$usuario->lastname.'</option>';

                                    } else {

                                        print '<option value='.$usuario->rowid.'>'.$usuario->firstname." ".$usuario->lastname.'</option>';

                                    }

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
				<button type="submit" name="addSeguimiento" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';
}

print '</div>';

print dol_get_fiche_end();

if ($action == "editar") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sqlSeguimientos = " SELECT rowid, observacion, fecha, fk_user_creat, fk_user_modif ";
	$sqlSeguimientos.= " FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_seguimientos ";
	$sqlSeguimientos.= " WHERE rowid = ".$rowid;

	$resultSeguimiento = $db->query($sqlSeguimientos);

	$seguimiento = $db->fetch_object($resultSeguimiento);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&idusuario='.$user->id.'&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Seguimiento</span>
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
								<span class="fieldrequired">Observación</span>
							</td>
							<td>
								<textarea name="observacion" rows=3 cols=30>' . $seguimiento->observacion . '</textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editSeguimiento">
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

if ($action == "borrar") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Seguimiento</span>
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
								<span class="field">¿Seguro que deseas eliminar este seguimiento?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteSeguimiento">
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


if ($action == "close") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sqlClose = " UPDATE ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
	$sqlClose.= " SET closed = 1 ";
	$sqlClose.= " WHERE fk_mo = ".$rowid;

	$db->query($sqlClose);

	$destination_url = 'averias_seguimientos.php?id='.$id;

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}


if ($action == "open") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	$sqlClose = " UPDATE ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
	$sqlClose.= " SET closed = 0 ";
	$sqlClose.= " WHERE fk_mo = ".$rowid;

	$db->query($sqlClose);

	$destination_url = 'averias_seguimientos.php?id='.$id;

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}


print  '
<script>

	$(".select-usuario").select2();


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
