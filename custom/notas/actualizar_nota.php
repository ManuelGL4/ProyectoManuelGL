<?php
require '../../main.inc.php';
if(isset($_POST['notaId'])) {

    $notaId = $_POST['notaId'];
    $categoryName = $_POST['categoryName'];

    $query = "SELECT rowid FROM khns_notas_nota_categories WHERE name = '$categoryName'";
    $result = $db->query($query);

    $row = $result->fetch_assoc();
    $categoryId = $row['rowid'];

    $updateQuery = "UPDATE khns_notas_nota SET category = $categoryId WHERE rowid = $notaId";
    $db->query($updateQuery);

}
?>
