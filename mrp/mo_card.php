<?php
/* Copyright (C) 2017-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       mo_card.php
 *		\ingroup    mrp
 *		\brief      Page to create/edit/view mo
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp_mo.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array("mrp", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'mocard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

if (isset($_GET['idaveria'])) {
	$areparar = $_GET['areparar'];
	$aconsumir = $_GET['aconsumir'];
	$idaveria = $_GET['idaveria'];
	$qty = $_GET['qty'];
	$idequipo = $_GET['idequipo'];
}

// Initialize technical objects
$object = new Mo($db);
$objectbom = new BOM($db);
$objectproduct = new Product($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('mocard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

if (GETPOST('fk_bom', 'int')) {
	$objectbom->fetch(GETPOST('fk_bom', 'int'));

	if ($action != 'add') {
		// We force calling parameters if we are not in the submit of creation of MO
		$_POST['fk_product'] = $objectbom->fk_product;
		$_POST['qty'] = $objectbom->qty;
		$_POST['fk_warehouse'] = $objectbom->fk_warehouse;
		$_POST['note_private'] = $objectbom->note_private;
	}
}

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = $user->rights->mrp->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->mrp->write; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->mrp->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->mrp->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				if (isset($idaveria)) {
					$backtopage = DOL_URL_ROOT.'/mrp/mo_card.php?reparacion='.$idaveria.'&id='.($id > 0 ? $id : '__ID__');
				} else {
					$backtopage = DOL_URL_ROOT.'/mrp/mo_card.php?&id='.($id > 0 ? $id : '__ID__');
				}
				
			}
		}
	}
	if ($cancel && !empty($backtopageforcancel)) {
		$backtopage = $backtopageforcancel;
	}

	$triggermodname = 'MRP_MO_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'MO_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'MO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
	$trackid = 'mo'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	// Action close produced
	if ($action == 'confirm_produced' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, 0, 0, 0);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans('Mo')." - ".$langs->trans("Card");

llxHeader('', $title, '');
//Crear tarea para el proyecto
if ($action == 'create_task') {

	$sqlMrp = "SELECT * FROM ".MAIN_DB_PREFIX."mrp_mo WHERE rowid=".$_GET["id"];
	$respuestaMrp = $db->query($sqlMrp);
	$objMrp = $db->fetch_object($respuestaMrp);
	
	// $sqlMax = "SELECT * FROM ".MAIN_DB_PREFIX."mrp_mo WHERE rowid=".$_GET["id"];
	// $respuestaMrp = $db->query($sqlMrp);
	// $objMrp = $db->fetch_object($respuestaMrp);

	$defaultref = '';
	$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
	if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php")) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$defaultref = $modTask->getNextValue($object->thirdparty, null);
	}

	$sqlTask = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (ref,fk_projet,datec,label,dateo)";
	$sqlTask.= " VALUES ('".$defaultref."',".$objMrp->fk_project.",'".date('Y-m-d')."','Orden de fabricación ".$object->ref."','".date('Y-m-d')."')";
	$respuestaTask = $db->query($sqlTask);

	if ($respuestaTask) {

		//ACTUALIZAMOS LA TAREA EN EXTRAFIELDS DE LA MO
		//SACAMOS EL ULTIMO ID INTRODUCIDO EN LA TABLA
		$sqlUltimoId = " SELECT MAX(rowid) as maximo FROM ".MAIN_DB_PREFIX."projet_task ";

		$resultId = $db->query($sqlUltimoId);
		$ultimoId = $db->fetch_object($resultId);

		$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
		$sqlUpdate.= " SET fk_task = ".$ultimoId->maximo."";
		/*if ($idaveria != "") {
			$sqlUpdate.= ", fk_averia = ".$idaveria." ";
		}*/

		$sqlUpdate.= " WHERE fk_mo = ".$id;
		$db->query($sqlUpdate);

		/*$selectAveria = " SELECT rowid FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias ";
		$selectAveria.= " ORDER BY rowid DESC LIMIT 1 ";

		$resultAveria = $db->query($selectAveria);
		$averia = $db->fetch_object($resultAveria);

		$sqlUpdate2 = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
		$sqlUpdate2.= " SET fk_averia = ".$averia->rowid."";

		$sqlUpdate2.= " WHERE fk_mo = ".$id;
		$db->query($sqlUpdate2);*/
		
		setEventMessages("Tarea creada con correctamente",[],'mesgs');
	}else{
		setEventMessages("La tarea no se pudo crear con exito",[],'errors');
	}
}

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Mo")), '', 'mrp');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	?>
	<script>
		 $(document).ready(function () {
			jQuery('#fk_bom').change(function() {
				console.log('We change value of BOM with BOM of id '+jQuery('#fk_bom').val());
				if ((jQuery('#fk_bom').val() > 0))
				{
					// Redirect to page with fk_bom set
					window.location.href = '<?php echo $_SERVER["PHP_SELF"] ?>?action=create&fk_bom='+jQuery('#fk_bom').val();
					/*
					$.getJSON('<?php echo DOL_URL_ROOT ?>/mrp/ajax/ajax_bom.php?action=getBoms&idbom='+jQuery('#fk_bom').val(), function(data) {
						console.log(data);
						if (typeof data.rowid != "undefined") {
							console.log("New BOM loaded, we set values in form");
							$('#qty').val(data.qty);
							$("#fk_product").val(data.fk_product);
							$('#fk_product').trigger('change'); // Notify any JS components that the value changed
							$('#note_private').val(data.description);
							$('#note_private').trigger('change'); // Notify any JS components that the value changed
							$('#fk_warehouse').val(data.fk_warehouse);
							$('#fk_warehouse').trigger('change'); // Notify any JS components that the value changed
							if (typeof CKEDITOR != "undefined") {
								if (typeof CKEDITOR.instances != "undefined") {
									if (typeof CKEDITOR.instances.note_private != "undefined") {
										console.log(CKEDITOR.instances.note_private);
										CKEDITOR.instances.note_private.setData(data.description);
									}
								}
							}
						} else {
							console.log("Failed to get BOM");
						}
					});*/
				}
				else if (jQuery('#fk_bom').val() < 0) {
					// Redirect to page with all fields defined except fk_bom set
					console.log(jQuery('#fk_product').val());
					window.location.href = '<?php echo $_SERVER["PHP_SELF"] ?>?action=create&qty='+jQuery('#qty').val()+'&fk_product='+jQuery('#fk_product').val()+'&label='+jQuery('#label').val()+'&fk_project='+jQuery('#fk_project').val()+'&fk_warehouse='+jQuery('#fk_warehouse').val();
					/*
					$('#qty').val('');
					$("#fk_product").val('');
					$('#fk_product').trigger('change'); // Notify any JS components that the value changed
					$('#note_private').val('');
					$('#note_private').trigger('change'); // Notify any JS components that the value changed
					$('#fk_warehouse').val('');
					$('#fk_warehouse').trigger('change'); // Notify any JS components that the value changed
					*/
				}
			 });

			//jQuery('#fk_bom').trigger('change');
		})
	</script>
	<?php

	print '<div class="center">';
	print '<input type="hidden" name="ocultoRepa" value="'.$areparar.'">';
	print '<input type="hidden" name="ocultoConsu" value="'.$aconsumir.'">';
	print '<input type="hidden" name="ocultoQty" value="'.$qty.'">';
	print '<input type="hidden" name="ocultoAveria" value="'.$idaveria.'">';
	print '<input type="hidden" name="ocultoEquipo" value="'.$idequipo.'">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	if (GETPOST('fk_bom', 'int') > 0) {
		print load_fiche_titre($langs->trans("ToConsume"));

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$object->lines = $objectbom->lines;
		$object->bom = $objectbom;

		$object->printOriginLinesList('', array());

		print '</table>';
		print '</div>';
	}

	/*if (isset($_GET['areparar'])) {
		$objectproduct->fetch(GETPOST('aconsumir', 'int'));
		print load_fiche_titre($langs->trans("ToConsume"));

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		//var_dump($objectproduct);
		//print_r($objectproduct);
		$object->lines = $objectproduct->lines;
		$object->bom = $objectbom;

		//$object->printOriginLinesList('', array());

		//SACAR PRODUCTO A CONSUMIR
		$sqlProducto = " SELECT * FROM ".MAIN_DB_PREFIX."product ";
		$sqlProducto.= " WHERE rowid = ".$aconsumir;

		$resultProducto = $db->query($sqlProducto);
		$producto = $db->fetch_object($resultProducto);

		print '<div class="div-table-responsive-no-min"><table class="noborder centpercent"><tbody><tr class="liste_titre"><td>Ref.</td><td class="right">Cant. <span class="opacitymedium">(Para una cantidad a producir de 1)</span></td><td class="center">Cantidad reservada</td><td class="center">Cambio de stock deshabilitado</td><td class="center"></td></tr>

		<tr class="oddeven"><td><a href="./../product/card.php?id='.$producto->rowid.'" title="<span class=&quot;fas fa-cube&quot; style=&quot; color: #a69944;&quot;></span> <u class=&quot;paddingrightonly&quot;>Producto</u> <span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;En venta&quot;>En venta</span> <span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;En compra&quot;>En compra</span><br><b>Ref. producto:</b> pieza_1<br><b>Etiqueta producto:</b> pieza 1<br><b>Usar numeración por lotes/series</b>: No (no se usa lote/serie)<br><b>Código de barras:</b> <br><b>Valor (PMP)</b>: 0,00 €" class="nowraponall classfortooltip"><span class="fas fa-cube paddingright classfortooltip" style=" color: #a69944;"></span>'.$producto->label.'</a></td><td class="right">'.$qty.'</td><td class="center"></td><td class="center"></td><td class="center"></td></tr>

		</tbody></table></div>';

		print '</table>';
		print '</div>';
	}*/

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ManufacturingOrder"), '', 'mrp');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	$object->fields['fk_bom']['disabled'] = 1;

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	$head = moPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("ManufacturingOrder"), -1, $object->picto);

	/*$sqlUpdate = " UPDATE ".MAIN_DB_PREFIX."mrp_production ";
	$sqlUpdate.= " SET role = 'toproduce' ";
	$sqlUpdate.= " WHERE fk_mo = ".$object->id;*/

	$formconfirm = '';

	if (isset($_GET['reparacion'])) {
		$repa = $_GET['reparacion'];
		$sqlUpdate2 = " UPDATE ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
		$sqlUpdate2.= " SET fk_averia = ".$repa."";

		$sqlUpdate2.= " WHERE fk_mo = ".$id;
		$db->query($sqlUpdate2);
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirmation of validation
	if ($action == 'validate') {
		// We check that object has a temporary ref
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$object->fetch_product();
			$numref = $object->getNextNumRef($object->fk_product);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateMo', $numref);
		/*if (! empty($conf->notification->enabled))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_VALIDATE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (!empty($conf->mrp->enabled)) {
			$langs->load("mrp");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);*/
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' ';
	$morehtmlref .= ': '.(is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	// Project
	if (!empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($permissiontoadd) {
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= ' : '.$proj->getNomUrl();
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		//$result = $object->getLinesArray();
		$object->fetchLines();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
    	<input type="hidden" name="token" value="' . newToken().'">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
    	<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
    	<input type="hidden" name="id" value="' . $object->id.'">
    	';

		/*if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}*/

		if (!empty($object->lines)) {
			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder noshadow" width="100%">';

			print '<tr class="liste_titre">';
			print '<td class="liste_titre">'.$langs->trans("Summary").'</td>';
			print '<td></td>';
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("ProductsToConsume").'</td>';
			print '<td>';
			if (!empty($object->lines)) {
				$i = 0;
				foreach ($object->lines as $line) {
					if ($line->role == 'toconsume') {
						if ($i) {
							print ', ';
						}
						$tmpproduct = new Product($db);
						$tmpproduct->fetch($line->fk_product);
						print $tmpproduct->getNomUrl(1);
						$i++;
					}
				}
			}
			//print_r ($object->lines);
			print '</td>';
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("ProductsToProduce").'</td>';
			print '<td>';
			if (!empty($object->lines)) {
				$i = 0;
				foreach ($object->lines as $line) {
					if ($line->role == 'toproduce') {
						if ($i) {
							print ', ';
						}
						$tmpproduct = new Product($db);
						$tmpproduct->fetch($line->fk_product);
						print $tmpproduct->getNomUrl(1);
						$i++;
					}
				}
			}
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';
		}

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			//if (empty($user->socid)) {
			//	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";
			//}

			// Crear tarea asociada al proyecto
			if ($object->status == $object::STATUS_VALIDATED) {

				$sqlTarea = " SELECT fk_task FROM ".MAIN_DB_PREFIX."mrp_mo_extrafields_new ";
				$sqlTarea.= " WHERE fk_mo = ".$id;

				$resultTarea = $db->query($sqlTarea);
				$tarea = $db->fetch_object($resultTarea);
				$tarea = $tarea->fk_task;

				if ($permissiontoadd) {
					if ($tarea == "") {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create_task&confirm=yes">'.$langs->trans("Crear tarea en proyecto").'</a>';
					}
				}
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				if ($permissiontoadd) {
					// TODO Add test that production has not started
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes">'.$langs->trans("SetToDraft").'</a>';
				}
			}

			// Modify
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
				}
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate">'.$langs->trans("Validate").'</a>';
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Clone
			if ($permissiontoadd) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->fk_soc.'&action=clone&object=mo">'.$langs->trans("ToClone").'</a>';
			}

			// Cancel - Reopen
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_INPROGRESS) {
					$arrayproduced = $object->fetchLinesLinked('produced', 0);
					$nbProduced = 0;
					foreach ($arrayproduced as $lineproduced) {
						$nbProduced += $lineproduced['qty'];
					}
					if ($nbProduced > 0) {	// If production has started, we can close it
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_produced&confirm=yes">'.$langs->trans("Close").'</a>'."\n";
					} else {
						print '<a class="butActionRefused" href="#" title="'.$langs->trans("GoOnTabProductionToProduceFirst", $langs->transnoentitiesnoconv("Production")).'">'.$langs->trans("Close").'</a>'."\n";
					}

					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_close&confirm=yes">'.$langs->trans("Cancel").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_PRODUCED || $object->status == $object::STATUS_CANCELED) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes">'.$langs->trans("ReOpen").'</a>'."\n";
				}
			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd)) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		// Documents
		$objref = dol_sanitizeFileName($object->ref);
		$relativepath = $objref.'/'.$objref.'.pdf';
		$filedir = $conf->mrp->dir_output.'/'.$objref;
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $user->rights->mrp->read; // If you can read, you can build the PDF to read content
		$delallowed = $user->rights->mrp->create; // If you can create/edit, you can remove a file on card
		print $formfile->showdocuments('mrp:mo', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $mysoc->default_lang);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('mo'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/mrp/mo_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'mo', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'mo';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->mrp->dir_output;
	$trackid = 'mo'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

print '<script src="https://code.jquery.com/jquery-3.7.0.js"></script>';
//Para el datepicker
print '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
//Para select2
//print '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />';
print '<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>';

if (isset($_GET['idaveria'])) {
	$areparar = $_GET['areparar'];
	$aconsumir = $_GET['aconsumir'];
	$idaveria = $_GET['idaveria'];
	$qty = $_GET['qty'];

	$sqlAveria = " SELECT fk_cliente, fk_project FROM ".MAIN_DB_PREFIX."averiasreparaciones_averias ";
	$sqlAveria.= " WHERE rowid = ".$idaveria;

	$resultAveria = $db->query($sqlAveria);
	$averia = $db->fetch_object($resultAveria);

	/*print '<script>
			$(document).ready(function() {
			var bom = '.$bom->rowid.';
			var areparar = '.$areparar.';
			var aconsumir = '.$aconsumir.';
			var cantidad = '.$qty.';
			var cliente = '.$averia->fk_cliente.';
			var proyecto = '.$averia->fk_project.';
			console.log(areparar);

			$("select[name=\'fk_bom\']").val(bom);
			$("select[name=\'fk_product\']").val(areparar);
			$("select[name=\'fk_soc\']").val(cliente);
			$("select[name=\'fk_project\']").val(proyecto);
			$("#qty").val(1);
		});
	</script>';*/

		print '<script>
		$(document).ready(function() {
		var areparar = '.$areparar.';
		var aconsumir = '.$aconsumir.';
		var cantidad = '.$qty.';
		var cliente = '.$averia->fk_cliente.';
		var proyecto = '.$averia->fk_project.';
		console.log(areparar);

		$("select[name=\'fk_soc\']").val(cliente);
		$("select[name=\'fk_project\']").val(proyecto);
	});
	</script>';
}

print "<script>
$(document).ready(function() {
	$('#fk_product').select2();
});
$(document).ready(function() {
	$('#fk_bom').select2();
});
$(document).ready(function() {
	$('#fk_soc').select2();
});
$(document).ready(function() {
	$('#fk_project').select2();
});
$(document).ready(function() {
	$('#fk_warehouse').select2();
});
</script>";

// End of page
llxFooter();
$db->close();
