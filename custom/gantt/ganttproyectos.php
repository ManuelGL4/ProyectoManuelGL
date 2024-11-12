<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/projet/ganttproyectos.php
 *	\ingroup    projet
 *	\brief      Gantt diagramm of all projects
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$id = GETPOST('id', 'intcomma');
$ref = GETPOST('ref', 'alpha');

$mode = GETPOST('mode', 'alpha');
$mine = ($mode == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

// Load translation files required by the page
$langs->loadlangs(array('users', 'projects'));


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$userstatic = new User($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$task = new Task($db);

$arrayofcss = array('/includes/jsgantt/jsgantt.css');

if (!empty($conf->use_javascript_ajax)) {
	$arrayofjs = array(
	'/includes/jsgantt/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

//$title=$langs->trans("Gantt").($object->ref?' - '.$object->ref.' '.$object->name:'');
$title = $langs->trans("Gráfico de Gantt");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = ($object->ref ? $object->ref.' '.$object->name.' - ' : '').$langs->trans("Gantt");
}
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("", $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');


$sqlPro = "SELECT rowid, title FROM ".MAIN_DB_PREFIX."projet ";
$resultPro = $db->query($sqlPro);

if (isset($_GET['projectCh'])) {
	$proyectoE = $_GET['projectCh'];
} else {
	$proyectoE = "";
}


print '    <form method="GET" action="' . $_SERVER['PHP_SELF'] . '?action=buscar">
<label for="projectCh">Proyecto:</label>
<select class="select-project" style="width:200px" name="projectCh">;
<option value="0">Todos</option>';

while ($project = $db->fetch_object($resultPro)) {

	if ($project->rowid == $proyectoE) {
		print '<option value = "'.$project->rowid.'" selected>'.$project->title.'</option>';
	} else {
		print '<option value = "'.$project->rowid.'">'.$project->title.'</option>';
	}

}



print '		</select>
<button class="butAction" type="submit">Buscar</button>
</form>';

print "<script>

$(document).ready(function() {
$('.select-project').select2();
});
</script>";

print '<br>';

if ($proyectoE == 0) {
	// 1er parámetro: para delimitar por las tareas de ese usuario
	// 2º parámetro: para delimitar los proyectos de ese usuario
	// 3er parámetro: el id del proyecto
	// 4º parámetro: el id del tercero
	// 5º parámetro: para devolver todos los proyectos y sus tareas
	$tasksarray = $task->getTasksArray(0, 0, 0, 0, 1);
	// We load also tasks limited to a particular user
	//$tasksrole=($_REQUEST["mode"]=='mine' ? $task->getUserRolesForProjectsOrTasks(0,$user,$object->id,0) : '');
} else {

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."projet_task ";
	$sql.= "WHERE fk_projet = ".$proyectoE;

	$sqlResult = $db->query($sql);

	if ($db->num_rows($sqlResult) > 0 ) {
		$tasksarray = $task->getTasksArray(0, 0, $proyectoE, 0, 1);
	} else {
		$tasksarray = array();
	}
}

// PARA MOSTRAR EL GRÁFICO
if (count($tasksarray) > 0) {
	// Show Gant diagram from $taskarray using JSGantt

	$dateformat = $langs->trans("FormatDateShortJQuery"); // Used by include ganttchart.inc.php later
	$datehourformat = $langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery"); // Used by include ganttchart.inc.php later
	$array_contacts = array();
	$tasks = array();
	$task_dependencies = array();
	$taskcursor = 0;
	foreach ($tasksarray as $key => $val) {	// Task array are sorted by "project, position, date"
		$task->fetch($val->id, '');

		$idparent = ($val->fk_parent ? $val->fk_parent : '-'.$val->fk_project); // If start with -, id is a project id

		$tasks[$taskcursor]['task_id'] = $val->id;
		$tasks[$taskcursor]['task_alternate_id'] = ($taskcursor + 1); // An id that has same order than position (required by ganttchart)
		$tasks[$taskcursor]['task_project_id'] = $val->fk_project;
		$tasks[$taskcursor]['task_parent'] = $idparent;

		$tasks[$taskcursor]['task_is_group'] = 0;
		$tasks[$taskcursor]['task_css'] = 'gtaskblue';
		$tasks[$taskcursor]['task_position'] = $val->rang;
		$tasks[$taskcursor]['task_planned_workload'] = $val->planned_workload;

		if ($val->fk_parent != 0 && $task->hasChildren() > 0) {
			$tasks[$taskcursor]['task_is_group'] = 1;
			$tasks[$taskcursor]['task_css'] = 'ggroupblack';
			//$tasks[$taskcursor]['task_css'] = 'gtaskblue';
		} elseif ($task->hasChildren() > 0) {
			$tasks[$taskcursor]['task_is_group'] = 1;
			//$tasks[$taskcursor]['task_is_group'] = 0;
			$tasks[$taskcursor]['task_css'] = 'ggroupblack';
			//$tasks[$taskcursor]['task_css'] = 'gtaskblue';
		}
		$tasks[$taskcursor]['task_milestone'] = '0';
		$tasks[$taskcursor]['task_percent_complete'] = $val->progress;
		//$tasks[$taskcursor]['task_name']=$task->getNomUrl(1);
		//print dol_print_date($val->date_start).dol_print_date($val->date_end).'<br>'."\n";
		$nombre = '<a href="./ganttproyectos.php?action=edit&idTask='.$val->id.'&projectCh='.$proyectoE.'">'.$val->ref.' - '.$val->label.'</a>';
		$tasks[$taskcursor]['task_name'] = $nombre;
		$tasks[$taskcursor]['task_start_date'] = $val->date_start;
		$tasks[$taskcursor]['task_end_date'] = $val->date_end;
		$tasks[$taskcursor]['task_color'] = 'b4d1ea';

		$idofusers = $task->getListContactId('internal');
		$idofcontacts = $task->getListContactId('external');
		$s = '';
		if (count($idofusers) > 0) {
			$s .= $langs->trans("Internals").': ';
			$i = 0;
			foreach ($idofusers as $valid) {
				$userstatic->fetch($valid);
				if ($i) {
					$s .= ', ';
				}
				$s .= $userstatic->login;
				$i++;
			}
		}
		//if (count($idofusers)>0 && (count($idofcontacts)>0)) $s.=' - ';
		if (count($idofcontacts) > 0) {
			if ($s) {
				$s .= ' - ';
			}
			$s .= $langs->trans("Externals").': ';
			$i = 0;
			$contactidfound = array();
			foreach ($idofcontacts as $valid) {
				if (empty($contactidfound[$valid])) {
					$res = $contactstatic->fetch($valid);
					if ($res > 0) {
						if ($i) {
							$s .= ', ';
						}
						$s .= $contactstatic->getFullName($langs);
						$contactidfound[$valid] = 1;
						$i++;
					}
				}
			}
		}

		/* For JSGanttImproved */
		//if ($s) $tasks[$taskcursor]['task_resources']=implode(',',$idofusers);
		$tasks[$taskcursor]['task_resources'] = $s;
		if ($s) {
			$tasks[$taskcursor]['task_resources'] = '<a href="'.DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$val->id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$langs->trans("List").'</a>';
		}
		//print "xxx".$val->id.$tasks[$taskcursor]['task_resources'];
		$tasks[$taskcursor]['note'] = $task->note_public;
		$taskcursor++;
	}

	// Search parent to set task_parent_alternate_id (requird by ganttchart)
	foreach ($tasks as $tmpkey => $tmptask) {
		foreach ($tasks as $tmptask2) {
			if ($tmptask2['task_id'] == $tmptask['task_parent']) {
				$tasks[$tmpkey]['task_parent_alternate_id'] = $tmptask2['task_alternate_id'];
				break;
			}
		}
		if (empty($tasks[$tmpkey]['task_parent_alternate_id'])) {
			$tasks[$tmpkey]['task_parent_alternate_id'] = $tasks[$tmpkey]['task_parent'];
		}
	}

	print "\n";

	if (!empty($conf->use_javascript_ajax)) {
		//var_dump($_SESSION);

		// How the date for data are formated (format used bu jsgantt)
		$dateformatinput = 'yyyy-mm-dd';
		// How the date for data are formated (format used by dol_print_date)
		$dateformatinput2 = 'standard';
		//var_dump($dateformatinput);
		//var_dump($dateformatinput2);

		$moreforfilter = '<div class="liste_titre liste_titre_bydiv centpercent">';

		$moreforfilter .= '<div class="divsearchfield">';
		//$moreforfilter .= $langs->trans("TasksAssignedTo").': ';
		//$moreforfilter .= $form->select_dolusers($tmpuser->id > 0 ? $tmpuser->id : '', 'search_user_id', 1);
		$moreforfilter .= '&nbsp;';
		$moreforfilter .= '</div>';

		$moreforfilter .= '</div>';

		print $moreforfilter;

		print '<div class="div-table-responsive">';

		print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/projet/ganttchart.inc.php';
		print '</div>'."\n";

		print '</div>';
	} else {
		$langs->load("admin");
		print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
	}
} else {
	print '<div class="opacitymedium">'.$langs->trans("NoTasks").'</div>';
}


if ($_GET["action"] == "edit") {

	$idTask = $_GET['idTask'];

	$sql = "SELECT dateo, datee, label FROM ".MAIN_DB_PREFIX."projet_task ";
	$sql.= "WHERE rowid = ".$idTask;

	$sqlResult = $db->query($sql);
	$tarea = $db->fetch_object($sqlResult);


	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?idTask=' . $idTask . '&projectCh='.$proyectoE.'" name="formedittask" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 328.503px; left: 670.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Tarea</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 160.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="field">Título</span>
							</td>
							<td>
								<input type="text" name="tit" value="'.$tarea->label.'">
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Fecha de inicio</span>
							</td>
							<td>
								<input type="datetime-local" name="fechaIni" value="'.$tarea->dateo.'">
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Fecha de finalización</span>
							</td>
							<td>
								<input type="datetime-local" name="fechaFin" value="'.$tarea->datee.'">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="edit">
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


if (isset($_POST['edit'])) {

	$idTask = $_GET['idTask'];
	$titulo = $_POST['tit'];
	$fechaIni = new DateTime($_POST['fechaIni']);
	$fechaIniFormat = $fechaIni->format('Y-m-d H:i:s');

	$fechaFin = new DateTime($_POST['fechaFin']);
	$fechaFinFormat = $fechaFin->format('Y-m-d H:i:s');

	$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."projet_task ";
	$sqlUpdate.= "SET label = '".$titulo."', ";
	$sqlUpdate.= "dateo = '".$fechaIniFormat."', ";
	$sqlUpdate.= "datee = '".$fechaFinFormat."' ";
	$sqlUpdate.= "WHERE rowid = ".$idTask;

	$db->query($sqlUpdate);

	$destination_url = 'ganttproyectos.php?idmenu=293&mainmenu=project&leftmenu=&projectCh='.$proyectoE.'';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}






// End of page
llxFooter();
$db->close();
