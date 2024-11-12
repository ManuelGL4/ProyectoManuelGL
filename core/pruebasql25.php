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

    $ruta = "archivosImportacion/CF-Proveedores.xlsx";

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

    for ($fila = 3; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {
            
            if ($col == 1) {    //ROWID
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $rowid = $celda;
            } else if ($col == 17) {    //CÓDIGO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            }

        }

        $contenido.= "SET @idproveedor = (SELECT se.fk_object FROM khns_societe_extrafields se INNER JOIN khns_societe s ON s.rowid = se.fk_object WHERE se.id_khonos = ".$rowid." AND s.fournisseur = 1)//";
        $contenido.= "\r\n";
        $contenido.= "UPDATE khns_societe_extrafields ";
        $contenido.= "SET codigo_cliente = '".$datos[0]."' ";
        $contenido.= "WHERE rowid = @idproveedor//";
        $contenido.= "\r\n";
        $contenido.= "\r\n";


        $datos = array();

        $numProductos++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Proveedores: ".$numProductos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Ajustes_proveedores.sql"');

    echo $contenido;
?>