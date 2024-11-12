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

    $ruta = "archivosImportacion/CONTRATOS TAREAS.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $numTareas = 0;

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $datos = array();
    $datosID = array();

    $contratoAnterior = "";
    $equipoAnterior = "";

    for ($fila = 45001; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID TAREAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosID[0] = $celda;
            } else if ($col == 2) {    //ID EQUIPO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 3) {    //ID ARTÍCULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[1] = $celda;
            } else if ($col == 4) {    //ID TIPO ARTÍCULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 8) {    //CANTIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 10) {    //TIPO ARTÍCULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($datos[2] == -2) {
                    $celda = "Articulo";
                }

                $datos[4] = $celda;
            } else if ($col == 13) {    //MANTENIDO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 15) {    //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 16) {    //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[7] = $fechaFormateada;
            }

        }

        $equipo = "SELECT fk_contract FROM khns_mantenimiento_contratos_equipos WHERE id_khonos = ".$datos[0]." ";
		$resultEquipo = $db->query($equipo);
		$contract = $db->fetch_object($resultEquipo);
		$contract = $contract->fk_contract;

        //CAMBIADO
        /*if ($contract == $contratoAnterior) {

            if ($datos[0] == $equipoAnterior) {

                $contenido.= "SET @idproducto = (SELECT p.rowid FROM khns_product p INNER JOIN khns_product_extrafields e ON e.fk_object = p.rowid WHERE e.id_khonos = ".$datos[1].")//";
                $contenido.= "\r\n";
                $contenido.= "INSERT INTO khns_mantenimiento_contratos_repuestos ";
                $contenido.= "(fk_contract, fk_equipo, fk_product, quantity, id_tipo_articulo, tipo_articulo, mantenido, usuario, fecha, id_khonos) ";
                $contenido.= "VALUES ";
                $contenido.= "(".$contract.", ".$datos[0].", @idproducto, ".$datos[3].", ".$datos[2].", '".$datos[4]."', ".$datos[5].", '".$datos[6]."', '".$datos[7]."', ".$datosID[0].")// ";
                $contenido.= "\r\n";
                $contenido.= "\r\n";

            }

        } else {*/

            $contratoAnterior = $contract;
            $equipoAnterior = $datos[0];

            $contenido.= "SET @idproducto = (SELECT p.rowid FROM khns_product p INNER JOIN khns_product_extrafields e ON e.fk_object = p.rowid WHERE e.id_khonos = ".$datos[1].")//";
            $contenido.= "\r\n";
            $contenido.= "INSERT INTO khns_mantenimiento_contratos_repuestos ";
            $contenido.= "(fk_contract, fk_equipo, fk_product, quantity, id_tipo_articulo, tipo_articulo, mantenido, usuario, fecha, id_khonos) ";
            $contenido.= "VALUES ";
            $contenido.= "(".$contract.", ".$datos[0].", @idproducto, ".$datos[3].", ".$datos[2].", '".$datos[4]."', ".$datos[5].", '".$datos[6]."', '".$datos[7]."', ".$datosID[0].")// ";
            $contenido.= "\r\n";
            $contenido.= "\r\n";

        //}

        $numTareas++;

        $datos = array();
        $datosID = array();

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "TAREAS: ".$numTareas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Contratos-Repuestos-TODOS.sql"');

    echo $contenido;
?>