<?php

require '../../../khonos-ORTRAT/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once '../chrono/class/tiempotarea.class.php';

if (!$user->rights->projet->lire) {
    accessforbidden();
}

$title = $langs->trans("Listado tiempo proyectos");
$langs->load("projects");

// Obtener parámetros de filtrado usando el método GET
$view_all_projects = isset($_GET['filter']) && $_GET['filter'] === 'all';
$selected_project = isset($_GET['project']) ? intval($_GET['project']) : 0;
$selected_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$date_start_input = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end_input = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Obtener la página actual
$perPage = 10; // Registros por página
$user_id = $user->id;
$project_ids = [];

// Consulta para obtener proyectos en función del filtro
if ($view_all_projects && $user->admin) {
    // Si es admin y selecciona "Ver todos los proyectos"
    $sql_projects = "SELECT DISTINCT p.rowid 
                     FROM " . MAIN_DB_PREFIX . "projet AS p";
} else {
    // Ver solo proyectos asignados al usuario
    $sql_projects = "SELECT DISTINCT p.rowid 
                     FROM " . MAIN_DB_PREFIX . "projet AS p
                     LEFT JOIN " . MAIN_DB_PREFIX . "element_contact AS ecp ON ecp.element_id = p.rowid 
                     WHERE ecp.fk_socpeople = " . $user_id;
}

$resql_projects = $db->query($sql_projects);
if ($resql_projects) {
    while ($obj = $db->fetch_object($resql_projects)) {
        $project_ids[] = $obj->rowid;
    }
}


if (empty($project_ids)) {
    echo "No hay proyectos asignados a este usuario.";
    exit;
}



// Consulta para obtener los proyectos y tareas sin duplicados
$sqlUpd = "SELECT t.rowid, t.*, p.title AS project_title, pt.label AS task_label
           FROM khns_attendance_event AS t
           INNER JOIN " . MAIN_DB_PREFIX . "projet AS p ON t.fk_project = p.rowid
           INNER JOIN " . MAIN_DB_PREFIX . "projet_task AS pt ON t.fk_task = pt.rowid
              WHERE 1 = 1 ";

 
if (isset($_GET['filter_type']) && $_GET['filter_type'] === 'all') {
    // Si `filter_type=all`, aplica solo `ls_userid` si está presente, distinto de 0 y distinto de -1
    if (isset($_GET['ls_userid']) && intval($_GET['ls_userid']) > 0) {
        $userid = intval($_GET['ls_userid']);
        $sqlUpd .= " AND t.fk_userid = $userid"; 
    }
} elseif (isset($_GET['filter_type']) && $_GET['filter_type'] === 'assigned') {
    // Si `filter_type=my_records`, filtra solo por el usuario actual
    $sqlUpd .= " AND t.fk_userid = " . intval($user->id);
} else {
    // En caso de no ser admin, aplica el filtro por usuario actual
    if (!$user->admin) {
        $sqlUpd .= " AND t.fk_userid = " . intval($user->id);
    }
}


if (isset($_GET['ls_event_type']) && intval($_GET['ls_event_type']) !== 0) {
    $eventype = intval($_GET['ls_event_type']);
    $sqlUpd .= " AND t.event_type = $eventype"; 
}

if (isset($_GET['ls_event_location_ref']) && !empty($_GET['ls_event_location_ref'])) {
    $eventLocationRef = $db->escape($_GET['ls_event_location_ref']); // Escapa el valor para evitar inyecciones SQL
    $sqlUpd .= " AND p.ref LIKE '%$eventLocationRef%'";
}

