<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       mo_note.php
 *  \ingroup    mrp
 *  \brief      Card with notes on Mo
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

dol_include_once('/mrp/class/mo.class.php');
dol_include_once('/mrp/lib/mrp_mo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mrp", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('monote', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->mrp->multidir_output[$object->entity]."/".$object->id;
}

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = $user->rights->mrp->write;	// Used by the include of actions_setnotes.inc.php

//COMPROBACIÓN DE SI HAY CREADA TASK Y SI HAY AVERIA
$sqlTask = " SELECT fk_task FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
$sqlTask.= " WHERE fk_mo = ".$object->id;

$resultTask = $db->query($sqlTask);
$task = $db->fetch_object($resultTask);
$task = $task->fk_task;

$sqlAveria = " SELECT fk_averia FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
$sqlAveria.= " WHERE fk_mo = ".$object->id;

$resultAveria = $db->query($sqlAveria);
$averiaE = $db->fetch_object($resultAveria);
$averiaE = $averiaE->fk_averia;


/*
 * Actions
 */

if (isset($_POST['deleteFase'])) {

	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_fases ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

}

if (isset($_POST['addGasto'])) {

	extract($_POST);

	$sqlFase = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlFase.= " WHERE rowid = ".$fase;

	$resultFase = $db->query($sqlFase);
	$fase1 = $db->fetch_object($resultFase);

	if ($stock == "") {
		$stock = "NULL";
	}

	$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlInsert.= " (fk_orden, fk_fase, codigo, descripcion, unidades, stock, tipo, coste) ";
	$sqlInsert.= " VALUES ";
	$sqlInsert.= " ($id, $fase, '".$fase1->rowid."', '".$descripcion."', $unidades, $stock, '".$tipo."', $coste) ";

	$db->query($sqlInsert);

	//Para añadir el gasto al coste de lineas OT
	/*$sqlUpdate = " UPDATE ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
	$sqlUpdate.= " SET actual_cost = COALESCE(actual_cost, 0) + ".$coste;
	$sqlUpdate.= " WHERE rowid = ".$id;

	$db->query($sqlUpdate);*/

}

