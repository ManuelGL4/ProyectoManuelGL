<?php   

require '../../main.inc.php';

$id=$_GET["id"];

$sql = "SELECT puntuacion_tiempo, puntuacion_calidad FROM ".MAIN_DB_PREFIX."societe_extrafields where fk_object=".$id;
$response = $db->query($sql);

$data=$db->fetch_object($response);

$data->puntuacion_tiempo= ( $data->puntuacion_tiempo==null) ? "No definido" : $data->puntuacion_tiempo;

$data->puntuacion_calidad= ( $data->puntuacion_calidad==null) ? "No definido" : $data->puntuacion_calidad;

$json=[
    'score_times'=>$data->puntuacion_tiempo,
    'score_supplies'=>$data->puntuacion_calidad,
];

echo json_encode($json);

?>