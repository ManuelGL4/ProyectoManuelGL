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

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Otros datos'), $help_url);

if ($id > 0 || !empty($ref)) {
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

	$sql = "SELECT p.rowid, mce.id, p.ref, p.label, p.description, pe.mantenimiento ";
	$sql .= "FROM " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos mce ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid=mce.producto_id ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields pe ON p.rowid=pe.fk_object ";
	$sql .= "WHERE mce.contrato_id=" . $id;
	$result = $db->query($sql);

	$num = $db->num_rows($result);


	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	
		print "<th class='center liste_titre' title='id_asoc'>";
		print "<a class='reposition' href=''>Nº serie</a>";
		print "</th>";
	



	
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i < $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		print "<td class='center' tdoverflowmax200'>" . $datos->ref . "</td> ";

		print "<td class='center' tdoverflowmax200'>" . $datos->label . "</td> ";

		print "<td class='center' tdoverflowmax200'>" . $datos->label . "</td> ";

		print "<td class='center' tdoverflowmax200'></td> ";

		print "<td class='center' tdoverflowmax200'></td> ";


		//print '<td class="center"><a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $id . '&idDelete=' . $datos->id . '">' . img_delete() . '</a></td>';

		print "</tr>";
		$i++;
	}
	print "</table>";
	print '</div>';

	print '</form>';
}

if ($_GET["action"] == "add") {

	$sqlProducts = "SELECT p.rowid, p.label FROM " . MAIN_DB_PREFIX . "product p ";
	$sqlProducts .= "INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields pe ON p.rowid=pe.fk_object ";
	$sqlProducts .= "WHERE pe.mantenimiento='1'";
	$resultProducts = $db->query($sqlProducts);


	//$id = $_GET['id'];
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
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
								<select style="width: 100px" name="product" id="">';
								while ($product = $db->fetch_object($resultProducts)) {

									print ' <option value="' . $product->rowid . '">' . $product->label . '</option>';
								}
								print '
								</select>		
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
}

if (isset($_POST['add'])) {
	$contrat_id = $_POST['id'];
	$product_id = $_POST['product'];

	$sqlInsertProducts = "INSERT INTO " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos ( contrato_id, producto_id ) VALUES ( " . $contrat_id . "," . $product_id . " )";
	$resultInsertProducts = $db->query($sqlInsertProducts);

	setEventMessages("Línea creada", null, 'mesgs');

	header('Location: contratos_equipos.php?id=' . $contrat_id . '');
}



// End of page
llxFooter();
$db->close();
ob_flush();
