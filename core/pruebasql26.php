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

    $ruta = "archivosImportacion/CF-Productos.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numProductos = 0;
    $datos = array();

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {
            
            if ($col == 1) {    //rowid
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $idKhonos = $celda;
            } else if ($col == 19) {    //COSTE ESTANDAR
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 20) {    //FECHA COSTE ESTANDAR
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 21) {    //COSTE TEÓRICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[2] = $celda;
            } else if ($col == 22) {    //FECHA COSTE TEÓRICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            } else if ($col == 23) {    //COSTE MEDIO ALMACÉN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[4] = $celda;
            } else if ($col == 24) {    //FECHA COSTE MEDIO ALMACÉN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[5] = $celda;
            }

        }

        $costeCompra = 0;

        if (($datos[0] > 0) && ($datos[2] > 0)) {   //Si ambos son mayor que 0

            if ($datos[1] > $datos[3]) {

                $costeCompra = $datos[0];

            } else {

                $costeCompra = $datos[2];

            }

        }

        if (($datos[0] == 0) && ($datos[2] == 0)) { //Si ambos son 0

            $costeCompra = $datos[4];

        }

        if (($datos[0] > 0) && ($datos[2] == 0)) {  //Si el primero no es 0 y el segundo si

            $costeCompra = $datos[0];

        }

        if (($datos[0] == 0) && ($datos[2] > 0)) {  //Si el primero es 0 y el segundo no

            $costeCompra = $datos[2];

        }

        $contenido.= "SET @producto = (SELECT fk_object FROM khns_product_extrafields WHERE id_khonos = ".$idKhonos.")//";
        $contenido.= "\r\n";
        $contenido.= "UPDATE khns_product ";
        $contenido.= "SET cost_price = ".$costeCompra.", pmp = ".$costeCompra." ";
        $contenido.= "WHERE rowid = @producto//";
        $contenido.= "\r\n";
        $contenido.= "\r\n";


        $datos = array();

        $numProductos++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Productos: ".$numProductos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Ajustes_Precio_compra.sql"');

    echo $contenido;
?>