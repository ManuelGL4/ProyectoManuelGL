<?php
ob_start();
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("documentosdrive@documentosdrive"));

$action = GETPOST('action', 'aZ09');
// Security check
// if (! $user->rights->documentosdrive->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

$max = 5;
$now = dol_now();

const CREDENTIALS_PATH = 'C:\xampp\htdocs\khonos-ORTRAT\custom\documentosdrive\build\ortrat-17b9ccc96c2c.json';
const DRIVE_BASE_FOLDER_ID = '1FPZdUrmoKNF-oaDYP6utqroOYJA8-rFE';
const FOLDER_MYMETYPE = 'application/vnd.google-apps.folder';
const STATUS_RESPONSES = [
    'deleted' => 'El archivo se eliminó con éxito',
    'uploaded' => 'El archivo se subió con éxito',
    'failed' => 'No se ha podido realizar la acción con éxito',
];

/*
 * Actions
 */

// None

//setup google drive

require __DIR__ . '/../../vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

/**
 * Returns an authorized API client.
 * @return Client the authorized client object
 */
function getClient()
{
    
    $client = new Client();
    $client->setApplicationName('Ortrat');
    $client->setScopes(Drive::DRIVE);
    $client->setSubject('dvillafranca@ortrat.es');
    $client->setAuthConfig(CREDENTIALS_PATH);
   
    return $client;
}

function uploadFile(Drive $service, $id)
{
    $file = new DriveFile();
    $file->setName($_FILES['file']['name']);
    $file->setParents([$id]);
    $content = file_get_contents($_FILES['file']['tmp_name']);

    $result = $service->files->create(
        $file,
        [
            'data' => $content,
            'mimeType' => $_FILES['file']['type'],
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]
    );

    $isUploaded = ( $result->getId() != 0 ) ? true : false;

    return $isUploaded;
    
}

function downloadFile(Client $client, $file)
{

    $fileSize = intval($file['size']);

    // Get the authorized Guzzle HTTP client
    $http = $client->authorize();
    
    $uuidFile = uniqid().$file['format'];

    // Open a file for writing
    $fp = fopen($uuidFile, 'w');

    // Download in 1 MB chunks
    $chunkSizeBytes = 1 * 1024 * 1024;
    $chunkStart = 0;

    // Iterate over each chunk and write it to our file
    while ($chunkStart < $fileSize) {
        $chunkEnd = $chunkStart + $chunkSizeBytes;
        $response = $http->request(
            'GET',
            sprintf('/drive/v3/files/%s', $file['id']),
            [
                'query' => ['alt' => 'media'],
                'headers' => [
                    'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
                ]
            ]
        );
        $chunkStart = $chunkEnd + 1;
        fwrite($fp, $response->getBody()->getContents());
    }
    // close the file pointer
    fclose($fp);

    header('Content-Type: '.$file['mimeType'].'');//; charset=UTF-8
    header("Content-length: ".$file['size']."");
    //header('Content-Encoding: UTF-8');
    header("Content-Disposition: attachment;filename=".$file['fileName']."");
    header('Expires: Jue, 21 Oct 2017 07:28:00 GMT');
    header('Pragma: cache');
    header('Cache-Control: private');

    ob_clean();
    flush();
    
    readfile($uuidFile);

    unlink($uuidFile);
}

function deleteFile(Drive $service, $id) {

    $isDeleted = false;

    try {

        $isDeleted = $service->files->delete($id);
        
    } catch (Exception $e) {
        //print "An error occurred: " . $e->getMessage();
        $isDeleted = false;
    }

    return $isDeleted;
}

function getListFiles(Drive $service, $id) {

    $files = [];

    try { 
    
        $optParams = array(
            'pageSize' => 100,
            'fields' => 'files(id, name, mimeType, size, webContentLink,thumbnailLink)',//
            //'q' => 'mimeType != "FOLDER_MYMETYPE"',
            'q' => '"'.$id.'" in parents',
            'orderBy' => 'name',//folder
            //'corpora' => 'drive',
            //'includeItemsFromAllDrives' => true,
            //'supportsAllDrives' => true,
            //'driveId' => ''
        );
        $results = $service->files->listFiles($optParams);
        $files = $results->getFiles();
    
    } catch (Exception $e) {
        // TODO(developer) - handle error appropriately
        echo 'Message: ' . $e->getMessage();
    }

    return $files;

}