if (isset($_POST['deleteGasto'])) {

	$rowid = $_GET['rowid'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

}



/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Mo - Líneas y Costes'), $help_url);

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = moPrepareHead($object);

    print dol_get_fiche_head($head, 'lineas', $langs->trans("ManufacturingOrder"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    //$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    //$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= $langs->trans('ThirdParty').' : '.(is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
    // Project
    if (!empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>'.$langs->trans('Project').' ';
        if ($permissiontoadd) {
            if ($action != 'classify') {
                $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            }
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref .= ' : '.$proj->getNomUrl();
            } else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    //print '<div class="underbanner clearboth"></div>';


    //$cssclass = "titlefield";
    //include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    print '</div>';



    print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	$arrayfieldsFases = array(
		'codigo' => array('label' => $langs->trans("codigo"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 1),
		'articulo' => array('label' => $langs->trans("articulo"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("unidades"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsFases = array(
			'codigo' => array('label' => $langs->trans("codigo"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("descripcion"), 'checked' => 0),
			'articulo' => array('label' => $langs->trans("articulo"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("unidades"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsFases[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsFases, $varpage); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	$sqlConsulta = " SELECT * FROM ".MAIN_DB_PREFIX."produccion_orden_de_trabajo_fases ";
	$sqlConsulta.= " WHERE fk_orden = ".$id;

	$resultConsulta = $db->query($sqlConsulta);
	$numFases = $db->num_rows($resultConsulta);

	$num = $numFases;
	$nbtotalofrecords = $num;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Fases"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsFases['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_tarea_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_trabajador_id">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['articulo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fecha">';
		print '</td>';
	}

	if (!empty($arrayfieldsFases['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_tiempo'>";
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

	if (!empty($arrayfieldsFases['codigo']['checked'])) {
		print "<th class='center liste_titre' title='id_asoc'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='nombre'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['articulo']['checked'])) {
		print "<th class='center liste_titre' title='fecha'>";
		print "<a class='reposition' href=''>Articulo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['unidades']['checked'])) {
		print "<th class='center liste_titre' title='tiempo'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	//CONSULTA PARA LAS FASES
	$sqlFase = " SELECT * FROM " . MAIN_DB_PREFIX . "produccion_orden_de_trabajo_fases ";
	$sqlFase.= " WHERE fk_orden = ".$id;

	$resultFase = $db->query($sqlFase);
	$fase = $db->fetch_object($resultFase);
	$numFases = $db->num_rows($resultFase);

	if ($numFases == 0) {

		//Si es 0, sacamos todos los toproduce y toconsume de la MO para sacar las fases
		$sqlToproduce = " SELECT pro.*, p.ref, p.label FROM " . MAIN_DB_PREFIX . "mrp_production pro";
		$sqlToproduce.= " INNER JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = pro.fk_product ";
		$sqlToproduce.= " WHERE pro.fk_mo = ".$id." AND pro.role = 'toproduce' ";

		$resultToproduce = $db->query($sqlToproduce);
		$produce = $db->fetch_object($resultToproduce);

		$sqlToconsume = " SELECT pro.*, p.ref, p.label FROM " . MAIN_DB_PREFIX . "mrp_production pro";
		$sqlToconsume.= " INNER JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = pro.fk_product ";
		$sqlToconsume.= " WHERE pro.fk_mo = ".$id." AND pro.role = 'toconsume' ";

		$resultToconsume = $db->query($sqlToconsume);
		$numConsu = $db->num_rows($resultToconsume);

		if ($averiaE == "") {
			$tipo = "PRODUCCIÓN";
		} else {
			$tipo = "REPARACIÓN";
		}

		while ($consume = $db->fetch_object($resultToconsume)) {

			$sqlInsert = " INSERT INTO " . MAIN_DB_PREFIX . "produccion_orden_de_trabajo_fases ";
			$sqlInsert.= " (fk_orden, fk_equipo, codigo, descripcion, articulo, unidades) ";
			$sqlInsert.= " VALUES ";

			$sqlInsert.= " ($id, 0, $consume->fk_product, '".$tipo." DE: ".$produce->ref." HACIENDO USO DE: ".$consume->ref."', '".$consume->label."', $consume->qty) ";

			$db->query($sqlInsert);
			//print $sqlInsert;
			//print "<br>";


		}

	}

	//PARA SACAR LAS FASES
	$sqlFases = " SELECT * FROM " . MAIN_DB_PREFIX . "produccion_orden_de_trabajo_fases ";
	$sqlFases.= " WHERE fk_orden = ".$id;

	$resultFinal = $db->query($sqlFases);


	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
			$idbusqueda = "" . $_POST['search_codigo'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$nombre = "" . $_POST['search_descripcion'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_articulo']) && ($_POST['search_articulo']) != "") {
			$apellidos = "" . $_POST['search_articulo'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
			$fecha = "'" . $_POST['search_unidades'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';

	while ($fase = $db->fetch_object($resultFinal)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfieldsFases['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->rowid . "</td> ";

		if (!empty($arrayfieldsFases['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>".$fase->descripcion."</td> ";

		if (!empty($arrayfieldsFases['articulo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->articulo . "</td> ";

		if (!empty($arrayfieldsFases['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $fase->unidades . "</td> ";

		//if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=borrarFase&rowid=' . $fase->rowid . '&id='.$id.'">' . img_delete() . '</a></td>';
		//} else {
			//print '<td class="center">&nbsp;</td>';
		//}
		print "</tr>";

		//INSERTAMOS CADA FASE EN SU TABLA CORRESPONDIENTE
		
	}

	print "</table>";

	print '</form>';


	//PARTES DE TRABAJO



    /*print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsTareas = array(
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsTareas = array(
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
			'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
			'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsTareas[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsTareas, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	$result = $db->query($sql);
	//if ($result) {
	$num = $db->num_rows($result);
	$nbtotalofrecords = $num;
	$nbtotalofrecords = 0;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Partes de trabajo"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsTareas['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_codigo">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_unidades">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['tipo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo">';
		print '</td>';
	}
	if (!empty($arrayfieldsTareas['coste']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_coste">';
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

	if (!empty($arrayfieldsTareas['codigo']['checked'])) {
		print "<th class='center liste_titre' title='codigo'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['unidades']['checked'])) {
		print "<th class='center liste_titre' title='cantidad'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['tipo']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Tipo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['coste']['checked'])) {
		print "<th class='center liste_titre' title='cantidad'>";
		print "<a class='reposition' href=''>Coste</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
			$idbusqueda = "" . $_POST['search_id'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$nombre = "" . $_POST['search_nombre'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
			$apellidos = "" . $_POST['search_apellidos'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_tipo']) && ($_POST['search_tipo']) != "") {
			$nombre = "" . $_POST['search_nombre'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_coste']) && ($_POST['search_coste']) != "") {
			$apellidos = "" . $_POST['search_apellidos'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i == $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		if (!empty($arrayfieldsTareas['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->id_asoc . "</td> ";

		if (!empty($arrayfieldsTareas['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->firstname . "</td> ";

		if (!empty($arrayfieldsTareas['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if (!empty($arrayfieldsTareas['tipo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->firstname . "</td> ";

		if (!empty($arrayfieldsTareas['coste']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
	//print '</div>';

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" style="margin-bottom:0px !important" href="'. $_SERVER["PHP_SELF"] .'?action=informe&id='.$id.'">Generar Informe</a>';
	print '</div>';*/

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsArticulos = array(
		'fase' => array('label' => $langs->trans("Fase/Tarea"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 1),
		'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'coste' => array('label' => $langs->trans("Coste"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsArticulos = array(
			'fase' => array('label' => $langs->trans("Fase/Tarea"), 'checked' => 0),
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 0),
			'unidades' => array('label' => $langs->trans("Unidades"), 'checked' => 0),
			'tipo' => array('label' => $langs->trans("Tipo"), 'checked' => 0),
			'coste' => array('label' => $langs->trans("Coste"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsArticulos[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsArticulos, $varpage2); // This also change content of $arrayfieldsFases



	$id_usuario = $object->id;

	$sqlGastos = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlGastos.= " WHERE fk_orden = ".$id;

	$resultGastos = $db->query($sqlGastos);
	$numGastos = $db->num_rows($resultGastos);

	$nbtotalofrecords = $numGastos;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Gastos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsArticulos['fase']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_fase">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_codigo">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['descripcion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_unidades">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['stock']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_stock">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['tipo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo">';
		print '</td>';
	}
	if (!empty($arrayfieldsArticulos['coste']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_coste">';
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

	if (!empty($arrayfieldsArticulos['fase']['checked'])) {
		print "<th class='center liste_titre' title='fase'>";
		print "<a class='reposition' href=''>Fase</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['codigo']['checked'])) {
		print "<th class='center liste_titre' title='codigo'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='descripcion'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['unidades']['checked'])) {
		print "<th class='center liste_titre' title='unidades'>";
		print "<a class='reposition' href=''>Unidades</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['tipo']['checked'])) {
		print "<th class='center liste_titre' title='tipo'>";
		print "<a class='reposition' href=''>Tipo</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsArticulos['coste']['checked'])) {
		print "<th class='center liste_titre' title='coste'>";
		print "<a class='reposition' href=''>Coste</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
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

		if (isset($_POST['search_descripcion']) && ($_POST['search_descripcion']) != "") {
			$descripcion = "" . $_POST['search_descripcion'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_unidades']) && ($_POST['search_unidades']) != "") {
			$unidades = "" . $_POST['search_unidades'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_stock']) && ($_POST['search_stock']) != "") {
			$stock = "" . $_POST['search_stock'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_tipo']) && ($_POST['search_tipo']) != "") {
			$tipo = "" . $_POST['search_tipo'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_coste']) && ($_POST['search_coste']) != "") {
			$coste = "" . $_POST['search_coste'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($gasto = $db->fetch_object($resultGastos)) {

		$sqlConsulta = " SELECT descripcion FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
		$sqlConsulta.= " WHERE rowid = ".$gasto->fk_fase;

		$resultConsulta = $db->query($sqlConsulta);
		$faseDesc = $db->fetch_object($resultConsulta);

		print '<tr class="oddeven">';

		if (!empty($arrayfieldsArticulos['fase']['checked']))	print "<td class='center' tdoverflowmax200'>".$faseDesc->descripcion."</td> ";

		if (!empty($arrayfieldsArticulos['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->codigo . "</td> ";

		if (!empty($arrayfieldsArticulos['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->descripcion . "</td> ";

		if (!empty($arrayfieldsArticulos['unidades']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->unidades . "</td> ";

		if (!empty($arrayfieldsArticulos['stock']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->stock . "</td> ";

		if (!empty($arrayfieldsArticulos['tipo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->tipo . "</td> ";

		if (!empty($arrayfieldsArticulos['coste']['checked']))	print "<td class='center' tdoverflowmax200'>" . $gasto->coste . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href='.$_SERVER['PHP_SELF'].'?action=borrarGasto&id='.$id.'&rowid='. $gasto->rowid . '>' . img_delete() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";

	}
	print "</table>";
	print '</div>';

	print '</form>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" style="margin-bottom:0px !important" href="'. $_SERVER["PHP_SELF"] .'?action=gasto&id='.$id.'">Nuevo gasto</a>';
	print '</div>';



	//COMPROBAMOS SI HAY UNA TASK
	$sqlTask = " SELECT fk_task FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
	$sqlTask.= " WHERE fk_mo = ".$id;

	$resultTask = $db->query($sqlTask);
	$task = $db->fetch_object($resultTask);
	$task = $task->fk_task;

	$totalTiempos = 0;

	if ($task != "") {
			
		$sqlTiempos = " SELECT task_duration, thm FROM ". MAIN_DB_PREFIX ."projet_task_time ";
		$sqlTiempos.= " WHERE fk_task = ".$task;

		$resultTiempos = $db->query($sqlTiempos);

		while ($tiempos = $db->fetch_object($resultTiempos)) {

			$totalTiempos+= (($tiempos->task_duration / 60) / 60) * number_format($tiempos->thm,2);

		}

		/*$total = $totalMateriales + $totalTiempos;

		$sqlUpdateTeorico = " UPDATE ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
		$sqlUpdateTeorico.= " SET teoric_cost = ".$total;
		$sqlUpdateTeorico.= " WHERE rowid = ".$id;

		$db->query($sqlUpdateTeorico);

		$sqlCoste = " SELECT teoric_cost, actual_cost FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo ";
		$sqlCoste.= " WHERE rowid = ".$id;

		$resultCoste = $db->query($sqlCoste);
		$costeTeorico = $db->fetch_object($resultCoste);

		$totalFinal = $costeTeorico->teoric_cost + $costeTeorico->actual_cost;*/

	}

	$totalMateriales = 0;
	//SACAMOS TODO EL DINERO DE LOS MOVIMIENTOS
	$sqlComprobacion = " SELECT SUM(price * value) as precio FROM ". MAIN_DB_PREFIX ."stock_mouvement ";
	$sqlComprobacion.= " WHERE fk_origin = ".$id." AND type_mouvement = 3 ";

	$resultComprobacion = $db->query($sqlComprobacion);
	$comp = $db->fetch_object($resultComprobacion);
	$precioMov = $comp->precio;

	if ($precioMov != "") {

		$totalMateriales = $totalMateriales + $precioMov;

	}

	if ($averiaE != "") {
		//APLICAMOS EL DESCUENTO DEL CONTRATO (SI LO HUBIERA)
		//Comprobamos primero que no tenga ya un descuento
		$sql = " SELECT dto FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
		$sql.= " WHERE fk_mo = ".$id;

		$resultsql = $db->query($sql);
		$hayDesc = $db->fetch_object($resultsql);

		if ($hayDesc->dto == "") {

			//SACAMOS LA AVERIA
			$sqlAveria = " SELECT fk_averia FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
			$sqlAveria.= " WHERE fk_mo = ".$id;

			$resultAveria = $db->query($sqlAveria);
			$averia = $db->fetch_object($resultAveria);

			if ($averia->fk_averia != "") {

				//Comprobamos que tenga informe y contrato la avería
				$sqlInforme = " SELECT fk_informe FROM ". MAIN_DB_PREFIX ."averiasreparaciones_averias ";
				$sqlInforme.= " WHERE rowid = ".$averia->fk_averia;

				$resultInforme = $db->query($sqlInforme);
				$informe = $db->fetch_object($resultInforme);
				$informe = $informe->fk_informe;

				//Si hay contrato, sacamos su descuento
				if ($informe != "") {

					$sqlDescuento = " SELECT mc.spare_parts_discount FROM ". MAIN_DB_PREFIX ."mantenimiento_contratos mc ";
					$sqlDescuento.= " INNER JOIN ". MAIN_DB_PREFIX ."mantenimiento_informes mi ON mi.contract_id = mc.rowid ";
					$sqlDescuento.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
					$sqlDescuento.= " WHERE a.rowid = ".$averia->fk_averia;

					$resultDescuento = $db->query($sqlDescuento);
					$descuento = $db->fetch_object($resultDescuento);
					$descuento = $descuento->spare_parts_discount;

					//Si el descuento no es vacío y es mayor que 0, lo aplicamos
					if (($descuento != "") && ($descuento > 0)) {

						/*$aDescontar = ($totalMateriales * $descuento) / 100;
						$totalMateriales = $totalMateriales - $aDescontar;*/

					}

				}

			}

		} else {

			/*$aDescontar = ($totalMateriales * $hayDesc->dto) / 100;
			$totalMateriales = $totalMateriales - $aDescontar;*/

			$descuento = $hayDesc->dto;

		}

	}

	$totalTeorico = $totalTiempos + $totalMateriales;

	//SACAMOS TODOS LOS GASTOS EXTRA
	//PRIMERO GASTOS DE TRANSPORTE
	$sqlGastosTrans = " SELECT SUM(coste) as costesT FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlGastosTrans.= " WHERE fk_orden = ".$id." AND tipo = 'Transporte' ";

	$resultGastosTrans = $db->query($sqlGastosTrans);
	$costesTrans = $db->fetch_object($resultGastosTrans);
	$costesTrans = $costesTrans->costesT;

	//GASTOS DE INSTALACIÓN
	$sqlGastosInsta = " SELECT SUM(coste) as costesT FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlGastosInsta.= " WHERE fk_orden = ".$id." AND tipo = 'Instalacion' ";

	$resultGastosInsta = $db->query($sqlGastosInsta);
	$costesInsta = $db->fetch_object($resultGastosInsta);
	$costesInsta = $costesInsta->costesT;

	//OTROS GASTOS
	$sqlGastosOtros = " SELECT SUM(coste) as costesT FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_gastos ";
	$sqlGastosOtros.= " WHERE fk_orden = ".$id." AND tipo = 'Otro' ";

	$resultGastosOtros = $db->query($sqlGastosOtros);
	$costesOtros = $db->fetch_object($resultGastosOtros);
	$costesOtros = $costesOtros->costesT;

	/*print "TRANSPORTE: ".$costesTrans;
	print "INSTALACION: ".$costesInsta;
	print "OTROS: ".$costesOtros;*/

	$totalActual = $totalTeorico + $costesTrans + $costesInsta + $costesOtros;


    print "
    <div  class='tabBar tabBarWithBottom' >
    <table class='border centpercent'>
        <tbody>";

		if ($averiaE != "") {
            print "<tr>
                <td>
                    <label class='field' >Dto. Repuestos que se le va a aplicar (del Contrato):</label>
                    <input class='center' style='width:80px' readonly type='text' step='0.01' value='".$descuento." %'>
                </td>
            </tr>";
		}
            print "<tr>
                <td>
                    <label class='fieldrequired' >Costes:</label>
                </td>
            </tr>
            <tr>
                <td>
                    <label class='field' >Teórico:</label>
                    <input class='center' style='width:80px' readonly type='text' step='0.01' value='".number_format($totalTeorico,2)."'>
                </td>
            </tr>
            <tr>
                <td>
                    <label class='field' >Actual:</label>
                    <input class='center' style='width:85px' readonly type='text' step='0.01' value='".number_format($totalActual,2)."'>
                </td>
            </tr>
        </tbody>
    </table>";

	if ($costesTrans == "") {
		$costesTrans = "NULL";
	}

	if ($costesInsta == "") {
		$costesInsta = "NULL";
	}

	if ($costesOtros == "") {
		$costesOtros = "NULL";
	}

	if ($totalTeorico == "") {
		$totalTeorico = "NULL";
	}

	if ($totalActual == "") {
		$totalActual = "NULL";
	}

	if ($totalMateriales == "") {
		$totalMateriales = "NULL";
	}

	if ($totalTiempos == "") {
		$totalTiempos = "NULL";
	}

	if ($descuento == "") {
		$descuento = "NULL";
	}


	$sqlUpdateCostes = " UPDATE ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
	$sqlUpdateCostes.= " SET gasto_teorico = ".$totalTeorico.", gasto_actual = ".$totalActual.", ";
	$sqlUpdateCostes.= " gasto_transporte = ".$costesTrans.", gasto_instalacion = ".$costesInsta.", gasto_otros = ".$costesOtros.", ";
	$sqlUpdateCostes.= " gasto_repuestos = ".$totalMateriales.", gasto_tiempos = ".$totalTiempos.", dto = ".$descuento." ";
	$sqlUpdateCostes.= " WHERE fk_mo = ".$id;

	$db->query($sqlUpdateCostes);


 
    print dol_get_fiche_end();

 }
 

if ($action == "borrarFase") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Fase</span>
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
								<span class="field">¿Seguro que deseas eliminar esta fase?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteFase">
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

if ($action == "gasto") {

	$id = $_GET['id'];

	$sqlFases = " SELECT * FROM ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_fases ";
	$sqlFases.= " WHERE fk_orden = ".$id;

	$resultFases = $db->query($sqlFases);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 650px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir gasto</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 280.928px;" class="ui-dialog-content ui-widget-content">
				<div>
				<table>
					<tbody>
						<tr>
							<td>
								<label for="fase" class="fieldrequired">Fase</label>
							</td>
							<td>
								<select class="select-fase" name="fase">
									<option value=-1>&nbsp</option>';

								while ($fase = $db->fetch_object($resultFases)) {

									print '<option value='.$fase->rowid.'>('.$fase->rowid.') - '.$fase->descripcion.'</option>';

								}

								print '</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="descripcion" class="fieldrequired">Descripción</label>
							</td>
							<td>
								<textarea name="descripcion" style="width:300px;height:80px">
								</textarea>
							</td>
						</tr>
						<tr>
							<td>
								<label for="unidades" class="fieldrequired">Unidades</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="unidades">
							</td>
						</tr>
						<tr>
							<td>
								<label for="tipo" class="fieldrequired">Tipo</label>
							</td>
							<td>
								<select name="tipo" class="select-tipo">
									<option value="Otro">&nbsp</option>
									<option value="Transporte">Transporte</option>
									<option value="Instalacion">Instalación</option>
									<option value="Otro">Otro</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="coste" class="fieldrequired">Coste</label>
							</td>
							<td>
								<input type="number" step="0.01" min=1 name="coste">
							</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addGasto">
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

if ($action == "borrarGasto") {

	$rowid = $_GET['rowid'];
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Eliminar Gasto</span>
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
								<span class="field">¿Seguro que deseas eliminar este gasto?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteGasto">
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

	$(".select-fase").select2({
		width: "150%" // Esto hará que el campo de selección ocupe todo el ancho disponible
	});
	$(".select-tipo").select2({
		width: "100%" // Esto hará que el campo de selección ocupe todo el ancho disponible
	});

</script>';

/*
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