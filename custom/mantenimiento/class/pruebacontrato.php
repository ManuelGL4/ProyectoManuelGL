<?php
include_once("curl.php");
require '../../../main.inc.php';

$products = [];
$produitParam = [];

$apiKey =$user->api_key;
$url = "https://erp.ortrat.es/api/index.php/mantenimientoapi/";
$busqueda="contratos";
$apiUrl = $url.$busqueda;
echo $apiKey;
echo "<br> $apiUrl";
echo "<br> ";

$listProduitsResult = CallAPI("GET", $apiKey, $apiUrl, $produitParam);

$listProduitsResult = json_decode($listProduitsResult, true);


    foreach ($listProduitsResult as $produit) {
        $product = [
            "rowid" => $produit["rowid"],
            "ref" => $produit["ref"],
            "order_number" => $produit["order_number"],
            "name" => $produit["name"],
            "description" => $produit["description"],
            "project_id" => $produit["project_id"],
            "representative_id" => $produit["representative_id"],
            "representative_commission" => $produit["representative_commission"],
            "offer_id" => $produit["offer_id"],
            "offer_date" => $produit["offer_date"],
            "offer_type" => $produit["offer_type"],
            "contact_discount" => $produit["contact_discount"],
            "spare_parts_discount" => $produit["spare_parts_discount"],
            "client_id" => $produit["client_id"],
            "client_discount" => $produit["client_discount"],
            "client_authorization" => $produit["client_authorization"],
            "client_same_facture" => $produit["client_same_facture"],
            "contact_id" => $produit["contact_id"],
            "currency" => $produit["currency"],
            "languaje" => $produit["languaje"],
            "delegation_id" => $produit["delegation_id"],
            "delegation_id_accountant" => $produit["delegation_id_accountant"],
            "delegation_id_manager" => $produit["delegation_id_manager"],
            "delegation_id_processing" => $produit["delegation_id_processing"],
            "payment_method" => $produit["payment_method"],
            "expirations" => $produit["expirations"],
            "periodicity" => $produit["periodicity"],
            "periodicity_select" => $produit["periodicity_select"],
            "date_start" => $produit["date_start"],
            "date_end" => $produit["date_end"],
            "warranty_end" => $produit["warranty_end"],
            "estimated_time" => $produit["estimated_time"],
            "estimated_anual_time" => $produit["estimated_anual_time"],
            "ejercicio" => $produit["ejercicio"],
            "numero" => $produit["numero"],
            "estado_homologacion" => $produit["estado_homologacion"],
            "id_regimen_iva" => $produit["id_regimen_iva"],
            "ref_anterior" => $produit["ref_anterior"],
            "garantia" => $produit["garantia"],
            "porc_resolucion" => $produit["porc_resolucion"],
            "plazo_entrega" => $produit["plazo_entrega"],
            "ref_proyecto" => $produit["ref_proyecto"],
            "id_contrato_padre" => $produit["id_contrato_padre"],
            "id_estado" => $produit["id_estado"],
            "usuario" => $produit["usuario"],
            "fecha" => $produit["fecha"],
            "dto_general" => $produit["dto_general"],
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
