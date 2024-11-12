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

    $ruta = "archivosImportacion/CF-Ofertas Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numOfertas = 0;
    $datos = array();
    $datosExtra = array();

    for ($fila = 8001; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID OFERTA (E.ID_KHONOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[0] = $celda;
            } else if ($col == 5) {     //ID CLIENTE (FK_SOC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 6) {     //ID CONTACTO (E.CONTACTO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[1] = $celda;
            } else if ($col == 8) {     //ID DIVISA DESTINO (FK_MULTICURRENCY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $celda = trim($celda);
                $celda = intval($celda);

                if ($celda == 0) {
                    $celda = 1;
                } else if ($celda == 3) {
                    $celda = 2;
                    $diferente = 1;
                } else if ($celda == 1) {
                    $celda = 3;
                    $diferente = 1;
                }
                
                $datos[1] = $celda;
            } else if ($col == 10) {     //ID DELEGACIÓN (E.DELEGACION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[2] = $celda;
            } else if ($col == 11) {     //FORMA PAGO (E.FORMA PAGO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[3] = $celda;
            } else if ($col == 13) {     //DTO PORC CLIENTE (REMISE_PERCENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 14) {     //DTO PORC OFERTA (REMISE_PERCENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 20) {     //FECHA VALIDEZ (DATE_VALID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado
                
                $datos[4] = $fechaFormateada;
            } else if ($col == 21) {     //DESCRIPCIÓN (E.DESCRIPCION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[4] = $celda;
            } else if ($col == 26) {     //CÓDIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            } else if ($col == 50) {     //BASE (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[6] = $celda;
            } else if ($col == 52) {     //IVA (TOTAL_TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 54) {     //TOTAL (TOTAL_TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 74) {     //GARANTIA (E.GARANTIA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[5] = $celda;
            } else if ($col == 75) {     //PORC_RESOLUCION (E.PORC_RESOLUCION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[6] = $celda;
            } else if ($col == 76) {     //PLAZO_ENTREGA (E.PLAZO_ENTREGA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[7] = $celda;
            } else if ($col == 79) {     //OBSERVACIONES (E.OBSERVACIONES)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[8] = $celda;
            }

        }

        $descuento = $datos[2] + $datos[3];

        if ($datos[1] == 1) {
            $multicode = "EUR";
        }

        if ($datos[1] == 2) {
            $multicode = "CHF";
        }

        if ($datos[1] == 3) {
            $multicode = "USD";
        }
    

        if ($datos[1] == 1) {

            $contenido.= "SET @idcliente = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0]." AND s.client = 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @idcontacto = (SELECT so.fk_object FROM khns_socpeople_extrafields so INNER JOIN khns_societe s ON so.fk_object = s.rowid WHERE so.id_khonos = ".$datosExtra[1]." AND s.client = 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @iddelegacion = (SELECT id FROM khns_delegacion WHERE id_khonos = ".$datosExtra[2]." AND fk_tercero = @idcliente)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propal ";
            $contenido.= "(ref, fk_soc, date_valid, remise_percent, total_ht, total_tva, total_ttc, fk_multicurrency, multicurrency_code) ";
            $contenido.= "VALUES ";
            $contenido.= "('".$datos[5]."', @idcliente, '".$datos[4]."', ".$descuento.", ".$datos[6].", ".$datos[7].", ".$datos[8].", ".$datos[1].", '".$multicode."')// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propal_extrafields ";
            $contenido.= "(fk_object, id_contacto, id_delegacion, forma_pago, descripcion, garantia, plazo_entrega, observaciones, porc_resolucion, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, @idcontacto, @iddelegacion, ".$datosExtra[3].", '".$datosExtra[4]."', '".$datosExtra[5]."', '".$datosExtra[7]."', '".$datosExtra[8]."', ".$datosExtra[6].", ".$datosExtra[0].")// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        } else {

            $contenido.= "SET @idcliente = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0]." AND s.client = 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @idcontacto = (SELECT so.fk_object FROM khns_socpeople_extrafields so INNER JOIN khns_societe s ON so.fk_object = s.rowid WHERE so.id_khonos = ".$datosExtra[1]." AND s.client = 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @iddelegacion = (SELECT id FROM khns_delegacion WHERE id_khonos = ".$datosExtra[2]." AND fk_tercero = @idcliente)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propal ";
            $contenido.= "(ref, fk_soc, date_valid, remise_percent, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, fk_multicurrency, multicurrency_code) ";
            $contenido.= "VALUES ";
            $contenido.= "('".$datos[5]."', @idcliente, '".$datos[4]."', ".$descuento.", ".$datos[6].", ".$datos[7].", ".$datos[8].", ".$datos[1].", '".$multicode."')// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propal_extrafields ";
            $contenido.= "(fk_object, id_contacto, id_delegacion, forma_pago, descripcion, garantia, plazo_entrega, observaciones, porc_resolucion, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, @idcontacto, @iddelegacion, ".$datosExtra[3].", '".$datosExtra[4]."', '".$datosExtra[5]."', '".$datosExtra[7]."', '".$datosExtra[8]."', ".$datosExtra[6].", ".$datosExtra[0].")// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        }

        $datos = array();
        $datosExtra = array();

        $numOfertas++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Ofertas: ".$numOfertas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Ofertas_CLI_cabeceras.sql"');

    echo $contenido;
?>