<?php

require '../../../khonos-ORTRAT/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once '../chrono/class/tiempotarea.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

if (!$user->rights->projet->lire) {
    accessforbidden();
}

$title = $langs->trans("Listado tiempo tareas");
$langs->load("projects");

llxHeader('', $title);

$user_id = $user->id;



if ($_GET["action"] == "edit") {

    $id = $_GET['id'];

    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event WHERE rowid = " . intval($id);
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $dat = $db->fetch_object($resql);

        $sqlProyecto = "SELECT title FROM " . MAIN_DB_PREFIX . "projet WHERE rowid = " . intval($dat->fk_project);
        $resqlProyecto = $db->query($sqlProyecto);
        $proyecto = $db->fetch_object($resqlProyecto);

        $sqlUsuario = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . intval($dat->fk_userid);
        $resqlUsuario = $db->query($sqlUsuario);
        $usuario = $db->fetch_object($resqlUsuario);

        $sqlTarea = "SELECT label FROM " . MAIN_DB_PREFIX . "projet_task WHERE rowid = " . intval($dat->fk_task);
        $resqlTarea = $db->query($sqlTarea);
        $tarea = $db->fetch_object($resqlTarea);

        if ($dat->event_type == 3) {
            $sqlUltimaEntrada = "SELECT date_time_event FROM " . MAIN_DB_PREFIX . "attendance_event WHERE token = '" . $db->escape($dat->token) . "' AND event_type IN (1, 2) ORDER BY date_time_event DESC LIMIT 1";
            $resqlUltimaEntrada = $db->query($sqlUltimaEntrada);
            if ($resqlUltimaEntrada && $db->num_rows($resqlUltimaEntrada) > 0) {
                $entrada = $db->fetch_object($resqlUltimaEntrada);
                $lastEntryTime = $db->jdate($entrada->date_time_event);
                $exitTime = $db->jdate($dat->date_time_event);

                $elapsedTime = $exitTime - $lastEntryTime;

                $hours = floor($elapsedTime / 3600);
                $minutes = floor(($elapsedTime % 3600) / 60);
                $seconds = $elapsedTime % 60;

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
    $id = intval($_GET['id']);

    $fecha_inicio = $_POST['fecha_inicio'];
    $nota = $_POST['nota'];

    if (empty($fecha_inicio)) {
        setEventMessage('Error: La fecha de inicio no puede estar vacía.', 'errors');
    } else {
        $sqlAttendance = "SELECT * FROM " . MAIN_DB_PREFIX . "attendance_event WHERE rowid = " . $id;
        $resqlAttendance = $db->query($sqlAttendance);

        if ($resqlAttendance && $db->num_rows($resqlAttendance) > 0) {
            $datAttendance = $db->fetch_object($resqlAttendance);
            $eventType = $datAttendance->event_type;
            $token = $db->escape($datAttendance->token);
            $userStartTime = strtotime($fecha_inicio);
            $isTimeValid = true;
            $otherDateTimeTimestamp = null;

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
                    setEventMessage('Error: No se encontró el evento de salida correspondiente.', 'errors');
                    $isTimeValid = false;
                }
            } elseif ($eventType == 3) { // Es una salida
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
                    setEventMessage('Error: No se encontró el evento de entrada correspondiente.', 'errors');
                    $isTimeValid = false;
                }
            }

            if ($isTimeValid) {
                // Si la hora es válida se actualiza el registro
                $formattedDate = date('Y-m-d H:i:s', strtotime($datAttendance->date_time_event));
                $taskDuration = abs($userStartTime - $otherDateTimeTimestamp);

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

if (isset($_POST['Borrar'])) {
    $token =$_GET['token'];

    $db->begin();
    try {
        $sqlUpd = "DELETE FROM " . MAIN_DB_PREFIX . "attendance_event WHERE token = " . $token;

        $resultUpd = $db->query($sqlUpd);

        if (!$resultUpd) {
            throw new Exception("Error en el borrado de la nota");
        }

        setEventMessage("La hora imputada ha sido borrada correctamente.", "mesgs");

    } catch (Exception $e) {
        $db->rollback();
        setEventMessage($e->getMessage(), "errors");
        exit;
    }

    $db->commit();
}
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' .
           str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' .
           str_pad($seconds, 2, '0', STR_PAD_LEFT);
}

$perPage = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$sortorder = 'DESC';
$sortfield = 't.rowid';

$sortorder=isset($_GET['sortorder']) ? $_GET['sortorder'] : $sortorder;
$sortfield=isset($_GET['sortfield']) ? $_GET['sortfield'] : $sortfield;

$sqlUpd = "SELECT DISTINCT t.rowid, t.*, p.title AS project_title, pt.label AS task_label 
           FROM khns_attendance_event AS t
           INNER JOIN " . MAIN_DB_PREFIX . "projet AS p ON t.fk_project = p.rowid
           INNER JOIN " . MAIN_DB_PREFIX . "projet_task AS pt ON p.rowid = pt.fk_projet
           WHERE 1 = 1 ";

 
if (isset($_GET['filter_type']) && $_GET['filter_type'] === 'all') {
    if (isset($_GET['ls_userid']) && intval($_GET['ls_userid']) > 0) {
        $userid = intval($_GET['ls_userid']);
        $sqlUpd .= " AND t.fk_userid = $userid"; 
    }
} elseif (isset($_GET['filter_type']) && $_GET['filter_type'] === 'my_records') {
    $sqlUpd .= " AND t.fk_userid = " . intval($user->id);
} else {
    if (!$user->admin) {
        $sqlUpd .= " AND t.fk_userid = " . intval($user->id);
    }
}


if (isset($_GET['ls_event_type']) && intval($_GET['ls_event_type']) !== 0) {
    $eventype = intval($_GET['ls_event_type']);
    $sqlUpd .= " AND t.event_type = $eventype"; 
}

if (isset($_GET['ls_event_location_ref']) && !empty($_GET['ls_event_location_ref'])) {
    $eventLocationRef = $db->escape($_GET['ls_event_location_ref']); 
    $sqlUpd .= " AND p.ref LIKE '%$eventLocationRef%'";
}

if (isset($_GET['ls_nombre_proyecto']) && !empty($_GET['ls_nombre_proyecto'])) {
    $nombre = $db->escape($_GET['ls_nombre_proyecto']); 
    $sqlUpd .= " AND p.title LIKE '%$nombre%'";
}
if (isset($_GET["ls_ref_tarea"]) && !empty($_GET["ls_ref_tarea"])) {
    $tarea_ref = $db->escape($_GET["ls_ref_tarea"]);
    $sqlUpd.= " AND pt.ref LIKE '%$tarea_ref%'";
}

if (isset($_GET["ls_nombre_tarea"]) && !empty($_GET["ls_nombre_tarea"])) {
    $tarea_nom = $db->escape($_GET["ls_nombre_tarea"]);
    $sqlUpd .= " AND pt.label LIKE '%$tarea_nom%'";
}

if (isset($_GET["ls_date_time"]) && !empty($_GET["ls_date_time"])) {
    $date_time = $db->escape($_GET["ls_date_time"]);

    $sqlUpd .= " AND DATE(t.date_time_event) = '".$date_time."'";
}
$sqlUpd .= " GROUP BY t.rowid";


$sqlUpd .= " ORDER BY $sortfield $sortorder 
             LIMIT $offset, $perPage";

$resultUpd = $db->query($sqlUpd);
// print $sqlUpd;

if ($resultUpd) {
    // Obtener los registros de la tabla khns_attendance_event
    $projects = [];
    while ($obj = $db->fetch_object($resultUpd)) {
        $projects[] = $obj;  // Almacenar los registros
        print  print_r($project, true) ; 

    }
} else {
    dol_print_error($db);
}

if ($projects === -1) {
    dol_print_error($db);
}

// Contar total de registros aplicando los filtros
$countSql = 'SELECT COUNT(*) as total FROM '.MAIN_DB_PREFIX.'attendance_event as t';
$countSql .= ' JOIN ' . MAIN_DB_PREFIX . 'user as u ON t.fk_userid = u.rowid';
$countSql .= ' JOIN ' . MAIN_DB_PREFIX . 'projet as p ON t.fk_project = p.rowid';
$countSql .= ' JOIN ' . MAIN_DB_PREFIX . 'projet_task as pt ON t.fk_task = pt.rowid';
$countSql .= ' WHERE 1 = 1';

if (isset($_GET['ls_event_type']) && intval($_GET['ls_event_type']) !== 0) {
    $eventype = intval($_GET['ls_event_type']);
    $countSql .= " AND t.event_type = $eventype"; 
}

if (isset($_GET['filter_type']) && $_GET['filter_type'] === 'all') {
    if (isset($_GET['ls_userid']) && intval($_GET['ls_userid']) > 0) {
        $userid = intval($_GET['ls_userid']);
        $countSql .= " AND t.fk_userid = $userid"; 
    }
} elseif (isset($_GET['filter_type']) && $_GET['filter_type'] === 'my_records') {
    $countSql .= " AND t.fk_userid = " . intval($user->id);
} else {
    if (!$user->admin) {
        $countSql .= " AND t.fk_userid = " . intval($user->id);
    }
}
if (isset($_GET['ls_nombre_proyecto']) && !empty($_GET['ls_nombre_proyecto'])) {
    $nombre = $db->escape($_GET['ls_nombre_proyecto']);
    $countSql .= " AND p.title LIKE '%$nombre%'";
}

if (isset($_GET["ls_ref_tarea"]) && !empty($_GET["ls_ref_tarea"])) {
    $tarea_ref = $db->escape($_GET["ls_ref_tarea"]);
    $countSql.= " AND pt.ref LIKE '%$tarea_ref%'";
}

if (isset($_GET["ls_nombre_tarea"]) && !empty($_GET["ls_nombre_tarea"])) {
    $tarea_nom = $db->escape($_GET["ls_nombre_tarea"]);
    $countSql .= " AND pt.label LIKE '%$tarea_nom%'";
}
if (isset($_GET["ls_date_time"]) && !empty($_GET["ls_date_time"])) {
    $date_time = $db->escape($_GET["ls_date_time"]);

    $countSql .= " AND DATE(t.date_time_event) = '".$date_time."'";
}




$resqlCount = $db->query($countSql);
$totalRecords = ($resqlCount && $db->num_rows($resqlCount) > 0) ? $db->fetch_object($resqlCount)->total : 0;
$totalPages = ceil($totalRecords / $perPage);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $totalRecords, $totalRecords, 'title_companies', 0, '', '', '');





$isAdmin = $user->admin;

$sql = "SELECT DISTINCT 
            p.rowid AS projectid, 
            p.ref AS projectref, 
            p.title AS projecttitle, 
            t.rowid AS taskid, 
            t.ref AS taskref, 
            t.label AS tasklabel
        FROM 
            " . MAIN_DB_PREFIX . "projet AS p
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "projet_task AS t ON t.fk_projet = p.rowid
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "element_contact AS ecp ON ecp.element_id = p.rowid 
        LEFT JOIN 
            " . MAIN_DB_PREFIX . "element_contact AS ect ON ect.element_id = t.rowid
        WHERE 
            1=1";  

if (!$isAdmin) {
    $sql .= " AND (ecp.fk_socpeople = " . intval($user_id) . " 
                OR ect.fk_socpeople = " . intval($user_id) . ")";
}

