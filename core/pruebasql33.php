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

    $ruta = "archivosImportacion/CF-Albaranes Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numAlbaranes = 0;
    $datos = array();
    $datosExtra = array();

    for ($fila = 5905; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID ALBARAN (E.ID_KHONOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[0] = $celda;
            } else if ($col == 5) {    //CODIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 8) {    //FECHA ALBARAN (DATE CREATION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado
                
                $datos[1] = $fechaFormateada;
            } else if ($col == 11) {    //ID CLIENTE (FK SOC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 77) {    //FECHA SALIDA (DATE DELIVERY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado
                
                $datos[3] = $fechaFormateada;
            } else if ($col == 25) {    //ID DELEGACION (E.ID DELEGACION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[1] = $celda ;
            } else if ($col == 75) {    //DIRECCION ENTREGA (E.DIRECCION ENTREGA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[2] = $celda ;
            } else if ($col == 69) {    //OBSERVACIONES (E.OBSERV)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[3] = $celda ;
            }
        }

        $contenido.= "SET @idcliente = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[2]." AND s.client = 1)//";
        $contenido.= "\r\n";
        //$contenido.= "SET @codigocli = (SELECT codigo_cliente FROM khns_societe_extrafields WHERE fk_object = ".$datos[1].")//";
        //$contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_expedition ";
        $contenido.= "(ref, date_creation, fk_soc, date_delivery) ";
        $contenido.= "VALUES ";
        $contenido.= "('".$datos[0]."', '".$datos[1]."', @idcliente, '".$datos[3]."')// ";
        $contenido.= "\r\n";
        $contenido.= "SET @last_id = LAST_INSERT_ID()//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_expedition_extrafields ";
        $contenido.= "(fk_object, observaciones, id_khonos, direccion_entrega, id_delegacion) ";
        $contenido.= "VALUES ";
        $contenido.= "(@last_id, '".$datosExtra[3]."', ".$datosExtra[0].", '".$datosExtra[2]."', ".$datosExtra[1].")// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $datos = array();
        $datosExtra = array();

        $numAlbaranes++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Albaranes: ".$numAlbaranes.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Albaranes_CLI_cabeceras.sql"');

    echo $contenido;
?>