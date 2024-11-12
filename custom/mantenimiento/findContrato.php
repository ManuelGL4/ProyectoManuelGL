<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sql = " SELECT pe.id_presu, pe.discount_offer, pe.discount_sp, p.fk_soc, se.discount FROM ". MAIN_DB_PREFIX ."projet_extrafields pe ";
    $sql.= " INNER JOIN ". MAIN_DB_PREFIX ."projet p ON p.rowid = pe.fk_object ";
    $sql.= " INNER JOIN ". MAIN_DB_PREFIX ."societe_extrafields se ON se.fk_object = p.fk_soc ";
	$sql.= " WHERE pe.fk_object = ".$id;
	
	$result = $db->query($sql);
	$contrato = $db->fetch_object($result);

	//PARA LAS OFERTAS
	if ($contrato->id_presu != "") {
		$sqlOfertas = " SELECT * FROM ". MAIN_DB_PREFIX ."propal ";
		$sqlOfertas.= " WHERE rowid = ".$contrato->id_presu." ";
	
		$result2 = $db->query($sqlOfertas);
		$oferta = $db->fetch_object($result2);
	}

	if ($result) {
		
		if ($contrato->id_presu != "") {

			$data = [
				'oferta' => $contrato->discount_offer,
				'oferta2' => $contrato->discount_sp,
				'cliente' => $contrato->fk_soc,
				'dto_cliente' => $contrato->discount,
				'oferta_anterior' => $oferta->ref,
			];

		} else {

			$data = [
				'oferta' => $contrato->discount_offer,
				'oferta2' => $contrato->discount_sp,
				'cliente' => $contrato->fk_soc,
				'dto_cliente' => $contrato->discount,
				'oferta_anterior' => "",
			];

		}
			

        $status = 200;

	}

	$response = [
		"status" => $status,
		"contrato" => $data,
	];

	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	echo json_encode($response);
}



?>