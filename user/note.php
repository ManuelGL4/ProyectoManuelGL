<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/user/note.php
 *      \ingroup    usergroup
 *      \brief      Fiche de notes sur un utilisateur Dolibarr
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'usernote'; // To manage different context of search

const NOTE_TYPE = [
    '1' => 'Reunión',
    '2' => 'Otro',
];

// Load translation files required by page
$langs->loadLangs(array('companies', 'members', 'bills', 'users'));

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->getrights();

// If user is not user read and no permission to read other users, we stop
if (($object->id != $user->id) && (!$user->rights->user->user->lire)) {
	accessforbidden();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard', 'usernote', 'globalcard'));


/*
 * Actions
 */

if (isset($_POST['crear'])) {
	
	$visibilidad = $_POST['visibilidad'];
	$tipo = $_POST['tipo'];
	$descripcion = $_POST['descripcion'];

	$sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."user_notas ";
	$sqlInsert.= "(tipo, descripcion, fk_usuario, private) ";
	$sqlInsert.= "VALUES ";
	$sqlInsert.= "($tipo, '".$descripcion."', $object->id, $visibilidad)";

	$resultInsert = $db->query($sqlInsert);

}

if (isset($_POST['update'])) {
	
	$idnota = $_POST['idnota'];

	$visibilidad = $_POST['visibilidad'];
	$tipo = $_POST['tipo'];
	$descripcion = $_POST['descripcion'];

	$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."user_notas ";
	$sqlUpdate.= "SET tipo = $tipo, ";
	$sqlUpdate.= "descripcion = '".$descripcion."', ";
	$sqlUpdate.= "private = $visibilidad ";
	$sqlUpdate.= "WHERE rowid = ".$idnota;

	$resultUpdate = $db->query($sqlUpdate);

}

if (isset($_POST['delete'])) {
	
	$idnota = $_POST['idnota'];

	$sqlDelete = "DELETE FROM ".MAIN_DB_PREFIX."user_notas ";
	$sqlDelete.= "WHERE rowid = ".$idnota;

	$resultDelete = $db->query($sqlDelete);

}

/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id) {
	$head = user_prepare_head($object);

	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'note', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="underbanner clearboth"></div>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td><td class="valeur">'.$object->login.'&nbsp;</td><td></td><td></td></tr>';
	
	$consulta = "SELECT rowid, descripcion, tipo, private FROM ".MAIN_DB_PREFIX."user_notas WHERE fk_usuario = ".$object->id;

	$resultConsulta = $db->query($consulta);

	$numFilas = $db->num_rows($resultConsulta);

	if ($user->admin == 1) {

		if ($numFilas > 0) {

			while ($nota = $db->fetch_object($resultConsulta)) {

				$visible = "";

				if ($nota->private == 1) {

					$visible = "Privada";

				}

				print '<tr><td class="titlefield">'.$langs->trans("Nota ".$visible).'</td><td>Tipo: '.NOTE_TYPE[$nota->tipo].'</td><td class="valeur">'.$nota->descripcion.'&nbsp;</td>
				<td>
				<a class="fas fa-pencil-alt edit" style=" color: #444;" title="Modificar" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $object->id . '&idnota='.$nota->rowid.'"></a>
				<a class="fas fa-trash pictodelete" style="" title="Eliminar" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $object->id . '&idnota='.$nota->rowid.'"></a>
				</td>
				</tr>';

			}

		} else {

			print '<tr><td class="titlefield">No hay notas para mostrar</td><td class="valeur"></td><td></td></tr>';

		}

	} else {

		if ($numFilas > 0) {

			while ($nota = $db->fetch_object($resultConsulta)) {

				if ($nota->private == 0) {

					print '<tr><td class="titlefield">'.$langs->trans("Nota").'</td><td>Tipo: '.NOTE_TYPE[$nota->tipo].'</td><td class="valeur">'.$nota->descripcion.'&nbsp;</td>
					<td>
					</td>
					</tr>';

				}

			}

		} else {

			print '<tr><td class="titlefield">No hay notas para mostrar</td><td class="valeur"></td><td></td></tr>';

		}

	}

	print "</table>";
	print '</div>';

	// Nota

	print dol_get_fiche_end();
	

	/*
	 * Actions
	 */

	print '<div class="tabsAction">';

	if ($user->rights->user->user->creer && ($action != 'edit' && $action != 'create' && $action != 'delete')) {
		print "<a class=\"butAction\" href=\"note.php?id=".$object->id."&amp;action=create\">".$langs->trans('Crear Nota')."</a>";
	}

	print "</div>";

	if ($action == 'create') {
		print '<br>';
		print '<div class="underbanner clearboth"></div>';
		print '<div class="fichecenter">';
		print '<table class="border centpercent tableforfield">';
		print '<tr><td class="titlefield">'.$langs->trans("Nota nueva").'</td><td>Visibilidad: <select name="visibilidad" class="select-visibilidad" style="width:100px"><option value=0>Pública</option><option value=1>Privada</option></select></td><td class="valeur">Tipo: <select name="tipo" class="select-tipo" style="width:90px"><option value=-1>&nbsp;</option><option value=1>Reunión</option><option value=2>Otro</option></select></td><td>Descripción: <input type="text" name="descripcion" style="width:600px"></td></tr>';
		print "</table>";
		print '</div>';
		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button button-create" name="crear" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
	}

	if ($action == 'edit') {

		$idnota = $_GET['idnota'];

		$consulta = "SELECT tipo, descripcion, private FROM ".MAIN_DB_PREFIX."user_notas ";
		$consulta.= "WHERE rowid = ".$idnota;

		$resultConsulta = $db->query($consulta);

		$nota = $db->fetch_object($resultConsulta);

		print '<br>';
		print '<div class="underbanner clearboth"></div>';
		print '<div class="fichecenter">';
		print '<table class="border centpercent tableforfield">';
		print '<tr><td class="titlefield">'.$langs->trans("Editar nota").'</td><td>Visibilidad: 
		<select name="visibilidad" class="select-visibilidad" style="width:100px">';

		if ($nota->private == 0) {

			print '<option value=0 selected>Pública</option>';
			print '<option value=1>Privada</option>';

		} else {

			print '<option value=0>Pública</option>';
			print '<option value=1 selected>Privada</option>';

		}

		print '</select></td>
		<td class="valeur">Tipo: 
		<select name="tipo" class="select-tipo" style="width:90px">
		<option value=-1>&nbsp;</option>';

		if ($nota->tipo == 1) {

			print '<option value=1 selected>Reunión</option>';
			print '<option value=2>Otro</option>';

		} else {

			print '<option value=1>Reunión</option>';
			print '<option value=2 selected>Otro</option>';

		}

		print '</select></td>
		<td>Descripción: <input type="text" name="descripcion" style="width:600px" value="'.$nota->descripcion.'"></td></tr>';
		print "</table>";
		print '</div>';
		print '<br>';
		print '<div class="center">';
		print '<input type="hidden" name="idnota" value='.$idnota.'>';
		print '<input type="submit" class="button button-save" name="update" value="'.$langs->trans("Editar").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
	}

	if ($action == 'delete') {

		$idnota = $_GET['idnota'];

		$consulta = "SELECT tipo, descripcion, private FROM ".MAIN_DB_PREFIX."user_notas ";
		$consulta.= "WHERE rowid = ".$idnota;

		$resultConsulta = $db->query($consulta);

		$nota = $db->fetch_object($resultConsulta);
		
		print '<br>';
		print '<div class="underbanner clearboth"></div>';
		print '<div class="fichecenter">';
		print '<table class="border centpercent tableforfield">';
		print '<tr><td class="titlefield">'.$langs->trans("Eliminar nota").'</td><td>Visibilidad: 
		<select name="visibilidad" class="select-visibilidad" style="width:100px">';

		if ($nota->private == 0) {

			print '<option value=0 selected>Pública</option>';
			print '<option value=1>Privada</option>';

		} else {

			print '<option value=0>Pública</option>';
			print '<option value=1 selected>Privada</option>';

		}

		print '</select></td>
		<td class="valeur">Tipo: 
		<select name="tipo" class="select-tipo" style="width:90px">
		<option value=-1>&nbsp;</option>';

		if ($nota->tipo == 1) {

			print '<option value=1 selected>Reunión</option>';
			print '<option value=2>Otro</option>';

		} else {

			print '<option value=1>Reunión</option>';
			print '<option value=2 selected>Otro</option>';

		}

		print '</select></td>
		<td>Descripción: <input type="text" name="descripcion" style="width:600px" value="'.$nota->descripcion.'"></td></tr>';
		print "</table>";
		print '</div>';
		print '<br>';
		print '<div class="center">';
		print '<input type="hidden" name="idnota" value='.$idnota.'>';
		print '<input type="submit" class="button button-save" name="delete" value="'.$langs->trans("Eliminar").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

	}

	print "</form>\n";
}

print "<script>

$(document).ready(function() {
	$('.select-tipo').select2();
	$('.select-visibilidad').select2();
});

</script>";

// End of page
llxFooter();
$db->close();
