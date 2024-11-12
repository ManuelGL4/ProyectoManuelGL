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

    $ruta = "archivosImportacion/CF-Contactos Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $numContactos = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $datos = array();
    $datosExtra = array();
    $datosID = array();

    for ($fila = 2973; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID CONTACTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosID[0] = $celda;
            } else if ($col == 2) {    //ID CLIENTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 4) {    //E.DELEGACIÓN 
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[0] = $celda;
            } else if ($col == 5) {    //E.DEPARTAMENTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[1] = $celda;
            } else if ($col == 6) {    //CONTACTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[1] = $celda;
            } else if ($col == 7) {    //CARGO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 8) {    //TELEFONO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 9) {    //TELEFONO 2
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[4] = $celda;
            } else if ($col == 11) {    //MOVIL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 12) {    //FAX
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 13) {    //E-MAIL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 14) {    //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[2] = $celda;
            } else if ($col == 15) {    //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datosExtra[3] = $fechaFormateada;
            } else if ($col == 16) {    //OBSERVACIONES
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[4] = $celda;
            }

        }

        $contenido.= "SET @iduser = (SELECT se.fk_object FROM khns_societe_extrafields se INNER JOIN khns_societe s ON s.rowid = se.fk_object WHERE id_khonos = ".$datos[0]." AND s.client = 1)//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_socpeople ";
        $contenido.= "(fk_soc, lastname, poste, phone, phone_perso, phone_mobile, fax, email, fk_user_creat) ";
        $contenido.= "VALUES ";
        $contenido.= "(@iduser, '".$datos[1]."', '".$datos[2]."', '".$datos[3]."', '".$datos[4]."', '".$datos[5]."', '".$datos[6]."', '".$datos[7]."', 1)// ";
        $contenido.= "\r\n";
        $contenido.= "SET @last_id = LAST_INSERT_ID()//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_socpeople_extrafields ";
        $contenido.= "(fk_object, department, delegacion, observaciones, id_khonos, usuario, fecha) ";
        $contenido.= "VALUES ";
        $contenido.= "(@last_id, '".$datosExtra[1]."', '".$datosExtra[0]."', '".$datosExtra[4]."', ".$datosID[0].", '".$datosExtra[2]."', '".$datosExtra[3]."')// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $numContactos++;

        $datos = array();
        $datosExtra = array();
        $datosID = array();

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "CONTACTOS: ".$numContactos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Contactos-CLI.sql"');

    echo $contenido;
?>