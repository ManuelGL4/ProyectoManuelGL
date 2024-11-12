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

    $ruta = "archivosImportacion/CF-Pedidos Proveedores Lineas.xlsx";

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

    for ($fila = 116; $fila <= $filamasalta; $fila++) {

        $diferente = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 2) {    //ID PEDIDO (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda < 5366) {
                    $diferente = 2;
                }

                if ($celda == 5375) {
                    $diferente = 1;
                }

                if ($celda == 5376) {
                    $diferente = 1;
                }

                if ($celda == 5414) {
                    $diferente = 1;
                }

                if ($celda == 5445) {
                    $diferente = 1;
                }

                if ($celda == 5450) {
                    $diferente = 1;
                }

                if ($celda == 5481) {
                    $diferente = 1;
                }
                
                $datos[0] = $celda;
            } else if ($col == 7) {    //CORDEN (RANG)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 11) {    //FECHA PREVISTA (??)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 14) {    //ID PRODUCTO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 26) {    //CANTIDAD (QTY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            } else if ($col == 31) {    //PRECIO FINAL (SUBPRICE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            } else if ($col == 46) {    //BASE (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[6] = $celda;
            } else if ($col == 48) {    //PORC_IVA (TVA_TX)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 49) {    //IMPORTE IVA (TOTAL TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 56) {    //TOTAL LINEA (TOTAL TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[9] = $celda;
            }          

        }

        if ($diferente == 0) {

            $contenido.= "SET @idpedido = (SELECT e.fk_object FROM khns_commande_fournisseur_extrafields e INNER JOIN khns_commande_fournisseur s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @multicu = (SELECT fk_multicurrency FROM khns_commande_fournisseur WHERE rowid = @idpedido)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseurdet ";
            $contenido.= "(fk_commande, fk_product, tva_tx, qty, remise_percent, remise, subprice, total_ht, total_tva, total_ttc, rang, fk_multicurrency) ";
            $contenido.= "VALUES ";
            $contenido.= "(@idpedido, @product, ".$datos[7].", ".$datos[4].", 0, 0, ".$datos[5].", ".$datos[6].", ".$datos[8].", ".$datos[9].", ".$datos[1].", @multicu)// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        } else if ($diferente == 1) {

            $contenido.= "SET @idpedido = (SELECT e.fk_object FROM khns_commande_fournisseur_extrafields e INNER JOIN khns_commande_fournisseur s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @multicu = (SELECT fk_multicurrency FROM khns_commande_fournisseur WHERE rowid = @idpedido)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_commande_fournisseurdet ";
            $contenido.= "(fk_commande, fk_product, tva_tx, qty, remise_percent, remise, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, rang, fk_multicurrency) ";
            $contenido.= "VALUES ";
            $contenido.= "(@idpedido, @product, ".$datos[7].", ".$datos[4].", 0, 0, ".$datos[5].", ".$datos[6].", ".$datos[8].", ".$datos[9].", ".$datos[1].", @multicu)// ";
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
    header('Content-Disposition: attachment; filename="Pedidos_PRO_lineas.sql"');

    echo $contenido;
?>