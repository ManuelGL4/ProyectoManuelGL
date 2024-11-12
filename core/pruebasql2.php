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

    $ruta = "archivosImportacion/CF-Productos2.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();
    $datosExtra = array();
    $datosSto = array();
    $datosTipo = array();

    $numeroIdentificador = 1;

    $numProductos = 0;

    $contenido = "";

    //LEEMOS EL EXCEL
    //CADAD FILA
    for ($fila = 14831; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID KHONOS

                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $id_khonos = $celda;

            } else if ($col == 2) {    //FAMILIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/

                $cable = 1;
                if ($celda == 1) {
                    $cable = 2;
                }

                $dataFilaExtra[0] = $celda;
            } else if ($col == 4) {     //ARTICULO LABEL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[0] = $celda;
            } else if ($col == 5) {     //ARTICULO DESCRIPTION
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[1] = $celda;
            } else if ($col == 6) {     //ARTICULO REF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[2] = $celda;
            } else if ($col == 9) {     //ID TIPO ARTICULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $celda = floatval($celda);
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/

                if (($celda == 0) || ($celda == -1)) {
                    $tipo = 1;
                } else {
                    $tipo = 0;
                }

                $dataFilaExtra[1] = $celda;
                $dataTipo[0] = $tipo;
            } else if ($col == 10) {     //ID CATEGORIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[2] = $celda;
            } else if ($col == 11) {     //ID NATURALEZA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[3] = $celda;
            } else if ($col == 13) {     //ID TIPO IVA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[4] = $celda;
            } else if ($col == 21) {     //COSTE TEORICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[3] = $celda;
            } else if ($col == 22) {     //FECHA COSTE TEORICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                    $fechaObjeto = DateTime::createFromFormat('Ymd', $celda);
                    $fechaFormateada = $fechaObjeto->format('Y-m-d');
                } else {
                    $fechaFormateada = "NULL";
                }

                $dataFilaExtra[5] = $fechaFormateada;
            } else if ($col == 23) {     //COSTE MEDIO ALMACEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[6] = $celda;
            } else if ($col == 24) {     //FECHA COSTE MEDIO ALMACEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                    $fechaObjeto = DateTime::createFromFormat('Ymd', $celda);
                    $fechaFormateada = $fechaObjeto->format('Y-m-d');
                } else {
                    $fechaFormateada = "NULL";
                }

                $dataFilaExtra[7] = $fechaFormateada;
            } else if ($col == 25) {     //COSTE ULTIMA COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[4] = $celda;
            } else if ($col == 26) {     //FECHA COSTE ULTIMA COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                    $fechaObjeto = DateTime::createFromFormat('Ymd', $celda);
                    $fechaFormateada = $fechaObjeto->format('Y-m-d');
                } else {
                    $fechaFormateada = "NULL";
                }

                $dataFilaExtra[8] = $fechaFormateada;
            } else if ($col == 39) {     //TIEMPO MEDIO COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[9] = $celda;
            } else if ($col == 40) {     //FECHA TIEMPO MEDIO COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                    $fechaObjeto = DateTime::createFromFormat('Ymd', $celda);
                    $fechaFormateada = $fechaObjeto->format('Y-m-d');
                } else {
                    $fechaFormateada = "NULL";
                }

                $dataFilaExtra[10] = $fechaFormateada;
            } else if ($col == 43) {     //TIEMPO REAL COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[11] = $celda;
            } else if ($col == 44) {     //FECHA TIEMPO REAL COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda != "") {
                    $fechaObjeto = DateTime::createFromFormat('Ymd', $celda);
                    $fechaFormateada = $fechaObjeto->format('Y-m-d');
                } else {
                    $fechaFormateada = "NULL";
                }

                $dataFilaExtra[12] = $fechaFormateada;
            } else if ($col == 45) {     //PRECIO VENTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[5] = $celda;
            } /*else if ($col == 46) {     //EXISTENCIA TEORICA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[19] = $celda;
            }*/ else if ($col == 47) {     //EXISTENCIA REAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaSto[0] = $celda;
            } else if ($col == 50) {     //STOCK MINIMO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFila[6] = $celda;
            } else if ($col == 53) {     //BLOQUEADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[13] = $celda;
            } else if ($col == 54) {     //MANTENIMIENTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[14] = $celda;
            } else if ($col == 61) {     //COSTE APLICABLE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[15] = $celda;
            } else if ($col == 62) {     //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[16] = $celda;
            } /*else if ($col == 63) {     //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $celda = "";

                $dataFila[26] = $celda;
            }*/ else if ($col == 64) {     //OBSERVACIONES
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                /*if ($celda == "") {
                    $celda = "NULL";
                }*/

                $dataFila[7] = $celda;
            } else if ($col == 65) {     //TRAZABILIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                /*if ($celda === "") {
                    $celda = "NULL";
                }*/
                $dataFilaExtra[17] = $celda;
            }

        }

        $datos[] = $dataFila;
        $datosExtra[] = $dataFilaExtra;
        $datosSto[] = $dataFilaSto;
        $datosTipo[] = $dataTipo;

        $numeroIdentificador++;

        /*print "<br>";
        print_r($datos);
        print "<br>";
        print_r($datosExtra);
        print "<br>";
        print_r($datosSto);
        print "<br>";*/
        //die;

        $contenido.= "\r\n";

        $contenido.= "INSERT INTO khns_product (label, description, ref, pmp, cost_price, price, desiredstock, note, fk_product_type) ";
        $contenido.= " VALUES ";
        $contenido.= "('".$datos[0][0]."', '".$datos[0][1]."', '".$datos[0][2]."', ".$datos[0][3].", ".$datos[0][4].", ".$datos[0][5].", ".$datos[0][6].",";

        if ($datos[0][7] == "") {
            $contenido.= " NULL,";
        } else {
            $contenido.= " '".$datos[0][7]."',";
        }

        $contenido.= " ".$datosTipo[0][0]."); ";

        $contenido.= "\r\n";

        $contenido.= "SET @last_id = LAST_INSERT_ID();";

        $contenido.= "\r\n";

        $contenido.= "INSERT INTO khns_product_extrafields (fk_object, id_familia, id_tipo_articulo, id_categoria, id_naturaleza, id_tipo_iva, fecha_coste_teorico, coste_medio_almacen, fecha_coste_medio_almacen, fecha_coste_ultima_compra, tiempo_medio_compras, fecha_tiempo_medio_compras, tiempo_real_compra, fecha_tiempo_real_compra, bloqueado, mantenimiento, coste_aplicable, usuario, trazabilidad, cable, id_khonos) ";
        $contenido.= " VALUES ";
        $contenido.= "(@last_id, ".$datosExtra[0][0].", ".$datosExtra[0][1].", ".$datosExtra[0][2].", ".$datosExtra[0][3].", ".$datosExtra[0][4].", '".$datosExtra[0][5]."', ".$datosExtra[0][6].", '".$datosExtra[0][7]."', '".$datosExtra[0][8]."', ".$datosExtra[0][9].", '".$datosExtra[0][10]."', ".$datosExtra[0][11].", '".$datosExtra[0][12]."', ".$datosExtra[0][13].", ".$datosExtra[0][14].", ".$datosExtra[0][15].", '".$datosExtra[0][16]."', ".$datosExtra[0][17].", $cable, $id_khonos); ";

        $contenido.= "\r\n";

        $contenido.= "INSERT INTO khns_product_stock (fk_product, fk_entrepot, reel) ";
        $contenido.= " VALUES ";
        $contenido.= "(@last_id, 1, ".$datosSto[0][0]."); ";

        $contenido.= "\r\n";

        //die;

        //print $contenido;
        //print "<br>";

        $datos = array();
        $datosExtra = array();
        $datosSto = array();
        $datosTipo = array();

        $numProductos++;

    }

    $contenido.= "\r\n";
    $contenido.= "PRODUCTOS: ".$numProductos.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="insert.sql"');

    echo $contenido;
?>