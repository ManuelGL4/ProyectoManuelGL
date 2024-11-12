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
 *  \file       averias_seguimientos_note.php
 *  \ingroup    averiasreparaciones
 *  \brief      Tab for notes on Averias_seguimientos
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

dol_include_once('/averiasreparaciones/class/averias_seguimientos.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias_seguimientos.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/averiasreparaciones/class/averias.class.php');
dol_include_once('/averiasreparaciones/lib/averiasreparaciones_averias.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("averiasreparaciones@averiasreparaciones", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$url = $_SERVER["PHP_SELF"]."?id=".$id; 

// Initialize technical objects
$object = new Averias($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->averiasreparaciones->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('averias_seguimientosnote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->averiasreparaciones->multidir_output[$object->entity]."/".$object->id;
}

$permissionnote = $user->rights->averiasreparaciones->averias_softwares->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->averiasreparaciones->averias_softwares->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->averiasreparaciones->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

if (isset($_POST['addSeguimiento'])) {

    extract($_POST);

    $sqlInsert = "INSERT INTO ". MAIN_DB_PREFIX ."averiasreparaciones_averias_seguimientos ";
    $sqlInsert.= " (fk_averia, observacion, fecha, fk_user_creat) ";
    $sqlInsert.= " VALUES ";
    $sqlInsert.= " ($id, '".$observacion."', '".$fecha."', $usuario) ";

    $db->query($sqlInsert);

}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Averias_softwares'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = averiasPrepareHead($object);
	print dol_get_fiche_head($head, 'software', $langs->trans("Workstation"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/averiasreparaciones/averias_card.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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
		'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'version' => array('label' => $langs->trans("Versión"), 'checked' => 1),
        'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 1),
		'ot' => array('label' => $langs->trans("OT"), 'checked' => 1),
        'sc' => array('label' => $langs->trans("SC"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'articulo' => array('label' => $langs->trans("Artículo"), 'checked' => 0),
            'version' => array('label' => $langs->trans("Versión"), 'checked' => 0),
            'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
            'indicaciones' => array('label' => $langs->trans("Indicaciones"), 'checked' => 0),
            'ot' => array('label' => $langs->trans("OT"), 'checked' => 0),
            'sc' => array('label' => $langs->trans("SC"), 'checked' => 0),
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
	print_barre_liste($langs->trans("Software"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['codigo']['checked'])) {
		print "<th class='center liste_titre' title='Codigo'>";
		print "<a class='reposition' href=''>Codigo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['articulo']['checked'])) {
		print "<th class='center liste_titre' title='Articulo'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['version']['checked'])) {
		print "<th class='center liste_titre' title='Version'>";
		print "<a class='reposition' href=''>Version</a>";
		print "</th>";
	}

    if (!empty($arrayfields['unidades']['checked'])) {
		print "<th class='center liste_titre' title='Unidades'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfields['indicaciones']['checked'])) {
		print "<th class='center liste_titre' title='Indicaciones'>";
		print "<a class='reposition' href=''>Indicaciones</a>";
		print "</th>";
	}

	if (!empty($arrayfields['ot']['checked'])) {
		print "<th class='center liste_titre' title='OT'>";
		print "<a class='reposition' href=''>OT</a>";
		print "</th>";
	}

    if (!empty($arrayfields['sc']['checked'])) {
		print "<th class='center liste_titre' title='SC'>";
		print "<a class='reposition' href=''>SC</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	/*$sqlSeguimientos = " SELECT aas.rowid, aas.fk_averia, aas.observacion, aas.fecha, aas.fk_user_creat, u.firstname, u.lastname ";
	$sqlSeguimientos.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_seguimientos aas ";
	$sqlSeguimientos.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = aas.fk_user_creat ";
	$sqlSeguimientos.= " WHERE aas.fk_averia =".$id;
	
	$resultSeguimientos = $db->query($sqlSeguimientos);

    while ($seguimiento = $db->fetch_object($resultSeguimientos)) {*/

		print '<tr class="oddeven">';

		if (!empty($arrayfields['codigo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->fecha."</td>";

		if (!empty($arrayfields['articulo']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->observacion."</td> ";

		if (!empty($arrayfields['version']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->firstname." ".$seguimiento->lastname."</td> ";

        if (!empty($arrayfields['unidades']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->fecha."</td>";

		if (!empty($arrayfields['indicaciones']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->observacion."</td> ";

		if (!empty($arrayfields['ot']['checked']))
			print "<td class='center' tdoverflowmax200'>".$seguimiento->firstname." ".$seguimiento->lastname."</td> "; 
            
        if (!empty($arrayfields['sc']['checked']))
        print "<td class='center' tdoverflowmax200'>".$seguimiento->firstname." ".$seguimiento->lastname."</td> "; 

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="'. $_SERVER["PHP_SELF"] .'?action=editar&id=' . $object->id . '&rowid=' . $seguimiento->rowid . '">' . img_edit() . '</a>
					</td>
					<td>
						<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrar&id=' . $object->id . '&rowid=' . $seguimiento->rowid . '">' . img_delete() . '</a>		
					</td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$i++;
		
	//}
	print "</table>";

	print '</div>';
    print '<div class="tabsAction">';
    print '<a class="butAction" type="button" href="#addSeguimientoModal" rel="modal:open">Nuevo software</a>';
    print '</div>';
	print '
	<div id="addSeguimientoModal" class="modal" role="dialog" aria-labelledby="addSeguimientoModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir seguimiento</h3>
			</div>
			<div class="modal-body">
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
								<label for="observacion">Observacion</label>
							</td>
							<td>
								<textarea name="observacion" style="width:300px;height:80px">
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
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addSeguimiento" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';
}
    print '</div>';

	print dol_get_fiche_end();

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
