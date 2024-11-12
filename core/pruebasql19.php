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

    $ruta = "archivosImportacion/CF-Facturas Ventas.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numFacturas = 0;
    $datos = array();
    $datosExtra = array();

    for ($fila = 6843; $fila <= $filamasalta; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //ID FACTURA (ROWID)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 5) {    //CÓDIGO (REF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[1] = $celda;
            } else if ($col == 8) {    //FECHA FACTURA (DATEF)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $ano = substr($celda,  0,  4); // Extrae los primeros dos dígitos (año)
                $mes = substr($celda,  4,  2); // Extrae los siguientes dos dígitos (mes)
                $dia = substr($celda,  6,  2); // Extrae los últimos dos dígitos (día)

                $fechaFormateada = $ano . '-' . $mes . '-' . $dia; // Concatena en el formato deseado

                $datos[2] = $fechaFormateada;
            } else if ($col == 11) {    //ID CLIENTE (FK_SOC) (MIRAR ID_KHONOS)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[3] = $celda;
            } else if ($col == 20) {    //ID DIVISA DESTINO (FK_MULTICURRENCY)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == 0) {
                    $celda = 1;
                }

                $datos[4] = $celda;
            } else if ($col == 21) {    //DIVISA DESTINO (MULTICURRENCY_CODE)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == "EURO") {
                    $celda = "EUR";
                }

                $datos[5] = $celda;
            } else if ($col == 28) {    //ID ESTADO
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[6] = $celda;
            } else if ($col == 35) {    //PORCENTAJE RETENCIÓN (E.RETENCION)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datosExtra[0] = $celda;
            } else if ($col == 54) {    //BASE IMPONIBLE (TOTAL_HT)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[7] = $celda;
            } else if ($col == 58) {    //IMPORTE IVA (TOTAL_TVA)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[8] = $celda;
            } else if ($col == 64) {    //TOTAL FACTURA (TOTAL_TTC)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[9] = $celda;
            } else if ($col == 75) {    //ID PROYECTO (FK_PROJET)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $datos[10] = $celda;
            } else if ($col == 69) {    //OBSERVACIONES (E.INFORMACION_ADICIONAL)
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $cadenaSinSaltos = preg_replace("/[\r\n]+/", ' ', $celda);


                $datosExtra[1] = $cadenaSinSaltos;
            }

        }

        $contenido.= "SET @idcliente = (SELECT e.fk_object FROM khns_societe_extrafields e INNER JOIN khns_societe s ON s.rowid = e.fk_object WHERE e.id_khonos = ".$datos[3]." AND s.client = 1)//";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_facture ";
        $contenido.= "(rowid, ref, datef, fk_soc, fk_multicurrency, multicurrency_code, total_ht, total_tva, total_ttc) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", '".$datos[1]."', '".$datos[2]."', @idcliente, ".$datos[4].", '".$datos[5]."', ".$datos[7].", ".$datos[8].", ".$datos[9].")// ";
        $contenido.= "\r\n";
        $contenido.= "INSERT INTO khns_facture_extrafields ";
        $contenido.= "(fk_object, retencion, informacion_adicional, id_proyecto) ";
        $contenido.= "VALUES ";
        $contenido.= "(".$datos[0].", ".$datosExtra[0].", '".$datosExtra[1]."', ".$datos[10].")// ";
        $contenido.= "\r\n";
        $contenido.= "\r\n";


        $datos = array();
        $datosExtra = array();

        $numFacturas++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Facturas: ".$numFacturas.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="FRAS_VTA_cabeceras.sql"');

    echo $contenido;
?>