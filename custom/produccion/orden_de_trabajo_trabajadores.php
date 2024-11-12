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

if (isset($_POST['addTrabajadorFinal'])) {

	extract($_POST);

	//PROYECTO---------------------------------------------------------------------------------------------------
	//Id de tipo proyecto en caso de que sea responsable
	$sqlProyecto1 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlProyecto1.= " WHERE element LIKE '%project%' AND code LIKE '%PROJECTLEADER%' AND source LIKE '%internal%' ";

	$resultPro1 = $db->query($sqlProyecto1);

	$idResponsable = $db->fetch_object($resultPro1);
	$idResponsable = $idResponsable->rowid;

	//Id de tipo proyecto en caso de que sea participante
	$sqlProyecto2 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlProyecto2.= " WHERE element LIKE '%project%' AND code LIKE '%PROJECTCONTRIBUTOR%' AND source LIKE '%internal%' ";

	$resultPro2 = $db->query($sqlProyecto2);

	$idParticipante = $db->fetch_object($resultPro2);
	$idParticipante = $idParticipante->rowid;

	//COMPROBAMOS SI ESTÁ YA EL USUARIO EN EL PROYECTO
	$consulta = " SELECT * FROM ". MAIN_DB_PREFIX ."element_contact ";
	$consulta.= " WHERE element_id = ".$proyecto." AND fk_socpeople = ".$usuario;

	$resultConsulta = $db->query($consulta);
	$numFilas = $db->num_rows($resultConsulta);

	if ($numFilas == 0) {

		//Insertamos en contacto de proyecto
		if ($tipo == 1) {
			$tipoTrab = $idParticipante;
		} else {
			$tipoTrab = $idResponsable;
		}

		$sqlInsertPro = " INSERT INTO ". MAIN_DB_PREFIX ."element_contact ";
		$sqlInsertPro.= " (statut, element_id, fk_c_type_contact, fk_socpeople) ";
		$sqlInsertPro.= " VALUES ";
		$sqlInsertPro.= " (4, $proyecto, $tipoTrab, $usuario) ";

		$db->query($sqlInsertPro);

		//INSERTAMOS AHORA EL SEGUIMIENTO
		//Sacamos el nombre del usuario asignado
		$sqlNombre = " SELECT firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";
		$sqlNombre.= " WHERE rowid = ".$usuario;

		$resultNombre = $db->query($sqlNombre);
		$nombre = $db->fetch_object($resultNombre);
		$nombre = $nombre->firstname." ".$nombre->lastname;

		//Sacamos la ref del proyecto
		$sqlRef = " SELECT ref FROM ". MAIN_DB_PREFIX ."projet ";
		$sqlRef.= " WHERE rowid = ".$proyecto;

		$resultRef = $db->query($sqlRef);
		$refe = $db->fetch_object($resultRef);
		$refe = $refe->ref;

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
		$sqlInsert.= " (fk_order, descripcion, fecha, usuario) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " ($id, 'Asignado ".$nombre." a proyecto ".$refe."', '".date('Y-m-d H:i:s')."', $user->id) ";
	
		$db->query($sqlInsert);

	}

	//TAREA---------------------------------------------------------------------------------------------------
	//Id de tipo project task en caso de que sea responsable
	$sqlTarea1 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlTarea1.= " WHERE element LIKE '%project_task%' AND code LIKE '%TASKEXECUTIVE%' AND source LIKE '%internal%' ";

	$resultTarea1 = $db->query($sqlTarea1);

	$idResponsable = $db->fetch_object($resultTarea1);
	$idResponsable = $idResponsable->rowid;

	//Id de tipo project task en caso de que sea participante
	$sqlTarea2 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlTarea2.= " WHERE element LIKE '%project_task%' AND code LIKE '%TASKCONTRIBUTOR%' AND source LIKE '%internal%' ";

	$resultTarea2 = $db->query($sqlTarea2);

	$idParticipante = $db->fetch_object($resultTarea2);
	$idParticipante = $idParticipante->rowid;

	//COMPROBAMOS SI ESTÁ YA EL USUARIO EN EL PROYECTO
	$consulta = " SELECT * FROM ". MAIN_DB_PREFIX ."element_contact ";
	$consulta.= " WHERE element_id = ".$tarea." AND fk_socpeople = ".$usuario;

	$resultConsulta = $db->query($consulta);
	$numFilas = $db->num_rows($resultConsulta);

	if ($numFilas == 0) {

		//Insertamos en contacto de tarea
		if ($tipo == 1) {
			$tipoTrab = $idParticipante;
		} else {
			$tipoTrab = $idResponsable;
		}

		$sqlInsertTarea = " INSERT INTO ". MAIN_DB_PREFIX ."element_contact ";
		$sqlInsertTarea.= " (statut, element_id, fk_c_type_contact, fk_socpeople) ";
		$sqlInsertTarea.= " VALUES ";
		$sqlInsertTarea.= " (4, $tarea, $tipoTrab, $usuario) ";

		$db->query($sqlInsertTarea);

		//INSERTAMOS AHORA EL SEGUIMIENTO
		//Sacamos la ref de la tarea
		$sqlRef = " SELECT ref FROM ". MAIN_DB_PREFIX ."projet_task ";
		$sqlRef.= " WHERE rowid = ".$tarea;

		$resultRef = $db->query($sqlRef);
		$refe = $db->fetch_object($resultRef);
		$refe = $refe->ref;

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
		$sqlInsert.= " (fk_order, descripcion, fecha, usuario) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " ($id, 'Asignado ".$nombre." a esta OT (tarea: ".$refe.")', '".date('Y-m-d H:i:s')."', $user->id) ";
	
		$db->query($sqlInsert);

	}

}

