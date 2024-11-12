<?php
ob_start();
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

/*
 * View
 */

 $form = new Form($db);
 $formfile = new FormFile($db);

 $sql = "SELECT * FROM ".MAIN_DB_PREFIX."easynotes_note_categories";
 $result = $db->query($sql);
 $numCat = $db->num_rows($result);

 if ($numCat < 5) {
 	$newcardbutton .= dolGetButtonTitle($langs->trans('Nueva Categoría'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=add');
 }
 llxHeader("", "Categorias");
print '<div class="fichecenter">';

if ($user->admin) {

 print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Categorias"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

    dolGetButtonTitle($langs->trans('Nueva Categoría'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"] . '?action=add');

	print "
        <form method='POST' action='' name='formfilter' autocomplete='off'>
        <tr class='liste_titre_filter'>";


    print "<th class='center liste_titre' title='nombre'>";
    print "<a class='reposition' href=''>Nombre</a>";
    print "</th>";

    print "<th class='center liste_titre' title='nombre'>";
    print "<a class='reposition' href=''>Acciones</a>";
    print "</th>";

    while ($data = $db->fetch_object($result)){
        print '<tr class="oddeven">';
        print "<td class='center' tdoverflowmax200'>". $data->name ."</td> ";
        print '<td class="center">';
        print '
        <table class="center">
            <tr>
                <td>
                    <a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $data->rowid . '">' . img_edit() . '</a>
                </td>
				<td>
					<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrar&id=' . $data->rowid . '">' . img_delete() . '</a>
				</td>
            </tr>
        </table>
        ';
        print '</td>';


        print "</tr>";
    }
    print "</table>";
	print '</div>';

	print '</form>';
	// <td>
    //     <a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $data->rowid . '">' . img_delete() . '</a>
    // </td>
print '
</div>
</div>
';

} else {

	$destination_url = 'easynotesindex.php?idmenu=202&mainmenu=ecm&leftmenu=';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}

if ($_GET["action"] == "add") {

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Nueva Categoría</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 97.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Nombre</span>
							</td>
							<td>
                                <input type="text" name="name">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="add">
						Sí
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						No
					</button>
				</div>
			</div>
		</div>
	</form>

	';
}elseif ($_GET["action"] == "edit") {

    $id = $_GET["id"];

	$sqlEdit = "SELECT * FROM ".MAIN_DB_PREFIX."easynotes_note_categories WHERE rowid =".$id;
	$resultEdit = $db->query($sqlEdit);
    $category = $db->fetch_object($resultEdit);


	//$id = $_GET['id'];
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $id . '" name=id >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar categoría</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 97.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Nombre</span>
							</td>
							<td>
								<input type="text" name="name" value="'. $category->name .'">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="edit">
						Sí
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						No
					</button>
				</div>
			</div>
		</div>
	</form>

	';
}

if ($_GET["action"] == "borrar") {

	$id = $_GET['id'];

	$sqlDelete = " DELETE FROM " . MAIN_DB_PREFIX . "easynotes_note_categories ";
	$sqlDelete.= " WHERE rowid = ".$id;

	$resultDelete = $db->query($sqlDelete);

	if (!$resultDelete) {
		setEventMessage("Hay notas asociadas a esta categoria, no se puede borrar", 'errors');
	}

	header('Location: note_categories.php');

}

if (isset($_POST['add'])) {
	$name = $_POST['name'];

	$sqlInsert = "INSERT INTO " . MAIN_DB_PREFIX . "easynotes_note_categories ( name ) VALUES ( '" . $name . "' )";
	$resultInsert = $db->query($sqlInsert);

	setEventMessages("Categoría creada", null, 'mesgs');

	header('Location: note_categories.php');

}elseif (isset($_POST['edit'])) {
	$category_id = $_POST['id'];
	$name = $_POST['name'];

	$sqlEdit = "UPDATE " . MAIN_DB_PREFIX . "easynotes_note_categories SET name='". $name ."' WHERE rowid=". $category_id ."";
	$resultEdit = $db->query($sqlEdit);

	setEventMessages("Categoría editada", null, 'mesgs');

	header('Location: note_categories.php');
}

 // End of page
 llxFooter();
 $db->close();


ob_flush();
?>