if (isset($_GET['ls_nombre_proyecto']) && !empty($_GET['ls_nombre_proyecto'])) {
    $nombre = $db->escape($_GET['ls_nombre_proyecto']); // Escapa el valor para evitar inyecciones SQL
    $sqlUpd .= " AND p.title LIKE '%$nombre%'";
}
if (isset($_GET["ls_ref_tarea"]) && !empty($_GET["ls_ref_tarea"])) {
    $tarea_ref = $db->escape($_GET["ls_ref_tarea"]);
    $sqlUpd.= " AND pt.ref LIKE '%$tarea_ref%'";
}
//ls_nombre_tarea
if (isset($_GET["ls_nombre_tarea"]) && !empty($_GET["ls_nombre_tarea"])) {
    $tarea_nom = $db->escape($_GET["ls_nombre_tarea"]);
    $sqlUpd .= " AND pt.label LIKE '%$tarea_nom%'";
}
if (isset($_GET["ls_date_time"]) && !empty($_GET["ls_date_time"])) {
    // Asegurarse de que la fecha esté correctamente formateada
    $date_time = $db->escape($_GET["ls_date_time"]);

    // Modificar la consulta para buscar por la fecha (ignorando la hora)
    $sqlUpd .= " AND DATE(t.date_time_event) = '".$date_time."'";
}
$sqlUpd .= " GROUP BY t.rowid";

$perPage = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$sortorder = 'DESC';
$sortfield = 't.rowid';

$sortorder=isset($_GET['sortorder']) ? $_GET['sortorder'] : $sortorder;
$sortfield=isset($_GET['sortfield']) ? $_GET['sortfield'] : $sortfield;

$sqlUpd .= " ORDER BY $sortfield $sortorder 
             LIMIT $offset, $perPage"; // Añadido espacio aquí

$resultUpd = $db->query($sqlUpd);
if ($resultUpd) {
    // Obtener los registros de la tabla khns_attendance_event
    $projects = [];
    while ($obj = $db->fetch_object($resultUpd)) {
        $projects[] = $obj;  // Almacenar los registros
    }
} else {
    dol_print_error($db);
}

$groupedProjects = [];
$projectTotalTime = [];

// Agrupamos los proyectos y sumamos los tiempos sin duplicación
foreach ($projects as $project) {
    if (!isset($groupedProjects[$project->fk_project])) {
        $groupedProjects[$project->fk_project] = [
            'projectid' => $project->fk_project,
            'tasks' => [],
        ];
        $projectTotalTime[$project->fk_project] = 0; // Inicializamos el tiempo total del proyecto
    }
    
    // Solo agregamos la tarea si no está ya en la lista de tareas del proyecto
    if (!in_array($project, $groupedProjects[$project->fk_project]['tasks'], true)) {
        $groupedProjects[$project->fk_project]['tasks'][] = $project;
    }

    // Sumar el tiempo transcurrido
    $projectTotalTime[$project->fk_project] += $project->tiempo_transcurrido; 
}



