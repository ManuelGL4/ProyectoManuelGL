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

$permissionnote = $user->rights->averiasreparaciones->averias_equipos->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->averiasreparaciones->averias_equipos->write; // Used by the include of actions_addupdatedelete.inc.php

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

if (isset($_POST['editEquipo'])) {

    $id = $_GET['id'];
	$rowid = $_GET['rowid'];
	$version = $_POST['version'];
	$indicacion = $_POST['indicacion'];
	$ot = $_POST['ot'];
	$sc = $_POST['sc'];

    $sqlEdit = "UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ";
    $sqlEdit.= " SET version =  '".$version."', indicaciones = '".$indicacion."', ot = ".$ot.", sc = ".$sc." ";
    $sqlEdit.= " WHERE rowid = ".$rowid;

    $db->query($sqlEdit);

}

if (isset($_POST['addEquipo'])) {

    $id = $_GET['id'];
	$rowid = $_GET['rowid'];

	extract($_POST);

	$sqlConsulta = " SELECT label FROM ". MAIN_DB_PREFIX ."product ";
	$sqlConsulta.= " WHERE rowid = ".$equipo;

	$resultConsulta = $db->query($sqlConsulta);
	$producto = $db->fetch_object($resultConsulta);
	$label = $producto->label;

	if ($equipo_padre == "") {
		$equipo_padre = 0;
	}

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ";
	$sqlInsert.= " (fk_averia, codigo_padre, codigo, label, version, qty, indicaciones) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $equipo_padre, $equipo, '".$label."', '".$version."', $unidades, '".$indicacion."') ";

    $db->query($sqlInsert);

}

if (isset($_POST['deleteEquipo'])) {

    $id = $_GET['id'];
	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

    $db->query($sqlDelete);

}

