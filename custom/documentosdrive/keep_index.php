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
use Google\Service\Keep;
use Google\Service\Keep\Resource\Notes;

/**
 * Returns an authorized API client.
 * @return Client the authorized client object
 */
function getClient()
{
    
    $client = new Client();
    $client->setApplicationName('Ortrat');
    $client->setScopes(Keep::KEEP);
    $client->setSubject('dvillafranca@ortrat.es');
    $client->setAuthConfig(CREDENTIALS_PATH);
   
    return $client;
}

function getNotesList(Keep $keepService){

    try {

        $optParams = array(
            'pageSize' => 100,
            'fields' => 'files(id, name, mimeType, size, webContentLink,thumbnailLink)',//
            'orderBy' => 'name',
        );
    
        $listNotesResponse = $keepService->notes->listNotes();
        $notes = $listNotesResponse->getNotes();
        
    } catch (Exception $e) {
        // TODO(developer) - handle error appropriately
        echo 'Message: ' . $e->getMessage();
    }

    return $notes;
}

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$client = getClient();
$keepService = new Keep($client);
$notes = getNotesList($keepService);


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

llxHeader("", $langs->trans("Notas Google Keep"));

print load_fiche_titre($langs->trans("Notas Google Keep"), '', 'documentosdrive.png@documentosdrive');

print '<div class="fichecenter"><div class="fichethirdleft">';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

print '</div></div></div>';

print "<div class='div-table-responsive'>
<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
    <thead>
        <tr class='liste_titre'>
            <th class='center liste_titre'>Titulo</th>
            <th class='center liste_titre'>Contenido</th>
        <tr/>
    </thead>
	<tbody>
    ";
    foreach ($notes as $key => $note) {

        print "
        <tr class='oddeven'>
            <td class='center' tdoverflowmax200'>".$note->getTitle()."</td>
            <td class='center' tdoverflowmax200'>".$note->getBody()->getText()->getText()."</td>
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