$resql = $db->query($sql);
if ($resql) {
    $projectsselect = [];
    $taskselect = [];

    while ($obj = $db->fetch_object($resql)) {
        if (!isset($projectsselect[$obj->projectid])) {
            $projectsselect[$obj->projectid] = [
                'id' => $obj->projectid,
                'ref' => $obj->projectref,
                'title' => $obj->projecttitle
            ];
        }

        if (!empty($obj->taskid)) {
            $taskselect[$obj->taskid] = [
                'id' => $obj->taskid,
                'ref' => $obj->taskref,
                'label' => $obj->tasklabel,
                'projectid' => $obj->projectid  
            ];
        }
    }
} else {
    echo "Error: " . $db->lasterror();
}

if ($totalPages > 1) {
    print '<div class="pagination" style="text-align: center;">'; 
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $page) {
            print '<strong style="font-size: 1.2em; margin: 0 10px;">' . $i . '</strong>';
        } else {
            print '<a href="' . $_SERVER["PHP_SELF"] . '?page=' . $i . '&filter_type=' . (isset($_GET['filter_type']) ? $_GET['filter_type'] : '') . '" style="font-size: 1.2em; margin: 0 10px;">' . $i . '</a>'; 
        }
    }
    print '</div>';
}

print '</tr>';
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
print_liste_field_titre($langs->trans("ProjectRef"), $_SERVER["PHP_SELF"], "p.ref", "", '', '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Nombre del proyecto"), $_SERVER["PHP_SELF"], "p.title", "", '', '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Referencia de tarea"), $_SERVER["PHP_SELF"], "pt.ref", "", '', '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Nombre de tarea"), $_SERVER["PHP_SELF"], "pt.label", "", '', '', $sortfield, $sortorder);
print_liste_field_titre('Date');
print "\n";
print_liste_field_titre($langs->trans("Tiempo transcurrido"), $_SERVER["PHP_SELF"], "t.duration_effective", "", '', '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Note"), $_SERVER["PHP_SELF"], "t.duration_effective", "", '', '', $sortfield, $sortorder);
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

print '<td class = "liste_titre" colspan = "1" >';
print $form->select_dolusers($object->userid, 'ls_userid', 1, '', 0);
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" size = "16" type = "text" name = "ls_event_location_ref" value = "'.$ls_event_location_ref.'">';
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" size = "16" type = "text" name = "ls_nombre_proyecto" value = "'.$ls_event_location_ref.'">';
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" size = "16" type = "text" name = "ls_ref_tarea" value = "'.$ls_event_location_ref.'">';
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" size = "16" type = "text" name = "ls_nombre_tarea" value = "'.$ls_event_location_ref.'">';
print '</td>';

print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" type = "date"   name = "ls_date_time" value = "'.$ls_date_time_event_year.'">';
print '</td>';


print '<td class = "liste_titre" colspan = "1" >';
print '<input class = "flat" size = "16" type = "text" name = "ls_status" value = "'.$ls_status.'">';//FIXME Array
print '</td>';
 print '<td class = "liste_titre" colspan = "1" />';
print '<td >';
print '<button class="butAction" type="submit">Buscar</button>';

print '</td>';
print '</tr>'."\n";
$lastEntryTime = null;

if (!empty($projects)) {
    foreach ($projects as $project) {
        $projectDetails = new Project($db);
        $projectDetails->fetch($project->fk_project);

        $taskDetails = new Task($db);
        $taskDetails->fetch($project->fk_task);

        $userDetails = new User($db);
        $userDetails->fetch($project->fk_userid);

        print '<tr>';
        print '<td>' . ($project->event_type == 1 || $project->event_type == 2 ? 'Entrada' : ($project->event_type == 3 ? 'Salida' : '')) . '</td>';
        print '<td>' . $userDetails->getFullName($langs) . '</td>';
        print '<td><a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $project->fk_project . '">' .$projectDetails->ref. '</a></td>';
        print '<td>' . $projectDetails->title . '</td>';
        print '<td><a href="' . DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $project->fk_task . '">' .$taskDetails->ref. '</a></td>';
        print '<td><a href="' . DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $project->fk_task . '">' .$taskDetails->label. '</a></td>';

        $date_start = '';
        if (!empty($project->date_time_event)) {
            $dateTime = new DateTime($project->date_time_event);
            $dateTime->setTimezone(new DateTimeZone('CET')); 
            $date_start = $dateTime->format('Y-m-d H:i:s'); 
        }
        
        
        print '<td>' . $date_start . '</td>';
        
        if ($project->event_type == 1 || $project->event_type == 2) {
            print '<td>--</td>'; 
        } elseif ($project->event_type == 3) { 
            
            $query = "SELECT date_time_event FROM khns_attendance_event 
                      WHERE token = '{$project->token}' AND event_type = 2 
                      ORDER BY date_time_event DESC LIMIT 1"; 
            
            $lastEntryResult = $db->query($query);
            $lastEntry = $lastEntryResult->fetch_object();
    
            if ($lastEntry) {
                $lastEntryTime = $db->jdate($lastEntry->date_time_event);
                $exitTime = $db->jdate($project->date_time_event);
                $elapsedTime = $exitTime - $lastEntryTime;
    
                // Convertir a horas, minutos y segundos
                $hours = floor($elapsedTime / 3600);
                $minutes = floor(($elapsedTime % 3600) / 60);
                $seconds = $elapsedTime % 60;
    
                print '<td>' . sprintf('%02d h %02d m %02d s', $hours, $minutes, $seconds) . '</td>';
            } else {
                print '<td>--</td>'; 
            }
        } else {
            print '<td>--</td>'; 
        }
                
        
        $total_time_seconds = $project->tiempo_transcurrido;
        $hours = floor($total_time_seconds / 3600);
        $minutes = floor(($total_time_seconds % 3600) / 60);
        $seconds = $total_time_seconds % 60;

        print '<td>'.$project->note.'</td>';
        print '<td>';
        print '<a class="fas fa-clock pictodelete" style="" title="Editar tiempo" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $project->rowid . '"></a>';
        print '<a class="fas fa-trash pictodelete" style="" title="Eliminar" href="' . $_SERVER["PHP_SELF"] . '?action=delete&token=' . $project->token . '"></a>';
        print'</td>';
        print '</tr>';
    }
}

print '</table>';


llxFooter();
?>
