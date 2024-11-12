<?php
ob_start();
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-Fran�ois Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Fr�d�ric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Josep Llu�s Amador      <joseplluis@lliuretic.cat>
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

if (isset($_GET['delegacionE'])) {
	$delegacionE = $_GET['delegacionE'];
} else {
	$delegacionE = "";
}

/*
 * View
 */

llxHeader('', $langs->trans(utf8_encode("Listado")));

if ($action != "mostrar") {

	print load_fiche_titre($langs->trans("Listado Delegaciones"), '', 'companies');
	$formcompany = new FormCompany($db);

	$dele = " SELECT id,nombre FROM ".MAIN_DB_PREFIX."delegacion ";
	$resultTodos = $db->query($dele);

	print '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '?action=buscar">';
	print "<label>Nombre de la delegación: </label>";
	print "<select class='select_dele' name='delegacionE'>
	<option value='-1'>Todas</option>";

	while ($delega = $db->fetch_object($resultTodos)) {
		print "<option value=".$delega->id." ";

		if ($delega->id == $delegacionE) {
			print " selected";
		}

		print ">".$delega->nombre."</option>";
	}

	print "</select>";
	print '<button class="butAction" type="submit">Buscar</button>';
	print "</form>";
	print "<br>";
	print "<br>";
	print "<br>";


	print "
		<div class='div-table-responsive'>
		<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
		<tbody>
			<form method='POST' action='' name='formfilter' autocomplete='off'>
			<tr class='liste_titre_filter'>
		
			</tr>
			</form>
			<tr class='liste_titre'>
				<th class='wrapcolumntitle liste_titre' title='Razón Social'>
					<a class='reposition' href=''>Razón Social</a>
				</th>
				<th class='wrapcolumntitle liste_titre' title='Nombre'>
					<a class='reposition' href=''>Nombre</a>
				</th>
				<th class='wrapcolumntitle  liste_titre' title='Teléfono'>
					<a class='reposition' href=''>Teléfono</a>
				</th>
				<th class='wrapcolumntitle  liste_titre' title='Fax'>
					<a class='reposition' href=''>Fax</a>
				</th>
				<th class='wrapcolumntitle liste_titre' title='Dirección'>
					<a class='reposition' href=''>Dirección</a>
				</th>
					<th class='wrapcolumntitle liste_titre' title='CP'>
					<a class='reposition' href=''>CP</a>
				</th>
				</th>
					<th class='wrapcolumntitle liste_titre' title='Población'>
					<a class='reposition' href=''>Población</a>
				</th>
				</th>
					<th class='wrapcolumntitle liste_titre' title='Provincia'>
					<a class='reposition' href=''>Provincia</a>
				</th>
				</th>
					<th class='wrapcolumntitle liste_titre' title='Dirección Material'>
					<a class='reposition' href=''>Dirección Material</a>
				</th>
				</th>
					<th class='wrapcolumntitle liste_titre' title='Dirección Factura'>
					<a class='reposition' href=''>Dirección Factura</a>
				</th>
				<th class='wrapcolumntitle  liste_titre' title='accion'>
				<dl class='dropdown'>
					<dt>
					<a href='#selectedfields'>
					<span class='fas fa-list' style='></span>
					</a>
					<input type='hidden' class='selectedfields' name='selectedfields' value=''>
					</dt>
				</dl>
				</th></tr>
			</tr>
			";

			print '<form method="POST" action="" name="formfilter" autocomplete="off">';

			if (($delegacionE == "") || ($delegacionE == -1)) {

				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegacion WHERE 1 ";
				
				$respuesta = $db->query($sql);
				$num= $db->num_rows($sql);

				$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
				$max = 30;
				$totalPages = ceil($num / $max);

				$delegaciones = "SELECT * FROM " . MAIN_DB_PREFIX . "delegacion ORDER BY id DESC LIMIT " . (($currentPage - 1) * $max) . ", ".$max."";
				$resultDeles = $db->query($delegaciones);

			} else {

				$delegaciones = "SELECT * FROM " . MAIN_DB_PREFIX . "delegacion WHERE id = ".$delegacionE."";
				$resultDeles = $db->query($delegaciones);

			}
			
			while($obj = $db->fetch_object($resultDeles)){

				/*$sqlProvincia = 'SELECT nom  FROM '. MAIN_DB_PREFIX . 'c_departements WHERE rowid='.$obj->provincia;
				$resultProvincia = $db->query($sqlProvincia);
				$objProvincia = $db->fetch_object($resultProvincia);*/

				print "<tr class='oddeven'>";
				print "<input type='hidden' name='id' value='" . $obj->id . "'>";
				print "<input type='hidden' name='nombre' value='" . $obj->nombre . "'>";
				print "<td class=' tdoverflowmax200'><a href='".$_SERVER["PHP_SELF"]."?dele=".$obj->id."&action=mostrar'>" . $obj->codigo_delegacion . "</a></td> ";
				print "<td class=' tdoverflowmax200'><a href='".$_SERVER["PHP_SELF"]."?dele=".$obj->id."&action=mostrar'>" . $obj->nombre . "</a></td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->telef1 . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->telef2 . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->direccion . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->cp . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->localidad . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->provincia . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->direccion_material . "</td> ";
				print "<td class=' tdoverflowmax200'>" . $obj->direccion_factura . "</td> ";
				
				print " <td class='nowrap'>";

				if (($delegacionE == "") || ($delegacionE == -1)) {
					print '<a class="editfielda" href="delegacion.php?action=editar&id='; print $obj->id.'&list=1">';
				} else {
					print '<a class="editfielda" href="delegacion.php?action=editar&id='; print $obj->id.'&list=1&dele='.$delegacionE.'">';
				}

				print '<span class="fas fa-pencil-alt" style="color:black;" title="Modificar"></span>
				</a>';
				/*print "<button type='submit' class='' name='delete' style='border: none; background-color:rgb(250,250,250);'>
					<span class='fas fa-trash marginleftonly' style='color: #444;'></span>
				</button>";*/
				print '<a class="editfielda" href="delegacionindex.php?action=delete&id='.$obj->id.'">'.img_delete().'
				</a></td>';
				print "</tr>";

			}
	print '</form>';
} else {
	print load_fiche_titre($langs->trans("Datos de la Delegación"), '', 'companies');

	$dele = $_GET['dele'];

	$sql = " SELECT d.*, s.nom FROM ". MAIN_DB_PREFIX ."delegacion d ";
	$sql.= " INNER JOIN ". MAIN_DB_PREFIX ."societe s ON s.rowid = d.fk_tercero ";
	$sql.= " WHERE id = ".$dele;

	$resultDele = $db->query($sql);
	$deleg = $db->fetch_object($resultDele);

	print "<table class='border centpercent'>
		<tbody>
			<tr>
				<th colspan=2>DATOS DE LA DELEGACIÓN</th><th></th>
			</tr>
			<tr>
				<th colspan=2></th><th></th>
			</tr>
			<tr>
				<th colspan=2></th><th></th>
			</tr>
			<tr>
				<td>
					<span class='fieldrequired' >NOMBRE DE LA DELEGACIÓN: </span>
				</td>
				<td>
					<input value='".$deleg->nombre."' type='text' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Razón Social: </span>
				</td>
				<td>
					<input value='".$deleg->codigo_delegacion."' type='text' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Tercero: </span>
				</td>
				<td>
					<input type='text' value='".$deleg->nom."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='' >Nombre encargado: </span>
				</td>
				<td>
					<input value = '".$deleg->responsible_name."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >IVA: </span>
				</td>
				<td>
					<input value = '".$deleg->iva."'/>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >CP: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->cp."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Localidad: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->localidad."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Provincia: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->provincia."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Teléfono: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->telef1."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Fax: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->telef2."'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Email: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->email."' style='width:800px'>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección facturación: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion_factura."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Dirección materiales: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->direccion_material."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Forma envío: </span>
				</td>
				<td>
					<textarea type='text' style='resize:none; width: 800px;height: 60px;'>".$deleg->forma_envio."</textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class='field' >Teléfono transportista: </span>
				</td>
				<td>
					<input type='text' value = '".$deleg->tlf_transp."'>
				</td>
			</tr>
			

		</tbody>
	</table>
</div>";
}
	

