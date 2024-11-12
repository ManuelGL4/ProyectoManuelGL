<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

/*SI EL USUARIO NO TIENE PERMISOS*/
if (!$user->rights->projet->lire) {
    accessforbidden();
}


$user_id = $user->id;
if (isset($_POST['button_removefilter'])) {
    $search_project_ref = '';
    $search_task_label = '';
} else {
    $search_project_ref = isset($_POST['search_project_ref']) ? trim($_POST['search_project_ref']) : '';
    $search_task_label = isset($_POST['search_task_label']) ? trim($_POST['search_task_label']) : '';
}

//OBTENER PROYECTOS Y TAREAS A LOS QUE EL USUARIO ESTE ASIGNADO
$sql = "SELECT DISTINCT 
            p.rowid AS projectid, 
            p.ref AS projectref, 
            p.title AS projecttitle, 
            p.fk_statut AS projectstatus, 
            p.datee AS projectdatee, 
            p.fk_opp_status, 
            p.public, 
            p.fk_user_creat AS projectusercreate, 
            p.usage_bill_time, 
            s.nom AS name, 
            s.rowid AS socid, 
            t.datec AS date_creation, 
            t.dateo AS date_start, 
            t.datee AS date_end, 
            t.tms AS date_update, 
            t.rowid AS id, 
            t.ref, 
            t.label, 
            t.planned_workload, 
            t.duration_effective, 
            t.progress, 
            t.fk_statut, 
            t.description, 
            t.fk_task_parent
        FROM 
            " . MAIN_DB_PREFIX . "projet AS p
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "societe AS s ON p.fk_soc = s.rowid
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "projet_task AS t ON t.fk_projet = p.rowid
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "element_contact AS ecp ON ecp.element_id = p.rowid 
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "element_contact AS ect ON ect.element_id = t.rowid
        WHERE 
            ecp.fk_socpeople = " . $user_id . " 
            AND ect.fk_socpeople = " . $user_id . "  
";

//FILTRADO
if (!empty($search_project_ref)) {
    $sql .= " AND (p.ref LIKE '%" . $db->escape($search_project_ref) . "%' OR p.title LIKE '%" . $db->escape($search_project_ref) . "%')";
}

if (!empty($search_task_label)) {
    $sql .= " AND t.label LIKE '%" . $db->escape($search_task_label) . "%'";
}
$sql .= " ORDER BY p.rowid;";

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
}

//Agrupamos las tareas por proyectos
$projects = [];
while ($obj = $db->fetch_object($resql)) {
    // Si el proyecto no esta todavia en el array se inserta
    if (!isset($projects[$obj->projectid])) {
        $projects[$obj->projectid] = [
            'ref' => $obj->projectref,
            'title' => $obj->projecttitle,
            'tasks' => [],
            'projectid' => $obj->projectid
        ];
    }
    $projects[$obj->projectid]['tasks'][] = $obj;
}





/**
 * 
 * 
 * 
 *      VISTA 
 * 
 * 
 *  */


/*Todo lo relaccionado con estilos en dolibarr*/ 
$title = $langs->trans("Chrono tiempos tareas");
$langs->load("projects");

print '<link rel="stylesheet" type="text/css" href="index.css">'; 

llxHeader("", $title);
print load_fiche_titre($langs->trans("Chrono"), '', 'object_informacion_formacion.png@recursoshumanos');
print '<script src="script.js"></script>';

