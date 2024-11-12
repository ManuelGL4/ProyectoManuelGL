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

const RESOURCE_TYPE = [
    '1' => 'Monitor',
    '2' => 'Impresora',
    '3' => 'Periférico',
    '4' => 'Portatil',
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

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'update' && $user->rights->user->user->creer && !GETPOST("cancel")) {
		$db->begin();

		$res = $object->update_note(dol_html_entity_decode(GETPOST('note_private', 'restricthtml'), ENT_QUOTES | ENT_HTML5));
		if ($res < 0) {
			$mesg = '<div class="error">'.$adh->error.'</div>';
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id) {
	$head = user_prepare_head($object);

	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'recursos', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}

$moreforfilter = '';

$consulta = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE code LIKE '%USERINCHARGE%'";
$resultConsulta = $db->query($consulta);
$idContact = $db->fetch_object($resultConsulta);

$consulta = "SELECT r.ref, re.tipo, re.marca, re.modelo, re.num_serie, re.fecha_compra, re.proveedor FROM khns_element_contact ec INNER JOIN khns_resource r ON ec.element_id = r.rowid INNER JOIN khns_resource_extrafields re ON re.fk_object = r.rowid WHERE ec.fk_c_type_contact = ".$idContact->rowid." AND ec.fk_socpeople = ".$id;
$resultConsulta = $db->query($consulta);
$numFilas = $db->num_rows($resultConsulta);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';

print '<tr class="liste_titre">';

print '<th>Recurso</th>';
print '<th>Tipo</th>';
print '<th>Marca</th>';
print '<th>Modelo</th>';
print '<th>Nº serie</th>';
print '<th>Fecha de compra</th>';
print '<th>Proveedor</th>';

if ($numFilas > 0) {
    while ($recurso = $db->fetch_object($resultConsulta)) {

        $tipo = RESOURCE_TYPE[$recurso->tipo];

        print "</tr>";


            print '<tr class="oddeven">';

                print '<td>';
                print $recurso->ref;
                print '</td>';
                print '<td>';
                print $tipo;
                print '</td>';
                print '<td>';
                print $recurso->marca;
                print '</td>';
                print '<td>';
                print $recurso->modelo;
                print '</td>';
                print '<td>';
                print $recurso->num_serie;
                print '</td>';
                print '<td>';
                print $recurso->fecha_compra;
                print '</td>';
                print '<td>';
                print $recurso->proveedor;
                print '</td>';
            print '</tr>';

    }
} else {
    print '<tr><td colspan=7 class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}
}

print '</table>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
