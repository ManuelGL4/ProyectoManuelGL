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

//COMPROBACIÓN DE SI HAY CREADA TASK
$sqlTask = " SELECT fk_task FROM ". MAIN_DB_PREFIX ."mrp_mo_extrafields_new ";
$sqlTask.= " WHERE fk_mo = ".$id;

$resultTask = $db->query($sqlTask);
$task = $db->fetch_object($resultTask);
$task = $task->fk_task;

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


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

if (isset($_POST['addTrabajadorFinal'])) {

	extract($_POST);

	//PROYECTO---------------------------------------------------------------------------------------------------
	//Id de tipo proyecto en caso de que sea responsable
	$sqlProyecto1 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlProyecto1.= " WHERE element LIKE '%project%' AND code LIKE '%PROJECTLEADER%' AND source LIKE '%internal%' ";

	$resultPro1 = $db->query($sqlProyecto1);

	$idResponsable = $db->fetch_object($resultPro1);
	$idResponsable = $idResponsable->rowid;

	//Id de tipo proyecto en caso de que sea participante
	$sqlProyecto2 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlProyecto2.= " WHERE element LIKE '%project%' AND code LIKE '%PROJECTCONTRIBUTOR%' AND source LIKE '%internal%' ";

	$resultPro2 = $db->query($sqlProyecto2);

	$idParticipante = $db->fetch_object($resultPro2);
	$idParticipante = $idParticipante->rowid;

	//COMPROBAMOS SI ESTÁ YA EL USUARIO EN EL PROYECTO
	$consulta = " SELECT * FROM ". MAIN_DB_PREFIX ."element_contact ";
	$consulta.= " WHERE element_id = ".$object->fk_project." AND fk_socpeople = ".$usuario;

	$resultConsulta = $db->query($consulta);
	$numFilas = $db->num_rows($resultConsulta);

	if ($numFilas == 0) {

		//Insertamos en contacto de proyecto
		if ($tipo == 1) {
			$tipoTrab = $idParticipante;
		} else {
			$tipoTrab = $idResponsable;
		}

		$sqlInsertPro = " INSERT INTO ". MAIN_DB_PREFIX ."element_contact ";
		$sqlInsertPro.= " (statut, element_id, fk_c_type_contact, fk_socpeople) ";
		$sqlInsertPro.= " VALUES ";
		$sqlInsertPro.= " (4, $object->fk_project, $tipoTrab, $usuario) ";

		$db->query($sqlInsertPro);

		//INSERTAMOS AHORA EL SEGUIMIENTO
		//Sacamos el nombre del usuario asignado
		/*$sqlNombre = " SELECT firstname, lastname FROM ". MAIN_DB_PREFIX ."user ";
		$sqlNombre.= " WHERE rowid = ".$usuario;

		$resultNombre = $db->query($sqlNombre);
		$nombre = $db->fetch_object($resultNombre);
		$nombre = $nombre->firstname." ".$nombre->lastname;

		//Sacamos la ref del proyecto
		$sqlRef = " SELECT ref FROM ". MAIN_DB_PREFIX ."projet ";
		$sqlRef.= " WHERE rowid = ".$proyecto;

		$resultRef = $db->query($sqlRef);
		$refe = $db->fetch_object($resultRef);
		$refe = $refe->ref;

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
		$sqlInsert.= " (fk_order, descripcion, fecha, usuario) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " ($id, 'Asignado ".$nombre." a proyecto ".$refe."', '".date('Y-m-d H:i:s')."', $user->id) ";
	
		$db->query($sqlInsert);*/

	}

	//TAREA---------------------------------------------------------------------------------------------------
	//Id de tipo project task en caso de que sea responsable
	$sqlTarea1 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlTarea1.= " WHERE element LIKE '%project_task%' AND code LIKE '%TASKEXECUTIVE%' AND source LIKE '%internal%' ";

	$resultTarea1 = $db->query($sqlTarea1);

	$idResponsable = $db->fetch_object($resultTarea1);
	$idResponsable = $idResponsable->rowid;

	//Id de tipo project task en caso de que sea participante
	$sqlTarea2 = " SELECT rowid FROM ". MAIN_DB_PREFIX ."c_type_contact ";
	$sqlTarea2.= " WHERE element LIKE '%project_task%' AND code LIKE '%TASKCONTRIBUTOR%' AND source LIKE '%internal%' ";

	$resultTarea2 = $db->query($sqlTarea2);

	$idParticipante = $db->fetch_object($resultTarea2);
	$idParticipante = $idParticipante->rowid;

	//COMPROBAMOS SI ESTÁ YA EL USUARIO EN EL PROYECTO
	$consulta = " SELECT * FROM ". MAIN_DB_PREFIX ."element_contact ";
	$consulta.= " WHERE element_id = ".$task." AND fk_socpeople = ".$usuario;

	$resultConsulta = $db->query($consulta);
	$numFilas = $db->num_rows($resultConsulta);

	if ($numFilas == 0) {

		//Insertamos en contacto de tarea
		if ($tipo == 1) {
			$tipoTrab = $idParticipante;
		} else {
			$tipoTrab = $idResponsable;
		}

		$sqlInsertTarea = " INSERT INTO ". MAIN_DB_PREFIX ."element_contact ";
		$sqlInsertTarea.= " (statut, element_id, fk_c_type_contact, fk_socpeople) ";
		$sqlInsertTarea.= " VALUES ";
		$sqlInsertTarea.= " (4, $task, $tipoTrab, $usuario) ";

		$db->query($sqlInsertTarea);

		/*//INSERTAMOS AHORA EL SEGUIMIENTO
		//Sacamos la ref de la tarea
		$sqlRef = " SELECT ref FROM ". MAIN_DB_PREFIX ."projet_task ";
		$sqlRef.= " WHERE rowid = ".$tarea;

		$resultRef = $db->query($sqlRef);
		$refe = $db->fetch_object($resultRef);
		$refe = $refe->ref;

		$sqlInsert = " INSERT INTO ". MAIN_DB_PREFIX ."produccion_orden_de_trabajo_seguimientos ";
		$sqlInsert.= " (fk_order, descripcion, fecha, usuario) ";
		$sqlInsert.= " VALUES ";
		$sqlInsert.= " ($id, 'Asignado ".$nombre." a esta OT (tarea: ".$refe.")', '".date('Y-m-d H:i:s')."', $user->id) ";
	
		$db->query($sqlInsert);*/

	}

}

