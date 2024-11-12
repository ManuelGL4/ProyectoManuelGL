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

if (isset($_POST['addIncidencia'])) {

	extract($_POST);

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_incidencias ";
	$sqlInsert.= " (fk_order, fecha, incidencia, usuario) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, '".$fecha."', '".$incidencia."', $usuario) ";

	$db->query($sqlInsert);

}

if (isset($_POST['deleteIncidencia'])) {

	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_incidencias ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

}

if (isset($_POST['addSeguimiento'])) {

	extract($_POST);

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
	$sqlInsert.= " (fk_order, descripcion, fecha, usuario) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, '".$descripcion."', '".$fecha."', $usuario) ";

	$db->query($sqlInsert);

}

if (isset($_POST['deleteSeguimiento'])) {

	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

}

if (isset($_POST['addGasto'])) {

	extract($_POST);

	$sqlFase = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlFase.= " WHERE rowid = ".$fase;

	$resultFase = $db->query($sqlFase);
	$fase1 = $db->fetch_object($resultFase);

	if ($stock == "") {
		$stock = "NULL";
	}

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlInsert.= " (fk_orden, fk_fase, codigo, descripcion, unidades, stock, tipo, coste) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $fase, '".$fase1->codigo."', '".$descripcion."', $unidades, $stock, '".$tipo."', $coste) ";

	$db->query($sqlInsert);

	//Para añadir el gasto al coste de lineas OT
	$sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
	$sqlUpdate.= " SET actual_cost = COALESCE(actual_cost, 0) + ".$coste;
	$sqlUpdate.= " WHERE rowid = ".$id;

	$db->query($sqlUpdate);

}

