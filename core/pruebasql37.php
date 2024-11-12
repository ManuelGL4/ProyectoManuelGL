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

    $ruta = "archivosImportacion/CF-Detalle Final.xlsx";

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
    $fecha = date("Y-m-d H:i:s");
    $loteAnt = "";

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        $tipo = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 3) {    //ID ARTÍCULO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 8) {    //NUM SERIE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 9) {    //LOTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 10) {    //CANTIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            }       

        }

        if (($datos[1] != "") && ($datos[2] == "")) {
            $tipo = 2;
        }

        if (($datos[1] == "") && ($datos[2] != "")) {
            $tipo = 1;
            $lote = $datos[2];
        }

        if (($datos[1] != "") && ($datos[2] != "")) {
            $tipo = 2;
        }

        if (($datos[1] != "") || ($datos[2] != "")) {

            if ($tipo == 1) {

                if ($lote != $loteAnt) {

                    $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
                    $contenido.= "\r\n";
                    /*$contenido.= "SET @productstock = (SELECT rowid FROM khns_product_stock WHERE fk_product = @product)//";
                    $contenido.= "\r\n";
                    $contenido.= "INSERT INTO khns_product_batch ";
                    $contenido.= "(fk_product_stock, batch, qty) ";
                    $contenido.= "VALUES ";
                    $contenido.= "(@productstock, '".$datos[2]."', ".$datos[3].")// ";
                    $contenido.= "\r\n";
                    $contenido.= "INSERT INTO khns_product_lot ";
                    $contenido.= "(fk_product, batch, datec) ";
                    $contenido.= "VALUES ";
                    $contenido.= "(@product, '".$datos[2]."', '".$fecha."')//";
                    $contenido.= "\r\n";
                    $contenido.= "\r\n";*/

                    $contenido.= "UPDATE khns_product SET tobatch = ".$tipo." WHERE rowid = @product//";
                    $contenido.= "\r\n";

                } /*else {

                    $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
                    $contenido.= "\r\n";
                    $contenido.= "SET @productstock = (SELECT rowid FROM khns_product_stock WHERE fk_product = @product)//";
                    $contenido.= "\r\n";
                    $contenido.= "UPDATE khns_product_batch ";
                    $contenido.= "SET qty = qty + ".$datos[3]." ";
                    $contenido.= "WHERE fk_product_stock = @productstock//";
                    $contenido.= "\r\n";
                    $contenido.= "\r\n";

                }*/

                $loteAnt = $lote;

            } else {

                $contenido.= "SET @product = (SELECT e.fk_object FROM khns_product_extrafields e INNER JOIN khns_product s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[0].")//";
                $contenido.= "\r\n";
                /*$contenido.= "SET @productstock = (SELECT rowid FROM khns_product_stock WHERE fk_product = @product)//";
                $contenido.= "\r\n";
                $contenido.= "INSERT INTO khns_product_batch ";
                $contenido.= "(fk_product_stock, batch, qty) ";
                $contenido.= "VALUES ";
                $contenido.= "(@productstock, '".$datos[1]."', ".$datos[3].")// ";
                $contenido.= "\r\n";
                $contenido.= "INSERT INTO khns_product_lot ";
                $contenido.= "(fk_product, batch, datec) ";
                $contenido.= "VALUES ";
                $contenido.= "(@product, '".$datos[1]."', '".$fecha."')//";
                $contenido.= "\r\n";
                $contenido.= "\r\n";*/

                $contenido.= "UPDATE khns_product SET tobatch = ".$tipo." WHERE rowid = @product//";
                $contenido.= "\r\n";

            }

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
    header('Content-Disposition: attachment; filename="Nº Serie y Lotes.sql"');

    echo $contenido;
?>