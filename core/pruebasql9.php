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

    $ruta = "archivosImportacion/REPRESENTANTES.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $numRepre = 0;

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
            } else if ($col == 2) {    //NOMBRE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 3) {    //DIRECCION
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 4) {    //POBLACIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 5) {    //CP
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 6) {    //PROVINCIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 7) {    //TLF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 8) {    //FAX
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 9) {    //E-MAIL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 10) {    //PAIS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 11) {    //CIF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 12) {    //IRPF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 13) {    //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[] = $celda;
            } else if ($col == 14) {    //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[] = $fechaFormateada;
            }

        }

        $contenido.= "INSERT INTO khns_representantes ";
        $contenido.= "(id_representante, nombre, direccion, poblacion, cp, provincia, tlf, fax, e_mail, pais, cif, irpf, usuario, fecha) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", '".$datos[1]."', '".$datos[2]."', '".$datos[3]."', '".$datos[4]."', '".$datos[5]."', '".$datos[6]."', '".$datos[7]."', '".$datos[8]."', '".$datos[9]."', '".$datos[10]."', ".$datos[11].", '".$datos[12]."', '".$datos[13]."')// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $numRepre++;

        $datos = array();

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "REPRES: ".$numRepre.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="representantes.sql"');

    echo $contenido;
?>