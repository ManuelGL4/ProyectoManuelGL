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

if (isset($_POST['add'])) {

	$obra_id = $_POST['id'];
	$project_id = $_POST['project'];
	$adjudicado = $_POST['adjudicado'];

	$sqlInsert = " INSERT INTO " . MAIN_DB_PREFIX . "proyectos_obra_oferta ( fk_project, fk_obra, adjudicado )";
	$sqlInsert.= " VALUES ( " . $project_id . "," . $obra_id . "," . $adjudicado . " )";
	$resultInsert = $db->query($sqlInsert);
	
	$message = ($resultInsert) ? "Oferta añadida con éxito" : "Error al añadir la oferta";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);

	//header('Location: contratos_equipos.php?id=' . $contrat_id . '');

}elseif (isset($_POST['edit'])) {

	$rowid = $_POST['id'];
	$project_id = $_POST['project'];
	$adjudicado = $_POST['adjudicado'];

	$sqlEdit = " UPDATE " . MAIN_DB_PREFIX . "proyectos_obra_oferta";
	$sqlEdit.= " SET fk_project=". $project_id .", adjudicado='". $adjudicado ."' WHERE rowid=". $rowid ."";
	$resultEdit = $db->query($sqlEdit);
	
	$message = ($resultEdit) ? "Oferta editada con éxito" : "Error al editar la oferta";
	$type = ($resultEdit) ? "mesgs" : "errors";

	setEventMessage($message, $type);

	//header('Location: contratos_equipos.php?id=' . $id . '');
}elseif (isset($_POST["delete"])) {

	$rowid = $_POST["id"];

	$sqlDelete = "DELETE FROM " . MAIN_DB_PREFIX . "proyectos_obra_oferta WHERE rowid=" . $rowid;
	
	$resultDelete = $db->query($sqlDelete);

	$message = ($resultDelete) ? "Oferta eliminada con éxito" : "Error al eliminar la oferta";
	$type = ($resultDelete) ? "mesgs" : "errors";

	setEventMessage($message, $type);

	//header('Location: contratos_equipos.php?id=' . $id . '');
}