if ($action == 'delete') {
	$id = $_GET['id'];
	echo '
	<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter" autocomplete="off">
	<input type="hidden" value="' . $id . '" name=id >
	<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 268.503px; left: 657.62px; z-index: 101;">
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
				¿Está seguro de querer eliminar la delegación?
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<button type="submit" class="ui-button ui-corner-all ui-widget" name="confirmmassaction">
					' ."Sí". '
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
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."delegacion where id=".$id;
	$respuesta = $db->query($sql);
	if($respuesta ){
		setEventMessages( "Delegación" .' eliminada', null, 'mesgs');
		header('Location: delegacionindex.php');
        die();
	}else{
		setEventMessages( 'Error al eliminar la delegación', null, 'errors');
	}
}

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

if (($delegacionE == "") || ($delegacionE == -1)) {

	print "<div style='display:flex;justify-content:center;min-height:50px'></div>";

	print "<div style='text-align: center;font-size:20px;padding-left:5px'>";
	for ($i = 1; $i <= $totalPages; $i++) {
		$activa = $i === $currentPage ? ' style="font-weight:bold;font-size:25px;margin: 0 5px"' : 'style="margin: 0 5px"';

		print "<a href='?page=".$i."'" . $activa . ">".$i."</a>";
	}

	print "</div>";

}

print '</div></div></div>';

print "<script>

$(document).ready(function() {
	$('.select_dele').select2();
});


</script>";

ob_end_flush();
// End of page
llxFooter();
$db->close();
ob_flush();