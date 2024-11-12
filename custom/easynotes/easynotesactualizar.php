<?php
require '../../main.inc.php';
if(isset($_POST['notaId'])) {

    $notaId = $_POST['notaId'];
    $categoryName = $_POST['categoryName'];

    $query = "SELECT rowid FROM ".MAIN_DB_PREFIX."easynotes_note_categories WHERE name = '$categoryName'";
    $result = $db->query($query);

    $row = $result->fetch_assoc();
    $categoryId = $row['rowid'];

    $updateQuery = "UPDATE ".MAIN_DB_PREFIX."easynotes_note SET category = $categoryId WHERE rowid = $notaId";
    $db->query($updateQuery);

}
?>
