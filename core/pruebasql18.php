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

    //print_r($datos);
    $sqlFactura = " SELECT f.*, s.*, se.* FROM khns_facture f ";
    $sqlFactura.= " INNER JOIN khns_societe s ON s.rowid = f.fk_soc ";
    $sqlFactura.= " INNER JOIN khns_societe_extrafields se ON se.fk_object = s.rowid ";
    $sqlFactura.= " WHERE f.datef >= '2024-01-01' AND f.fk_user_closing = 1";

    $resultFactura = $db->query($sqlFactura);
    
    //CREAR NUEVO ARCHIVO
    $nuevoExcel = new Spreadsheet();
    $hojaNueva = $nuevoExcel->getActiveSheet();

    $cabecera = ["Orden", "Cuenta", "Nombre", "NIF", "Fecha", "Asiento", "Factura", "Fecha Factura", "Base", "%IVA/IGIC", "%RE", "Cuota", "RE", "Total"];

    $hojaNueva->fromArray($cabecera, NULL, 'A1');

    // Crea un nuevo estilo para la cabecera
    $styleArray = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
    ];

    // Aplica el estilo a las celdas de la cabecera
    foreach ($cabecera as $col => $value) {
        $hojaNueva->getStyleByColumnAndRow($col +  1,  1)->applyFromArray($styleArray);
    }

    $totalCol = 14;
    $fila = 2;
    $orden = 1;
    $asiento = 1;
    $numFactura = 1;

    while ($factura = $db->fetch_object($resultFactura)) {
        $col = 1;

        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$orden);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->cuenta_contable);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->nom);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->siren);
        $col++;

        $newDate = date("d/m/Y", strtotime($factura->datef));
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$newDate);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$asiento);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$numFactura);
        $col++;

        $newDate = date("d/m/Y", strtotime($factura->datef));
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$newDate);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->total_ht);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,21);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,0);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->total_tva);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,0);
        $col++;
        $hojaNueva->setCellValueByColumnAndRow($col,$fila,$factura->total_ttc);
        $col++;

        $fila++;
        $orden++;
        $asiento++;
        $numFactura++;

    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="prueba-facturas.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($nuevoExcel, 'Xlsx');
    $writer->save('php://output');
    exit;
    


?>