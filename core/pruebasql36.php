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

    $ruta = "archivosImportacion/CF-Ofertas Clientes Lineas2.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numLineas = 0;
    $datos = array();
    $datosExtra = array();

    $ofertaAnt = -1;
    for ($fila = 12001; $fila <= $filamasalta; $fila++) {

        $diferente = 0;
        $updatear = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID LÍNEA OFERTA (E.ID_KHONOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if (($celda == 173) || ($celda == 9164) || ($celda == 9414) || ($celda == 10101) || ($celda == 10102) || ($celda == 10103) || ($celda == 10779) || ($celda == 11696)) {
                    $diferente = 1;
                }
                
                $datosExtra[0] = $celda;
            } else if ($col == 2) {    //ID OFERTA (FK_PROPAL)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != $ofertaAnt) {
                    $updatear = 1;
                    $ofertaAnt = $celda;
                }
                
                $datos[0] = $celda;
            } else if ($col == 4) {    //CAPITULO (E.CAPITULO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datosExtra[1] = $celda;
            } else if ($col == 9) {    //ORDEN (RANG)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 12) {    //FECHA PREVISTA (EN PROPAL, DATE_LIVRAISON)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                
                    $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                    $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                    $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                    $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                } else {

                    $fechaFormateada = '';

                }
                
                $datos[2] = $fechaFormateada;
            } else if ($col == 15) {    //ID ARTÍCULO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 17) {    //DTO_PORC_OFERTA (REMISE_PERCENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            } else if ($col == 18) {    //DTO_PORC_CLIENTE (REMISE_PERCENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            } else if ($col == 26) {    //CANTIDAD (QTY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[6] = $celda;
            } else if ($col == 30) {    //PRECIO FINAL (SUBPRICE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 36) {    //BASE (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 37) {    //PORC_IVA (TVA_TX)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[9] = $celda;
            } else if ($col == 38) {    //IMPORTE IVA (TOTAL TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[10] = $celda;
            } else if ($col == 41) {    //TOTAL LINEA (TOTAL TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[11] = $celda;
            }          

        }

        $descuento = $datos[4] + $datos[5];

        if ($diferente == 0) {

            $contenido.= "SET @oferta = (SELECT e.fk_object FROM khns_propal_extrafields e INNER JOIN khns_propal s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3].")//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propaldet ";
            $contenido.= "(fk_propal, fk_product, tva_tx, qty, remise_percent, subprice, total_ht, total_tva, total_ttc, rang, fk_multicurrency, multicurrency_code) ";
            $contenido.= "VALUES ";
            $contenido.= "(@oferta, @product, ".$datos[9].", ".$datos[6].", ".$descuento.", ".$datos[7].", ".$datos[8].", ".$datos[10].", ".$datos[11].", ".$datos[1].", 1, 'EUR')// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propaldet_extrafields ";
            $contenido.= "(fk_object, id_khonos, capitulo) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, ".$datosExtra[0].", ".$datosExtra[1].")// ";

            if ($updatear == 1) {
                $contenido.= "\r\n";
                $contenido.= "UPDATE khns_propal SET date_livraison = '".$datos[2]."' WHERE rowid = @oferta//";
            }

            $contenido.= "\r\n";
            $contenido.= "\r\n";

        } else if ($diferente == 1) {

            $contenido.= "SET @oferta = (SELECT e.fk_object FROM khns_propal_extrafields e INNER JOIN khns_propal s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3].")//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propaldet ";
            $contenido.= "(fk_propal, fk_product, tva_tx, qty, remise_percent, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, rang, fk_multicurrency, multicurrency_code) ";
            $contenido.= "VALUES ";
            $contenido.= "(@oferta, @product, ".$datos[9].", ".$datos[6].", ".$descuento.", ".$datos[7].", ".$datos[8].", ".$datos[10].", ".$datos[11].", ".$datos[1].", 1, 'EUR')// ";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_propaldet_extrafields ";
            $contenido.= "(fk_object, id_khonos, capitulo) ";
            $contenido.= "VALUES ";
            $contenido.= "(@last_id, ".$datosExtra[0].", ".$datosExtra[1].")// ";

            if ($updatear == 1) {
                $contenido.= "\r\n";
                $contenido.= "UPDATE khns_propal SET date_livraison = '".$datos[2]."' WHERE rowid = @oferta//";
            }

            $contenido.= "\r\n";
            $contenido.= "\r\n";

        }

        $datos = array();
        $datosExtra = array();

        $numLineas++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Lineas: ".$numLineas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Ofertas_CLI_lineas.sql"');

    echo $contenido;
?>