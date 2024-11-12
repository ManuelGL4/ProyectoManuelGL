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

    $ruta = "archivosImportacion/CF-Pedidos Clientes Lineas.xlsx";

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

    $k = 1;
    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        $diferente = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID LINEA (ID_KHONOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            }

            if ($col == 2) {    //ID PEDIDO (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } /*else if ($col == 9) {    //CORDEN (RANG)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 12) {    //FECHA PREVISTA (??)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 15) {    //ID PRODUCTO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 20) {    //DTO POR CLIENTE (REMISE CLIENT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[10] = $celda;
            } else if ($col == 32) {    //CANTIDAD (QTY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            } else if ($col == 37) {    //PRECIO FINAL (SUBPRICE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            } else if ($col == 47) {    //BASE (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[6] = $celda;
            } else if ($col == 49) {    //PORC_IVA (TVA_TX)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[7] = $celda;
            } else if ($col == 50) {    //IMPORTE IVA (TOTAL TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[8] = $celda;
            } else if ($col == 55) {    //TOTAL LINEA (TOTAL TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[9] = $celda;
            } */

        }

        /*$contenido.= "SET @idpedido = (SELECT e.fk_object FROM khns_commande_extrafields e INNER JOIN khns_commande s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
        $contenido.= "\r\n";
        $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3].")//";
        $contenido.= "\r\n";
        $contenido.= "SET @multicu = (SELECT fk_multicurrency FROM khns_commande WHERE rowid = @idpedido)//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_commandedet ";
        $contenido.= "(fk_commande, fk_product, tva_tx, qty, remise_percent, subprice, total_ht, total_tva, total_ttc, rang, fk_multicurrency) ";
        $contenido.= "VALUES ";
        $contenido.= "(@idpedido, @product, ".$datos[7].", ".$datos[4].", ".$datos[10].", ".$datos[5].", ".$datos[6].", ".$datos[8].", ".$datos[9].", ".$datos[1].", @multicu)// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";*/

        $contenido.= "SET @idpedido = (SELECT e.fk_object FROM khns_commande_extrafields e INNER JOIN khns_commande s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[1].")//";
        $contenido.= "\r\n";
        $contenido.= "SET @idlinea = (SELECT rowid FROM khns_commandedet WHERE fk_commande = @idpedido AND rowid = ".$k.")//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_commandedet_extrafields ";
        $contenido.= "(fk_object, id_khonos) ";
        $contenido.= "VALUES ";
        $contenido.= "(@idlinea, ".$datos[0].")// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $datos = array();
        $datosExtra = array();

        $numLineas++;
        $k++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Lineas: ".$numLineas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Pedidos_CLI_lineas.sql"');

    echo $contenido;
?>