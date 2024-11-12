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

    $ruta = "archivosImportacion/RUTAS.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $numRutas = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $datos = array();

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 2) {    //RUTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 3) {    //CODIGO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 4) {    //DIST ANALÍTICA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 5) {    //CTA CONTABLE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 6) {    //CENTRO COSTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 7) {    //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 8) {    //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[] = $fechaFormateada;
            }

        }

        $contenido.= "INSERT INTO khns_ruta ";
        $contenido.= "(id_ruta, ruta, codigo, dist_analitica, cta_contable, centro_coste, usuario, fecha) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", '".$datos[1]."', '".$datos[2]."', ".$datos[3].", '".$datos[4]."', '".$datos[5]."', '".$datos[6]."', '".$datos[7]."')// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $numRutas++;

        $datos = array();

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "RUTAS: ".$numRutas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="rutas.sql"');

    echo $contenido;
?>