<?php
ob_start();

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$url = $_SERVER["PHP_SELF"]."?id=".$id; 
// Initialize technical objects
$object = new Project($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once

if (isset($_POST['addMaterial'])) {

	extract($_POST);

	//Para sacar los precios del producto
	$sqlPrecios = " SELECT price FROM ".MAIN_DB_PREFIX."product p ";
	$sqlPrecios.= " WHERE rowid = ".$product_id." ";

	$resultPrecios = $db->query($sqlPrecios);
	$precio = $db->fetch_object($resultPrecios);
	$price = $precio->price;

	$precioTotal = $price * $quantity;

	if ($discount == "") {
		$discount = 0;
	}

	$aDescontar = ($precioTotal * $discount) / 100;
	$taxable_base = $precioTotal - $aDescontar;
	
	$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_oferta_materiales ( fk_project, fk_product, fk_chapter, quantity, price, discount, taxable_base, equivalence) "; //, material_cost, transport_cost, installation_cost, development_cost, test_cost, equivalence )";
	$sqlInsert.= " VALUES ( ".$id.",".$product_id.",".$chapter_id.",".$quantity.",".$price.",".$discount.",".$taxable_base.", '".$equivalence."') "; //.$material_cost.", ".$transport_cost.", ".$installation_cost.", ".$development_cost.", ".$test_cost.", '".$equivalence."' )";
	$resultInsert = $db->query($sqlInsert);
	
	$message = ($resultInsert) ? "Material añadido con éxito" : "Error al añadir el material";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);

}elseif (isset($_POST['deleteMaterial'])) {

	$line_id = $_POST['line_id'];

	$sqlDelete = " DELETE FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales WHERE rowid = ".$line_id;
	$resultDelete = $db->query($sqlDelete);
		
	$message = ($resultDelete) ? "Línea borrada con éxito" : "Error al borrar la línea";
	$type = ($resultDelete) ? "mesgs" : "errors";

	setEventMessage($message, $type);

}elseif (isset($_POST['editMaterial'])) {

	extract($_POST);

	/*$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."proyectos_oferta_materiales";
	$sqlUpdate.= " SET fk_project = ".$id.", fk_product = ".$product_id.", fk_chapter = ".$chapter_id.",";
	$sqlUpdate.= " quantity = ".$quantity.", price = ".$price.", discount = ".$discount.", taxable_base = ".$taxable_base.",";
	$sqlUpdate.= " material_cost = ".$material_cost.", transport_cost = ".$transport_cost.", installation_cost = ".$installation_cost.",";
	$sqlUpdate.= " development_cost = ".$development_cost.", test_cost = ".$test_cost.", equivalence = '".$equivalence."'";
	$sqlUpdate.= " WHERE rowid = ".$line_id;
	$resultUpdate = $db->query($sqlUpdate);*/

	//Para sacar los precios del producto
	$sqlPrecios = " SELECT price FROM ".MAIN_DB_PREFIX."product p ";
	$sqlPrecios.= " WHERE rowid = ".$product_id." ";

	$resultPrecios = $db->query($sqlPrecios);
	$precio = $db->fetch_object($resultPrecios);
	$price = $precio->price;

	$precioTotal = $price * $quantity;

	if ($discount == "") {
		$discount = 0;
	}

	$aDescontar = ($precioTotal * $discount) / 100;
	$taxable_base = $precioTotal - $aDescontar;

	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."proyectos_oferta_materiales";
	$sqlUpdate.= " SET fk_project = ".$id.", fk_product = ".$product_id.", fk_chapter = ".$chapter_id.",";
	$sqlUpdate.= " quantity = ".$quantity.", discount = ".$discount.", taxable_base = ".$taxable_base.",";
	$sqlUpdate.= " equivalence = '".$equivalence."'";
	$sqlUpdate.= " WHERE rowid = ".$line_id;

	$resultUpdate = $db->query($sqlUpdate);
		
	$message = ($resultUpdate) ? "Línea editada con éxito" : "Error al editar la línea";
	$type = ($resultUpdate) ? "mesgs" : "errors";

	setEventMessage($message, $type);

}elseif (isset($_POST['createChapter'])) {

	$project_id = $_POST['project_id'];
	$name = $_POST['name'];
	

	$sqlInsert = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_capitulo ( fk_project, name )";
	$sqlInsert.= " VALUES ( ".$project_id.",'".$name."' )";
	$resultInsert = $db->query($sqlInsert);
	
	$message = ($resultInsert) ? "Capítulo añadido con éxito" : "Error al añadir el capítulo";
	$type = ($resultInsert) ? "mesgs" : "errors";

	setEventMessage($message, $type);

} elseif (isset($_POST['createExpense'])) {

	$project_id = $_POST['project_id'];
	$cantidad = $_POST['cantidad'];
	$tipo = $_POST['tipo'];
	
	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."projet_extrafields ";

	if ($tipo == 1) {
		$sqlUpdate.= " SET coste_material = IFNULL(coste_material, 0) + ".$cantidad."";
	} elseif ($tipo == 2) {
		$sqlUpdate.= " SET coste_pruebas = IFNULL(coste_pruebas, 0) + ".$cantidad."";
	} elseif ($tipo == 3) {
		$sqlUpdate.= " SET coste_instalacion = IFNULL(coste_instalacion, 0) + ".$cantidad."";
	}

	$sqlUpdate.= " WHERE fk_object = ".$project_id;

	$resultUpdate = $db->query($sqlUpdate);
	
	$message = ($resultUpdate) ? "Coste añadido con éxito" : "Error al añadir el coste";
	$type = ($resultUpdate) ? "mesgs" : "errors";

	setEventMessage($message, $type);

} elseif (isset($_POST['editExpense'])) {
	
	$coste_material = $_POST['coste_material'];
	$coste_pruebas = $_POST['coste_pruebas'];
	$coste_instalacion = $_POST['coste_instalacion'];
	
	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."projet_extrafields ";

	if ($coste_material == "") {
		$coste_material = 0;
	}

	if ($coste_pruebas == "") {
		$coste_pruebas = 0;
	}

	if ($coste_instalacion == "") {
		$coste_instalacion = 0;
	}

	$sqlUpdate.= " SET coste_material = ".$coste_material.", coste_pruebas = ".$coste_pruebas.", coste_instalacion = ".$coste_instalacion." ";
	$sqlUpdate.= " WHERE fk_object = ".$id." ";

	$resultUpdate = $db->query($sqlUpdate);
	
	$message = ($resultUpdate) ? "Coste actualizado con éxito" : "Error al actualizar el coste";
	$type = ($resultUpdate) ? "mesgs" : "errors";

	setEventMessage($message, $type);

}  

