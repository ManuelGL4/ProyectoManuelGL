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

    $ruta = "archivosImportacion/PROYECTOS.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numProyectos = 0;
    $datos = array();

    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID PROYECTO (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 2) {    //REPRESENTANTE (E.RESPONSABLE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 5) {    //NOMBRE (TITLE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 6) {    //DESCRIPCION (DESCRIPTION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 7) {    //CLASIFICACIÓN (E.CLASIFICACIÓN)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == "Túnel ferroviario") {
                    $celda = 1;
                }

                else if ($celda == "Túnel de carretera") {
                    $celda = 2;
                }

                else if ($celda == "Caldera") {
                    $celda = 3;
                }

                else if ($celda == "Aparcamiento") {
                    $celda = 4;
                }

                else if ($celda == "Intercambiador") {
                    $celda = 5;
                }

                else if ($celda == "") {
                    $celda = 0;
                }

                $datos[4] = $celda;
            } else if ($col == 8) {    //FECHA INICIO (DATEO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[5] = $fechaFormateada;
            } else if ($col == 9) {    //FECHA FIN (DATEE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[6] = $fechaFormateada;
            } else if ($col == 10) {    //OBSERVACIONES (E.OBSERVACIONES)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 12) {    //CÓDIGO (PROYECTO)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 13) {    //CÓDIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[9] = $celda;
            }

        }

        $contenido.= "INSERT INTO khns_proyectos_obra ";
        $contenido.= "(rowid, ref, representative, name, description, date_start, date_end, typology, remarks, code, fk_user_creat, status) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", '".$datos[9]."', ".$datos[1].", '".$datos[2]."', '".$datos[3]."', '".$datos[5]."', '".$datos[6]."', '".$datos[4]."', '".$datos[7]."', '".$datos[8]."', 1, 1)// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $datos = array();

        $numProyectos++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Proyectos: ".$numProyectos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Proyectos.sql"');

    echo $contenido;
?>