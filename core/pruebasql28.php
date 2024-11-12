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

    $ruta = "archivosImportacion/CF-Pedidos Proveedores.xlsx";

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

        $diferente = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID PEDIDO (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 5) {    //CÓDIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 8) {    //CODIGO PRO (REF SUPPLIER)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 9) {    //FECHA PEDIDO (DATE COMMANDE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado
                
                $datos[3] = $fechaFormateada;
            } else if ($col == 12) {    //ID PROVEEDOR (FK SOC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            } else if ($col == 16) {    //VALORACIÓN TIEMPO (E.PUNTUACIÓN PLAZOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[0] = $celda;
            } else if ($col == 17) {    //VALORACIÓN CALIDAD (E.PUNTUACIÓN ARTÍCULO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[1] = $celda;
            } else if ($col == 21) {    //ID DIVISA DESTINO (FK MULTICURRENCY)
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
                
                $datos[5] = $celda;
            } else if ($col == 22) {    //DIVISA DESTINO (MULTICURRENCY CODE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($datos[5] == 1) {
                    $celda = "EUR";
                }

                if ($datos[5] == 2) {
                    $celda = "CHF";
                }

                if ($datos[5] == 3) {
                    $celda = "USD";
                }
                
                $datos[6] = $celda;
            } else if ($col == 27) {    //FORMA PAGO (E.FORMA PAGO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[2] = $celda;
            } else if ($col == 55) {    //BASE IMPONIBLE (TOTAL HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 59) {    //IMP IVA (TOTAL TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 65) {    //TOTAL (TOTAL TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[9] = $celda;
            } else if ($col == 67) {    //OBSERVACIONES (E.OBSERVACIONES)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[3] = $celda;
            } else if ($col == 69) {    //DIRECCIÓN ENTREGA (E.DIRECCIÓN ENTREGA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[4] = $celda;
            }

        }

        if ($diferente == 0) {
            $contenido.= "SET @idproveedor = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[4]." AND s.fournisseur = 1)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseur ";
            $contenido.= "(ref, ref_supplier, date_commande, fk_soc, fk_multicurrency, multicurrency_code, remise, remise_percent, total_ht, total_tva, total_ttc) ";
            $contenido.= "VALUES ";
            $contenido.= "('".$datos[1]."', '".$datos[2]."', '".$datos[3]."', @idproveedor, ".$datos[5].", '".$datos[6]."', 0, 0, ".$datos[7].", ".$datos[8].", ".$datos[9].")// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseur_extrafields ";
            $contenido.= "(fk_object, puntuacion_articulo, puntuacion_plazos, observaciones, direccion_entrega, forma_pago, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, ".$datosExtra[0].", ".$datosExtra[1].", '".$datosExtra[3]."', '".$datosExtra[4]."', ".$datosExtra[2].", ".$datos[0].")// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";
        }

        if ($diferente == 1) { 
            $contenido.= "SET @idproveedor = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[4]." AND s.fournisseur = 1)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseur ";
            $contenido.= "(ref, ref_supplier, date_commande, fk_soc, fk_multicurrency, multicurrency_code, remise, remise_percent, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc) ";
            $contenido.= "VALUES ";
            $contenido.= "('".$datos[1]."', '".$datos[2]."', '".$datos[3]."', @idproveedor, ".$datos[5].", '".$datos[6]."', 0, 0, ".$datos[7].", ".$datos[8].", ".$datos[9].")// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseur_extrafields ";
            $contenido.= "(fk_object, puntuacion_articulo, puntuacion_plazos, observaciones, direccion_entrega, forma_pago, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, ".$datosExtra[0].", ".$datosExtra[1].", '".$datosExtra[3]."', '".$datosExtra[4]."', ".$datosExtra[2].", ".$datos[0].")// ";
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
    header('Content-Disposition: attachment; filename="Pedidos_PRO_cabeceras.sql"');

    echo $contenido;
?>