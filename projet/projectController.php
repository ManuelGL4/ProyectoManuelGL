<?php 

require '../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sqlCompound = " SELECT TRUNCATE(p.price,2) as price_product,";
	$sqlCompound.= " ( SELECT  FORMAT(SUM(p.price),2) as price_materials FROM ".MAIN_DB_PREFIX."bom_bom b";
	$sqlCompound.= " INNER JOIN ".MAIN_DB_PREFIX."bom_bomline bl ON bl.fk_bom = b.rowid";
	$sqlCompound.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = bl.fk_product";
	$sqlCompound.= " WHERE b.rowid =".$id;
	$sqlCompound.= " GROUP BY bl.fk_product ";
	$sqlCompound.= " ) as price_materials";
	$sqlCompound.= " FROM ".MAIN_DB_PREFIX."product p";
	$sqlCompound.= " WHERE p.rowid =".$id;
	
	$resultCompound = $db->query($sqlCompound);

    $priceProduct = 0.00;
    $priceMaterials = 0.00;

	if ($resultCompound) {

		while ($product = $db->fetch_object($resultCompound)) {
			
			$priceProduct = $product->price_product;
			$priceMaterials = $product->price_materials;
			
		}

        $status = 200;

	}else{

		$sqlSimple = " SELECT TRUNCATE(price,2) as price";
		$sqlSimple.= " FROM ".MAIN_DB_PREFIX."product";
		$sqlSimple.= " WHERE rowid =".$id;

		$resultSimple = $db->query($sqlSimple);

		if ($resultSimple) {
			
			while ($product = $db->fetch_object($resultSimple)) {
			
				$priceProduct = $product->price_product;
				
			}
	
			$status = 200;

		}else {
			
			$status = 401;
			$priceProduct = null;
			$priceMaterials = null;
		}

    }

	$response = [
		"status" => $status,
		"priceProduct" => $priceProduct,
		"priceMaterials" => $priceMaterials
	];

	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	echo json_encode($response);
}



?>