//Formulario busqueda
print '
<table class="noborder centpercent">
    <tr class="liste_titre">
        <form name="buscar" method="POST" action="'.$_SERVER["PHP_SELF"].($project->id > 0 ? '?id='.$project->id : '').'">
            <div class="div-table-responsive" style="min-height: 0px !important;">
                <tbody>
                    <tr class="liste_titre_filter">
                        <td class="liste_titre">
                            <label>Nombre del Proyecto</label><br>
                            <input class="flat" type="text" name="search_project_ref" value="'. htmlspecialchars($search_project_ref) .'">
                        </td>
                        <td class="liste_titre">
                            <label>Nombre de la tarea</label><br>
                            <input class="flat" type="text" name="search_task_label" value="'. htmlspecialchars($search_task_label) .'">
                        </td>
                        <td class="liste_titre">
                            <button type="submit" class="liste_titre button_search" name="button_search_x" value="x">
                                <span class="fa fa-search"></span>
                            </button>
                            <button type="submit" class="liste_titre button_removefilter" name="button_removefilter" value="1">
                                <span class="fa fa-remove"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </div>
        </form>
    </tr>
    <tr>
        <td class="liste_titre">
            <div class="nowraponall">
                <div style="text-align: right;">
                    <button type="button" id="resetButton"  class="butAction">Nuevo Día / Reestablecer Todos los Tiempos</button>
                </div>
            </div>
        </td>
        <td>
        </td>
        <td>
        </td>
    </tr>
</table>
<br>';

/*MODAL PARA RESEETEAR TODOS LOS CONTADORES*/
print '<div id="confirmModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
    <div style="background: white; margin: 15% auto; padding: 20px; border-radius: 5px; width: 300px; text-align: center;">
        <h3>Confirmar Acción</h3>
        <p>¿Está seguro de que desea reiniciar todos los tiempos?</p>
        <button id="confirmReset" class="button button-confirm">Sí</button>
        <button id="cancelReset" class="button button-cancel">No</button>
    </div>
</div>';


/*TABLA PROYECTOS Y TAREAS CON LOS BOTONES DE ACCION*/ 
foreach ($projects as $project) {
    print "<div class='project-container'>";
    print "<div class='project-title'>" . $project['title'] . " - <a href='" . DOL_URL_ROOT . "/projet/card.php?id=" . $project['projectid'] . "'>" . $project['ref'] . "</a></div>";

    foreach ($project['tasks'] as $task) {
        print "<div class='task-container'>";
        print "<div class='status-icon' id='icon-" . $task->id . "'>";
        print "<img src='img/notstarted.png' alt='Iniciar' style='height: 40px;' onclick='startTimer(" . intval($task->id) . ", \"" . htmlspecialchars($user->api_key) . "\", \"" . intval($user->id) . "\")'>";
  
        print "</div>";
        print "<div class='task-info'>";
        print "<h3><a href='" . DOL_URL_ROOT . "/projet/tasks/task.php?id=" . $task->id . "'>" . $task->label . "</a></h3>";
        print "<p>Hora de inicio: <span id='start-time-" . $task->id . "'>no iniciado</span></p>";
        print "<p>Tiempo transcurrido: <span id='time-" . $task->id . "'>no iniciado</span></p>";
        print "</div>";
        print "<div class='task-controls'>";
        print "<div class='status-icon' id='reset-" . $task->id . "' style='display: none;' onclick='resetTimer(" . $task->id . ")'>"; 
        print "</div>";
        print "</div>";
        print "<input type='hidden' data-task-id='" . $task->id . "' value='" . $project['projectid'] . "'>"; 
        print "<input type='text' class='notes' placeholder='Notas adicionales' data-task-id='" . $task->id . "'>";
        print "</div>"; 
    }
    

    print "</div>";
}

/*BOTONES CON INFORMACION GENERAL*/ 
print '<div class="search-container">';
print '<div class="time-buttons">';
print '<button class=" button-cancel butAction" id="current-time">Hora actual: 00:00:00</button>';
print '<button class=" button-cancel butAction" id="total-time">Tiempo total: 0h 0m 0s</button>';
print '</div>';
print '</div>';
print '</form>';


print '<div id="successMessage" style="display: none; color: green; margin-top: 20px;">';
print 'Tiempo guardado correctamente.';
print '</div>';


llxFooter();
$db->close();
?>
