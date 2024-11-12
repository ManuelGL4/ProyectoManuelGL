<?php

    require __DIR__ . '/../vendor/autoload.php';

    require '../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

    // Get parameters
    $id = GETPOST('id', 'int');
    $ref        = GETPOST('ref', 'alpha');
    $action = GETPOST('action', 'aZ09');
    $cancel     = GETPOST('cancel', 'aZ09');
    $backtopage = GETPOST('backtopage', 'alpha');

    // Initialize technical objects
    $object = new Project($db);
    $extrafields = new ExtraFields($db);
    $diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
    $hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
    // Fetch optionals attributes and labels
    $extrafields->fetch_name_optionals_label($object->table_element);

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;

    $ruta3 = "archivosImportacion/Fases.xlsx";

    $hoja3 = IOFactory::load($ruta3);

    $sheet3 = $hoja3->getActiveSheet();

    $filamasalta3 = $sheet3->getHighestRow();
    $colummasalta3 = $sheet3->getHighestColumn();

    $columnumero3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta3);

    $datsoEquipoContrato = array();
    $datosIdFase = array();
    $datosInforme = array();
    $datos = array();
    $datosID = array();

    $numEquipos = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $contrato = "";
    $indice = 0;
    $orden = "";
    $informe = 1;
    $numInformes = 0;
    $datos = array();

    for ($fila = 2; $fila <= 15000; $fila++) {

        $insertado = false;
        $equipo = "";

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero3; $col++) {

            $indice2 = 0;
            $contrato = "";

            if ($col == 1) {    //ID ORDEN (CADA ORDEN ÚNICO ES UN INFORME)
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();

                $ordenFila = $celda;
    
                if ($orden == "") {
                    $orden = $ordenFila;

                    $datosInforme[$indice][$indice2] = $orden;
                } else {
                    if ($orden != $ordenFila) {

                        $insertado = true;

                    }
                }

            } else if ($col == 2) { //ID FASE
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();

                //PARA COMPROBAR SI LA FASE ESTÁ LIGADA
                $sqlFase = " SELECT id_equipo_contrato FROM khns_contratos_fases WHERE id_fase = ".$celda." ";
                $resultFase = $db->query($sqlFase);
                $fase = $db->fetch_object($resultFase);
                $numFases = $db->num_rows($resultFase);

                if ($numFases > 0) {
                    $datos[0] = $celda;
                    $equipo = $fase->id_equipo_contrato;
                }

            } else if ($col == 8) { //FECHA INICIO
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                //PARA REFERENCIA
                $ano2 = substr($ano,  2,  2); // Extrae los primeros dos dígitos (año)

                $datos[1] = $fechaFormateada;
            } else if ($col == 9) { //FECHA PREVISTA
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[2] = $fechaFormateada;
            } else if ($col == 10) { //FECHA FIN
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[3] = $fechaFormateada;
            }

        }

        if (($insertado) && ($equipo != "")) {

            $sqlContrato = " SELECT fk_contract FROM khns_mantenimiento_contratos_equipos WHERE id_khonos = ".$equipo." ";
            $resultContrato = $db->query($sqlContrato);
            $contrato = $db->fetch_object($resultContrato);
            $contrato = $contrato->fk_contract;

            //$contenido.= "INFORME DE ".$datosInforme[$indice][$indice2]." ";
            $contenido.= "INSERT INTO khns_mantenimiento_informes ";
            $contenido.= "(ref, description, technician_id, last_technician_id, storage_id, maintenance_date, real_date, start_date, end_date, contract_id, observations, fk_user_creat, id_khonos) ";
            $contenido.= " VALUES ";
            $contenido.= "('INF".$ano2."".$mes."-".str_pad($informe, 5, 0, STR_PAD_LEFT)."', 'Informe de orden: ".$ordenFila."', -1, -1, 1, '".$datos[1]."', '".$datos[2]."', '".$datos[1]."', '".$datos[3]."', ".$contrato.", 'Intervención realizada para el contrato: (ver ficha)', 1, ".$ordenFila.")//";
            $contenido.= "\r\n";
            $indice++;
            $orden = $ordenFila;

            $datosInforme[$indice][$indice2] = $orden;

            $numInformes++;
            $informe++;

        }

        $datos = array();
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "INFORMES: ".$numInformes.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Informes.sql"');

    echo $contenido;
?>