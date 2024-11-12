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

    $ruta = "archivosImportacion/PRUEBA INFORMES.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numInformes = 0;
    $datos = array();

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {
            
            if ($col == 1) {    //ROWID
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $rowid = $celda;
            } else if ($col == 2) {    //REF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 3) {    //DESCRIPTION
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 7) {    //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $timestamp = strtotime($celda);

                $anio = date('y', $timestamp);
                $mes = date('m', $timestamp);
                
                $datos[2] = $celda;
            } else if ($col == 9) {    //CONTRATO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[3] = $celda;
            }

        }

        $contenido.= "SET @contrato = (SELECT ref FROM khns_mantenimiento_contratos WHERE rowid = ".$datos[3].")//";
        $contenido.= "\r\n";
        $contenido.= "UPDATE khns_mantenimiento_informes ";
        $contenido.= "SET ref = 'INF".$anio."".$mes."-".str_pad($rowid, 5, 0, STR_PAD_LEFT)."', description = '".$datos[1].", del contrato: (VER FICHA)', observations = 'Intervención de mantenimiento para el contrato: (VER FICHA)' ";
        $contenido.= "WHERE rowid = ".$rowid."//";
        $contenido.= "\r\n";
        $contenido.= "\r\n";


        $datos = array();

        $numInformes++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Informes: ".$numInformes.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="INFORMES-Ajuste.sql"');

    echo $contenido;
?>