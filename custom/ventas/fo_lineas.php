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
 *  \file       factura_origen_note.php
 *  \ingroup    ventas
 *  \brief      Tab for notes on Factura_origen
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

dol_include_once('/ventas/class/factura_origen.class.php');
dol_include_once('/ventas/lib/ventas_factura_origen.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("ventas@ventas", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Factura_origen($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->ventas->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('factura_origennote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->ventas->multidir_output[$object->entity]."/".$object->id;
}

$permissionnote = $user->rights->ventas->factura_origen->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->ventas->factura_origen->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->ventas->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */






/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Factura - líneas'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = factura_origenPrepareHead($object);

    print dol_get_fiche_head($head, 'lineas', $langs->trans("Workstation"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/ventas/factura_origen_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
        'iva' => array('label' => $langs->trans("Iva"), 'checked' => 1),
		'precio' => array('label' => $langs->trans("Precio"), 'checked' => 1),
		'dto' => array('label' => $langs->trans("Dto"), 'checked' => 1),
        'importe' => array('label' => $langs->trans("Importe"), 'checked' => 1),
		'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'descripcion' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'cantidad' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
            'iva' => array('label' => $langs->trans("Iva"), 'checked' => 0),
            'precio' => array('label' => $langs->trans("Precio"), 'checked' => 0),
            'dto' => array('label' => $langs->trans("Dto"), 'checked' => 0),
            'importe' => array('label' => $langs->trans("Importe"), 'checked' => 0),
			'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 0),
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
	print_barre_liste($langs->trans("Lineas Factura"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

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

    if (!empty($arrayfields['iva']['checked'])) {
		print "<th class='center liste_titre' title='Iva'>";
		print "<a class='reposition' href=''>Iva</a>";
		print "</th>";
	}

	if (!empty($arrayfields['precio']['checked'])) {
		print "<th class='center liste_titre' title='Precio Uni.'>";
		print "<a class='reposition' href=''>Precio Uni.</a>";
		print "</th>";
	}

	if (!empty($arrayfields['dto']['checked'])) {
		print "<th class='center liste_titre' title='Dto Total'>";
		print "<a class='reposition' href=''>Dto. Total</a>";
		print "</th>";
	}

    if (!empty($arrayfields['importe']['checked'])) {
		print "<th class='center liste_titre' title='Importe Total'>";
		print "<a class='reposition' href=''>Importe Total</a>";
		print "</th>";
	}

	if (!empty($arrayfields['porcentaje']['checked'])) {
		print "<th class='center liste_titre' title='Porcentaje'>";
		print "<a class='reposition' href=''>Porcentaje</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	//Cogemos el IVA, que viene de la delegacion
	$sqlDelegacion = " SELECT iva FROM ".MAIN_DB_PREFIX."delegacion ";
	$sqlDelegacion.= " WHERE id = ".$object->delegacion." ";

	$resultDelegacion = $db->query($sqlDelegacion);
	$delegacion = $db->fetch_object($resultDelegacion);

	//Cogemos el proyecto de la certificacion
	$sqlProyecto = " SELECT fk_proyect FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
	$sqlProyecto.= " WHERE rowid = ".$object->certificacion." ";

	$resultProyecto = $db->query($sqlProyecto);
	$proyecto = $db->fetch_object($resultProyecto);
	$proyecto = $proyecto->fk_proyect;

    //SACAMOS EL IVA Y LOS DESCUENTOS
    $sqlDescontar = " SELECT porc_iva FROM ".MAIN_DB_PREFIX."societe_extrafields ";
    $sqlDescontar.= " WHERE fk_object = ".$object->cliente." ";

    $resultDescontar = $db->query($sqlDescontar);
    $descontar = $db->fetch_object($resultDescontar);

	if ($delegacion->iva != "") {
		$porc_iva = $delegacion->iva;
	} else {
		if ($descontar->porc_iva == "") {
			$porc_iva = 0;
		} else {
			$porc_iva = $descontar->porc_iva;
		}
	}


    if (($object->dto_factura == "") || ($object->dto_factura == 0)) {
        $dto_oferta = 0;
    } else {
        $dto_oferta = $object->dto_factura;
    }

    if (($object->dto_cliente == "") || ($object->dto_cliente == 0)) {
        $dto_cliente = 0;
    } else {
        $dto_cliente = $object->dto_cliente;
    }

    $descuentos = $dto_oferta + $dto_cliente;

	//COMPROBAMOS ANTES SI EXISTEN CERTIFICACIONES ANTERIORES PARA ESE PROYECTO
	$sqlTotalCert = " SELECT * FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
	$sqlTotalCert.= " WHERE fk_proyect = ".$proyecto." AND rowid < ".$object->certificacion." ";

	$resultTotalCert = $db->query($sqlTotalCert);
	$numCerts = $db->num_rows($resultTotalCert);

	//Si hay certificados anteriores, los mostramos todos, sino, significa que este es el primero que se hace, y mostramos solo ese
	if ($numCerts > 0) {

		$sqlLineasCertificacion = " SELECT DISTINCT cl.*, c.*, p.ref, p.description, pom.price, pom.discount, pom.taxable_base ";
		$sqlLineasCertificacion.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = cl.fk_producto ";
		$sqlLineasCertificacion.= " WHERE c.fk_proyect = ".$proyecto." AND pom.fk_project = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion <= ".$object->certificacion;

	} else {

		$sqlLineasCertificacion = " SELECT DISTINCT cl.*, c.*, p.ref, p.description, pom.price, pom.discount, pom.taxable_base ";
		$sqlLineasCertificacion.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
		$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = cl.fk_producto ";
		$sqlLineasCertificacion.= " WHERE c.fk_proyect = ".$proyecto." AND pom.fk_project = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion = ".$object->certificacion;
	}
	
	$resultLineasCertificacion = $db->query($sqlLineasCertificacion);

    while ($linea = $db->fetch_object($sqlLineasCertificacion)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['codigo']['checked']))
			print "<td class='center' tdoverflowmax200'><a href='../../mrp/mo_card.php?id=".$linea->rowid."'>".$linea->ref."</a></td>";

		if (!empty($arrayfields['descripcion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$linea->description."</td> ";

		if (!empty($arrayfields['cantidad']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($linea->cant_mes,2),['.' => ',', ',' => '.'])."</td> "; 

        if (!empty($arrayfields['iva']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($porc_iva,2),['.' => ',', ',' => '.'])." %</a></td>";


		if (($linea->discount > 0)) {

			$descontar = ($linea->price * $linea->discount) / 100;
			$linea->price = $linea->price - $descontar;

		}

		if (!empty($arrayfields['precio']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($linea->price,2),['.' => ',', ',' => '.'])." €</td> ";

		if (!empty($arrayfields['dto']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($descuentos,2),['.' => ',', ',' => '.'])." %</td> "; 

		$cantIva = ($linea->imp_mes * $porc_iva) / 100;
		$linea->imp_mes = $linea->imp_mes + $cantIva;

        if (!empty($arrayfields['importe']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($linea->imp_mes,2),['.' => ',', ',' => '.'])." €</td> "; 

		if (!empty($arrayfields['importe']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($linea->porcentaje,2),['.' => ',', ',' => '.'])." %</td> "; 

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>';
						//<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrarLinea&id=' . $object->id . '&rowid=' . $linea->rowid . '">' . img_delete() . '</a>		
					print '</td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;
		
	}
	print "</table>";

	print '</div>';

	/*print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'.$_SERVER["PHP_SELF"].'?action=addLinea&id='.$id.'">Nueva Línea de Oferta</a>';
	print '</div>';*/
}

    print '</div>';

	print dol_get_fiche_end();


if ($action == "addLinea") {

	$sqlLineasOferta = " SELECT m.rowid, m.ref, m.label, m.qty ";
	$sqlLineasOferta.= " FROM ".MAIN_DB_PREFIX."mrp_mo m ";
	$sqlLineasOferta.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ON en.fk_mo = m.rowid ";
	$sqlLineasOferta.= " WHERE en.fk_averia = ".$id." AND en.added IS NULL ";

	$resultLineas = $db->query($sqlLineasOferta);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
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
								<label for="linea">Material</label>
							</td>
							<td>
                                <select name="linea" class="select-linea">
                                <option value=-1>&nbsp</option>';

                                while ($material = $db->fetch_object($resultLineas)) {
                                    print '<option value='.$material->rowid.'>'.$material->ref.' - '.$material->label.'</option>';
                                }

								/*while ($material2 = $db->fetch_object($resultMateriales2)) {
                                    print '<option value='.$material2->rowid.'>( ) - '.$material2->codhijo.' - '.$material2->label.'</option>';
                                }*/

                                print '</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addLineaFinal">
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
