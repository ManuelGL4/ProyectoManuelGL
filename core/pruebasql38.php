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

    $ruta = "archivosImportacion/CONTRATOS_222.xlsx";

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

    for ($fila = 2107; $fila <= $filamasalta; $fila++) {

        $tipo = 0;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID CONTRATO (FK_PRODUCT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 6) {    //ID CONTACTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            }  

        }

        if (($fila != 2108) && ($fila != 2109) && ($fila != 2110) && ($fila != 2111) && ($fila != 2112)) {

            $contenido.= "SET @contrato = (SELECT rowid FROM khns_mantenimiento_contratos WHERE id_khonos = ".$datos[0].")//";
            $contenido.= "\r\n";

            if ($datos[1] != -1) {
                $contenido.= "SET @idcontacto = (SELECT so.fk_object FROM khns_socpeople_extrafields so WHERE so.id_khonos = ".$datos[1]." LIMIT 1)//";
                $contenido.= "\r\n";
            }

            if ($datos[1] != -1) {
                $contenido.= "UPDATE khns_mantenimiento_contratos SET contact_id = @idcontacto WHERE rowid = @contrato//";
                $contenido.= "\r\n";
            } else {
                $contenido.= "UPDATE khns_mantenimiento_contratos SET contact_id = -1 WHERE rowid = @contrato//";
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
    header('Content-Disposition: attachment; filename="Ajuste_contactos_CONT.sql"');

    echo $contenido;
?>