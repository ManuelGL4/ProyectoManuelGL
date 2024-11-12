<?php

    require '../../main.inc.php';
    require __DIR__ . '/../../vendor/autoload.php';

    use Fpdf\Fpdf;
    

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        
        $id = $_GET["id"];
        

        //Cuadrado izquierdo
        //INFO GENERAL DE LA OFERTA
        $sqlOferta = " SELECT a.*, ad.* FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas a ";
        $sqlOferta.= " INNER JOIN ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas_datos ad ON ad.fk_oferta = a.rowid ";
        $sqlOferta.= " WHERE a.rowid = ".$id;
        
        $resultOferta = $db->query($sqlOferta);
        $oferta = $db->fetch_object($resultOferta);

        //INFO DEL CLIENTE
        $sqlCliente = " SELECT s.*, se.libre_1 FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas a ";
        $sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.cliente ";
        $sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = a.cliente ";
        $sqlCliente.= " WHERE a.rowid = ".$id;

        $resultCliente = $db->query($sqlCliente);
        $datosCliente = $db->fetch_object($resultCliente);

        //INFO DEL DEPARTAMENTO Y EL PAIS
        $sqlCliente2 = " SELECT d.nom, c.label FROM ".MAIN_DB_PREFIX."c_departements d ";
        $sqlCliente2.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.fk_departement = d.rowid ";
        $sqlCliente2.= " INNER JOIN ".MAIN_DB_PREFIX."c_country c ON c.rowid = s.fk_pays ";
        $sqlCliente2.= " WHERE d.rowid = ".$datosCliente->fk_departement;

        $resultCliente2 = $db->query($sqlCliente2);
        $datosCliente2 = $db->fetch_object($resultCliente2);

        //INFORMACIÓN DE LA DELEGACIÓN
        $sqlDele = " SELECT d.*, de.nom FROM ".MAIN_DB_PREFIX."delegacion d ";
        $sqlDele.= " INNER JOIN ".MAIN_DB_PREFIX."c_departements de ON de.rowid = d.provincia ";
        $sqlDele.= " WHERE d.id = ".$oferta->delegacion;

        $resultDele = $db->query($sqlDele);
        $dele = $db->fetch_object($resultDele);

        //INFO DE LO QUE INCLUYE LA OFERTA
        if ($oferta->averia != "") {

            $sqlDatos = " SELECT en.*, p.ref as refpro, p.label, mo.ref, mo.label as labelmo, mo.qty as qtymo ";
            $sqlDatos.= " FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo mo ON mo.rowid = en.fk_mo ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_production mop ON mop.fk_mo = mo.rowid ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mop.fk_product ";
            $sqlDatos.= " WHERE en.fk_averia = ".$oferta->averia." AND mop.role = 'toproduce' AND en.added = 1 ";
    
            $resultDatos = $db->query($sqlDatos);
            $numEquipos = $db->num_rows($resultDatos);
    
        } else {
    
            $sqlDatos = " SELECT en.*, p.ref as refpro, p.label, mo.ref, mo.label as labelmo, mo.qty as qtymo ";
            $sqlDatos.= " FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new en ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_mo mo ON mo.rowid = en.fk_mo ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."mrp_production mop ON mop.fk_mo = mo.rowid ";
            $sqlDatos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mop.fk_product ";
            $sqlDatos.= " WHERE en.fk_averia IS NULL AND mo.fk_project = ".$oferta->obra." AND mop.role = 'toproduce' ";
    
            $resultDatos = $db->query($sqlDatos);
            $numEquipos = $db->num_rows($resultDatos);
    
        }


        //print $sqlLineasProductos;
        $paginas = 0;
        $totalPaginas = 1 + ceil($numEquipos / 3);

        if ($numEquipos % 3 == 0) {
            $totalPaginas++;
        }

        $pdf = new Fpdf();

        $pdf->AddPage();
        $paginas++;
        
        //Header
        $pdf->SetFont('Arial','',10);
        $pdf->Image(__DIR__.'/../../documents/mycompany/logos/thumbs/descarga_small.png',10,10,75,20);
    
        $pdf->SetXY(150,10);
        $pdf->Cell(10,10,utf8_decode('c/ Teide, 4'));
        $pdf->SetXY(130,15);
        $pdf->Cell(10,10,utf8_decode('28703 San Sebastian de los Reyes'));
        $pdf->SetXY(150,20);
        $pdf->Cell(10,10,utf8_decode('Madrid'));
        $pdf->SetXY(135,25);
        $pdf->Cell(10,10,utf8_decode('Teléfono: (+34) 915 791 606'));
        $pdf->SetXY(140,30);
        $pdf->Cell(10,10,utf8_decode('e-mail: ortrat@ortrat.es'));

        //Cuadrado izquierdo
        $pdf->Rect(10,40,100,45);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,42);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->nom));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(12,55);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->address));

        $pdf->SetFont('Arial','BU',10);
        $pdf->SetXY(12,59);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." ".$datosCliente2->nom));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,73);
        $pdf->MultiCell(95,4,utf8_decode('C.I.F: '.$datosCliente->siren));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,77);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->code_client));

        //Cuadrado derecho
        $pdf->Rect(120,40,80,45);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,45);
        $pdf->MultiCell(95,4,utf8_decode($dele->direccion));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,55);
        $pdf->MultiCell(95,4,utf8_decode($dele->localidad));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,50);
        $pdf->MultiCell(95,4,utf8_decode($dele->cp." ".$dele->nom));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,65);
        $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,70);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,75);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef2));

        //Datos intermedios
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,86);
        $pdf->Cell(10,10,utf8_decode('Ref. Oferta:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(32,86);
        $pdf->Cell(10,10,utf8_decode($oferta->ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,94);
        $pdf->Cell(10,10,utf8_decode('Oferta:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,94);
        $pdf->Cell(10,10,utf8_decode($oferta->nombre));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,86);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,86);
        $pdf->Cell(10,10,utf8_decode($oferta->fecha_oferta));

        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(85,105);
        $pdf->Cell(10,10,utf8_decode('OFERTA:'));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(182,276);
        $pdf->Cell(0,0,utf8_decode('Pagina '.$paginas.' de '.$totalPaginas.''));

        //Productos
        $pdf->Rect(10,118,190,155);

        //Cabecera productos
        $pdf->Rect(10,118,190,6);

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,116);
        $pdf->Cell(10,10,utf8_decode('UDS.'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(60,116);
        $pdf->Cell(10,10,utf8_decode('DESCRIPCIÓN'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(132,116);
        $pdf->Cell(10,10,utf8_decode('PRECIO'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(152,116);
        $pdf->Cell(10,10,utf8_decode('DTO.'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(166,116);
        $pdf->Cell(10,10,utf8_decode('SUBTOTAL (EUR)'));

        //Columna UDS
        $pdf->Rect(10,118,14,155);

        //Columna DESCRIPCIÓN
        $pdf->Rect(10,118,120,155);

        //Columna PRECIO
        $pdf->Rect(10,118,140,155);

        //Columna DTO
        $pdf->Rect(10,118,154,155);

        $y = 130;
        $contador = 0;
        $hecho = 0;
        while ($lineas = $db->fetch_object($resultDatos)) {

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(15,$y);
            $pdf->Cell(0,0,utf8_decode($lineas->qtymo));

            $pdf->SetXY(26,$y-2);
            $pdf->MultiCell(100,4,utf8_decode($lineas->ref." - ".$lineas->labelmo));

            $pdf->SetXY(132,$y-2);
            $pdf->Cell(0,4,utf8_decode(strtr(number_format($lineas->gasto_actual,2),['.' => ',', ',' => '.'])));

            $pdf->SetXY(153,$y-2);
            $pdf->Cell(0,4,utf8_decode($lineas->dto." %"));

            if ($lineas->dto == "") {
                $lineas->dto = 0;
            }

            $descuento = ($lineas->gasto_actual * $lineas->dto) / 100;

            $pdf->SetXY(183,$y-2);
            $pdf->Cell(0,4,utf8_decode(strtr(number_format($lineas->gasto_actual - $descuento,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

            $y+=20;

        }

        //LA PAGINA FINAL ------------------------------------------------------------------------------------------------------------>
        $pdf->AddPage();
        $paginas++;

        //Header
        $pdf->SetFont('Arial','',10);
        $pdf->Image(__DIR__.'/../../documents/mycompany/logos/thumbs/descarga_small.png',10,10,75,20);
    
        $pdf->SetXY(150,10);
        $pdf->Cell(10,10,utf8_decode('c/ Teide, 4'));
        $pdf->SetXY(130,15);
        $pdf->Cell(10,10,utf8_decode('28703 San Sebastian de los Reyes'));
        $pdf->SetXY(150,20);
        $pdf->Cell(10,10,utf8_decode('Madrid'));
        $pdf->SetXY(135,25);
        $pdf->Cell(10,10,utf8_decode('Teléfono: (+34) 915 791 606'));
        $pdf->SetXY(140,30);
        $pdf->Cell(10,10,utf8_decode('e-mail: ortrat@ortrat.es'));
    
        //Cuadrado izquierdo
        $pdf->Rect(10,40,100,45);

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,42);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->nom));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(12,55);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->address));

        $pdf->SetFont('Arial','BU',10);
        $pdf->SetXY(12,59);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." ".$datosCliente2->nom));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,73);
        $pdf->MultiCell(95,4,utf8_decode('C.I.F: '.$datosCliente->siren));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(12,77);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->code_client));

        //Cuadrado derecho
        $pdf->Rect(120,40,80,45);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,45);
        $pdf->MultiCell(95,4,utf8_decode($dele->direccion));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,55);
        $pdf->MultiCell(95,4,utf8_decode($dele->localidad));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,50);
        $pdf->MultiCell(95,4,utf8_decode($dele->cp." ".$dele->nom));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,65);
        $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,70);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,75);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef2));

        //Datos intermedios
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,86);
        $pdf->Cell(10,10,utf8_decode('Ref. Oferta:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(32,86);
        $pdf->Cell(10,10,utf8_decode($oferta->ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,94);
        $pdf->Cell(10,10,utf8_decode('Oferta:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,94);
        $pdf->Cell(10,10,utf8_decode($oferta->nombre));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,86);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,86);
        $pdf->Cell(10,10,utf8_decode($oferta->fecha_oferta));

        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(80,105);
        $pdf->Cell(10,10,utf8_decode('DATOS DE LA OFERTA:'));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(182,276);
        $pdf->Cell(0,0,utf8_decode('Pagina '.$paginas.' de '.$totalPaginas.''));

        //Productos

        //Cabecera productos
        $pdf->Rect(10,118,190,6);

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(20,116);
        $pdf->Cell(10,10,utf8_decode('SUMA'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(45,116);
        $pdf->Cell(10,10,utf8_decode('TOTAL DTOS'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(78,116);
        $pdf->Cell(10,10,utf8_decode('BASE IMP.'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(114,116);
        $pdf->Cell(10,10,utf8_decode('IVA'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(166,116);
        $pdf->Cell(10,10,utf8_decode('TOTAL IMPORTE'));

        //Columna SUMA
        $pdf->Rect(10,118,33,20);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(18,126);
        $pdf->Cell(10,10,utf8_decode(strtr(number_format($oferta->base_imponible,2),['.' => ',', ',' => '.'])));

        //Columna TOTAL DTOS
        $pdf->Rect(10,118,63,20);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(52,126);

        $dto_cliente = number_format($oferta->dto_cliente,2);
        $dto_oferta = number_format($oferta->dto_oferta,2);

        $dto_total = $oferta->dto_pp + $dto_cliente + $dto_oferta;

        $pdf->Cell(10,10,utf8_decode(strtr(number_format($dto_total,2),['.' => ',', ',' => '.'])));

        //Columna BASE IMP.
        $pdf->Rect(10,118,93,20);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(82,126);

        $baseImp = number_format($oferta->base_imponible,2);
        $base = $baseImp - $dto_total;

        $pdf->Cell(10,10,utf8_decode(strtr($base,['.' => ',', ',' => '.'])));

        //Columna IVA
        $pdf->Rect(10,118,123,20);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(113,126);
        $pdf->Cell(10,10,utf8_decode(strtr($oferta->iva_final,['.' => ',', ',' => '.'])));

        //Columna VACÍA
        $pdf->Rect(10,118,153,20);

        //Columna TOTAL IMPORTE
        $pdf->Rect(10,118,190,20);
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(175,126);
        $pdf->Cell(10,10,utf8_decode(strtr(number_format($oferta->total_final,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))));

        //OBSERVACIONES Y PLAZO DE ENTREGA
        $pdf->Rect(10,150,130,40);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(60,150);
        $pdf->Cell(10,10,utf8_decode('OBSERVACIONES'));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(10,164);
        $pdf->MultiCell(124,4,utf8_decode($oferta->observaciones));

        $pdf->Rect(10,150,190,10);

        $pdf->Rect(140,150,60,20);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(150,150);
        $pdf->Cell(10,10,utf8_decode('PLAZO DE ENTREGA'));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(160,164);
        $pdf->MultiCell(124,4,utf8_decode($oferta->plazo_entrega));
        

        //Final
        $name = 'Factura_'.$id.'.pdf';

        $pdf->Output('I',$name,true);

    }

?>