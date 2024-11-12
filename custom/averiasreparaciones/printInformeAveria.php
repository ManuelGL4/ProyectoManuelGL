<?php 

require '../../main.inc.php';
require __DIR__ . '/../../vendor/autoload.php';

use Fpdf\Fpdf;

/*const MONTHS = [
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

const PAYMENT_PERIODICITY = [
    '0' => 'Mensual',
    '1' => 'Bimensual',
    '2' => 'Trimestral',
    '3' => 'Semestral',
    '4' => 'Anual',
];*/

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $id = $_GET['id'];

    //Datos generales de la avería
    $sqlAveria = " SELECT a.*, s.nom, s.address, s.zip, s.town, s.fax, s.phone, d.nom as ciudad FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias a ";
    $sqlAveria.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_cliente ";
    $sqlAveria.= " INNER JOIN ".MAIN_DB_PREFIX."c_departements d ON d.rowid = s.fk_departement ";
    $sqlAveria.= " WHERE a.rowid = ".$id;

    $resultAveria = $db->query($sqlAveria);
    $averia = $db->fetch_object($resultAveria);

    $sqlAveriado = " SELECT mp.fk_product, mp.fk_mo, p.ref, p.label FROM ".MAIN_DB_PREFIX."mrp_production mp ";
    $sqlAveriado.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ON en.fk_mo = mp.fk_mo ";
    $sqlAveriado.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mp.fk_product ";
    $sqlAveriado.= " WHERE en.fk_averia = ".$id." AND en.added = 1 AND mp.role = 'toproduce' ";

    $resultAveriado = $db->query($sqlAveriado);
    $numAveriados = $db->num_rows($resultAveriado);

    //PARA EL NÚMERO DE PÁGINAS
    $sqlPaginas = " SELECT rowid FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_equipos ";
    $sqlPaginas.= " WHERE fk_averia = ".$id." ";

    $resultPaginas = $db->query($sqlPaginas);
    $paginasTotales = $db->num_rows($resultPaginas);

    if ($averia->fk_informe != "") {
        //PARA LA GARANTIA
        $sqlContrato = " SELECT contract_id FROM ". MAIN_DB_PREFIX ."mantenimiento_informes mi ";
        $sqlContrato.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias a ON a.fk_informe = mi.rowid ";
        $sqlContrato.= " INNER JOIN ". MAIN_DB_PREFIX ."averiasreparaciones_averias_ofertas ao ON ao.averia = a.rowid ";
        $sqlContrato.= " WHERE a.rowid = ".$id;

        $resultContrato = $db->query($sqlContrato);
        $contrato = $db->fetch_object($resultContrato);
        $contrato = $contrato->contract_id;

        $garantia = "-";
        if ($contrato != "") {

            $sqlGarantia = " SELECT warranty_end FROM ". MAIN_DB_PREFIX ."mantenimiento_contratos ";
            $sqlgarantia = " WHERE rowid = ".$contrato;

            $resultGarantia = $db->query($sqlGarantia);
            $garantia = $db->fetch_object($resultGarantia);
            $garantia = $garantia->warranty_end;
            
            $formateada = DateTime::createFromFormat('Y-m-d H:i:s', $garantia);
            $garantia = $formateada->format('d-m-Y H:i:s');

        }
    }

    $fecha_actual = new DateTime();
    $fecha_actual = $fecha_actual->format('d-m-Y H:i:s');

    $newDate2 = date("d-m-Y", strtotime($averia->fecha_averia));

    $sqlIdPresu = " SELECT fk_oferta FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
    $sqlIdPresu.= " WHERE fk_averia = ".$id." LIMIT 1";
    $resultPresu = $db->query($sqlIdPresu);
    $presu = $db->fetch_object($resultPresu);

    $sqlOferta = " SELECT p.*  FROM ".MAIN_DB_PREFIX."propal p ";
    $sqlOferta.= " WHERE p.rowid = ".$presu->fk_oferta." LIMIT 1";
    $resultOferta = $db->query($sqlOferta);
    $oferta = $db->fetch_object($resultOferta);

    if ($oferta->fk_availability != "") {
        $sqlEntrega = " SELECT label  FROM ".MAIN_DB_PREFIX."c_availability ";
        $sqlEntrega.= " WHERE rowid = ".$oferta->fk_availability." ";
        $resultEntrega = $db->query($sqlEntrega);
        $entrega = $db->fetch_object($resultEntrega);
    }

    if ($oferta->fk_shipping_method != "") {
        $sqlEnvio = " SELECT libelle  FROM ".MAIN_DB_PREFIX."c_shipment_mode ";
        $sqlEnvio.= " WHERE rowid = ".$oferta->fk_shipping_method." ";
        $resultEnvio = $db->query($sqlEnvio);
        $envio = $db->fetch_object($resultEnvio);
    }


    $listaLineas = array();
    $sqlOferta2 = " SELECT * FROM ".MAIN_DB_PREFIX."propaldet ";
    $sqlOferta2.= " WHERE fk_propal = ".$presu->fk_oferta."";
    $resultOferta2 = $db->query($sqlOferta2);
    
    while ($oferta2 = $db->fetch_object($resultOferta2)) {
        $listaLineas[] = $oferta2->total_ttc;
    }

    $newDate = date("d-m-Y", strtotime($oferta->datec));

    //$pages = ceil( ( $db->num_rows($resultProductsIncluded) + 2 ) / 2);
    $pdf = new Fpdf();
    
    $page = 0;

    $k = 0;
    while ($averiado = $db->fetch_object($resultAveriado)) {

        $page++;
        
        $pdf->AddPage();
        
        //Header
        $pdf->Rect(10,9,190,32);

        $pdf->SetFont('Arial','',8);
        $pdf->Image(__DIR__.'/../../documents/mycompany/logos/thumbs/descarga_small.png',11,15,75,20);
        $pdf->SetXY(83,15);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(50,20,utf8_decode('INFORME DE AVERÍA'));
        $pdf->SetXY(173,12);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(100,10,utf8_decode('Página: '.$page.' de '.$paginasTotales.''));
        $pdf->SetXY(143,25);
        $pdf->Cell(100,10,utf8_decode('Nº AVERÍA:'));
        $pdf->SetXY(163,25);
        $pdf->Cell(100,10,utf8_decode($averia->ref));
        $pdf->SetXY(143,31);
        $pdf->Cell(100,10,utf8_decode('FECHA:'));
        $pdf->SetXY(157,31);
        $pdf->Cell(100,10,utf8_decode($newDate2));

        //Cliente
        $pdf->Rect(10,41,190,32);

        $pdf->SetXY(12,46);
        $pdf->Cell(0,0,utf8_decode('CLIENTE:'));
        $pdf->SetXY(30,46);
        $pdf->Cell(0,0,utf8_decode($averia->nom));
        $pdf->SetXY(12,56);
        $pdf->Cell(0,0,utf8_decode('DIRECCIÓN:'));
        $pdf->SetXY(34,56);
        $pdf->Cell(0,0,utf8_decode($averia->address. " - ". $averia->zip. " - ". $averia->ciudad));
        $pdf->SetXY(12,68);
        $pdf->Cell(0,0,utf8_decode('RESPONSABLE:'));
        $pdf->SetXY(40,68);
        $pdf->Cell(0,0,utf8_decode($averia->nom));

        $pdf->SetXY(112,46);
        $pdf->Cell(0,0,utf8_decode('CONTRATO MANTENIMIENTO:'));
        $pdf->Rect(170,44,4,4);
        $pdf->SetXY(174,46);
        $pdf->Cell(0,0,utf8_decode('SÍ'));
        $pdf->Rect(184,44,4,4);
        $pdf->SetXY(188,46);
        $pdf->Cell(0,0,utf8_decode('NO'));

        if ($averia->fk_informe == "") {
            $pdf->SetXY(184,46);
            $pdf->Cell(0,0,utf8_decode('X'));
        } else {
            $pdf->SetXY(170,46);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        $pdf->SetXY(112,53);
        $pdf->Cell(0,0,utf8_decode('FAX:'));
        $pdf->SetXY(122,53);
        $pdf->Cell(0,0,utf8_decode($averia->fax));
        $pdf->SetXY(112,60);
        $pdf->Cell(0,0,utf8_decode('TELÉFONO:'));
        $pdf->SetXY(134,60);
        $pdf->Cell(0,0,utf8_decode($averia->phone));
        $pdf->SetXY(112,68);
        $pdf->Cell(0,0,utf8_decode('MÓVIL:'));


        //Descripción
        $pdf->Rect(10,73,190,32);

        $pdf->SetXY(12,80);
        $pdf->Cell(0,0,utf8_decode('DESCRIPCIÓN AVERÍA:'));
        $pdf->SetXY(53,80);
        $pdf->Cell(0,0,utf8_decode($averia->descripcion));
        $pdf->SetXY(12,90);
        $pdf->Cell(0,0,utf8_decode('DESCRIPCIÓN PROBLEMA (INSTRUCCIONES DEL CLIENTE):'));
        $pdf->SetXY(115,90);
        $pdf->Cell(0,0,utf8_decode($averia->description_problema));

        $pdf->SetXY(12,100);
        $pdf->Cell(0,0,utf8_decode('CONFIRMAR CITA:'));
        $pdf->Rect(62,98,4,4);
        $pdf->SetXY(66,100);
        $pdf->Cell(0,0,utf8_decode('SÍ'));
        $pdf->Rect(78,98,4,4);
        $pdf->SetXY(82,100);
        $pdf->Cell(0,0,utf8_decode('NO'));

        //Control
        $pdf->Rect(10,105,190,18);

        $pdf->SetXY(12,110);
        $pdf->Cell(0,0,utf8_decode('CONTROL:'));

        $pdf->SetXY(52,110);
        $pdf->Cell(0,0,utf8_decode('Revisión para presupuesto (sin reparación)'));
        $pdf->Rect(48,108,4,4);

        $pdf->SetXY(145,110);
        $pdf->Cell(0,0,utf8_decode('Reparación sin presupuesto'));
        $pdf->Rect(141,108,4,4);

        $pdf->SetXY(18,118);
        $pdf->Cell(0,0,utf8_decode('Equipo en cliente'));
        $pdf->Rect(14,116,4,4);

        $pdf->SetXY(78,118);
        $pdf->Cell(0,0,utf8_decode('Equipo en nuestro almacén'));
        $pdf->Rect(74,116,4,4);

        $pdf->SetXY(152,118);
        $pdf->Cell(0,0,utf8_decode('Reparación externa'));
        $pdf->Rect(148,116,4,4);

        if ($averia->control == 0) {
            $pdf->SetXY(14,118);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        if ($averia->control == 1) {
            $pdf->SetXY(141,110);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        if ($averia->control == 2) {
            $pdf->SetXY(148,118);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        if ($averia->control == 3) {
            $pdf->SetXY(74,118);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        if ($averia->control == 4) {
            $pdf->SetXY(48,110);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        //Averiado
        $pdf->Rect(10,123,190,32);

        /*$listaAveriados = "";
        //Concatenacion de los equipos averiados
        while ($averiado = $db->fetch_object($resultAveriado)) {
            if ($listaAveriados == "") {
                $listaAveriados = $averiado->ref;
            } else {
                $listaAveriados.= ", ".$averiado->ref;
            }
        }*/

        $pdf->SetXY(12,128);
        $pdf->Cell(0,0,utf8_decode('EQUIPO(S) AVERIADO(S):'));
        $pdf->SetXY(56,126);
        $pdf->MultiCell(100,5,utf8_decode($averiado->ref));


        $pdf->SetXY(12,138);
        $pdf->Cell(0,0,utf8_decode('INDICACIONES DEL CLIENTE:'));
        $pdf->SetXY(12,148);
        $pdf->Cell(0,0,utf8_decode('FORMA DE ENVÍO:'));
        $pdf->SetXY(45,148);
        $pdf->Cell(0,0,utf8_decode($envio->libelle));

        $pdf->SetXY(126,128);
        $pdf->Cell(0,0,utf8_decode('EN GARANTÍA:'));
        $pdf->Rect(158,126,4,4);

        $pdf->SetXY(162,128);
        $pdf->Cell(0,0,utf8_decode('SÍ'));
        $pdf->Rect(172,126,4,4);
        $pdf->SetXY(176,128);
        $pdf->Cell(0,0,utf8_decode('NO'));

        if ($garantia > $fecha_actual) {
            $pdf->SetXY(158,128);
            $pdf->Cell(0,0,utf8_decode('X'));
        } else {
            $pdf->SetXY(172,128);
            $pdf->Cell(0,0,utf8_decode('X'));
        }

        //DIAGNÓSTICO
        $pdf->Rect(10,155,190,72);

        $sqlSustitucion = " SELECT mp.fk_product, mp.fk_mo, p.ref, p.label, en.gasto_actual, gasto_teorico, en.dto FROM ".MAIN_DB_PREFIX."mrp_production mp ";
        $sqlSustitucion.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ON en.fk_mo = mp.fk_mo ";
        $sqlSustitucion.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mp.fk_product ";
        $sqlSustitucion.= " WHERE en.fk_averia = ".$id." AND en.added = 1 AND mp.role = 'toproduce' AND mp.fk_mo = ".$averiado->fk_mo." ";

        $resultSustitucion = $db->query($sqlSustitucion);
        $numSustituidos = $db->num_rows($resultSustitucion);

        //Para dividir en dos
        $pdf->Rect(10,155,95,72);

        $pdf->SetXY(12,160);
        $pdf->Cell(0,0,utf8_decode('DIAGNÓSTICO:'));
        $pdf->SetXY(12,175);
        $pdf->Cell(0,0,utf8_decode('FECHA:'));
        $pdf->SetXY(20,180);
        $pdf->Cell(0,0,utf8_decode('ELEMENTOS A SUSTITUIR O REPARAR'));

        $contador = 0;
        $descuento = 0;
        $puesto = 0;
        $y = 180;
        while ($sust = $db->fetch_object($resultSustitucion)) {
            if ($contador <= 5) {
                $x = 25;
                $pdf->SetXY($x,$y);
                $pdf->Cell(10,10,utf8_decode($sust->ref));

                if ($puesto == 0) {
                    $x = 125;
                    $pdf->SetXY($x,$y);

                    if ($sust->dto != "") {
                        $descuento = ($sust->gasto_teorico * $sust->dto) / 100;
                    }

                    $pdf->Cell(10,10,utf8_decode(strtr(number_format($sust->gasto_actual,2),['.' => ',', ',' => '.'])));

                    $x = 168;
                    $pdf->SetXY($x,$y);
                    if ($k < count($listaLineas)) {
                        $pdf->Cell(10,10,utf8_decode(strtr(number_format($listaLineas[$k],2),['.' => ',', ',' => '.'])));
                    }
                    //$x+= 15;
                }
                $y+= 8;
                $puesto = 1;
            } else {
                if ($contador == 6) {
                    $y = 180;
                }
                $x = 68;
                $pdf->SetXY($x,$y);
                $pdf->Cell(10,10,utf8_decode($sust->ref));
                //$x+= 15;
                $y+= 8;
            }
            $contador++;
        }

        /*$sqlOferta = " SELECT ao.*, aod.* FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ao ";
        $sqlOferta.= " INNER JOIN ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_datos aod ON aod.fk_oferta = ao.rowid ";
        $sqlOferta.= " WHERE ao.averia = ".$id;*/

        $pdf->SetXY(108,160);
        $pdf->Cell(0,0,utf8_decode('Nº OFERTA ASOCIADA:'));
        $pdf->SetXY(150,160);
        $pdf->Cell(0,0,utf8_decode($oferta->ref));
        $pdf->SetXY(108,165);
        $pdf->Cell(0,0,utf8_decode('FECHA PRESUPUESTO:'));
        $pdf->SetXY(150,165);
        $pdf->Cell(0,0,utf8_decode($newDate));
        $pdf->SetXY(108,170);
        $pdf->Cell(0,0,utf8_decode('VALORACIÓN TOTAL FINAL:'));
        $pdf->SetXY(158,170);
        $pdf->Cell(0,0,utf8_decode(strtr(number_format($oferta->total_ttc,2),['.' => ',', ',' => '.'])));
        $pdf->SetXY(108,179);
        $pdf->Cell(0,0,utf8_decode('VALORACIÓN INICIAL (OT)'));
        $pdf->SetXY(160,179);
        $pdf->Cell(0,0,utf8_decode('VALORACIÓN FINAL'));

        //Para las líneas de sustituciones
        $pdf->Rect(10,182,190,7);
        $pdf->Rect(10,189,190,7);
        $pdf->Rect(10,196,190,7);
        $pdf->Rect(10,203,190,7);
        $pdf->Rect(10,210,190,9);

        //TIEMPO DE TRABAJO Y DESPLAZAMIENTO
        if ($numAveriados == $page) {

            $pdf->Rect(10,227,190,8);
            $pdf->Rect(10,227,95,8);
            $pdf->SetXY(12,231);
            $pdf->Cell(0,0,utf8_decode('TIEMPO DE TRABAJO PREVISTO:'));
            $pdf->SetXY(72,231);
            $pdf->Cell(0,0,utf8_decode($averia->tiempo_estimado));
            $pdf->SetXY(108,231);
            $pdf->Cell(0,0,utf8_decode('TIEMPO DE DESPLAZAMIENTO:'));

            //Plazo de reparación
            $pdf->Rect(10,235,190,8);

            //$entrega = date("d-m-Y", strtotime($oferta->date_livraison));

            $pdf->SetXY(12,240);
            $pdf->Cell(0,0,utf8_decode('PLAZO DE REPARACIÓN A INDICAR EN LA OFERTA:'));
            $pdf->SetXY(120,240);
            $pdf->Cell(0,0,utf8_decode($entrega->label));

            //Operaciones a realizar
            $pdf->Rect(10,243,190,18);

            $pdf->SetXY(12,248);
            $pdf->Cell(0,0,utf8_decode('OPERACIONES, COMPROBACIONES DE MÁS A REALIZAR:'));

            //OBSERVACIONES
            $pdf->Rect(10,261,190,20);

            //Para dividir en dos
            $pdf->Rect(10,261,95,20);

            $pdf->SetXY(12,267);
            $pdf->Cell(0,0,utf8_decode('OBSERVACIONES:'));
            $pdf->SetXY(12,269);
            $pdf->MultiCell(90,3,utf8_decode($oferta->observaciones));
            $pdf->SetFont('Arial','',7);
            $pdf->SetXY(108,273);
            $pdf->Cell(0,0,utf8_decode('Fdo:'));
            $pdf->SetXY(108,276);
            $pdf->Cell(0,0,utf8_decode('RESPONSABLE DE MANTENIMIENTO'));

        }
        $k++;

    }

    $name = 'Informe_Averia_'.$id.'.pdf';

    $pdf->Output('I',$name,true);

}

?>