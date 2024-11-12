<?php

ob_start();

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

$responseCodeMessages = [
    200 => "Factura enviada con exito",
    201 => "Factura creada con exito",
    401 => "Acceso no autorizado",
    403 => "Acceso olvidado",
    406 => "Factura no valida",
    422 => "Entidad no procesable/valida"//ya esta siendo utilizada
];

$API_KEY = "f2699f30d1733dca5d55ccfa30778fcdf49f4c80";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id = $_POST['facid'];

    $tmp = $_FILES['invoice']['tmp_name'];

    $file = file_get_contents($tmp);

    $encodeFile = base64_encode($file);
   
    $project = "1657";

    //create invoice from xml or json file
    $curlCreate = curl_init();

    curl_setopt_array($curlCreate, [
        CURLOPT_URL => "https://app-staging.b2brouter.net/projects/" . $project . "/invoices/import.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "data:text/xml;name=" . $_FILES['invoice']['name'] . ";base64," . $encodeFile,
        CURLOPT_HTTPHEADER => [
            "X-B2B-API-Key: ".$API_KEY, 
            "content-type: application/octet-stream"
        ],
    ]);
    
    $response = json_decode( curl_exec($curlCreate), true );
    //$err = curl_error($curlCreate);
    $responseCodeCreate = curl_getinfo($curlCreate, CURLINFO_RESPONSE_CODE);

    curl_close($curlCreate);
    
    if ($responseCodeCreate != 201) {
        error_log('ERROR AT CREATING INVOICE FACTURAE with response code:'.$responseCodeCreate);
        $message = isset($responseCodeMessages[$responseCodeSend]) ? $responseCodeMessages[$responseCodeSend] : 'Error inesperado';  
        header('Location: card.php?facid=' . $id . '&message='.$message.'');
    }

    //TODO recieve the id from the created invoice
    $invoiceId = $response['invoice']['id'];

    //send a registered invoice 
    $curlSend = curl_init();

    curl_setopt_array($curlSend, [
        CURLOPT_URL => "https://app-staging.b2brouter.net/invoices/send_invoice/".$invoiceId.".json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => [
            "X-B2B-API-Key: ".$API_KEY,
            "accept: application/xml"
        ],
    ]);

    $responseSend = json_decode( curl_exec($curlSend), true );
    //$err = curl_error($curl);
    $responseCodeSend = curl_getinfo($curlSend, CURLINFO_RESPONSE_CODE);

    if ( !in_array($responseCodeSend, [ 200, 204 ]) ) {
        error_log('ERROR SENDING INVOICE FACTURAE with response code:'.$responseCodeSend.' and message '.$responseSend['errors'][0]);
        $message = isset($responseCodeMessages[$responseCodeSend]) ? $responseCodeMessages[$responseCodeSend] : $responseSend['errors'][0];  
        header('Location: card.php?facid='.$id.'&message='.$message);
    }

    curl_close($curlSend);

    header('Location: card.php?facid='.$id.'&message='.$responseCodeMessages[$responseCodeSend]);
}

ob_flush();
