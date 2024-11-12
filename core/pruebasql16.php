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

    $ruta3 = "archivosImportacion/Fases.xlsx";

    $hoja3 = IOFactory::load($ruta3);

    $sheet3 = $hoja3->getActiveSheet();

    $filamasalta3 = $sheet3->getHighestRow();
    $colummasalta3 = $sheet3->getHighestColumn();

    $columnumero3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta3);

    $datsoEquipoContrato = array();
    $datosIdFase = array();
    $datosInforme = array();
    $datos = array();
    $datosID = array();

    $numEquipos = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numInformes = 0;
    $datos = array();
    $orden = "";

    for ($fila = 28001; $fila <= $filamasalta3; $fila++) {

        $indice = 0;
        $encontrado = false;

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero3; $col++) {

            if ($col == 1) {    //ID ORDEN (CADA ORDEN ÚNICO ES UN INFORME)
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $ordenFila = $celda;

                if ($orden == "") {
                    $orden = $ordenFila;
                }

                $sqlInforme = " SELECT rowid, id_khonos FROM khns_mantenimiento_informes WHERE id_khonos = ".$ordenFila." ";
                $resultInforme = $db->query($sqlInforme);
                $num = $db->num_rows($resultInforme);

            } else if ($col == 3) {    //ID ARTÍCULO
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $equipo = $celda;

            } else if ($col == 5) {    //DESCRIPCIÓN
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $descripcion = $celda;

            } else if ($col == 6) {    //UNIDADES
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $unidades = $celda;

            } else if ($col == 7) {    //AVANCE
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $avance = $celda;

            } else if ($col == 13) {    //OBSERVACIONES
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $observ = $celda;

            } else if ($col == 2) {    //ID FASE
                $celda = $sheet3->getCellByColumnAndRow($col, $fila)->getValue();
                $fase = $celda;

            }

        }

        if ($num > 0) {

            $informe = $db->fetch_object($resultInforme);
            $informe = $informe->rowid;

            $encontrado = true;

        }

        if ($encontrado) {

            $contenido.= "SET @idequipo = (SELECT fk_object FROM khns_product_extrafields WHERE id_khonos = ".$equipo.")//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_mantenimiento_informes_equipos (fk_report, id_fase_khonos, fk_product, description, advance, remarks, failure, qty_failure) ";
            $contenido.= "VALUES ";
            $contenido.= "(".$informe.", ".$fase.", @idequipo, '".$descripcion."', ".$avance.", '".$observ."', @idequipo, '".$unidades."')//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_mantenimiento_informes_sustituciones (fk_report, id_fase_khonos, fk_product_root, fk_product, quantity, is_future, is_retired, is_returned) ";
            $contenido.= "VALUES ";
            $contenido.= "(".$informe.", ".$fase.", @idequipo, @idequipo, ".$unidades.", 0, 0, 0)//";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        }

        $datos = array();

        $numEquipos++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Equipos: ".$numEquipos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Informes-Equipos.sql"');

    echo $contenido;
?>