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

/*
 * View
 */
$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Otros datos'), $help_url);

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

	print dol_get_fiche_head($head, 'other_data', '', -1, $object->picto);

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

	$id_usuario = $object->id;

	$sqlData = " SELECT (pom.taxable_base) as line_taxable_base,";
	$sqlData.= " SUM(pom.price) as total_price, SUM(pom.material_cost) as total_material_cost,";
	$sqlData.= " SUM(pom.price) as total_price, SUM(pom.material_cost) as total_material_cost, SUM(pom.transport_cost + pom.installation_cost) as total_expenses,";
	$sqlData.= " SUM(pom.transport_cost) as total_transport_cost, SUM(pom.installation_cost) as total_installation_cost,";
	$sqlData.= " SUM(pom.development_cost) as total_development_cost, SUM(pom.test_cost) as total_test_cost, SUM(pom.taxable_base) as total_taxable_base,";
	$sqlData.= " SUM(pom.quantity * pom.price *((100 - pom.discount)/100) + pom.quantity * (pom.transport_cost + pom.installation_cost)) as total_cost,";
	$sqlData.= " FORMAT(SUM(pom.price * ( 1 + ( IFNULL(pe.team_plus, 0) / 100 ) ) + (pom.transport_cost + pom.installation_cost) * ( 1 + ( IFNULL(pe.installations_plus, 0) / 100 ) ) * ( 1 + ( IFNULL(pe.percentage_plus, 0) / 100 ) ) ) * pom.quantity - SUM(pom.quantity * pom.price * ( (100 - pom.discount)/100) + pom.quantity * (pom.transport_cost + pom.installation_cost)),2) as revenue";
	$sqlData.= " FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
    $sqlData.= " INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = pom.fk_project ";
	$sqlData.= " WHERE pom.fk_project =".$id;
	
	$resultData = $db->query($sqlData);

	while ($row = $db->fetch_object($resultProducts)) {							
		$data = $row;
	}
    //unidades*precio_unitario*((100-porcentaje_descuento)/100)+unidades*(gastos_transporte+gastos_montaje) COSTO TOTAL
    

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;


	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Otros datos"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	//print '<div class="div-table-responsive">';
	print '<table class="border tableforfield centpercent">';
        print '<tbody>';
            print '<tr>';
                print '<td class="titlefield">Ref. anterior</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Garantía</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Porcentaje resolución</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Suma</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Dto. P.P</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Subtotal</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste material</td>';
                print '<td>'.$data->total_material_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Gastos</td>';
                print '<td>'.$data->total_expenses.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste transporte</td>';
                print '<td>'.$data->total_transport_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste desarrollo</td>';
                print '<td>'.$data->total_development_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste instalación</td>';
                print '<td>'.$data->total_installation_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste pruebas</td>';
                print '<td>'.$data->total_test_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Coste total</td>';
                print '<td>'.$data->total_cost.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Base imponible</td>';
                print '<td>'.$data->total_taxable_base.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Beneficios</td>';
                print '<td>'.$data->revenue.'</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">IVA / Impuestos</td>';
                print '<td></td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefield">Total importe</td>';
                print '<td></td>';
            print '</tr>';
        print '</tbody>';
    print '</table>';
    //print '</div>';

}

// End of page
llxFooter();
$db->close();
ob_flush();
