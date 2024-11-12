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

    $ruta = "archivosImportacion/CF-Pedidos Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numPedidos = 0;
    $datos = array();
    $datosExtra = array();

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID PEDIDO (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 5) {    //ID CLIENTE (FK_SOC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 7) {    //DIVISA ORIGEN (FK_MULTICURRENCY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 11) {    //FORMA PAGO (E.FORMA PAGO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[0] = $celda;
            } else if ($col == 13) {    //DTO POR CLIENTE (REMISE CLIENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 19) {    //FECHA OFERTA (DATE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado
                
                $datos[4] = $fechaFormateada;
            } else if ($col == 21) {    //DESCRIPCION (E.DESCRIPCION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[1] = $celda;
            } else if ($col == 26) {    //CÓDIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            } else if ($col == 32) {    //SUBTOTAL (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[6] = $celda;
            } else if ($col == 50) {    //BASE (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 52) {    //IVA (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 54) {    //TOTAL (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[9] = $celda;
            } else if ($col == 62) {    //REGIMEN IVA (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[10] = $celda;
            } else if ($col == 80) {    //OBSERVACIONES (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[2] = $celda;
            } else if ($col == 10) {    //DELEGACION (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[3] = $celda;
            }
        }

        if ($diferente == 0) {
            $contenido.= "SET @idcliente = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[1]." AND s.client = 1)//";
            $contenido.= "\r\n";
            //$contenido.= "SET @codigocli = (SELECT codigo_cliente FROM khns_societe_extrafields WHERE fk_object = ".$datos[1].")//";
            //$contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande ";
            $contenido.= "(ref, date_commande, fk_soc, fk_multicurrency, multicurrency_code, remise_percent, total_ht, total_tva, total_ttc) ";
            $contenido.= "VALUES ";
            $contenido.= "('".$datos[5]."', '".$datos[4]."', @idcliente, 1, 'EUR', ".$datos[3].", ".$datos[7].", ".$datos[8].", ".$datos[9].")// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_extrafields ";
            $contenido.= "(fk_object, forma_pago, descripcion, observaciones, id_delegacion, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, ".$datosExtra[0].", '".$datosExtra[1]."', '".$datosExtra[2]."', ".$datosExtra[3].", ".$datos[0].")// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";
        }

        $datos = array();
        $datosExtra = array();

        $numPedidos++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Pedidos: ".$numPedidos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Pedidos_CLI_cabeceras.sql"');

    echo $contenido;
?>