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

    $ruta = "archivosImportacion/Facturas pendientes de Cobro.xls";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $contenido = "DELIMITER //";
    $contenido.= "\r\n";
    $contenido.= "\r\n";

    $numFact = 0;
    $datos = array();
    $datosExtra = array();
    $fecha = date("Y-m-d H:i:s");
    $loteAnt = "";

    for ($fila = 3; $fila <= 83; $fila++) {

        //CADA COLUMNA
        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 1) {    //REF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                
                $datos[0] = $celda;
            } else if ($col == 3) {    //FECHA INICIAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
        

                $datos[1] = $fechaNueva;
            } else if ($col == 4) {    //FECHA FINAL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $fecha = \DateTime::createFromFormat('Y-m-d', '1900-01-01')->add(new \DateInterval('P' . ($celda - 2) . 'D'));
                $fechaFinal = $fecha->format('Y-m-d'); // Salida: 2024-03-16
                
                $datos[2] = $fechaFinal;
            } else if ($col == 6) {    //NUMERO DIAS
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                if ($celda == 0) {
                    $idpayment = 12;
                }

                if ($celda == 30) {
                    $idpayment = 2;
                }

                if ($celda == 60) {
                    $idpayment = 4;
                }

                if ($celda == 90) {
                    $idpayment = 9;
                }

                if ($celda == 120) {
                    $idpayment = 10;
                }

                if ($celda == 180) {
                    $idpayment = 11;
                }
                
                $datos[3] = $idpayment;
            } 

        }

        $contenido.= "UPDATE khns_facture SET date_lim_reglement = '".$datos[2]."', fk_cond_reglement = ".$datos[3].", fk_statut = 1, paye = 0 WHERE ref LIKE '%".$datos[0]."%'//";
        $contenido.= "\r\n";

        $datos = array();
        $datosExtra = array();

        $numFact++;
    
    }

    $contenido.= "\r\n";
    $contenido.= "\r\n";
    $contenido.= "DELIMITER ;";
    $contenido.= "\r\n";
    $contenido.= "Fact: ".$numFact.";";

    //die;

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Ajuste_estados_FACT.sql"');

    echo $contenido;
?>