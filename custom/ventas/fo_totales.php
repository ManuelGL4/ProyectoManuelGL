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
llxHeader('', $langs->trans('Factura - totales'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = factura_origenPrepareHead($object);

    print dol_get_fiche_head($head, 'datos', $langs->trans("Workstation"), -1, $object->picto);

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

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Totales Factura"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	//print '<table class="tagtable liste">' . "\n";
    
	/*$sqlOfertas = " SELECT rowid, ref, nombre ";
	$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlOfertas.= " WHERE averia = ".$id;

	$resultOferta = $db->query($sqlOfertas);
	$numOfertas = $db->num_rows($resultOferta);
	$ofertalinea = $db->fetch_object($resultOferta);

	if ($numOfertas > 0) {*/

	//}
}

//Cogemos el proyecto de la certificacion
$sqlProyecto = " SELECT fk_proyect FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
$sqlProyecto.= " WHERE rowid = ".$object->certificacion." ";

$resultProyecto = $db->query($sqlProyecto);
$proyecto = $db->fetch_object($resultProyecto);
$proyecto = $proyecto->fk_proyect;

//COMPROBAMOS ANTES SI EXISTEN CERTIFICACIONES ANTERIORES PARA ESE PROYECTO
$sqlTotalCert = " SELECT * FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
$sqlTotalCert.= " WHERE fk_proyect = ".$proyecto." AND rowid < ".$object->certificacion." ";

$resultTotalCert = $db->query($sqlTotalCert);
$numCerts = $db->num_rows($resultTotalCert);

if ($numCerts > 0) {

	$sqlLineasCertificacion = " SELECT cl.*, c.*, p.ref, p.description, SUM(imp_mes) as suma ";
	$sqlLineasCertificacion.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
	$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
	$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
	$sqlLineasCertificacion.= " WHERE c.fk_proyect = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion <= ".$object->certificacion;

} else {

	$sqlLineasCertificacion = " SELECT cl.*, c.*, p.ref, p.description ";
	$sqlLineasCertificacion.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
	$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
	$sqlLineasCertificacion.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
	$sqlLineasCertificacion.= " WHERE c.fk_proyect = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion = ".$object->certificacion;

}

$resultLineasCertificacion = $db->query($sqlLineasCertificacion);
$datos = $db->fetch_object($resultLineasCertificacion);



//PARA SACAR LOS DATOS BIEN
if ($numCerts > 0) {

	$sqlLineasCertificacion2 = " SELECT DISTINCT (cl.cant_mes * pom.taxable_base) as total, cl.*, c.*, p.ref, p.description, pom.price, pom.discount, pom.taxable_base ";
	$sqlLineasCertificacion2.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = cl.fk_producto ";
	$sqlLineasCertificacion2.= " WHERE c.fk_proyect = ".$proyecto." AND pom.fk_project = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion <= ".$object->certificacion;

} else {

	$sqlLineasCertificacion2 = " SELECT DISTINCT (cl.cant_mes * pom.taxable_base) as total, cl.*, c.*, p.ref, p.description, pom.price, pom.discount, pom.taxable_base ";
	$sqlLineasCertificacion2.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
	$sqlLineasCertificacion2.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = cl.fk_producto ";
	$sqlLineasCertificacion2.= " WHERE c.fk_proyect = ".$proyecto." AND pom.fk_project = ".$proyecto." AND cl.cant_mes IS NOT NULL AND fk_certificacion = ".$object->certificacion;
}

$resultLineasCertificacion2 = $db->query($sqlLineasCertificacion2);
$bruto = 0;
while ($datos2 = $db->fetch_object($resultLineasCertificacion2)) {
	$cantidad = $datos2->taxable_base / $datos2->cant_contrato;
	$cantidad2 = $cantidad * $datos2->cant_mes;
	$bruto+= $cantidad2;
}



//Para los descuentos
$descuentoTotal = $object->dto_cliente + $object->dto_factura;

//Para el importe bruto
if ($numCerts > 0) {
	$impBruto = $bruto;
} else {
	$impBruto = $bruto;
}

$fecha_formateada = date("d-m-Y", $object->fecha);

print "
<div  class='tabBarWithBottom'>
<table class='border centpercent'>
    <tbody>
        <tr>
            <td>
                <label class='fieldrequired' >Datos Generales:</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Fecha:</label>
                <input class='right' style='width:258px' readonly type='text' name='ref_anterior' value='".$fecha_formateada."'>
            </td>
        </tr>
		<tr>
			<td>
				<label class='field' >Forma de Pago:</label>
				<input class='right' style='width:200px' readonly type='text' name='ref_anterior' value='".$object->forma_pago."'>
			</td>
		</tr>
        <tr>
        </tr>
    </tbody>
</table>";

print "
<div  class='tabBar tabBar'>
<table class='border centpercent'>
    <tbody>
        <tr>
            <td>
                <label class='fieldrequired' >Datos Totales:</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Bruto</label>
                <input class='right' style='width:162px' readonly type='number' step=0.01 value='".$impBruto."'>
            </td>
        </tr>
        <tr>
        </tr>
    </tbody>
</table>";

//Para las deducciones
$sqlDeduc = " SELECT f.*, c.imp_mes_total FROM ".MAIN_DB_PREFIX."ventas_factura_origen f ";
$sqlDeduc.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = f.certificacion ";
$sqlDeduc.= " WHERE f.certificacion < ".$object->certificacion." AND fk_proyect = ".$proyecto." ";

$resultDeduc = $db->query($sqlDeduc);
$numDeduc = $db->num_rows($resultDeduc);

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;

$i = 1;
$numTotal = $numDeduc;
$num_results_on_page = 5;
$start = ($page-1) * $num_results_on_page;

print "
<table class='border centpercent' style='width:1000px;background:grey;color:white;'>";
print "<tbody>
		<tr style='text-align:center'>
			<th colspan=2>
				<label class='field' >DEDUCCIONES</label>
			</th>
		</tr>
        <tr>
            <th>
                <label class='field' >CONCEPTO</label>
            </th>
            <th>
                <label class='field' >TOTAL</label>
            </th>
        </tr>";

		$totalDeduccion = 0;

		if ($numDeduc > 0) {
			while ($deduc = $db->fetch_object($resultDeduc)) {
				print "<tr>
						<td class='center'>
							<input class='center' style='width:100%' readonly type='text' name='garantia' value='A deducir N/FRA.".$deduc->ref." (".$deduc->fecha.")'>
						</td>

						<td class='center'>
							<input class='center' style='width:98%' readonly type='text' name='garantia' value='".strtr(number_format($deduc->imp_mes_total,2),['.' => ',', ',' => '.'])." €'>
						</td>
				</tr>";

				$totalDeduccion+= $deduc->imp_mes_total;

			}
		} else {
			print "<tr>
					<td class='center' colspan=2>
						<input readonly style='width:99%' class='center' type='text' name='garantia' value='No hay deducciones'>
					</td>
				</tr>";
		}

print "</table>";

/*print '<div style="margin-left:40%;margin-top:5px">';
for ($pagina = 1; $pagina <= ceil($numTotal / $num_results_on_page); $pagina++) {
    if ($pagina == $page) {
        print '<a style="font-size:20px;font-weight:bold;" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&page='.$pagina.'">'.$pagina.'</a>';
    } else {
        print '<a style="font-size:15px;" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&page='.$pagina.'">'.$pagina.'</a>';
    }
    print '&nbsp';
}
print '</div>';*/

/*print "<div class='tabsAction'>";
print "<a class='butAction' type='button' style='margin-bottom:0px !important' href='". $_SERVER["PHP_SELF"] ."?action=edit&id=".$id."'>Modificar porcentaje</a>";
print '</div>';*/

//Para base imponible
$baseImponible = $impBruto;

$aDescontar = (($baseImponible * $descuentoTotal) / 100) + $object->dto_pp;
$descuentos = $baseImponible - $aDescontar;

$baseImponible = $baseImponible - $aDescontar;
$baseImponible = $baseImponible  - $totalDeduccion;

//Cogemos el IVA, que viene de la delegacion
$sqlDelegacion = " SELECT iva FROM ".MAIN_DB_PREFIX."delegacion ";
$sqlDelegacion.= " WHERE id = ".$object->delegacion." ";

$resultDelegacion = $db->query($sqlDelegacion);
$delegacion = $db->fetch_object($resultDelegacion);

$iva = $delegacion->iva;
$impuestos = ($baseImponible * $iva) / 100;
$totalFactura = $baseImponible + $impuestos;

if ($object->retencion_pagada == 0) {
	$impRetencion = ($totalFactura * $object->retencion) / 100 ;
} else {
	$impRetencion = 0 ;
}
$totalPagar = $totalFactura - $impRetencion;

$retencion = $object->retencion;

print "<br>
<br>
<div  class='tabBar' >
    <table class='border centpercent'>
		<tr>
			<td>
				<label class='field' >Deducciones</label>
				<input class='right' style='width:120px' readonly type='number' step=0.01 name='garantia' value='".$totalDeduccion."'>
			</td>
		</tr>
		<tr>
			<td>
				<label class='field' >Descuento Total (%)</label>
				<input class='right' style='width:72px' readonly type='number' step=0.01 name='garantia' value='".number_format($descuentoTotal,2)."'>
			</td>
		</tr>
		<tr>
			<td>
				<label class='field' >Descuento (+ PP)</label>
				<input class='right' style='width:88px' readonly type='number' step=0.01 name='garantia' value='".number_format($aDescontar,2)."'>
			</td>
		</tr>
        <tr>
            <td>
                <label class='field' style='font-weight:bold'>Base Imponible:</label>
                <input class='right' style='width:92px' readonly type='number' step=0.01 name='suma' value='".number_format($baseImponible,2)."'>
            </td>
        </tr>
		<tr>
			<td>
				<label class='field' >IVA (%):</label>
				<input class='right' style='width:150px' readonly type='number' step=0.01 name='dto_pp'  value='".number_format($iva,2)."'>
			</td>
		</tr>
        <tr>
            <td>
                <label class='field' >Impuestos:</label>
                <input class='right' style='width:132px' readonly type='number' step=0.01 name='dto_pp'  value='".number_format($impuestos,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' style='font-weight:bold'>Total Factura:</label>
                <input class='right' style='width:110px' readonly type='number' step=0.01 name='dto_cliente'  value='".number_format($totalFactura,2)."'>
            </td>
        </tr>
		<tr>
			<td>
				<label class='field' >Retención (%):</label>
				<input class='right' style='width:108px' readonly type='number' step=0.01 name='dto_oferta'  value='".number_format($retencion,2)."'>
			</td>
		</tr>
        <tr>
            <td>
                <label class='field' >Importe Retención:</label>
                <input class='right' style='width:80px' readonly type='number' step=0.01 name='dto_oferta'  value='".number_format($impRetencion,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' style='font-weight:bold'>Total a Pagar:</label>
                <input class='right' style='width:107px' readonly type='number' step=0.01 name='subtotal'  value='".number_format($totalPagar,2)."'>
            </td>
        </tr>
    </tbody>
</table>
";
print '</div>';

print "<div class='tabsAction'>";
//print "<a class='butAction' type='button' style='margin-bottom:0px !important' href='". $_SERVER["PHP_SELF"] ."?action=edit&id=".$id."'>Imprimir Factura</a>";
print dolGetButtonAction($langs->trans('Imprimir Factura'), '', 'default', 'printFO.php?id='.$object->id.'');
print '</div>';

print dol_get_fiche_end();

//COMPROBAMOS SI EXISTE YA UN REGISTRO CON LOS DATOS DE ESTA FACTURA
$sqlFacturaDatos = " SELECT * FROM ".MAIN_DB_PREFIX."ventas_factura_origen_datos ";
$sqlFacturaDatos.= " WHERE fk_factura = ".$object->id;

$resultFacturaDatos = $db->query($sqlFacturaDatos);
$numDatos = $db->num_rows($resultFacturaDatos);

if ($numDatos == 0) {

	//SI NO HAY, INSERTAMOS
	$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."ventas_factura_origen_datos ";
	$sqlInsert.= " (fk_factura, bruto, descuento, deducciones, base_imponible, impuestos, total_factura, imp_retencion, total_pagar) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($object->id, $impBruto, $aDescontar, $totalDeduccion, $baseImponible, $impuestos, $totalFactura, $impRetencion, $totalPagar) ";

	$db->query($sqlInsert);

} else {

	//SI LO HAY, ACTUALIZAMOS
	$sqlUpdate.= " UPDATE ".MAIN_DB_PREFIX."ventas_factura_origen_datos ";
	$sqlUpdate.= " SET bruto = $impBruto, ";
	$sqlUpdate.= " descuento = $aDescontar, ";
	$sqlUpdate.= " deducciones = $totalDeduccion, ";
	$sqlUpdate.= " base_imponible = $baseImponible, ";
	$sqlUpdate.= " impuestos = $impuestos, ";
	$sqlUpdate.= " total_factura = $totalFactura, ";
	$sqlUpdate.= " imp_retencion = $impRetencion, ";
	$sqlUpdate.= " total_pagar = $totalPagar ";
	$sqlUpdate.= " WHERE fk_factura = ".$object->id;

	$db->query($sqlUpdate);

}


/*if ($action == "addLinea") {

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

								while ($material2 = $db->fetch_object($resultMateriales2)) {
                                    print '<option value='.$material2->rowid.'>( ) - '.$material2->codhijo.' - '.$material2->label.'</option>';
                                }

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
print '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />';*/

// End of page
llxFooter();
$db->close();