if ($_GET["action"] == "edit") {

    $id = $_GET['id'];

    // SQL para obtener el registro específico que se va a editar
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event WHERE rowid = " . intval($id);
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $dat = $db->fetch_object($resql);

        // SQL para obtener el proyecto
        $sqlProyecto = "SELECT title FROM " . MAIN_DB_PREFIX . "projet WHERE rowid = " . intval($dat->fk_project);
        $resqlProyecto = $db->query($sqlProyecto);
        $proyecto = $db->fetch_object($resqlProyecto);

        // SQL para obtener el usuario
        $sqlUsuario = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . intval($dat->fk_userid);
        $resqlUsuario = $db->query($sqlUsuario);
        $usuario = $db->fetch_object($resqlUsuario);

        // SQL para obtener la tarea
        $sqlTarea = "SELECT label FROM " . MAIN_DB_PREFIX . "projet_task WHERE rowid = " . intval($dat->fk_task);
        $resqlTarea = $db->query($sqlTarea);
        $tarea = $db->fetch_object($resqlTarea);

        if ($dat->event_type == 3) {
            // SQL para obtener la última entrada relacionada con el mismo token
            $sqlUltimaEntrada = "SELECT date_time_event FROM " . MAIN_DB_PREFIX . "attendance_event WHERE token = '" . $db->escape($dat->token) . "' AND event_type IN (1, 2) ORDER BY date_time_event DESC LIMIT 1";
            $resqlUltimaEntrada = $db->query($sqlUltimaEntrada);
            if ($resqlUltimaEntrada && $db->num_rows($resqlUltimaEntrada) > 0) {
                $entrada = $db->fetch_object($resqlUltimaEntrada);
                $lastEntryTime = $db->jdate($entrada->date_time_event);
                $exitTime = $db->jdate($dat->date_time_event);

                // Calcular el tiempo transcurrido
                $elapsedTime = $exitTime - $lastEntryTime;

                // Convertir a horas, minutos y segundos
                $hours = floor($elapsedTime / 3600);
                $minutes = floor(($elapsedTime % 3600) / 60);
                $seconds = $elapsedTime % 60;

                // Formatear el tiempo transcurrido como "H:M:S"
                $tiempoFormateado = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $tiempoFormateado = '00:00:00'; // Si no hay entrada, mostrar 0
            }
        } else {
            $tiempoFormateado = '00:00:00'; // Para entradas no mostrar tiempo transcurrido
        }

        print '
        <div style="position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center;">
            <form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=save" name="formfilter" autocomplete="off" style="background: white; padding: 20px; border-radius: 8px; width: 500px; text-align: center;">
                <div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle" style="border-bottom: 1px solid #ddd; padding: 10px;">
                    <span id="ui-id-1" class="ui-dialog-title">Edición de Horas</span>
                    <button type="button" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close" onclick="window.history.back();" style="background: none; border: none; cursor: pointer;">
                        <span class="ui-button-icon ui-icon ui-icon-closethick"></span>
                    </button>
                </div>
                <div style="width: auto; min-height: 0px; max-height: none; height: 290px;" class="ui-dialog-content ui-widget-content">
                    <div class="confirmquestions"></div>
                    <div>
                        <table style="width: 100%; margin-bottom: 10px;">
                            <tr>
                                <td><span class="field">Usuario</span></td>
                                <td><input type="text" name="usuario" value="' . htmlspecialchars($usuario->firstname . " " . $usuario->lastname) . '" readonly style="width: 100%; padding: 5px;"></td>
                            </tr>
                            <tr>
                                <td><span class="field">Nombre del proyecto</span></td>
                                <td><input type="text" name="proyecto" value="' . htmlspecialchars($proyecto->title) . '" readonly style="width: 100%; padding: 5px;"></td>
                            </tr>
                            <tr>
                                <td><span class="field">Tarea</span></td>
                                <td><input type="text" name="tarea" value="' . htmlspecialchars($tarea->label) . '" readonly style="width: 100%; padding: 5px;"></td>
                            </tr>';
        
                            if($user->admin){
                                print'
                                <tr>
                                    <td><span class="field">Fecha/Hora de '.($dat->event_type == 2 ? 'entrada' : 'salida') .'</span></td>
                                    <td><input type="datetime-local" name="fecha_inicio" value="' . ($dat->date_time_event) . '" style="width: 100%; padding: 5px;"></td>
                                </tr>';
                            }else{
                                print'
                                <tr>
                                    <td><span class="field">Fecha/Hora de '.($dat->event_type == 2 ? 'entrada' : 'salida') .'</span></td>
                                    <td><input type="datetime-local" name="fecha_inicio" value="' . ($dat->date_time_event) . '" readonly style="width: 100%; padding: 5px;"></td>
                                </tr>';
                            }
        
                            print '
                            <tr>
                                <td><span class="field">Tiempo Transcurrido (H:M:S)</span></td>
                                <td><input type="text" name="tiempo_transcurrido" value="' . htmlspecialchars($tiempoFormateado) . '" placeholder="HH:MM:SS" readonly style="width: 100%; padding: 5px;"></td>
                            </tr>
                            <tr>
                                <td><span class="field">Nota</span></td>
                                <td><input type="text" name="nota" value="' . htmlspecialchars($dat->note) . '" style="width: 100%; padding: 5px;"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="ui-dialog-buttonset" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button type="submit" class="ui-button ui-corner-all ui-widget" name="edit" style="background-color: #5bc0de; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                        Guardar
                    </button>
                    <button type="button" class="ui-button ui-corner-all ui-widget" onclick="window.history.back();" style="background-color: #d9534f; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                        Salir
                    </button>
                </div>
            </form>
        </div>';
        } else {
        print '<p>Error: No se encontró el registro solicitado.</p>';
    }
}

