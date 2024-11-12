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

$formcompany = new FormCompany($db);

/*
 * View
 */

llxHeader('', $langs->trans(utf8_encode("Listado")));
print load_fiche_titre($langs->trans(utf8_encode("Listado Delegados")), '', 'companies');

print "
	<div class='div-table-responsive'>
	<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
	<tbody>
		<form method='POST' action='' name='formfilter' autocomplete='off'>
		<tr class='liste_titre_filter'>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='text' name='search_nombre'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth80imp' type='text' name='search_direccion'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>
				<input class='flat maxwidth75imp' type='text' name='search_poblacion'>
			</td>
			<td class='wrapcolumntitle liste_titre middle'>";
								//state_id
				print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code );//4
				
			print "
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
			<th class='wrapcolumntitle liste_titre' title='nombre'>
				<a class='reposition' href=''>Nombre</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='direccion'>
				<a class='reposition' href=''>Dirección</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='poblacion'>
				<a class='reposition' href=''>Población</a>
			</th>
			<th class='wrapcolumntitle liste_titre' title='provincia'>
				<a class='reposition' href=''>Provincia</a>
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

		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegados WHERE 1 ";
		if (isset($_POST['button_search'])) {
			$delegacion = array();

			if (isset($_POST['search_nombre']) && ($_POST['search_nombre']) != "") {
				$nombre = $_POST['search_nombre'];
				$sql .=' and nombre LIKE "%'.$nombre.'%"';
			}

			if (isset($_POST['search_direccion']) && ($_POST['search_direccion']) != "") {
				$direccion = $_POST['search_direccion'];
				$sql .=' and direccion LIKE "%'.$direccion.'%"';
			}
			  
			if (isset($_POST['search_poblacion']) && ($_POST['search_poblacion']) != "") {
				$poblacion = $_POST['search_poblacion'];
				$sql .=' and poblacion LIKE "%'.$poblacion.'%"';
			}

			if (isset($_POST['state_id']) && ($_POST['state_id']) != 0) {
				$provincia= $_POST['state_id'];
				$sql .=' and provincia='.$provincia;
			}
		}
		print '<form method="POST" action="" name="formfilter" autocomplete="off">';
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		while($aux<$num){
			$obj = $db->fetch_object($respuesta);

			$sqlProvincia = 'SELECT *  FROM '. MAIN_DB_PREFIX . 'c_departements WHERE rowid='.$obj->provincia;
			$resultProvincia = $db->query($sqlProvincia);
			$numProvincia = $db->num_rows($result);
			while ($numProvincia > 0) {
				$datos = $db->fetch_object($resultProvincia);
				$nombreProvincia=$datos->ncc;
				$numProvincia --;

			}

			print "<tr class='oddeven'>";
			print "<input type='hidden' name='id' value='" . $obj->id . "'>";
			print "<input type='hidden' name='nombre' value='" . $obj->nombre . "'>";
			print "<td class=' tdoverflowmax200'>" . $obj->nombre." ".$obj->apellidos."</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->direccion . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->poblacion . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $nombreProvincia . "</td> ";
			
			print " <td class='nowrap'>";
			print '<a class="editfielda" href="delegados_acciones.php?action=editar&id='; print $obj->id.'">
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
				Estas seguro de querer eliminar el delegado: '; echo $nombre.'?
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<button type="submit" class="ui-button ui-corner-all ui-widget" name="confirmmassaction">
					Si
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
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."delegados where id=".$id;
	$respuesta = $db->query($sql);
	if($respuesta ){
		setEventMessages( "Delegado eliminado", null, 'mesgs');
		header('Location: delegados.php');
	}else{
		setEventMessages( 'Error al eliminar el delegado', null, 'errors');
	}
}

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div></div>';

ob_end_flush();
// End of page
llxFooter();
$db->close();
//ob_flush();