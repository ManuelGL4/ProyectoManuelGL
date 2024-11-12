<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sql = " SELECT s.nom as nombre, s.address, s.zip, s.town, s.phone, s.fax, s.email, s.siren, s.url, s.fk_departement FROM ". MAIN_DB_PREFIX ."societe s ";
	$sql.= " WHERE s.rowid = ".$id;
	
	$result = $db->query($sql);
	$cliente = $db->fetch_object($result);

	if ($cliente->fk_departement != "") {

		$consulta = " SELECT nom FROM ". MAIN_DB_PREFIX ."c_departements d ";
		$consulta.= " WHERE rowid = ".$cliente->fk_departement."";

		$resultado = $db->query($consulta);
		$provincia = $db->fetch_object($resultado);
		$provincia = $provincia->nom;
	} else {
		$provincia = "";
	}

	if ($result) {
		
		$data = [
			'nombre' => $cliente->nombre,
			'direccion' => $cliente->address,
			'zip' => $cliente->zip,
			'poblacion' => $cliente->town,
			'telefono' => $cliente->phone,
			'fax' => $cliente->fax,
			'email' => $cliente->email,
			'cif' => $cliente->siren,
			'departement' => $provincia,
			'contacto' => $id,
		];
			

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