if (isset($_POST['edit'])) {
    // Obtener el ID de la tarea que se va a editar
    $id = intval($_GET['id']);

    // Recoger datos del formulario
    $fecha_inicio = $_POST['fecha_inicio'];
    $nota = $_POST['nota'];

    // Validación básica
    if (empty($fecha_inicio)) {
        setEventMessage('Error: La fecha de inicio no puede estar vacía.', 'errors');
    } else {
        // SQL para obtener el registro específico en attendance_event
        $sqlAttendance = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event WHERE rowid = " . $id;
        $resqlAttendance = $db->query($sqlAttendance);

        if ($resqlAttendance && $db->num_rows($resqlAttendance) > 0) {
            $datAttendance = $db->fetch_object($resqlAttendance);
            $eventType = $datAttendance->event_type;
            $token = $db->escape($datAttendance->token);
            $userStartTime = strtotime($fecha_inicio);
            $isTimeValid = true;
            $otherDateTimeTimestamp = null;

            // Buscar el evento relacionado (entrada o salida) con el mismo token y tipo opuesto
            if ($eventType == 2) { // Es una entrada
                // Buscar la salida correspondiente
                $sqlOtherEvent = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event 
                                  WHERE fk_userid = " . intval($datAttendance->fk_userid) . " 
                                  AND token = '" . $token . "' 
                                  AND event_type = 3"; // Salida
                $resqlOtherEvent = $db->query($sqlOtherEvent);

                if ($resqlOtherEvent && $db->num_rows($resqlOtherEvent) > 0) {
                    $datOtherEvent = $db->fetch_object($resqlOtherEvent);
                    $otherDateTimeTimestamp = strtotime($datOtherEvent->date_time_event);

                    // Validar que la nueva hora de entrada no sea posterior a la hora de salida
                    if ($userStartTime > $otherDateTimeTimestamp) {
                        $isTimeValid = false;
                        setEventMessage('Error: La hora de entrada no puede ser posterior a la hora de salida.', 'errors');
                    }
                } else {
                    // No se encontró la salida correspondiente
                    setEventMessage('Error: No se encontró el evento de salida correspondiente.', 'errors');
                    $isTimeValid = false;
                }
            } elseif ($eventType == 3) { // Es una salida
                // Buscar la entrada correspondiente
                $sqlOtherEvent = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event 
                                  WHERE fk_userid = " . intval($datAttendance->fk_userid) . " 
                                  AND token = '" . $token . "' 
                                  AND event_type = 2"; // Entrada
                $resqlOtherEvent = $db->query($sqlOtherEvent);

                if ($resqlOtherEvent && $db->num_rows($resqlOtherEvent) > 0) {
                    $datOtherEvent = $db->fetch_object($resqlOtherEvent);
                    $otherDateTimeTimestamp = strtotime($datOtherEvent->date_time_event);

                    // Validar que la nueva hora de salida no sea anterior a la hora de entrada
                    if ($userStartTime < $otherDateTimeTimestamp) {
                        $isTimeValid = false;
                        setEventMessage('Error: La hora de salida no puede ser anterior a la hora de entrada.', 'errors');
                    }
                } else {
                    // No se encontró la entrada correspondiente
                    setEventMessage('Error: No se encontró el evento de entrada correspondiente.', 'errors');
                    $isTimeValid = false;
                }
            }

            if ($isTimeValid) {
                // Si la hora es válida, proceder con la actualización
                $formattedDate = date('Y-m-d H:i:s', strtotime($datAttendance->date_time_event));
                $taskDuration = abs($userStartTime - $otherDateTimeTimestamp); // Duración en segundos

                // Actualizar el registro en la tabla projet_task_time
                $sqlUpdateTask = "UPDATE " . MAIN_DB_PREFIX . "projet_task_time SET
                    task_datehour = '" . $db->escape($fecha_inicio) . "',
                    note = '" . $db->escape($nota) . "',
                    task_duration = task_duration + " . $taskDuration . "
                    WHERE fk_user = " . intval($datAttendance->fk_userid) . " 
                    AND task_datehour = '" . $db->escape($formattedDate) . "'";

                if (!$db->query($sqlUpdateTask)) {
                    setEventMessage('Error al actualizar projet_task_time: ' . $db->lasterror(), 'errors');
                }

                // Actualizar el registro en attendance_event
                $sqlUpdateAttendance = "UPDATE " . MAIN_DB_PREFIX . "attendance_event SET
                    date_time_event = '" . $db->escape($fecha_inicio) . "',
                    note = '" . $db->escape($nota) . "',
                    date_modification = NOW()  
                    WHERE rowid = " . $id;

                if ($db->query($sqlUpdateAttendance)) {
                    // Mensaje de éxito
                    setEventMessage("Registro actualizado correctamente", 'mesgs');
                } else {
                    setEventMessage('Error al actualizar attendance_event: ' . $db->lasterror(), 'errors');
                }
            }
        } else {
            setEventMessage('Error: No se encontró el registro de la tabla attendance_event.', 'errors');
        }
    }
}






