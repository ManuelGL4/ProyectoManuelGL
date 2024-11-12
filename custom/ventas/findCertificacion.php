<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sql = " SELECT s.rowid, s.remise_client, se.libre_1, pe.discount_offer FROM ". MAIN_DB_PREFIX ."societe s ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."societe_extrafields se ON se.fk_object = s.rowid ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."projet p ON p.fk_soc = s.rowid ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."projet_extrafields pe ON pe.fk_object = p.rowid ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."proyectos_certificaciones pc ON pc.fk_proyect = p.rowid ";
	$sql.= " WHERE pc.rowid = ".$id;
	
	$result = $db->query($sql);

	if ($result) {

		while ($cliente = $db->fetch_object($result)) {

            //Representante
            $sql2 = " SELECT u.rowid FROM ". MAIN_DB_PREFIX ."user u ";
            $sql2.= " WHERE login = '".$cliente->responsable."' ";
            
            $result2 = $db->query($sql2);
            $respon = $db->fetch_object($result2);
			
			$data = [
				'estenombre' => $cliente->rowid,
                'pago' => $cliente->libre_1,
                'desc' => strtr(number_format($cliente->remise_client,2),['.' => ',', ',' => '.']),
				'desc_fa' => strtr(number_format($cliente->discount_offer,2),['.' => ',', ',' => '.']),

            ];
			
		}

        $status = 200;

	}

	$response = [
		"status" => $status,
		"cliente" => $data,
	];

	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	echo json_encode($response);
}



?>