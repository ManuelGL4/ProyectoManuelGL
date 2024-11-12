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

const MONTHS = [
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre'
];

$fecha_actual = date("d-m-Y"); // Obtener la fecha actual en formato "Año-Mes-Día"

// Separar la fecha en año, mes y día
list($dia, $mes, $ano) = explode("-", $fecha_actual);

//Para sacar el cliente
$consultaCli = " SELECT s.nom, s.code_client FROM ".MAIN_DB_PREFIX."societe s ";
$consultaCli.= " INNER JOIN ".MAIN_DB_PREFIX."projet p ON p.fk_soc = s.rowid ";
$consultaCli.= " WHERE p.rowid = ".$id;

$resultConsultaCli = $db->query($consultaCli);
$cli = $db->fetch_object($resultConsultaCli);



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
llxHeader('', $langs->trans('Certificaciones'), $help_url);

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

	print dol_get_fiche_head($head, 'certificaciones', '', -1, $object->picto);

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






    //PARA EL ENVÍO
        $arrayfields = array(
            'referencia' => array('label' => $langs->trans("Referencia"), 'checked' => 1),
            'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
        );
    
        if (isset($_POST["selectedfields"])) {
            $fieldsSelected = $_POST["selectedfields"];
            $fieldsSelectedArray = explode(",", $fieldsSelected);
    
            $arrayfields = array(
                'referencia' => array('label' => $langs->trans("Referencia"), 'checked' => 0),
                'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 0),
            );
    
            foreach ($fieldsSelectedArray as $key => $value) {
                $arrayfields[$value]["checked"] = 1;
            }
        }
    
        $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
    
    
    
        $id_usuario = $object->id;
    
        $i = 0;
    
        $param = '';
        if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;
    
        //$newcardbutton = '';
        //$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo parte'), '', 'fa fa-plus-circle', '#addEquipmentModal');
    
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';

        print_barre_liste($langs->trans("Pedido - Albaranes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);
    
        print '<div class="fichethirdleft" style="margin-right:20px">';

        print '<div class="div-table-responsive">';
        print '<table class="tagtable liste">' . "\n";
    
        print '<tr class="liste_titre">';
    
        if (!empty($arrayfields['referencia']['checked'])) {
            print "<th class='center liste_titre' title='Referencia'>";
            print "<a class='reposition' href=''>Referencia</a>";
            print "</th>";
        }
    
        if (!empty($arrayfields['fecha']['checked'])) {
            print "<th class='center liste_titre' title='Fecha'>";
            print "<a class='reposition' href=''>Fecha</a>";
            print "</th>";
        }
    
        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
        print "</tr>";
    
        /*$sqlOfertas = " SELECT rowid, ref, nombre ";
        $sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
        $sqlOfertas.= " WHERE averia = ".$id;
    
        $resultOferta = $db->query($sqlOfertas);
        $numOfertas = $db->num_rows($resultOferta);
        $ofertalinea = $db->fetch_object($resultOferta);
    
        if ($numOfertas > 0) {*/
    
        $sqlEnvio = " SELECT * ";
        $sqlEnvio.= " FROM ".MAIN_DB_PREFIX."commande ";
        $sqlEnvio.= " WHERE fk_projet = ".$id." ";
        
        $resultEnvio = $db->query($sqlEnvio);
        $numEnvios = $db->num_rows($resultEnvio);
    
        //}
    
       $envio = $db->fetch_object($resultEnvio);
    
        print '<tr class="oddeven">';

        if ($numEnvios > 0) {

            if (!empty($arrayfields['referencia']['checked']))
            print "<td class='center' tdoverflowmax200'><a href='../commande/card.php?id=".$envio->rowid."'>".$envio->ref."</a></td>";


            $fecha_formateada = date("d-m-Y", strtotime($envio->date_creation));

            list($dia2, $mes2, $ano2) = explode("-", $fecha_formateada);

            if (!empty($arrayfields['fecha']['checked']))
            print "<td class='center' tdoverflowmax200'>".$fecha_formateada."</td> "; 

            print '<td class="center">';
            print '
                <table class="center">
                    <tr>
                        <td>
                    
                        </td>
                    </tr>
                </table>
                ';
            print '</td>';

        }
        

        print "</tr>";
            
        print "</table>";
    
        print '</div>';

        print '<div class="tabsAction">';

        if ($numEnvios == 0) {
            print '<a class="butAction" type="button" href="../commande/card.php?action=create&projectid='.$id.'&socid='.$object->thirdparty->id.'&leftmenu=orders&idpro='.$id.'" target="_blank">Generar Pedido de Materiales</a>';
        }

        print '</div>';
        print '</div>';








    //PARA LOS ALBARANES
    $arrayfields = array(
        'referencia' => array('label' => $langs->trans("Referencia"), 'checked' => 1),
        'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 1),
        'albaran' => array('label' => $langs->trans("Albaran"), 'checked' => 1),
    );

    if (isset($_POST["selectedfields"])) {
        $fieldsSelected = $_POST["selectedfields"];
        $fieldsSelectedArray = explode(",", $fieldsSelected);

        $arrayfields = array(
            'referencia' => array('label' => $langs->trans("Referencia"), 'checked' => 0),
            'fecha' => array('label' => $langs->trans("Fecha"), 'checked' => 0),
            'albaran' => array('label' => $langs->trans("Albaran"), 'checked' => 0),
        );

        foreach ($fieldsSelectedArray as $key => $value) {
            $arrayfields[$value]["checked"] = 1;
        }
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



    $id_usuario = $object->id;

    $i = 0;

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

    //$newcardbutton = '';
    //$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo parte'), '', 'fa fa-plus-circle', '#addEquipmentModal');

    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';

    print '<div class="fichethirdright">';
    
    //print_barre_liste($langs->trans("Albaranes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste">' . "\n";

    print '<tr class="liste_titre">';

    if (!empty($arrayfields['referencia']['checked'])) {
        print "<th class='center liste_titre' title='Referencia'>";
        print "<a class='reposition' href=''>Referencia</a>";
        print "</th>";
    }

    if (!empty($arrayfields['fecha']['checked'])) {
        print "<th class='center liste_titre' title='Fecha'>";
        print "<a class='reposition' href=''>Fecha</a>";
        print "</th>";
    }

    if (!empty($arrayfields['albaran']['checked'])) {
        print "<th class='center liste_titre' title='Albaran'>";
        print "<a class='reposition' href=''>Albaran</a>";
        print "</th>";
    }

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
    print "</tr>";

    /*$sqlOfertas = " SELECT rowid, ref, nombre ";
    $sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
    $sqlOfertas.= " WHERE averia = ".$id;

    $resultOferta = $db->query($sqlOfertas);
    $numOfertas = $db->num_rows($resultOferta);
    $ofertalinea = $db->fetch_object($resultOferta);

    if ($numOfertas > 0) {*/

    $sqlAlbaranes = " SELECT * ";
    $sqlAlbaranes.= " FROM ".MAIN_DB_PREFIX."expedition ";
    $sqlAlbaranes.= " WHERE fk_projet = ".$id." ";
    
    $resultAlbaranes = $db->query($sqlAlbaranes);

    //}

    $numero = 1;
    while ($alba = $db->fetch_object($resultAlbaranes)) {

        print '<tr class="oddeven">';

        if (!empty($arrayfields['referencia']['checked']))
            print "<td class='center' tdoverflowmax200'>(".$numero."º) <a href='../expedition/card.php?id=".$alba->rowid."' target='_blank'>".$alba->ref."</a></td>";

        $fecha_formateada = date("d-m-Y", strtotime($alba->date_creation));

        list($dia2, $mes2, $ano2) = explode("-", $fecha_formateada);
        
        if (!empty($arrayfields['fecha']['checked']))
            print "<td class='center' tdoverflowmax200'>".$fecha_formateada."</td> "; 

        if (!empty($arrayfields['albaran']['checked']))
            print "<td class='center' tdoverflowmax200'><a href='../document.php?modulepart=expedition&amp;attachment=0&amp;file=".$alba->ref."%2F".$alba->ref.".pdf&amp;entity=1' mime='application/pdf' target='_blank'>ALBARAN <span class='fa fa-search-plus' style='color: gray'></span></a></td> "; 


        print '<td class="center">';
        print '
            <table class="center">
                <tr>
                    <td>
                        
                    </td>
                </tr>
            </table>
            ';
        print '</td>';
        print "</tr>";

        $numero++;
        
    }
    print "</table>";

    print '</div>';

    print '<div class="tabsAction">';

    if ($numEnvios > 0) {
	    print '<a class="butAction" type="button" href="../expedition/shipment.php?id='.$envio->rowid.'" target="_blank">Nuevo Envío/Albarán</a>';
    }
    
    print '</div>';
    print '</div>';






    $arrayfields = array(
		'certificacion' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'total' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'fecha' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
        'mes' => array('label' => $langs->trans("Mes"), 'checked' => 1),
        'ano' => array('label' => $langs->trans("Año"), 'checked' => 1),
        'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'certificacion' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'total' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'fecha' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
            'mes' => array('label' => $langs->trans("Mes"), 'checked' => 0),
            'ano' => array('label' => $langs->trans("Año"), 'checked' => 0),
            'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	//$newcardbutton = '';
	//$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo parte'), '', 'fa fa-plus-circle', '#addEquipmentModal');

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Propuestas de Certificación"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste" id="propuestas">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['certificacion']['checked'])) {
		print "<th class='center liste_titre' title='Certificacion'>";
		print "<a class='reposition' href=''>Certificacion</a>";
		print "</th>";
	}

	if (!empty($arrayfields['total']['checked'])) {
		print "<th class='center liste_titre' title='Total'>";
		print "<a class='reposition' href=''>Total</a>";
		print "</th>";
	}

	if (!empty($arrayfields['fecha']['checked'])) {
		print "<th class='center liste_titre' title='Fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

    if (!empty($arrayfields['mes']['checked'])) {
		print "<th class='center liste_titre' title='Mes'>";
		print "<a class='reposition' href=''>Mes</a>";
		print "</th>";
	}

    if (!empty($arrayfields['ano']['checked'])) {
		print "<th class='center liste_titre' title='Año'>";
		print "<a class='reposition' href=''>Año</a>";
		print "</th>";
	}

    if (!empty($arrayfields['porcentaje']['checked'])) {
		print "<th class='center liste_titre' title='Porcentaje'>";
		print "<a class='reposition' href=''>Porcentaje Total</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	/*$sqlOfertas = " SELECT rowid, ref, nombre ";
	$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlOfertas.= " WHERE averia = ".$id;

	$resultOferta = $db->query($sqlOfertas);
	$numOfertas = $db->num_rows($resultOferta);
	$ofertalinea = $db->fetch_object($resultOferta);

	if ($numOfertas > 0) {*/

    $sqlCertificados = " SELECT rowid, ref, fecha, imp_mes_total, porcent_total ";
    $sqlCertificados.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlCertificados.= " WHERE fk_proyect = ".$id." AND aprobado = 0 ";
    
    $resultCertificados = $db->query($sqlCertificados);

	//}

    while ($cert = $db->fetch_object($resultCertificados)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['certificacion']['checked']))
			print "<td class='center' tdoverflowmax200'>".$cert->ref."</td>";

		if (!empty($arrayfields['total']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($cert->imp_mes_total,2),['.' => ',', ',' => '.'])." €</td> ";

        $fecha_formateada = date("d-m-Y", strtotime($cert->fecha));

        list($dia2, $mes2, $ano2) = explode("-", $fecha_formateada);
        
		if (!empty($arrayfields['fecha']['checked']))
			print "<td class='center' tdoverflowmax200'>".$fecha_formateada."</td> "; 

        if (!empty($arrayfields['mes']['checked']))
            print "<td class='center' tdoverflowmax200'>".MONTHS[$mes2]."</td> "; 

        if (!empty($arrayfields['ano']['checked']))
            print "<td class='center' tdoverflowmax200'>".$ano2."</td> ";
        
        $porcentajeComa = strtr(number_format($cert->porcent_total,2),['.' => ',', ',' => '.']);
        //$porcentajeComa = str_replace(".", ",", $cert->porcent_total);
        //$porcentajeComa = number_format($porcentajeComa, 2);

        if (!empty($arrayfields['porcentaje']['checked']))
            print "<td class='center' tdoverflowmax200'>".$porcentajeComa." %</td> "; 

		print '<td class="center">';
		print '
			<table class="center">
				<tr>';
                    //<td>
                        //<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=editarCert&id=' . $object->id . '&cert=' . $cert->rowid . '" title="Aprobado">'.img_edit().'</a>	
                    //</td>
					print '<td>
						<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrarCert&id=' . $object->id . '&cert=' . $cert->rowid . '">' . img_delete() . '</a>		
					</td>
                    <td>
                        <a class="editfielda" href="printCertificacion.php?id='.$cert->rowid.'">' . img_printer() . '</a>		
                    </td>
                    <td>
                        <a class="fas fa-check" href="' . $_SERVER["PHP_SELF"] . '?action=aprobarCert&id=' . $object->id . '&cert=' . $cert->rowid . '" title="Aprobado"></a>	
                    </td>
				</tr>
			</table>
			';
		print '</td>';
		print "</tr>";
		
	}
	print "</table>";

	print '</div>';

	print '<div class="tabsAction">';
	print '<a class="butAction" type="button" href="'.$_SERVER["PHP_SELF"].'?action=addCertificacion&id='.$id.'">Nueva Certificación</a>';
	print '</div>';





        








    //PARA LOS APROBADOS
    $arrayfields = array(
		'certificacion' => array('label' => $langs->trans("Código"), 'checked' => 1),
		'total' => array('label' => $langs->trans("Descripción"), 'checked' => 1),
		'fecha' => array('label' => $langs->trans("Cantidad"), 'checked' => 1),
        'mes' => array('label' => $langs->trans("Mes"), 'checked' => 1),
        'ano' => array('label' => $langs->trans("Año"), 'checked' => 1),
        'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 1),
	);

	if (isset($_POST["selectedfields"])) {
		$fieldsSelected = $_POST["selectedfields"];
		$fieldsSelectedArray = explode(",", $fieldsSelected);

		$arrayfields = array(
            'certificacion' => array('label' => $langs->trans("Código"), 'checked' => 0),
            'total' => array('label' => $langs->trans("Descripción"), 'checked' => 0),
            'fecha' => array('label' => $langs->trans("Cantidad"), 'checked' => 0),
            'mes' => array('label' => $langs->trans("Mes"), 'checked' => 0),
            'ano' => array('label' => $langs->trans("Año"), 'checked' => 0),
            'porcentaje' => array('label' => $langs->trans("Porcentaje"), 'checked' => 0),
		);

		foreach ($fieldsSelectedArray as $key => $value) {
			$arrayfields[$value]["checked"] = 1;
		}
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields



	$id_usuario = $object->id;

	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . $contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . $limit;

	//$newcardbutton = '';
	//$newcardbutton .= dolGetButtonTitle($langs->trans('Nuevo parte'), '', 'fa fa-plus-circle', '#addEquipmentModal');

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print_barre_liste($langs->trans("Certificaciones"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">' . "\n";

	print '<tr class="liste_titre">';

	if (!empty($arrayfields['certificacion']['checked'])) {
		print "<th class='center liste_titre' title='Certificacion'>";
		print "<a class='reposition' href=''>Certificacion</a>";
		print "</th>";
	}

	if (!empty($arrayfields['total']['checked'])) {
		print "<th class='center liste_titre' title='Total'>";
		print "<a class='reposition' href=''>Total</a>";
		print "</th>";
	}

	if (!empty($arrayfields['fecha']['checked'])) {
		print "<th class='center liste_titre' title='Fecha'>";
		print "<a class='reposition' href=''>Fecha</a>";
		print "</th>";
	}

    if (!empty($arrayfields['mes']['checked'])) {
		print "<th class='center liste_titre' title='Mes'>";
		print "<a class='reposition' href=''>Mes</a>";
		print "</th>";
	}

    if (!empty($arrayfields['ano']['checked'])) {
		print "<th class='center liste_titre' title='Año'>";
		print "<a class='reposition' href=''>Año</a>";
		print "</th>";
	}

    if (!empty($arrayfields['porcentaje']['checked'])) {
		print "<th class='center liste_titre' title='Porcentaje'>";
		print "<a class='reposition' href=''>Porcentaje Total</a>";
		print "</th>";
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', "", "", 'maxwidthsearch');
	print "</tr>";

	/*$sqlOfertas = " SELECT rowid, ref, nombre ";
	$sqlOfertas.= " FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias_ofertas ";
	$sqlOfertas.= " WHERE averia = ".$id;

	$resultOferta = $db->query($sqlOfertas);
	$numOfertas = $db->num_rows($resultOferta);
	$ofertalinea = $db->fetch_object($resultOferta);

	if ($numOfertas > 0) {*/

    $sqlCertificados = " SELECT rowid, ref, fecha, imp_mes_total, porcent_total ";
    $sqlCertificados.= " FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlCertificados.= " WHERE fk_proyect = ".$id." AND aprobado = 1 ";
    
    $resultCertificados = $db->query($sqlCertificados);

	//}

    $numero = 1;
    while ($cert = $db->fetch_object($resultCertificados)) {

		print '<tr class="oddeven">';

		if (!empty($arrayfields['certificacion']['checked']))
			print "<td class='center' tdoverflowmax200'>(".$numero."ª) ".$cert->ref."</td>";

		if (!empty($arrayfields['total']['checked']))
			print "<td class='center' tdoverflowmax200'>".strtr(number_format($cert->imp_mes_total,2),['.' => ',', ',' => '.'])." €</td> ";

        $fecha_formateada = date("d-m-Y", strtotime($cert->fecha));

        list($dia2, $mes2, $ano2) = explode("-", $fecha_formateada);
        
		if (!empty($arrayfields['fecha']['checked']))
			print "<td class='center' tdoverflowmax200'>".$fecha_formateada."</td> "; 

        if (!empty($arrayfields['mes']['checked']))
            print "<td class='center' tdoverflowmax200'>".MONTHS[$mes2]."</td> "; 

        if (!empty($arrayfields['ano']['checked']))
            print "<td class='center' tdoverflowmax200'>".$ano2."</td> ";
        
        $porcentajeComa = strtr(number_format($cert->porcent_total,2),['.' => ',', ',' => '.']);
        //$porcentajeComa = str_replace(".", ",", $cert->porcent_total);
        //$porcentajeComa = number_format($porcentajeComa, 2);

        if (!empty($arrayfields['porcentaje']['checked']))
            print "<td class='center' tdoverflowmax200'>".$porcentajeComa." %</td> "; 

		print '<td class="center">';
		print '
			<table class="center">
				<tr>
					<td>
						<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=borrarCert&id=' . $object->id . '&cert=' . $cert->rowid . '">' . img_delete() . '</a>		
					</td>
                    <td>
                        <a class="editfielda" href="printCertificacion.php?id='.$cert->rowid.'">' . img_printer() . '</a>		
                    </td>';
                    //<td>
                        //<a class="editfielda" href="../custom/ventas/factura_origen_card.php?action=create&cert='.$cert->rowid.'">' . img_edit() . '</a>		
                    //</td>
				print '</tr>
			</table>
			';
		print '</td>';
		print "</tr>";

		$numero++;
		
	}
	print "</table>";

	print '</div>';
	
}

//Para añadir los datos
if ($_GET["action"] == "addCertificacion") {

	$id = $_GET['id'];

    //Consulta para recoger los datos actuales
    $consulta = " SELECT pom.rowid, pom.fk_product, p.ref, p.label, pom.quantity FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
    $consulta.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product ";
    $consulta.= " WHERE pom.fk_project = ".$id;

    $resultConsulta = $db->query($consulta);

    $numProductos = $db->num_rows($resultConsulta);

    //Modal
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id='.$object->id.'&action=mostrarCertificacion" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable center-modal" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 35%; left: 35%; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar usuario</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 220.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
                        <tbody>
                            <tr>
                                <td>
                                    <span>Selecciona material y cantidad a certificar</span>
                                </td>
                            </tr>';

                            while ($producto = $db->fetch_object($resultConsulta)) {

                                print '<tr><td>
                                <input type="checkbox" name="producto['.$producto->rowid.']" value="'.$producto->rowid.'">';
                                print '
                                    <label for="producto">'.$producto->ref.'</label>
                                </td>';
                                print '
                                <td>
                                <label for="cantidad">Cantidad:</label>
                                </td>
                                <td>
                                <input type="number" style="width:50px" name="cantidad['.$producto->rowid.']">
                                </td>
                                </tr>';

                            }


                            print '
                        </tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="mostrarCertificacion">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';
}

if (isset($_POST['mostrarCertificacion'])) {

    $producto = $_POST['producto'];
    $cantidad = $_POST['cantidad'];

    //SACAMOS EL IVA Y LOS DESCUENTOS
    $sqlDescontar = " SELECT pe.discount_offer, s.remise_client, se.porc_iva FROM ".MAIN_DB_PREFIX."projet p ";
    $sqlDescontar.= " INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = p.rowid ";
    $sqlDescontar.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = p.fk_soc ";
    $sqlDescontar.= " INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = s.rowid ";
    $sqlDescontar.= " WHERE p.rowid = ".$id." ";

    $resultDescontar = $db->query($sqlDescontar);
    $descontar = $db->fetch_object($resultDescontar);

    if ($descontar->porc_iva == "") {
        $porc_iva = 21;
    } else {
        $porc_iva = $descontar->porc_iva;
    }

    if (($descontar->discount_offer == "") || ($descontar->discount_offer == 0)) {
        $dto_oferta = 0;
    } else {
        $dto_oferta = $descontar->discount_offer;
    }

    if (($descontar->remise_client == "") || ($descontar->remise_client == 0)) {
        $dto_cliente = 0;
    } else {
        $dto_cliente = $descontar->remise_client;
    }

    $descuentos = $dto_oferta + $dto_cliente;

    $listaPom = array();
    //SACAMOS LOS IDS DE LOS MATERIALES DEL POM
    $sqlPom = " SELECT rowid FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales ";
    $sqlPom.= " WHERE fk_project = ".$id." ";

    $resultPom = $db->query($sqlPom);

    while ($pom = $db->fetch_object($resultPom)) {
        $listaPom[] = $pom->rowid;
    }

    //Consulta para recoger los datos actuales
    $consulta = " SELECT pom.rowid, pom.fk_product, p.ref, p.label, p.description, pom.quantity, pom.taxable_base, pom.discount, pom.price FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
    $consulta.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product ";
    $consulta.= " WHERE pom.fk_project = ".$id;

    $resultConsulta = $db->query($consulta);

    //Comprobamos si ya hay alguna certificación para este proyecto
    $consultaCert = " SELECT * FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $consultaCert.= " WHERE fk_proyect = ".$id." ORDER BY rowid DESC ";

    $resultConsultaCert = $db->query($consultaCert);
    $numCert = $db->num_rows($resultConsultaCert);
    $certificado = $db->fetch_object($resultConsultaCert);

    //Si ya hay certificaciones, sacamos su ID
    if ($numCert > 0) {
        
        $certificadoID = $certificado->rowid;
        
    } else  {
        $cant_origen = 0;
        $cant_anterior = 0;
        $cantOrigen = 0;
    }

    $linea = 1;

    //Para cada una de las líneas de productos
    $k = 0;
    while ($pro = $db->fetch_object($resultConsulta)) {

        //Si hay certificado anterior, sacamos sus datos
        if ($certificadoID != "") {

            $sqlLineaAnt = " SELECT * FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
            $sqlLineaAnt.= " WHERE fk_certificacion = ".$certificadoID." AND fk_producto = ".$pro->fk_product." AND id_pom = ".$pro->rowid." ";

            $resultLineaAnt = $db->query($sqlLineaAnt);
            $lineaAnt = $db->fetch_object($resultLineaAnt);
            
            $cantMesAnt = $lineaAnt->cant_mes + $lineaAnt->cant_anterior;

            if ($cantMesAnt == "") {
                $cantMesAnt = 0;
            }

            $impOrigen = $lineaAnt->imp_origen;

            if ($impOrigen == "") {
                $impOrigen = 0;
            }

            $cantOrigen = $lineaAnt->cant_origen;

            if ($cantOrigen == "") {
                $cantOrigen = 0;
            }

            $impAnterior = $lineaAnt->imp_anterior;

        }

        //EL PEDIDO TOTAL
        $aRestar = ($pro->taxable_base * $descuentos) / 100;
        $pedido = $pro->taxable_base - $aRestar;

        if ($cantidad[$pro->rowid] == "") {
            $cantidad[$pro->rowid] = 0;
        }

        //Para la cantidad origen
        if ($pro->rowid == $producto[$pro->rowid]) {
            $cant_origen = $cantOrigen + $cantidad[$pro->rowid];
        } else {
            $cant_origen = $cantOrigen;
        }

        //Para la cantidad anterior

        //Para la cantidad mes
        if ($pro->rowid == $producto[$pro->rowid]) {
            $precioTotal = $cantidad[$pro->rowid] * $pro->price;
            $aDescontar = ($precioTotal * $pro->discount) / 100;
            $precioTotal = $precioTotal - $aDescontar;

            $aDescontar2 = ($precioTotal * $descuentos) / 100;
            $precioTotal = $precioTotal - $aDescontar2;
            /*$aRestar = ($pro->taxable_base * $descuentos) / 100;
            $precioTotal = $pro->taxable_base - $aRestar;*/
            $importeMes = $precioTotal;
        } else {
            $importeMes = 0;
        }

        //Para el importe origen
        if ($importeMes > 0) {
            $imp_origen = $impOrigen + $importeMes;
            $totalOrigen+= $imp_origen;
        } else {
            $imp_origen = $impOrigen;
            $totalOrigen+= $imp_origen;
        }

        //Para el importe anterior
        $anteriorTotal = $cantMesAnt * $pro->price;
        /*$aDescontar = ($anteriorTotal * $pro->discount) / 100;
        $impAnterior = $anteriorTotal - $aDescontar;*/
        $aDescontar = ($anteriorTotal * $pro->discount) / 100;
        $impAnterior = $anteriorTotal - $aDescontar;

        $aDescontar2 = ($anteriorTotal * $descuentos) / 100;
        $impAnterior = $anteriorTotal - $aDescontar2;
        $totalAnterior+= $impAnterior;

        //Para calcular el porcentaje

        if ($pro->rowid == $producto[$pro->rowid]) {
            $porcentaje = ($imp_origen / $pedido) * 100;
            $porcentaje = number_format($porcentaje,2);
        } else {
            $porcentaje = ($imp_origen / $pedido) * 100;
            $porcentaje = number_format($porcentaje,2);
        }

        $linea++;
        $totalCantidad+= $pro->quantity;
        $totalPedido+= $pedido;
        $totalMes+= $importeMes;

        //Una vez tenemos todo, tenemos que hacer la inserción de la línea
        //En el caso de que no haya Certificaciones anteriores
        if ($numCert == 0) {

            if ($cant_origen == 0) {
                $cant_origen = "NULL";
            }

            if ($pro->rowid == $producto[$pro->rowid]) {
                //print 'uno';
                $sqlInsertLinea = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
                $sqlInsertLinea.= " (fk_producto, cant_contrato, cant_origen, cant_mes, imp_origen, imp_mes, porcentaje, id_pom) ";
                $sqlInsertLinea.= " VALUES ";
                $sqlInsertLinea.= " ($pro->fk_product, $pro->quantity, $cant_origen, ".$cantidad[$pro->rowid].", $imp_origen, $importeMes, $porcentaje, ".$listaPom[$k].") ";

                /*print "<br>";
                print $sqlInsertLinea;
                print "<br>";*/

                $db->query($sqlInsertLinea);
            } else {
                //print 'dos';

                if ($imp_origen == "") {
                    $imp_origen = "NULL";
                }

                $sqlInsertLinea = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
                $sqlInsertLinea.= " (fk_producto, cant_contrato, cant_origen, imp_origen, porcentaje, id_pom) ";
                $sqlInsertLinea.= " VALUES ";
                $sqlInsertLinea.= " ($pro->fk_product, $pro->quantity, $cant_origen, $imp_origen, $porcentaje, ".$listaPom[$k].") ";

                /*print "<br>";
                print $sqlInsertLinea;
                print "<br>";*/

                $db->query($sqlInsertLinea);
            }

        } else {

            /*print "<br>";
            print 'ya hay cert';
            print "<br>";*/

            if ($cantidad[$pro->rowid] == "") {
                $cantidad[$pro->rowid] = "NULL";
            }

            if ($cant_origen == "") {
                $cant_origen = "NULL";
            }

            if ($cantMesAnt == "") {
                $cantMesAnt = "NULL";
            }

            if ($pro->rowid != $producto[$pro->rowid]) {
                $cantidad[$pro->rowid] = "NULL";
            }

            $sqlInsertLinea = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
            $sqlInsertLinea.= " (fk_producto, cant_contrato, cant_origen, cant_anterior, cant_mes, imp_origen, imp_anterior, imp_mes, porcentaje, id_pom) ";
            $sqlInsertLinea.= " VALUES ";
            $sqlInsertLinea.= " ($pro->fk_product, $pro->quantity, $cant_origen, $cantMesAnt, ".$cantidad[$pro->rowid].", $imp_origen, $impAnterior, $importeMes, $porcentaje, ".$listaPom[$k].") ";

            /*print "<br>";
            print $sqlInsertLinea;
            print "<br>";*/

            $db->query($sqlInsertLinea);

        }

        $k++;
    }

    //Para calcular el porcentaje total
    /*$porcentajeTotal = ($totalAFacturar / $totalCantidad) * 100;
    $porcentajeTotal = number_format($porcentajeTotal,2);*/
    $porcentajeTotal = ($totalOrigen / $totalPedido) * 100;
    $porcentajeTotal = number_format($porcentajeTotal,2);

    $fecha_actual2 = date("Y-m-d"); // Obtener la fecha actual en formato "Año-Mes-Día"

    if ($certificadoID == "") {
        $certificadoID = 0;
    }

    $certificadoID++;

    $ano = strval($ano); // Convertir el número a una cadena
    $resultadoAno = substr($ano, -2); // Recortar los últimos dos caracteres

    $mes = strval($mes); // Convertir el número a una cadena

    //Una vez hemos mostrado la certificación, hacemos las inserciones en BBDD
    $sqlInsercion1 = " INSERT INTO ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlInsercion1.= " (ref, fk_proyect, fecha, pedido, imp_origen_total, imp_anterior_total, imp_mes_total, porcent_total) ";
    $sqlInsercion1.= " VALUES ";
    $sqlInsercion1.= " ('PCE".$resultadoAno."".$mes."-".str_pad($certificadoID, 5, 0, STR_PAD_LEFT)."', $id, '$fecha_actual2', $totalPedido, $totalOrigen, $totalAnterior, $totalMes, $porcentajeTotal) ";

    //print $sqlInsercion1;

    $db->query($sqlInsercion1);

    //Actualizamos este id en las lineas introducidas anteriormente
    $sqlId = " SELECT rowid FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlId.= " ORDER BY rowid DESC ";

    $resultId = $db->query($sqlId);
    $idUltimoCert = $db->fetch_object($resultId);
    $idUltimoCert = $idUltimoCert->rowid;

    //HACEMOS UPDATE
    $sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
    $sqlUpdate.= " SET fk_certificacion = ".$idUltimoCert." ";
    $sqlUpdate.= " WHERE fk_certificacion IS NULL ";

    $db->query($sqlUpdate);

    //print $idUltimoCert;

    print '<meta http-equiv="refresh" content="0; url=' . $_SERVER['PHP_SELF'] . '?id='.$object->id. '">';

}

if ($action == "borrarCert") {

    $cert = $_GET['cert'];
    
    $sqlDeleteLineas = " DELETE FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
    $sqlDeleteLineas.= " WHERE fk_certificacion = ".$cert;

    $db->query($sqlDeleteLineas);

    $sqlDeleteCert = " DELETE FROM ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlDeleteCert.= " WHERE rowid = ".$cert;

    $db->query($sqlDeleteCert);

    print '<meta http-equiv="refresh" content="0; url=' . $_SERVER['PHP_SELF'] . '?id='.$object->id. '">';

    
}

if ($action == "aprobarCert") {

    $cert = $_GET['cert'];
    
    $sqlAprobar = " UPDATE ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlAprobar.= " SET aprobado = 1 ";
    $sqlAprobar.= " WHERE rowid = ".$cert;

    $db->query($sqlAprobar);

    print '<meta http-equiv="refresh" content="0; url=' . $_SERVER['PHP_SELF'] . '?id='.$object->id. '#propuestas">';

    
}

if ($_GET["action"] == "editarCert") {

	$idCert = $_GET['cert'];

    //Consulta para recoger los datos actuales
    $consulta = " SELECT cl.*, p.ref FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
    $consulta.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cl.fk_producto ";
    $consulta.= " WHERE cl.fk_certificacion = ".$idCert;

    $resultConsulta = $db->query($consulta);

    $numProductos = $db->num_rows($resultConsulta);

    //Modal
	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id='.$object->id.'&action=mostrarCertificacion&idCert='.$idCert.'" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable center-modal" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 35%; left: 35%; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar usuario</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 220.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
                        <tbody>
                            <tr>
                                <td>
                                    <span>Selecciona material y cantidad a certificar</span>
                                </td>
                            </tr>';

                            while ($producto = $db->fetch_object($resultConsulta)) {

                                if ($producto->cant_mes == "") {
                                    print '<tr><td>
                                    <input type="checkbox" name="producto['.$producto->fk_producto.']" value="'.$producto->fk_producto.'">';
                                } else {
                                    print '<tr><td>
                                    <input type="checkbox" name="producto['.$producto->fk_producto.']" value="'.$producto->fk_producto.'" checked>';
                                }
                                print '
                                    <label for="producto">'.$producto->ref.'</label>
                                </td>';
                                print '
                                <td>
                                <label for="cantidad">Cantidad:</label>
                                </td>';

                                if ($producto->cant_mes == "") {
                                    print '<td>
                                    <input type="number" style="width:50px" name="cantidad['.$producto->fk_producto.']">
                                    </td>
                                    </tr>';
                                } else {
                                    print '<td>
                                    <input type="number" style="width:50px" name="cantidad['.$producto->fk_producto.']" value="'.$producto->cant_mes.'">
                                    </td>
                                    </tr>';
                                }

                            }


                            print '
                        </tbody>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editarCertificacion">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';
}

if (isset($_POST['editarCertificacion'])) {

    $producto = $_POST['producto'];
    $cantidad = $_POST['cantidad'];
    $idCert = $_GET['idCert'];

    //Consulta para recoger los datos actuales
    $consulta = " SELECT pom.rowid, pom.fk_product, p.ref, p.label, p.description, pom.quantity, pom.taxable_base, pom.price FROM ".MAIN_DB_PREFIX."proyectos_oferta_materiales pom ";
    $consulta.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pom.fk_product ";
    $consulta.= " WHERE pom.fk_project = ".$id;

    $resultConsulta = $db->query($consulta);

    //Para cada una de las líneas de productos
    while ($pro = $db->fetch_object($resultConsulta)) {

        $pedidoLinea = $pro->taxable_base;

        $consultaLinea = " SELECT * FROM ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas cl ";
        $consultaLinea.= " INNER JOIN ".MAIN_DB_PREFIX."proyectos_certificaciones c ON c.rowid = cl.fk_certificacion ";
        $consultaLinea.= " WHERE cl.fk_certificacion = ".$idCert." AND cl.fk_producto = ".$pro->fk_product;

        /*print $consultaLinea;
        print "<br>";*/

        $resultConsultaLinea = $db->query($consultaLinea);
        $linea = $db->fetch_object($resultConsultaLinea);

        if ($linea->cant_anterior == "") {
            $linea->cant_anterior = 0;
        }

        if ($linea->imp_anterior == "") {
            $linea->imp_anterior = 0;
        }

        if ($pro->fk_product == $producto[$pro->fk_product]) {
            $cantidadMes = $cantidad[$pro->fk_product];
            $cantidadOrigen = $linea->cant_anterior + $cantidadMes;
            
        } else {
            $cantidadMes = 0;
            $cantidadOrigen = $linea->cant_anterior;
        }

        if ($pro->fk_product == $producto[$pro->fk_product]) {
            $impMes = $cantidadMes * $pro->price;
            $impOrigen = $linea->imp_anterior + $impMes;
        } else {
            $impMes = 0;
            $impOrigen = $linea->imp_anterior;
        }

        if ($pro->fk_product == $producto[$pro->fk_product]) {
            $porcentaje = ($impOrigen / $pedidoLinea) * 100;
            $porcentaje = number_format($porcentaje,2);
        } else {
            $porcentaje = ($impOrigen / $pedidoLinea) * 100;
            $porcentaje = number_format($porcentaje,2);
        }

        $totalOrigen+= $impOrigen;
        $totalAnterior+= $linea->imp_anterior;
        $totalMes+= $impMes;
        $totalPedido+= $pedidoLinea;

        if ($linea->cant_anterior == 0) {
            $linea->cant_anterior = "NULL";
        }

        if ($linea->imp_anterior == 0) {
            $linea->imp_anterior = "NULL";
        }

        if ($cantidadMes == 0) {
            $cantidadMes = "NULL";
        }

        if ($impMes == 0) {
            $impMes = "NULL";
        }

        /*print "CANT ORIGEN :".$cantidadOrigen;
        print "<br>";
        print "CANT MES :".$cantidadMes;
        print "<br>";
        print "CANT ANTERIOR :".$linea->cant_anterior;
        print "<br>";
        print "IMP ORIGEN :".$impOrigen;
        print "<br>";
        print "IMP MES :".$impMes;
        print "<br>";
        print "IMP ANTERIOR :".$linea->imp_anterior;
        print "<br>";
        print "PORCENTAJE :".$porcentaje;
        print "<br>";*/

        $sqlUpdateLinea = " UPDATE ".MAIN_DB_PREFIX."proyectos_certificaciones_lineas ";
        $sqlUpdateLinea.= " SET cant_contrato = $pro->quantity, ";
        $sqlUpdateLinea.= " cant_origen = $cantidadOrigen, ";
        $sqlUpdateLinea.= " cant_anterior = $linea->cant_anterior, ";
        $sqlUpdateLinea.= " cant_mes = $cantidadMes, ";
        $sqlUpdateLinea.= " imp_origen = $impOrigen, ";
        $sqlUpdateLinea.= " imp_anterior = $linea->imp_anterior, ";
        $sqlUpdateLinea.= " imp_mes = $impMes, ";
        $sqlUpdateLinea.= " porcentaje = $porcentaje ";
        $sqlUpdateLinea.= " WHERE fk_producto = $pro->fk_product AND fk_certificacion = ".$idCert;

        $db->query($sqlUpdateLinea);

       print "<br>";

    }

    //die;

    /*print "TOTAL ORIGEN: ".$totalOrigen;
    print "<br>";
    print "TOTAL ANTERIOR: ".$totalAnterior;
    print "<br>";
    print "TOTAL MES: ".$totalMes;
    print "<br>";*/

    //Para calcular el porcentaje total
    /*$porcentajeTotal = ($totalAFacturar / $totalCantidad) * 100;
    $porcentajeTotal = number_format($porcentajeTotal,2);*/
    $porcentajeTotal = ($totalOrigen / $totalPedido) * 100;
    $porcentajeTotal = number_format($porcentajeTotal,2);

    $fecha_actual2 = date("Y-m-d"); // Obtener la fecha actual en formato "Año-Mes-Día"

    if ($certificadoID == "") {
        $certificadoID = 0;
    }

    $certificadoID++;

    $ano = strval($ano); // Convertir el número a una cadena
    $resultadoAno = substr($ano, -2); // Recortar los últimos dos caracteres

    //Una vez hemos mostrado la certificación, hacemos las inserciones en BBDD
    $sqlUpdate1 = " UPDATE ".MAIN_DB_PREFIX."proyectos_certificaciones ";
    $sqlUpdate1.= " SET imp_origen_total = $totalOrigen, ";
    $sqlUpdate1.= " imp_anterior_total = $totalAnterior, ";
    $sqlUpdate1.= " imp_mes_total = $totalMes, ";
    $sqlUpdate1.= " porcent_total = $porcentajeTotal ";
    $sqlUpdate1.= " WHERE rowid = ".$idCert;

    /*print $sqlUpdate1;
    print "<br>";*/

    $db->query($sqlUpdate1);

    print '<meta http-equiv="refresh" content="0; url=' . $_SERVER['PHP_SELF'] . '?id='.$object->id. '">';

}


// End of page
llxFooter();
$db->close();
ob_flush();
