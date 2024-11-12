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

    $ruta = "archivosImportacion/CF-Delegaciones Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();
    $datosID = array();

    $numDelegaciones = 0;

    $contenido = "DELIMITER //";

    //LEEMOS EL EXCEL
    //CADAD FILA
    for ($fila = 1302; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID KHONOS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $id_khonos = $celda;
                $datosID[] = $id_khonos;

            } else if ($col == 2) {    //ID CLIENTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 4) {     //RUTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                //CONFIGURAR RUTA

                $datos[17] = $celda;
            } else if ($col == 5) {     //REPRESENTANTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                //CONFIGURAR REPRESENTANTE

                $datos[1] = $celda;
            } else if ($col == 6) {     //RAZÓN SOCIAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 7) {     //DESCRIPCIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $tipo;
            } else if ($col == 8) {     //NOMBRE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[4] = $celda;
            } else if ($col == 9) {     //ENCARGADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 11) {     //TELEFONO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 12) {     //FAX
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 13) {     //DIRECCIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 14) {     //POBLACIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[9] = $celda;
            } else if ($col == 15) {     //CP
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[10] = $celda;
            } else if ($col == 16) {     //PROVINCIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                //CONFIGURAR PROVINCIA

                $datos[11] = $celda;
            } else if ($col == 19) {     //EMAIL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[12] = $celda;
            } else if ($col == 20) {     //DIRECCIÓN MATERIAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[13] = $celda;
            } else if ($col == 21) {     //DIRECCIÓN FACTURA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[14] = $celda;
            } else if ($col == 24) {     //FORMA ENVÍO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                /*if ($celda == "") {
                    $celda = "NULL";
                }*/

                $datos[15] = $celda;
            } else if ($col == 25) {     //TLF TRANSPORTISTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                /*if ($celda == "") {
                    $celda = "NULL";
                }*/

                $datos[16] = $celda;
            }

        }

        $contenido.= "\r\n";
        $contenido.= "\r\n";
        $contenido.= "SET @iduser = (SELECT fk_object FROM khns_societe_extrafields WHERE id_khonos = ".$datos[0].")//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_delegacion (nombre, fk_representante, responsible_name, fk_ruta, telef1, telef2, direccion, cp, localidad, provincia, direccion_material, direccion_factura, pais, fk_tercero, codigo_delegacion, email, forma_envio, tlf_transp, iva, id_khonos) ";
        $contenido.= "\r\n";
        $contenido.= " VALUES ";
        $contenido.= "\r\n";
        $contenido.= "('".$datos[4]."', ".$datos[1].", '".$datos[5]."', ".$datos[17].", '".$datos[6]."', '".$datos[7]."', '".$datos[8]."', '".$datos[10]."', '".$datos[9]."', '".$datos[11]."', '".$datos[13]."', '".$datos[14]."', 4, @iduser, '".$datos[2]."', '".$datos[12]."', '".$datos[15]."', '".$datos[16]."', 21, ".$datosID[0].")//";

        $contenido.= "\r\n";

        //$contenido.= "SET @last_id = LAST_INSERT_ID();";

        $contenido.= "\r\n";

        $datos = array();
        $datosID = array();

        $numDelegaciones++;

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "DELEGACIONES: ".$numDelegaciones.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="delegaciones.sql"');

    echo $contenido;
?>