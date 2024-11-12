<?php 

require '../../main.inc.php';
require __DIR__ . '/../../vendor/autoload.php';

use Fpdf\Fpdf;

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

const PAYMENT_PERIODICITY = [
    '0' => 'Mensual',
    '1' => 'Mensual',
    '2' => 'Bimestral',
    '3' => 'Trimestral',
    '4' => 'Semestral',
    '5' => 'Anual',
    '8' => 'Cuatrimestral'
];

function calcular_fecha_vencimiento($fecha_inicial, $periodicidad) {

    switch($periodicidad) {

        case 0: $meses = 1; break;
        case 1: $meses = 1; break;
        case 2: $meses = 2; break;
        case 3: $meses = 3; break;
        case 4: $meses = 6; break;
        case 5: $meses = 12; break;
        case 8: $meses = 4; break;
        
        default: return FALSE;

    }

    $fecha_inicial_formato_correcto = DateTime::createFromFormat('d/m/Y', $fecha_inicial)->format('Y-m-d');

    $fecha_final = date('d/m/Y', strtotime($fecha_inicial_formato_correcto.' + '.$meses.' months'));

    return $fecha_final;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $id = $_GET["id"];

    $sqlHeaderCard = " SELECT DISTINCT m.name, s.nom, s.rowid as cliente_id, s.fk_departement, s.address,s.zip, s.town, s.phone, s.email, s.siren, s.code_fournisseur,"; 
    $sqlHeaderCard.= " s.code_client, d.iva, d.telef1, d.direccion, d.cp, d.localidad, d.provincia,  "; 
    $sqlHeaderCard.= " d.responsible_name, pe.discount_offer, pe.price_packaging, se.libre_1,"; 
    $sqlHeaderCard.= " pe.price_shipping, mc.ref as mc_ref, DATE(mc.date_creation) as date_creation, mc.periodicity,"; 
    $sqlHeaderCard.= " mi.ref as mi_ref, mi.rowid as infid, DATE(mi.date_creation) as mi_date_creation, pro.ref as proyecto, ";
    $sqlHeaderCard.= " pro.rowid as idproyecto, mc.description as mc_desc, mc.rowid as mc_rowid, mi.observations as mi_obs, ";
    $sqlHeaderCard.= " mc.contact_discount as dto_oferta, mc.client_discount as dto_cliente ";
    $sqlHeaderCard.= " FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi"; 
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mc.rowid = mi.contract_id";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."multicurrency m ON m.rowid = mc.currency";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = mc.offer_id";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."projet pro ON pe.fk_object = pro.rowid";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_project = pro.rowid";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."delegacion d ON d.id = mc.delegation_id";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = mc.client_id";
    $sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = s.rowid";
    //$sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."c_departements cd ON cd.rowid = s.fk_departement";
    //$sqlHeaderCard.= " INNER JOIN ".MAIN_DB_PREFIX."c_departements cdd ON cdd.rowid = d.provincia";
    $sqlHeaderCard.= " WHERE mi.rowid = ".$id;
    
    $resultHeaderCard = $db->query($sqlHeaderCard);                                                                             
    $dataHeaderCard = $db->fetch_object($resultHeaderCard);

    //PARA LA PROVINCIA
    if ($dataHeaderCard->fk_departement != "") {
        $sqlProvincia = " SELECT * FROM ".MAIN_DB_PREFIX."c_departements ";
        $sqlProvincia.= " WHERE rowid = ".$dataHeaderCard->fk_departement." ";

        $resultProvincia = $db->query($sqlProvincia);
        $provincia = $db->fetch_object($resultProvincia);

    }

    /*$paymentDaysAllowed = explode(",",$dataHeaderCard->payment_days);
    
    $expirationDay = date("Y-m-d",strtotime($dataHeaderCard->datef . " + " . $dataHeaderCard->nbjour . " days"));
    
    $yearDateCreation = date("Y",strtotime($dataHeaderCard->datef));
    
    $dayOfExpiration = date("d",strtotime($expirationDay));
    
    $newDayOfExpiration = 0;
    
    for ($i=0; $i < count($paymentDaysAllowed); $i++) { 
        
        if ($dayOfExpiration < $paymentDaysAllowed[$i] ){

            $newDayOfExpiration = $paymentDaysAllowed[$i]; 
            break;
        } 

    }

    if($newDayOfExpiration == 0){
        
        $newDayOfExpiration = $paymentDaysAllowed[0];//Check if first and go to new month
        $newExpirationDay = date("d/m/Y",strtotime($newDayOfExpiration."-".substr($expirationDay,2). "+ 1 month"));

    } else{

        $newExpirationDay = date("d/m/Y",strtotime($expirationDay . " + " . ($newDayOfExpiration - $dayOfExpiration) . " days"));
    }*/

    $dto_oferta = $dataHeaderCard->dto_oferta;
    $dto_cliente = $dataHeaderCard->dto_cliente;

    if ($dto_oferta == NULL || $dto_oferta == "") {
        $dto_oferta = 0;
    }

    if ($dto_cliente == NULL || $dto_cliente == "") {
        $dto_cliente = 0;
    }

    //Para condiciones de pago en el informe externo del informe
    /*$sqlCondPago = " SELECT s.cond_reglement FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi ";
    $sqlCondPago.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mi.contract_id = mc.rowid ";
    $sqlCondPago.= " INNER JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = mc.project_id ";
    $sqlCondPago.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = p.fk_soc ";
    $sqlCondPago.= " WHERE mi.rowid = ".$id;

    $resultCondPago = $db->query($sqlCondPago);

    $condPago = $db->fetch_object($resultCondPago);

    $sqlCodigoPago = " SELECT code FROM ".MAIN_DB_PREFIX."c_payment_term ";
    $sqlCodigoPago.= " WHERE rowid = ".$condPago->cond_reglement;

    $resultCodigoPago = $db->query($sqlCodigoPago);

    $codigoPago = $db->fetch_object($resultCodigoPago);

    $sqlTextoCond = " SELECT rowid, texto FROM ".MAIN_DB_PREFIX."c_payment_term_text ";
    $sqlTextoCond.= " WHERE fk_cond = ".$condPago->cond_reglement;

    $resultTextoCond = $db->query($sqlTextoCond);*/
    $campoLibre = $dataHeaderCard->libre_1;

    // A COSTE CERO (DE LOS CONTRATOS)
    $sqlProducts = " SELECT p.rowid, p.label, p.ref, p.description, mcr.quantity, mcr.mantenido ";
    $sqlProducts.= "FROM ".MAIN_DB_PREFIX."mantenimiento_informes mi ";
    $sqlProducts.= "INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mi.contract_id = mc.rowid ";
    $sqlProducts.= "INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos_repuestos mcr ON mcr.fk_contract = mc.rowid ";
    $sqlProducts.= "INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = mcr.fk_product ";
    $sqlProducts.= "WHERE mi.rowid = ".$id;
   
    $resultProducts = $db->query($sqlProducts);
    $numProducts = $db->num_rows($resultProducts);

    $costeCero_tva_tx = 0;
    $costeCero_remise_percent = 0;
    $costeCero_subprice = 0;
    $costeCero_total_ht = 0;
    $costeCero_total_tva = 0;
    $costeCero_total_ttc = 0;

    $sqlSubprice = "pom.quantity * pom.price";
    $sqlTotalHt = "pom.quantity * ( pom.taxable_base - ( ( pom.taxable_base * pom.discount ) / 100) )";
    $sqlTotalTva = " ( (".$sqlTotalHt.") / 100 ) * 21 ";

    $sqlProductsIncluded = " SELECT p.description, mce.location, p.tva_tx, p.ref, p.label, pom.taxable_base, pom.fk_product, pom.quantity, pom.price as subprice, pom.discount, d.iva, ";
    $sqlProductsIncluded.= " (".$sqlTotalHt.") as total_ht, ( ".$sqlTotalTva." ) as total_tva,   ";
    $sqlProductsIncluded.= " ( (".$sqlTotalHt.") + (".$sqlTotalTva.")  ) as total_ttc,   ";
    $sqlProductsIncluded.= " mc.contact_discount as dto_oferta, i.contract_id, mc.client_discount as dto_cliente, mc.spare_parts_discount ";
    $sqlProductsIncluded.= " FROM ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos mce";
    $sqlProductsIncluded.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mc.rowid = mce.fk_contract";
    $sqlProductsIncluded.= " INNER JOIN ".MAIN_DB_PREFIX."delegacion d ON d.id = mc.delegation_id";
    $sqlProductsIncluded.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_project = mc.project_id";
    $sqlProductsIncluded.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes i ON i.contract_id = mc.rowid";
    $sqlProductsIncluded.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product";
    $sqlProductsIncluded.= " WHERE i.rowid = ".$id;
    $sqlProductsIncluded.= " GROUP BY pom.fk_product";
    
    $resultProductsIncluded = $db->query($sqlProductsIncluded);

    $simantSubprice = number_format(floatval(0.00),2,",",".");
    $simantDescuento = 0.00;
    $simantImpIva = 0;
    $simantPrecioFinal = 0;
    $simantDesc = "Trabajos Preventivos. Cuota de mantenimiento de conservación del siguiente mantenimiento: \n";

    $sqlLocations = " SELECT mce.location FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
    $sqlLocations.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mc.rowid = mi.contract_id ";
    $sqlLocations.= " INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos mce ON mce.fk_contract = mc.rowid ";
    $sqlLocations.= " WHERE mi.rowid = ".$id;

    $resultLocations = $db->query($sqlLocations);

    $locations = [];

    while ($loc = $db->fetch_object($resultLocations)) {
        $locations [] = $loc->location;
    }

    while ($producto = $db->fetch_object($resultProductsIncluded)) {

        $dto_oferta = $producto->dto_oferta;
        $dto_cliente = $producto->dto_cliente;
        $dto_repuestos = $producto->spare_parts_discount;
		$contratoo = $producto->contract_id;
		
    }
	
	//PARA SACAR BIEN LAS LINEAS
    $sqlLineas = "SELECT DISTINCT mce.rowid as idequipo, p.ref, p.description, p.label, mce.location, pom.taxable_base FROM ".MAIN_DB_PREFIX."product p
    INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON pom.fk_product = p.rowid
    INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos mce ON mce.fk_product = p.rowid 
    WHERE mce.fk_contract = ".$contratoo." ORDER BY idequipo ";
	
	$resultLineas = $db->query($sqlLineas);
	
	$i = 0;
	while ($linea = $db->fetch_object($resultLineas)) {
        $aDescontar = ($linea->taxable_base * $dto_repuestos) / 100;
        $simantSubprice+= $linea->taxable_base - $aDescontar;
        //$simantImpIva+= $producto->total_tva;
        //$simantPrecioFinal+= $producto->total_ht + $producto->total_tva;

        $location = $locations[$i];

        if ($location == "" || $location == NULL) {
            $location = "...";
        }

        $simantDesc.= $linea->ref." - ".$linea->label."  instalados en ".$location."\n";

        $i++;
    }

    if ($dto_oferta == NULL || $dto_oferta == "") {
        $dto_oferta = 0;
    }

    if ($dto_cliente == NULL || $dto_cliente == "") {
        $dto_cliente = 0;
    }

    $simantDescuento = $dto_oferta + $dto_cliente;

    if ($simantDescuento > 0) {
        $descuento = ($simantSubprice * $simantDescuento) / 100;
        //print $descuento;
    } elseif ($simantDescuento == 0) {
        $descuento = 0;
    }

    //print $simantSubprice;
    $simantConDescuento = $simantSubprice - $descuento;
    //print $simantConDescuento;
    $iva = $dataHeaderCard->iva;
    //$cantidadIva = ($simantConDescuento * $dataHeaderCard->iva) / 100;

    $pages = ceil( ( $numProducts + 2 ) / 2);

    $pdf = new Fpdf();
    $page = 1;

    $lineProduct = [];

    $fechaFacturaI = date_format( date_create($dataHeaderCard->mi_date_creation),'d/m/Y' );

    $fechaFacturaF = calcular_fecha_vencimiento($fechaFacturaI, $dataHeaderCard->periodicity);

    list($diaI, $mesI, $anoI) = explode("/", $fechaFacturaI);
    list($diaF, $mesF, $anoF) = explode("/", $fechaFacturaF);

    $simantDesc .= "Mantenimiento ".PAYMENT_PERIODICITY[$dataHeaderCard->periodicity]." correspondiente a ".MONTHS[$mesI]." ".$anoI." hasta ".MONTHS[$mesF]." ".$anoF."";
    $lineProduct[0] = "SIMANT";
    $lineProduct[1] = 1;
    $lineProduct[2] = $simantDesc;
    $lineProduct[3] = $simantSubprice;
    $lineProduct[4] = $simantDescuento."%";
    $lineProduct[5] = $simantConDescuento;

    $listaIds = [];
    $listaLabels = [];
   
    $contCosteCero = 6;

    while ($line = $db->fetch_object($resultProducts)) {
        
        if (($line->mantenido == 0) || ($line->mantenido == "")) {

            $listaIds [] = $line->ref;
            $listaLabels [] = $line->label;

            //---
            //SACAMOS PRECIO PRODUCTO
            $sqlPrecio = " SELECT price FROM ".MAIN_DB_PREFIX."product p ";
            $sqlPrecio.= " WHERE rowid = ".$line->rowid." ";

            $resultPrecio = $db->query($sqlPrecio);
            $precio = $db->fetch_object($resultPrecio);

            //Precio
			$precio->price = number_format($precio->price,2);
            $precioFinal = $precio->price - (($precio->price * $dto_repuestos) / 100);
            
            //Importe
            $descontado = ($precioFinal * $simantDescuento) / 100;
            $base = ($precioFinal * $line->quantity) - $descontado;
            //iva total
            $line->total_tva = ($line->total_ht * $dele->iva) / 100;
            //importe total
            $line->total_ttc = $line->total_ht + $line->total_tva;

            //---

            $lineProduct[$contCosteCero] = $line->ref;
            $lineProduct[$contCosteCero + 1] = $line->quantity;
            $lineProduct[$contCosteCero + 2] = $line->description;
            //Precio
            $lineProduct[$contCosteCero + 3] = $precioFinal;
            //Dto
            $lineProduct[$contCosteCero + 4] = $simantDescuento."%";
            //Importe
            $lineProduct[$contCosteCero + 5] = $base;

            $contCosteCero+=6;

            //ACTUALIZAMOS EL PRECIO NETO/B.IMPONIBLE
            $simantConDescuento+= $base;

        } else if ($line->mantenido == 1) {

            $listaIds [] = $line->ref;
            $listaLabels [] = $line->label;

            $lineProduct[$contCosteCero] = $line->ref;
            $lineProduct[$contCosteCero + 1] = $line->quantity;
            $lineProduct[$contCosteCero + 2] = $line->description;
            //Precio
            $lineProduct[$contCosteCero + 3] = number_format(0,2,",",".");
            //Dto
            $lineProduct[$contCosteCero + 4] = number_format(0,2,",",".")."%";
            //Importe
            $lineProduct[$contCosteCero + 5] = number_format(0,2,",",".");

            $contCosteCero+=6;

        }

    }

    $cantidadIva = ($simantConDescuento * $dataHeaderCard->iva) / 100;

    /*while ($line = $db->fetch_object($resultProducts)) {
        
        if (count($lineProduct) == 0)  $lineProduct[0] = $line;
        
        $lineProduct[0]->price = number_format(floatval($simantSubprice),2);
        $lineProduct[0]->amount = number_format(floatval($simantConDescuento),2);
    }*/

    /*while ($line = $db->fetch_object($resultProductsIncluded)) {

        $line->price = number_format(floatval(0.00),2);
        $line->amount = number_format(floatval(0.00),2);
        $line->remise_percent = number_format(floatval(0.00),2);
        $lineProduct[] = $line;

    }*/

    $lineProduct[] = "";
    $lineProduct[] = "";
    $lineProduct[] = $campoLibre;
    $lineProduct[] = "";
    $lineProduct[] = "";
    $lineProduct[] = "";

    $monthNameDateCreation = MONTHS[date_format( date_create($dataHeaderCard->date_creation),'m' )];

    $numProducts = ceil( ( $numProducts + 2 ) / 2);

    //array_shift($lineProduct);

    $final = count($lineProduct) - 1;

    $j = 0;
    for ($i=0; $i < $numProducts; $i++) {
        
        $pdf->AddPage();
        
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
    
        $pdf->Rect(10,40,80,40);

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(16,40);
        $pdf->Cell(10,10,utf8_decode($provincia->nom));
        $pdf->SetXY(16,48);
        $pdf->MultiCell(65,4,utf8_decode($dataHeaderCard->address));
        $pdf->SetXY(16,55);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->zip.' - '.$dataHeaderCard->town));
        $pdf->SetXY(16,60);
        $pdf->Cell(10,10,utf8_decode($provincia->nom));
        $pdf->SetXY(16,65);
        $pdf->Cell(10,10,utf8_decode('NIF: '.$dataHeaderCard->siren));
        $pdf->SetXY(16,70);
        $pdf->Cell(10,10,utf8_decode('Nº CLIENTE/PROVEEDOR: '.$dataHeaderCard->code_client));

        $pdf->Rect(100,40,80,40);
        $pdf->SetXY(106,40);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->provincia));
        $pdf->SetXY(106,48);
        $pdf->MultiCell(65,4,utf8_decode($dataHeaderCard->direccion));
        $pdf->SetXY(106,55);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->cp.' - '.$dataHeaderCard->localidad));
        $pdf->SetXY(106,60);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->provincia));
        
        $pdf->SetXY(106,70);
        $pdf->MultiCell(65,4,utf8_decode('Atn. Sr/a '.$dataHeaderCard->responsible_name.' Tel: '.$dataHeaderCard->telef1));
        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,90);
        $pdf->Cell(10,10,utf8_decode('Su pedido:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,90);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->mc_ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(100,90);
        $pdf->Cell(10,10,utf8_decode('FECHA:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(115,90);
        $pdf->Cell(10,10,date_format( date_create($dataHeaderCard->date_creation),'d/m/Y' ));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,95);
        $pdf->Cell(10,10,utf8_decode('Nº Albarán:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(30,95);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->mi_ref));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(100,95);
        $pdf->Cell(10,10,utf8_decode('FECHA:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(115,95);
        $pdf->Cell(10,10,date_format( date_create($dataHeaderCard->mi_date_creation),'d/m/Y' ));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(100,100);
        $pdf->Cell(10,10,utf8_decode('FECHA:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(115,100);
        $pdf->Cell(10,10,date_format( date_create($dataHeaderCard->datef),'d/m/Y' ) );

        $pdf->SetXY(170,100);
        $pdf->Cell(10,10,utf8_decode('Página: '.$page.'/'.$pages));

        //HEADER TABLE

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(5,120,25,100);
        $pdf->SetXY(10,120);
        $pdf->Cell(10,10,utf8_decode('CÓDIGO'));

        $pdf->Rect(30,120,15,100);
        $pdf->SetXY(32,120);
        $pdf->Cell(10,10,utf8_decode('UDS'));

        $pdf->Rect(45,120,90,100);
        $pdf->SetXY(75,120);
        $pdf->Cell(10,10,utf8_decode('DESCRIPCIÓN'));

        $pdf->Rect(135,120,20,100);
        $pdf->SetXY(137,120);
        $pdf->Cell(10,10,utf8_decode('PRECIO'));

        $pdf->Rect(155,120,20,100);
        $pdf->SetXY(160,120);
        $pdf->Cell(10,10,utf8_decode('DTO'));

        $pdf->Rect(175,120,30,100);
        $pdf->SetXY(180,120);
        $pdf->Cell(10,10,utf8_decode('IMPORTE'));

        $pdf->Rect(5,120,200,10);

        //PRODUCTS TABLE
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(7,130);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j]));

        $pdf->SetXY(35,130);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+1]));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(47,133);
        // $chars_no=strlen($lineProduct[$i]->description);
        // $f=0.75*(70/$chars_no)*(20/5);
        //$pdf->SetFont('Arial','',$f);
        $pdf->MultiCell(85,4,utf8_decode($lineProduct[$j+2]),0,'L');

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(137,130);
        $pdf->Cell(10,10,utf8_decode(number_format(floatval($lineProduct[$j+3]),2,",",".")));

        $pdf->SetXY(161,130);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+4]));

        $pdf->SetXY(182,130);
        $pdf->Cell(10,10,utf8_decode(number_format(floatval($lineProduct[$j+5]),2,",",".")));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(7,185);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+6]));

        $pdf->SetXY(35,185);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+7]));

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(47,189);
        $pdf->MultiCell(85,3,utf8_decode($lineProduct[$j+8]),0,'L');

        $pdf->SetXY(139,185);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+9]));

        $pdf->SetXY(162,185);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+10]));

        $pdf->SetXY(182,185);
        $pdf->Cell(10,10,utf8_decode($lineProduct[$j+11]));

        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,220);
        $pdf->Cell(10,10,utf8_decode('Forma de pago:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(40,220);
        $pdf->Cell(10,10,utf8_decode($codigoPago->code));

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(10,225);
        $pdf->Cell(10,10,utf8_decode('Moneda base:'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(40,225);
        $pdf->Cell(10,10,iconv('UTF-8', 'windows-1252',$dataHeaderCard->name));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(5,235,30,20);
        $pdf->SetXY(12,235);
        $pdf->Cell(10,10,utf8_decode('NETO'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(12,245);
        $pdf->Cell(10,10,utf8_decode(number_format($simantConDescuento,2,",",".")));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(35,235,20,20);
        $pdf->SetXY(38,235);
        $pdf->Cell(10,10,utf8_decode('% DTO'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(38,245);
        $pdf->Cell(10,10,utf8_decode($dto_oferta.'+'.$dto_cliente.'%'));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(55,235,25,20);
        $pdf->SetXY(58,235);
        $pdf->Cell(10,10,utf8_decode('EMBALAJE'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(60,245);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->price_packaging));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(80,235,25,20);
        $pdf->SetXY(85,235);
        $pdf->Cell(10,10,utf8_decode('PORTES'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(87,245);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->price_shipping));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(105,235,30,20);
        $pdf->SetXY(107,235);
        $pdf->Cell(10,10,utf8_decode('B. IMPONIBLE'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(110,245);
        $pdf->Cell(10,10,utf8_decode(number_format($simantConDescuento,2,",",".")));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(135,235,20,20);
        $pdf->SetXY(140,235);
        $pdf->Cell(10,10,utf8_decode('% IVA'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(140,245);
        $pdf->Cell(10,10,utf8_decode($dataHeaderCard->iva.'%'));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(155,235,25,20);
        $pdf->SetXY(162,235);
        $pdf->Cell(10,10,utf8_decode('IVA'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(160,245);
        $pdf->Cell(10,10,utf8_decode(number_format(floatval($cantidadIva),2,",",".")));

        $pdf->SetFont('Arial','B',10);
        $pdf->Rect(180,235,25,20);
        $pdf->SetXY(185,235);
        $pdf->Cell(10,10,utf8_decode('TOTAL'));
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY(185,245);
        $precioFinal = $dataHeaderCard->total_ttc;
        $pdf->Cell(10,10,utf8_decode(number_format(floatval($simantConDescuento + $cantidadIva),2,",",".")));

        $pdf->Rect(5,235,200,10);

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5,260);
        $pdf->Write(3,utf8_decode("NOTA: Rigen para todos n/ suministros y efectos n/ condiciones genrelaes. El Precio indicado se entiende para el material en fábrica, sin embalaje y sin seguro de transporte. El I.V.A. será cargado en factura. "));
        $pdf->SetXY(5,266);
        $pdf->Write(5,utf8_decode("La mercancía se transporta siempre por cuenta y riesgo del comprador. Cualquier litigio que haya, queda sometido a los Tribunales de Madrid."));
        $pdf->SetXY(5,270);
        $pdf->Write(5,utf8_decode("Registro Mercantil de Madrid 1.110 Folio 2 Hoja 2.092 Inscripción 1ª fecha 10-2-56 Número de Identificación Fiscal ES - B-28074979"));

        $page++;
        $j+=12;

    }

    //CUSTOM PAGE

    $pdf->AddPage();
        
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

    $pdf->Rect(10,40,80,35);

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(35,40);
    $pdf->Cell(10,8,utf8_decode('Datos del cliente'));

    $pdf->SetXY(12,47);
    $pdf->Cell(10,8,utf8_decode('Cliente:'));
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(27,49);
    $pdf->MultiCell(60,4,utf8_decode($dataHeaderCard->nom));

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(12,57);
    $pdf->Cell(10,8,utf8_decode('Proyecto:'));
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(31,57);
    $pdf->Cell(10,8,utf8_decode($dataHeaderCard->proyecto));

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(12,64);
    $pdf->Cell(10,8,utf8_decode('Dirección:'));
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(31,66);
    $pdf->MultiCell(58,4,utf8_decode($dataHeaderCard->address));

    $pdf->Rect(100,40,80,35);

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(120,40);
    $pdf->Cell(10,8,utf8_decode('Tipo de intervención'));

    $pdf->SetXY(105,50);
    $pdf->Cell(10,6,utf8_decode('[X] Mantenimiento'));

    $pdf->SetXY(140,50);
    $pdf->Cell(10,6,utf8_decode('[ ] Reparación'));

    $pdf->SetXY(105,55);
    $pdf->Cell(10,6,utf8_decode('[ ] Instalación'));

    $pdf->SetXY(140,55);
    $pdf->Cell(10,6,utf8_decode('[ ] Puesta en marcha'));
    
    $pdf->SetXY(105,60);
    $pdf->Cell(10,6,utf8_decode('[ ] Otros: '));

    $pdf->SetXY(105,70);
    $pdf->Cell(10,2,utf8_decode('Horas de desplazamiento:'));

    $pdf->Rect(10,90,190,80);

    $fecha = $dataHeaderCard->date_creation;
    $time = strtotime($fecha);
    $fechaFinal = date('Y', $time);

    $pdf->SetXY(20,90);
    $pdf->Cell(10,10,utf8_decode('Día'));
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(13,99);
    $pdf->Cell(10,8,utf8_decode($monthNameDateCreation." ".$fechaFinal));

    $trabajosRealizados = "Labor de mantenimiento haciendo uso de repuestos: ";
    for ($k = 0; $k < count($listaIds); $k++) {
        $trabajosRealizados.= $listaIds[$k]." - ".$listaLabels[$k];

        if ($k != count($listaIds)-1) {
            $trabajosRealizados.= ", ";
        }
    }

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(85,90);
    $pdf->Cell(10,10,utf8_decode('Trabajos realizados'));
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(45, 100);
    $pdf->MultiCell(95,5,utf8_decode($trabajosRealizados));

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(155,90);
    $pdf->Cell(10,10,utf8_decode('Horas/Técnico'));

    $pdf->SetXY(10,105);
    $pdf->Cell(10,10,utf8_decode('H. Inicio'));

    $pdf->SetXY(30,105);
    $pdf->Cell(10,10,utf8_decode('H. Fin'));

    $pdf->SetXY(150,97);
    $pdf->Cell(10,8,utf8_decode('Nº Tec. IT'));

    $pdf->SetXY(175,97);
    $pdf->Cell(10,8,utf8_decode('Nº Tec. HW'));

    $pdf->SetXY(150,107);
    $pdf->Cell(10,10,utf8_decode('Horas IT'));

    $pdf->SetXY(175,107);
    $pdf->Cell(10,10,utf8_decode('Horas HW'));

    $pdf->SetXY(10,130);
    $pdf->Cell(10,10,utf8_decode('H. Inicio'));

    $pdf->SetXY(30,130);
    $pdf->Cell(10,10,utf8_decode('H. Fin'));

    $pdf->SetXY(150,120);
    $pdf->Cell(10,6,utf8_decode('Nº Tec. IT'));

    $pdf->SetXY(175,120);
    $pdf->Cell(10,6,utf8_decode('Nº Tec. HW'));

    $pdf->SetXY(150,130);
    $pdf->Cell(10,12,utf8_decode('Horas IT'));

    $pdf->SetXY(175,130);
    $pdf->Cell(10,12,utf8_decode('Horas HW'));

    $pdf->SetXY(10,155);
    $pdf->Cell(10,10,utf8_decode('H. Inicio'));

    $pdf->SetXY(30,155);
    $pdf->Cell(10,10,utf8_decode('H. Fin'));

    $pdf->SetXY(150,145);
    $pdf->Cell(10,6,utf8_decode('Nº Tec. IT'));

    $pdf->SetXY(175,145);
    $pdf->Cell(10,6,utf8_decode('Nº Tec. HW'));

    $pdf->SetXY(150,155);
    $pdf->Cell(10,10,utf8_decode('Horas IT'));

    $pdf->SetXY(175,155);
    $pdf->Cell(10,10,utf8_decode('Horas HW'));

    //horizontally
    $pdf->Rect(10,90,190,8);
    $pdf->Rect(10,90,190,30);
    $pdf->Rect(10,90,190,55);

    //vertically
    $pdf->Rect(10,90,35,80);
    $pdf->Rect(10,90,135,80);

    //Barra separación datos del cliente y tipo de intervención
    $pdf->Rect(10,40,80,8);
    $pdf->Rect(100,40,80,8);
    $pdf->Rect(100,67,80,8);

    //Barra separación horas/técnico
    $pdf->Rect(10,98,162,72);

    //Separaciones H.Inicio/H.Fin
    $pdf->Rect(10,107,17,13);
    $pdf->Rect(10,107,35,13);

    $pdf->Rect(10,132,17,13);
    $pdf->Rect(10,132,35,13);

    $pdf->Rect(10,157,17,13);
    $pdf->Rect(10,157,35,13);

    //H.Inicio y H.Fin encuadradas
    $pdf->Rect(10,107,17,5);
    $pdf->Rect(10,107,35,5);

    $pdf->Rect(10,132,17,5);
    $pdf->Rect(10,132,35,5);

    $pdf->Rect(10,157,17,5);
    $pdf->Rect(10,157,35,5);

    //Nº Tec. IT y Nº Tec HW encuadradas
    $pdf->Rect(145,98,27,5);
    $pdf->Rect(172,98,28,5);

    $pdf->Rect(145,120,27,5);
    $pdf->Rect(172,120,28,5);

    $pdf->Rect(145,145,27,5);
    $pdf->Rect(172,145,28,5);

    //Horas IT y Horas HW encuadradas
    $pdf->Rect(145,109,27,5);
    $pdf->Rect(172,109,28,5);

    $pdf->Rect(145,133,27,5);
    $pdf->Rect(172,133,28,5);

    $pdf->Rect(145,157,27,5);
    $pdf->Rect(172,157,28,5);

    //Para obtener los materiales del contrato
    //$consulta = "SELECT * FROM ".MAIN_DB_PREFIX."product p INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos_equipos mce ON p.rowid = mce.fk_product WHERE mce.fk_contract = ".$dataHeaderCard->mc_rowid;

    //$consulta = "SELECT DISTINCT p.ref, p.label, p.description, pom.quantity FROM ".MAIN_DB_PREFIX."product p INNER JOIN ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ON p.rowid = pom.fk_product INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_contratos mc ON mc.project_id = pom.fk_project WHERE pom.fk_project = ".$dataHeaderCard->idproyecto." AND mc.rowid = ".$dataHeaderCard->mc_rowid;
    $consulta = "SELECT p.ref, p.label, mis.quantity FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mis.fk_report = mi.rowid INNER JOIN ".MAIN_DB_PREFIX."product p ON mis.fk_product = p.rowid WHERE mis.is_retired = 0 AND mis.is_returned = 0 AND mis.is_future = 0 AND mi.rowid = ".$dataHeaderCard->infid;

    $resultConsulta = $db->query($consulta);

    $numFilas = $db->num_rows($resultConsulta);

    //MATERIALES EMPLEADOS
    $i = 0;
    $pdf->SetXY(85,172);
    $pdf->Cell(10,10,utf8_decode('Materiales empleados'));
    
    $pdf->Rect(10,174,190,6);
    $pdf->Rect(10,174,190,30);

    //Separación columnas
    $pdf->Rect(10,180,18,24);
    $pdf->Rect(105,180,18,24);

    $pdf->SetFont('Arial','',8);

    $contador = 0;
    $y = 180;
    while ($producto = $db->fetch_object($resultConsulta)) {
        if ($contador <= 3) {
            $x = 15;
            $pdf->SetXY($x,$y-3);
            $pdf->Cell(10,10,utf8_decode($producto->quantity));
            $x+= 15;
            $pdf->SetXY($x,$y+1);
            $pdf->MultiCell(70,3,utf8_decode($producto->ref." - ".$producto->label));
            $y+= 6;
        } else {
            if ($contador == 4) {
                $y = 180;
            }
            $x = 110;
            $pdf->SetXY($x,$y);
            $pdf->Cell(10,10,utf8_decode($producto->quantity));
            $x+= 15;
            $pdf->SetXY($x,$y);
            $pdf->MultiCell(70,3,utf8_decode($producto->ref." - ".$producto->label));
            $y+= 6;
        }
        $contador++;
    }


    $consultaRetirados = "SELECT p.ref, p.label, mis.quantity FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mis.fk_report = mi.rowid INNER JOIN ".MAIN_DB_PREFIX."product p ON mis.fk_product = p.rowid WHERE mis.is_retired = 1 AND mis.is_returned = 0 AND mi.rowid = ".$dataHeaderCard->infid;
    $resultConsultaRetirados = $db->query($consultaRetirados);
    $numFilas = $db->num_rows($resultConsultaRetirados);

    $consultaDevueltos = "SELECT p.ref, p.label, mis.quantity FROM ".MAIN_DB_PREFIX."mantenimiento_informes_sustituciones mis INNER JOIN ".MAIN_DB_PREFIX."mantenimiento_informes mi ON mis.fk_report = mi.rowid INNER JOIN ".MAIN_DB_PREFIX."product p ON mis.fk_product = p.rowid WHERE mis.is_retired = 0 AND mis.is_returned = 1 AND mi.rowid = ".$dataHeaderCard->infid;
    $resultConsultaDevueltos = $db->query($consultaDevueltos);
    $numFilas = $db->num_rows($resultConsultaDevueltos);

    //MATERIALES RETIRADOS Y MATERIALES DEVUELTOS
    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(40,206);
    $pdf->Cell(10,10,utf8_decode('Materiales retirados'));
    $pdf->SetXY(135,206);
    $pdf->Cell(10,10,utf8_decode('Materiales devueltos'));
    $pdf->Rect(10,208,190,6);
    $pdf->Rect(10,208,190,30);

    //Separación columnas
    $pdf->Rect(10,214,18,24);

    $pdf->SetFont('Arial','',8);
    $contador = 0;
    $y = 215;
    while ($retirado = $db->fetch_object($resultConsultaRetirados)) {

        $x = 15;
        $pdf->SetXY($x,$y-3);
        $pdf->Cell(10,10,utf8_decode($retirado->quantity));
        $x+= 15;
        $pdf->SetXY($x,$y);
        $pdf->MultiCell(70,3,utf8_decode($retirado->ref." - ".$retirado->label));
        $y+= 7;

        $contador++;
    }

    $pdf->Rect(105,214,18,24);

    $contador = 0;
    $y = 215;
    while ($devuelto = $db->fetch_object($resultConsultaDevueltos)) {

        $x = 110;
        $pdf->SetXY($x,$y-3);
        $pdf->Cell(10,10,utf8_decode($devuelto->quantity));
        $x+= 15;
        $pdf->SetXY($x,$y);
        $pdf->MultiCell(70,3,utf8_decode($devuelto->ref." - ".$devuelto->label));
        $y+= 7;

        $contador++;
    }

    $pdf->Rect(105,208,0,24);

    //OBSERVACIONES
    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(90,240);
    $pdf->Cell(10,10,utf8_decode('Observaciones'));
    $pdf->Rect(10,242,190,6);
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(9, 248);
    $pdf->MultiCell(190,5,utf8_decode($dataHeaderCard->mi_obs));

    //FIRMAS
    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY(40,266);
    $pdf->Cell(10,10,utf8_decode('Por parte de ORTRAT'));
    $pdf->SetXY(130,266);
    $pdf->Cell(10,10,utf8_decode('Por parte del CLIENTE'));

    $name = 'Factura_'.$id.'.pdf';

    $pdf->Output('I',$name,true);

}

?>