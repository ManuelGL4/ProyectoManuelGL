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

require __DIR__."/../../vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id = $_POST['facid'];

    $tmp = $_FILES['invoice']['tmp_name'];

    $file = file_get_contents($tmp);

    $encodeFile = base64_encode($file);

    $postParameter = [
        'invoiceFile' => [
            'mime' => 'text/xml',
            'content' => $encodeFile,
            'name' => $_FILES['invoice']['name'],
        ]
    ];

    $privateKeyFile = __DIR__.'/deltanet-public.pem';

    $pub_key = openssl_pkey_get_public(file_get_contents($privateKeyFile));
    $keyData = openssl_pkey_get_details($pub_key);
    var_dump($keyData['key']);die;

// $privateKey = <<<EOD
// -----BEGIN RSA PRIVATE KEY-----
// -----END RSA PRIVATE KEY-----
// EOD;
     
    // // Create a private key of type "resource"
    // $privateKey = openssl_pkey_get_private(
    //     file_get_contents($privateKeyFile),
    //     $passphrase
    //);

    $payload = [
        'iat' => strtotime('now'),
        'exp' => strtotime('now +5 minute')
    ];

    // $header = [
    //     "x5c"=> "MIIF/TCCBOWgAwIBAgIQUj5ofy7TYXhWsKBZVz6lxDANBgkqhkiG9w0BAQsFADBHMQswCQYDVQQGEwJFUzERMA8GA1UECgwIRk5NVC1SQ00xJTAjBgNVBAsMHEFDIENvbXBvbmVudGVzIEluZm9ybcOhdGljb3MwHhcNMTYwMjAyMTIyNjAxWhcNMTkwMjAyMTIyNTU5WjCB2DELMAkGA1UEBhMCRVMxDzANBgNVBAcMBk1BRFJJRDE8MDoGA1UECgwzTUlOSVNURVJJTyBERSBIQUNJRU5EQSBZIEFETUlOSVNUUkFDSU9ORVMgUMOaQkxJQ0FTMUswSQYDVQQLDEJESVJFQ0NJw5NOIERFIFRFQ05PTE9Hw41BUyBERSBMQSBJTkZPUk1BQ0nDk04gWSBMQVMgQ09NVU5JQ0FDSU9ORVMxEjAQBgNVBAUTCVMyODMzMDAyRTEZMBcGA1UEAwwQRFRJQyBBR0UgUFJVRUJBUzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALBcouy5wk1P1Lwq38b+mVbZfoqskPBepawieHarQ1NrkJJV+hIYOngGX/4DdpoUKr/ezAqrNiu0mH1WxPI+eRLse1loUbjwQTgxnJI9QP0v79L6g0UqLyFcwyy7/dIxVkJUIq7qPHXbjvlgu5fCw6uB8h0EQ2JlrpKfqtdkh+ipDmUfinageM11sMXEebS+YxO0iiqK0WgHPG27dSzd0Tfo2SKQ/XHsguTtrIoV4kktGhkb7IEpO8+G8QzHd347HiQAy/MruzeLAJjaBhcYzkCmMFw5xWc7k6PB0S82heFB6RN+/SyPFj1QDUlZoVOuo4dfgFUZLCVBfMUBY73WNazVAojqZhG9d8tAgg2c64nusuMDY+25MLUKFzsbzFg=="
    // ];    
   
    $jwt = JWT::encode($payload, $privateKey, 'RS256', null, null);
    echo "Encode:\n" . print_r($jwt, true) . "\n";die;

    $postHeader = [
        'Content-Type' => 'application/json',
        'token' => $jwt
    ];
    die;

    $curlHandle = curl_init('https://st-faceb2b.gob.es/api/invoice');
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postParameter);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $postHeader); 

    $curlResponse = json_decode( curl_exec($curlHandle), true );
    
    $info = curl_getinfo($curlHandle,CURLINFO_RESPONSE_CODE);

    $message = ($info == "200") ? "Success" : "Error";
    
    echo $message;

    curl_close($curlHandle);

    header('Location: card.php?facid=' . $id . '');
}

ob_flush();
