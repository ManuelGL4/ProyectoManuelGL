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

    $ruta = "archivosImportacion/CF-Facturas Ventas Lineas.xlsx";

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
    $datosOrden = array();

    for ($fila = 1937; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 2) {    //ID FACTURA (F_FACTURA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 6) {    //ORDEN (RANG)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $orden = $celda;
            } else if ($col == 8) {    //ID ARTICULO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 10) {    //NOMBRE ARTÍCULO (LABEL)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 11) {    //NOOMBRE ARTÍCULO FRA (DESCRIPTION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[4] = $celda;
            } else if ($col == 16) {    //CANTIDAD (QTY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 27) {    //BRUTO LÍNEA (SUBPRICE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 35) {    //BASE LÍNEA (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 37) {    //PORC IVA (TVA_TX)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 38) {    //IMPORTE IVA (TOTAL_TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[9] = $celda;
            } else if ($col == 45) {    //TOTAL LÍNEA (TOTAL_TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[10] = $celda;
            }

        }

        $contenido.= "SET @descuento = (SELECT s.remise_client FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object INNER JOIN khns_facture f ON f.fk_soc = s.rowid WHERE f.rowid = ".$datos[0].")//";
        $contenido.= "\r\n";
        $contenido.= "SET @idproducto = (SELECT p.rowid FROM khns_product p INNER JOIN khns_product_extrafields e ON e.fk_object = p.rowid WHERE e.id_khonos = ".$datos[2].")//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_facturedet ";
        $contenido.= "(fk_facture, fk_product, label, description, qty, subprice, total_ht, tva_tx, total_tva, total_ttc, rang, remise_percent) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", @idproducto, '".$datos[3]."', '".$datos[4]."', ".$datos[5].", ".$datos[6].", ".$datos[7].", ".$datos[8].", ".$datos[9].", ".$datos[10].", ".$orden.", @descuento)// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";


        $datos = array();
        $datosOrden = array();

        $numLineas++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Líneas: ".$numLineas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="FRAS_VTA_lineas.sql"');

    echo $contenido;
?>