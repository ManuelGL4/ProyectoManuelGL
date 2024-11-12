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

const MONTHS = [
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre'
];

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


/*
 * View
 */

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    //Para sacar los datos de la certificacion
    $sqlDatosCert = " SELECT c.* FROM ".MAIN_DB_PREFIX."proyectos_certificaciones c ";
    $sqlDatosCert.= " WHERE c.rowid = ".$id;

    $resultDatosCert = $db->query($sqlDatosCert);
    $datosCert = $db->fetch_object($resultDatosCert);

    $sqlDatosLineas = " SELECT cl.* FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
    $sqlDatosLineas.= " WHERE fk_certificacion = ".$id;

    list($ano2, $mes2, $dia2) = explode("-", $datosCert->fecha);

    $resultDatosLineas = $db->query($sqlDatosLineas);

    //Para sacar el proyecto
    $sqlProyecto = " SELECT ref, fk_proyect FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlProyecto.= " WHERE rowid = ".$id;

    $resultProyecto = $db->query($sqlProyecto);
    $datosProyecto = $db->fetch_object($resultProyecto);

    $sqlProyecto2 = " SELECT p.*, pe.discount_offer FROM ".MAIN_DB_PREFIX."projet p ";
    $sqlProyecto2.= " INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = p.rowid ";
    $sqlProyecto2.= " WHERE p.rowid = ".$datosProyecto->fk_proyect;

    $resultProyecto2 = $db->query($sqlProyecto2);
    $datosProyecto2 = $db->fetch_object($resultProyecto2);

    //Para los datos del cliente
    $sqlCli = " SELECT s.*, se.porc_iva FROM ".MAIN_DB_PREFIX."societe s ";
    $sqlCli.= " INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = s.rowid ";
    $sqlCli.= " WHERE s.rowid = ".$datosProyecto2->fk_soc;

    $resultCli = $db->query($sqlCli);
    $datosCli = $db->fetch_object($resultCli);

    //PARA DESCUENTOS E IVA
    if ($datosCli->porc_iva == "") {
        $porc_iva = 21;
    } else {
        $porc_iva = $datosCli->porc_iva;
    }

    if (($datosProyecto2->discount_offer == "") || ($datosProyecto2->discount_offer == 0)) {
        $dto_oferta = 0;
    } else {
        $dto_oferta = $datosProyecto2->discount_offer;
    }

    if (($datosCli->remise_client == "") || ($datosCli->remise_client == 0)) {
        $dto_cliente = 0;
    } else {
        $dto_cliente = $datosCli->remise_client;
    }

    $descuentos = $dto_oferta + $dto_cliente;

    //Para el excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Unir celdas A1 y B1
    $sheet->mergeCells('A1:B3');

    // Establecer el texto en la celda unida
    $sheet->setCellValue('A1', 'EMPRESA:');

    // Centrar el texto horizontal y verticalmente
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    //Parte roja
    $sheet->mergeCells('C1:F2');
    $sheet->setCellValue('C1', 'Ortrat');
    $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('C1')->getFont()->getColor()->setARGB('FFFFFF');

    $sheet->setCellValue('C3', 'MES:');
    $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('C3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('C3')->getFont()->getColor()->setARGB('FFFFFF');

    $sheet->setCellValue('D3', ''.MONTHS[$mes2].'');
    $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('D3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('D3')->getFont()->getColor()->setARGB('FFFFFF');

    $sheet->setCellValue('E3', 'AÑO:');
    $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('E3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('E3')->getFont()->getColor()->setARGB('FFFFFF');

    $sheet->setCellValue('F3', ''.$ano2.':');
    $sheet->getStyle('F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('F3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('F3')->getFont()->getColor()->setARGB('FFFFFF');

    //Parte blanca
    $sheet->mergeCells('G1:I1');
    $sheet->setCellValue('G1', 'Cerrada a dia:');
    $sheet->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $fecha_formateada = date("d-m-Y", strtotime($datosCert->fecha));

    $sheet->mergeCells('J1:K1');
    $sheet->setCellValue('J1', ''.$fecha_formateada.'');
    $sheet->getStyle('J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('G2:K3');
    $sheet->setCellValue('G2', ''.$datosProyecto2->title.'');
    $sheet->getStyle('G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('L1:M3');
    $sheet->setCellValue('L1', 'PEDIDO Nº:');
    $sheet->getStyle('L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('L1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    //Segunda parte roja
    $sheet->mergeCells('N1:U3');
    $sheet->setCellValue('N1', ''.$datosProyecto2->ref.' - '.$datosProyecto2->title.''.chr(13).chr(10).$datosCli->code_client.' - '.$datosCli->nom.''.chr(13).chr(10).'CERT: '.$datosProyecto->ref);
    $sheet->getStyle('N1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('N1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
    $sheet->getStyle('N1')->getFont()->getColor()->setARGB('FFFFFF');

    // Habilitar el ajuste de texto para la celda
    $sheet->getStyle('N1')->getAlignment()->setWrapText(true);

    //Cabecera blanca antes de los datos
    $sheet->mergeCells('A4:A5');
    $sheet->setCellValue('A4', "LN");
    $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('B4:C5');
    $sheet->setCellValue('B4', "Uds. Contrato");
    $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('D4:L4');
    $sheet->setCellValue('D4', "PEDIDO");
    $sheet->getStyle('D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('M4:O4');
    $sheet->setCellValue('M4', "MEDICIÓN EJECUTADA");
    $sheet->getStyle('M4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('M4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('P4:U4');
    $sheet->setCellValue('P4', "IMPORTE");
    $sheet->getStyle('P4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('P4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('D5', "UD");
    $sheet->getStyle('D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('E5:I5');
    $sheet->setCellValue('E5', "CONCEPTO");
    $sheet->getStyle('E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('J5', "PRECIO");
    $sheet->getStyle('J5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('K5:L5');
    $sheet->setCellValue('K5', "PEDIDO");
    $sheet->getStyle('K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('K5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('M5', "ORIGEN");
    $sheet->getStyle('M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('M5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('N5', "ANTERIOR");
    $sheet->getStyle('N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('N5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('O5', "MES");
    $sheet->getStyle('O5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('O5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('P5:Q5');
    $sheet->setCellValue('P5', "ORIGEN");
    $sheet->getStyle('P5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('P5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('R5:S5');
    $sheet->setCellValue('R5', "ANTERIOR");
    $sheet->getStyle('R5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('R5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('T5:U5');
    $sheet->setCellValue('T5', "MES");
    $sheet->getStyle('T5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('T5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('V1:V5');
    $sheet->setCellValue('V1', "%");
    $sheet->getStyle('V1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('V1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    //PARA LAS LINEAS DE PRODUCTOS
    $numLinea = 1;
    $fila = 6;
    while ($linea = $db->fetch_object($resultDatosLineas)) {

        $sheet->setCellValue('A'.$fila.'', ''.$numLinea.'');
        $sheet->getStyle('A'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A'.$fila.'')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('DFDFDF');

        $sheet->mergeCells('B'.$fila.':C'.$fila.'');
        $sheet->setCellValue('B'.$fila.'', ''.$linea->cant_contrato.'');
        $sheet->getStyle('B'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        //Para el concepto
        $consultaNueva = " SELECT pom.rowid, pom.fk_product, p.rowid, p.ref, p.label, pe.cable, p.description, pom.quantity, pom.taxable_base, pom.price, pom.discount FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
        $consultaNueva.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product ";
        $consultaNueva.= " INNER JOIN ".MAIN_DB_PREFIX."product_extrafields pe ON pe.fk_object = p.rowid ";
        $consultaNueva.= " WHERE pom.fk_project = ".$datosProyecto->fk_proyect." AND p.rowid = ".$linea->fk_producto." AND pom.quantity = ".$linea->cant_contrato." ";

        $resultNueva = $db->query($consultaNueva);
        $concepto = $db->fetch_object($resultNueva);

        if ($concepto->cable == 2) {
            $sheet->setCellValue('D'.$fila.'', 'M. Linea');
        } else {
            $sheet->setCellValue('D'.$fila.'', 'Unidad');
        }

        $sheet->getStyle('D'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('E'.$fila.':I'.$fila.'');
        $sheet->setCellValue('E'.$fila.'', ''.$concepto->ref.' - '.$concepto->description.'');
        //$sheet->getRowDimension($fila)->setRowHeight(50);
        $sheet->getStyle('E'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('E'.$fila.'')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($fila)->setRowHeight(50);

        $precio = $concepto->price - (($concepto->price * $concepto->discount) / 100);
        $aDescontar = ($precio * $descuentos) / 100;
        $precio = $precio - $aDescontar;

        $sheet->setCellValue('J'.$fila.'', ''.$precio.'');
        $sheet->getStyle('J'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('J'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);

        $aRestar = ($concepto->taxable_base * $descuentos) / 100;
        $pedido = $concepto->taxable_base - $aRestar;

        $sheet->mergeCells('K'.$fila.':L'.$fila.'');
        $sheet->setCellValue('K'.$fila.'', ''.$pedido.'');
        $sheet->getStyle('K'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('K'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);

        $sheet->setCellValue('M'.$fila.'', ''.$linea->cant_origen.'');
        $sheet->getStyle('M'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('N'.$fila.'', ''.$linea->cant_anterior.'');
        $sheet->getStyle('N'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('O'.$fila.'', ''.$linea->cant_mes.'');
        $sheet->getStyle('O'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('O'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('P'.$fila.':Q'.$fila.'');
        if ($linea->imp_origen == 0) {
            $sheet->setCellValue('P'.$fila.'', '');
        } else {
            $sheet->setCellValue('P'.$fila.'', ''.$linea->imp_origen.'');
            $sheet->getStyle('P'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
        }
        $sheet->getStyle('P'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('P'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        $sheet->mergeCells('R'.$fila.':S'.$fila.'');
        if ($linea->imp_anterior == 0) {
            $sheet->setCellValue('R'.$fila.'', '');
        } else {
            $sheet->setCellValue('R'.$fila.'', ''.$linea->imp_anterior.'');
            $sheet->getStyle('R'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
        }
        $sheet->getStyle('R'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('R'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('T'.$fila.':U'.$fila.'');
        if ($linea->imp_mes == 0) {
            $sheet->setCellValue('T'.$fila.'', '');
        } else {
            $sheet->setCellValue('T'.$fila.'', ''.$linea->imp_mes.'');
            $sheet->getStyle('T'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
        }
        $sheet->getStyle('T'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('T'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('V'.$fila.'', ''.$linea->porcentaje.' %');
        $sheet->getStyle('V'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('V'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $fila++;
        $numLinea++;

    }

    //PARA LOS DATOS TOTALES
    $sheet->mergeCells('A'.$fila.':J'.$fila.'');

    $sheet->mergeCells('K'.$fila.':L'.$fila.'');
    $sheet->setCellValue('K'.$fila.'', ''.$datosCert->pedido.'');
    $sheet->getStyle('K'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('K'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('K'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);

    $sheet->mergeCells('M'.$fila.':O'.$fila.'');

    $sheet->mergeCells('P'.$fila.':Q'.$fila.'');
    if ($datosCert->imp_origen_total == 0) {
        $sheet->setCellValue('P'.$fila.'', '');
    } else {
        $sheet->setCellValue('P'.$fila.'', ''.$datosCert->imp_origen_total.'');
        $sheet->getStyle('P'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
    }
    $sheet->getStyle('P'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('P'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('R'.$fila.':S'.$fila.'');
    if ($datosCert->imp_anterior_total == 0) {
        $sheet->setCellValue('R'.$fila.'', '');
    } else {
        $sheet->setCellValue('R'.$fila.'', ''.$datosCert->imp_anterior_total.'');
        $sheet->getStyle('R'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
    }
    $sheet->getStyle('R'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('R'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('T'.$fila.':U'.$fila.'');
    if ($datosCert->imp_mes_total == 0) {
        $sheet->setCellValue('T'.$fila.'', '');
    } else {
        $sheet->setCellValue('T'.$fila.'', ''.$datosCert->imp_mes_total.'');
        $sheet->getStyle('T'.$fila.'')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
    }
    $sheet->getStyle('T'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('T'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('V'.$fila.'', ''.$datosCert->porcent_total.' %');
    $sheet->getStyle('V'.$fila.'')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('V'.$fila.'')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    // Establecer el estilo de borde
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ];
    // Aplicar el estilo de borde a la tabla
    $spreadsheet->getActiveSheet()->getStyle('A1:V'.$fila.'')->applyFromArray($borderStyle);

    // Configura las cabeceras HTTP
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="certificacion.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

}

?>