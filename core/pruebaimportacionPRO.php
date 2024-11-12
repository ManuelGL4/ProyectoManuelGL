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

    $ruta = "archivosImportacion/aimportar(sin cabecera).xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();

    $numeroIdentificador = 1;

    for ($fila = 1; $fila <= $filamasalta; $fila++) {

        //$dataFila = array_fill(0,$columnumero,"");
        //$dataFila[3] = 1;
        //$dataFila[32] = 0;
        //$dataFila[33] = 1;
        //$dataFila[5] = "SU2401-".str_pad($numeroIdentificador, 5, 0, STR_PAD_LEFT);

        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 2) {    //FAMILIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[0] = $celda;
            } else if ($col == 4) {     //ARTICULO LABEL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[1] = $celda;
            } else if ($col == 5) {     //ARTICULO DESCRIPTION
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[2] = $celda;
            } else if ($col == 6) {     //ARTICULO REF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[3] = $celda;
            } else if ($col == 9) {     //ID TIPO ARTICULO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[4] = $celda;
            } else if ($col == 10) {     //ID CATEGORIA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[5] = $celda;
            } else if ($col == 11) {     //ID NATURALEZA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[6] = $celda;
            } else if ($col == 13) {     //ID TIPO IVA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[7] = $celda;
            } else if ($col == 21) {     //COSTE TEORICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[8] = $celda;
            } else if ($col == 22) {     //FECHA COSTE TEORICO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[9] = $celda;
            } else if ($col == 23) {     //COSTE MEDIO ALMACEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[10] = $celda;
            } else if ($col == 24) {     //FECHA COSTE MEDIO ALMACEN
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[11] = $celda;
            } else if ($col == 25) {     //COSTE ULTIMA COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[12] = $celda;
            } else if ($col == 26) {     //FECHA COSTE ULTIMA COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[13] = $celda;
            } else if ($col == 39) {     //TIEMPO MEDIO COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[14] = $celda;
            } else if ($col == 40) {     //FECHA TIEMPO MEDIO COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[15] = $celda;
            } else if ($col == 43) {     //TIEMPO REAL COMPRAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[16] = $celda;
            } else if ($col == 44) {     //FECHA TIEMPO REAL COMPRA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[17] = $celda;
            } else if ($col == 45) {     //PRECIO VENTA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[18] = $celda;
            } else if ($col == 46) {     //EXISTENCIA TEORICA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[19] = $celda;
            } else if ($col == 47) {     //EXISTENCIA REAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[20] = $celda;
            } else if ($col == 50) {     //STOCK MINIMO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[21] = $celda;
            } else if ($col == 53) {     //BLOQUEADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[22] = $celda;
            } else if ($col == 54) {     //MANTENIMIENTO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[23] = $celda;
            } else if ($col == 61) {     //COSTE APLICABLE
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[24] = $celda;
            } else if ($col == 62) {     //USUARIO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[25] = $celda;
            } else if ($col == 63) {     //FECHA
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $celda = "";

                $dataFila[26] = $celda;
            } else if ($col == 64) {     //OBSERVACIONES
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[27] = $celda;
            } else if ($col == 65) {     //TRAZABILIDAD
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[28] = $celda;
            }

        }

        $datos[] = $dataFila;

        $numeroIdentificador++;

    }

    //print_r($datos);

    //CREAR NUEVO ARCHIVO
    $nuevoExcel = new Spreadsheet();
    $hojaNueva = $nuevoExcel->getActiveSheet();

    $cabecera = ["id_familia", "articulo", "descripcion", "codigo", "id_tipo_articulo", "id_categoria", "id_naturaleza", "id_tipo_iva", "coste_teorico", "fecha_coste_teorico", "coste_medio_almacen", "fecha_coste_medio_almacen", "coste_ultima_compra", "fecha_coste_ultima_compra", "tiempo_medio_compras", "fecha_tiempo_medio_compras",	"tiempo_real_compra", "fecha_tiempo_real_compra", "precio_venta", "existencia_teorica",	"existencia_real",	"stock_minimo", "bloqueado", "mantenimiento","coste_aplicable",	"usuario", "fecha",	"observaciones", "id_tipo_trazabilidad"];

    $hojaNueva->fromArray($cabecera, NULL, 'A1');
    
    foreach ($datos as $fila => $dataFila) {

        foreach ($dataFila as $col => $dato) {
            // +1 porque las filas y columnas en Excel empiezan desde 1, no desde 0
            $hojaNueva->setCellValueByColumnAndRow($col + 1, $fila + 2, $dato);
        }

    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="productos_totales.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($nuevoExcel, 'Xlsx');
    $writer->save('php://output');
    exit;
    


?>