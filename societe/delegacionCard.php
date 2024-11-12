<?php
ob_start();
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2013  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *  \file       htdocs/societe/contact.php
 *  \ingroup    societe
 *  \brief      Page of contacts of thirdparties
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

	
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';


$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users"));
if (!empty($conf->categorie->enabled)) {
	$langs->load("categories");
}
if (!empty($conf->incoterm->enabled)) {
	$langs->load("incoterm");
}
if (!empty($conf->notification->enabled)) {
	$langs->load("mails");
}

$mesg = ''; $error = 0; $errors = array();

$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel     = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm	= GETPOST('confirm');
$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
if (empty($socid) && $action == 'view') {
	$action = 'create';
}

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycontact', 'globalcard'));

if ($object->fetch($socid) <= 0 && $action == 'view') {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);
if (empty($user->rights->societe->contact->lire)) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = '';
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}


/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$adherent = new Adherent($db);
$formcompany = new FormCompany($db);

if ($socid > 0 && empty($object->id)) {
	$result = $object->fetch($socid);
	if ($result <= 0) {
		dol_print_error('', $object->error);
	}
}

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->name." - ".$langs->trans('Delegaciones');
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if (!empty($object->id)) {
	$res = $object->fetch_optionals();
}
//if ($res < 0) { dol_print_error($db); exit; }


$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'delegaciones', $langs->trans("ThirdParty"), 0, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

print dol_get_fiche_end();

print '<br>';

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

