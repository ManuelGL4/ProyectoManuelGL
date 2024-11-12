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

    $ruta = "archivosImportacion/CF-Albaranes Clientes Lineas.xlsx";

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

    for ($fila = 3364; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID ALBARAN LÍNEA ()
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 2) {    //ID ALBARAN (FK_EXPEDITION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 7) {    //ORDEN (RANG)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 26) {    //CANTIDAD (QTY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == $columnumero) {    //LINEA PEDIDO ORIGEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            }
        }

        if (($datos[4] >= 27677) && ($datos[4] != 27700) && ($datos[4] != 27680) && ($datos[4] != 28011)) {

            $contenido.= "SET @idenvio = (SELECT e.fk_object FROM khns_expedition_extrafields e INNER JOIN khns_expedition s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[1].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @idlineapedido = (SELECT fk_object FROM khns_commandedet_extrafields e INNER JOIN khns_commandedet s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[4].")//";
            $contenido.= "\r\n";
            $contenido.= "SET @idpedido = (SELECT fk_commande FROM khns_commandedet WHERE rowid = @idlineapedido)//";
            $contenido.= "\r\n";
            //$contenido.= "SET @codigocli = (SELECT codigo_cliente FROM khns_societe_extrafields WHERE fk_object = ".$datos[1].")//";
            //$contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_expeditiondet ";
            $contenido.= "(fk_expedition, fk_origin_line, fk_entrepot, qty, rang) ";
            $contenido.= "VALUES ";
            $contenido.= "(@idenvio, @idlineapedido, 1, ".$datos[3].", ".$datos[2].")// ";

            if ($datos[2] == 1) {
                $contenido.= "\r\n";
                $contenido.= "INSERT INTO khns_element_element ";
                $contenido.= "(fk_source, sourcetype, fk_target, targettype) ";
                $contenido.= "VALUES ";
                $contenido.= "(@idpedido, 'commande', @idenvio, 'shipping')// ";
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
    $contenido.= "Líneas: ".$numLineas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Albaranes_CLI_lineas.sql"');

    echo $contenido;
?>