if ($_GET["action"] == "delete") {
    $token = intval($_GET['token']);

    print '
    <div style="position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center;">
        <form method="POST" action="' . $_SERVER['PHP_SELF'] . '?token=' . $token . '" name="formfilter" autocomplete="off" style="background: white; padding: 20px; border-radius: 8px; width: 400px; text-align: center;">
                    <span id="ui-id-1" class="ui-dialog-title">Borrar Hora Imputada</span>
                    <button type="button" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close" onclick="window.history.back();" style="background: none; border: none; cursor: pointer;">
                        <span class="ui-button-icon ui-icon ui-icon-closethick"></span>
                    </button>
                    <br><br>
                    <p>¿Desea borrar esta hora imputada?</p>

                    <div class="ui-dialog-buttonset" style="display: flex; gap: 10px; justify-content: center;">
                        <button type="submit" class="ui-button ui-corner-all ui-widget" name="Borrar" style="background-color: #d9534f; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                            Borrar
                        </button>
                        <button type="button" class="ui-button ui-corner-all ui-widget" onclick="window.history.back();" style="background-color: #5bc0de; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                            Salir
                        </button>
                </div>
            </div>
        </form>
    </div>';
    
}

// Si pulsamos en confirmar borrado
if (isset($_POST['Borrar'])) {
    $token = intval($_GET['token']); // Asegúrate de que el ID sea un entero

    $db->begin();
    try {
        $sqlUpd = "DELETE FROM " . MAIN_DB_PREFIX . "attendance_event WHERE token = " . $token;

        $resultUpd = $db->query($sqlUpd);

        if (!$resultUpd) {
            throw new Exception("Error en el borrado de la nota");
        }

        // Mensaje de éxito
        setEventMessage("La hora imputada ha sido borrada correctamente.", "mesgs");

    } catch (Exception $e) {
        $db->rollback();
        setEventMessage($e->getMessage(), "errors");
        exit; // Termina la ejecución del script en caso de error
    }

    $db->commit();
}


















llxHeader('', $title);
$totalRecords = count($groupedProjects);
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', '', '', '', $totalRecords, $totalRecords, 'title_companies', 0, '', '', '');

print '<form method = "GET" action = "">';
if ($user->admin) {
    print '<div style="text-align: center; margin-bottom: 20px;">';
    print '<select name="filter_type" onchange="this.form.submit()" style="padding: 5px;">';
    print '<option value="my_records" ' . (isset($_GET['filter_type']) && $_GET['filter_type'] === 'my_records' ? 'selected' : '') . '>Ver Tiempos Tareas Asignadas</option>';
    print '<option value="all" ' . (isset($_GET['filter_type']) && $_GET['filter_type'] === 'all' ? 'selected' : '') . '>Ver todos los tiempos de todos los usuarios</option>';
    print '</select>';
    print '</div>';
}


