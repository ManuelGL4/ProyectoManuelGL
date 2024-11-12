<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       averias_ofertas_agenda.php
 *  \ingroup    averiasreparaciones
 *  \brief      Tab of events on Averias_ofertas
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

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/averiasreparaciones/class/averias_ofertas.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias_ofertas.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("averiasreparaciones@averiasreparaciones", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$consulta = " SELECT averia FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
$consulta.= " WHERE rowid = ".$id;

$resultConsulta = $db->query($consulta);
$idAveria = $db->fetch_object($resultConsulta);
$idAveria = $idAveria->averia;

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Initialize technical objects
$object = new Averias_ofertas($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->averiasreparaciones->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('averias_ofertasagenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->averiasreparaciones->multidir_output[$object->entity]."/".$object->id;
}

$permissiontoadd = $user->rights->averiasreparaciones->averias_ofertas->write; // Used by the include of actions_addupdatedelete.inc.php

const CONTROL = [
    '0' => 'Equipo en Cliente',
    '1' => 'Reparación sin Presupuesto',
    '2' => 'Reparación externa',
    '3' => 'Reparación en taller',
];


/*
 *  Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode = '';
		$search_agenda_label = '';
	}
}

if (isset($_POST['editPorcentaje'])) {

    extract($_POST);

    $sqlPorcent = " UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
    $sqlPorcent.= " SET porcent_res = ".$porcentaje." ";
    $sqlPorcent.= " WHERE rowid = ".$id;

    $db->query($sqlPorcent);

}


/*
 *	View
 */

$form = new Form($db);

if ($object->id > 0) {
	$title = $langs->trans("Otros Datos");
	//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
	$help_url = 'EN:Module_Agenda_En';
	llxHeader('', $title, $help_url);

	if (!empty($conf->notification->enabled)) {
		$langs->load("mails");
	}
	$head = averias_ofertasPrepareHead($object);


	print dol_get_fiche_head($head, 'otrosdatos', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/averiasreparaciones/averias_ofertas_materiales.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	// Project
	if (! empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		if ($permissiontoadd) {
			if ($action != 'classify') {
				//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			}
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

	$object->info($object->id);
	//dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '&origin='.urlencode($object->element.'@'.$object->module).'&originid='.urlencode($object->id);
	$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);
	$permok = $user->rights->agenda->myactions->create;
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode($objthirdparty->id);
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '').'&percentage=-1';
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}

//REFERENCIA ANTERIOR
//Sacamos primero el contrato
$sqlContrato = " SELECT contract_id FROM ". MAIN_DB_PREFIX ."mantenimiento_informes mi ";
$sqlContrato.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
$sqlContrato.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ao ON ao.averia = a.rowid ";
$sqlContrato.= " WHERE ao.rowid = ".$id;

$resultContrato = $db->query($sqlContrato);
$contrato = $db->fetch_object($resultContrato);
$contrato = $contrato->contract_id;

$garantia = "-";
if ($contrato != "") {

    $sqlGarantia = " SELECT warranty_end FROM ". MAIN_DB_PREFIX ."mantenimiento_contratos ";
    $sqlgarantia = " WHERE rowid = ".$contrato;

    $resultGarantia = $db->query($sqlGarantia);
    $garantia = $db->fetch_object($resultGarantia);
    $garantia = $garantia->warranty_end;
    
    $formateada = DateTime::createFromFormat('Y-m-d H:i:s', $garantia);
    $garantia = $formateada->format('d-m-Y H:i:s');

}

/*$referencia = "-";
if ($contrato == "") {

    $contratoFinal = "";

} else {

    $contratoFinal = $contrato;
    
    //Comprobamos cuantos informes hay para ese contrato
    $sqlNumCont = " SELECT rowid FROM ". MAIN_DB_PREFIX ."mantenimiento_informes ";
    $sqlNumCont.= " WHERE contract_id = ".$contratoFinal;

    $resultNumCont = $db->query($sqlNumCont);
    $numCont = $db->num_rows($resultNumCont);
    
    //Si hay más de 1
    $listaInformes = [];
    if ($numCont > 1) {

        $informes = "";
        while ($informe = $db->fetch_object($resultNumCont)) {
            $listaInformes[] = $informe->rowid;
            
            if ($informes == "") {
                $informes = $informe->rowid;
            } else {
                $informes.= ", ".$informe->rowid;
            }

        }

        $sqlAverias = " SELECT rowid FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias "; 
        $sqlAverias.= " WHERE fk_informe IN ($informes) AND rowid <> ".$idAveria." ";
        $sqlAverias.= " ORDER BY rowid DESC ";
        $sqlAverias.= " LIMIT 1";

        print $sqlAverias;
        die;

        $resultAverias = $db->query($sqlAverias);
        $averia = $db->fetch_object($resultAverias);
        $averiaFinal = $averia->rowid;

        $sqlOferta = " SELECT ref FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
        $sqlOferta.= " WHERE averia = ".$averiaFinal;

        print $sqlOferta;
        die;

        $resultOferta = $db->query($sqlOferta);
        $oferta = $db->fetch_object($resultOferta);
        
        $referencia = $oferta->ref;
    }

}*/

//MODALIDAD

$sqlModalidad = " SELECT a.control FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ao ";
$sqlModalidad.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias a ON a.rowid = ao.averia ";
$sqlModalidad.= " WHERE ao.rowid = ".$id;

$resultModalidad = $db->query($sqlModalidad);
$modalidad = $db->fetch_object($resultModalidad);
$modalidad = CONTROL[$modalidad->control];

//GARANTIA

//PORCENTAJE RESOLUCION
if ($object->porcent_res == "") {
    $porcentaje = 1;

    $sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
    $sqlUpdate.= " SET porcent_res = ".$porcentaje." ";
    $sqlUpdate.= " WHERE rowid = ".$id;

    $db->query($sqlUpdate);
}

//RECOGEMOS TODOS LOS DATOS DE LA OFERTA
$sqlInfo = " SELECT * FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ";
$sqlInfo.= " WHERE rowid = ".$id;

$resultInfo = $db->query($sqlInfo);

$info = $db->fetch_object($resultInfo);

//PRONTO PAGO, DESCUENTO CLIENTE Y DESCUENTO OFERTA
$dtoPP = $info->dto_pp;
$dtoCli = $info->dto_cliente;
$dtoOfe = $info->dto_oferta;

$sqlDatos = " SELECT * FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
$sqlDatos.= " WHERE fk_oferta = ".$id;

$resultDatos = $db->query($sqlDatos);

$datos = $db->fetch_object($resultDatos);

//Para calcular los datos combinados que faltan
//$aRestarPP = ($datos->base_imponible * $dtoPP) / 100;
$aRestarCli = ($datos->base_imponible * $dtoCli) / 100;
$aRestarOfe = ($datos->base_imponible * $dtoOfe) / 100;

$aRestarTotal = $aRestarCli + $aRestarOfe;

$subtotal = number_format($datos->base_imponible,2) - number_format($aRestarTotal,2) - $dtoPP;

$ivaFinal = ($subtotal * 21) / 100;
$totalImporte = $subtotal + $ivaFinal;

print "
<div  class='tabBar tabBarWithBottom' >
<table class='border centpercent'>
    <tbody>
        <tr>
            <td>
                <label class='fieldrequired' >Datos Generales:</label>
            </td>
        </tr>
        <tr>";

        if ($idAveria != "") {
                    print "<td>
                        <label class='field' >Ref. Anterior:</label>
                        <input style='width:350px' readonly type='text' name='ref_anterior' value='".$referencia."'>
                    </td>
                </tr>";
            print "<tr>
                <td>
                    <label class='field' >Modalidad:</label>
                    <input style='width:365px' readonly type='text' name='modalidad' value='".$modalidad."'>
                </td>
            </tr>
            <tr>
                <td>
                    <label class='field' >Garantía:</label>
                    <input style='width:375px' readonly type='text' name='garantia' value='Hasta: ".$garantia."'>
                </td>
            </tr>";
        }

        print "<tr>
            <td>
                <label class='field' >Porcentaje Resolución:</label>
                <input style='width:70px' class='center' readonly type='number' name='porcentaje_resolucion' value='".$info->porcent_res."'>
            </td>
        </tr>
    </tbody>
</table>";

print "<div class='tabsAction'>";
print "<a class='butAction' type='button' style='margin-bottom:0px !important' href='". $_SERVER["PHP_SELF"] ."?action=edit&id=".$id."'>Modificar porcentaje</a>";
print '</div>';

print "<br>
<br>
<div  class='tabBar' >
    <table class='border centpercent'>
        <tr>
            <td>
                <label class='fieldrequired' >Datos Totales:</label>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Suma:</label>
                <input class='right' style='width:150px' readonly type='number' step=0.01 name='suma' value='".number_format($datos->base_imponible,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Dto. PP.:</label>
                <input class='right' style='width:136px' readonly type='number' step=0.01 name='dto_pp'  value='".number_format($dtoPP,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Dto. Cliente.:</label>
                <input class='right' style='width:110px' readonly type='number' step=0.01 name='dto_cliente'  value='".number_format($aRestarCli,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Dto. Oferta.:</label>
                <input class='right' style='width:115px' readonly type='number' step=0.01 name='dto_oferta'  value='".number_format($aRestarOfe,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Subtotal:</label>
                <input class='right' style='width:135px' readonly type='number' step=0.01 name='subtotal'  value='".number_format($subtotal,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Materiales:</label>
                <input class='right' style='width:80px' readonly type='number' step=0.01 name='coste_materiales'  value='".number_format($datos->coste_material,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Gastos:</label>
                <input class='right' style='width:140px' readonly type='number' step=0.01 name='gastos'  value='".number_format($datos->gastos,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Transporte:</label>
                <input class='right' style='width:76px' readonly type='number' step=0.01 name='coste_transporte'  value='".number_format($datos->coste_transporte,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Desarrollo:</label>
                <input class='center' style='width:80px' readonly type='text' name='coste_desarrollo'  value='-'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Instalación:</label>
                <input class='right' style='width:80px' readonly type='number' step=0.01 name='coste_instalacion'  value='".number_format($datos->coste_instalacion,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Pruebas:</label>
                <input class='center' style='width:96px' readonly type='text' name='coste_pruebas'  value='-'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste PRL:</label>
                <input class='center' style='width:120px' readonly type='text' name='coste_prl'  value='-'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Coste Total:</label>
                <input class='right' style='width:120px' readonly type='number' step=0.01 name='coste_total'  value='".number_format($datos->coste_total,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Base Imponible:</label>
                <input class='right' style='width:90px' readonly type='number' step=0.01 name='base_imponible'  value='".number_format($subtotal,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Beneficios:</label>
                <input class='right' style='width:122px' readonly type='number' step=0.01 name='beneficios'  value='".number_format($datos->beneficio,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >IVA/Impuestos:</label>
                <input class='right' style='width:95px' readonly type='number' step=0.01 name='impuestos'  value='".number_format($ivaFinal,2)."'>
            </td>
        </tr>
        <tr>
            <td>
                <label class='field' >Total Importe:</label>
                <input class='right' style='width:105px' readonly type='number' step=0.01 name='total_importe'  value='".number_format($totalImporte,2)."'>
            </td>
        </tr>
    </tbody>
</table>
";

print "<div class='tabsAction'>";
print "<a class='butAction' type='button' style='margin-bottom:0px !important' href='printOferta.php?&id=".$id."' target='_blank'>Imprimir Oferta</a>";
print '</div>';

//Hacemos update de todos los datos
$sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas_datos ";
$sqlUpdate.= " SET dto_pp = ".number_format($dtoPP,2).", dto_cliente = ".number_format($aRestarCli,2).", dto_oferta = ".number_format($aRestarOfe,2).", base_imponible_final = ".number_format($subtotal,2).", iva_final = ".number_format($ivaFinal,2).", total_final = ".number_format($totalImporte,2)." ";
$sqlUpdate.= " WHERE fk_oferta = ".$id;

$db->query($sqlUpdate);

}


if ($action == "edit") {

	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar porcentaje de resolución</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 100.928px;" class="ui-dialog-content ui-widget-content">
				<div>
				<table>
					<tbody>
						<tr>
							<td>
								<label for="porcentaje">Porcentaje</label>
							</td>
							<td>
                                <input class="center" style="width:80px" type="number" step=1 name="porcentaje" value='.$object->porcent_res.'>
							</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editPorcentaje">
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


print '</div>';

print dol_get_fiche_end();

print  '
<script>

    
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
