<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       nota_card.php
 *		\ingroup    notas
 *		\brief      Page to create/edit/view nota
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

/*print
'<style>
.creator {
	font-weight: bold;
	font-size: 20px;
}
.date_creation {
	margin-left: 20px;
}
.comment {
	margin-top: 30px;
}
form input[type="text"] {
	background: rgb(233,234,237);
	border-radius: 10px;
	width: 600px;
	height: 40px;
}
form input[type="submit"]:hover {
	cursor: pointer;
}
.edited {
	font-style: italic;
}
.act_title {
	font-weight: bold;
}

</style>';*/

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/notas/class/nota.class.php');
dol_include_once('/notas/lib/notas_nota.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("notas@notas", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'notacard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$parent = GETPOST('fk_parent', 'int');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Nota($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->notas->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('notacard', 'globalcard')); // Note that conf->hooks_modules contains array

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


$permissiontoread = $user->rights->notas->nota->read;
$permissiontoadd = $user->rights->notas->nota->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->notas->nota->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->notas->nota->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->notas->nota->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->notas->multidir_output[isset($object->entity) ? $object->entity : 1].'/nota';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->notas->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


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

	$backurlforlist = dol_buildpath('/notas/nota_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/notas/notasindex.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'NOTAS_NOTA_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'NOTAS_NOTA_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_NOTA_TO';
	$trackid = 'nota'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Nueva nota");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
// print '<script type="text/javascript" language="javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	//print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Notas")), '', 'object_'.$object->picto);
	//print load_fiche_titre($langs->trans("Notas"), '', 'object_'.$object->picto);

	if ($user->admin) {

		?>
		<table class="centpercent notopnoleftnoright table-fiche-title">
			<tr>
				<td class="nobordernopadding widthpictotitle valignmiddle col-picto">
					<span class="far fa-clipboard infobox-project valignmiddle pictotitle widthpictotitle" style=""></span>
				</td>
				<td class="nobordernopadding valignmiddle col-title">
					<div class="titre inline-block">
						<span style="padding: 0px; padding-right: 3px !important;">Nueva nota</span>
					</div>
				</td>
				<td class="nobordernopadding valignmiddle col-title" align="right">
					<a class="btnTitle btnTitlePlus" href="<?php echo DOL_URL_ROOT; ?>/custom/notas/notasindex.php" title="Notas"><span class="fa fa-list-alt valignmiddle btnTitle-icon"></span></a>
				</td>
			</tr>
		</table>
		<?php

		print '<form name="f1" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		}
		if ($backtopageforcancel) {
			print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
		}

		print dol_get_fiche_head(array(), '');

		// Set some default values
		//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

		print '<table class="border centpercent tableforfieldcreate">'."\n";

		// Common attributes
		//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';
		//************************************88
		$object->fields = dol_sort_array($object->fields, 'position');

		foreach ($object->fields as $key => $val) {
			// Discard if extrafield is a hidden field on form
			if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
				continue;
			}

			if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
				continue; // We don't want this field
			}

			print '<tr class="field_'.$key.'">';
			print '<td';
			print ' class="titlefieldcreate';
			if (isset($val['notnull']) && $val['notnull'] > 0) {
				print ' fieldrequired';
			}
			if ($val['type'] == 'text' || $val['type'] == 'html') {
				print ' tdtop';
			}
			print '"';
			print '>';
			if (!empty($val['help'])) {
				print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
			} else {
				print $langs->trans($val['label']);
			}
			print '</td>';
			print '<td class="valuefieldcreate">';

			if ($key!='priority') {

				if (!empty($val['picto'])) {
					print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
				}
				if (in_array($val['type'], array('int', 'integer'))) {
					$value = GETPOST($key, 'int');
				} elseif ($val['type'] == 'double') {
					$value = price2num(GETPOST($key, 'alphanohtml'));
				} elseif ($val['type'] == 'text' || $val['type'] == 'html') {
					$value = GETPOST($key, 'restricthtml');
				} elseif ($val['type'] == 'date') {
					$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
				} elseif ($val['type'] == 'datetime') {
					$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
				} elseif ($val['type'] == 'boolean') {
					$value = (GETPOST($key) == 'on' ? 1 : 0);
				} elseif ($val['type'] == 'price') {
					$value = price2num(GETPOST($key));
				} else {
					$value = GETPOST($key, 'alphanohtml');
				}
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key=='fk_user') {

						// PARA CREAR EL ARRAY DE IDS QUE NO DEBE MOSTRAR
						/*$sqlRights = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
						$sqlRights.= "WHERE module LIKE 'notas' ";
						$sqlRights.= "LIMIT 1";
						$result = $db->query($sqlRights);
						$idRight = $db->fetch_object($result);

						$sqlListaUsu = "SELECT u.* FROM " . MAIN_DB_PREFIX . "user u ";
						$sqlListaUsu.= "LEFT JOIN ".MAIN_DB_PREFIX."user_rights ur ";
						$sqlListaUsu.= "ON u.rowid = ur.fk_user ";
						$sqlListaUsu.= "AND ur.fk_id = ".$idRight->id." ";
						$sqlListaUsu.= "WHERE ur.fk_user IS NULL ";
						$listUsuarios = $db->query($sqlListaUsu);*/

						// PARA LOS PERMISOS DEL GRUPO TELETRABAJO
						$sqlRights = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
						$sqlRights.= "WHERE module LIKE 'notas' ";
						$sqlRights.= "LIMIT 1";
						$result = $db->query($sqlRights);
						$idRight = $db->fetch_object($result);

						$sqlGroup = "SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_rights ";
						$sqlGroup.= "WHERE fk_id = ".$idRight->id."";
						
						$result = $db->query($sqlGroup);
						$group = $db->fetch_object($result);
					
						$sqlUs = "SELECT u.rowid, u.firstname, u.lastname FROM ".MAIN_DB_PREFIX."user u ";
						$sqlUs.= "WHERE NOT EXISTS (";
						$sqlUs.= "SELECT * FROM ".MAIN_DB_PREFIX."usergroup_user gu ";
						$sqlUs.= "WHERE gu.fk_user = u.rowid AND gu.fk_usergroup = ".$group->fk_usergroup.")";
						$listUsuarios = $db->query($sqlUs);

						$arrayIdsExc = array();

						while ($userExc = $db->fetch_object($listUsuarios)) {
							$arrayIdsExc[] = $userExc->rowid;
						}

						print $form->select_dolusers($value?$value: $user->id, 'fk_user', 0, $arrayIdsExc, 0, '', '', 0, 0, 0, '', 1, '', $val['css'], 0, 0, true);
					} else {
						print $object->showInputField($val, $key, $value, '', '', '', 0);
					}

				}

			} else {

				print '<select class="select-priority" name="priority">';

				$arrayPrio = array("1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "10" => 10);

				foreach ($arrayPrio as $clave => $valor) {
					print '<option value='.$clave.'>'.$valor.'</option>';

				}

				print '</select>';

				print "<script>

				$(document).ready(function() {
					$('.select-priority').select2();
				});
				</script>";

			}

			if ($key=='fk_project') {

				print '<tr class="field_fk_parent">';
				print '<td class="fieldcreate fieldrequired">Tarea Padre</td>';
				print '<td class="valuefieldcreate">';
				print '<select class="select-parent" name="parent" id="fk_parent" style="width:400px">';

				print '</option>';
				print '</td>';
				print '</tr>';

				print "<script>

				$(document).ready(function() {
					$('.select-parent').select2();
				});
				</script>";

			}

			print '</td>';
			print '</tr>';

		}


		//****************************

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '</table>'."\n";

		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
		print '&nbsp; ';
		print '<a class="button button-cancel" href="notasindex.php?idmenu=578&mainmenu=notas&leftmenu=">Anular</a>';
		print '</div>';

		print '</form>';

		//dol_set_focus('input[name="ref"]');

	} else {

		$destination_url = 'notasindex.php';
		
		print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';
		

	}

}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Nota"), '', 'object_'.$object->picto);

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
	$res = $object->fetch_optionals();

	//$head = notePrepareHead($object);
	//print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteNote'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
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


	if ((int)$id>0) {

		$notes_user = $user->id; //mine
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota as t
				WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.rowid = ".((int)$id)."
				ORDER BY tms DESC ";
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);

		if ($nbtotalofrecords>0) {
			$obj = $db->fetch_object($result);

			?>
			<table class="centpercent notopnoleftnoright table-fiche-title">
				<tr>
					<td class="nobordernopadding widthpictotitle valignmiddle col-picto">
						<span class="far fa-clipboard infobox-project valignmiddle pictotitle widthpictotitle" style=""></span>
					</td>
					<td class="nobordernopadding valignmiddle col-title">
						<div class="titre inline-block">
							<span style="padding: 0px; padding-right: 3px !important; font-size: 120%;"><?php echo $obj->label; ?></span>
						</div>
					</td>
					<td class="nobordernopadding valignmiddle col-title" align="right">
						<a class="btnTitle btnTitlePlus" href="<?php echo DOL_URL_ROOT; ?>/custom/notas/notasindex.php" title="Notas"><span class="fa fa-list-alt valignmiddle btnTitle-icon"></span></a>
					</td>
				</tr>
			</table>
			<?php

			$canedit = 0;

			if ($obj->fk_user_creat == $notes_user || $user->rights->notas->nota->delete) {
				$canedit = 1;
			}

			$tuser = new User($db);
			$tuser->fetch($obj->fk_user_creat);

			print '<div style="clear:both; overflow:hidden;">';
			print '<div class="dash_in" style="float:right;">';

			/*if ($canedit) {
				print '<div style="float:right;">';
				print '<a class="marginleftonly" href="'.DOL_URL_ROOT.'/custom/notas/nota_card.php?action=edit&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode("notasindex.php?noteid=".$obj->rowid).'">'.img_edit()."</a>";

				print '<a class="marginleftonly" href="'.DOL_URL_ROOT.'/custom/notas/nota_card.php?action=delete&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode("notasindex.php?noteid=".$obj->rowid).'">'.img_delete()."</a>";

				print '</div>';
			}*/

			print $tuser->getNomUrl(-2);
			if ($obj->fk_user_creat != $obj->fk_user) { //shared content
				print " &nbsp;";
				if ($obj->fk_user>0) {
					$tuser->fetch($obj->fk_user);
					print $tuser->getNomUrl(-2);

				} else {
					print "<i class='fas fa-share-alt' title='Shared with Everyone'></i>";
				}
			}


			print '<div style="clear:both; margin-top: 5px; padding-top: 5px;border-top: 1px solid rgba(0,0,0,0.1);font-size:90%;">';
			if ($obj->tms) {
				print 'Last updated: '.date("d.m.Y h:ma", strtotime($obj->tms));
			} else {
				print 'Created: '.date("d.m.Y h:ma", strtotime($obj->date_creation));
			}
			print '</div>';


			print '</div>';
			print '</div>';

			// Descripción de la tarea
			print '<div class="fullnotes" style="white-space: pre-wrap">'.$obj->note.'</div>';
			print '<br>';
			print '<hr>';

			// ID de la tarea
			print '<div class="idnote">ID nota: '.$obj->rowid.'</div>';
			print '<hr>';

			// Proyecto
			$sql = "SELECT title FROM ".MAIN_DB_PREFIX."projet ";
			$sql.= "WHERE rowid = ".$obj->fk_project."";
			$resultPro = $db->query($sql);

			if ($db->num_rows($resultPro) > 0) {
				$proj = $db->fetch_object($resultPro);

				print '<div class="proyecto">Proyecto: '.$proj->title.'</div>';
			} else {
				print '<div class="proyecto">Proyecto: Sin proyecto</div>';
			}

			// Fecha
			print '<hr>';
			print '<div class="datecreation">Fecha de creación: '.$obj->date_creation.'</div>';
			print '<hr>';

			// Prioridad
			print '<div class="prioridad">Prioridad: '.$obj->priority.'</div>';

			// Para sacar el nombre de la categoría de la tarea
			$sql = "SELECT name
			FROM ".MAIN_DB_PREFIX."notas_nota_categories c, ".MAIN_DB_PREFIX."notas_nota n
			WHERE c.rowid = n.category
			AND n.rowid = ".$obj->rowid;
			$resultado = $db->query($sql);
			$campo = $db->fetch_object($resultado);
			$nombreCat = $campo->name;

			print '<hr>';
			print '<div class="catnote">Categoría: '.$nombreCat.'</div>';

			// Para sacar el nombre del creador de la tarea
			$sql = "SELECT firstname, lastname
						FROM ".MAIN_DB_PREFIX."user u, ".MAIN_DB_PREFIX."notas_nota n
						WHERE u.rowid = n.fk_user_creat
						AND n.rowid = ".$obj->rowid;
			$resultado = $db->query($sql);
			$campo = $db->fetch_object($resultado);
			$nombreCre = $campo->firstname;
			$nombreCre.= " ".$campo->lastname;

			print '<hr>';
			print '<div class="usercreate">Creada por: '.$nombreCre.'</div>';

			// Para sacar el nombre de los asignados a la tarea
			$sql = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user u
						INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu
						ON u.rowid = nu.iduser
						WHERE nu.idnote = ".$obj->rowid."";

			$resultado = $db->query($sql);

			print '<hr>';
			print '<div class="userassigned">Usuarios asignados: ';

			$usuarios = "";

			while ($campo = $db->fetch_object($resultado)) {

				$nombreAsig = $campo->firstname;
				$nombreAsig.= " ".$campo->lastname;

				if ($usuarios != "") {
					$usuarios.= ", ".$nombreAsig;
				} else {
					$usuarios = $nombreAsig;
				}

			}

			print ''.$usuarios.' ';
			print '</div>';
			print '<hr>';

			// Para el formulario de los comentarios

			print '	<form name="addcomment" id="addcomment" action="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'" method="POST">
			<input type="text" name="comment">
			<input type="submit" name="Insertar" value="Insertar comentario">
			';

			// Para insertar comentario

			if (isset($_POST['Insertar'])) {

				$label = $_POST['comment'];
				$date = date('Y-m-d H:i:s');
				$mod = date('Y-m-d H:i:s');
				$noteid = $obj->rowid;
				$creator = $user->id;

				$db->begin();
				try {

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."notas_nota_comment ";
					$sql.= "(label, date_creation, tms, fk_note, fk_user_creat) ";
					$sql.= "VALUES ('".$label."', '".$date."', '".$mod."', '".$noteid."', '".$creator."')";

					$resulInsert = $db->query($sql);

					if (!$resulInsert) {
						throw new Exception("Error al crear comentario");
					}

				} catch (Exception $e) {

					$db->rollback();
					setEventMessage($e->getMessage(), "errors");

				}

				$db->commit();


			}

			print '<br>';
			print '<br>';
			print '<span class="act_title">ACTIVIDAD</span>';
			print '<hr>';
			print '<br>';
			print '<br>';

			// Para mostrar los comentarios

			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota_comment ";
			$sql.= "WHERE fk_note = '$obj->rowid' ";
			$sql.= "ORDER BY date_creation DESC";

			$resultado = $db->query($sql);

			if ($resultado) {

				while($datos = $db->fetch_object($resultado)) {

					$sql = "SELECT DISTINCT firstname, lastname FROM ".MAIN_DB_PREFIX."user u ";
					$sql.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_comment nc ";
					$sql.= "ON u.rowid = nc.fk_user_creat ";
					$sql.= "WHERE nc.fk_user_creat = ".$datos->fk_user_creat."";

					$resul = $db->query($sql);
					$campo = $db->fetch_object($resul);
					$nombreCre = $campo->firstname;
					$nombreCre.= " ".$campo->lastname;

					print '<div class="comment">';
					print '<span class="creator">'.$nombreCre.'.</span><span class="date_creation">'.$datos->date_creation.'</span><br>';

					print '<input type=text name='.$datos->rowid.' value="'.$datos->label.'">';

					print '<input type="submit" name="Modificar['.$datos->rowid.']" value="Editar">';
					print '<input type="submit" name="Borrar['.$datos->rowid.']" value="Eliminar">';
					print '<input type="hidden" name="idcomment" value='.$datos->rowid.'>';
					print '<br>';

					if ($datos->date_creation != $datos->tms) {
						print '<span class="edited">(Última edición: '.$datos->tms.')</span>';
						print '<br>';
					}

					print '</div>';

				}

			}

			// Para borrar los comentarios

			if (isset($_POST['Borrar'])) {

				$idBorrar = $_POST['Borrar'];

				$db->begin();
				try {

					foreach ($idBorrar as $clave => $valor) {

						$sql = "DELETE FROM ".MAIN_DB_PREFIX."notas_nota_comment ";
						$sql.= "WHERE rowid = ".$clave."";

						$resulBorrar = $db->query($sql);

						if (!$resulBorrar) {
							throw new Exception("Error al borrar comentario");
						}

						$destination_url = 'nota_card.php?id='.$obj->rowid.'';

						print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

					}

				} catch (Exception $e) {

					$db->rollback();
					setEventMessage($e->getMessage(), "errors");

				}

				$db->commit();

			}

			// Para editar los comentarios

			if (isset($_POST['Modificar'])) {

				$idMod = $_POST['Modificar'];


				$db->begin();
				try {

					foreach ($idMod as $clave => $valor) {

						$label = $_POST[$clave];

						$sql = "UPDATE ".MAIN_DB_PREFIX."notas_nota_comment ";
						$sql.= "SET label = '".$label."' ";
						$sql.= "WHERE rowid = ".$clave."";

						$resulMod = $db->query($sql);

						if (!$resulMod) {
							throw new Exception("Error al modificar comentario");
						}

						$destination_url = 'nota_card.php?id='.$obj->rowid.'';

						print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

					}

				} catch (Exception $e) {

					$db->rollback();
					setEventMessage($e->getMessage(), "errors");

				}

				$db->commit();

			}

			print '</form>';

		}
	}

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}

}


// Para obtener las tareas de cada proyecto en el segundo select que aparece al elegir proyecto en cuestión

if ($conf->use_javascript_ajax) {
	print "\n" . "<script type='text/javascript' language='javascript'>";
	print "jQuery(document).ready(function () {
		$('#fk_project').on('input', function() {

			if ($('#fk_project').val() != -1) {
				$('.field_fk_parent').show();
			} else {
				$('.field_fk_parent').hide();
			}

			var idPro = $('#fk_project').val();

			fetch('obtenerTareasAPI.php?id=' + idPro)
				.then(response => response.json())
				.then(data => {
					var parent = $('#fk_parent');

					parent.empty();

					let option1 = document.createElement('option');
					option1.value = -1;
					option1.textContent = 'Ninguna';
					parent.append(option1);

					data.data.forEach(tarea => {
						let option = document.createElement('option');
						option.value = tarea.rowid;
						option.textContent = tarea.label;

						parent.append(option);

					});
			});

		});

		$('#fk_project').trigger('input');
		
	})";
	print "</script>" . "\n";
}

// End of page
llxFooter();
$db->close();
