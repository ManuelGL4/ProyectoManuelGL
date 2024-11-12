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
 *   	\file       dias_permiso_list.php
 *		\ingroup    recursoshumanos
 *		\brief      List page for dias_permiso
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// load recursoshumanos libraries
require_once __DIR__.'/class/dias_permiso.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("recursoshumanos@recursoshumanos", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'dias_permisolist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Dias_permiso($db);

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->recursoshumanos->dias_permiso->read;
$permissiontoadd = $user->rights->recursoshumanos->dias_permiso->write;
$permissiontodelete = $user->rights->recursoshumanos->dias_permiso->delete;

if (empty($conf->recursoshumanos->enabled)) {
	accessforbidden('Module not enabled');
}

if ($user->socid > 0) accessforbidden();




/*
 * Actions
 */




/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url="EN:Module_Dias_permiso|FR:Module_Dias_permiso_FR|ES:Módulo_Dias_permiso";
$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("dias de permiso"));
$morejs = array();
$morecss = array();




$query = "
    SELECT t.rowid AS peticion_id, 
           t.label, 
           t.date_solic, 
           t.date_solic_fin, 
           u1.firstname AS creator_firstname, 
           u1.lastname AS creator_lastname, 
           u2.firstname AS modifier_firstname, 
           u2.lastname AS modifier_lastname, 
           t.date_creation, 
           t.status 
    FROM khns_recursoshumanos_dias_permiso AS t
    LEFT JOIN khns_user AS u1 ON t.fk_user_creat = u1.rowid
    LEFT JOIN khns_user AS u2 ON t.fk_user_modif = u2.rowid
    WHERE 1 = 1"; 
	
	if ($user->admin) {
		// Si es administrador, no se añade ninguna condición
	} else {
		// Si no es administrador, se añade la condición para filtrar por fk_user_solicitador
		$query .= " AND t.fk_user_solicitador = " . intval($user->id);
	}

	if (isset($_POST['codigo']) && !empty($_POST['codigo'])) {
		$query .= " AND t.rowid = " . intval($_POST['codigo']); 
	}

// Ejecutar la consulta
$resql = $db->query($query);







// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', '');


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/recursoshumanos/dias_permiso_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '');

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);


print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';

print '<td class = "liste_titre" colspan = "1">';
print '<input type="text" name="codigo" value="" />';
print '</td>';

print '<td class = "liste_titre" colspan = "1">';
print '<input type="text" name="description" value="" />';
print '</td>';

print '<td class = "liste_titre center" colspan = "1">';
print '<input type="date" name="fecha_inicio" value="" />';
print '</td>';

print '<td class = "liste_titre" colspan = "1">';
print '<input type="date" name="fecha_fin" value="" />';
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print $form->select_dolusers($object->userid, 'ls_userid', 1, '', 0);
print '</td>';

print '<td class = "liste_titre" colspan = "1">';
print '<input type="date" name="fecha_create" value="" />';
print '</td>';


print '<td class = "liste_titre" colspan = "1" >';
print $form->select_dolusers($object->userid, 'ls_userid', 1, '', 0);
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<select>
  <option value="">Todos</option>
  <option value="1">Borrador</option>
  <option value="2">Validado</option>
  <option value="3">Rechazado</option>';
print '</td>';

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column


print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Código de solicitud</div>';
print '</th>';

print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Descripción</div>';
print '</th>';

print '<th class="wrapcolumntitle minwidth300 center liste_titre">';
print '<div>Fecha de inicio solicitada</div>';
print '</th>';

print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Fecha fin</div>';
print '</th>';

print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Usuario de solicitud</div>';
print '</th>';


print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Fecha creacion solicitud</div>';
print '</th>';


print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Usuario validador</div>';
print '</th>';

print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Estado Peticion</div>';
print '</th>';

print '<th class="wrapcolumntitle center liste_titre">';
print '<div>Acciones</div>';
print '</th>';

// Action column
print '</tr>'."\n";




// Loop on record
// --------------------------------------------------------------------

$statusLabels = [
    0 => ["label" => "Borrador", "color" => "background-color: white;"],
    1 => ["label" => "Validado", "color" => "background-color: green;"],
    9 => ["label" => "Cancelado", "color" => "background-color: red;"],
];

if ($resql) {
    $i = 0;
    $totalarray = array();
    $totalarray['nbfield'] = 0;
    
    // Bucle para mostrar los resultados
    while ($obj = $db->fetch_object($resql)) {
		$statusLabel = "";
		$statusClass = "";
	
		switch ($obj->status) {
			case 0:
				$statusLabel = "Pendiente";
				$statusClass = "badge badge-status0 badge-status"; // Clase para "Borrador"
				break;
			case 1:
				$statusLabel = "Aprobada";
				$statusClass = "badge  badge-status4 badge-status"; // Clase para "Validado"
				break;
			case 9:
				$statusLabel = "Rechazada";
				$statusClass = "badge badge-status8 badge-status"; // Clase para "Cancelado"
				break;
			default:
				$statusLabel = "Desconocido";
				$statusClass = "badge badge-status unknown"; // Clase por defecto
				break;
		}

        print '<tr class="oddeven">';
        print '<td class="center">' . $obj->peticion_id . '</td>';
        print '<td class="center">' . $obj->label . '</td>';
        print '<td class="center">' . $obj->date_solic . '</td>';
        print '<td class="center">' . $obj->date_solic_fin . '</td>';
        
        print '<td class="center">' . $obj->creator_firstname . ' ' . $obj->creator_lastname . '</td>';
        print '<td class="center">' . $obj->date_creation . '</td>';
        
        print '<td class="center">' . $obj->modifier_firstname . ' ' . $obj->modifier_lastname . '</td>';
    	print '<td class="center">';
		print '<span class="'.$statusClass.'">'. $statusLabel. '</span>';
		print'</td>';
		print '<td class="center">';
		
		print '<a class="fas  fa-folder-open" style="" title="Editar Registro" href="dias_permiso_card.php?action=edit&id=' . $obj->peticion_id  . '"></a>';
        print '<a class="fas fa-eye" style="" title="Eliminar" href="' . $_SERVER["PHP_SELF"] . '?action=delete&token=' . $obj->peticion_id  . '"></a>';
		print '</td>';


        print '</tr>' . "\n";

        $i++;
    }
} else {
    // Manejo de errores si la consulta falla
    print 'Error en la consulta: ' . $db->lasterror();
}


print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
