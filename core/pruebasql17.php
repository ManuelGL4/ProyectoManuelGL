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

    $ruta3 = "archivosImportacion/CONTRATOS SIG VISITA.xlsx";

    $hoja3 = IOFactory::load($ruta3);

    $sheet3 = $hoja3->getActiveSheet();

    $filamasalta3 = $sheet3->getHighestRow();
    $colummasalta3 = $sheet3->getHighestColumn();

    $columnumero3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta3);

    $numSig = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numInformes = 0;
    $datos = array();
    $orden = "";

    for ($fila = 2; $fila <= $filamasalta3; $fila++) {

        $indice = 0;
        $encontrado = false;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero3; $col++) {

            if ($col == 4) {    //ID FASE
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $fase = $celda;

                $sqlFase = " SELECT * FROM khns_mantenimiento_informes_sustituciones ";
                $sqlFase.= " WHERE id_fase_khonos = ".$fase." ";
                $resultFase = $db->query($sqlFase);
                $numFases = $db->num_rows($resultFase);

                if ($numFases > 0) {
                    $encontrado = true;
                }

            } else if ($col == 5) {    //ID ARTICULO
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $producto = $celda;

            } else if ($col == 6) {    //CANTIDAD
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $cantidad = $celda;

            }

        }

        if ($encontrado) {

            $contenido.= "SET @producto = (SELECT fk_object FROM khns_product_extrafields WHERE id_khonos = ".$producto.")//";
            $contenido.= "\r\n";
            $contenido.= "SET @informe = (SELECT fk_report FROM khns_mantenimiento_informes_sustituciones WHERE id_fase_khonos = ".$fase." LIMIT 1)//";
            $contenido.= "\r\n";
            $contenido.= "SET @productroot = (SELECT fk_product_root FROM khns_mantenimiento_informes_sustituciones WHERE id_fase_khonos = ".$fase." LIMIT 1)//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_mantenimiento_informes_sustituciones ";
            $contenido.= "(fk_report, id_fase_khonos, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned) ";
            $contenido.= "VALUES ";
            $contenido.= "(@informe, ".$fase.", @productroot, @producto, ".$cantidad.", 1, 0, 0)// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        }

        $datos = array();

        $numSig++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "SigVisita: ".$numSig.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Informes-SigVisita.sql"');

    echo $contenido;
?>