$newcardbutton .= dolGetButtonTitle($langs->trans('Nueva delegación'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/societe/delegacion.php?action=crear&socid='.$socid);

if ($action != "mostrar") {

	print_barre_liste($langs->trans("Listado de delegaciones"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	if (isset($_POST['confirmmassaction'])) {
		$archivoActual = $_SERVER['PHP_SELF'];
		$id = $_POST['id'];
		$idAsociado=$_POST['idAsociado'];
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."delegacion where id=".$id;
		$respuesta = $db->query($sql);
		
		if($respuesta ){
			//setEventMessages( utf8_encode("Delegación") .' eliminada', null, 'mesgs');
			header('delegacionCard.php?socid='.$idAsociado);
			
		}else{
			setEventMessages( 'Error al eliminar la delegación', null, 'errors');
		}
	}

	$countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';

	//print '<div class="fichecenter"><div class="fichethirdleft">';

	//print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';
	$object->country_id = $object->country_id ? $object->country_id : $mysoc->country_id;


	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'">';
	//if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	//print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	//print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$arrayfields = array(
		'codigo_delegacion'=>array('label'=>$langs->trans("Razón Social"), 'checked'=>1),
		'nombre'=>array('label'=>$langs->trans("Nombre"), 'checked'=>1),
		'telefono'=>array('label'=>$langs->trans("Telefono"), 'checked'=>1),
		'fax'=>array('label'=>$langs->trans("Fax"), 'checked'=>1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected=$_POST["selectedfields"];
		$fieldsSelectedArray=explode(",",$fieldsSelected);
		
		$arrayfields = array(
			'codigo_delegacion'=>array('label'=>$langs->trans("Razón Social"), 'checked'=>0),
			'nombre'=>array('label'=>$langs->trans("Nombre"), 'checked'=>0),
			'telefono'=>array('label'=>$langs->trans("Telefono"), 'checked'=>0),
			'fax'=>array('label'=>$langs->trans("Fax"), 'checked'=>1),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"]=1;
		}
		
		
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

	print "
		<div class='div-table-responsive'>
		<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
		<tbody>
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
				/*if (!empty($arrayfields['id']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="number" name="search_id">';
					print '</td>';
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="text" name="search_nombre">';
					print '</td>';
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="text" name="search_responsible_name">';
					print '</td>';
				}
				if (!empty($arrayfields['telefono1']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="number" name="search_tel1">';
					print '</td>';
				}
				if (!empty($arrayfields['telefono2']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="number" name="search_tel2">';
					print '</td>';
				}
				if (!empty($arrayfields['direccion']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth80imp" type="text" name="search_direccion">';
					print '</td>';
				}
				if (!empty($arrayfields['codigo_postal']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
					print '</td>';
				}
				if (!empty($arrayfields['localidad']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
					print '</td>';
				}
				if (!empty($arrayfields['provincia']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					if ($object->country_id) {
						print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
					} else {
						print $countrynotdefined;
					}
					print '</td>';
				}
				if (!empty($arrayfields['pais']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
					print '</td>';
				}
				if (!empty($arrayfields['direccion_material']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="text" name="search_direccion_material">';
					print '</td>';
				}
				if (!empty($arrayfields['direccion_factura']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="text" name="search_direccion_factura">';
					print '</td>';
				}
				if (!empty($arrayfields['tipo_delegacion']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print "<select name='search_tipo_delegacion'>
					<option class='optiongrey' value'' </option>";
					$sql = 'SELECT *  FROM '. MAIN_DB_PREFIX . 'tipo_delegacion';
					$result = $db->query($sql);
					$num = $db->num_rows($result);
					while ($num > 0) {
						$datos = $db->fetch_object($result);
						echo "<option class='optiongrey' value='" . $datos->id. "'";
						// if ($$_POST["tipo_delegacion"] == $datos->nombre) {
						// 	echo " selected";
						// }
						echo " >" . $datos->nombre . " </option> ";
						$num --;
			
					}
					print "</select>";
					print '</td>';
				}
				if (!empty($arrayfields['codigo_delegacion']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="number" name="search_codigo_delegacion">';
					print '</td>';
				}
				if (!empty($arrayfields['codigo_delegacion']['checked'])) {
					print '<td class="wrapcolumntitle liste_titre middle">';
					print '<input class="flat maxwidth75imp" type="number" name="search_codigo_delegacion">';
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
			</tr>*/
			print "</form>
			<tr class='liste_titre'>";
				if (!empty($arrayfields['codigo_delegacion']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Razón Social'>";
					print "<a class='reposition' href=''>Razón Social</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Nombre'>";
					print "<a class='reposition' href=''>Nombre</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Telefono'>";
					print "<a class='reposition' href=''>Telefono</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Fax'>";
					print "<a class='reposition' href=''>Fax</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Direccion'>";
					print "<a class='reposition' href=''>Direccion</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='CP'>";
					print "<a class='reposition' href=''>CP</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Población'>";
					print "<a class='reposition' href=''>Población</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Provincia'>";
					print "<a class='reposition' href=''>Provincia</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Dirección Material'>";
					print "<a class='reposition' href=''>Dirección Material</a>";
					print "</th>";
				}
				if (!empty($arrayfields['nombre']['checked'])) {
					print "<th class='wrapcolumntitle liste_titre' title='Dirección Factura'>";
					print "<a class='reposition' href=''>Dirección Factura</a>";
					print "</th>";
				}
				
				print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
				print "</tr>
			";

			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegacion WHERE 1 and fk_tercero=".$socid;
			/*if (isset($_POST['button_search'])) {
				$delegacion = array();

				if (isset($_POST['search_id']) && ($_POST['search_id']) != "") {
					$id = $_POST['search_id'];
					$sql .=' and id='.$id;
				}

				if (isset($_POST['search_nombre']) && ($_POST['search_nombre']) != "") {
					$nombre = "" . $_POST['search_nombre'] . "";
					$sql .=' and nombre LIKE "%'. $nombre.'%"';
				}

				if (isset($_POST['search_telefono1']) && ($_POST['search_telefono1']) != "") {
					$telefono1 = "'" . $_POST['search_telefono1'] . "'";
					$sql .=' and telef1='.$telefono1;
				}

				if (isset($_POST['search_telefono2']) && ($_POST['search_telefono2']) != "") {
					$telefono2 = "'" . $_POST['search_telefono2'] . "'";
					$sql .=' and telef2='.$telefono2;
				}

				if (isset($_POST['search_localidad']) && ($_POST['search_localidad']) != "") {
					$localidad = "" . $_POST['search_localidad'] . "";
					$sql .=' and localidad LIKE "%'.$localidad.'%"';
				}
				
				if (isset($_POST['search_direccion']) && ($_POST['search_direccion']) != "") {
					$direccion = "" .$_POST['search_direccion'] . "";
					$sql .=' and direccion LIKE "%'.$direccion.'%"';
				}

				if (isset($_POST['zipcode']) && ($_POST['zipcode']) != "") {
					$zipcode = "'" .$_POST['zipcode'] . "'";
					$sql .=' and cp='.$zipcode;
				}

				if (isset($_POST['town']) && ($_POST['town']) != "") {
					$town = "" .$_POST['town'] . "";
					$sql .=' and localidad LIKE "%'.$town.'%"';
				}

				if (isset($_POST['state_id']) && ($_POST['state_id']) != "") {
					$provincia= $_POST['state_id'];
					$sql .=' and provincia='.$provincia;
				}

				if (isset($_POST['country_id']) && ($_POST['country_id']) != "") {
					$pais= $_POST['country_id'];
					$sql .=' and pais='.$pais;
				}

				if (isset($_POST['search_direccion_material']) && ($_POST['search_direccion_material']) != "") {
					$direccion_material = "'" . $_POST['search_direccion_material'] . "'";
					$sql .=' and direccion_material LIKE "%'.$direccion_material.'%"';
				}

				if (isset($_POST['search_direccion_factura']) && ($_POST['search_direccion_factura']) != "") {
					$direccion_factura = "'" . $_POST['search_direccion_factura'] . "'";
					$sql .=' and direccion_factura LIKE "%'.$direccion_factura.'%"';
				}

				if (isset($_POST['search_tipo_delegacion']) && ($_POST['search_tipo_delegacion']) != "") {
					$tipo_delegacion= $_POST['search_tipo_delegacion'];
					$sql .=' and fk_tipo_delegacion='.$tipo_delegacion;
				}

				if (isset($_POST['search_codigo_delegacion']) && ($_POST['search_codigo_delegacion']) != "") {
					$codigo_delegacion= $_POST['codigo_delegacion'];
					$sql .=' and codigo_delegacion="'.$codigo_delegacion.'"';
				}
				
			}*/
			print '<form method="POST" action="" name="formfilter" autocomplete="off">';
			$respuesta = $db->query($sql);
			$num= $db->num_rows($sql);
			$aux = 0;
			while($aux<$num){
				$obj = $db->fetch_object($respuesta);

				/*$sqlProvincia = 'SELECT nom  FROM '. MAIN_DB_PREFIX . 'c_departements WHERE rowid='.$obj->provincia;
				$resultProvincia = $db->query($sqlProvincia);
				$objProvincia = $db->fetch_object($resultProvincia);*/

				$sqlPais = 'SELECT label  FROM '. MAIN_DB_PREFIX . 'c_country WHERE rowid='.$obj->pais;
				$resultPais = $db->query($sqlPais);
				$objPais = $db->fetch_object($resultPais);

				/*$sqlTipoDelegacion = 'SELECT nombre  FROM '. MAIN_DB_PREFIX . 'tipo_delegacion WHERE id='.$obj->fk_tipo_delegacion;
				$resultTipoDelegacion = $db->query($sqlTipoDelegacion);
				$objTipoDelegacion = $db->fetch_object($resultTipoDelegacion);*/

				print "<tr class='oddeven'>";
				print "<input type='hidden' name='id' value='" . $obj->id . "'>";
				print "<input type='hidden' name='nombre' value='" . $obj->nombre . "'>";
				print "<input type='hidden' name='idAsociado' value='" . $socid . "'>";
				if (!empty($arrayfields['codigo_delegacion']['checked']))	print "<td class=' tdoverflowmax200'><a href='".$_SERVER["PHP_SELF"]."?socid=".$socid."&dele=".$obj->id."&action=mostrar'>" . $obj->codigo_delegacion . "</a></td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'><a href='".$_SERVER["PHP_SELF"]."?socid=".$socid."&dele=".$obj->id."&action=mostrar'>" . $obj->nombre . "</a></td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->telef1 . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->telef2 . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->direccion . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->cp . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->localidad . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->provincia . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->direccion_material . "</td> ";
				if (!empty($arrayfields['nombre']['checked']))	print "<td class=' tdoverflowmax200'>" . $obj->direccion_factura . "</td> ";

				print " <td class='nowrap'>";
				print '<a class="editfielda" href="delegacion.php?action=editar&id='.$obj->id.'&socid='.$socid.'">
				<span class="fas fa-pencil-alt" style="color:black;" title="Modificar"></span>
				</a>';
				/*print "<button type='submit' class='' name='delete' style='border: none; background-color:rgb(250,250,250);'>
					<span class='fas fa-trash marginleftonly' style='color: #444;'></span>
				</button>";*/
				print '<a class="editfielda" href="delegacionCard.php?action=delete&id='.$obj->id.'&socid='.$socid.'">'.img_delete().'
				</a></td>';
				print "</tr>";
				$aux++;

			}
	print '</form>';
} else {

	$dele = $_GET['dele'];

	$sql = " SELECT d.*, s.nom FROM ". MAIN_DB_PREFIX ."delegacion d ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."societe s ON s.rowid = d.fk_tercero ";
	$sql.= " WHERE id = ".$dele;

	$resultDele = $db->query($sql);
	$deleg = $db->fetch_object($resultDele);

	print "<table class='border centpercent'>
		<tbody>
			<tr>
				<th colspan=2>DATOS DE LA DELEGACIÓN</th><th></th>
			</tr>
			<tr>
				<th colspan=2></th><th></th>
			</tr>
			<tr>
				<th colspan=2></th><th></th>
			</tr>
			<tr>
				<td>
					<span class='fieldrequired' >NOMBRE DE LA DELEGACIÓN: </span>
				</td>
				<td>
					<input value='".$deleg->nombre."' type='text' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Razón Social: </span>
				</td>
				<td>
					<input value='".$deleg->codigo_delegacion."' type='text' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Tercero: </span>
				</td>
				<td>
					<input type='text' value='".$deleg->nom."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='' >Nombre encargado: </span>
				</td>
				<td>
					<input value = '".$deleg->responsible_name."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >IVA: </span>
				</td>
				<td>
					<input value = '".$deleg->iva."'/>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >CP: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->cp."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Localidad: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->localidad."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Provincia: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->provincia."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Teléfono: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->telef1."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Fax: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->telef2."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Email: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->email."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección facturación: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion_factura."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección materiales: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion_material."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Forma envío: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->forma_envio."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Teléfono transportista: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->tlf_transp."'>
				</td>
			</tr>
			

		</tbody>
	</table>
</div>";
}

if ($action == "delete") {
	$id = $_GET['id'];
	$idAsociado=$_GET['socid'];
	echo '
	<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?socid='.$idAsociado.'" name="formfilter" autocomplete="off">
	<input type="hidden" value="' . $id . '" name=id >
	<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
		<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
			<span id="ui-id-1" class="ui-dialog-title">Eliminar registro</span>
			<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
				<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
				<span class="ui-button-icon-space"> </span>
				Close
			</button>
		</div>
		<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 97.928px;" class="ui-dialog-content ui-widget-content">
			<div class="confirmquestions">
			</div>
			<div class="confirmmessage">
				<img src="/theme/eldy/img/info.png" alt="" style="vertical-align: middle;">
				Está seguro de querer eliminar la delegación?
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<button type="submit" class="ui-button ui-corner-all ui-widget" name="confirmmassaction">
					' ."Sí". '
				</button>
				<button type="submit" class="ui-button ui-corner-all ui-widget">
					No
				</button>
			</div>
		</div>
	</div>
	</form>
';
}
print "
</tbody></table>
</div>
";

print '</form>';

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div></div>';

ob_end_flush();

// End of page
llxFooter();
$db->close();
ob_flush();