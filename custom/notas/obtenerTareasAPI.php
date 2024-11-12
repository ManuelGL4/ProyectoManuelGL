<?php

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id= $_GET["id"];

    $sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "projet_task ";
	$sql.= " WHERE fk_projet ='" . $id . "'";
	$result = $db->query($sql);

	if ($result) {

		$tareas = array();

		while ($tarea = $db->fetch_object($result)) {
			$tareas[] = [
				"rowid" => $tarea->rowid,
				"label" => $tarea->label
			];
		}

	}

	$result = [
		"status" => 200,
		"data" => $tareas
	];

	http_response_code(200);
	header('Content-Type: application/json; charset=utf-8');

	echo json_encode($result);


}


?>
