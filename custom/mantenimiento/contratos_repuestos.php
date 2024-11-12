<?php
ob_start();
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

dol_include_once('/mantenimiento/class/contratos.class.php');
dol_include_once('/mantenimiento/lib/mantenimiento_contratos.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mantenimiento@mantenimiento", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Contratos($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mantenimiento->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('informesrepuestos', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->mantenimiento->multidir_output[$object->entity]."/".$object->id;
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

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Precios Repuestos'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = contratosPrepareHead($object);

	print dol_get_fiche_head($head, 'preciorepuestos', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/mantenimiento/contratos_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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
	print '</div>';

	print dol_get_fiche_end();

	//A partir de aquí se pintan tablas y demás
	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	$arrayfields = array(
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
		'mantenido' => array('label' => $langs->trans("Mantenido"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
			'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
			'mantenido' => array('label' => $langs->trans("Mantenido"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$sql = "SELECT r.rowid, r.fk_product, SUM(r.quantity) as cantidad, r.mantenido, p.ref, p.label, p.description ";
	$sql.= "FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_repuestos r ";
	$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = r.fk_product ";
	$sql.= "WHERE r.fk_contract = ".$id." ";
	$sql.= "GROUP BY fk_product ";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

		$newcardbutton = '';
		
		$newcardbutton .= dolGetButtonTitle($langs->trans('Nueva Línea'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$id.'&action=add');

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		print_barre_liste($langs->trans("Repuestos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste">' . "\n";

		print "
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
		if (!empty($arrayfields['codigo']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="number" name="search_codigo">';
			print '</td>';
		}
		if (!empty($arrayfields['nombre']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_nombre">';
			print '</td>';
		}
		if (!empty($arrayfields['descripcion']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
			print '</td>';
		}
		if (!empty($arrayfields['cantidad']['checked'])) {
			print '<td class="center liste_titre center">';
			print '<input class="flat maxwidth75imp" type="text" name="search_cantidad">';
			print '</td>';
		}

		if (!empty($arrayfields['mantenido']['checked'])) {
			print '<td class="center liste_titre center">';
			print "<input class='flat maxwidth175imp' type='number' name='search_mantenido'>";
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

		if (!empty($arrayfields['codigo']['checked'])) {
			print "<th class='center liste_titre' title='codigo'>";
			print "<a class='reposition' href=''>Código</a>";
			print "</th>";
		}

		if (!empty($arrayfields['nombre']['checked'])) {
			print "<th class='center liste_titre' title='nombre'>";
			print "<a class='reposition' href=''>Nombre</a>";
			print "</th>";
		}

		if (!empty($arrayfields['descripcion']['checked'])) {
			print "<th class='center liste_titre' title='descripcion'>";
			print "<a class='reposition' href=''>Descripción</a>";
			print "</th>";
		}

		if (!empty($arrayfields['cantidad']['checked'])) {
			print "<th class='center liste_titre' title='cantidad'>";
			print "<a class='reposition' href=''>Cantidad</a>";
			print "</th>";
		}

		if (!empty($arrayfields['mantenido']['checked'])) {
			print "<th class='center liste_titre' title='incluido'>";
			print "<a class='reposition' href=''>Incluido</a>";
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

			if (isset($_POST['search_nombre']) && ($_POST['search_nombre']) != "") {
				$nombre = "" . $_POST['search_nombre'] . "";
				$sql .= ' and firstname like "%' . $nombre . '%"';
			}

			if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
				$apellidos = "" . $_POST['search_descripcion'] . "";
				$sql .= ' and lastname like "%' . $apellidos . '%"';
			}

			if (isset($_POST['search_cantidad']) && ($_POST['search_cantidad']) != "") {
				$apellidos = "" . $_POST['search_cantidad'] . "";
				$sql .= ' and lastname like "%' . $apellidos . '%"';
			}

			if (isset($_POST['search_mantenido']) && ($_POST['search_mantenido']) != "") {
				$fecha = "'" . $_POST['search_mantenido'] . "'";
				$sql .= ' and DATE(datec)=' . $fecha;
			}
		}	

		print '<form method="POST" action="" name="formfilter" autocomplete="off">';
		$i = 0;

		//MODIFICAR AQUI
		//MODIFICAR AQUI
		while ($i < $num) {

			$datos = $db->fetch_object($result);
			print '<tr class="oddeven">';

			$mantenido=($datos->mantenido == 0) ? "No" : "Si";

			if (!empty($arrayfields['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->ref . "</td> ";

			if (!empty($arrayfields['nombre']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->label . "</td> ";

			if (!empty($arrayfields['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->description . "</td> ";

			if (!empty($arrayfields['cantidad']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->cantidad . "</td> ";

			if (!empty($arrayfields['mantenido']['checked']))	print "<td class='center' tdoverflowmax200'>" . $mantenido . "</td> ";

			print '<td class="center"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'&idDelete=' . $datos->rowid . '">' . img_delete() . '</a></td>';
			
			print "</tr>";
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	} else {
		dol_print_error($db);
	}


}

if ($_GET["action"]=="add") {

	$sqlProducts = "SELECT rowid, label, ref FROM ".MAIN_DB_PREFIX."product";
	$resultProducts = $db->query($sqlProducts);
	
	print '
	<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $id . '" name=id >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Nueva Línea</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 97.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Producto</span>
							</td>
							<td>
								<select class="select-products" style="width: 200px" name="product" id="">';
									while ($product = $db->fetch_object($resultProducts)) {
										
										print' <option value="'.$product->rowid.'">'.$product->ref.' - '.$product->label.'</option>';
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
								<input type="number" name="quantity" min="1">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="add">
						Sí
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						No
					</button>
				</div>
			</div>
		</div>
	</form>

	';
}elseif($_GET["action"]=="delete"){

	$idDelete=$_GET["idDelete"];

	$sqlDeleteProducts = "DELETE FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_repuestos WHERE rowid=".$idDelete;
	$resultDeleteProducts = $db->query($sqlDeleteProducts);

	setEventMessages("Línea borrada", null, 'mesgs');

	header('Location: contratos_repuestos.php?id='.$id.'');

}

if (isset($_POST['add'])) {
	$contrat_id = $_POST['id'];
	$product_id=$_POST['product'];
	$quantity = $_POST['quantity'];

	$sqlMantenido = " SELECT mantenimiento FROM ".MAIN_DB_PREFIX."product_extrafields pe ";
	$sqlMantenido.= " WHERE fk_object = ".$product_id." ";

	$resultMantenido = $db->query($sqlMantenido);
	$mantenido = $db->fetch_object($resultMantenido);

	$sqlInsertProducts = "INSERT INTO ".MAIN_DB_PREFIX."mantenimiento_contratos_repuestos ( fk_contract, fk_product, quantity, mantenido ) VALUES ( ".$contrat_id.",".$product_id.",".$quantity.", ".$mantenido->mantenimiento." )";
	$resultInsertProducts = $db->query($sqlInsertProducts);

	setEventMessages("Línea creada", null, 'mesgs');

	header('Location: contratos_repuestos.php?id='.$contrat_id.'');
}	

print "
<script>
	$(document).ready(function() {
		$('.select-products').select2();
	});
</script>
";

// End of page
llxFooter();
$db->close();
ob_flush();
