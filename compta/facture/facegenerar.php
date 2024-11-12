<?php

    require __DIR__ . '/../../vendor/autoload.php';

    require '../../main.inc.php';
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
    $idFactura = $_GET["facid"];

    use josemmo\Facturae\Facturae;
    use josemmo\Facturae\FacturaeParty;
    use josemmo\Facturae\FacturaeFile;
    use josemmo\Facturae\Face\FaceClient;
    use josemmo\Facturae\Common\FacturaeSigner;
    use RuntimeException;

    // Creamos la factura
    $fac = new Facturae();

    //Para los datos generales de la factura
    $sqlFactura = " SELECT f.*, f.rowid as facturaid, s.* FROM ".MAIN_DB_PREFIX."facture f ";
    $sqlFactura.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.fk_soc ";
    $sqlFactura.= " WHERE f.rowid = ".$idFactura." ";

    $resultFactura = $db->query($sqlFactura);
    $factura = $db->fetch_object($resultFactura);

    //Para la provincia
    if ($factura->fk_departement != "") {
        $sqlProvincia = " SELECT nom FROM ".MAIN_DB_PREFIX."c_departements ";
        $sqlProvincia.= " WHERE rowid = ".$factura->fk_departement." ";

        $resultProvincia = $db->query($sqlProvincia);
        $provincia = $db->fetch_object($resultProvincia);
        $provincia = $provincia->nom;
    } else {
        $provincia = "";
    }

    //Para los productos de la factura
    $sqlProductos = " SELECT * FROM ".MAIN_DB_PREFIX."facturedet ";
    $sqlProductos.= " WHERE fk_facture = ".$factura->facturaid." ";

    $resultProductos = $db->query($sqlProductos);

    //PARA EL CÓDIGO DE FACTURA
    $codigoFactura = $factura->ref;
    $partes = explode('-', $codigoFactura);

    // Asignamos el número EMP2017120003 a la factura
    // Nótese que Facturae debe recibir el lote y el
    // número separados
    $fac->setNumber($partes[0], $partes[1]);

    // Asignamos el 01/12/2017 como fecha de la factura
    $fechaFactura = $factura->datef;
    $fac->setIssueDate($fechaFactura);

    // Incluimos los datos del vendedor
    // DATOS DELTANET
    $fac->setSeller(new FacturaeParty([
    "taxNumber" => "A00000000",
    "name"      => "DELTANET SI",
    "address"   => "C/Luxemburgo. 2B",
    "postCode"  => "13005",
    "town"      => "Ciudad Real",
    "province"  => "Ciudad Real"
    ]));

    // Incluimos los datos del comprador,
    // ISLEGALENTITY = TRUE, para empresas
    // ISLEGALENTITY = FALSE, para personas físicas
    $fac->setBuyer(new FacturaeParty([
    "isLegalEntity" => true,
    "taxNumber"     => $factura->siren,
    "name"          => $factura->nom,
    /*"firstSurname"  => "García",
    "lastSurname"   => "Pérez",*/
    "address"       => $factura->address,
    "postCode"      => $factura->zip,
    "town"          => $factura->town,
    "province"      => $provincia
    ]));

    // Añadimos los productos a incluir en la factura
    // precio unitario, IVA ya incluído
    while ($pro = $db->fetch_object($resultProductos)) {
        //$fac->addItem(".$pro->description.", $pro->total_ht, $pro->qty, Facturae::TAX_IVA, 21);
        //print $pro->total_ttc;
        //die;
        $fac->addItem($pro->description, $pro->total_ttc, 2, Facturae::TAX_IVA, $pro->tva_tx);
    }

    // PARA FIRMAR LA FACTURA
    /*$fac->sign("ruta/hacia/banco-de-certificados.p12", null, "passphrase");*/


    // ESTA LINEA DE ABAJO ES PARA SACAR LA FACTURA COMO ARCHIVO
    //$fac->export("FACTURA-".$factura->ref."2.xsig");


    //PARA FIRMAR UN DOCUMENTO XML ---------------------------------------------------------------------------------
    // Creación y configuración de la instancia
    $signer = new FacturaeSigner();
    $signer->loadPkcs12("certificado.pfx", "passphrase");
    $signer->setTimestampServer("https://www.safestamper.com/tsa", "usuario", "contraseña");

    // Firma electrónica
    $xml = file_get_contents(__DIR__ . "/FACTURA-FA2311-00322.xsig");
    try {
    $signedXml = $signer->sign($xml);
    } catch (RuntimeException $e) {
    // Fallo al firmar
    }

    // Sellado de tiempo
    try {
    $timestampedXml = $signer->timestamp($signedXml);
    } catch (RuntimeException $e) {
    // Fallo al añadir sello de tiempo
    }
    file_put_contents(__DIR__ . "/factura.xsig", $timestampedXml);
    // -------------------------------------------------------------------------------------------------------------

    // ESTAS LÍNEAS DE ABAJO SON PARA ESTABLECER CONEXIÓN CON FACE Y SUBIR LA FACTURA
    // Cargamos la factura en una instancia de FacturaeFile
    // ESTAMOS CARGANDO LA FACTURA CREADA ANTERIORMENTE
    $fact = new FacturaeFile();
    $fact->loadData($fac->export(), "prueba-factura-face.xsig");

    // Creamos una conexión con FACe
    $face = new FaceClient("rutaparacertificado.pfx", null, "passphrase");
    //$face->setProduction(false); // Descomentar esta línea para entorno de desarrollo

    // Subimos la factura a FACe
    // El correo será al que llegue la notificación con el resultado del envío de la factura
    // fact es la factura que se sube
    $res = $face->sendInvoice("email-de-notificaciones@email.com", $fact);
    // Verificamos si se ha subido correctamente
    if ($res->resultado->codigo == 0) {
        // Si el resultado es 0, la factura ha sido aceptada
        echo "Número de registro => " . $res->factura->numeroRegistro . "\n";
    } else {
        // De lo contrario, FACe ha rechazado la factura
    }

?>