print '<table class = "liste" width = "100%">'."\n";
        //TITLE
print '<tr class = "liste_titre">';
print_liste_field_titre('Eventtype', $PHP_SELF, 't.event_type', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('User', $PHP_SELF, 't.fk_userid', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Nombre del proyecto"), $_SERVER["PHP_SELF"], "p.title", "", '', '', $sortfield, $sortorder);
print_liste_field_titre('Date');

print "\n";
print_liste_field_titre($langs->trans("Acciones"), $_SERVER["PHP_SELF"], "t.duration_effective", "", '', '', $sortfield, $sortorder);


print "\n";
print '</tr>';
print '<tr class = "liste_titre">';
print '<td class = "liste_titre" colspan = "1" >';
print '<select name = "ls_event_type" class ="litre_titre" colspan="1">';
print '<option value="0">-</option>';
print '<option value="2">Registro</option>';
print '<option value="3">Salida</option>';
print '</select>';
print '</td>';
//Search field foruserid
print '<td class = "liste_titre" colspan = "1" >';
print $form->select_dolusers($object->userid, 'ls_userid', 1, '', 0);
print '</td>';

    // Desplegable de Proyectos
    print '<td class = "liste_titre" colspan = "1" >';

    print '<select name="project" style="padding: 5px;">';
    print '<option value="">Seleccionar Proyecto</option>';
    foreach ($project_ids as $proj_id) {
        $proj = new Project($db);
        $proj->fetch($proj_id);
        print '<option value="' . $proj_id . '"' . ($selected_project == $proj_id ? ' selected' : '') . '>' . $proj->title . '</option>';
    }
    print '</select>';
    print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" type = "date"   name = "ls_date_time" value = "'.$ls_date_time_event_year.'">';
print '</td>';



 print '<td class = "liste_titre" colspan = "1" />';
print '<button class="butAction" type="submit">Buscar</button>';

print '</td>';
print '</tr>'."\n";



print '<table class="noborder centpercent">';

//tabla de proyectos
foreach ($groupedProjects as $project) {
    $projectDetails = new Project($db);
    $projectDetails->fetch($project['projectid']);

    // Inicializar el tiempo total del proyecto en segundos
    $total_time_seconds = 0;

    // Calcular el tiempo total sumando el tiempo de cada tarea
    foreach ($project['tasks'] as $task) {
        if ($task->event_type == 3) { // Solo contar las tareas de salida
            $query = "SELECT date_time_event FROM khns_attendance_event 
                      WHERE token = '{$task->token}' AND event_type = 2 
                      ORDER BY date_time_event DESC LIMIT 1";

            $lastEntryResult = $db->query($query);
            $lastEntry = $lastEntryResult->fetch_object();

            if ($lastEntry) {
                $lastEntryTime = $db->jdate($lastEntry->date_time_event);
                $exitTime = $db->jdate($task->date_time_event);
                $elapsedTime = $exitTime - $lastEntryTime;

                $total_time_seconds += $elapsedTime;
            }
        }
    }

    // Convertir el tiempo total a horas, minutos y segundos
    $hours = floor($total_time_seconds / 3600);
    $minutes = floor(($total_time_seconds % 3600) / 60);
    $seconds = $total_time_seconds % 60;

    // Fila del proyecto
    print '<tr class="liste_titre">';
    print '<td colspan="6"><strong>Proyecto: </strong><a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $project['projectid'] . '">' . $projectDetails->title . '</a> <strong>Total: </strong>' . $hours . ' h ' . $minutes . ' m ' . $seconds . ' s</td>';
    print '</tr>';

    // Calcular total de tareas
    $totalTasks = count($project['tasks']);
    
    // Paginación solo para el proyecto actual
    $totalPages = ceil($totalTasks / $perPage);
    $startIndex = ($page - 1) * $perPage;
    $projectTasks = array_slice($project['tasks'], $startIndex, $perPage);

    // Encabezados de la tabla de tareas
    print '<tr class="liste_titre">';
    print_liste_field_titre('Eventtype', $PHP_SELF, 't.event_type', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre('User', $PHP_SELF, 't.fk_userid', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Inicio"), $_SERVER["PHP_SELF"], "t.dateo", "", $param, '', '', '');
    print_liste_field_titre($langs->trans("Tiempo transcurrido"), $_SERVER["PHP_SELF"], "t.duration_effective", "", $param, '', '', '');
    print_liste_field_titre($langs->trans("Nota"), $_SERVER["PHP_SELF"], "t.duration_effective", "", $param, '', '', '');
    print_liste_field_titre($langs->trans("Acciones"), $_SERVER["PHP_SELF"], "t.duration_effective", "", '', '', $sortfield, $sortorder);
    print '</tr>';
    
    // Mostrar las tareas del proyecto
    foreach ($projectTasks as $task) {
        $userDetails = new User($db);
        $userDetails->fetch($task->fk_userid);

        print '<tr>';
        print '<td>' . ($task->event_type == 1 || $task->event_type == 2 ? 'Entrada' : ($task->event_type == 3 ? 'Salida' : '')) . '</td>';
        

        print '<td>' . $userDetails->getFullName($langs) . '</td>';

        $date_start = '';
        if (!empty($task->date_time_event)) {
            $dateTime = new DateTime($task->date_time_event); // Pasa la fecha y hora directamente
            $dateTime->setTimezone(new DateTimeZone('CET')); // Ajusta la zona horaria a CET
            $date_start = $dateTime->format('Y-m-d H:i:s'); // Formato de fecha y hora en CET
        }
        
        
        print '<td>' . $date_start . '</td>';
        
        if ($task->event_type == 1 || $task->event_type == 2) { // Entrada
            print '<td>--</td>'; // No se muestra tiempo transcurrido para entradas
        } elseif ($task->event_type == 3) { // Salida
            // Realiza una consulta para obtener el date_time_event de la última entrada
            $query = "SELECT date_time_event FROM khns_attendance_event 
                      WHERE token = '{$task->token}' AND event_type = 2 
                      ORDER BY date_time_event DESC LIMIT 1"; 
            
            $lastEntryResult = $db->query($query);
            $lastEntry = $lastEntryResult->fetch_object(); // Recupera el resultado como objeto
    
            if ($lastEntry) {
                $lastEntryTime = $db->jdate($lastEntry->date_time_event);
                $exitTime = $db->jdate($task->date_time_event);
                $elapsedTime = $exitTime - $lastEntryTime;
    
                // Convertir a horas, minutos y segundos
                $hours = floor($elapsedTime / 3600);
                $minutes = floor(($elapsedTime % 3600) / 60);
                $seconds = $elapsedTime % 60;
    
                print '<td>' . sprintf('%02d h %02d m %02d s', $hours, $minutes, $seconds) . '</td>';
            } else {
                print '<td>--</td>'; // Sin entrada previa para este token
            }
        } else {
            print '<td>--</td>'; // Para cualquier caso en que no haya tiempo transcurrido
        }

        print '<td>' . $task->note . '</td>'; // Mostrar nota
        
        // Asegurarse de que no hay <td> vacío
        print '<td>';
        print '<a class="fas fa-clock pictodelete" title="Imputar tiempo" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $task->rowid . '"></a>';
        print '<a class="fas fa-trash pictodelete" title="Eliminar" href="' . $_SERVER["PHP_SELF"] . '?action=delete&token=' . $task->token . '"></a>';
        print '</td>'; // Aquí cerramos la celda de acciones

        print '</tr>';
    }

    // Navegación de páginas
    if ($totalPages > 1) {
        print '<tr><td colspan="6" style="text-align:center;">';
        for ($i = 1; $i <= $totalPages; $i++) {
            print '<a href="' . $_SERVER["PHP_SELF"] . '?page=' . $i . '&project=' . $project['projectid'] . '">' . $i . '</a> ';
        }
        print '</td></tr>';
    }
}

print '</table>';




llxFooter();
$db->close();
?>