/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Averias_equipos'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = averiasPrepareHead($object);
	print dol_get_fiche_head($head, 'equipos', $langs->trans("Workstation"), -1, $object->picto);

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
		'codigo_padre' => array('label' => $langs->trans("Código Padre"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'version' => array('label' => $langs->trans("Versión"), 'checked' => 1),
        'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 1),
		'ot' => array('label' => $langs->trans("OT"), 'checked' => 1),
        'sc' => array('label' => $langs->trans("SC"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'codigo_padre' => array('label' => $langs->trans("Código Padre"), 'checked' => 0),
            'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 0),
            'version' => array('label' => $langs->trans("Versión"), 'checked' => 0),
            'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
            'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 0),
            'ot' => array('label' => $langs->trans("OT"), 'checked' => 0),
            'sc' => array('label' => $langs->trans("SC"), 'checked' => 0),
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
	print_barre_liste($langs->trans("Equipos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['codigo_padre']['checked'])) {
		print "<th class='center liste_titre' title='Codigo Padre'>";
		print "<a class='reposition' href=''>Codigo Padre</a>";
		print "</th>";
	}

	if (!empty($arrayfields['codigo']['checked'])) {
		print "<th class='center liste_titre' title='Codigo'>";
		print "<a class='reposition' href=''>Codigo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['articulo']['checked'])) {
		print "<th class='center liste_titre' title='Articulo'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['version']['checked'])) {
		print "<th class='center liste_titre' title='Version'>";
		print "<a class='reposition' href=''>Version</a>";
		print "</th>";
	}

    if (!empty($arrayfields['unidades']['checked'])) {
		print "<th class='center liste_titre' title='Unidades'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfields['indicaciones']['checked'])) {
		print "<th class='center liste_titre' title='Indicaciones'>";
		print "<a class='reposition' href=''>Indicaciones</a>";
		print "</th>";
	}

	if (!empty($arrayfields['ot']['checked'])) {
		print "<th class='center liste_titre' title='OT'>";
		print "<a class='reposition' href=''>OT</a>";
		print "</th>";
	}

    if (!empty($arrayfields['sc']['checked'])) {
		print "<th class='center liste_titre' title='SC'>";
		print "<a class='reposition' href=''>SC</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	$sqlInforme = " SELECT fk_informe FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias ";
	$sqlInforme.= " WHERE rowid = ".$id;

	$resultInforme = $db->query($sqlInforme);

	$informe = $db->fetch_object($resultInforme);

	if ($informe->fk_informe != "") {

		$sqlEquipos = " SELECT mis.rowid, mis.fk_report, mis.fk_product_root, mis.fk_product, mis.quantity, p.rowid as idroot, p.ref as refroot, p.label as labelroot, p.description as descroot, pr.rowid as idpro, pr.ref as refpro, pr.label as labelpro, pr.description as descpro ";
		$sqlEquipos.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ";
		$sqlEquipos.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mis.fk_report ";
		$sqlEquipos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product_root ";
		$sqlEquipos.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product ";
		$sqlEquipos.= " WHERE mis.is_future = 0 AND mis.is_retired = 0 AND mis.is_returned = 0 AND mi.rowid = ".$informe->fk_informe;
		
		$resultEquipos = $db->query($sqlEquipos);

		$consulta = " SELECT * FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ";
		$consulta.= " WHERE fk_averia = ".$id;

		$resultConsulta = $db->query($consulta);

		$num_filas = $db->num_rows($resultConsulta);

		if ($num_filas == 0) {

			while ($equipo = $db->fetch_object($resultEquipos)) {

				$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ";
				$sqlInsert.= " (fk_averia, codigo_padre, codigo, label, qty) ";
				$sqlInsert.= " VALUES ";
				$sqlInsert.= " (".$id.", '".$equipo->idroot."', '".$equipo->idpro."', '".$equipo->labelpro."', ".$equipo->quantity.") ";
		
				$db->query($sqlInsert);
		
			}

		}

	}

	$sqlDatos = " SELECT ap.rowid as rowid, ap.codigo_padre, ap.codigo, ap.fk_averia, ap.version, ap.qty, ap.indicaciones, ap.ot, ap.sc, ";
	$sqlDatos.= " p.rowid as idroot, p.ref as refroot, p.label as labelroot, p.description as descroot, ";
	$sqlDatos.= " pr.rowid as idpro, pr.ref as refpro, pr.label as labelpro, pr.description as descpro ";
	$sqlDatos.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ap";
	$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ap.codigo_padre ";
	$sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = ap.codigo ";
	$sqlDatos.= " WHERE fk_averia = ".$id;

	$resultDatos = $db->query($sqlDatos);

	$sqlDatosNuevos = "SELECT ap.rowid as rowid, ap.fk_averia, ap.codigo_padre, ap.codigo, ap.version, ap.qty, ap.indicaciones, ap.ot, ap.sc, ";
	$sqlDatosNuevos.= " pr.rowid as idpro, pr.ref as refpro, pr.label as labelpro, pr.description as descpro ";
	$sqlDatosNuevos.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ap INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = ap.codigo ";
	$sqlDatosNuevos.= " WHERE fk_averia = ".$id." AND codigo_padre = 0";

	$resultDatos2 = $db->query($sqlDatosNuevos);

    while ($equipoF = $db->fetch_object($resultDatos)) {

		$sqlBom = " SELECT rowid FROM ".MAIN_DB_PREFIX."bom_bom ";
		$sqlBom.= " WHERE fk_product = ".$equipoF->codigo." ";

		$resultBom = $db->query($sqlBom);
		$bom = $db->fetch_object($resultBom);

		print '<tr class="oddeven">';

		if (!empty($arrayfields['codigo_padre']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->refroot."</td>";

		if (!empty($arrayfields['codigo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->refpro."</td>";

		if (!empty($arrayfields['articulo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->descpro."</td> ";

		if (!empty($arrayfields['version']['checked']))
			print "<td class='center' tdoverflowmax200'>$equipoF->version</td> ";

        if (!empty($arrayfields['unidades']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF->qty."</td>";

		if (!empty($arrayfields['indicaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>$equipoF->indicaciones</td> ";

		if (!empty($arrayfields['ot']['checked']))
	
			if ($equipoF->ot == 0) {
				print "<td class='center' tdoverflowmax200'>No</td> "; 
			} else {
				print "<td class='center' tdoverflowmax200'>Si</td> "; 
			}
            
        if (!empty($arrayfields['sc']['checked']))

			if ($equipoF->sc == 0) {
				print "<td class='center' tdoverflowmax200'>No</td> "; 
			} else {
				print "<td class='center' tdoverflowmax200'>Si</td> "; 
			}

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editar&id=' . $object->id . '&rowid=' . $equipoF->rowid . '">' . img_edit() . '</a>
					</td>
					<td>
						<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=borrar&id=' . $object->id . '&rowid=' . $equipoF->rowid . '">' . img_delete() . '</a>
					</td>';
					

					//if ($equipoF->ot == 0) {
						print '<td>
								<a class="fas fa-plus" href="../../mrp/mo_card.php?action=create&fk_bom='.$bom->rowid.'&fk_project='.$object->fk_project.'&fk_soc='.$object->fk_cliente.'&idaveria=' . $object->id.'&qty='.$equipoF->qty.'&idequipo=' . $equipoF->rowid . '" target="_blank" title="Lanzar OT"></a>
							</td>';
							print '<td>
							<a class="fas fa-clipboard" href="../../supplier_proposal/card.php?action=create&projectid='.$object->fk_project.'&equipo='.$equipoF->rowid.'" target="_blank" title="Solicitud de compra"></a>
						</td>';
							
					//}
					
				print '</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		//$i++;
		
	}

	while ($equipoF2 = $db->fetch_object($resultDatos2)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['codigo_padre']['checked']))
			print "<td class='center' tdoverflowmax200'></td>";

		if (!empty($arrayfields['codigo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF2->refpro."</td>";

		if (!empty($arrayfields['articulo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF2->descpro."</td> ";

		if (!empty($arrayfields['version']['checked']))
			print "<td class='center' tdoverflowmax200'>$equipoF2->version</td> ";

        if (!empty($arrayfields['unidades']['checked']))
			print "<td class='center' tdoverflowmax200'>".$equipoF2->qty."</td>";

		if (!empty($arrayfields['indicaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>$equipoF2->indicaciones</td> ";

		if (!empty($arrayfields['ot']['checked']))
	
			if ($equipoF2->ot == 0) {
				print "<td class='center' tdoverflowmax200'>No</td> "; 
			} else {
				print "<td class='center' tdoverflowmax200'>Si</td> "; 
			}
            
        if (!empty($arrayfields['sc']['checked']))

			if ($equipoF2->sc == 0) {
				print "<td class='center' tdoverflowmax200'>Nosss</td> "; 
			} else {
				print "<td class='center' tdoverflowmax200'>Si</td> "; 
			}

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editar&id=' . $object->id . '&rowid=' . $equipoF2->rowid . '">' . img_edit() . '</a>
					</td>
					<td>
						<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=borrar&id=' . $object->id . '&rowid=' . $equipoF2->rowid . '">' . img_delete() . '</a>
					</td>';

						print '<td>
								<a class="fas fa-plus" href="../../mrp/mo_card.php?action=create&areparar='.$equipoF2->codigo_padre.'&aconsumir='.$equipoF2->codigo.'&idaveria=' . $object->id.'&qty='.$equipoF2->qty.'&rowid=' . $equipoF2->rowid . '" title="Lanzar OT"></a>
							</td>';
					
				print '</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		//$i++;
		
	}


	print "</table>";

	print '</div>';
    print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="printInformeAveria.php?id='.$id.'" target="_blank">Informe Avería</a>';
    print '<a class="butAction" type="button" href="#addEquipoModal" rel="modal:open">Nuevo equipo</a>';
    print '</div>';

	if ($object->fk_informe != "") {

		$sqlProductosPadre = " SELECT e.*, p.rowid, p.ref, p.label FROM ".MAIN_DB_PREFIX."mantenimiento_informes_equipos e ";
		$sqlProductosPadre.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = e.fk_product ";
		$sqlProductosPadre.= " WHERE fk_report = ".$object->fk_informe;

		$resultProductosPadre = $db->query($sqlProductosPadre);

		$sqlProductos = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";

		$resultProductos = $db->query($sqlProductos);
		
		print '
	<div id="addEquipoModal" class="modal" role="dialog" aria-labelledby="addEquipoModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir equipo</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="equipo_padre">Equipo padre</label>
							</td>
							<td>
								<select name="equipo_padre" class="select_padre" style="width:100%">
									<option value=-1>&nbsp</option>';

									while ($productoPadre = $db->fetch_object($resultProductosPadre)) {
										print '<option value='.$productoPadre->rowid.'>'.$productoPadre->ref.' - '.$productoPadre->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="equipo">Equipo</label>
							</td>
							<td>
								<select name="equipo" class="select_equipo" style="width:100%">
									<option value=-1>&nbsp</option>';

									while ($producto = $db->fetch_object($resultProductos)) {
										print '<option value='.$producto->rowid.'>'.$producto->ref.' - '.$producto->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="version">Version</label>
							</td>
							<td>
                                <input type="text" name="version">
							</td>
						</tr>
						<tr>
							<td>
								<label for="unidades">Unidades</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="unidades">
							</td>
						</tr>
						<tr>
							<td>
								<label for="indicacion">Indicacion</label>
							</td>
							<td>
								<textarea name="indicacion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addEquipo" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	} else {

		$sqlProductos1 = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";

		$resultProductos1 = $db->query($sqlProductos1);

		$sqlProductos2 = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";

		$resultProductos2 = $db->query($sqlProductos2);
		
		print '
	<div id="addEquipoModal" class="modal" role="dialog" aria-labelledby="addEquipoModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir equipo</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="equipo_padre">Equipo padre</label>
							</td>
							<td>
								<select name="equipo_padre" class="select_padre" style="width:100%">
									<option value=-1>&nbsp</option>';

									while ($producto1 = $db->fetch_object($resultProductos1)) {
										print '<option value='.$producto1->rowid.'>'.$producto1->ref.' - '.$producto1->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="equipo">Equipo</label>
							</td>
							<td>
								<select name="equipo" class="select_equipo" style="width:100%">
									<option value=-1>&nbsp</option>';

									while ($producto2 = $db->fetch_object($resultProductos2)) {
										print '<option value='.$producto2->rowid.'>'.$producto2->ref.' - '.$producto2->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="version">Version</label>
							</td>
							<td>
                                <input type="text" name="version">
							</td>
						</tr>
						<tr>
							<td>
								<label for="unidades">Unidades</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="unidades">
							</td>
						</tr>
						<tr>
							<td>
								<label for="indicacion">Indicacion</label>
							</td>
							<td>
								<textarea name="indicacion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addEquipo" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	}

	$sqlProductos = " SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product ";

	$resultProductos = $db->query($sqlProductos);

	print '
	<div id="addEquipoModal" class="modal" role="dialog" aria-labelledby="addEquipoModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir equipo</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="equipo_padre">Equipo padre</label>
							</td>
							<td>
								<select name="equipo_padre">
									<option value=-1>&nbsp</option>';

									while ($producto = $db->fetch_object($resultProductos)) {
										print '<option value='.$producto->rowid.'>'.$producto->ref.' - '.$producto->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="equipo">Equipo</label>
							</td>
							<td>
								<select name="equipo">
									<option value=-1>&nbsp</option>';

									while ($producto = $db->fetch_object($resultProductos)) {
										print '<option value='.$producto->rowid.'>'.$producto->ref.' - '.$producto->label.'</option>';								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="version">Version</label>
							</td>
							<td>
                                <input type="text" name="version">
							</td>
						</tr>
						<tr>
							<td>
								<label for="unidades">Unidades</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="unidades">
							</td>
						</tr>
						<tr>
							<td>
								<label for="indicacion">Indicacion</label>
							</td>
							<td>
								<textarea name="indicacion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addEquipo" class="butAction">Añadir</button>
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

	$sqlEquipos = " SELECT version, indicaciones, ot, sc ";
	$sqlEquipos.= " FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_equipos ";
	$sqlEquipos.= " WHERE rowid = ".$rowid;

	$resultEquipos = $db->query($sqlEquipos);

	$equipo = $db->fetch_object($resultEquipos);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
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
								<label for="version">Version</label>
							</td>
							<td>
								<input type="text" name="version" value="'.$equipo->version.'">
							</td>
						</tr>
						<tr>
							<td>
								<label for="indicacion">Indicacion</label>
							</td>
							<td>
								<textarea name="indicacion" style="width:300px;height:80px">'.$equipo->indicaciones.'</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<label for="ot">OT</label>
							</td>
							<td>
								<select class="select-ot" name="ot">';

								if ($equipo->ot == 0) {

									print '<option value="0" selected>No</option>';
									print '<option value="1">Si</option>';

								} else {

									print '<option value="0">No</option>';
									print '<option value="1" selected>Si</option>';

								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="sc">SC</label>
							</td>
							<td>
								<select class="select-sc" name="sc">';

								if ($equipo->sc == 0) {

									print '<option value="0" selected>No</option>';
									print '<option value="1">Si</option>';

								} else {

									print '<option value="0">No</option>';
									print '<option value="1" selected>Si</option>';

								}

								print '</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editEquipo">
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
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Equipo</span>
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
								<span class="field">¿Seguro que deseas eliminar este equipo?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteEquipo">
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

	$(".select-ot").select2();
	$(".select-sc").select2();
	$(".select_padre").select2();
	$(".select_equipo").select2();

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
