<?php 

require '../../main.inc.php';
require __DIR__ . '/../../vendor/autoload.php';

use Fpdf\Fpdf;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $id = $_GET["id"];

    $type = $_GET["type"];

    $sqlHeader = " SELECT mc.rowid as contrato, mc.name, mc.order_number, mc.periodicity, DATE(mc.date_end) as date_end, DATE(mi.real_date) as real_date, DATE(mi.maintenance_date) as maintenance_date, mi.last_technician_id,"; 
    $sqlHeader.= " CONCAT(u.firstname,' ', u.lastname) as full_name"; 
    $sqlHeader.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi"; 
    $sqlHeader.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mc.rowid = mi.contract_id";
    $sqlHeader.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = mi.technician_id";
    $sqlHeader.= " WHERE mi.rowid = ".$id;

    $resultHeader = $db->query($sqlHeader);

    $dataHeader = $db->fetch_object($resultHeader);

	if ($dataHeader->last_technician_id != "") {
		$sqlUltTecnico = " SELECT CONCAT(firstname,' ',lastname) as lt_full_name FROM ".MAIN_DB_PREFIX."user ";
		$sqlUltTecnico.= " WHERE rowid = ".$dataHeader->last_technician_id." ";
		
		$resultUltTecnico = $db->query($sqlUltTecnico);                                                                             
    	$dataTecnico = $db->fetch_object($resultUltTecnico);
	}

    $sqlFutureReplacements = " SELECT p.ref, p.description, p.label, mis.quantity"; 
    $sqlFutureReplacements.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis"; 
    $sqlFutureReplacements.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product";
    $sqlFutureReplacements.= " WHERE mis.is_future = 1 AND mis.fk_report = ".$id;
    
    $resultFutureReplacements = $db->query($sqlFutureReplacements);

    $futureReplacements = "";

    while ($dataFutureReplacements = $db->fetch_object($resultFutureReplacements)) {
        
        $futureReplacements.= ' ( '.$dataFutureReplacements->quantity.' ) - '.$dataFutureReplacements->ref.' - '.$dataFutureReplacements->label.'  ';
    }

    $sqlRetired = " SELECT p.ref, p.description, p.label, mis.quantity, mis.id_fase_khonos as observ"; 
    $sqlRetired.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis"; 
    $sqlRetired.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product";
    $sqlRetired.= " WHERE mis.is_retired = 1 AND mis.fk_report = ".$id;
    
    $resultRetired = $db->query($sqlRetired);

    $retired = "";

    while ($dataRetired = $db->fetch_object($resultRetired)) {
        
        if ($retired != "") {
            $retired.= ", ";
        }

        $retired.= '( '.$dataRetired->quantity.' ) - '.$dataRetired->ref.' - '.$dataRetired->label.' ['.$dataRetired->observ.']';
    }

    /*$sqlReplacements = " SELECT pr.ref as prod_root_ref, pr.description as prod_root_des, ";
    $sqlReplacements.= " mie.remarks, mie.future_remarks, mie.location, mie.failure, mie.repairs";
    $sqlReplacements.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi";
    $sqlReplacements.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ON mie.fk_report = mi.rowid";
    $sqlReplacements.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ON mis.fk_report = mi.rowid";
    $sqlReplacements.= " INNER JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = mis.fk_product_root";
    $sqlReplacements.= " LEFT JOIN ".MAIN_DB_PREFIX."product pc ON pc.rowid = mis.fk_product";
    $sqlReplacements.= " WHERE mis.is_future = 0 AND mie.fk_product = mis.fk_product_root AND mis.fk_report = ".$id;*/

    $sqlReplacements = "SELECT p.rowid as prod_root_rowid, p.ref as prod_root_ref, p.description, p.label as prod_root_des, p.label, ";
    $sqlReplacements.= " mie.rowid as idequi, mie.remarks, mie.future_remarks, mie.location, mie.failure, mie.repairs ";
    $sqlReplacements.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_equipos mie ON mi.rowid = mie.fk_report ";
    $sqlReplacements.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mie.fk_product WHERE mi.rowid = ".$id;

    $resultReplacements = $db->query($sqlReplacements);

    //print $sqlReplacements;

    $pages = $db->num_rows($resultReplacements);

    $sqlSustituciones = " SELECT p.ref, p.label, p.description, mis.quantity ";
    $sqlSustituciones.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mis.fk_report ";
    $sqlSustituciones.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mis.fk_product ";
    $sqlSustituciones.= " WHERE mi.rowid = 1 AND is_future = 0 AND is_retired = 0 AND is_returned = 0";

    $resultSustituciones = $db->query($sqlSustituciones);

    $listaSustituciones = [];

    while ($sust = $db->fetch_object($resultSustituciones)) {
        $listaSustituciones [] = $sust->ref;
        $listaSustituciones [] = $sust->label;
        $listaSustituciones [] = $sust->description;
        $listaSustituciones [] = $sust->quantity;
    }

    $documentType = '';
    
    switch ($Header->periodicity) {
        case '1':
            $documentType = '';
        break;
        case '2':
            $documentType = '';
        break;
        case '3':
            $documentType = '';
        break;
        case '4':
            $documentType = '';
        break;
        
        default:
            
        break;
    }

    $pdf = new Fpdf();
    $page = 1;

    //PARA SACAR LOS NÚMEROS DE SERIE DE LOS PRODUCTOS (SE BUSCA EN CONTRATOS EQUIPOS)
    $listaSeries = array();

    $sqlSeries = " SELECT num_serie, fk_product FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos ";
    $sqlSeries.= " WHERE fk_contract = ".$dataHeader->contrato." ";

    $resultSeries = $db->query($sqlSeries);
    $contSeries = 0;
    while ($serie = $db->fetch_object($resultSeries)) {
        $listaSeries[] = $serie->fk_product;
        $listaSeries[] = $serie->num_serie;
        $contSeries++;
    }

    $k = 0;
    while ($dataReplacements = $db->fetch_object($resultReplacements)) {

        $quantities = explode(",",$dataReplacements->quantity);
        $refs = explode(",",$dataReplacements->prod_ref);
        $descriptions = explode(",",$dataReplacements->label);
        $serviceDescriptions = explode(",",$dataReplacements->service_description);
        
        for ($i=0; $i < count($quantities) ; $i++) {
            
            $description = ($descriptions[$i] == "") ? $serviceDescriptions[$i] : $descriptions[$i];
            $ref = ($refs[$i] == "") ? "Servicio" : $refs[$i];
            $replacements.= '('.$quantities[$i].') - '.$ref.' - '.$description.'   ';
        }

        $rootProduct = $dataReplacements->prod_root_ref. " - ".$dataReplacements->prod_root_des;

        $piezas = "-";
        if (($dataReplacements->failure == "") || ($dataReplacements->failure == NULL)) {
            $piezas = "-";
        } else {
            /*$sqlPiezas = " SELECT ref, label FROM ".MAIN_DB_PREFIX."product ";
            $sqlPiezas.= " WHERE rowid = ".$dataReplacements->failure;*/

            $sqlPiezas = " SELECT DISTINCT mis.fk_product_root, p.rowid, p.ref, p.label, mis.quantity, mis.is_future, mis.is_returned, mis.is_retired FROM ".MAIN_DB_PREFIX."product p ";
            $sqlPiezas.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ON mis.fk_product = p.rowid ";
            $sqlPiezas.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mis.fk_report ";
            $sqlPiezas.= " WHERE mi.rowid = ".$id." AND mis.fk_product_root = ".$dataReplacements->prod_root_rowid." AND mis.is_future = 0 AND mis.is_retired = 0 AND mis.is_returned = 0 AND p.rowid IN (".$dataReplacements->failure.") ORDER BY FIELD (p.rowid, ".$dataReplacements->failure.")";
    
            //print "CONSULTA: ".$sqlPiezas;
            $resultado = $db->query($sqlPiezas);
            
            while ($producto = $db->fetch_object($resultado)) {
                if ($piezas == "-") {
                    $piezas = "(".$producto->quantity.") - ". $producto->ref." - ".$producto->label;
                } else {
                    $piezas.= ", (".$producto->quantity.") - ".$producto->ref." - ".$producto->label;
                }
                
            }
    
        }

        $reparaciones = "-";
        if (($dataReplacements->repairs == "") || ($dataReplacements->repairs == NULL)) {
            $reparaciones = "-";
        } else {
            /*$sqlReparaciones = " SELECT ref, label FROM ".MAIN_DB_PREFIX."product ";
            $sqlReparaciones.= " WHERE rowid = ".$dataReplacements->repairs;*/

            $sqlReparaciones = " SELECT DISTINCT mis.fk_product_root, p.rowid, p.ref, p.label, mis.quantity, mis.is_future, mis.is_returned, mis.is_retired FROM ".MAIN_DB_PREFIX."product p";
            $sqlReparaciones.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ON mis.fk_product = p.rowid ";
            $sqlReparaciones.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mis.fk_report ";
            $sqlReparaciones.= " WHERE mi.rowid = ".$id." AND mis.fk_product_root = ".$dataReplacements->prod_root_rowid." AND mis.is_future = 0 AND mis.is_retired = 0 AND mis.is_returned = 0 AND p.rowid IN (".$dataReplacements->repairs.") ORDER BY FIELD (p.rowid, ".$dataReplacements->repairs.")";

            $resultado = $db->query($sqlReparaciones);

            while ($servicio = $db->fetch_object($resultado)) {
                if ($reparaciones == "-") {
                    $reparaciones = "(".$servicio->quantity.") - ".$servicio->ref." - ".$servicio->label;
                } else {
                    $reparaciones.= ", (".$servicio->quantity.") - ".$servicio->ref." - ".$servicio->label;
                }
                
            }
        }



        $afuturo = "-";
        if (($dataReplacements->failure == "") && ($dataReplacements->repairs == "")) {
            $afuturo = "-";
            //print "ENTRA AQUI";
            //die;
        } else {
            /*$sqlReparaciones = " SELECT ref, label FROM ".MAIN_DB_PREFIX."product ";
            $sqlReparaciones.= " WHERE rowid = ".$dataReplacements->repairs;*/

            $sqlAFuturo = " SELECT DISTINCT mis.fk_product_root, mis.id_fase_khonos, p.rowid, p.ref, p.label, mis.quantity, mis.is_future, mis.is_returned, mis.is_retired FROM ".MAIN_DB_PREFIX."product p";
            $sqlAFuturo.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis ON mis.fk_product = p.rowid ";
            $sqlAFuturo.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mi.rowid = mis.fk_report ";
            $sqlAFuturo.= " WHERE mi.rowid = ".$id." AND mis.fk_product_root = ".$dataReplacements->prod_root_rowid." AND mis.is_future = 1 AND mis.is_retired = 0 AND mis.is_returned = 0 ";

            //print $sqlAFuturo;
            //die;
            $resultado = $db->query($sqlAFuturo);

            while ($profuturo = $db->fetch_object($resultado)) {
                $id_fase_khonos = intval($profuturo->id_fase_khonos);

                if ($id_fase_khonos == $dataReplacements->idequi) {

                    if ($afuturo == "-") {
                        $afuturo = "(".$profuturo->quantity.") - ".$profuturo->ref." - ".$profuturo->label;
                    } else {
                        $afuturo.= ", (".$profuturo->quantity.") - ".$profuturo->ref." - ".$profuturo->label;
                    }

                }
                
            }
        }



        if (($dataReplacements->location == "") || ($dataReplacements->location == NULL)) {
            $location = "-";
        } else {
            $location = $dataReplacements->location;
        }

        if (($dataReplacements->remarks == "") || ($dataReplacements->remarks == NULL)) {
            $observ = "-";
        } else {
            $observ = $dataReplacements->remarks;
        }

        if (($dataReplacements->future_remarks == "") || ($dataReplacements->future_remarks == NULL)) {
            $observFut = "-";
        } else {
            $observFut = $dataReplacements->future_remarks;
        }

        /*$sqlReparaciones = " SELECT ref, label FROM ".MAIN_DB_PREFIX."product ";
        $sqlReparaciones.= " WHERE rowid = ".$dataReplacements->repairs;

        $resultado = $db->query($sqlReparaciones);
        $servicio = $db->fetch_object($resultado);*/
        
        $pdf->AddPage();
        //Header
        $pdf->SetFont('Arial','B',16);
        $pdf->Image(__DIR__.'/../../documents/mycompany/logos/thumbs/descarga_small.png',10,10,75,20);
    
        $pdf->SetXY(100,20);
        $pdf->Cell(10,10,'INFORME DE MANTENIMIENTO');
    
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(10,40);
        $pdf->Cell(10,10,utf8_decode('Nº Contrato: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(36,40);
        $pdf->Cell(10,10,utf8_decode($dataHeader->order_number));
    
		$fechaVen = DateTime::createFromFormat('Y-m-d', $dataHeader->date_end);
		$fechaVen = $fechaVen->format('d-m-Y');
		
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(90,40);
        $pdf->Cell(10,10,'Fecha Vencimiento: ');
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(132,40);
        $pdf->Cell(10,10,utf8_decode($fechaVen));
    
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(170,40);
        $pdf->Cell(10,10,utf8_decode('Página: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(186,40);
        $pdf->Cell(10,10,utf8_decode($page.'/'.$pages));
    
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(10,50);
        $pdf->Cell(10,10,utf8_decode('Denominación: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(42,50);
        $pdf->Cell(10,10,utf8_decode($dataHeader->name));
    
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(10,60);
        $pdf->Cell(10,10,utf8_decode('Técnico ult. intervención: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(63,60);
        $pdf->Cell(10,10,utf8_decode($dataTecnico->lt_full_name));
    
		$fechaMant = DateTime::createFromFormat('Y-m-d', $dataHeader->maintenance_date);
		$fechaMant = $fechaMant->format('d-m-Y');
		
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(10,70);
        $pdf->Cell(10,10,utf8_decode('Fecha última intervención: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(65,70);
        $pdf->Cell(10,10,utf8_decode($fechaMant));
    
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(10,80);
        $pdf->Cell(10,10,utf8_decode('Técnico: '));
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(28,80);
        $pdf->Cell(10,10,utf8_decode($dataHeader->full_name));
    
        
        if ($type == "intern") {
            
            $pdf->SetFont('Arial','B',12);
            $pdf->SetXY(10,90);
            $pdf->Cell(10,10,utf8_decode('Materiales a llevar a la intervención: '));
            
            $pdf->SetXY(83,92);
            $pdf->SetFont('Arial','',8);
            $pdf->Write(5,utf8_decode($futureReplacements));

            $pdf->SetFont('Arial','B',12);
            $pdf->SetXY(10,103);
            $pdf->Cell(10,10,utf8_decode('Materiales retirados: '));
            
            $pdf->SetXY(52,105);
            $pdf->SetFont('Arial','',8);
            $pdf->Write(5,utf8_decode($retired));
        }

        //Body
		$fechaReal = DateTime::createFromFormat('Y-m-d', $dataHeader->real_date);
		$fechaReal = $fechaReal->format('d-m-Y');
		
        $pdf->Rect(5,120,200,120);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,120);
        $pdf->Cell(10,10,utf8_decode('Fecha: '));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(23,120);
        $pdf->Cell(10,10,utf8_decode($fechaReal));
        
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(10,130);

        if ($listaSeries[$k] == $dataReplacements->prod_root_rowid) {
            $pdf->Cell(10,10,utf8_decode('Material: '.$rootProduct.' Nº serie/lote: '.$listaSeries[$k+1].''));
        } else {
            $pdf->Cell(10,10,utf8_decode('Material: '.$rootProduct.''));
        }

        //$dataReplacements->prod_root_rowid
        $pdf->SetXY(10,140);
        $pdf->Cell(10,10,utf8_decode('Ubicación: '.$location));
    
        $pdf->SetXY(10,150);
        $pdf->Cell(10,10,utf8_decode('Piezas: '.$piezas));
    
        $pdf->SetXY(10,160);
        $pdf->Cell(10,10,utf8_decode('Reparaciones: '.$reparaciones));
        
        $pdf->Rect(10,170,190,20);
        $pdf->SetXY(10,170);
        $pdf->Cell(10,10,utf8_decode('Observaciones: '));

        $pdf->SetXY(37,172.5);
        $pdf->Write(5,utf8_decode($observ));
        
        
        if ($type == "intern") {
            
            $pdf->Rect(10,190,190,20);
            $pdf->SetXY(10,190);
            $pdf->Cell(10,10,utf8_decode('Observaciones siguiente visita: '));
            
            $pdf->SetXY(60,192.5);
            $pdf->Write(5,utf8_decode($observFut));

        }

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(10,212);
        $pdf->Cell(10,10,utf8_decode('Materiales a futuro: '));
        
        $pdf->SetXY(42,214);
        $pdf->SetFont('Arial','',10);
        $pdf->Write(5,utf8_decode($afuturo));


        $page++;
        $k+=2;
    }

    $name = ($type == "intern") ? 'Reporte_'.$id.'_interno.pdf' : 'Reporte_'.$id.'_externo.pdf';

    $pdf->Output('I',$name,true);

}

?>