/*
 * View
 */
$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Materiales y operaciones'), $help_url);

if ($id > 0 || !empty($ref)) {
	
    $ret = $object->fetch($id, $ref); // If we create project, ref may be defined into POST but record does not yet exists into database
    if ($ret > 0) {
        $object->fetch_thirdparty();
        if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) {
            $object->fetchComments();
        }
        $id = $object->id;
    }
    

	$head = project_prepare_head($object);

	print dol_get_fiche_head($head, 'materials', '', -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/projet/list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
    // Title
    $morehtmlref .= dol_escape_htmltag($object->title);
    // Thirdparty
    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : ';
    if ($object->thirdparty->id > 0) {
        $morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
    }
    $morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	print dol_get_fiche_end();
	$sqlInstallationCost = "( pom.installation_cost * 2 * ( (IFNULL(pe.team_labour_unit_cost, 0) * IFNULL(pe.team_labour_hours_day, 0)) + IFNULL(pe.team_overnight_unit_cost, 0) + IFNULL(pe.team_daily_diet_unit_cost, 0) + IFNULL(pe.team_vehicle_unit_cost, 0) ) + ( (IFNULL(pe.displacement_labour_unit_cost, 0) * IFNULL(pe.displacement_labour_km, 0) * IFNULL(pe.displacement_labour_quantity, 0) ) + (IFNULL(pe.displacement_overnight_unit_cost, 0) * IFNULL(pe.displacement_overnight_quantity, 0)) + (IFNULL(pe.displacement_daily_diet_unit_cost, 0) * IFNULL(pe.displacement_daily_diet_quantity, 0)) + (IFNULL(pe.displacement_travel_hours_km, 0) * IFNULL(pe.displacement_travel_unit_cost, 0) * IFNULL(pe.displacement_travel_quantity, 0)) ) )";
	
	$sqlTotalSoldPrice = "SUM(pom.price * ( 1 + ( IFNULL(pe.team_plus, 0) / 100 ) ) + (pom.transport_cost + ".$sqlInstallationCost." ) * ( 1 + ( IFNULL(pe.installations_plus, 0) / 100 ) ) * ( 1 + ( IFNULL(pe.percentage_plus, 0) / 100 ) ) ) * pom.quantity";
	
	$sqlTotalCost = "SUM(pom.quantity * pom.price * ((100 - pom.discount)/100) + pom.quantity * (pom.transport_cost + ".$sqlInstallationCost."  ))";

	$id_usuario = $object->id;
	
	$sqlMaterials = " SELECT pc.rowid as chapter_id, pc.name as chapter_name, GROUP_CONCAT(pom.equivalence) as line_equivalence, GROUP_CONCAT(pom.rowid) as line_id, GROUP_CONCAT(p.rowid) as product_id,";
	$sqlMaterials.= " GROUP_CONCAT(p.ref) as product_reference, GROUP_CONCAT(p.description SEPARATOR ';') as product_description,";
	$sqlMaterials.= " GROUP_CONCAT(pom.quantity) as line_quantity, GROUP_CONCAT(pom.price) as line_price, GROUP_CONCAT(pom.discount) as line_discount,";
	$sqlMaterials.= " GROUP_CONCAT(pom.taxable_base) as line_taxable_base,";

	$sqlMaterials.= " GROUP_CONCAT(pom.material_cost) as line_material_cost,";
	$sqlMaterials.= " GROUP_CONCAT(pom.transport_cost) as line_transport_cost, GROUP_CONCAT(pom.installation_cost) as line_installation_cost,";
	$sqlMaterials.= " GROUP_CONCAT(pom.development_cost) as line_development_cost, GROUP_CONCAT(pom.test_cost) as line_test_cost,";
	$sqlMaterials.= " GROUP_CONCAT(pom.taxable_base) as line_taxable_base,";
	
	$sqlMaterials.= " FORMAT(".$sqlTotalCost.",2) as total_cost,";

	$sqlMaterials.= " FORMAT(".$sqlTotalSoldPrice.",2) as total_sold_price,";

	$sqlMaterials.= " FORMAT( ".$sqlTotalSoldPrice." - ".$sqlTotalCost.",2) as revenue,";

	$sqlMaterials.= " SUM(pom.price) as total_price, SUM(pom.material_cost) as total_material_cost, SUM(pom.transport_cost + pom.installation_cost) as total_expenses,";
	$sqlMaterials.= " SUM(pom.transport_cost) as total_transport_cost, SUM(pom.installation_cost) as total_installation_cost,";
	$sqlMaterials.= " SUM(pom.development_cost) as total_development_cost, SUM(pom.test_cost) as total_test_cost, SUM(pom.taxable_base) as total_taxable_base";
	$sqlMaterials.= " FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
	$sqlMaterials.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product ";
	$sqlMaterials.= " INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = pom.fk_project ";
	$sqlMaterials.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_capitulo pc ON pc.rowid = pom.fk_chapter ";
	$sqlMaterials.= " WHERE pom.fk_project =".$id;
	$sqlMaterials.= " GROUP BY pc.rowid";
	
	$resultMaterials = $db->query($sqlMaterials);
	
	$sqlProducts = "SELECT p.rowid, p.label, p.ref FROM ".MAIN_DB_PREFIX."product p";
	$resultProducts = $db->query($sqlProducts);
	$productsList = [];
	while ($product = $db->fetch_object($resultProducts)) {							
		$productsList[] = $product;
	}
	
	$sqlChapters = "SELECT pc.rowid, pc.name FROM ".MAIN_DB_PREFIX."proyectos_capitulo pc";
	$resultChapters = $db->query($sqlChapters);
	$chaptersList = [];
	while ($chapter = $db->fetch_object($sqlChapters)) {							
		$chaptersList[] = $chapter;
	}
	

	
	
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;
	print '<a class="butAction" type="button" href="#addMaterialModal" rel="modal:open">Añadir material</a>';
	print '<a class="butAction" type="button" href="#addChapterModal" rel="modal:open">Añadir capítulo</a>';
	print '<a class="butAction" type="button" href="#addExpenseModal" rel="modal:open">Añadir coste</a>';
	print '
	<div id="addMaterialModal" class="modal" role="dialog" aria-labelledby="addMaterialModal" aria-hidden="true">
		<form action="'.$url.'" method="POST" >
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3 id="myModalLabel">Añadir material</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Partida</label>
							</td>
							<td>
								<input name="equivalence" type="text">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Producto</label>
							</td>
							<td>
								<select id="product_id_create" onchange="getProductById(this,`price_create`,`cost_materials_create`);" style="width: 250px;" class="select-products" name="product_id">
								<option value="-1" selected disabled>Selecciona un producto</option>
								';
	
								foreach ($productsList as $key => $product) {
			
									print ' <option value="'.$product->rowid.'">'.$product->ref.' - '.$product->label.'</option>';
								}
			
								print '
								</select>
								<a href="../product/card.php?action=create"><span class="fa fa-plus-circle valignmiddle paddingleft" title="Crear producto"></span></a>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Capítulo</label>
							</td>
							<td>
								<select style="width: 150px;" class="select-products" name="chapter_id">
								';
			
								foreach ($chaptersList as $key => $chapter) {
			
									print ' <option value="'.$chapter->rowid.'">'.$chapter->name.'</option>';
								}
			
								print '
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Cantidad</label>
							</td>
							<td>
								<input name="quantity" step="0.01" type="number">
							</td>
						</tr>';
						/*<tr>
							<td>
								<label for="">Precio</label>
							</td>
							<td>
								<input id="price_create" name="price" step="0.01" type="number">
							</td>
						</tr>*/
						print '<tr>
							<td>
								<label for="">Descuento</label>
							</td>
							<td>
								<input name="discount" step="0.01" type="number">
							</td>
						</tr>';
						/*<tr>
							<td>
								<label for="">Base imponible</label>
							</td>
							<td>
								<input name="taxable_base" step="0.01" type="number">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Coste material</label>
							</td>
							<td>
								<input id="cost_materials_create" name="material_cost" step="0.01" type="number">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Coste transporte</label>
							</td>
							<td>
								<input name="transport_cost" step="0.01" type="number">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Coste instalación</label>
							</td>
							<td>
								<input name="installation_cost" step="0.01" type="number">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Coste desarrollo</label>
							</td>
							<td>
								<input name="development_cost" step="0.01" type="number">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Coste pruebas</label>
							</td>
							<td>
								<input name="test_cost" step="0.01" type="number">
							</td>
						</tr>*/
					print '</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="addMaterial" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	print '
	<div id="addChapterModal" class="modal" role="dialog" aria-labelledby="addChapterModal" aria-hidden="true">
		<form action="'.$url.'" method="POST">
			<input name="project_id" type="hidden" value="'.$id.'">
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3>Crear capítulo</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Nombre</label>
							</td>
							<td>
								<input name="name" type="text">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="createChapter" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';

	print '
	<div id="addExpenseModal" class="modal" role="dialog" aria-labelledby="addExpenseModal" aria-hidden="true">
		<form action="'.$url.'" method="POST">
			<input name="project_id" type="hidden" value="'.$id.'">
			<div class="modal-header">
				<a href="#" rel="modal:close">X</a>
				<h3>Añadir Coste</h3>
			</div>
			<div class="modal-body">
				<table>
					<tbody>
						<tr>
							<td>
								<label for="">Cantidad</label>
							</td>
							<td>
								<input name="cantidad" type="text">
							</td>
						</tr>
						<tr>
							<td>
								<label for="">Tipo</label>
							</td>
							<td>
								<select name="tipo" class="select_tipo" style="width:100%">
									<option value="-1">&nbsp;</option>
									<option value="1">Material</option>
									<option value="2">Pruebas</option>
									<option value="3">Instalación</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="createExpense" class="butAction">Añadir</button>
			</div>
		</form>
	</div>
	';
	
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Materiales y operaciones"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);
	print '<div>';
	while ($line = $db->fetch_object($resultMaterials)) {

		$productIds = explode(",",$line->product_id);
		$productReferences = explode(",",$line->product_reference);
		$productDescriptions = explode(";",$line->product_description);

		$lineEquivalences = explode(",",$line->line_equivalence);
		$lineIds = explode(",",$line->line_id);
		$lineQuantities = explode(",",$line->line_quantity);
		$linePrices = explode(",",$line->line_price);
		$lineDiscounts = explode(",",$line->line_discount);
		$lineTaxableBases = explode(",",$line->line_taxable_base);

		$lineMaterialCosts = explode(",",$line->line_material_cost);
		$lineTransportCosts = explode(",",$line->line_transport_cost);
		$lineInstallationCosts = explode(",",$line->line_installation_cost);
		$lineDevelopmentsCosts = explode(",",$line->line_development_cost);
		$lineTestCosts = explode(",",$line->line_test_cost);
		$lineTaxableBaseCosts = explode(",",$line->line_taxable_base);
		
		print '<button class="butAction" id="btn_toggle_'.$line->chapter_id.'" type="button" onclick="toggle('.$line->chapter_id.')">-</button>';
		print '<label for="">'.$line->chapter_name.'</label>';
			print '<table id="offer_line_'.$line->chapter_id.'" class="border tableforfield centpercent">';
				print '<thead>';
					print '<tr>';
						print '<th>PARTIDA</td>';
						print '<th>COD. ART/SERVICIO</td>';
						print '<th>DESCRIPCIÓN</td>';
						print '<th>UNIDADES</td>';
						print '<th>PRECIO UNI.</td>';
						print '<th>DTO (%)</td>';
						print '<th>PRECIO UNI. FINAL</td>';
						//print '<th>PRECIO TOTAL.</td>';
						print '<th>BRUTO</td>';
						print '<th>ACCIONES</td>';
					print '</tr>';
				print '</thead>';
				print '<tbody>';
					for ($i = 0; $i < count($lineIds); $i++) { 

						$precioTotal = $lineQuantities[$i] * $linePrices[$i];
						$aDescontar = ($linePrices[$i] * $lineDiscounts[$i]) / 100;
						$precioUnidadFInal = $linePrices[$i] - $aDescontar;
						
						print '<tr>';
							print '<td class="center liste_titre center">'.$lineEquivalences[$i].'</td>';
							print '<td class="center liste_titre center">'.$productReferences[$i].'</td>';
							print '<td class="center liste_titre center">'.$productDescriptions[$i].'</td>';
							print '<td class="center liste_titre center">'.strtr(number_format($lineQuantities[$i],2),['.' => ',', ',' => '.']).'</td>';
							print '<td class="center liste_titre center">'.strtr(number_format($linePrices[$i],2),['.' => ',', ',' => '.']).'</td>';
							print '<td class="center liste_titre center">'.strtr(number_format($lineDiscounts[$i],2),['.' => ',', ',' => '.']).' %</td>';
							print '<td class="center liste_titre center">'.strtr(number_format($precioUnidadFInal,2),['.' => ',', ',' => '.']).'</td>';
							//print '<td class="center liste_titre center">'.$precioTotal.'</td>';
							print '<td class="center liste_titre center">'.strtr(number_format($lineTaxableBases[$i],2),['.' => ',', ',' => '.']).'</td>';
							print '<td class="center liste_titre center">';
								print '<a type="button" href="#editMaterialModal'.$lineIds[$i].'" rel="modal:open">'.img_edit().'</a>&nbsp&nbsp';
								print '<a type="button" href="#deleteMaterialModal'.$lineIds[$i].'" rel="modal:open">'.img_delete().'</a>';
							print '</td>';
						print '</tr>';

						$checked = $lineIsCompound[$i] == 1 ? "checked" : "";

						print '
						<div id="editMaterialModal'.$lineIds[$i].'" class="modal" role="dialog" aria-labelledby="editMaterialModal'.$lineIds[$i].'" aria-hidden="true">
							<form action="'.$url.'" method="POST">
								<input name="line_id" type="hidden" value="'.$lineIds[$i].'">
								<div class="modal-body">
									<br><br>
									<div>
										<label for="">Partida</label>
										<input name="equivalence" type="text" value="'.$lineEquivalences[$i].'">
									</div>
									<div>

										<label for="">Producto</label>
										<select id="product_id_update_'.$lineIds[$i].'" onchange="getProductById(this,`price_update_'.$lineIds[$i].'`,`cost_materials_update_'.$lineIds[$i].'`);" style="width: 250px;" class="select-products" name="product_id">
										';
					
										foreach ($productsList as $key => $product) {
											
											$selected = ( $product->rowid == $productIds[$i] ) ? "selected" : "";
											print ' <option '.$selected.' value="'.$product->rowid.'">'.$product->ref.' - '.$product->label.'</option>';
										}
					
										print '
										</select>
										<a href="../product/card.php?action=create"><span class="fa fa-plus-circle valignmiddle paddingleft" title="Crear producto"></span></a>
									</div>
									<div>
										<label for="">Capítulo</label>
										<select style="width: 150px;" class="select-products" name="chapter_id">
										';
					
										foreach ($chaptersList as $key => $chapter) {
											$selected = ( $chapter->rowid == $line->chapter_id ) ? "selected" : "";
											print ' <option '.$selected.' value="'.$chapter->rowid.'">'.$chapter->name.'</option>';
										}
					
										print '
										</select>
									</div>
							
									<div>
										<label for="">Cantidad</label>
										<input name="quantity" step="0.01" type="number" value="'.$lineQuantities[$i].'">
									</div>';
										
									print '<div>
										<label for="">Descuento</label>
										<input name="discount" step="0.01" type="number" value="'.$lineDiscounts[$i].'">
									</div>';
										
									/*<div>
										<label for="">Base imponible</label>
										<input name="taxable_base" step="0.01" type="number" value="'.$lineTaxableBases[$i].'">
									</div>
									<div>
										<label for="">Coste material</label>
										<input name="material_cost" id="cost_materials_update_'.$lineIds[$i].'" step="0.01" type="number" value="'.$lineMaterialCosts[$i].'">
									</div>
									<div>
										<label for="">Coste transporte</label>
										<input name="transport_cost" step="0.01" type="number" value="'.$lineTransportCosts[$i].'">
									</div>
									<div>
										<label for="">Coste instalación</label>
										<input name="installation_cost" step="0.01" type="number" value="'.$lineInstallationCosts[$i].'">
									</div>
									<div>
										<label for="">Coste desarrollo</label>
										<input name="development_cost" step="0.01" type="number" value="'.$lineDevelopmentsCosts[$i].'">
									</div>
									<div>
										<label for="">Coste pruebas</label>
										<input name="test_cost" step="0.01" type="number" value="'.$lineTestCosts[$i].'">
									</div>*/
												
								print '</div>
								<br>
								<div>
									<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
									<button type="submit" name="editMaterial" class="butAction">Editar</button>
								</div>
							</form>
						</div>
						';

						print '
						<div id="deleteMaterialModal'.$lineIds[$i].'" class="modal" role="dialog" aria-labelledby="deleteMaterialModal'.$lineIds[$i].'" aria-hidden="true">
							<form action="'.$url.'" method="POST">
								<input name="line_id" type="hidden" value="'.$lineIds[$i].'">
								<div class="modal-header">
									<a href="#" rel="modal:close">X</a>
									<h3>Estas seguro de borrar esta línea?</h3>
								</div>
								<div class="modal-body">
								</div>
								<br>
								<div>
									<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
									<button type="submit" name="deleteMaterial" class="butAction">Eliminar</button>
								</div>
							</form>
						</div>
						';

					}	
					/*print '<tr>';
						print '<td class="center liste_titre center">Coste material</td>';
						print '<td>'.$line->total_material_cost.'</td>';

						print '<td class="center liste_titre center">Coste transporte</td>';
						print '<td>'.$line->total_transport_cost.'</td>';

						print '<td class="center liste_titre center">Coste instalación</td>';
						print '<td>'.$line->total_installation_cost.'</td>';

						print '<td class="center liste_titre center">Coste total</td>';
						print '<td>'.$line->total_cost.'</td>';
					print '</tr>';
					print '<tr>';
						print '<td class="center liste_titre center">Gastos</td>';
						print '<td>'.$line->total_expenses.'</td>';

						print '<td class="center liste_titre center">Coste desarrollo</td>';
						print '<td>'.$line->total_development_cost.'</td>';

						print '<td class="center liste_titre center">Coste pruebas</td>';
						print '<td>'.$line->total_test_cost.'</td>';

						print '<td class="center liste_titre center">Beneficio</td>';
						print '<td>'.$line->revenue.'</td>';
					print '</tr>';
					print '<tr>';
						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center">Base imponible</td>';
						print '<td>'.$line->total_taxable_base.'</td>';

					print '</tr>';
					print '<tr>';
						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center"></td>';
						print '<td></td>';

						print '<td class="center liste_titre center">Total</td>';
						print '<td>'.$line->total_sold_price.'</td>';
					print '</tr>';*/
				print '</tbody>';
			print '</table>';
		print '</div>';
	}
	/*print '<table class="border tableforfield centpercent">';
        print '<tbody>';
            print '<tr>';
                print '<td class="titlefield">Observaciones</td>';
                print '<td></td>';
            print '</tr>';
			print '<tr>';
                print '<td class="titlefield">Plazo entrega</td>';
                print '<td></td>';
            print '</tr>';
        print '</tbody>';
    print '</table>';*/
	print "<br>";

	//PARA COSTE DE MATERIAL, DE PRUEBAS Y DE INSTALACIÓN
	$sqlCostes = " SELECT coste_material, coste_pruebas, coste_instalacion FROM ".MAIN_DB_PREFIX."projet_extrafields ";
	$sqlCostes.= " WHERE fk_object = ".$object->id." ";

	$resultCostes = $db->query($sqlCostes);
	$coste = $db->fetch_object($resultCostes);

	//PARA LAS HORAS Y LOS COTES DE HORAS
	$sqlTasks = " SELECT t.*, tt.* FROM ".MAIN_DB_PREFIX."projet_task t ";
	$sqlTasks.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task_time tt ON tt.fk_task = t.rowid ";
	$sqlTasks.= " WHERE t.fk_projet = ".$object->id." ";

	$resultTasks = $db->query($sqlTasks);
	
	$horas_totales = 0;
	$coste_horas_total = 0;
	while ($task = $db->fetch_object($resultTasks)) {

		$segundos = $task->task_duration;
		$aHoras = ($segundos / 60) / 60;

		$horas_totales+= $aHoras;
		$coste_horas_total+= $aHoras * $task->thm;

	}

	print '<table id="expenses" class="border tableforfield centpercent" style="width: 50%;">';
	print '<thead>';
		print '<tr>';
			print '<th>COSTE MATERIAL</td>';
			print '<th>COSTE PRUEBAS</td>';
			print '<th>COSTE INSTALACIÓN</td>';
			print '<th>COSTE HORAS</td>';
			print '<th>HORAS DEDICADAS</td>';
			print '<th>ACCIONES</td>';
		print '</tr>';
		print '<tr>';
			print '<td class="center liste_titre center">'.strtr(number_format($coste->coste_material,2),['.' => ',', ',' => '.']).'</td>';
			print '<td class="center liste_titre center">'.strtr(number_format($coste->coste_pruebas,2),['.' => ',', ',' => '.']).'</td>';
			print '<td class="center liste_titre center">'.strtr(number_format($coste->coste_instalacion,2),['.' => ',', ',' => '.']).'</td>';
			print '<td class="center liste_titre center">'.strtr(number_format($coste_horas_total,2),['.' => ',', ',' => '.']).'</td>';
			print '<td class="center liste_titre center">'.strtr(number_format($horas_totales,2),['.' => ',', ',' => '.']).'</td>';
			print '<td><a type="button" href="#editExpenseModal'.$lineIds[$i].'" rel="modal:open">'.img_edit().'</a></td>';
		print '</tr>';
	print '</thead>';
	print '<tbody>';


	print '
	<div id="editExpenseModal'.$lineIds[$i].'" class="modal" role="dialog" aria-labelledby="editExpenseModal'.$lineIds[$i].'" aria-hidden="true">
		<form action="'.$url.'" method="POST">
			<input name="line_id" type="hidden" value="'.$lineIds[$i].'">
			<div class="modal-body">
				<br><br>	
				<div>
					<label for="">Coste Material</label>
					<input name="coste_material" step="0.01" type="number" value="'.number_format($coste->coste_material,2).'">
				</div>

				<div>
					<label for="">Coste Pruebas</label>
					<input name="coste_pruebas" step="0.01" type="number" value="'.number_format($coste->coste_pruebas,2).'">
				</div>
				
				<div>
					<label for="">Coste Instalación</label>
					<input name="coste_instalacion" step="0.01" type="number" value="'.number_format($coste->coste_instalacion,2).'">
				</div>';
							
			print '</div>
			<br>
			<div>
				<a class="butAction" type="button" href="#" rel="modal:close">Cerrar</a>
				<button type="submit" name="editExpense" class="butAction">Editar</button>
			</div>
		</form>
	</div>
	';



}
print  '
<script>
	$(".select-products").select2();
	$(".select_tipo").select2();

	function toggle(id){
		if($("#btn_toggle_"+id).text() == "-"){

			$("#btn_toggle_"+id).text("+");
		}else{
			$("#btn_toggle_"+id).text("-");
		}
		$("#offer_line_"+id).toggle();
	}

	function getProductById(idProduct,idPrice,idMaterials){
		
		let url = "projectController.php?id="+idProduct.value;
		
		fetch(url)
		.then(response => response.json())
		.then(data => printResult(data,idPrice,idMaterials));
	}

	function printResult(response,idPrice,idMaterials){
		
		if(response["status"] == 200){
			document.getElementById(idPrice).value = response["priceProduct"];
			document.getElementById(idMaterials).value = response["priceMaterials"];
		} 
	}

</script>';
print '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />';
// End of page
llxFooter();
$db->close();
ob_flush();
