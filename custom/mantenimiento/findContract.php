<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $id = $_GET["id"];
    
    if ($id == -1) {//NO SELECCIONAS CONTRATO
        $status = 200;
        $data = [ 
			'periodicity' => '',
			'delegation' => '',
			'client' => '',
			'contact' => '',
			'ultimo' => '',
			'rowid' => '',
		];
    }else{
        $sqlTecnico = "SELECT technician_id FROM ".MAIN_DB_PREFIX."mantenimiento_informes WHERE contract_id = ".$id." ORDER BY rowid DESC LIMIT 1";
        $resultTecnico = $db->query($sqlTecnico);
        $tecnico = $db->fetch_object($resultTecnico);

        if (!$tecnico || $tecnico->technician_id == "") {
            $tecnico_id = -1;
        }
        $periodicity = [
            '0' => 'Mensual',
            '1' => 'Mensual',
            '2' => 'Bimestral',
            '3' => 'Trimestral',
            '4' => 'Semestral',
            '5' => 'Anual',
            '8' => 'Cuatrimestral',
        ];

        $sqlContact = "SELECT contact_id FROM ".MAIN_DB_PREFIX."mantenimiento_contratos WHERE rowid = ".$id;
        $resultContact = $db->query($sqlContact);
        $contactId = $db->fetch_object($resultContact);
    
        if ($contactId->contact_id != -1) {
            $sql = "SELECT mc.rowid, mc.periodicity, d.nombre, s.nom, sc.lastname as contact ";
        } else {
            $sql = "SELECT mc.rowid, mc.periodicity, d.nombre, s.nom ";
        }
        
        $sql .= "FROM ".MAIN_DB_PREFIX."mantenimiento_contratos mc ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."delegacion d ON d.id = mc.delegation_id ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = mc.client_id ";
        
        if ($contactId->contact_id != -1) {
            $sql .= "INNER JOIN ".MAIN_DB_PREFIX."socpeople sc ON sc.rowid = mc.contact_id ";
        }
        
        $sql .= "WHERE mc.rowid = ".$id;
        
        $result = $db->query($sql);
    
        if ($result) {
            while ($contract = $db->fetch_object($result)) {
                $data = [
                    'periodicity' => $periodicity[$contract->periodicity],
                    'delegation' => $contract->nombre,
                    'client' => $contract->nom,
                    'ultimo' => $tecnico->technician_id,
                    'rowid' => $contract->rowid,
                ];
                if ($contactId->contact_id != -1) {
                    $data['contact']= $contract->contact;
                }else{
                    $data['contact']= '';
                }
            }
            $status = 200;
        } else {
            $status = 500;
            $data = [];
        }
    }

    $response = [
        "status" => $status,
        "contract" => $data,
    ];

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($response);
}

?>
