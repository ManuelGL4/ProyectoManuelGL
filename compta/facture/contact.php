<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2009 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *       \file       htdocs/compta/facture/contact.php
 *       \ingroup    facture
 *       \brief      Onglet de gestion des contacts des factures
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies'));

$id     = (GETPOST('id') ?GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Facture($db);
// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref, '', '', $conf->global->INVOICE_USE_SITUATION);
}

$result = restrictedArea($user, 'facture', $object->id);


/*
 * Add a new contact
 */

if ($action == 'addcontact' && $user->rights->facture->creer) {
	if ($result > 0 && $id > 0) {
		$contactid = (GETPOST('userid') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
		$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
} elseif ($action == 'swapstatut' && $user->rights->facture->creer) {
	// Toggle the status of a contact
	$result = $object->swapContactStatus(GETPOST('ligne', 'int'));
} elseif ($action == 'deletecontact' && $user->rights->facture->creer) {
	// Deletes a contact
	$result = $object->delete_contact($lineid);

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$title = $langs->trans('InvoiceCustomer')." - ".$langs->trans('ContactsAddresses');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* View and edit mode                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		$object->fetch_thirdparty();

		$head = facture_prepare_head($object);

		$totalpaye = $object->getSommePaiement();

		print dol_get_fiche_head($head, 'contact', $langs->trans('InvoiceCustomer'), -1, 'bill');

		// Invoice content

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
		// Project
		if (!empty($conf->projet->enabled)) {
			$langs->load("projects");
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($user->rights->facture->creer) {
				if ($action != 'classify') {
					//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
					$morehtmlref .= ' : ';
				}
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
					$morehtmlref .= $proj->ref;
					$morehtmlref .= '</a>';
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$object->totalpaye = $totalpaye; // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

		print dol_get_fiche_end();

		print '<br>';

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
		foreach ($dirtpls as $reldir) {
			$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) {
				break;
			}
		}
	} else {
		// Record not found
		print "ErrorRecordNotFound";
	}

	$sqlTercero = " SELECT nom FROM ".MAIN_DB_PREFIX."societe ";
	$sqlTercero.= " WHERE rowid = ".$object->socid." ";

	$resultTecero = $db->query($sqlTercero);
	$tercero = $db->fetch_object($resultTercero);

	//DELEGACIONES
	$sqlDelegaciones = " SELECT id, nombre FROM ".MAIN_DB_PREFIX."delegacion ";
	$sqlDelegaciones.= " WHERE fk_tercero = ".$object->socid." ";

	$resultDelegacion = $db->query($sqlDelegaciones);

	//PARA COMPROBAR SI YA HAY DIRECCIÓN
	$sqlDir = " SELECT dir_adicional FROM ".MAIN_DB_PREFIX."facture_extrafields ";
	$sqlDir.= " WHERE fk_object = ".$id." ";

	$resultDir = $db->query($sqlDir);
	$dir = $db->fetch_object($resultDir);

	print "<br>";

	print '<div class="div-table-responsive-no-min">
<div class="tagtable tableforcontact centpercent noborder nobordertop allwidth">
	<form class="tagtr liste_titre">
		<div class="tagtd liste_titre"><span class="fas fa-building optiongrey paddingright" style=" color: #6c6aa8;"></span>Tercero</div>
		<div class="tagtd liste_titre"><span class="fas fa-address-book optiongrey paddingright" style=" color: #6c6aa8;" title="Delegaciones"></span>Se cogerá como dirección adicional la de esta delegación</div>
		<div class="tagtd liste_titre">&nbsp;</div>
		<div class="tagtd liste_titre">&nbsp;</div>
	</form>

	<form class="tagtr impair nohover" action="../../compta/facture/contact.php?id='.$id.'&action=addDele" method="POST">
		<div class="tagtd">'.$tercero->nom.'</div>
		<div class="tagtd maxwidthonsmartphone">
		<select name="delegacion" class="select_dele" style="width:100%">
		<option value=-1>&nbsp</option>';
			
		while ($delegacion = $db->fetch_object($resultDelegacion)) {
			if ($dir->dir_adicional == $delegacion->id) {
				print '<option value='.$delegacion->id.' selected>'.$delegacion->nombre.'</option>';
			} else {
				print '<option value='.$delegacion->id.'>'.$delegacion->nombre.'</option>';
			}
		}
		
		print '</select>

</div>
		<div class="tagtd">&nbsp;</div>
		<div class="tagtd center"><input type="submit" class="button" value="Añadir"></div>
	</form>


		</div></div>';
	
}

if ($action == "addDele") {
	
	$delegacion = $_POST['delegacion'];
	
	$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."facture_extrafields ";
	$sqlUpdate.= " SET dir_adicional = ".$delegacion." ";
	$sqlUpdate.= " WHERE fk_object = ".$id." ";

	$resultUpdate = $db->query($sqlUpdate);

	print '<meta http-equiv="refresh" content="0; url=' . $_SERVER['PHP_SELF'] . '?facid='.$id.'">';

}

print "<script>

$(document).ready(function() {
	$('.select_dele').select2();
});

</script>";

// End of page
llxFooter();
$db->close();
