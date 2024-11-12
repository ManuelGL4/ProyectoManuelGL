<?php
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


require_once('model/factura.php');
require_once('model/servicio.php');
require_once('model/entidad.php');
require_once('model/pais.php');
require_once('model/empresa.php');
require_once('model/departamento.php');
require_once('model/delegacion.php');
require_once('model/daoFactura.php');
require_once("../../DB/bbdd.php");
   
$daoFactura=new daoFactura(dbname, user, host, pass);

$idFactura=$_GET["facid"];

//Modelos
$factura=$daoFactura->obtenerFactura($idFactura);
$daoFactura->obtenerExtrasFactura($factura);
$servicios=$daoFactura->obtenerServiciosFactura($idFactura);
$entidad=$daoFactura->obtenerEntidadFactura($factura->__get("id_sociedad"));
$delegacionesEntidad=$daoFactura->obtenerDelegacionesEntidadFactura($factura->__get("id_sociedad"));
$paisEntidad=$daoFactura->obtenerPaisEntidadFactura($entidad->__get("pais"));
$departamentoEntidad=$daoFactura->obtenerDepartamentoEntidadFactura($entidad->__get("id_departamento"));
$empresa=$daoFactura->obtenerEmpresaFactura();

//Variables
//Bruto=Resultante sin aplicar descuentos ni impuestos
foreach ($servicios as $key => $servicio) {

    $porcentajeDescuento=$servicio->__get("porcentaje_descuento")/100;
    $precioBase=$servicio->__get("precio_base");
    $totalConDescuento=$servicio->__get("total_con_descuento");
    $cantidadServicio=$servicio->__get("cantidad_servicio");

    $cantidadDescuento=$porcentajeDescuento*($precioBase*$cantidadServicio);
    //$TotalGeneralDiscounts+=$cantidadDescuento;
    
    $TaxAmount+=number_format($servicio->__get("iva_total"), 2, '.', '');//Cuota. Importe resultante de aplicar a la Base Imponible el porcentaje correspondiente
    $Amount+=$servicio->__get("total_con_descuento");//$cantidadServicio*$precioBase +ChargeAmount? DUDA
}

$TotalGrossAmount=number_format($Amount, 2, '.', '');

//$TotalGeneralDiscounts=number_format($TotalGeneralDiscounts, 2, '.', '');
$TotalGeneralDiscounts=0.00;//Total descuentos globales sobre la factura obtenida del Tercero

$TotalTaxOutputs=$TaxAmount;
$TotalTaxOutputs=number_format($TotalTaxOutputs,'2','.','');

//Mal?Incluir campos GeneralDiscounts o no hacen?
$TotalGeneralSurcharge=0.00;//Cargos sobre el Total Importe Bruto
$TotalGrossAmountBeforeTaxes=$TotalGrossAmount+$TotalGeneralSurcharge-$TotalGeneralDiscounts;//
$TotalGrossAmountBeforeTaxes=number_format($TotalGrossAmountBeforeTaxes,'2','.','');

$TotalTaxesWithheld=0.00;//$TaxAmount
$TotalTaxesWithheld=number_format($TotalTaxesWithheld,'2','.','');

$TotalInvoicesAmount=$TotalGrossAmountBeforeTaxes+$TotalTaxOutputs-$TotalTaxesWithheld;
$TotalInvoicesAmount=number_format($TotalInvoicesAmount,'2','.','');

$InvoiceTotal=$TotalGrossAmountBeforeTaxes+$TotalTaxOutputs-$TotalTaxesWithheld;
$InvoiceTotal=number_format($InvoiceTotal,'2','.','');

$PaymentOnAccountAmount=0.00;//Importe de cada anticipo

$TotalPaymentsOnAccount=$PaymentOnAccountAmount;

$SubsidyAmount=0.00;//Importe a aplicar al Total Factura

$TotalOutstandingAmount=$InvoiceTotal-($SubsidyAmount+$TotalPaymentsOnAccount);
$TotalOutstandingAmount=number_format($TotalOutstandingAmount,'2','.','');

$percentageHoldingAmount=$factura->__get("porcentaje_retencion")/100;
$WithholdingReason=$factura->__get("motivo_retencion");
$InvoiceAdditionalInformation=$factura->__get("informacion_adicional");