/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Ofertas'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = obraPrepareHead($object);

	print dol_get_fiche_head($head, 'ofertas', '', -1, $object->picto);

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
		'num_offer' => array('label' => $langs->trans("Nº oferta"), 'checked' => 1),
		'description' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'date_offer' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
		'user_created' => array('label' => $langs->trans("Realizado garantía"), 'checked' => 1),
		'client' => array('label' => $langs->trans("Cliente"), 'checked' => 1),
		'percentaje_discount' => array('label' => $langs->trans("% Descuento"), 'checked' => 1),
		'total' => array('label' => $langs->trans("Total"), 'checked' => 1),
		'awarded' => array('label' => $langs->trans("Adjudicado"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'num_offer' => array('label' => $langs->trans("Nº oferta"), 'checked' => 0),
            'description' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'date_offer' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
            'user_created' => array('label' => $langs->trans("Realizado por"), 'checked' => 0),
            'client' => array('label' => $langs->trans("Cliente"), 'checked' => 0),
            'percentaje_discount' => array('label' => $langs->trans("% Descuento"), 'checked' => 0),
            'total' => array('label' => $langs->trans("Total"), 'checked' => 0),
            'awarded' => array('label' => $langs->trans("Adjudicado"), 'checked' => 0),
        );

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$sql = "SELECT pof.rowid, p.ref, p.description, p.datec, CONCAT(u.firstname, ' ', u.lastname) as user_created, s.nom, p.opp_percent, p.opp_amount, pof.adjudicado ";
	$sql .= "FROM " . MAIN_DB_PREFIX . "proyectos_obra_oferta pof ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "projet p ON p.rowid = pof.fk_project ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = p.fk_soc ";
	$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "user u ON u.rowid = p.fk_user_creat  ";
	$sql .= "WHERE pof.fk_obra=" . $id;
	
	$result = $db->query($sql);
	
	$num = $db->num_rows($result);
	$nbtotalofrecords = $num;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	$newcardbutton = '';

	$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo proyecto'), '', 'fa fa-plus-circle', $_SERVER['PHP_SELF'].'?action=add&id='.$id);


	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Ofertas"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print "<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>";

	if (!empty($arrayfields['num_offer']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_num_offer">';
		print '</td>';
	}
	if (!empty($arrayfields['description']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="number" name="search_description">';
		print '</td>';
	}
	if (!empty($arrayfields['date_offer']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_date_offer">';
		print '</td>';
	}

	if (!empty($arrayfields['user_created']['checked'])) {
		print '<td class="center liste_titre center">';
		print "<input class='flat maxwidth175imp' type='number' name='search_user_created'>";
		print '</td>';
	}

	if (!empty($arrayfields['client']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_client">';
		print '</td>';
	}

    if (!empty($arrayfields['percentaje_discount']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_percentaje_discount">';
		print '</td>';
	}

    if (!empty($arrayfields['total']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_total">';
		print '</td>';
	}

    if (!empty($arrayfields['awarded']['checked'])) {
		print '<td class="center liste_titre center">';
		print '<input class="flat maxwidth75imp" type="text" name="search_awarded">';
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

	if (!empty($arrayfields['num_offer']['checked'])) {
		print "<th class='center liste_titre' title='nombre'>";
		print "<a class='reposition' href=''>Nº oferta</a>";
		print "</th>";
	}

	if (!empty($arrayfields['description']['checked'])) {
		print "<th class='center liste_titre' title='id_asoc'>";
		print "<a class='reposition' href=''>Descripción</a>";
		print "</th>";
	}

	if (!empty($arrayfields['date_offer']['checked'])) {
		print "<th class='center liste_titre' title='fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

	if (!empty($arrayfields['user_created']['checked'])) {
		print "<th class='center liste_titre' title='tiempo'>";
		print "<a class='reposition' href=''>Realizado</a>";
		print "</th>";
	}

	if (!empty($arrayfields['client']['checked'])) {
		print "<th class='center liste_titre' title='fallo'>";
		print "<a class='reposition' href=''>Cliente</a>";
		print "</th>";
	}

    if (!empty($arrayfields['percentaje_discount']['checked'])) {
		print "<th class='center liste_titre' title='fallo'>";
		print "<a class='reposition' href=''>% descuento</a>";
		print "</th>";
	}

    if (!empty($arrayfields['total']['checked'])) {
		print "<th class='center liste_titre' title='fallo'>";
		print "<a class='reposition' href=''>Total</a>";
		print "</th>";
	}

    if (!empty($arrayfields['awarded']['checked'])) {
		print "<th class='center liste_titre' title='fallo'>";
		print "<a class='reposition' href=''>Adjudicado</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	// $sql = "SELECT id_asoc, firstname, lastname, " . MAIN_DB_PREFIX . "don.datec, " . MAIN_DB_PREFIX . "don_extrafields.tms, amount, fk_statut, fk_object";
	// $sql .= " FROM " . MAIN_DB_PREFIX . "don INNER JOIN " . MAIN_DB_PREFIX . "don_extrafields ON " . MAIN_DB_PREFIX . "don.rowid=" . MAIN_DB_PREFIX . "don_extrafields.fk_object";
	// $sql .= " WHERE id_asoc = '$id_usuario'";

	if (isset($_POST['button_search'])) {
		$activity = array();

		if (isset($_POST['search_num_offer']) && ($_POST['search_num_offer']) != "") {
			$num_offer = "" . $_POST['search_num_offer'] . "";
			$sql .= ' and num_offer =' . $idbusqueda;
		}

		if (isset($_POST['search_description']) && ($_POST['search_description']) != "") {
			$description = "" . $_POST['search_description'] . "";
			$sql .= ' and description like "%' . $nombre . '%"';
		}

		if (isset($_POST['search_date_offer']) && ($_POST['search_date_offer']) != "") {
			$date_offer = "" . $_POST['search_date_offer'] . "";
			$sql .= ' and date_offer like "%' . $apellidos . '%"';
		}

		if (isset($_POST['search_user_created']) && ($_POST['search_user_created']) != "") {
			$user_created = "'" . $_POST['search_user_created'] . "'";
			$sql .= ' and user_created(datec)=' . $fecha;
		}

		if (isset($_POST['search_client']) && ($_POST['search_client']) != "") {
			$client = "" . $_POST['search_client'] . "";
			$sql .= ' and client = ' . $importe . '';
		}

        if (isset($_POST['search_percentaje_discount']) && ($_POST['search_percentaje_discount']) != "") {
			$percentaje_discount = "" . $_POST['search_percentaje_discount'] . "";
			$sql .= ' and percentaje_discount = ' . $importe . '';
		}

        if (isset($_POST['search_total']) && ($_POST['search_total']) != "") {
			$total = "" . $_POST['search_total'] . "";
			$sql .= ' and total = ' . $importe . '';
		}

        if (isset($_POST['search_awarded']) && ($_POST['search_awarded']) != "") {
			$awarded = "" . $_POST['search_awarded'] . "";
			$sql .= ' and awarded = ' . $importe . '';
		}
	}

	print '<form method="POST" action="" name="formfilter" autocomplete="off">';
	$result = $db->query($sql);
	$num = $db->num_rows($sql);
	$i = 0;

	while ($i < $num) {

		$datos = $db->fetch_object($result);
		print '<tr class="oddeven">';

		$adjudicado = $datos->adjudicado == 1 ? "Si" : "No";

		if (!empty($arrayfields['num_offer']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->ref . "</td> ";
		
		if (!empty($arrayfields['description']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->description . "</td> ";

		if (!empty($arrayfields['date_offer']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->datec . "</td> ";

		if (!empty($arrayfields['user_created']['checked']))	print "<td class='center' tdoverflowmax200'>" . $datos->user_created . "</td> ";

		if (!empty($arrayfields['client']['checked']))	print "<td class='center' tdoverflowmax200'>". $datos->nom ."</td> ";
		
        if (!empty($arrayfields['percentaje_discount']['checked']))	print "<td class='center' tdoverflowmax200'>". $datos->opp_percent = $datos->opp_percent ?? 0 ."</td> ";
		
        if (!empty($arrayfields['total']['checked']))	print "<td class='center' tdoverflowmax200'>". number_format($datos->opp_amount, 2) ."</td> ";
		
        if (!empty($arrayfields['awarded']['checked']))	print "<td class='center' tdoverflowmax200'>". $adjudicado ."</td> ";


		print '<td class="center">';
		print '
		<table class="center">
			<tr>
				<td>
					<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $id . '&idDelete=' . $datos->rowid . '">' . img_delete() . '</a>
				</td>
				<td>
					<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $id . '&idEdit=' . $datos->rowid . '">' . img_edit() . '</a>		
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

	$sqlProjets = "SELECT p.rowid, p.title FROM " . MAIN_DB_PREFIX . "projet p";
	$resultProducts = $db->query($sqlProjets);

	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $id . '" name= "id" >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Nueva oferta</span>
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
								<span class="fieldrequired">Oferta</span>
							</td>
							<td>
								<select class="select-projets" style="width: 200px" name="project" id="">';
								while ($projet = $db->fetch_object($resultProducts)) {

									print ' <option value="' . $projet->rowid . '">' . $projet->title . '</option>';
								}
								print '
								</select>		
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Adjudicado</span>
							</td>
							<td>
							<select class="" style="width: 200px" name="adjudicado">
								<option value="0">No</option>
								<option value="1">Si</option>
							</select>
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

	$id = $_GET['id'];
	$idSelected = $_GET['idEdit'];

	$sqlProjets = "SELECT p.rowid, p.title FROM " . MAIN_DB_PREFIX . "projet p";
	$resultProjets = $db->query($sqlProjets);

	$sqlSelected = " SELECT pof.fk_project, pof.adjudicado ";
	$sqlSelected.= " FROM " . MAIN_DB_PREFIX . "proyectos_obra_oferta pof ";
	$sqlSelected.= " WHERE pof.rowid=" . $idSelected;
	$resultSelected = $db->query($sqlSelected);
	$objectSelected = $db->fetch_object($resultSelected);
	
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $idSelected . '" name= "id" >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Nueva oferta</span>
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
								<span class="fieldrequired">Oferta</span>
							</td>
							<td>
								<select class="select-projets" style="width: 200px" name="project" id="">';
								while ($projet = $db->fetch_object($resultProjets)) {

									$isSelected = ($projet->rowid == $objectSelected->fk_project) ? "selected" : "";

									print ' <option '.$isSelected.' value="' . $projet->rowid . '">' . $projet->title . '</option>';
								}
								print '
								</select>		
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Adjudicado</span>
							</td>
							<td>
							<select class="" style="width: 200px" name="adjudicado">';
							$isSelectedNo = ($objectSelected->adjudicado == 0) ? "selected" : "";
							$isSelectedYes = ($objectSelected->adjudicado == 1) ? "selected" : "";

								print '<option '.$isSelectedNo.' value="0">No</option>';
								print '<option '.$isSelectedYes.' value="1">Si</option>';
							print '
							</select>
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

	
	$id = $_GET['id'];
	$idDelete = $_GET['idDelete'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<input type="hidden" value="' . $idDelete . '" name= "id" >
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Confirmar eliminación</span>
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
					<span class="field">Estas seguro de eliminar el registro?</span>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="delete">
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

print "
<script>
	$(document).ready(function() {
		$('.select-projets').select2();
	});
</script>
";



// End of page
llxFooter();
$db->close();
ob_flush();
