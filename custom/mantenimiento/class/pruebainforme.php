<?php
include_once("curl.php");
require '../../../main.inc.php';

$products = [];
$produitParam = [];

$apiKey =$user->api_key;
$url = "https://erp.ortrat.es/api/index.php/mantenimientoapi/";
$busqueda="informes/contratos/";
$idContract="2";
$apiUrl = $url.$busqueda.$idContract;
echo $apiKey;
echo "<br> $apiUrl";
echo "<br> ";

$listProduitsResult = CallAPI("GET", $apiKey, $apiUrl, $produitParam);

$listProduitsResult = json_decode($listProduitsResult, true);


    foreach ($listProduitsResult as $produit) {
        $product = [
            "rowid" => $produit["rowid"],
            "ref" => $produit["ref"],
            "description" => $produit["description"],
            "technician_id" => $produit["technician_id"],
            "last_technician_id" => $produit["last_technician_id"],
            "storage_id" => $produit["storage_id"],
            "maintenance_date" => $produit["maintenance_date"],
            "real_date" => $produit["real_date"],
            "contract_id" => $produit["contract_id"],
            "observations" => $produit["observations"],
            "hours_spent" => $produit["hours_spent"],
            "start_date" => $produit["start_date"],
            "end_date" => $produit["end_date"],
            "futures_inherited" => $produit["futures_inherited"],
            "id_fase" => $produit["id_fase"],
            "id_khonos" => $produit["id_khonos"],
            "note_public" => $produit["note_public"],
            "note_private" => $produit["note_private"],
            "date_creation" => $produit["date_creation"],
            "tms" => $produit["tms"],
            "fk_user_creat" => $produit["fk_user_creat"],
            "fk_user_modif" => $produit["fk_user_modif"],
            "last_main_doc" => $produit["last_main_doc"],
            "import_key" => $produit["import_key"],
            "model_pdf" => $produit["model_pdf"],
            "status" => $produit["status"]
        ];
        
        
        $products[] = $product;
    }

echo json_encode($products, JSON_PRETTY_PRINT);




?>
