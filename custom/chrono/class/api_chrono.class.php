<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024 Comercial ORTRAT <prueba@deltanet.es>
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

use Luracast\Restler\RestException;

dol_include_once('/chrono/class/tiempotarea.class.php');
// Incluir la clase TimesheetAttendanceEvent
dol_include_once('../timesheet/class/TimesheetAttendanceEvent.class.php');
// require_once '../../timesheet/class/TimesheetAttendanceEvent.class.php';
/**
 * \file    mantenimiento/class/api_mantenimiento.class.php
 * \ingroup mantenimiento
 * \brief   File for API management of contratos.
 */

/**
 * API class for mantenimiento contratos
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ChronoApi extends DolibarrApi
{
    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
    }

/**
 * Post nuevo tiempo
 *
 * @param  array $request_data Datos del tiempo a insertar en la base de datos
 *
 * @url    POST chrono/
 *
 * @throws RestException 401 Not allowed
 * @throws RestException 404 Not found
 */
public function insertar($request_data = null)
{
    global $user;
    
    if (is_null($request_data) || !is_array($request_data)) {
        throw new RestException(400, "No data provided or data is not an array");
    }

    $insertedIds = [];
    $now = date('Y-m-d H:i:s'); // Fecha actual
    
    // Preparar datos para insertar en `khns_attendance_event`
    $event_location_ref = isset($request_data['event_location_ref']) ? $this->db->escape($request_data['event_location_ref']) : 'NULL';
    $event_type = isset($request_data['event_type']) ? (int)$request_data['event_type'] : 2;
    $note = isset($request_data['note']) ? $this->db->escape($request_data['note']) : 'NULL';
    $fk_third_party = isset($request_data['fk_third_party']) ? (int)$request_data['fk_third_party'] : 'NULL';
    $fk_task = isset($request_data['fk_task']) ? (int)$request_data['fk_task'] : 'NULL';
    $fk_project = isset($request_data['fk_project']) ? (int)$request_data['fk_project'] : 'NULL';
    $status = isset($request_data['status']) ? (int)$request_data['status'] : 'NULL';
    $fk_userid = isset($request_data['fk_userid']) ? (int)$request_data['fk_userid'] : 'NULL'; 
    $token = isset($request_data['token']) ? $request_data['token'] : 'NULL'; 

    // Insertar en `khns_attendance_event`
    $sql = "INSERT INTO khns_attendance_event (
                date_time_event,
                event_location_ref,
                event_type,
                note,
                fk_userid,
                fk_user_modification,
                fk_third_party,
                fk_task,
                fk_project,
                status,
                token
            ) VALUES (
                '".$now."',               -- date_time_event
                '".$event_location_ref."', -- event_location_ref
                ".$event_type.",           -- event_type
                '".$note."',               -- note
                ".$fk_userid.",            -- fk_userid (el usuario que crea)
                ".$fk_userid.",            -- fk_user_modification
                ".$fk_third_party.",       -- fk_third_party
                ".$fk_task.",              -- fk_task
                ".$fk_project.",           -- fk_project
                ".$status.",                -- status
                '".$token."'               -- token
            )";

    $resql = $this->db->query($sql);
    if (!$resql) {
        throw new RestException(500, "Error inserting TiempoTarea in database", [
            "sql_error" => $this->db->lasterror(),
            "field_values" => $request_data
        ]);
    }

    $insertedIds[] = $this->db->last_insert_id("khns_attendance_event");

    // Si es una salida, insertar en `projet_task_time`
    if ($event_type == 3) { 
        $token = isset($request_data['token']) ? $this->db->escape($request_data['token']) : null;

        if ($event_type == 3 && $token) { // Si es una salida y el token es válido
            // Consulta para obtener el date_time_event de la entrada correspondiente
            $entry_sql = "SELECT date_time_event FROM khns_attendance_event 
                          WHERE token = '" . $token . "' AND event_type = 2 
                          ORDER BY date_time_event DESC LIMIT 1";
        
            $entry_resql = $this->db->query($entry_sql);
        
            if ($entry_resql && $entry_row = $this->db->fetch_object($entry_resql)) {
                $entry_date_time = $entry_row->date_time_event;
        
                // Convertir las fechas a timestamp para calcular la diferencia en segundos
                $entry_timestamp = strtotime($entry_date_time);
                $exit_timestamp = strtotime($now);
        
                // Calcular la duración en segundos
                $task_duration = $exit_timestamp - $entry_timestamp;
        
                // Asegurar que la duración no sea negativa
                $task_duration = max(0, $task_duration);
            } else {
                // Manejar el caso en el que no se encuentra una entrada correspondiente
                throw new RestException(404, "No se encontró un evento de entrada correspondiente al token proporcionado");
            }
        } else {
            $task_duration = 0; // Valor predeterminado si no es una salida o no se proporciona un token
        }

        // Obtener el thm del usuario
        $user_thm_sql = "SELECT thm FROM khns_user WHERE rowid = " . (int)$fk_userid;
        $user_thm_resql = $this->db->query($user_thm_sql);

        if ($user_thm_resql && $user_thm_row = $this->db->fetch_object($user_thm_resql)) {
            $thm = $user_thm_row->thm;
        } else {
            $thm = NULL; // Manejo en caso de error o que no se encuentre el usuario
        }


        // Preparar datos para `projet_task_time`
        $task_date = $now;
        $task_datehour = $now;
        $invoice_id = isset($request_data['invoice_id']) ? (int)$request_data['invoice_id'] : 'NULL';
        $invoice_line_id = isset($request_data['invoice_line_id']) ? (int)$request_data['invoice_line_id'] : 'NULL';
        $import_key = isset($request_data['import_key']) ? $this->db->escape($request_data['import_key']) : 'NULL';
        $status_exit = isset($request_data['status']) ? (int)$request_data['status'] : 1;

        $sql_task_time = "INSERT INTO khns_projet_task_time (
                            fk_task,
                            task_date,
                            task_datehour,
                            task_date_withhour,
                            task_duration,
                            fk_user,
                            thm,
                            note,
                            invoice_id,
                            invoice_line_id,
                            import_key,
                            datec,
                            status
                        ) VALUES (
                            ".$fk_task.",              -- fk_task
                            '".$task_date."',         -- task_date
                            '".$task_datehour."',     -- task_datehour
                            1, -- task_date_withhour
                            ".$task_duration.",       -- task_duration
                            ".$fk_userid.",           -- fk_user
                            ".$thm.",                 -- thm
                            '".$note."',              -- note
                            ".$invoice_id.",          -- invoice_id
                            ".$invoice_line_id.",     -- invoice_line_id
                            '".$import_key."',        -- import_key
                            '".$now."',               -- datec
                            ".$status_exit."          -- status
                        )";

        $resql_task_time = $this->db->query($sql_task_time);
        if (!$resql_task_time) {
            throw new RestException(500, "Error inserting task time in database", [
                "sql_error" => $this->db->lasterror(),
                "field_values" => $request_data
            ]);
        }
    }

    return $insertedIds;
}


