<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];

	$sql = " SELECT s.nom as nombre, s.address, s.zip, s.town, s.phone, s.fax, s.email, s.siren, s.url, s.fk_departement, d.nom as provincia FROM ". MAIN_DB_PREFIX ."societe s ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."c_departements d ON d.rowid = s.fk_departement ";
	$sql.= " WHERE s.rowid = ".$id;
	
	$result = $db->query($sql);

	if ($result) {

		while ($cliente = $db->fetch_object($result)) {
			
			$data = [
				'nombre' => $cliente->nombre,
				'direccion' => $cliente->address,
				'zip' => $cliente->zip,
				'poblacion' => $cliente->town,
				'telefono' => $cliente->phone,
				'fax' => $cliente->fax,
                'email' => $cliente->email,
				'cif' => $cliente->siren,
				'departement' => $cliente->provincia,
				'contacto' => $id,
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