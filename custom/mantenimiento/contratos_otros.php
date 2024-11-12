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
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
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
$diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->mantenimiento->multidir_output[$object->entity] . "/" . $object->id;
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

include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 * View
 */

$form = new Form($db);

const PERIODICITY_MONTHS = [
	'0'=> 1,
	'1'=> 2,
	'2'=> 3,
	'3'=> 6,
	'4'=> 12
];

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Otros datos'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch($id);
	$object->fetch_thirdparty();

	$head = contratosPrepareHead($object);

	print dol_get_fiche_head($head, 'otrosdatos', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/mantenimiento/contratos_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	print "<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "?id=" . $_GET['id'] . "\">";
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';


	print dol_get_fiche_end();

	$id_usuario = $object->id;

	/*$sqlCosteMateriales = "SELECT (SUM(p.price_ttc) * mcr.quantity) coste_materiales ";
	$sqlCosteMateriales.= "FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_repuestos mcr ";
	$sqlCosteMateriales.= "INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mcr.fk_product ";
	$sqlCosteMateriales.= "INNER JOIN ".MAIN_DB_PREFIX."product_extrafields pe ON p.rowid = pe.fk_object ";
	$sqlCosteMateriales.= "WHERE pe.mantenimiento = 1 and mcr.fk_contract=" . $id;
	
	$resultCosteMateriales = $db->query($sqlCosteMateriales);
	
	$dataCosteMateriales = $db->fetch_object($resultCosteMateriales);

	$costeMateriales = number_format($dataCosteMateriales->coste_materiales, 2);*/

	// $sqlCostMaterials = " SELECT SUM(pom.material_cost) as sum_material_cost";
	// $sqlCostMaterials.= " FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
	// $sqlCostMaterials.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mc.offer_id = pom.fk_project";
	// $sqlCostMaterials.= " WHERE mc.rowid =".$id;

	// $resultCosteMateriales = $db->query($sqlCosteMateriales);
	
	// $dataCosteMateriales = $db->fetch_object($resultCosteMateriales);

	if ($object->project_id != "") {

		$sqlTiempo = " SELECT SUM(ptt.task_duration) as tiempo_empleado, SUM( (ptt.task_duration / 3600) * u.thm) as technical_cost";
		$sqlTiempo.= " FROM ".MAIN_DB_PREFIX."projet_task_time ptt";
		$sqlTiempo.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task pt ON ptt.fk_task = pt.rowid";
		$sqlTiempo.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON ptt.fk_user = u.rowid";
		$sqlTiempo.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON pt.fk_projet = mc.project_id";
		$sqlTiempo.= " WHERE mc.project_id=". $object->project_id ." AND ptt.task_datehour BETWEEN  mc.date_start AND mc.date_end ";
		
		$resultCoste = $db->query($sqlTiempo);
		$tiempo = $db->fetch_object($resultCoste);

		$tiempo_empleado =  number_format( ( $tiempo->tiempo_empleado ) / 3600 , 0);
	
		$diferencia_tiempo = number_format( ( $object->estimated_anual_time - $tiempo_empleado ) , 0);
	
		$mano_obra = $tiempo->technical_cost;

	} else {

		$tiempo_empleado =  0;
	
		$diferencia_tiempo = 0;
	
		$mano_obra = 0;

	}

	//IVA DEL CLIENTE
	$sqlIva = " SELECT se.porc_iva FROM ".MAIN_DB_PREFIX."societe_extrafields se ";
	$sqlIva.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = se.fk_object ";
	$sqlIva.= " WHERE s.rowid = ".$object->client_id." AND s.client = 1 ";

	$resultIva = $db->query($sqlIva);
	$iva = $db->fetch_object($resultIva);

	$sqlContrato = " SELECT mc.*, mcp.* FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
	$sqlContrato.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos_precios mcp ON mcp.id_contrato = mc.rowid ";
	$sqlContrato.= " WHERE mc.rowid = ".$id;

	$resultContrato = $db->query($sqlContrato);
	$contrato = $db->fetch_object($resultContrato);

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print_barre_liste($langs->trans("Otros datos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);
	
	print "
	<div  class='tabBar tabBarWithBottom' >
	<table class='border centpercent'>
		<tbody>
			<tr>
				<td>
					<label class='field' >Tiempo estimado anual (h):</label>
					<input readonly class='right' style='width:100px' type='text' name='codigo_delegacion' value='". strtr(number_format($contrato->estimated_time,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Tiempo empleado (h):</label>
					<input readonly class='right' style='width:136px' type='text' name='codigo_delegacion' value='".strtr(number_format($contrato->horas_dedicadas,2),['.' => ',', ',' => '.'])."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Diferencia de tiempo (h):</label>
					<input readonly class='right' style='width:118px' type='text' name='codigo_delegacion' value='".strtr(number_format($contrato->estimated_time - $contrato->horas_dedicadas,2),['.' => ',', ',' => '.'])."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Coste materiales:</label>
					<input readonly class='right' style='width:164px' type='text' name='coste_materiales' value='". strtr(number_format($contrato->coste_material,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Coste mano de obra:</label>
					<input readonly class='right' style='width:140px' type='text' name='codigo_delegacion'  value='".strtr(number_format($contrato->coste_horas,2),['.' => ',', ',' => '.'])."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Coste pruebas:</label>
					<input readonly class='right' style='width:178px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->coste_pruebas,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Coste instalaciones:</label>
					<input readonly class='right' style='width:146px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->coste_instalacion,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Bruto:</label>
					<input readonly class='right' style='width:238px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->bruto,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Dto. Cliente:</label>
					<input readonly class='right' style='width:198px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->dto_cliente,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Base imponible:</label>
					<input readonly class='right' style='width:172px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->base_imponible,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >IVA (%):</label>
					<input readonly class='right' style='width:222px' type='text' name='codigo_delegacion'  value='".strtr(number_format($iva->porc_iva,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >IVA:</label>
					<input readonly class='right' style='width:248px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->iva,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Suma:</label>
					<input readonly class='right' style='width:232px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->suma,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Subtotal:</label>
					<input readonly class='right' style='width:216px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->subtotal,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
			<tr>
				<td>
					<label class='field' >Total:</label>
					<input readonly class='right' style='width:238px' type='text' name='codigo_delegacion'  value='". strtr(number_format($contrato->total,2),['.' => ',', ',' => '.']) ."'>
				</td>
			</tr>
		</tbody>
	</table			
	";
		
	
	
}

if (isset($_POST['add'])) {
	$contrat_id = $_POST['id'];
	$product_id = $_POST['product'];

	$sqlInsertProducts = "INSERT INTO " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos ( fk_contract, fk_product ) VALUES ( ".$contrat_id.",".$product_id." )";
	$resultInsertProducts = $db->query($sqlInsertProducts);

	setEventMessages("Línea creada", null, 'mesgs');

	header('Location: contratos_equipos.php?id=' . $contrat_id . '');
}



// End of page
llxFooter();
$db->close();
ob_flush();
