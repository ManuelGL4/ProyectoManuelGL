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

    $ruta = "archivosImportacion/CF-Productos Compuestos.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();

    $numLineas = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $producto = "";

    //LEEMOS EL EXCEL
    //CADAD FILA
    for ($fila = 20007; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID ARTÍCULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 2) {    //ID ARTÍCULO DESCOMPOSICIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[1] = $celda;
            } else if ($col == 3) {     //ID LÍNEA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 4) {     //CANTIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            }

        }

        if ($producto != $datos[0]) {

            $producto = $datos[0];

            $contenido.= "\r\n";
            $contenido.= "\r\n";
            $contenido.= "SET @product = (SELECT fk_object FROM khns_product_extrafields WHERE id_khonos = ".$producto.")//";
            $contenido.= "\r\n";
            $contenido.= "SET @referencia = (SELECT ref FROM khns_product WHERE rowid = @product)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_bom_bom (ref, label, description, fk_product, efficiency, qty, fk_user_creat, status) VALUES (CONCAT('BOM-', @referencia), CONCAT('Composición de ', @referencia), CONCAT('Materiales de los que está compuesto el producto ', @referencia), @product, 1, 1, 1, 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @last_id = LAST_INSERT_ID()//";

            $position = 2;

        }

        $contenido.= "\r\n";
        $contenido.= "\r\n";
        $contenido.= "SET @productComp = (SELECT fk_object FROM khns_product_extrafields WHERE id_khonos = ".$datos[1].")//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_bom_bomline (fk_bom, fk_product, qty, efficiency, position) VALUES (@last_id, @productComp, ".$datos[3].", 1, ".$position.")//";
        $contenido.= "\r\n";
        $contenido.= "SET @last_id2 = LAST_INSERT_ID()//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_bom_bomline_extrafields (fk_object, id_linea) VALUES (@last_id2, ".$datos[2].")//";

        $datos = array();

        $numLineas++;

        $position+=2;

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $contenido.= "DELIMITER ;";

    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $contenido.= "LINEAS: ".$numLineas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="compuestos.sql"');

    echo $contenido;
?>