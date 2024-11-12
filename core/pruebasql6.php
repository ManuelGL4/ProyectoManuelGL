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

    $ruta = "archivosImportacion/CONTRATOS EQUIPOS.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();
    $datosID = array();

    $numEquipos = 0;
    $contrato = "";

    $contenido = "DELIMITER //";

    //LEEMOS EL EXCEL
    //CADAD FILA
    for ($fila = 2; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID KHONOS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $id_khonos = $celda;
                $datosID[] = $id_khonos;

            } else if ($col == 2) {    //ID CONTRATO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 3) {     //ID FASE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[1] = $celda;
            } else if ($col == 4) {     //ID ARTICULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 8) {     //ID TIPO ARTICULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == -2) {
                    $celda = 0;
                }

                if ($celda == -1) {
                    $celda = 1;
                }

                $datos[3] = $celda;
            } else if ($col == 9) {     //TIPO ARTICULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == "Articulo") {
                    $celda = "Producto";
                }

                if ($celda == "Servicio Interno") {
                    $celda = "Servicio";
                }


                $datos[4] = $celda;
            } else if ($col == 10) {     //CANTIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 12) {     //NUM SERIE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 13) {     //LOTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 14) {     //MANTENIDO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 15) {     //OBSERVACIONES
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[9] = $celda;
            } else if ($col == 16) {     //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[10] = $celda;
            } else if ($col == 17) {     //FECHA CREACIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[11] = $fechaFormateada;
            } else if ($col == 18) {     //FIN GARANTÍA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[12] = $fechaFormateada;
            }

        }

        //QUEDA POR AJUSTAR:
        //IDS DE FASES (HAY QUE AÑADIR ANTES LOS PROYECTOS)
        $contenido.= "\r\n";
        $contenido.= "\r\n";
        $contenido.= "SET @idcontrato = (SELECT rowid FROM khns_mantenimiento_contratos WHERE id_khonos = ".$datos[0].")//";
        $contenido.= "\r\n";
        $contenido.= "SET @idproducto = (SELECT p.rowid FROM khns_product p INNER JOIN khns_product_extrafields pe ON pe.fk_object = p.rowid WHERE pe.id_khonos = ".$datos[2].")//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_mantenimiento_contratos_equipos ";
        $contenido.= "(fk_contract, id_fase, fk_product, id_tipo_articulo, tipo_articulo, cantidad, num_serie, lote, mantenido, observaciones, fin_garantia, usuario, date_creation, id_khonos)";
        $contenido.= "\r\n";
        $contenido.= "VALUES ";
        $contenido.= "\r\n";
        $contenido.= "(@idcontrato, ".$datos[1].", @idproducto, ".$datos[3].", '".$datos[4]."', ".$datos[5].", '".$datos[6]."', '".$datos[7]."', ".$datos[8].", '".$datos[9]."', '".$datos[12]."', '".$datos[10]."', '".$datos[11]."', $datosID[0])// ";

        //$contenido.= "SET @last_id = LAST_INSERT_ID()//";

        if ($contrato != $datos[0]) {

            $contenido.= "\r\n";
            $contrato = $datos[0];
            $contenido.= "UPDATE khns_mantenimiento_contratos SET warranty_end = '".$datos[12]."' WHERE rowid = @idcontrato//";

        }

        $contenido.= "\r\n";
        $contenido.= "\r\n";

        $datos = array();
        $datosID = array();

        $numEquipos++;

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "EQUIPOS: ".$numEquipos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Contratos-Equipos.sql"');

    echo $contenido;
?>