/**
 * Get list of tiempos
 *
 * Get a list of all tiempos from the database
 *
 * @return array Array of TiempoTarea objects
 *
 * @throws RestException
 */
public function listar()
{
    global $db;

    $obj_ret = array();
    $sql = "SELECT t.* FROM " . MAIN_DB_PREFIX . "chrono_tiempotarea AS t";

    // Execute the query
    $result = $this->db->query($sql);

    // Check if the query was successful
    if ($result) {
        while ($obj = $this->db->fetch_object($result)) {
            // Prepare TiempoTarea object
            $tmp_object = new TiempoTarea($this->db);
            $tmp_object->fetch($obj->rowid);
            // Add the object to the result array
            $obj_ret[] = $this->_cleanObjectDatas($tmp_object);
        }
    } else {
        throw new RestException(503, 'Error when retrieving tiempos list: ' . $this->db->lasterror());
    }

    // Check if any tiempos were found
    if (!count($obj_ret)) {
        throw new RestException(404, 'No tiempos found');
    }

    return $obj_ret;
}


    /**
     * Put para actualizar un tiempo existente
     *
     * @param  int $id ID del tiempo a actualizar
     * @param  array $request_data Datos del tiempo a actualizar
     *
     * @url    PUT chrono/{id}
     *
     * @throws RestException 404 Not found
     * @throws RestException 400 Bad Request
     */
    public function actualizar($id, $request_data = null)
    {
        // Verificar que los datos de la solicitud estén disponibles
        if (is_null($request_data) || !is_array($request_data)) {
            throw new RestException(400, "No data provided or data is not an array");
        }

        $tiempoTarea = new TiempoTarea($this->db);
        if ($tiempoTarea->fetch($id) <= 0) {
            throw new RestException(404, "TiempoTarea not found");
        }

        foreach ($request_data as $field => $value) {
            $tiempoTarea->$field = $this->_checkValForAPI($field, $value, $tiempoTarea);
        }

        if ($tiempoTarea->update(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error updating TiempoTarea", [
                "error" => $tiempoTarea->error,
                "errors" => $tiempoTarea->errors,
                "field_values" => $request_data
            ]);
        }

        return $tiempoTarea;
    }



      /**
     * Obtener el estado de los temporizadores
     *
     * @url    GET /timer/state?userid={userid}
     *
     * @param  int $userid ID del usuario para el que se desea obtener el estado
     *
     * @throws RestException 404 Not found
     * @throws RestException 401 Not allowed
     */
    public function getTimerState($userid)
    {
        // Verificar que el usuario tenga permiso para ver los temporizadores
        if (!DolibarrApiAccess::$user->rights->tiempotarea->ver) {
            throw new RestException(401, "No tienes permiso para ver los tiempos");
        }

        $sql = "
            SELECT 
                SC1.fk_userid AS id_usuario,
                SC1.fk_task AS id_tarea,
                SC1.date_time_event AS hora_inicio,
                COALESCE(MIN(TIMESTAMPDIFF(SECOND, SC1.date_time_event, SC2.date_time_event)), 0) AS duracion_en_segundos
            FROM 
                (SELECT fk_userid, fk_task, date_time_event 
                 FROM khns_attendance_event
                 WHERE event_type = 2) SC1
            LEFT JOIN 
                (SELECT fk_userid, fk_task, date_time_event 
                 FROM khns_attendance_event
                 WHERE event_type = 3) SC2
            ON 
                SC1.fk_userid = SC2.fk_userid AND SC1.fk_task = SC2.fk_task
            WHERE
                SC1.fk_userid = $userid
            GROUP BY 
                SC1.fk_userid, SC1.fk_task, SC1.date_time_event;
        ";

        // Ejecutar la consulta
        $result = $this->db->query($sql);

        // Verificar si la consulta fue exitosa
        if ($result) {
            $obj_ret = [];
            while ($obj = $this->db->fetch_object($result)) {
                $obj_ret[] = [
                    'id_usuario' => $obj->id_usuario,
                    'id_tarea' => $obj->id_tarea,
                    'hora_inicio' => $obj->hora_inicio,
                    'duracion_en_segundos' => $obj->duracion_en_segundos
                ];
            }

            // Verificar si se encontraron resultados
            if (!count($obj_ret)) {
                throw new RestException(404, "No se encontraron temporizadores para el usuario.");
            }

            return $obj_ret;
        } else {
            throw new RestException(503, 'Error al recuperar el estado de los temporizadores: ' . $this->db->lasterror());
        }
    }



