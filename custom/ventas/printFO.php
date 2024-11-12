<?php

    require '../../main.inc.php';
    require __DIR__ . '/../../vendor/autoload.php';

    use Fpdf\Fpdf;

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        
        $id = $_GET["id"];

        //Cuadrado izquierdo
        //INFO DEL CLIENTE
        $sqlCliente = " SELECT s.*, se.libre_1 FROM ".MAIN_DB_PREFIX."ventas_factura_origen f ";
        $sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.cliente ";
        $sqlCliente.= " INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = f.cliente ";
        $sqlCliente.= " WHERE f.rowid = ".$id;

        $resultCliente = $db->query($sqlCliente);
        $datosCliente = $db->fetch_object($resultCliente);

        //INFO DEL DEPARTAMENTO Y EL PAIS
        $sqlCliente2 = " SELECT d.nom, c.label FROM ".MAIN_DB_PREFIX."c_departements d ";
        $sqlCliente2.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.fk_departement = d.rowid ";
        $sqlCliente2.= " INNER JOIN ".MAIN_DB_PREFIX."c_country c ON c.rowid = s.fk_pays ";
        $sqlCliente2.= " WHERE d.rowid = ".$datosCliente->fk_departement;

        $resultCliente2 = $db->query($sqlCliente2);
        $datosCliente2 = $db->fetch_object($resultCliente2);

        //DATOS GENERALES DE LA FACTURA
        $sqlDatosFactura = " SELECT f.*, d.* FROM ".MAIN_DB_PREFIX."ventas_factura_origen f ";
        $sqlDatosFactura.= " INNER JOIN ".MAIN_DB_PREFIX."ventas_factura_origen_datos d ON d.fk_factura = f.rowid ";
        $sqlDatosFactura.= " WHERE f.rowid = ".$id;

        $resultDatosFactura = $db->query($sqlDatosFactura);
        $datosFactura = $db->fetch_object($resultDatosFactura);

        //DESCUENTOS
        if ($datosFactura->dto_cliente == "") {
            $datosFactura->dto_cliente = 0;
        }

        if ($datosFactura->dto_factura == "") {
            $datosFactura->dto_factura = 0;
        }

        $descuentos = $datosFactura->dto_cliente + $datosFactura->dto_factura;

        //Cogemos el proyecto de la certificacion
        $sqlProyecto = " SELECT fk_proyect FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
        $sqlProyecto.= " WHERE rowid = ".$datosFactura->certificacion." ";

        $resultProyecto = $db->query($sqlProyecto);
        $proyecto = $db->fetch_object($resultProyecto);
        $proyecto = $proyecto->fk_proyect;

        $sqlLineasProductos = " SELECT DISTINCT cl.*, c.*, p.ref, p.description, pom.price, pom.discount, pom.taxable_base ";
        $sqlLineasProductos.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
        $sqlLineasProductos.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
        $sqlLineasProductos.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
        $sqlLineasProductos.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = cl.fk_producto ";
        $sqlLineasProductos.= " WHERE c.fk_proyect = ".$proyecto." AND pom.fk_project = ".$proyecto." AND cl.cant_mes IS NOT NULL AND cl.fk_certificacion <= ".$datosFactura->certificacion." ";

        //AND fk_certificacion = ".$datosFactura->certificacion

        $resultLineasProductos = $db->query($sqlLineasProductos);
        $numProductos = $db->num_rows($resultLineasProductos);

        //INFORMACIÓN DE LA DELEGACIÓN
        $sqlDele = " SELECT d.* FROM ".MAIN_DB_PREFIX."delegacion d ";
        $sqlDele.= " WHERE d.id = ".$datosFactura->delegacion;

        $resultDele = $db->query($sqlDele);
        $dele = $db->fetch_object($resultDele);

        //INFORMACIÓN DEL PROYECTO
        $sqlProyectoFecha = " SELECT dateo FROM ".MAIN_DB_PREFIX."projet ";
        $sqlProyectoFecha.= " WHERE rowid = ".$proyecto." ";

        $resultProyectoFecha = $db->query($sqlProyectoFecha);
        $proyectoFecha = $db->fetch_object($resultProyectoFecha);
        $proyectoFecha = $proyectoFecha->dateo;

        $proyectoFecha = date("d-m-Y", strtotime($proyectoFecha));
        $datosFactura->fecha = date("d-m-Y", strtotime($datosFactura->fecha));

        //print $sqlLineasProductos;
        $paginas = 0;
        $totalPaginas = 1 + ceil($numProductos / 3);

        if ($numProductos % 3 == 0) {
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
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." - ".$datosCliente->town));

        $pdf->SetFont('Arial','BU',10);
        $pdf->SetXY(12,63);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente2->nom));

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
        $pdf->MultiCell(72,4,utf8_decode($dele->direccion));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,60);
        $pdf->MultiCell(95,4,utf8_decode($dele->provincia));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,55);
        $pdf->MultiCell(95,4,utf8_decode($dele->cp." - ".$dele->localidad));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,68);
        $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,73);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,78);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef2));

        //Datos intermedios
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,86);
        $pdf->Cell(10,10,utf8_decode('Contrato:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,86);
        $pdf->Cell(10,10,utf8_decode($datosFactura->num_pedido));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,94);
        $pdf->Cell(10,10,utf8_decode('Factura:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,94);
        $pdf->Cell(10,10,utf8_decode($datosFactura->ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,86);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,86);
        $pdf->Cell(10,10,utf8_decode($proyectoFecha));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,94);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,94);
        $pdf->Cell(10,10,utf8_decode($datosFactura->fecha));

        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(85,105);
        $pdf->Cell(10,10,utf8_decode('FACTURA:'));

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

        $y = 127;
        $contador = 0;
        $hecho = 0;
        while ($producto = $db->fetch_object($resultLineasProductos)) {

            //Para el precio del producto
            $sqlPrecio = " SELECT taxable_base, price, discount FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales ";
            $sqlPrecio.= " WHERE fk_product = ".$producto->fk_producto." AND fk_project = ".$proyecto." ";

            $resultPrecio = $db->query($sqlPrecio);
            $precio = $db->fetch_object($resultPrecio);

            if (($contador % 3 == 0) && ($contador != 0)) {

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
                $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." - ".$datosCliente->town));

                $pdf->SetFont('Arial','BU',10);
                $pdf->SetXY(12,63);
                $pdf->MultiCell(95,4,utf8_decode($datosCliente2->nom));

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
                $pdf->MultiCell(72,4,utf8_decode($dele->direccion));

                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(122,60);
                $pdf->MultiCell(95,4,utf8_decode($dele->provincia));

                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(122,55);
                $pdf->MultiCell(95,4,utf8_decode($dele->cp." - ".$dele->localidad));

                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(122,68);
                $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(122,73);
                $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(122,78);
                $pdf->MultiCell(95,4,utf8_decode($dele->telef2));
        
                //Datos intermedios
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(10,86);
                $pdf->Cell(10,10,utf8_decode('Contrato:'));
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(30,86);
                $pdf->Cell(10,10,utf8_decode($datosFactura->num_pedido));
        
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(10,94);
                $pdf->Cell(10,10,utf8_decode('Factura:'));
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(30,94);
                $pdf->Cell(10,10,utf8_decode($datosFactura->ref));
        
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(140,86);
                $pdf->Cell(10,10,utf8_decode('Fecha:'));
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(154,86);
                $pdf->Cell(10,10,utf8_decode($proyectoFecha));
        
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(140,94);
                $pdf->Cell(10,10,utf8_decode('Fecha:'));
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(154,94);
                $pdf->Cell(10,10,utf8_decode($datosFactura->fecha));
        
                $pdf->SetFont('Arial','B',12);
                $pdf->SetXY(85,105);
                $pdf->Cell(10,10,utf8_decode('FACTURA:'));
        
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

                $y = 127;

            }

            $contador++;

            //AND fk_certificacion = ".$datosFactura->certificacion

            if (($producto->fk_certificacion == $datosFactura->certificacion)) {

                if ($hecho == 0) {
                    $pdf->Rect(10,$y-2,190,0);
                    $hecho = 1;
                }

                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY(14,$y);
                $pdf->Cell(0,2,utf8_decode($producto->cant_mes));
    
                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY(24,$y-1);
                $pdf->MultiCell(105,4,utf8_decode($producto->description));

                $precioTotal = $precio->price * $producto->cant_mes;
    
                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY(136,$y-1);
                $pdf->MultiCell(105,4,utf8_decode(strtr(number_format($precio->price,2),['.' => ',', ',' => '.'])));
    
                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY(151,$y-1);
                $pdf->MultiCell(105,4,utf8_decode(strtr(number_format($descuentos,2),['.' => ',', ',' => '.'])).' %');
    
                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY(188,$y-1);
                $pdf->Cell(0,4,utf8_decode(strtr(number_format($producto->imp_mes,2),['.' => ',', ',' => '.'])), 0, 0, 'R');

            } else {

                $pdf->SetFont('Arial','',9);
                $pdf->SetXY(14,$y);
                $pdf->Cell(0,2,utf8_decode($producto->cant_mes));
    
                $pdf->SetFont('Arial','',9);
                $pdf->SetXY(24,$y-1);
                $pdf->MultiCell(105,4,utf8_decode($producto->description));

                $precioTotal = $precio->price * $producto->cant_mes;
    
                $pdf->SetFont('Arial','',9);
                $pdf->SetXY(136,$y-1);
                $pdf->MultiCell(105,4,utf8_decode(strtr(number_format($precio->price,2),['.' => ',', ',' => '.'])));
    
                $pdf->SetFont('Arial','',9);
                $pdf->SetXY(151,$y-1);
                $pdf->MultiCell(105,4,utf8_decode(strtr(number_format($descuentos,2),['.' => ',', ',' => '.'])).' %');
    
                $pdf->SetFont('Arial','',9);
                $pdf->SetXY(188,$y-1);
                $pdf->Cell(0,4,utf8_decode(strtr(number_format($producto->imp_mes,2),['.' => ',', ',' => '.'])), 0, 0, 'R');

            }


            $y+= 44;

        }

        //print $numProductos;

        if ($numProductos % 3 == 0) {
            
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
            $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." - ".$datosCliente->town));

            $pdf->SetFont('Arial','BU',10);
            $pdf->SetXY(12,63);
            $pdf->MultiCell(95,4,utf8_decode($datosCliente2->nom));

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
            $pdf->MultiCell(72,4,utf8_decode($dele->direccion));

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(122,60);
            $pdf->MultiCell(95,4,utf8_decode($dele->provincia));

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(122,55);
            $pdf->MultiCell(95,4,utf8_decode($dele->cp." - ".$dele->localidad));

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(122,68);
            $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(122,73);
            $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(122,78);
            $pdf->MultiCell(95,4,utf8_decode($dele->telef2));
    
            //Datos intermedios
            $pdf->SetFont('Arial','B',10);
            $pdf->SetXY(10,86);
            $pdf->Cell(10,10,utf8_decode('Contrato:'));
            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(30,86);
            $pdf->Cell(10,10,utf8_decode($datosFactura->num_pedido));
    
            $pdf->SetFont('Arial','B',10);
            $pdf->SetXY(10,94);
            $pdf->Cell(10,10,utf8_decode('Factura:'));
            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(30,94);
            $pdf->Cell(10,10,utf8_decode($datosFactura->ref));
    
            $pdf->SetFont('Arial','B',10);
            $pdf->SetXY(140,86);
            $pdf->Cell(10,10,utf8_decode('Fecha:'));
            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(154,86);
            $pdf->Cell(10,10,utf8_decode($proyectoFecha));
    
            $pdf->SetFont('Arial','B',10);
            $pdf->SetXY(140,94);
            $pdf->Cell(10,10,utf8_decode('Fecha:'));
            $pdf->SetFont('Arial','',10);
            $pdf->SetXY(154,94);
            $pdf->Cell(10,10,utf8_decode($datosFactura->fecha));
    
            $pdf->SetFont('Arial','B',12);
            $pdf->SetXY(85,105);
            $pdf->Cell(10,10,utf8_decode('FACTURA:'));
    
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

            $y = 106;

        }

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(24,$y+20);
        $pdf->MultiCell(105,4,utf8_decode($datosFactura->observaciones));

        /*$pdf->SetFont('Arial','',10);
        $pdf->SetXY(24,$y+24);
        $pdf->MultiCell(105,4,utf8_decode($datosCliente->libre_1));*/

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
        $pdf->MultiCell(95,4,utf8_decode($datosCliente->zip." - ".$datosCliente->town));

        $pdf->SetFont('Arial','BU',10);
        $pdf->SetXY(12,63);
        $pdf->MultiCell(95,4,utf8_decode($datosCliente2->nom));

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
        $pdf->MultiCell(72,4,utf8_decode($dele->direccion));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,60);
        $pdf->MultiCell(95,4,utf8_decode($dele->provincia));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,55);
        $pdf->MultiCell(95,4,utf8_decode($dele->cp." - ".$dele->localidad));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,68);
        $pdf->MultiCell(95,4,utf8_decode($dele->responsible_name));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,73);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef1));

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(122,78);
        $pdf->MultiCell(95,4,utf8_decode($dele->telef2));

        //Datos intermedios
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,86);
        $pdf->Cell(10,10,utf8_decode('Contrato:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,86);
        $pdf->Cell(10,10,utf8_decode($datosFactura->num_pedido));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,94);
        $pdf->Cell(10,10,utf8_decode('Factura:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,94);
        $pdf->Cell(10,10,utf8_decode($datosFactura->ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,86);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,86);
        $pdf->Cell(10,10,utf8_decode($proyectoFecha));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(140,94);
        $pdf->Cell(10,10,utf8_decode('Fecha:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(154,94);
        $pdf->Cell(10,10,utf8_decode($datosFactura->fecha));

        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(85,105);
        $pdf->Cell(10,10,utf8_decode('FACTURA:'));

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

        //Descuento y sus datos
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(25,124);
        $pdf->Cell(10,10,utf8_decode('Descuento'));

        $euro = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', '€');

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(132,124);
        $pdf->Cell(10,10,utf8_decode($datosFactura->dto_cliente.'+'.$datosFactura->dto_factura.'%'));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(185,124);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->descuento,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

        //Suma factura y sus datos
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(25,132);
        $pdf->Cell(10,10,utf8_decode('SUMA FACTURA (incluido descuento)'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(180,132);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->bruto,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

        //Deducciones y sus datos
        //Para las deducciones
        $sqlDeduc = " SELECT f.*, c.imp_mes_total FROM ".MAIN_DB_PREFIX."ventas_factura_origen f ";
        $sqlDeduc.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = f.certificacion ";
        $sqlDeduc.= " WHERE f.certificacion < ".$datosFactura->certificacion." AND fk_proyect = ".$proyecto." ";

        $resultDeduc = $db->query($sqlDeduc);
        $numDeduc = $db->num_rows($resultDeduc);

        $y = 142;

        while ($deduc = $db->fetch_object($resultDeduc)) {
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(25,$y);
            $pdf->Cell(10,10,utf8_decode('A deducir: '.$deduc->ref));

            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(180,$y);
            $pdf->Cell(0,10,utf8_decode(strtr(number_format($deduc->imp_mes_total,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');
            $y+=5;
        }

        //Total factura y sus datos
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(25,$y+3);
        $pdf->Cell(10,10,utf8_decode('TOTAL FACTURA'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(180,$y+3);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->base_imponible,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');


        $sqlDelegacion = " SELECT iva FROM ".MAIN_DB_PREFIX."delegacion ";
        $sqlDelegacion.= " WHERE id = ".$datosFactura->delegacion." ";
    
        $resultDelegacion = $db->query($sqlDelegacion);
        $delegacion = $db->fetch_object($resultDelegacion);

        //Iva y sus datos
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(25,$y+10);
        $pdf->Cell(10,10,utf8_decode('IVA: '.$delegacion->iva.'%'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(180,$y+10);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->impuestos,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

        $pdf->SetFont('Arial','BU',10);
        $pdf->SetXY(25,$y+17);
        $pdf->Cell(10,10,utf8_decode('IMPORTE TOTAL FACTURA'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(180,$y+17);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->total_factura,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

        if ($datosFactura->divisa == 1) {
            $divisa = "EURO";
        } else {
            $divisa = "OTRA";
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(25,$y+24);
        $pdf->Cell(10,10,utf8_decode('MONEDA BASE: '.$divisa));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(25,$y+31);
        $pdf->Cell(10,10,utf8_decode('TOTAL A PAGAR:'));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(180,$y+31);
        $pdf->Cell(0,10,utf8_decode(strtr(number_format($datosFactura->total_pagar,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))), 0, 0, 'R');

        //Para la fecha del vencimiento
        $patron = '/(\d+)\sD.*S/i';
        //$datosCliente->libre_1 = "BLABLABLABLABLA";
        preg_match($patron, $datosCliente->libre_1, $coincidencias);
        $dias = $coincidencias[1];
        //$dias = "";

        if ($dias == "") {
            $cadena = $datosCliente->libre_1;
            //$cadena = "BLABLABLABLA [130;5;10]";
            preg_match('/\[(\d+)/', $cadena, $primerNumeroCorchetes);
            $primerNumeroCorchetes = isset($primerNumeroCorchetes[1]) ? $primerNumeroCorchetes[1] : "";
            $dias = $primerNumeroCorchetes;
        }


        //print "DIAS: ".$dias;
        if ($dias == "") {
            $dias = 30;
        }
        //die;
        $fechaFormatoCorrecto = DateTime::createFromFormat('d-m-Y', $datosFactura->fecha)->format('Y-m-d');
        //print $fechaFormatoCorrecto;
        $fechaFinal = date('d-m-Y', strtotime("$fechaFormatoCorrecto + $dias days"));
        //print $fechaFinal;

        //PRUEBAS
        $cadena = $datosCliente->libre_1;
        preg_match_all('/VTO\. (.+)/', $cadena, $matches);
        $texto = $matches[1][0];
        $numeros = array_filter(explode(" ", $texto), function($numero) {
            return is_numeric($numero);
        });

        if (empty($numeros)) {
            //print 'hola';
            $numerosTemp = preg_split('/[\[\],;]/', $texto);
            $primerNumero = true;
            foreach ($numerosTemp as $numero) {
                $numero = trim($numero);
                if (is_numeric($numero)) {
                    if ($primerNumero && $numero > 31) {
                        $primerNumero = false;
                        continue;
                    }
                    $numeros[] = $numero;
                    $primerNumero = false;
                }
            }
        }

        //print $fechaFinal;

        //print_r($numeros); // Esto imprimirá Array ( [0] => 5 [2] => 20 )
        //print $fechaFinal;
        $diaNuevo = substr($fechaFinal, 0, 2); // Obtiene el día actual
        $mesNuevo = substr($fechaFinal, 3, 2); // Obtiene el día actual
        $anoNuevo = substr($fechaFinal, 6, 4); // Obtiene el día actual
        //print $anoActual;
        //echo $diaActual;

        $menorDiferencia = null;
        $numeroCercano = null;

        foreach ($numeros as $numero) {
            $diferencia = abs($diaNuevo - $numero);
            if ($menorDiferencia === null || $diferencia < $menorDiferencia) {
                $menorDiferencia = $diferencia;
                $numeroCercano = $numero;
            }
        }
        //echo "numero cercano ".$numeroCercano;

        if ($numeroCercano == "") {
            $numeroCercano = $diaNuevo;
        }

        //FECHA DE VENCIMIENTO EN CASO DE QUE HAYA VTO.
        $fcvcm = $numeroCercano."-".$mesNuevo."-".$anoNuevo;
        //print $fcvcm;

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(25,$y+42);
        $pdf->MultiCell(100,4,utf8_decode('Forma de pago: '.$datosCliente->libre_1));

        $pdf->SetFont('Arial','B',8);
        $pdf->SetXY(25,$y+52);
        $pdf->MultiCell(100,4,utf8_decode('Vencimiento: '.$fcvcm));

        if ($datosFactura->retencion == "") {
            $datosFactura->retencion = 0;
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(25,$y+59);
        $pdf->MultiCell(100,4,utf8_decode('Retención: '.strtr(number_format($datosFactura->retencion,2),['.' => ',', ',' => '.']).' %'));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(25,$y+64);
        $pdf->MultiCell(100,4,utf8_decode('s. BASE IMPONIBLE: '.strtr(number_format($datosFactura->imp_retencion,2),['.' => ',', ',' => '.']).' '.utf8_encode(chr(128))));

        //Columna PRECIO
        $pdf->Rect(10,118,140,155);

        //Columna DTO
        $pdf->Rect(10,118,154,155);
        

        //Final
        $name = 'Factura_'.$id.'.pdf';

        $pdf->Output('I',$name,true);

    }

?>