// Get the API client and construct the service object.
$client = getClient();
$service = new Drive($client);
$folderId = ( isset($_GET['id']) ) ? $_GET['id'] : DRIVE_BASE_FOLDER_ID;
$files = getListFiles($service, $folderId);
/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

if (isset($_GET['status'])) {
    $status = $_GET['status'];

    $message = STATUS_RESPONSES[$status];

    setEventMessage($message);
}

llxHeader("", $langs->trans("Documentos Google Drive"));

print load_fiche_titre($langs->trans("Documentos Google Drive"), '', 'documentosdrive.png@documentosdrive');

print '<div class="fichecenter"><div class="fichethirdleft">';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

print '</div></div></div>';

print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">
    <input required type="file" name="file">
    <input required type="hidden" name="folderId" value="'.$folderId.'">
    <button type="submit" class="butAction reposition" name="upload">Subir archivo</button>
</form><br><br>';
if (isset($_GET['id'])) {
    print "<button class='butAction reposition' onclick='history.back()'>Volver</button><br><br>";
}

print "<div class='div-table-responsive'>
<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
    <thead>
        <tr class='liste_titre'>
            <th class='center liste_titre'>Miniatura</th>
            <th class='center liste_titre'>Nombre</th>
            <th class='center liste_titre'>Peso</th>
            <th class='center liste_titre'>Acciónes</th>
        <tr/>
    </thead>
	<tbody>
    ";
    foreach ($files as $key => $file) {

        $sizeMB = round( $file->getSize() / 1000000, 3 ).' MB';
        $img = ( $file->getMimeType() == FOLDER_MYMETYPE ) ? 'img/folder.png' : $file->getThumbnailLink();
        print "
        <tr class='oddeven'>
            <td class='center' tdoverflowmax200'>
                <img src='".$img."' width='100' height='100'>
            </td>
            <td class='center' tdoverflowmax200'>".$file->getName()."</td>
            <td class='center' tdoverflowmax200'>".$sizeMB."</td>
            <td class='center' tdoverflowmax200'>
            <form action='".$_SERVER['PHP_SELF']."' method='post'>
                    <input type='hidden' name='folderId' value='".$folderId."'>
                    <input type='hidden' name='id' value='".$file->getId()."'>
                    <input type='hidden' name='size' value='".$file->getSize()."'>
                    <input type='hidden' name='mimeType' value='".$file->getMimeType()."'>
                    <input type='hidden' name='fileName' value='".$file->getName()."'>";

                    if ($file->getMimeType() == FOLDER_MYMETYPE) {
                        print "<a href='".$_SERVER['PHP_SELF']."?id=".$file->getId()."' class='butAction reposition'>Acceder</a>";
                    } else {
                        //<a href='".$file->getWebContentLink()."' class='butAction reposition'>Descargar</a>
                        print "
                        <button class='butAction reposition' name='delete' type='submit'>Eliminar</button>
                        <button class='butAction reposition' name='download' type='submit'>Descargar</button>
                        ";
                    }
                    
                    print "
                </form>
            </td>
        </tr>
        ";
    }
    print "
    </tbody>
</table>
</div>";

// End of page
llxFooter();
$db->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    
    $folderId = $_POST['folderId']; 
    
    $isUploaded = uploadFile($service, $folderId);

    $status = ( $isUploaded = true ) ? 'uploaded' : 'failed';

    if ($folderId != DRIVE_BASE_FOLDER_ID) {
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status.'&id='.$folderId);
    }else{
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status);
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['download'])) {

    $pos = strpos($_POST['fileName'], '.');

    $format = substr($_POST['fileName'], $pos);

    $file = [
        'id' => $_POST['id'],
        'size' => $_POST['size'],
        'mimeType' => $_POST['mimeType'],
        'fileName' => $_POST['fileName'],
        'format' => $format,
    ];
    
    $response = downloadFile($client, $file);

    
}elseif($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])){
    
    $folderId = $_POST['folderId'];

    $isDeleted = deleteFile($service, $_POST['id']);

    $status = ( $isDeleted = true ) ? 'deleted' : 'failed';

    if ($folderId != DRIVE_BASE_FOLDER_ID) {
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status.'&id='.$folderId);
    }else{
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status);
    }

}

ob_flush();