<?php
ob_start();
header("Content-Type: text/html; charset=UTF-8");
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2020       Open-Dsi     			<support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/contact/card.php
 *       \ingroup    societe
 *       \brief      Card of a contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'other', 'commercial'));

$mesg = ''; $error = 0; $errors = array();

$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');

/*
 * View
 */

llxHeader('', $langs->trans(utf8_encode("Listado")));
print load_fiche_titre($langs->trans(utf8_encode("Listado Licitadores")), '', 'companies');


print "
	<div class='div-table-responsive'>
	<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
	<tbody>
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='text' name='search_representante'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='text' name='search_nombre'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth80imp' type='text' name='search_clasificacion'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='text' name='search_abjudicatario'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='date' name='search_fecha_inicio'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='date' name='search_fecha_fin'>
			</td>
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
		<tr class='liste_titre'>
			<th class='wrapcolumntitle liste_titre' title='representante'>
				<a class='reposition' href=''>Representante</a>
			</th>
			<th class='wrapcolumntitle liste_titre' title='nombre'>
				<a class='reposition' href=''>Nombre Licitador</a>
			</th>
			<th class='wrapcolumntitle liste_titre' title='clasificacion'>
				<a class='reposition' href=''>" .utf8_encode("Clasificación") . "</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='abjudicatorio'>
				<a class='reposition' href=''>Abjudicatario</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='fecha_inicio'>
				<a class='reposition' href=''>Fecha inicio</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='fecha_fin'>
				<a class='reposition' href=''>Fecha fin</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='accion'>
			<dl class='dropdown'>
				<dt>
				<a href='#selectedfields'>
				  <span class='fas fa-list' style='></span>
				</a>
				<input type='hidden' class='selectedfields' name=''>
				</dt>
			</dl>
			</th></tr>
		</tr>
		";

		$sql = "SELECT * FROM khns_licitador WHERE 1 ";
		if (isset($_POST['button_search'])) {
			$licitadores = array();

			if (isset($_POST['search_representante']) && ($_POST['search_representante']) != "") {
				$representante = "'" . $_POST['search_representante'] . "'";
				$sql .=' and representante='.$representante;
			}

			if (isset($_POST['search_nombre']) && ($_POST['search_nombre']) != "") {
				$nombre = "'" . $_POST['search_nombre'] . "'";
				$sql .=' and nombre='.$nombre;
			}
			  
			if (isset($_POST['search_clasificacion']) && ($_POST['search_clasificacion']) != "") {
				$clasificacion = "'" .$_POST['search_clasificacion'] . "'";
				$sql .=' and clasificacion='.$clasificacion;
			}

			if (isset($_POST['search_abjudicatario']) && ($_POST['search_abjudicatario']) != "") {
				$abjudicatario= "'" . $_POST['search_abjudicatario'] . "'";
				$sql .=' and abjudicatario='.$abjudicatario;
			}

			if (isset($_POST['search_fecha_inicio']) && ($_POST['search_fecha_inicio']) != "") {
				$fecha_inicio= "'" . $_POST['search_fecha_inicio'] . "'";
				$sql .=' and fecha_inicio='.$fecha_inicio;
			}

			if (isset($_POST['search_fecha_fin']) && ($_POST['search_fecha_fin']) != "") {
				$fecha_fin= "'" . $_POST['search_fecha_fin'] . "'";
				$sql .=' and fecha_fin='.$fecha_fin;
			}
		}
		print '<form method="POST" action="" name="formfilter" autocomplete="off">';
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		while($aux<$num){
			$obj = $db->fetch_object($respuesta);
			print "<tr class='oddeven'>";
			print "<input type='hidden' name='id' value='" . $obj->id . "'>";
			print "<input type='hidden' name='nombre' value='" . $obj->nombre . "'>";
			print "<td class=' tdoverflowmax200'>" . $obj->representante . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->nombre . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->clasificacion . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->abjudicatario . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->fecha_inicio . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->fecha_fin . "</td> ";

			print " <td class='nowrap'>";
			print '<a class="editfielda" href="licitador.php?action=editar&id='; print $obj->id.'">
			<span class="fas fa-pencil-alt" style="color:black;" title="Modificar"></span>
			</a>';
			print "<button type='submit' class='' name='delete' style='border: none; background-color:rgb(250,250,250);'>
				<span class='fas fa-trash marginleftonly' style='color: #444;'></span>
			</button>";
			print "</tr>";
			$aux++;
		}
print '</form>';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];
	$nombre = $_POST['nombre'];
	echo '
	<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter" autocomplete="off">
	<input type="hidden" value="' . $id . '" name=id >
	<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 457.62px; z-index: 101;">
		<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
			<span id="ui-id-1" class="ui-dialog-title">Eliminar registro</span>
			<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
				<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
				<span class="ui-button-icon-space"> </span>
				Close
			</button>
		</div>
		<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 97.928px;" class="ui-dialog-content ui-widget-content">
			<div class="confirmquestions">
			</div>
			<div class="confirmmessage">
				<img src="/theme/eldy/img/info.png" alt="" style="vertical-align: middle;">
				' . utf8_encode("¿Está seguro ") . 'de querer eliminar el ' . utf8_encode("licitador") .': '; echo $nombre.'?
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<button type="submit" class="ui-button ui-corner-all ui-widget" name="confirmmassaction">
					' . utf8_encode("Sí") . '
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
</tbody></table>
</div>
";

if (isset($_POST['confirmmassaction'])) {
	$archivoActual = $_SERVER['PHP_SELF'];
	$id = $_POST['id'];
	$sql = "DELETE FROM khns_licitador where id=".$id;
	$respuesta = $db->query($sql);
	if($respuesta ){
		setEventMessages( utf8_encode("Licitador") .' eliminado', null, 'mesgs');
		header('Location: /projet/licitadorlista.php');
        die();
	}else{
		setEventMessages( 'Error al eliminar el licitador', null, 'errors');
	}
}


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div></div>';

ob_end_flush();
// End of page
llxFooter();
$db->close();