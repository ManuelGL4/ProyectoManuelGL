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

/*
 * View
 */

llxHeader('', $langs->trans(utf8_encode("Listado")));
print load_fiche_titre($langs->trans(utf8_encode("Listado Rutas")), '', 'companies');


print "
	<div class='div-table-responsive'>
	<table class='tagtable nobottomiftotal liste listwithfilterbefore'>
	<tbody>
		<tr class='liste_titre'>
			<th class='wrapcolumntitle liste_titre' title='id'>
				<a class='reposition' href=''>Id Ruta</a>
			</th>
			<th class='wrapcolumntitle liste_titre' title='name'>
				<a class='reposition' href=''>Nombre Ruta</a>
			</th>
			<th class='wrapcolumntitle liste_titre' title='name'>
				<a class='reposition' href=''>Código</a>
			</th>
			<th class='wrapcolumntitle  liste_titre' title='accion'>
			</th>
		</tr>
		";

		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."ruta WHERE 1 ";

		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		while($obj = $db->fetch_object($respuesta)){
			print "<tr class='oddeven'>";

			print "<td class=' tdoverflowmax200'>" . $obj->id ."</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->ruta . "</td> ";
			print "<td class=' tdoverflowmax200'>" . $obj->codigo . "</td> ";

			print " <td class='nowrap'>";
			print '<a class="editfielda" href="ruta.php?action=editar&id='; print $obj->id.'">'.img_edit().'
			</a>';
			print '<a class="editfielda" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id='.$obj->id.'">'.img_delete().'
			</a></td>';
			print "</tr>";

		}


if ($action == "delete") {
	$id = $_GET['id'];

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
				¿Seguro que deseas eliminar la ruta?
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

print '</div></div></div>';

if (isset($_POST['confirmmassaction'])) {
	$archivoActual = $_SERVER['PHP_SELF'];
	$id = $_POST['id'];
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."ruta where id=".$id;
	$respuesta = $db->query($sql);
	if($respuesta ){
		setEventMessages( utf8_encode("Ruta") .' eliminada', null, 'mesgs');
		header('Location: rutaindex.php');
		die();
	}else{
		setEventMessages( 'Error al eliminar la Ruta', null, 'errors');
	}
}

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

ob_end_flush();
// End of page
llxFooter();
$db->close();
ob_flush();