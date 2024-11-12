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

use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Microsoft\Graph\Model;

const SITE_ID = "deltanetsi-my.sharepoint.com,9afe483d-8c3b-4634-843f-72e2fbca44e6,c52ed903-e568-4884-9f62-3f8f33bdf984";
const TENANT_ID = "af4df713-32b9-49bf-ac88-70d0c4368385";
const CLIENT_ID = "b26689b5-4e6e-4508-ad0b-4e7fd220fab0";
const CLIENT_SECRET = "ePA8Q~1sfaW4k1hu-jh3Zr13c6WqLFV1.L22Pa_W";


function getToken()
{
    
    $guzzle = new \GuzzleHttp\Client();

    $url = 'https://login.microsoftonline.com/'.TENANT_ID.'/oauth2/v2.0/token';

    $token = json_decode($guzzle->post($url, [
        'form_params' => [
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ],
    ])->getBody()->getContents());

    $accessToken = $token->access_token;
   
    return $accessToken;
}

 function uploadFile(Graph $graph, $fileName, $file, $driveId)
{

    $folder = ( $driveId == SITE_ID ) ? "root:" : "root:/".$driveId;

    try {

        $result = $graph->createRequest("PUT", "/sites/".SITE_ID."/drive/".$folder."/".$fileName.":/content")
          ->upload($file);

    } catch (Exception $e) {
        
    }

    $isUploaded = ( $result != null ) ? true : false;

    return $isUploaded;
    
}

function deleteFile(Graph $graph, $fileId) {

    $isDeleted = false;

    try {

        $result = $graph->createRequest("DELETE", "/sites/".SITE_ID."/drive/items/".$fileId)
            ->execute();

        $isDeleted = ( $result != null ) ? true : false;
        
    } catch (Exception $e) {
        
    }

    return $isDeleted;
}

function getListFiles(Graph $graph, $folderId) {

    $folder = ( $folderId == SITE_ID ) ? "root" : "items/".$folderId;

    $docGrabber = $graph->createCollectionRequest("GET", "/sites/".SITE_ID."/drive/".$folder."/children")
                        ->setReturnType(Model\DriveItem::class)
                        ->setPageSize(10);

    // $docs = $docGrabber->getPage();

    // foreach ($docs as $doc){
    //     $files[] = $doc->getName();
    // }

    while (!$docGrabber->isEnd()) {
        $files = array_merge($docGrabber->getPage());
    }

    return $files;

}

$accessToken = getToken();

$graph = new Graph();
$graph->setAccessToken($accessToken);
//Id to navigate throw the folders
$folderId = ( isset($_GET['id']) ) ? $_GET['id'] : SITE_ID;
//Id/name of folder where we want to upload a file
$driveId = ( isset($_GET['driveId']) ) ? $_GET['driveId'] : SITE_ID;

$previous = ( isset($_GET['previous']) ) ? $_GET['previous'] : 1;

$files = getListFiles($graph, $folderId);

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

llxHeader("", $langs->trans("Documentos  Onedrive"));

print load_fiche_titre($langs->trans("Documentos Onedrive"), '', 'documentosdrive.png@documentosdrive');

print '<div class="fichecenter"><div class="fichethirdleft">';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

print '</div></div></div>';

print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">
    <input required type="file" name="fileToUpload">
    <input required type="hidden" name="folderId" value="'.$folderId.'">
    <input required type="hidden" name="driveId" value="'.$driveId.'">
    <input required type="hidden" name="previous" value="'.$previous.'">
    <button type="submit" class="butAction reposition" name="upload">Subir archivo</button>
</form><br><br>';
if (isset($_GET['id'])) {
    print "<button class='butAction reposition' onclick='history.go(-".$previous.")'>Volver</button><br><br>";
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
        
        $sizeMB = round( $file->getSize() / 1000000, 2 ).' MB';

        $img = ( $file->getFolder() != null ) ? 'img/folder.png' : 'img/document.png';

        $properties = $file->getProperties();

        if (array_key_exists("@microsoft.graph.downloadUrl", $properties)){
            $downloadUrl = $properties["@microsoft.graph.downloadUrl"];
        }

        $driveId = $file->getParentReference()->getDriveId();
        
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
                    <input required type='hidden' name='driveId' value='".$driveId."'>
                    <input type='hidden' name='id' value='".$file->getId()."'>
                    <input type='hidden' name='fileName' value='".$file->getName()."'>
                    <input required type='hidden' name='previous' value='".$previous."'>";

                    if ($file->getFolder() != null) {
                        print "<a href='".$_SERVER['PHP_SELF']."?id=".$file->getId()."&driveId=".$file->getName()."&previous=".$previous."' class='butAction reposition'>Acceder</a>";
                    } else {
                        
                        //<a href='".$file->getWebContentLink()."' class='butAction reposition'>Descargar</a>
                        print "<button class='butAction reposition' name='delete' type='submit'>Eliminar</button>";
                        print "<a href='".$downloadUrl."' class='butAction reposition'>Descargar</a>";
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
    
    $fileName = $_FILES["fileToUpload"]["name"]; 

    $file = $_FILES["fileToUpload"]["tmp_name"];

    $folderId = $_POST["folderId"];

    $driveId = $_POST["driveId"];

    $previous = $_POST["previous"];

    $previous++;
    
    $isUploaded = uploadFile($graph, $fileName, $file, $driveId);

    $status = ( $isUploaded = true ) ? 'uploaded' : 'failed';

    if ($folderId != SITE_ID) {
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status.'&id='.$folderId."&driveId=".$driveId."&previous=".$previous);
    }else{
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status);
    }

}elseif($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])){
    
    $folderId = $_POST['folderId'];

    $driveId = $_POST["driveId"];

    $fileId = $_POST['id'];

    $previous = $_POST["previous"];

    $previous++;
    
    $isDeleted = deleteFile($graph, $fileId);

    $status = ( $isDeleted = true ) ? 'deleted' : 'failed';

    if ($folderId != SITE_ID) {
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status.'&id='.$folderId."&driveId=".$driveId."&previous=".$previous);
    }else{
        header('Location: '.$_SERVER['PHP_SELF'].'?status='.$status);
    }

}

ob_flush();