if (isset($_POST['deleteTrabajador'])) {

	$rowid = $_GET['rowid'];
	$idusuario = $_GET['idusuario'];

	$sqlDelete = " DELETE FROM ". MAIN_DB_PREFIX ."element_contact ";
	$sqlDelete.= " WHERE rowid = ".$rowid;

	$db->query($sqlDelete);

	$rowid--;

	$sqlDelete2 = " DELETE FROM ". MAIN_DB_PREFIX ."element_contact ";
	$sqlDelete2.= " WHERE rowid = ".$rowid." AND fk_socpeople = ".$idusuario." AND element_id = ".$object->fk_project;

	$db->query($sqlDelete2);

}


/*
 * View
 */

 $form = new Form($db);
 $formproject = new FormProjets($db);
 
 //$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
 $help_url = '';
 llxHeader('', $langs->trans('Mo - Trabajadores'), $help_url);
 
 if ($id > 0 || !empty($ref)) {
     $object->fetch_thirdparty();
 
     $head = moPrepareHead($object);
 
     print dol_get_fiche_head($head, 'trabajadores', $langs->trans("ManufacturingOrder"), -1, $object->picto);
 
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
		'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 1),
		'tipo_trabajador' => array('label' => $langs->trans("Tipo de Trabajador"), 'checked' => 1),
		'estatus' => array('label' => $langs->trans("Estatus"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfieldsFases = array(
			'nombre' => array('label' => $langs->trans("Nombre"), 'checked' => 0),
			'tipo_trabajador' => array('label' => $langs->trans("Tipo de Trabajador"), 'checked' => 0),
			'estatus' => array('label' => $langs->trans("Estatus"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfieldsFases[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfieldsFases, $varpage); // This also change content of $arrayfieldsFases

	$id_usuario = $object->id;

	if ($task != "") {

        $sqlTarea = " SELECT * FROM ".MAIN_DB_PREFIX."projet_task ";
		$sqlTarea.= " WHERE rowid = ".$task;
		
		$resultTarea = $db->query($sqlTarea);
		$tarea = $db->fetch_object($resultTarea);
		$numTareas = $db->num_rows($resultTarea);

		$sqlTrabajadores = " SELECT u.rowid as idusuario, ec.rowid as idelemento, u.firstname, u.lastname, tc.source, tc.libelle, tc.rowid as idcontact FROM ". MAIN_DB_PREFIX ."user u ";
		$sqlTrabajadores.= " INNER JOIN ". MAIN_DB_PREFIX ."element_contact ec ON ec.fk_socpeople = u.rowid ";
		$sqlTrabajadores.= " INNER JOIN ". MAIN_DB_PREFIX ."c_type_contact tc ON tc.rowid = ec.fk_c_type_contact ";
		$sqlTrabajadores.= " WHERE ec.element_id = ".$task;

		$resultTrabajadores = $db->query($sqlTrabajadores);

		$num = $db->num_rows($resultTrabajadores);

		$nbtotalofrecords = $num;
		
	}

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;














    //TAREA
    print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?id=".$_GET['id']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$arrayfieldsTareas = array(
		'ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
		'etiqueta' => array('label' => $langs->trans("Etiqueta"), 'checked' => 1),
		'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 1),
		'fecha_inicio' => array('label' => $langs->trans("Fecha de Inicio"), 'checked' => 1),
		'fecha_limite' => array('label' => $langs->trans("Tipo"), 'checked' => 1),
		'tiempo_dedicado' => array('label' => $langs->trans("Tiempo Dedicado"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields2"])) {
		$fieldsSelected2 = $_POST["selectedfields2"];
		$fieldsSelectedArray2 = explode(",", $fieldsSelected2);

		$arrayfieldsTareas = array(
			'ref' => array('label' => $langs->trans("Ref"), 'checked' => 0),
			'etiqueta' => array('label' => $langs->trans("Etiqueta"), 'checked' => 0),
			'descripcion' => array('label' => $langs->trans("Descripcion"), 'checked' => 0),
			'fecha_inicio' => array('label' => $langs->trans("Fecha de Inicio"), 'checked' => 0),
			'fecha_limite' => array('label' => $langs->trans("fecha Límite"), 'checked' => 0),
			'tiempo_dedicado' => array('label' => $langs->trans("Tiempo Dedicado"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray2 as $key => $value) {
			$arrayfieldsTareas[$value]["checked"] = 1;
		}
	}

	$varpage2 = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields2 = $form->multiSelectArrayWithCheckbox('selectedfields2', $arrayfieldsTareas, $varpage2); // This also change content of $arrayfieldsFases

    $i = 0;

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;	

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print_barre_liste($langs->trans("Tareas"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $numTareas, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

    //print '<div class="div-table-responsive">';
    print '<table class="tagtable liste">' . "\n";

    print "
        <form method='POST' action='' name='formfilter' autocomplete='off'>
        <tr class='liste_titre_filter'>";
    if (!empty($arrayfieldsTareas['ref']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="number" name="search_ref">';
        print '</td>';
    }
    if (!empty($arrayfieldsTareas['etiqueta']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="text" name="search_etiqueta">';
        print '</td>';
    }
    if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="text" name="search_descripcion">';
        print '</td>';
    }
    if (!empty($arrayfieldsTareas['fecha_inicio']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="text" name="search_fecha_inicio">';
        print '</td>';
    }
    if (!empty($arrayfieldsTareas['fecha_limite']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="text" name="search_fecha_limite">';
        print '</td>';
    }
    if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked'])) {
        print '<td class="center liste_titre center">';
        print '<input class="flat maxwidth75imp" type="text" name="search_tiempo_dedicado">';
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

	if (!empty($arrayfieldsTareas['ref']['checked'])) {
		print "<th class='center liste_titre' title='Ref'>";
		print "<a class='reposition' href=''>Ref</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['etiqueta']['checked'])) {
		print "<th class='center liste_titre' title='Etiqueta'>";
		print "<a class='reposition' href=''>Etiqueta</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['descripcion']['checked'])) {
		print "<th class='center liste_titre' title='Descripcion'>";
		print "<a class='reposition' href=''>Descripcion</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['fecha_inicio']['checked'])) {
		print "<th class='center liste_titre' title='Fecha de Inicio'>";
		print "<a class='reposition' href=''>Fecha de Inicio</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['fecha_limite']['checked'])) {
		print "<th class='center liste_titre' title='Fecha Límite'>";
		print "<a class='reposition' href=''>Fecha Límite</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked'])) {
		print "<th class='center liste_titre' title='Tiempo Dedicado'>";
		print "<a class='reposition' href=''>Tiempo Dedicado</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields2, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_ref']) && ($_POST['search_ref']) != "") {
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
	/*$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;*/

	//while ($i < $num) {

		//$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		if (!empty($arrayfieldsTareas['ref']['checked']))	print "<td class='center' tdoverflowmax200'><a href='../projet/tasks/task.php?id=".$tarea->rowid."'>" . $tarea->ref . "</a></td> ";

		if (!empty($arrayfieldsTareas['etiqueta']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->label . "</td> ";

		if (!empty($arrayfieldsTareas['descripcion']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->description . "</td> ";

		if (!empty($arrayfieldsTareas['fecha_inicio']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->dateo . "</td> ";

		if (!empty($arrayfieldsTareas['fecha_limite']['checked']))	print "<td class='center' tdoverflowmax200'>" . $tarea->datee . "</td> ";

        $minT = $tarea->duration_effective / 60;

		// Para sacar horas y minutos
		$horas = floor($minT / 60);
		$minutosRest = $minT % 60;

		if (!empty($arrayfieldsTareas['tiempo_dedicado']['checked']))	print "<td class='center' tdoverflowmax200'>".$horas."H y ".$minutosRest."min</td> ";

		if ($user->rights->adherent->configurer) {
			//print '<td class="center"><a class="editfielda" href="../don/card.php?action=edit&rowid=' . $datos->fk_object . '">' . img_edit() . '</a></td>';
			print '<td class="center">&nbsp;</td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";
		//$i++;
	//}
	print "</table>";

	print '</form>';
















	
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Trabajadores"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, '', '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>";
	if (!empty($arrayfieldsFases['nombre']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_nombre">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['tipo_trabajador']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_tipo_trabajador">';
		print '</td>';
	}
	if (!empty($arrayfieldsFases['estatus']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_estatus">';
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

	if (!empty($arrayfieldsFases['nombre']['checked'])) {
		print "<th class='center liste_titre' title='Nombre'>";
		print "<a class='reposition' href=''>Nombre</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['tipo_trabajador']['checked'])) {
		print "<th class='center liste_titre' title='Tipo de Trabajador'>";
		print "<a class='reposition' href=''>Tipo de Trabajador</a>";
		print "</th>";
	}

	if (!empty($arrayfieldsFases['estatus']['checked'])) {
		print "<th class='center liste_titre' title='Estatus'>";
		print "<a class='reposition' href=''>Estatus</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
		
		</tr>
		";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';

	while ($trabajador = $db->fetch_object($resultTrabajadores)) {

		print '<tr class="oddeven">';

		if ($trabajador->libelle == "Responsable") {
			$libelle = "Responsable";
		} else {
			$libelle = "Participante";
		}

		if ($trabajador->source == "internal") {
			$source = "Interno";
		} else {
			$source = "Externo";
		}

		if (!empty($arrayfieldsFases['nombre']['checked']))	print "<td class='center' tdoverflowmax200'>" . $trabajador->firstname." ".$trabajador->lastname . "</td> ";

		if (!empty($arrayfieldsFases['tipo_trabajador']['checked']))	print "<td class='center' tdoverflowmax200'>" . $libelle . "</td> ";

		if (!empty($arrayfieldsFases['estatus']['checked']))	print "<td class='center' tdoverflowmax200'>" . $source . "</td> ";

		if ($user->rights->adherent->configurer) {
			print '<td class="center"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=borrarTrabajador&id='.$id.'&rowid=' . $trabajador->idelemento . '&idusuario='.$trabajador->idusuario.'">' . img_delete() . '</a></td>';
		} else {
			print '<td class="center">&nbsp;</td>';
		}
		print "</tr>";

	}
	print "</table>";

	print '</form>';

    if ($task != "") {
        print '<div class="tabsAction">';
        print '<a class="butAction" type="button" href="'. $_SERVER["PHP_SELF"] .'?action=addATrabajador&id='.$id.'">Nuevo trabajador</a>';
        print '</div>';
    }
 
     print dol_get_fiche_end();

 }
 
 if ($action == "addATrabajador") {

	$id = $_GET['id'];

	$sqlUsuarios = " SELECT rowid, firstname, lastname FROM ". MAIN_DB_PREFIX ."user u";

	$resultUsuarios = $db->query($sqlUsuarios);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Añadir trabajador</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 200.928px;" class="ui-dialog-content ui-widget-content">
				<div>
					<table>
						<tbody>
							<tr>
								<td>
									<label for="usuario">Usuario</label>
								</td>
								<td>
									<select name="usuario" class="select-usuario">
									<option value=-1>&nbsp</option>';

									while ($usu = $db->fetch_object($resultUsuarios)) {
										print '<option value='.$usu->rowid.'>'.$usu->firstname.' '.$usu->lastname.'</option>';
									}

									print '</select>
								</td>
							</tr>
							<tr>
								<td>
									<label for="tipo">Tipo</label>
								</td>
								<td>
									<select name="tipo" class="select-tipo">
										<option value=1 selected>Participante</option>
										<option value=2>Responsable</option>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addTrabajadorFinal">
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

if ($action == "borrarTrabajador") {

	$id = $_GET['id'];
	$rowid = $_GET['rowid'];
	$idusuario = $_GET['idusuario'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&rowid='.$rowid.'&idusuario='.$idusuario.'">
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
								<span class="field">¿Seguro que deseas desasignar a este trabajador?</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="deleteTrabajador">
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

	$(".select-usuario").select2({
		width: "200%" // Esto hará que el campo de selección ocupe todo el ancho disponible
	});
	$(".select-tipo").select2({
		width: "100%" // Esto hará que el campo de selección ocupe todo el ancho disponible
	});

</script>';


 // End of page
 llxFooter();
 $db->close();