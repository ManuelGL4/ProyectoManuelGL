<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sqlCompound = " SELECT bl.fk_product, p.ref, p.description FROM ".MAIN_DB_PREFIX."bom_bom b";
	$sqlCompound.= " INNER JOIN ".MAIN_DB_PREFIX."bom_bomline bl ON bl.fk_bom = b.rowid";
    $sqlCompound.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = bl.fk_product";
	$sqlCompound.= " WHERE b.fk_product =".$id;
	$sqlCompound.= " GROUP BY bl.fk_product ";
	
	$resultCompound = $db->query($sqlCompound);

	/*$sqlServices = " SELECT p.rowid, p.ref, p.description FROM ".MAIN_DB_PREFIX."product p";
	$sqlServices.= " WHERE p.fk_product_type = 1";
	
	$resultServices = $db->query($sqlServices);*/

    $data = [];

	if ($resultCompound) {

		while ($product = $db->fetch_object($resultCompound)) {
			
			$data[] = [
                'rowid' => $product->fk_product,
                'ref' => $product->ref,
                'description' => $product->description,
            ];
			
		}

        $status = 200;

	}

	/*if ($resultServices) {
		
		while ($service = $db->fetch_object($resultServices)) {
				
			$data[] = [
				'rowid' => $service->rowid,
				'ref' => $service->ref,
				'description' => $service->description,
			];
			
		}
	}*/

	$response = [
		"status" => $status,
		"data" => $data,
	];

	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	echo json_encode($response);
}



?>