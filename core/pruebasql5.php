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

    $ruta = "archivosImportacion/CONTRATOS.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();
    $datosExtra = array();
    $datosID = array();
    $datosFijos = array();
    $datosEstado = array();

    $numContratos = 0;

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

            } else if ($col == 3) {    //ID RESPONSABLE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[0] = $celda;
            } else if ($col == 4) {     //ID TIPO OFERTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[1] = $celda;
            } else if ($col == 5) {     //ID CLIENTE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[2] = $celda;
            } else if ($col == 6) {     //ID CONTACTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 10) {     //ID DELEGACIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[4] = $celda;
            } else if ($col == 11) {     //FORMA PAGO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[5] = $celda;
            } else if ($col == 13) {     //ID PERIODICIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 14) {     //DTO CLIENTE (%)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 15) {     //DTO PEDIDO (%)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 16) {     //E.DTO PEDIDO (EUROS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[0] = $celda;
            } else if ($col == 18) {     //E.DTO CLIENTE (EUROS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[1] = $celda;
            } else if ($col == 20) {     //FECHA INICIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[9] = $fechaFormateada;
            } else if ($col == 21) {     //FECHA FIN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[10] = $fechaFormateada;
            } else if ($col == 23) {     //DESCRIPCIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[11] = $celda;
            } else if ($col == 26) {     //EJERCICIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[12] = $celda;
            } else if ($col == 27) {     //NÚMERO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[13] = $celda;
            } else if ($col == 28) {     //CODIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[14] = $celda;
            } else if ($col == 30) {     //E.SUMA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[2] = $celda;
            } else if ($col == 34) {     //E.SUBTOTAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[3] = $celda;
            } else if ($col == 36) {     //E.COSTE MATERIAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[4] = $celda;
            } else if ($col == 42) {     //E.COSTE PRUEBAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[5] = $celda;
            } else if ($col == 52) {     //E.BASE IMPONIBLE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[6] = $celda;
            } else if ($col == 54) {     //E.IVA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[7] = $celda;
            } else if ($col == 56) {     //E.TOTAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[8] = $celda;
            } else if ($col == 58) {     //E.BRUTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[9] = $celda;
            } else if ($col == 61) {     //ESTADO HOMOLOGACIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[15] = $celda;
            } else if ($col == 62) {     //ID ESTADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosEstado[0] = $celda;
            } else if ($col == 64) {     //RÉGIMEN IVA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[16] = $celda;
            } else if ($col == 70) {     //E.DTO LÍNEA TOTAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[10] = $celda;
            } else if ($col == 74) {     //REF ANTERIOR
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[17] = $celda;
            } else if ($col == 76) {     //GARANTÍA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[18] = $celda;
            } else if ($col == 77) {     //PORC RESOLUCIÓN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[19] = $celda;
            } else if ($col == 78) {     //PLAZO ENTREGA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[20] = $celda;
            } else if ($col == 79) {     //TIEMPO ESTIMADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[21] = $celda;
            } else if ($col == 80) {     //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosFijos[0] = $celda;
            } else if ($col == 81) {     //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datosFijos[1] = $fechaFormateada;
            } else if ($col == 84) {     //ID PEDIDO ORIGEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[22] = $celda;
            } else if ($col == 85) {     //REF PROYECTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[23] = $celda;
            } else if ($col == 86) {     //ID CONTRATO PADRE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[24] = $celda;
            } else if ($col == 87) {     //DTO GENERAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[25] = $celda;
            }

        }

        //QUEDA POR AJUSTAR:
        //IDS DE PROYECTOS Y SUS REFS (HAY QUE AÑADIR ANTES LOS PROYECTOS)
        //IDS DE RESPONSABLES (HAY QUE HACER AJUSTE CON LOS IDS DE KHONOS)
        //IDS DE CONTACTOS (HAY QUE AÑADIRLOS ANTES)
        $contenido.= "\r\n";
        $contenido.= "\r\n";

        if (($datos[2] == "") || ($datos[2] == "NULL")) {
            $contenido.= "SET @idcliente = -1//";
        } else {
            $contenido.= "SET @idcliente = (SELECT se.fk_object FROM khns_societe_extrafields se INNER JOIN khns_societe s ON s.rowid = se.fk_object WHERE id_khonos = ".$datos[2]." AND s.client = 1)//";
        }
        
        //Para el contrato padre
        if ($datos[24] != 0) {
            $contenido.= "SET @idpadre = (SELECT rowid FROM khns_mantenimiento_contratos WHERE id_khonos = ".$datos[24].")//";
            $contenido.= "\r\n";
        }
        
        $contenido.= "\r\n";
        
        if ($numContratos >= 2000) {
            if (($datos[3] != -1) && ($datos[3] != 330) && ($datos[3] != 453) && ($datos[3] != 1364) && ($datos[3] != 1610) && ($datos[3] != 1682) && ($datos[3] != 1428) && ($datos[3] != 1846) && ($datos[3] != 3631) && ($datos[3] != 2285)) {
                $contenido.= "SET @idcontacto = (SELECT se.fk_object FROM khns_socpeople_extrafields se INNER JOIN khns_socpeople s ON s.rowid = se.fk_object INNER JOIN khns_societe soc ON soc.rowid = s.fk_soc WHERE id_khonos = ".$datos[3]." AND soc.client = 1)//";
                $contenido.= "\r\n";
            }
        }

        $contenido.= "INSERT INTO khns_mantenimiento_contratos ";
        $contenido.= "\r\n";
        $contenido.= "(ref, order_number, description, project_id, representative_id, offer_id, offer_type, contact_discount, spare_parts_discount, client_id, ";
        $contenido.= "client_discount, contact_id, currency, languaje, delegation_id, payment_method, periodicity, date_start, ";
        $contenido.= "date_end, estimated_time, usuario, fecha, ejercicio, numero, estado_homologacion, id_regimen_iva, garantia, porc_resolucion, plazo_entrega, ";
        $contenido.= "ref_proyecto, id_contrato_padre, id_estado, dto_general, id_khonos, fk_user_creat) ";
        $contenido.= "\r\n";
        $contenido.= "VALUES ";
        $contenido.= "\r\n";
        $contenido.= "('".$datos[14]."', '".$datos[17]."', '".$datos[11]."', -1, ".$datos[0].", '', ".$datos[1].", ".$datos[8].", ".$datos[25].", @idcliente, ";
        
        if ($numContratos >= 2000) {
            if (($datos[3] != -1) && ($datos[3] != 330) && ($datos[3] != 453) && ($datos[3] != 1364) && ($datos[3] != 1610) && ($datos[3] != 1682) && ($datos[3] != 1428) && ($datos[3] != 1846) && ($datos[3] != 3631) && ($datos[3] != 2285)) {
                $contenido.= "".$datos[7].", -1, 1, 0, ".$datos[4].", ".$datos[5].", ".$datos[6].", '".$datos[9]."', ";
            } else {
                $contenido.= "".$datos[7].", -1, 1, 0, ".$datos[4].", ".$datos[5].", ".$datos[6].", '".$datos[9]."', ";
            }
        } else {
            $contenido.= "".$datos[7].", -1, 1, 0, ".$datos[4].", ".$datos[5].", ".$datos[6].", '".$datos[9]."', ";
        }
        
        $contenido.= "'".$datos[10]."', ".$datos[21].", '".$datosFijos[0]."', '".$datosFijos[1]."', ".$datos[12].", '".$datos[13]."', '".$datos[15]."', ".$datos[16].", '".$datos[18]."', ".$datos[19].", '".$datos[20]."', ";
        
        if ($datos[24] != 0) {
            $contenido.= "'".$datos[23]."', @idpadre, ".$datosEstado[0].", ".$datos[25].", ".$datosID[0].", 1)//";
        } else {
            $contenido.= "'".$datos[23]."', ".$datos[24].", ".$datosEstado[0].", ".$datos[25].", ".$datosID[0].", 1)//";
        }

        $contenido.= "\r\n";

        $contenido.= "SET @last_id = LAST_INSERT_ID()//";

        $contenido.= "\r\n";

        $contenido.= "INSERT INTO khns_mantenimiento_contratos_precios ";
        $contenido.= "(id_contrato, coste_material, coste_pruebas, dto_cliente, dto_pedido, dto_linea_total, bruto, base_imponible, iva, suma, subtotal, total) ";
        $contenido.= "\r\n";
        $contenido.= "VALUES ";
        $contenido.= "\r\n";
        $contenido.= "(@last_id, ".$datosExtra[4].", ".$datosExtra[5].", ".$datosExtra[1].", ".$datosExtra[0].", ".$datosExtra[10].", ".$datosExtra[9].", ".$datosExtra[6].", ".$datosExtra[7].", ".$datosExtra[2].", ".$datosExtra[3].", ".$datosExtra[8].")//";

        $datos = array();
        $datosExtra = array();
        $datosID = array();
        $datosFijos = array();
        $datosEstado = array();

        $numContratos++;

    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "CONTRATOS: ".$numContratos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="MAN-Contratos.sql"');

    echo $contenido;
?>