if (isset($_POST['deleteTrabajador'])) {

	$rowid = $_GET['rowid'];
	$idusuario = $_GET['idusuario'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."element_contact ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

	$rowid--;

	$sqlDelete2 = " DELETE FROM ". MAIN_DB_PREFIX ."element_contact ";
	$sqlDelete2.= " WHERE rowid = ".$rowid." AND fk_socpeople = ".$idusuario." AND element_id = ".$proyecto;

	$db->query($sqlDelete2);

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

	print dol_get_fiche_head($head, 'trabajadores', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/produccion/orden_de_trabajo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	//Id de la tarea
if ($object->fk_task != "") {

	$sqlTarea = " SELECT fk_task FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
	$sqlTarea.= " WHERE rowid = ".$id;

	$resultTarea = $db->query($sqlTarea);
	$tarea = $db->fetch_object($resultTarea);
	$tarea = $tarea->fk_task;

	//Id del proyecto de la tarea
	$sqlPro = " SELECT fk_projet FROM ". MAIN_DB_PREFIX ."projet_task ";
	$sqlPro.= " WHERE rowid = ".$tarea;

	$resultProyecto = $db->query($sqlPro);

	$proyecto = $db->fetch_object($resultProyecto);
	$proyecto = $proyecto->fk_projet;

}


	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	$arrayfieldsFases = array(
		'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 1),
		'tipo_trabajador' => array('label' => $langs->trans("Tipo de Trabajador"), 'checked' => 1),
		'estatus' => array('label' => $langs->trans("Estatus"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsFases = array(
			'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 0),
			'tipo_trabajador' => array('label' => $langs->trans("Tipo de Trabajador"), 'checked' => 0),
			'estatus' => array('label' => $langs->trans("Estatus"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsFases[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsFases, $varpage); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	if ($object->fk_task != "") {

		$sqlTrabajadores = " SELECT u.rowid as idusuario, ec.rowid as idelemento, u.firstname, u.lastname, tc.source, tc.libelle, tc.rowid as idcontact FROM ". MAIN_DB_PREFIX ."user u ";
		$sqlTrabajadores.= " INNER JOIN ". MAIN_DB_PREFIX ."element_contact ec ON ec.fk_socpeople = u.rowid ";
		$sqlTrabajadores.= " INNER JOIN ". MAIN_DB_PREFIX ."c_type_contact tc ON tc.rowid = ec.fk_c_type_contact ";
		$sqlTrabajadores.= " WHERE ec.element_id = ".$tarea;

		$resultTrabajadores = $db->query($sqlTrabajadores);

		$num = $db->num_rows($resultTrabajadores);

		$nbtotalofrecords = $num;
		
	}

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;
	
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Trabajadores"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsFases['nombre']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_nombre">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['tipo_trabajador']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo_trabajador">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['estatus']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_estatus">';
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

	if (!empty($arrayfieldsFases['nombre']['checked'])) {
		print "<th class='center liste_titre' title='Nombre'>";
		print "<a class='reposition' href=''>Nombre</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['tipo_trabajador']['checked'])) {
		print "<th class='center liste_titre' title='Tipo de Trabajador'>";
		print "<a class='reposition' href=''>Tipo de Trabajador</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['estatus']['checked'])) {
		print "<th class='center liste_titre' title='Estatus'>";
		print "<a class='reposition' href=''>Estatus</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';

	while ($trabajador = $db->fetch_object($resultTrabajadores)) {

		print '<tr class="oddeven">';

		if ($trabajador->libelle == "Responsable") {
			$libelle = "Responsable";
		} else {
			$libelle = "Participante";
		}

		if ($trabajador->source == "internal") {
			$source = "Interno";
		} else {
			$source = "Externo";
		}

		if (!empty($arrayfieldsFases['nombre']['checked']))	print "<td class='center' tdoverflowmax200'>" . $trabajador->firstname." ".$trabajador->lastname . "</td> ";

		if (!empty($arrayfieldsFases['tipo_trabajador']['checked']))	print "<td class='center' tdoverflowmax200'>" . $libelle . "</td> ";

		if (!empty($arrayfieldsFases['estatus']['checked']))	print "<td class='center' tdoverflowmax200'>" . $source . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=borrarTrabajador&id='.$id.'&rowid=' . $trabajador->idelemento . '&idusuario='.$trabajador->idusuario.'">' . img_delete() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";

	}
	print "</table>";

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=addATrabajador&id='.$id.'">Nuevo trabajador</a>';
	print '</div>';
	
}

print dol_get_fiche_end();

if ($action == "addATrabajador") {

	$id = $_GET['id'];

	$sqlUsuarios = " SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user u";

	$resultUsuarios = $db->query($sqlUsuarios);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir trabajador</span>
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
								<td>
									<label for="usuario">Usuario</label>
								</td>
								<td>
									<select name="usuario" class="select-usuario">
									<option value=-1>&nbsp</option>';

									while ($usu = $db->fetch_object($resultUsuarios)) {
										print '<option value='.$usu->rowid.'>'.$usu->firstname.' '.$usu->lastname.'</option>';
									}

									print '</select>
								</td>
							</tr>
							<tr>
								<td>
									<label for="tipo">Tipo</label>
								</td>
								<td>
									<select name="tipo" class="select-tipo">
										<option value=1 selected>Participante</option>
										<option value=2>Responsable</option>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addTrabajadorFinal">
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

if ($action == "borrarTrabajador") {

	$id = $_GET['id'];
	$rowid = $_GET['rowid'];
	$idusuario = $_GET['idusuario'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'&idusuario='.$idusuario.'">
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
								<span class="field">¿Seguro que deseas desasignar a este trabajador?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteTrabajador">
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

// End of page
llxFooter();
$db->close();
