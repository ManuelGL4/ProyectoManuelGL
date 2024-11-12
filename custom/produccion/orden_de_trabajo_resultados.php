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

	print dol_get_fiche_head($head, 'resultados', '', -1, $object->picto);

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

	$arrayfieldsMateriales = array(
		'fase' => array('label' => $langs->trans("Fase"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("Articulo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'num_serie' => array('label' => $langs->trans("Nº Serie"), 'checked' => 1),
		//'lote' => array('label' => $langs->trans("Lote"), 'checked' => 1),
		'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
		//'coste' => array('label' => $langs->trans("Coste/Uds"), 'checked' => 1),
		'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
		'generado' => array('label' => $langs->trans("Generado"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsMateriales = array(
			'fase' => array('label' => $langs->trans("Fase"), 'checked' => 1),
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
			'articulo' => array('label' => $langs->trans("Articulo"), 'checked' => 1),
			'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
			'num_serie' => array('label' => $langs->trans("Nº Serie"), 'checked' => 1),
			//'lote' => array('label' => $langs->trans("Lote"), 'checked' => 1),
			'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
			//'coste' => array('label' => $langs->trans("Coste/Uds"), 'checked' => 1),
			'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
			'generado' => array('label' => $langs->trans("Generado"), 'checked' => 1),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsMateriales[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsMateriales, $varpage); // This also change content of $arrayfieldsMateriales



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

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Materiales generados"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsMateriales['fase']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_fase">';
		print '</td>';
	}
	if (!empty($arrayfieldsMateriales['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_codigo">';
		print '</td>';
	}
	if (!empty($arrayfieldsMateriales['articulo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_articulo">';
		print '</td>';
	}

	if (!empty($arrayfieldsMateriales['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_descripcion'>";
		print '</td>';
	}
	if (!empty($arrayfieldsMateriales['num_serie']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_num_serie">';
		print '</td>';
	}

	/*if (!empty($arrayfieldsMateriales['lote']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_lote'>";
		print '</td>';
	}*/
	if (!empty($arrayfieldsMateriales['cantidad']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_cantidad">';
		print '</td>';
	}

	/*if (!empty($arrayfieldsMateriales['coste']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_coste'>";
		print '</td>';
	}*/
	if (!empty($arrayfieldsMateriales['fecha']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fecha">';
		print '</td>';
	}

	if (!empty($arrayfieldsMateriales['generado']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_generado'>";
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

	if (!empty($arrayfieldsMateriales['fase']['checked'])) {
		print "<th class='center liste_titre' title='Fase'>";
		print "<a class='reposition' href=''>Fase</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsMateriales['codigo']['checked'])) {
		print "<th class='center liste_titre' title='Código'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsMateriales['articulo']['checked'])) {
		print "<th class='center liste_titre' title='Artículo'>";
		print "<a class='reposition' href=''>Artículo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsMateriales['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripción'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsMateriales['num_serie']['checked'])) {
		print "<th class='center liste_titre' title='Nº serie'>";
		print "<a class='reposition' href=''>Nº serie</a>";
		print "</th>";
	}

	/*if (!empty($arrayfieldsMateriales['lote']['checked'])) {
		print "<th class='center liste_titre' title='Lote'>";
		print "<a class='reposition' href=''>Lote</a>";
		print "</th>";
	}*/

	if (!empty($arrayfieldsMateriales['cantidad']['checked'])) {
		print "<th class='center liste_titre' title='Cantidad'>";
		print "<a class='reposition' href=''>Cantidad</a>";
		print "</th>";
	}

	/*if (!empty($arrayfieldsMateriales['coste']['checked'])) {
		print "<th class='center liste_titre' title='Coste'>";
		print "<a class='reposition' href=''>Coste</a>";
		print "</th>";
	}*/

	if (!empty($arrayfieldsMateriales['fecha']['checked'])) {
		print "<th class='center liste_titre' title='Fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsMateriales['generado']['checked'])) {
		print "<th class='center liste_titre' title='Generado'>";
		print "<a class='reposition' href=''>Generado</a>";
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

		if (isset($_POST['search_fase']) && ($_POST['search_fase']) != "") {
			$fase = "" . $_POST['search_fase'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
			$codigo = "" . $_POST['search_codigo'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_articulo']) && ($_POST['search_articulo']) != "") {
			$articulo = "" . $_POST['search_articulo'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$descripcion = "'" . $_POST['search_descripcion'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}

		if (isset($_POST['search_num_serie']) && ($_POST['search_num_serie']) != "") {
			$num_serie = "" . $_POST['search_num_serie'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		/*if (isset($_POST['search_lote']) && ($_POST['search_lote']) != "") {
			$lote = "'" . $_POST['search_lote'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}*/

		if (isset($_POST['search_cantidad']) && ($_POST['search_cantidad']) != "") {
			$cantidad = "" . $_POST['search_cantidad'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		/*if (isset($_POST['search_coste']) && ($_POST['search_coste']) != "") {
			$coste = "'" . $_POST['search_coste'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}*/

		if (isset($_POST['search_fecha']) && ($_POST['search_fecha']) != "") {
			$fecha = "" . $_POST['search_fecha'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_generado']) && ($_POST['search_generado']) != "") {
			$generado = "'" . $_POST['search_generado'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i < $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		if (!empty($arrayfieldsMateriales['fase']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->id_asoc . "</td> ";

		if (!empty($arrayfieldsMateriales['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->firstname . "</td> ";

		if (!empty($arrayfieldsMateriales['articulo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if (!empty($arrayfieldsMateriales['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->lastname . "</td> ";

		if (!empty($arrayfieldsMateriales['num_serie']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		//if (!empty($arrayfieldsMateriales['lote']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->lastname . "</td> ";

		if (!empty($arrayfieldsMateriales['cantidad']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		//if (!empty($arrayfieldsMateriales['coste']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->lastname . "</td> ";

		if (!empty($arrayfieldsMateriales['fecha']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if (!empty($arrayfieldsMateriales['generado']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->lastname . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";
		$i++;
	}
	print "</table>";

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=addAGenerado&id='.$id.'">Nuevo material generado</a>';
	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