/**
 * Delete un tiempo por ID
 *
 * @param  int $id ID del tiempo a eliminar
 *
 * @url    DELETE chrono/{id}
 *
 * @throws RestException 404 Not found
 * @throws RestException 401 Not allowed
 */
public function delete($id)
{
    // Verificar que el usuario tenga permiso para eliminar
    if (!DolibarrApiAccess::$user->rights->tiempotarea->supprimer) {
        throw new RestException(401, "No tienes permiso para eliminar tiempos");
    }

    // Crear una instancia de TiempoTarea
    $tiempoTarea = new TiempoTarea($this->db);
    $result = $tiempoTarea->fetch($id);

    // Verificar que el tiempo exista
    if (!$result) {
        throw new RestException(404, "TiempoTarea no encontrado");
    }

    // Verificar acceso al recurso específico
    if (!DolibarrApi::_checkAccessToResource('tiempotarea', $tiempoTarea->id)) {
        throw new RestException(401, 'Acceso no permitido para el usuario ' . DolibarrApiAccess::$user->login);
    }

    // La función delete usa la variable global $user.
    global $user;
    $user = DolibarrApiAccess::$user;

    // Ejecutar la eliminación
    if ($tiempoTarea->delete($user) < 0) {
        throw new RestException(500, "Error al eliminar TiempoTarea", [
            "error" => $tiempoTarea->error,
        ]);
    }

    return ["status" => "success", "message" => "TiempoTarea eliminado"];
}

}
