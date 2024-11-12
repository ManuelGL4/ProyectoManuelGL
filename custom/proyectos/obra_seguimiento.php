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

dol_include_once('/proyectos/class/obra.class.php');
dol_include_once('/proyectos/lib/proyectos_obra.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("proyectos@proyectos", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Obra($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->mantenimiento->multidir_output[$object->entity] . "/" . $object->id;
}

$permissionnote = $user->rights->mantenimiento->informes->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->mantenimiento->informes->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->mantenimiento->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Seguimiento'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = obraPrepareHead($object);

	print dol_get_fiche_head($head, 'seguimiento', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/proyectos/obra_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';

	print "<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "?id=" . $_GET['id'] . "\">";
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';


	print dol_get_fiche_end();

	//A partir de aquí se pintan tablas y demás

	$arrayfields = array(
		'num_serie' => array('label' => $langs->trans("Nº serie"), 'checked' => 1),
		'codigo' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'articulo_id' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
		'fin_garantia' => array('label' => $langs->trans("Fin garantía"), 'checked' => 1),
		'ubicacion' => array('label' => $langs->trans("Ubicación"), 'checked' => 1),
	);
    // $arrayfields = array(
	// 	'num_offer' => array('label' => $langs->trans("Nº serie"), 'checked' => 1),
	// 	'description' => array('label' => $langs->trans("Código"), 'checked' => 1),
	// 	'date_offer' => array('label' => $langs->trans("Artículo"), 'checked' => 1),
	// 	'user_created' => array('label' => $langs->trans("Fin garantía"), 'checked' => 1),
	// 	'client' => array('label' => $langs->trans("Ubicación"), 'checked' => 1),
	// 	'percentaje_discount' => array('label' => $langs->trans("Ubicación"), 'checked' => 1),
	// 	'total' => array('label' => $langs->trans("Ubicación"), 'checked' => 1),
	// 	'awarded' => array('label' => $langs->trans("Ubicación"), 'checked' => 1),
	// );

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
			'num_serie' => array('label' => $langs->trans("Nº serie"), 'checked' => 0),
			'codigo' => array('label' => $langs->trans("Código"), 'checked' => 0),
			'articulo_id' => array('label' => $langs->trans("Articulo"), 'checked' => 0),
			'fin_garantia' => array('label' => $langs->trans("Fin garantía"), 'checked' => 0),
			'ubicacion' => array('label' => $langs->trans("Ubicación"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$sql = "SELECT p.rowid, mce.id, p.ref, p.label, p.description, pe.mantenimiento, mce.ubicacion, pe.fecha_garantia ";
	$sql .= "FROM " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos mce ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid=mce.producto_id ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields pe ON p.rowid=pe.fk_object ";
	$sql .= "WHERE mce.contrato_id=" . $id;
	$result = $db->query($sql);

	$num = $db->num_rows($result);
	$nbtotalofrecords = $num;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	$newcardbutton = '';

	$newcardbutton .= dolGetButtonTitle($langs->trans('Nueva Línea'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=add');


	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Equipos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";
	if (!empty($arrayfields['codigo']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_trabajador_id">';
		print '</td>';
	}
	if (!empty($arrayfields['num_serie']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_tarea_id">';
		print '</td>';
	}
	if (!empty($arrayfields['articulo_id']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fecha">';
		print '</td>';
	}

	if (!empty($arrayfields['fin_garantia']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_tiempo'>";
		print '</td>';
	}

	if (!empty($arrayfields['ubicacion']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_fallo_id">';
		print '</td>';
	}

	print "
			<td class='liste_titre middle'>
				<div class='nowrap'>
					<button type='submit' class='liste_titre button_search' name='button_search' value='x'>
						<span class='fa fa-search'></span>
					</button>
					<button type='submit' class='liste_titre button_removefilter' name='button_removefilter' value='x'>
						<span class='fa fa-remove'></span>
					</button>
				</div>
			</td>
			</tr>
			</form>
			";

	print '<tr class="liste_titre">';
	
	if (!empty($arrayfields['codigo']['checked'])) {
		print "<th class='center liste_titre' title='nombre'>";
		print "<a class='reposition' href=''>Código</a>";
		print "</th>";
	}

	if (!empty($arrayfields['num_serie']['checked'])) {
		print "<th class='center liste_titre' title='id_asoc'>";
		print "<a class='reposition' href=''>Nº serie</a>";
		print "</th>";
	}

	if (!empty($arrayfields['articulo_id']['checked'])) {
		print "<th class='center liste_titre' title='fecha'>";
		print "<a class='reposition' href=''>Artículo</a>";
		print "</th>";
	}

	if (!empty($arrayfields['fin_garantia']['checked'])) {
		print "<th class='center liste_titre' title='tiempo'>";
		print "<a class='reposition' href=''>Fin garantía</a>";
		print "</th>";
	}

	if (!empty($arrayfields['ubicacion']['checked'])) {
		print "<th class='center liste_titre' title='fallo'>";
		print "<a class='reposition' href=''>Ubicación</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "
			
			</tr>
			";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_num_serie']) && ($_POST['search_num_serie']) != "") {
			$idbusqueda = "" . $_POST['search_num_serie'] . "";
			$sql .= ' and id_asoc =' . $idbusqueda;
		}

		if (isset($_POST['search_codigo']) && ($_POST['search_codigo']) != "") {
			$nombre = "" . $_POST['search_codigo'] . "";
			$sql .= ' and firstname like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_articulo_id']) && ($_POST['search_articulo_id']) != "") {
			$apellidos = "" . $_POST['search_articulo_id'] . "";
			$sql .= ' and lastname like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_fin_garantia']) && ($_POST['search_fin_garantia']) != "") {
			$fecha = "'" . $_POST['search_fin_garantia'] . "'";
			$sql .= ' and DATE(datec)=' . $fecha;
		}

		if (isset($_POST['search_ubicacion']) && ($_POST['search_ubicacion']) != "") {
			$importe = "" . $_POST['search_ubicacion'] . "";
			$sql .= ' and amount = ' . $importe . '';
		}
	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i < $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		$warranty = new DateTime($datos->fecha_garantia);
		$endWarranty = $warranty->modify('+2 years');
		$formatEndWarranty = ($datos->fecha_garantia != null) ? $endWarranty->format('Y-m-d') : '';

		if (!empty($arrayfields['codigo']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->label . "</td> ";
		
		if (!empty($arrayfields['num_serie']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->ref . "</td> ";

		if (!empty($arrayfields['articulo_id']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->label . "</td> ";

		if (!empty($arrayfields['fin_garantia']['checked']))	print "<td class='center' tdoverflowmax200'>" . $formatEndWarranty . "</td> ";

		if (!empty($arrayfields['ubicacion']['checked']))	print "<td class='center' tdoverflowmax200'>". $datos->ubicacion ."</td> ";


		print '<td class="center">';
		print '
		<table class="center">
			<tr>
				<td>
					<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $id . '&idDelete=' . $datos->id . '">' . img_delete() . '</a>
				</td>
				<td>
					<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $id . '&idEdit=' . $datos->id . '">' . img_edit() . '</a>		
				</td>
			</tr>
		</table>
		';
		print '</td>';


		print "</tr>";
		$i++;
	}
	print "</table>";
	print '</div>';

	print '</form>';
}

if ($_GET["action"] == "add") {

	$sqlProducts = "SELECT p.rowid, p.label FROM " . MAIN_DB_PREFIX . "product p ";
	$sqlProducts .= "INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields pe ON p.rowid=pe.fk_object ";
	$sqlProducts .= "WHERE pe.mantenimiento='1'";
	$resultProducts = $db->query($sqlProducts);


	//$id = $_GET['id'];
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $id . '" name=id >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Nueva Línea</span>
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
								<span class="fieldrequired">Producto</span>
							</td>
							<td>
								<select class="select-products" style="width: 200px" name="product" id="">';
								while ($product = $db->fetch_object($resultProducts)) {

									print ' <option value="' . $product->rowid . '">' . $product->label . '</option>';
								}
								print '
								</select>		
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Ubicación</span>
							</td>
							<td>
								<input type="text" name="ubicacion">
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

	$idEdit = $_GET['idEdit'];

	$sqlProducts = "SELECT p.rowid, p.label FROM " . MAIN_DB_PREFIX . "product p ";
	$sqlProducts .= "INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields pe ON p.rowid=pe.fk_object ";
	$sqlProducts .= "WHERE pe.mantenimiento='1'";
	$resultProducts = $db->query($sqlProducts);

	$sqlEquipo = "SELECT ubicacion, producto_id FROM " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos WHERE id=".$idEdit." ";
	$resultEquipo = $db->query($sqlEquipo);
	$equipo = $db->fetch_object($resultEquipo);


	//$id = $_GET['id'];
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $idEdit . '" name=id >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Línea</span>
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
								<span class="fieldrequired">Producto</span>
							</td>
							<td>
								<select class="select-products" style="width: 200px" name="product" id="">';
								while ($product = $db->fetch_object($resultProducts)) {

									if ($product->rowid==$equipo->producto_id) {

										print ' <option selected value="' . $product->rowid . '">' . $product->label . '</option>';
									
									}else{

										print ' <option value="' . $product->rowid . '">' . $product->label . '</option>';
									}
								}
								print '
								</select>		
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Ubicación</span>
							</td>
							<td>
								<input type="text" name="ubicacion" value="'. $equipo->ubicacion .'">
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
}elseif ($_GET["action"] == "delete") {

	$idDelete = $_GET["idDelete"];

	$sqlDeleteProducts = "DELETE FROM " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos WHERE id=" . $idDelete;
	$resultDeleteProducts = $db->query($sqlDeleteProducts);

	setEventMessages("Línea borrada", null, 'mesgs');

	header('Location: contratos_equipos.php?id=' . $id . '');
}

if (isset($_POST['add'])) {
	$contrat_id = $_POST['id'];
	$product_id = $_POST['product'];
	$location = $_POST['ubicacion'];

	$sqlInsertProducts = "INSERT INTO " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos ( contrato_id, producto_id, ubicacion ) VALUES ( " . $contrat_id . "," . $product_id . ",'" . $location . "' )";
	$resultInsertProducts = $db->query($sqlInsertProducts);

	setEventMessages("Línea creada", null, 'mesgs');

	header('Location: contratos_equipos.php?id=' . $contrat_id . '');

}elseif (isset($_POST['edit'])) {
	$contrat_id = $_POST['id'];
	$product_id = $_POST['product'];
	$location = $_POST['ubicacion'];

	$sqlEditProducts = "UPDATE " . MAIN_DB_PREFIX . "mantenimiento_contratos_equipos SET producto_id=". $product_id .", ubicacion='". $location ."' WHERE id=". $contrat_id ."";
	$resultEditProducts = $db->query($sqlEditProducts);

	setEventMessages("Línea editada", null, 'mesgs');

	header('Location: contratos_equipos.php?id=' . $id . '');
}

print "
<script>
	$(document).ready(function() {
		$('.select-products').select2();
	});
</script>
";



// End of page
llxFooter();
$db->close();
ob_flush();