$WithholdingAmount=$percentageHoldingAmount*($TotalGrossAmountBeforeTaxes);
$WithholdingAmount=number_format($WithholdingAmount,'2','.','');
//$WithholdingAmount=0.00;//Importe a retener sobre el Total a Pagar

$TotalExecutableAmount=$TotalOutstandingAmount-$WithholdingAmount;//Sumatorio de las diferencias
$TotalExecutableAmount=number_format($TotalExecutableAmount,'2','.','');

$xml = new XMLWriter();
$xml->openMemory();
$xml->setIndent(true);
$xml->setIndentString('	'); 
$xml->startDocument('1.0', 'UTF-8');
$xml->startElement("namespace:Facturae");
$xml->writeAttribute('xmlns:namespace', 'http://www.facturae.es/Facturae/2009/v3.2/Facturae');
//$xml->writeAttribute('xmlns:namespace2', 'http://uri.etsi.org/01903/v1.2.2#');
//$xml->writeAttribute('xmlns:namespace3', 'http://www.w3.org/2000/09/xmldsig#');//http://www.w3.org/2000/09/xmldsig#
    $xml->startElement("FileHeader");
        $xml->writeElement("SchemaVersion", "3.2");
        $xml->writeElement("Modality", "I");//Individual o Lote, Si es "lote" (L), el valor del campo InvoicesCount será siempre > "1".
        $xml->writeElement("InvoiceIssuerType", "EM"); //EM,RE y TE emisor,receptor y tercero, si es tercero el grupo ThirdParty será obligatorio cumplimentarlo en todos sus apartados.
        // $xml->startElement("ThirdParty");
        //     $xml->startElement("TaxIdentification");
        //         $xml->writeElement("PersonTypeCode", "J"); //F o J, fisica o juridica
        //         $xml->writeElement("ResidenceTypeCode", "U"); //E,R,U , extranjero, residente, rsidente de la union europea
        //         $xml->writeElement("TaxIdentificationNumber", "A2800056F");//NIF
        //     $xml->endElement(); 
        //     $xml->startElement("LegalEntity");//La entidad de base de datos asociada a la factura sustituye LegalEntity
        //         $xml->writeElement("CorporateName", "Sociedad Anonima S. A.");//Razon social
        //         $xml->startElement("AddressInSpain");
        //             $xml->writeElement("Address", $empresa->__get("direccion"));
        //             $xml->writeElement("PostCode", $empresa->__get("codigo_postal"));
        //             $xml->writeElement("Town", $empresa->__get("ciudad"));
        //             $xml->writeElement("Province", $empresa->__get("provincia"));
        //             $xml->writeElement("CountryCode", $empresa->__get("codigo_pais"));
        //         $xml->endElement();
        //     $xml->endElement();
        // $xml->endElement();
        $xml->startElement("Batch");
            $xml->writeElement("BatchIdentifier", $factura->__get('ref'));//Identificador lote Ej:A2800056FBX-375-09
            $xml->writeElement("InvoicesCount", "1");//Numero de facturas
            $xml->startElement("TotalInvoicesAmount");//Total facturas. Suma de los importes 3.1.5.9 del Fichero.Este importe lo es a efectos de total de factura y fiscales, sin tener en cuenta subvenciones, anticipos y/o retenciones que pudieran haberse practicado. 
                $xml->writeElement("TotalAmount", $TotalInvoicesAmount);//total facturas
            $xml->endElement(); 
            $xml->startElement("TotalOutstandingAmount");//Total a pagar. Suma de los importes 3.1.5.13 del Fichero, con dos decimales.Es el importe que efectivamente se adeuda, una vez descontadoslos anticipos y sin tener en cuenta las retenciones.
                $xml->writeElement("TotalAmount", $TotalOutstandingAmount);
            $xml->endElement(); 
            $xml->startElement("TotalExecutableAmount"); //Total a Ejecutar. Sumatorio de las diferencias de los importes (3.1.5.13 y 3.1.5.14.3) del fichero = Sumatorio de los Importes 3.1.5.15, con dos decimales.
                $xml->writeElement("TotalAmount", $TotalExecutableAmount);
            $xml->endElement(); 
            $xml->writeElement("InvoiceCurrencyCode", "EUR");
        $xml->endElement(); 
    $xml->endElement(); 
    $xml->startElement("Parties");
        $xml->startElement("SellerParty");//Datos fijos de la empresa en este caso Khonos?
            $xml->startElement("TaxIdentification");
                $xml->writeElement("PersonTypeCode", $empresa->__get("tipoEmpresa")); //F o J, fisica o juridica
                $xml->writeElement("ResidenceTypeCode", "U"); //E,R,U , extranjero, residente, rsidente de la union europea
                $xml->writeElement("TaxIdentificationNumber", $empresa->__get("nif"));//NIF
            $xml->endElement(); 
            $xml->startElement("LegalEntity");//La entidad de base de datos asociada a la factura sustituye LegalEntity
                $xml->writeElement("CorporateName", $empresa->__get("nombre"));//Razon social
                $xml->startElement("AddressInSpain");
                    $xml->writeElement("Address", $empresa->__get("direccion"));
                    $xml->writeElement("PostCode", $empresa->__get("codigo_postal"));
                    $xml->writeElement("Town", $empresa->__get("ciudad"));
                    $xml->writeElement("Province", $empresa->__get("provincia"));
                    $xml->writeElement("CountryCode", $empresa->__get("codigo_pais"));
                $xml->endElement();
                $xml->startElement("ContactDetails");
                    $xml->writeElement("WebAddress", $empresa->__get("web"));
                    $xml->writeElement("ElectronicMail", $empresa->__get("email"));
                $xml->endElement();
            $xml->endElement();
        $xml->endElement(); 
        $xml->startElement("BuyerParty");
            $xml->startElement("TaxIdentification");
                $xml->writeElement("PersonTypeCode", $entidad->__get("tipoEntidad"));
                $xml->writeElement("ResidenceTypeCode", "U");
                $xml->writeElement("TaxIdentificationNumber", $entidad->__get("nif"));
            $xml->endElement();
            if (count($delegacionesEntidad)>0) {

                $xml->startElement("AdministrativeCentres");
                
                    foreach ($delegacionesEntidad as $key => $delegacion) {

                        $idProvincia=$delegacion->__get("provincia");
                        $provinciaDelegacion=$daoFactura->obtenerDepartamentoEntidadFactura($idProvincia);

                        $idPais=$delegacion->__get("pais");
                        $paisDelegacion=$daoFactura->obtenerPaisEntidadFactura($idPais);

                        $idTipoDelegacion=$delegacion->__get("fk_tipo_delegacion");
                        $valorDelegacion=$daoFactura->obtenerTipoDelegacion($idTipoDelegacion);
                        
                        $xml->startElement("AdministrativeCentre");
                            $xml->writeElement("CentreCode", $delegacion->__get("codigo_delegacion"));
                            $xml->writeElement("RoleTypeCode", $valorDelegacion);
                            $xml->writeElement("Name", $delegacion->__get("nombre"));
                            $xml->startElement("AddressInSpain");
                                $xml->writeElement("Address", $delegacion->__get("direccion"));
                                $xml->writeElement("PostCode", $delegacion->__get("codigo_postal"));
                                $xml->writeElement("Town", $delegacion->__get("localidad"));
                                $xml->writeElement("Province", $provinciaDelegacion->__get("provincia"));
                                $xml->writeElement("CountryCode", $paisDelegacion->__get("codigo_iso"));
                            $xml->endElement();
                        $xml->endElement();

                    }

                $xml->endElement();

            }
            $xml->startElement("LegalEntity");//La entidad de base de datos asociada a la factura
                $xml->writeElement("CorporateName", $entidad->__get("nombre"));
                $xml->startElement("AddressInSpain");
                    $xml->writeElement("Address", $entidad->__get("direccion"));
                    $xml->writeElement("PostCode", $entidad->__get("codigo_postal"));
                    $xml->writeElement("Town", $entidad->__get("ciudad"));
                    $xml->writeElement("Province", $departamentoEntidad->__get("provincia"));
                    $xml->writeElement("CountryCode", $paisEntidad->__get("codigo_iso"));
                $xml->endElement();
                $xml->startElement("ContactDetails");
                    $xml->writeElement("WebAddress",$entidad->__get("web"));
                    $xml->writeElement("ElectronicMail",$entidad->__get("email"));
                $xml->endElement();
            $xml->endElement();
        $xml->endElement();
    $xml->endElement(); 
    $xml->startElement("Invoices");
        $xml->startElement("Invoice");
            $xml->startElement("InvoiceHeader");
                $xml->writeElement("InvoiceNumber", $factura->__get("referencia"));
                $xml->writeElement("InvoiceDocumentType", "FC");//FC,FA,AF, factura completa,abreviada,autofactura
                $xml->writeElement("InvoiceClass", "OO");//OO,OR,OC,CO, original,rectificativa,original recapitulativa,copia de la original
            $xml->endElement();
            $xml->startElement("InvoiceIssueData");
                $xml->writeElement("IssueDate", $factura->__get("fecha_creacion"));//YYYY-mm-dd
                // $xml->startElement("InvoicingPeriod");//Cuando aparezca factura tipo OC o CC
                //     $xml->writeElement("StartDate","2021-11-14");
                //     $xml->writeElement("EndDate","2021-11-18");
                // $xml->endElement();
                $xml->writeElement("InvoiceCurrencyCode", "EUR"); //Si la moneda de la operación difiere de la moneda del impuesto (EURO), los campos del contravalor ExchangeRate y ExchangeRateDate, deberán cumplimentarse $xml->writeElement("TaxCurrencyCode", "EUR");
                $xml->writeElement("TaxCurrencyCode", "EUR");
                $xml->writeElement("LanguageName", "es");
            $xml->endElement();
            $xml->startElement("TaxesOutputs");//count($factura->__get("items"))
                foreach ($servicios as $key => $servicio) { 

                    $TaxRate=number_format($servicio->__get("porcentaje_iva"), 2, '.', '');
                    $TotalAmount=number_format($servicio->__get("total_con_descuento"), 2, '.', '');
                    $TaxAmount=number_format($servicio->__get("iva_total"), 2, '.', '');

                    $xml->startElement("Tax");
                        $xml->writeElement("TaxTypeCode", "01");//Clase de impuesto 01,02,03
                        $xml->writeElement("TaxRate", $TaxRate );//Porcentaje a aplicar en cada caso.
                        $xml->startElement("TaxableBase");//Base imponible = Total Importe Bruto - Cargos + Recargos - Descuentos Globales/factura, por cada clase y porcentaje.
                            $xml->writeElement("TotalAmount", $TotalAmount);//Importe en la moneda original de la facturación
                        $xml->endElement();
                        $xml->startElement("TaxAmount");//Cuota. Importe resultante de aplicar a la Base Imponible el porcentaje correspondiente.
                            $xml->writeElement("TotalAmount", $TaxAmount);
                        $xml->endElement();
                    $xml->endElement();
                }
            $xml->endElement();
            $xml->startElement("InvoiceTotals");
                $xml->writeElement("TotalGrossAmount", $TotalGrossAmount);//Suma total de importes brutos de los detalles de la factura
                // foreach ($servicios as $key => $servicio) {

                //     $precioBase=number_format($servicio->__get("precio_base"), 2, '.', '');
                //     $porcentajeDescuento=$servicio->__get("porcentaje_descuento")/100;
                //     $cantidadServicio=$servicio->__get("cantidad_servicio");
                //     $cantidadDescuento=$porcentajeDescuento*($precioBase*$cantidadServicio);

                //     $DiscountAmount=number_format($cantidadDescuento, 6, '.', '');
                //     $DiscountRate=number_format($cantidadDescuento, 4, '.', '');

                //     $xml->startElement("GeneralDiscounts");
                //         $xml->startElement("Discount");
                //             $xml->writeElement("DiscountReason","");
                //             $xml->writeElement("DiscountRate",$DiscountRate);
                //             $xml->writeElement("DiscountAmount",$DiscountAmount);
                //         $xml->endElement();
                //     $xml->endElement();
                // }
                $xml->writeElement("TotalGeneralDiscounts","0.00");//$TotalGeneralDiscounts
                $xml->writeElement("TotalGrossAmountBeforeTaxes", $TotalGrossAmountBeforeTaxes); //Total importe bruto antes de impuestos. Resultado: 3.1.5.1 - 3.1.5.4 + 3.1.5.5.
                $xml->writeElement("TotalTaxOutputs", $TotalTaxOutputs);//Total impuestos repercutidos. Sumatorio diferentes 3.1.3.1.4.
                $xml->writeElement("TotalTaxesWithheld", $TotalTaxesWithheld);//Total impuestos retenidos.Sumatorio diferentes 3.1.4.1.4. 
                $xml->writeElement("InvoiceTotal", $InvoiceTotal);//Total factura. Resultado: 3.1.5.6 + 3.1.5.7 - 3.1.5.8
                $xml->writeElement("TotalFinancialExpenses","0.00");//Total de gastos financieros. Siempre con dos decimales.
                $xml->writeElement("TotalOutstandingAmount", $TotalOutstandingAmount); //Total a pagar. Resultado: 3.1.5.9 - (3.1.5.10.1.3 + 3.1.5.12).
                $xml->writeElement("TotalPaymentsOnAccount","0.00");//Total de anticipos, Sumatorio de los campos PaymentOnAccountAmount.
                $xml->startElement("AmountsWithheld");
                    $xml->writeElement("WithholdingReason",$WithholdingReason);
                    $xml->writeElement("WithholdingAmount",$WithholdingAmount);
                $xml->endElement();
                $xml->writeElement("TotalExecutableAmount", $TotalExecutableAmount);//Total a ejecutar. Resultado: 3.1.5.13 - 3.1.5.14.3.
            $xml->endElement();
            $xml->startElement("Items");//count($factura->__get("items"))
                foreach ($servicios as $key => $servicio) { 

                    $precioBase=number_format($servicio->__get("precio_base"), 2, '.', '');
                    $totalDescuento=number_format($servicio->__get("total_con_descuento"), 6, '.', '');

                    $porcentajeDescuento=$servicio->__get("porcentaje_descuento")/100;
                    $cantidadServicio=$servicio->__get("cantidad_servicio");
                    $cantidadDescuento=$porcentajeDescuento*($precioBase*$cantidadServicio);

                    $DiscountAmount=number_format($cantidadDescuento, 6, '.', '');
                    $DiscountRate=number_format($servicio->__get("porcentaje_descuento"), 4, '.', '');
                    //$total=number_format($totalDescuento+$descuento, 2, '.', '');
                    //$total=number_format($totalDescuento+$cantidadDescuento, 2, '.', '');

                    $ItemDescription=$servicio->__get("descripcion");

                    $Quantity=number_format($servicio->__get("cantidad_servicio"), 2, '.', '');

                    //$UnitPriceWithoutTax=number_format($total, 6, '.', '');
                    $UnitPriceWithoutTax=number_format($precioBase, 6, '.', '');

                    $TotalCost=number_format($Quantity*$UnitPriceWithoutTax, 6, '.', '');

                    $GrossAmount=number_format($TotalCost-$cantidadDescuento, 6, '.', '');//Falta mas cosas en la operacion

                    $TaxableBase= number_format($totalDescuento, 2, '.', '');//Total Importe Bruto + Recargos - Descuentos Globales/factura, por cada clase y porcentaje
                    
                    $TaxRate=number_format($servicio->__get("porcentaje_iva"), 2, '.', '');
                    
                    $TaxAmount=number_format($servicio->__get("iva_total"), 2, '.', '');

                    $StartDate=$servicio->__get("fecha_comienzo");
                    $StartDate=substr($StartDate,0,10);

                    $EndDate=$servicio->__get("fecha_fin");
                    $EndDate=substr($EndDate,0,10);

                    $xml->startElement("InvoiceLine");
                        $xml->writeElement("IssuerContractReference", $factura->__get("referencia"));//Contrato
                        $xml->writeElement("IssuerContractDate", $factura->__get("fecha_creacion"));
                        //$xml->writeElement("IssuerTransactionReference", $factura->__get("referencia"));//Referencia Operación, Número de Pedido, Contrato, etc.Emisor
                        $xml->writeElement("ItemDescription", $ItemDescription);
                        $xml->writeElement("Quantity", $Quantity);
                        $xml->writeElement("UnitOfMeasure", "01");//Double?
                        $xml->writeElement("UnitPriceWithoutTax", $UnitPriceWithoutTax);//Precio sin IVA 6 decimales
                        $xml->writeElement("TotalCost", $TotalCost);
                        $xml->startElement("DiscountsAndRebates");
                            $xml->startElement("Discount");
                                $xml->writeElement("DiscountReason","");
                                $xml->writeElement("DiscountRate",$DiscountRate);
                                $xml->writeElement("DiscountAmount",$DiscountAmount);
                            $xml->endElement();
                        $xml->endElement();
                        //Mal?
                        $xml->writeElement("GrossAmount", $GrossAmount);//Resultado: TotalCost - DiscountAmount + ChargeAmount. 
                        $xml->startElement("TaxesOutputs");//Impuestos repercutidos. El elemento "importe total" de este bloque - nivel de detalle - se considerará sólo a efectos informativos
                            $xml->startElement("Tax");
                                $xml->writeElement("TaxTypeCode", "01");//Clase de impuesto
                                $xml->writeElement("TaxRate", $TaxRate);
                                $xml->startElement("TaxableBase");
                                    $xml->writeElement("TotalAmount", $TaxableBase);//Dos decimales
                                $xml->endElement();
                                $xml->startElement("TaxAmount");
                                    $xml->writeElement("TotalAmount", $TaxAmount);
                                $xml->endElement();
                            $xml->endElement();
                        $xml->endElement();
                        if ($StartDate!="" && $EndDate!="") {
                            $xml->startElement("LineItemPeriod");//- La fecha de emisión de la factura debe ser posterior a la fecha de validación del Acuerdo
                                $xml->writeElement("StartDate", $StartDate);
                                $xml->writeElement("EndDate", $EndDate);
                            $xml->endElement();
                        }
                        //$xml->writeElement("TransactionDate", $factura->__get("fecha_creacion"));//ISO 8601
                    $xml->endElement();
                }
            $xml->endElement();
            // $xml->startElement("PaymentDetails");
            //     $xml->startElement("Installment");//Vencimiento
            //         $xml->writeElement("InstallmentDueDate","2022-02-01");//Fechas en las que se deben atender los pagos
            //         $xml->writeElement("InstallmentAmount","0.00");//Importe a satisfacer en cada plazo. Siempre con dos decimales.
            //         $xml->writeElement("PaymentMeans","13");
            //         $xml->writeElement("CollectionAdditionalInformation","");//Observaciones de cobro
            //     $xml->endElement();
            // $xml->endElement();
            $xml->startElement("AdditionalData");//Deben poner ellos esta parte?
                $xml->writeElement("InvoiceAdditionalInformation",$InvoiceAdditionalInformation);//En este elemento se recogerá el motivo por lo que el impuesto correspondiente está "no sujeto" o "exento",cuando se produzca esta situación
            $xml->endElement();
        $xml->endElement();
    $xml->endElement();//Invoices
    /*
        Financiado por el Programa KIT Digital. Plan de
        Recuperación, Transformación y Resiliencia de España
        "Next Generation EU". IMPORTE SUBVENCIONADO:
        4000.00€

    */
    // $xml->startElement("ds:Signature");
    //     $xml->writeAttribute("Id","Signature");
    //     $xml->writeAttribute("xmlns:etsi","http://uri.etsi.org/01903/v1.2.2#");
    //     $xml->writeAttribute("xmlns:ds","http://uri.etsi.org/01903/v1.2.2#");
    //     $xml->startElement("ds:SignedInfo");
    //         $xml->writeAttribute("Id","Signature-SignedInfo");
    //         $xml->startElement("ds:CanonicalizationMethod");
    //             $xml->writeAttribute("Algorithm","http://www.w3.org/TR/2001/REC-xml-c14n-20010315");
    //         $xml->endElement();
    //         $xml->startElement("ds:SignatureMethod");
    //             $xml->writeAttribute("Algorithm","http://www.w3.org/2000/09/xmldsig#rsa-sha1");
    //         $xml->endElement();
    //         $xml->startElement("ds:Reference");
    //             $xml->writeAttribute("Id","SignedPropertiesId");
    //             $xml->writeAttribute("URI","#Signature-SignedProperties");
    //             $xml->writeAttribute("Type","http://uri.etsi.org/0193/v1.2.2#SignedProperties");
    //             $xml->startElement("ds:DigestMethod");
    //                 $xml->writeAttribute("Algorithm","http://www.w3.org.2000/09/xmldsig#sha1");
    //             $xml->endElement();
    //             $xml->writeElement("ds:DigestValue","E70IIZJgM5B3rTwGJ5b4hEeJ8N0=");
    //         $xml->endElement();
    //         $xml->startElement("ds:Reference");
    //             $xml->writeAttribute("URI","");
    //             $xml->startElement("ds:Transforms");
    //                 $xml->startElement("ds:Transform");
    //                     $xml->writeAttribute("Algorithm","http://www.w3.org/2000/09/xmldsig#enveloped-signature");
    //                 $xml->endElement();
    //             $xml->endElement();
    //             $xml->startElement("ds:DigestMethod");
    //                 $xml->writeAttribute("Algorithm","http://www.w3.org.2000/09/xmldsig#sha1");
    //             $xml->endElement();
    //             $xml->writeElement("ds:DigestValue","q54/ZNHSjMWKMD4A5xI9qL2tBOA=");
    //         $xml->endElement();
    //         $xml->startElement("ds:Reference");
    //             $xml->writeAttribute("URI","#Certificate1");
    //             $xml->startElement("ds:DigestMethod");
    //                 $xml->writeAttribute("Algorithm","http://www.w3.org.2000/09/xmldsig#sha1");
    //             $xml->endElement();
    //             $xml->writeElement("ds:DigestValue","njihA04aMjUOyc0gnw6mfxjsfv8=");
    //         $xml->endElement();
    //     $xml->endElement();
    //     $xml->startElement("ds:SignatureValue");
    //         $xml->writeAttribute("Id","SignatureValue");
    //         //algo falta
    //     $xml->endElement();
    //     $xml->startElement("ds:KeyInfo");
    //         $xml->writeAttribute("Id","Certificate1");
    //         $xml->startElement("ds:X509Data");
    //             $xml->writeElement("ds:X509Certificate", "MIID4DCCA0mgAwIBAgIBOjANBgkqhkiG9w0BAQUFADByMQswCQYDVQQGEwJFUzEPMA0GA1UECBMG TWFkcmlkMQ8wDQYDVQQHEwZNYWRyaWQxDjAMBgNVBAoTBU1JVHlDMRswGQYDVQQLExJNSVR5QyBE TkllIFBydWViYXMxFDASBgNVBAMTC0NBIHVzdWFyaW9zMB4XDTA3MTIxMTE2NDYyNVoXDTA4MTIx MDE2NDYyNVowfzELMAkGA1UEBhMCRVMxDzANBgNVBAgTBk1hZHJpZDEPMA0GA1UEBxMGTWFkcmlk MQ4wDAYDVQQKEwVNSVR5QzEbMBkGA1UECxMSTUlUeUMgRE5JZSBQcnVlYmFzMSEwHwYDVQQDExhV c3VhcmlvIGVqZW1wbG8gRmFjdHVyYUUwgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBALiUcVbT N077nqQ2H+NaoGoE27n9x2LArAfiQ+2J+O5xpX1j0SyqdUqcXNL4LK6/6GJWqj93mkHEf7c3SBXv q68bvfaUUBQSOIbPqUGjA4kkK9gc/bx5NdkgfqZShNs7ErZFQDNho3Q2u2XBGWBerov6pOTmrzjE +82vUYvIu+R9AgMBAAGjggF3MIIBczAJBgNVHRMEAjAAMAsGA1UdDwQEAwIF4DAdBgNVHQ4EFgQU 3tDPGV3C+DRtihXUKstMKGFp5zwwgZgGA1UdIwSBkDCBjYAU9aFqqHdPW7EEjKd+SPEOn8V2jxuh cqRwMG4xDzANBgNVBAgTBk1hZHJpZDEPMA0GA1UEBxMGTWFkcmlkMQ4wDAYDVQQKEwVNSVR5QzEb MBkGA1UECxMSTUlUeUMgRE5JZSBQcnVlYmFzMRAwDgYDVQQDEwdSb290IENBMQswCQYDVQQGEwJF U4IBAzAJBgNVHREEAjAAMDYGA1UdEgQvMC2GK2h0dHA6Ly9taW5pc3Rlci04amd4eTkubWl0eWMu YWdlL1BLSS9DQS5jcnQwPQYDVR0fBDYwNDAyoDCgLoYsaHR0cDovL21pbmlzdGVyLThqZ3h5OS5t aXR5Yy5hZ2UvUEtJL2NybC5jcmwwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMEMA0GCSqG SIb3DQEBBQUAA4GBAES/a/gimvoEe168IQbWORPJLh1tuTrjzB549XF0kpGDIuUzBqgeZq1HjYjA iPgErqxGdk2qVVfDjjiNS5J+S6j5MXTs7toij/qEtdZmQ9AUfYRNKsNVFkUUI9j1ies3wUEecfvt wmAAN12LtrNeBRc4GfTOOAeupFufFDjmI4gB");
    //         $xml->endElement();
    //         $xml->startElement("ds:keyValue");
    //             $xml->startElement("ds:RSAKeyValue");
    //                 $xml->writeElement("ds:Modulus","uJRxVtM3TvuepDYf41qgagTbuf3HYsCsB+JD7Yn47nGlfWPRLKp1Spxc0vgsrr/oYlaqP3eaQcR/ tzdIFe+rrxu99pRQFBI4hs+pQaMDiSQr2Bz9vHk12SB+plKE2zsStkVAM2GjdDa7ZcEZYF6ui/qk 5OavOMT7za9Ri8i75H0=");
    //                 $xml->writeElement("ds:Exponent","AQAB");
    //             $xml->endElement();
    //         $xml->endElement();
    //     $xml->endElement();
    //     $xml->startElement("ds:Object");
    //         $xml->writeAttribute("Id","Signature-Object");
    //         $xml->startElement("xades:QualifyingProperties");
    //             $xml->writeAttribute("xmlns:xades","http://uri.etsi.org/01903/v1.2.2#");
    //             $xml->writeAttribute("Target","#Signature");
    //             $xml->startElement("xades:SignedProperties");
    //                 $xml->writeAttribute("Id","SignedProperties");
    //                 $xml->startElement("xades:SignedSignatureProperties");
    //                     $xml->writeElement("xades:SigningTime", "2006-10-19T13:14:20+02:00");
    //                     $xml->startElement("xades:SignaturePolicyIdentifier");
    //                         $xml->startElement("xades:SignaturePolicyId");
    //                             $xml->startElement("xades:SigPolicyId");
    //                                 $xml->writeElement("xades:Identifier","http://www.facturae.es/politica de firma formato facturae/politica de firma formato facturae v3_0.pdf ");
    //                                 $xml->writeElement("xades:Description","Política de firma electrónica para facturación electrónica con formato Facturae");
    //                             $xml->endElement();
    //                             $xml->startElement("xades:SigPolicyHash");
    //                                 $xml->startElement("ds:DigestMethod", "");
    //                                      $xml->writeAttribute("Algorithm","http://www.w3.org/2000/09/xmldsig#sha1");
    //                                 $xml->endElement();
    //                                 $xml->writeElement("ds:DigestValue", "xmfh8D/Ec/hHeE1IB4zPd61zHIY=");
    //                             $xml->endElement();
    //                         $xml->endElement();
    //                     $xml->endElement();
    //                 $xml->endElement();
    //             $xml->endElement();
    //         $xml->endElement();  
    //     $xml->endElement();
    // $xml->endElement();//Signature
$xml->endElement();//Facturae        

$content = $xml->outputMemory();

ob_end_clean();
ob_start();
header('Content-Type: application/xml; charset=UTF-8');
header('Content-Encoding: UTF-8');
header("Content-Disposition: attachment;filename=factura.xml");
header('Expires: Jue, 21 Oct 2017 07:28:00 GMT');
header('Pragma: cache');
header('Cache-Control: private');
echo $content;

?>