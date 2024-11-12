<?php 

require '../../main.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $id = $_GET["id"];

    $sql = "SELECT periodicity FROM ".MAIN_DB_PREFIX."mantenimiento_contratos WHERE rowid = ".$id;

    $result = $db->query($sql);

    $contract = $db->fetch_object($result);

    $documentType = '';
    
    switch ($contract->periodicity) {
        case '1':
            $documentType = '';
        break;
        case '2':
            $documentType = '';
        break;
        case '3':
            $documentType = '';
        break;
        case '4':
            $documentType = '';
        break;
        
        default:
            
        break;
    }

    

}

?>