if (isset($_POST['deleteGasto'])) {

	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

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

	print dol_get_fiche_head($head, 'seguimiento', '', -1, 'object_orden_de_trabajo.png');

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
		'fecha' => array('label' => $langs->trans("codigo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 1),
		'usuario' => array('label' => $langs->trans("articulo"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsFases = array(
			'fecha' => array('label' => $langs->trans("codigo"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 0),
			'usuario' => array('label' => $langs->trans("articulo"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsFases[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsFases, $varpage); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	$sqlSeguimientos = " SELECT rowid, fecha, descripcion, usuario FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
	$sqlSeguimientos.= " WHERE fk_order = ".$id;

	$resultSeguimientos = $db->query($sqlSeguimientos);
	
	$num = $db->num_rows($resultSeguimientos);
	$nbtotalofrecords = $num;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Seguimiento"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';//MIRAR
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsFases['fecha']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_tarea_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_trabajador_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['usuario']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fecha">';
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

	if (!empty($arrayfieldsFases['fecha']['checked'])) {
		print "<th class='center liste_titre' title='Fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripción'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['usuario']['checked'])) {
		print "<th class='center liste_titre' title='Usuario'>";
		print "<a class='reposition' href=''>Usuario</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($seguimiento = $db->fetch_object($resultSeguimientos)) {

		$sqlUser = " SELECT firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";
		$sqlUser.= " WHERE rowid = ".$seguimiento->usuario;

		$resultUsuario = $db->query($sqlUser);

		$usuario = $db->fetch_object($resultUsuario);

		print '<tr class="oddeven">';

		if (!empty($arrayfieldsFases['fecha']['checked']))	print "<td class='center' tdoverflowmax200'>" . $seguimiento->fecha . "</td> ";

		if (!empty($arrayfieldsFases['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $seguimiento->descripcion . "</td> ";

		if (!empty($arrayfieldsFases['usuario']['checked']))	print "<td class='center' tdoverflowmax200'>" . $usuario->firstname." ".$usuario->lastname . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=borrarSeguimiento&id='.$id.'&rowid=' . $seguimiento->rowid . '">' . img_delete() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";

	}
	print "</table>";
	//print '</div>';//Tocar por aqui

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" style="margin-bottom:0px !important" href="'. $_SERVER["PHP_SELF"] .'?action=seguimiento&id='.$id.'">Nuevo seguimiento</a>';
	print '</div>';

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsTareas = array(
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsTareas = array(
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
			'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
			'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
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

	$result = $db->query($sql);
	//if ($result) {
	$num = $db->num_rows($result);
	$nbtotalofrecords = $num;
	$nbtotalofrecords = 0;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Partes de trabajo"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsTareas['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_codigo">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_unidades">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['tipo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['coste']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_coste">';
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

	if (!empty($arrayfieldsTareas['codigo']['checked'])) {
		print "<th class='center liste_titre' title='codigo'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['unidades']['checked'])) {
		print "<th class='center liste_titre' title='cantidad'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['tipo']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Tipo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['coste']['checked'])) {
		print "<th class='center liste_titre' title='cantidad'>";
		print "<a class='reposition' href=''>Coste</a>";
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

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
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
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i < $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		if (!empty($arrayfieldsTareas['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->id_asoc . "</td> ";

		if (!empty($arrayfieldsTareas['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->firstname . "</td> ";

		if (!empty($arrayfieldsTareas['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if (!empty($arrayfieldsTareas['tipo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->firstname . "</td> ";

		if (!empty($arrayfieldsTareas['coste']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
	//print '</div>';

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" style="margin-bottom:0px !important" href="'. $_SERVER["PHP_SELF"] .'?action=informe&id='.$id.'">Generar Informe</a>';
	print '</div>';

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsArticulos = array(
		'fase' => array('label' => $langs->trans("Fase/Tarea"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'stock' => array('label' => $langs->trans("Stock"), 'checked' => 1),
		'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsArticulos = array(
			'fase' => array('label' => $langs->trans("Fase/Tarea"), 'checked' => 0),
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
			'stock' => array('label' => $langs->trans("Stock"), 'checked' => 0),
			'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 0),
			'coste' => array('label' => $langs->trans("Coste"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsArticulos[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsArticulos, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	$sqlGastos = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlGastos.= " WHERE fk_orden = ".$id;

	$resultGastos = $db->query($sqlGastos);
	$numGastos = $db->num_rows($resultGastos);

	$nbtotalofrecords = $numGastos;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Gastos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsArticulos['fase']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_fase">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_codigo">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_unidades">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['stock']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_stock">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['tipo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['coste']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_coste">';
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

	if (!empty($arrayfieldsArticulos['fase']['checked'])) {
		print "<th class='center liste_titre' title='fase'>";
		print "<a class='reposition' href=''>Fase</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
		print "<th class='center liste_titre' title='codigo'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
		print "<th class='center liste_titre' title='unidades'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['stock']['checked'])) {
		print "<th class='center liste_titre' title='stock'>";
		print "<a class='reposition' href=''>Stock</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['tipo']['checked'])) {
		print "<th class='center liste_titre' title='tipo'>";
		print "<a class='reposition' href=''>Tipo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['coste']['checked'])) {
		print "<th class='center liste_titre' title='coste'>";
		print "<a class='reposition' href=''>Coste</a>";
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
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($gasto = $db->fetch_object($resultGastos)) {

		$sqlConsulta = " SELECT descripcion FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
		$sqlConsulta.= " WHERE rowid = ".$gasto->fk_fase;

		$resultConsulta = $db->query($sqlConsulta);
		$faseDesc = $db->fetch_object($resultConsulta);

		print '<tr class="oddeven">';

		if (!empty($arrayfieldsArticulos['fase']['checked']))	print "<td class='center' tdoverflowmax200'>".$faseDesc->descripcion."</td> ";

		if (!empty($arrayfieldsArticulos['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->codigo . "</td> ";

		if (!empty($arrayfieldsArticulos['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->descripcion . "</td> ";

		if (!empty($arrayfieldsArticulos['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->unidades . "</td> ";

		if (!empty($arrayfieldsArticulos['stock']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->stock . "</td> ";

		if (!empty($arrayfieldsArticulos['tipo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->tipo . "</td> ";

		if (!empty($arrayfieldsArticulos['coste']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->coste . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href='.$_SERVER['PHP_SELF'].'?action=borrarGasto&id='.$id.'&rowid='. $gasto->rowid . '>' . img_delete() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";

	}
	print "</table>";
	print '</div>';

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" style="margin-bottom:0px !important" href="'. $_SERVER["PHP_SELF"] .'?action=gasto&id='.$id.'">Nuevo gasto</a>';
	print '</div>';

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsIncidencias = array(
		'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
		'incidencia' => array('label' => $langs->trans("Incidencia"), 'checked' => 1),
		'usuario' => array('label' => $langs->trans("Usuario"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsIncidencias = array(
			'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 0),
			'incidencia' => array('label' => $langs->trans("Incidencia"), 'checked' => 0),
			'usuario' => array('label' => $langs->trans("Usuario"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsIncidencias[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsIncidencias, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	$result = $db->query($sql);
	//if ($result) {
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

		$newcardbutton = '';
		
		$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo artículo'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/don/card.php?action=create');
		
		$consultaIncidencias = " SELECT rowid, fecha, incidencia, usuario FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_incidencias ";
		$consultaIncidencias.= " WHERE fk_order = ".$id;

		$resultIncidencias = $db->query($consultaIncidencias);

		$nbtotalofrecords = $db->num_rows($resultIncidencias);

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		print_barre_liste($langs->trans("Incidencias"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

		//print '<div class="div-table-responsive">';
		print '<table class="tagtable liste">' . "\n";

		print "
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
		if (!empty($arrayfieldsIncidencias['fecha']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="number" name="search_fecha">';
			print '</td>';
		}
		if (!empty($arrayfieldsIncidencias['incidencia']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_incidencia">';
			print '</td>';
		}
		if (!empty($arrayfieldsIncidencias['usuario']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_usuario">';
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

		if (!empty($arrayfieldsIncidencias['fecha']['checked'])) {
			print "<th class='center liste_titre' title='Fecha'>";
			print "<a class='reposition' href=''>Fecha</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsIncidencias['incidencia']['checked'])) {
			print "<th class='center liste_titre' title='Incidencia'>";
			print "<a class='reposition' href=''>Incidencia</a>";
			print "</th>";
		}

		if (!empty($arrayfieldsIncidencias['usuario']['checked'])) {
			print "<th class='center liste_titre' title='Usuario'>";
			print "<a class='reposition' href=''>Usuario</a>";
			print "</th>";
		}

		print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
		print "
			
			</tr>
			";

		print '<form method="POST" action="" name="formfilter" autocomplete="off">';

		while ($inc = $db->fetch_object($resultIncidencias)) {

			$sqlUser = " SELECT firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";
			$sqlUser.= " WHERE rowid = ".$inc->usuario;

			$resultUsuario = $db->query($sqlUser);

			$usuario = $db->fetch_object($resultUsuario);

			print '<tr class="oddeven">';

			if (!empty($arrayfieldsIncidencias['fecha']['checked']))	print "<td class='center' tdoverflowmax200'>" . $inc->fecha . "</td> ";

			if (!empty($arrayfieldsIncidencias['incidencia']['checked']))	print "<td class='center' tdoverflowmax200'>" . $inc->incidencia . "</td> ";

			if (!empty($arrayfieldsIncidencias['usuario']['checked']))	print "<td class='center' tdoverflowmax200'>" . $usuario->firstname." ".$usuario->lastname . "</td> ";

			if ($user->rights->adherent->configurer) {
				print '<td class="center"><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=borrarIncidencia&rowid=' . $inc->rowid . '">' . img_delete() . '</a></td>';
			} else {
				print '<td class="center">&nbsp;</td>';
			}
			print "</tr>";
			$i++;
		}
		print "</table>";

		print '</form>';

		print '<div class="tabsAction">';
		print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=incidencia&id='.$id.'">Nueva incidencia</a>';
		print '</div>';
		print '</div>';
	// } else {
	// 	dol_print_error($db);
	// }

}
print dol_get_fiche_end();

if ($action == "incidencia") {

	$id = $_GET['id'];

	$sqlUsuarios = "SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";

	$resultUsuarios = $db->query($sqlUsuarios);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir incidencia</span>
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
								<label for="fecha">Fecha</label>
							</td>
							<td>
                                <input type="datetime-local" name="fecha">
							</td>
						</tr>
						<tr>
							<td>
								<label for="incidencia">Incidencia</label>
							</td>
							<td>
								<textarea name="incidencia" style="width:300px;height:80px">
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
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addIncidencia">
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

if ($action == "borrarIncidencia") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Incidencia</span>
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
								<span class="field">¿Seguro que deseas eliminar esta incidencia?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteIncidencia">
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


if ($action == "seguimiento") {

	$id = $_GET['id'];

	$sqlUsuarios = "SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";

	$resultUsuarios = $db->query($sqlUsuarios);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir seguimiento</span>
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
								<label for="fecha">Fecha</label>
							</td>
							<td>
                                <input type="datetime-local" name="fecha">
							</td>
						</tr>
						<tr>
							<td>
								<label for="descripcion">Descripción</label>
							</td>
							<td>
								<textarea name="descripcion" style="width:300px;height:80px">
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
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addSeguimiento">
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

if ($action == "borrarSeguimiento") {

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


if ($action == "gasto") {

	$id = $_GET['id'];

	$sqlFases = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlFases.= " WHERE fk_orden = ".$id;

	$resultFases = $db->query($sqlFases);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir gasto</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 280.928px;" class="ui-dialog-content ui-widget-content">
				<div>
				<table>
					<tbody>
						<tr>
							<td>
								<label for="fase" class="fieldrequired">Fase</label>
							</td>
							<td>
								<select class="select-fase" name="fase">
									<option value=-1>&nbsp</option>';

								while ($fase = $db->fetch_object($resultFases)) {

									print '<option value='.$fase->rowid.'>('.$fase->codigo.') - '.$fase->descripcion.'</option>';

								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="descripcion" class="fieldrequired">Descripción</label>
							</td>
							<td>
								<textarea name="descripcion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<label for="stock">Stock</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="stock">
							</td>
						</tr>
						<tr>
							<td>
								<label for="unidades" class="fieldrequired">Unidades</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="unidades">
							</td>
						</tr>
						<tr>
							<td>
								<label for="tipo" class="fieldrequired">Tipo</label>
							</td>
							<td>
								<input type="text" name="tipo">
							</td>
						</tr>
						<tr>
							<td>
								<label for="coste" class="fieldrequired">Coste</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="coste">
							</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addGasto">
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

if ($action == "borrarGasto") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Gasto</span>
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
								<span class="field">¿Seguro que deseas eliminar este gasto?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteGasto">
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
