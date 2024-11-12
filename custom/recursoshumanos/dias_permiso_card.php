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
 *   	\file       dias_permiso_card.php
 *		\ingroup    recursoshumanos
 *		\brief      Page to create/edit/view dias_permiso
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
dol_include_once('/recursoshumanos/class/dias_permiso.class.php');
dol_include_once('/recursoshumanos/lib/recursoshumanos_dias_permiso.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("recursoshumanos@recursoshumanos", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'dias_permisocard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Dias_permiso($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->recursoshumanos->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('dias_permisocard', 'globalcard')); // Note that conf->hooks_modules contains array

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


$permissiontoread = $user->rights->recursoshumanos->dias_permiso->read;
$permissiontoadd = $user->rights->recursoshumanos->dias_permiso->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->recursoshumanos->dias_permiso->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->recursoshumanos->dias_permiso->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->recursoshumanos->dias_permiso->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->recursoshumanos->multidir_output[isset($object->entity) ? $object->entity : 1].'/dias_permiso';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->recursoshumanos->enabled)) accessforbidden();
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

	$backurlforlist = dol_buildpath('/recursoshumanos/dias_permiso_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/recursoshumanos/dias_permiso_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'RECURSOSHUMANOS_DIAS_PERMISO_MODIFY'; // Name of trigger action code to execute when we modify record

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
	$triggersendname = 'RECURSOSHUMANOS_DIAS_PERMISO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_DIAS_PERMISO_TO';
	$trackid = 'dias_permiso'.$object->id;
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

$title = $langs->trans("Dias_permiso");
$help_url = '';
llxHeader('', $title, $help_url);



// Part to create
if ($action == 'create') {
	print '<script>
    function closeModal() {
        document.querySelector("div[style*=fixed]").style.display = "none";
    }
    
    function confirmEdit() {
        document.getElementById("confirmForm").submit();
    }
    </script>';

	print load_fiche_titre($langs->trans("Solicitud de dias de permiso"), '', 'object_'.$object->picto);

	print '	<form id="form1"action="" method="post">';
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
    $fecha_solicitada = isset($_POST['fecha_solicitada']) ? $_POST['fecha_solicitada'] : '';
    $fecha_solicitada_fin = isset($_POST['fecha_solicitada_fin']) ? $_POST['fecha_solicitada_fin'] : '';
    $usuario_id = isset($_POST['usuario']) ? intval($_POST['usuario']) : -1;
    $admin_id = isset($_POST['admin']) ? intval($_POST['admin']) : -1;
    $descripcion = isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '';

	print "
			<tbody>
				
				<tr>
					<td>
						<span class='fieldrequired'>Fechas solicitada</span>
					</td>
					<td>
                    	<input type='datetime-local' name='fecha_solicitada' value='" . htmlspecialchars($fecha_solicitada) . "'>
						a
						<input type='datetime-local' name='fecha_solicitada_fin' value='" . htmlspecialchars($fecha_solicitada_fin) . "'>
                	</td>
					</tr>
";
print "
					<td>
						<span class='fieldrequired'>Usuario que solicita el dia</span>
					</td>
										<td>";


										print $form->select_dolusers($user->id, 'usuario', 1, '', 0);
										if (!$user->admin) {
											// Si el usuario no es administrador, deshabilitar el campo
											echo '<script>document.getElementsByName("usuario")[0].disabled = true;</script>';
										}
						print "
					</td>
					</tr>";

					print "
					<td>
						<span class='fieldrequired'>Administrador validador</span>
					</td>
					<td>
							";
							echo $form->select_dolusers('', 'admin', 1, '', 0, '', '','','','','AND admin=1');
							print "
					</td>
					</tr>";

					print "<tr>
					<td>
					<span class='fieldrequired'>Descripcion de la solicitud</span>
					</td>
					<td>
						<textarea name='descripcion' id='descripcion' class='required'  style='height: 168px; width: 625px;'> " . htmlspecialchars($descripcion) . "</textarea>
					</td>
					</tr>";

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print "
</table>
</div>
</div>
<div class='center'>
<input type='submit' class='button' name='guardar' value='Solicitar' id='btnModal'>
<input type='button' class='button button-cancel'  value='Volver al listado' onclick='javascript:history.go(-".$back.")'>
";

	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

if (isset($_POST['guardar'])) {
    $fecha = isset($_POST['fecha_solicitada']) ? $_POST['fecha_solicitada'] : 'Fecha no especificada';
    $fecha_fin = isset($_POST['fecha_solicitada_fin']) ? $_POST['fecha_solicitada_fin'] : 'Fecha no especificada';
    $usuario_id = isset($_POST['usuario']) ? $_POST['usuario'] : null;
    $admin_id = isset($_POST['admin']) ? $_POST['admin'] : null;

    $usuario_nombre = 'Usuario no especificado';
    $admin_nombre = 'Administrador no especificado';

    if ($usuario_id) {
        $consulta_usuario = "SELECT firstname, lastname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . intval($usuario_id);
        $resultado_usuario = $db->query($consulta_usuario);

        if ($resultado_usuario && $db->num_rows($resultado_usuario) > 0) {
            $usuario_data = $db->fetch_object($resultado_usuario);
            $usuario_nombre = htmlspecialchars($usuario_data->firstname . ' ' . $usuario_data->lastname);
        }
    }

    if ($admin_id) {
        $consulta_admin = "SELECT firstname, lastname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . intval($admin_id);
        $resultado_admin = $db->query($consulta_admin);

        if ($resultado_admin && $db->num_rows($resultado_admin) > 0) {
            $admin_data = $db->fetch_object($resultado_admin);
            $admin_nombre = htmlspecialchars($admin_data->firstname . ' ' . $admin_data->lastname);
        }
    }

    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : 'Descripción no especificada';
	$fecha_formateada = ($fecha && $fecha !== 'Fecha no especificada') ? (new DateTime($fecha))->format('d/m/Y H:i') : '';
	$fecha_fin_formateada = ($fecha_fin && $fecha_fin !== 'Fecha fin no especificada') ? (new DateTime($fecha_fin))->format('d/m/Y H:i') : '';
	

    print '<div style="position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div style="background: white; margin: 15% auto; padding: 20px; border-radius: 5px; width: 400px; text-align: center;">
            <h3>Confirmar Acción</h3>
            <p>Está seguro de que desea solicitar el día con la siguiente información:</p>
            <p><strong>Fecha solicitada:</strong> ' . htmlspecialchars($fecha_formateada) . ' <strong>a</strong> ' . htmlspecialchars($fecha_fin_formateada) . '</p>
            <p><strong>Usuario solicitante:</strong> ' . $usuario_nombre . '</p>
            <p><strong>Administrador validador:</strong> ' . $admin_nombre . '</p>
            <p><strong>Descripción:</strong> ' . htmlspecialchars($descripcion) . '</p>
            <form id="confirmForm" method="post" action="">
                <input type="hidden" name="confirmForm" value="1">
                <input type="hidden" name="fecha_solicitada" value="' . htmlspecialchars($fecha) . '">
                <input type="hidden" name="fecha_solicitada_fin" value="' . htmlspecialchars($fecha_fin) . '">
                <input type="hidden" name="usuario" value="' . intval($usuario_id) . '">
                <input type="hidden" name="admin" value="' . intval($admin_nombre) . '">
                <input type="hidden" name="descripcion" value="' . htmlspecialchars($descripcion) . '">
                
                <br><br>
                <input type="submit" class="button button-confirm" value="Sí">
                <button type="button" id="cancelReset" class="button button-cancel" onclick="closeModal()">No</button>
            </form>
        </div>
    </div>';
}



if (isset($_POST['confirmForm'])) {
    // Recoger los datos enviados y validar que no estén vacíos
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $usuario_id = isset($_POST['usuario']) ? intval($_POST['usuario']) : 0;
    $fecha_solicitada = isset($_POST['fecha_solicitada']) ? trim($_POST['fecha_solicitada']) : '';
    $fecha_solicitada_fin = isset($_POST['fecha_solicitada_fin']) ? trim($_POST['fecha_solicitada_fin']) : '';

    // Validar que todos los campos requeridos están completos
    if ($descripcion && $usuario_id && $fecha_solicitada && $fecha_solicitada_fin) {
        // Establecer valores fijos y convertir formato de fecha si es necesario
        $status = 0; // Status fijo en 0
        $date_creation = date("Y-m-d H:i:s"); // Fecha actual para la creación

        // Construir la consulta de inserción
        $consulta = 'INSERT INTO ' . MAIN_DB_PREFIX . 'recursoshumanos_dias_permiso 
            (label, date_creation, fk_user_creat, fk_user_modif, status, fk_user_solicitado, date_solic, date_solic_fin) VALUES 
            ("' . $db->escape($descripcion) . '", 
            "' . $date_creation . '", 
            ' . $usuario_id . ', 
            ' . $usuario_id . ', 
            ' . $status . ', 
            ' . $usuario_id . ', 
            "' . $db->escape($fecha_solicitada) . '", 
            "' . $db->escape($fecha_solicitada_fin) . '")';

        // Ejecutar la consulta
        if ($db->query($consulta)) {
			print '<div style="color: green; text-align: center; margin-top: 20px;">';
			print '<strong>Datos Actualizados:</strong> Solicitud creada correctamente.<br>';
        } else {
			print '<div style="color: red; text-align: center; margin-top: 20px;">';
			print '<strong>Datos Actualizados:</strong>Error al intentar solicitar la fecha, por favor, inténtelo de nuevo.<br>';
        }
    } else {
        // Mensaje de error si algún campo está vacío
        print '<div style="color: red;text-align: center;margin-top: 20px;"><strong>Error: Todos los campos son obligatorios. Por favor, complete toda la información.</div></strong>';
    }
}






// Parte para editar el registro
if (($id || $ref) && $action == 'edit') {

    // Si tienes $id, realizas una consulta a la base de datos para obtener el registro
    if ($id > 0) {
        $sql = "SELECT * FROM khns_recursoshumanos_dias_permiso WHERE rowid = ".$db->escape($id);
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $object = $db->fetch_object($resql);  // Aquí obtenemos el objeto del registro

            // Ahora asignamos los valores del objeto a las variables
            $fecha_solicitada = $object->date_solic;
            $fecha_solicitada_fin = $object->date_solic_fin;
            $usuario_id = $object->fk_user_solicitado;
            $admin_id = $object->admin;
            $descripcion = $object->label;
            $estado = $object->status;
            $motivos = $object->motivos; // Suponiendo que "motivos" es el campo en la base de datos
        } 
    }

    // Mostrar el formulario de edición con los valores recuperados
    print load_fiche_titre($langs->trans("Edicion de registro"), '', 'object_'.$object->picto);

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

    // Mostrar valores específicos del registro
    print "
        <tr>
            <td><span class='fieldrequired'>Fechas solicitada</span></td>
            <td>
                <input type='datetime-local' name='fecha_solicitada' value='" . htmlspecialchars($fecha_solicitada) . "'>
                a
                <input type='datetime-local' name='fecha_solicitada_fin' value='" . htmlspecialchars($fecha_solicitada_fin) . "'>
            </td>
        </tr>
        <tr>
            <td><span class='fieldrequired'>Usuario que solicita el dia</span></td>
            <td>";
    // Se selecciona el usuario
    print $form->select_dolusers($usuario_id, 'usuario', 1, '', 0);
    if (!$user->admin) {
        // Si el usuario no es administrador, deshabilitar el campo
        echo '<script>document.getElementsByName("usuario")[0].disabled = true;</script>';
    }
    print "</td></tr>";

    print "
        <tr>
            <td><span class='fieldrequired'>Administrador validador</span></td>
            <td>";
    // Se selecciona el administrador
    print $form->select_dolusers($admin_id, 'admin', 1, '', 0, '', '','','','','AND admin=1');
    print "</td></tr>";

    print "
        <tr>
            <td><span class='fieldrequired'>Descripcion de la solicitud</span></td>
            <td>
                <textarea name='descripcion' id='descripcion' class='required' style='height: 168px; width: 625px;'>".htmlspecialchars($descripcion)."</textarea>
            </td>
        </tr>";

    // Estado: 0 - Pendiente, 1 - Aprobada, 9 - Rechazada
    print "
        <tr>
            <td><span class='field'>Estado</span></td>
            <td>
                <select name='estado' id='estado' onchange='mostrarMotivos()'>
                    <option value='0'".($estado == 0 ? " selected" : "").">Pendiente</option>
                    <option value='1'".($estado == 1 ? " selected" : "").">Aprobada</option>
                    <option value='9'".($estado == 9 ? " selected" : "").">Rechazada</option>
                </select>
            </td>
        </tr>";

    // Campo de Motivos (solo se muestra si el estado es Aprobada o Rechazada)
    print "
        <tr id='motivos-row' style='display: ".($estado == 1 || $estado == 9 ? "table-row" : "none").";'>
            <td><span class='field'>Motivos</span></td>
            <td>
                <textarea name='motivos' id='motivos' style='height: 168px; width: 625px;'>".htmlspecialchars($motivos)."</textarea>
            </td>
        </tr>";

    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" name="edit" value="'.$langs->trans("Edit").'">';
    print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';


// Agregar el JavaScript para mostrar/ocultar el campo de motivos dinámicamente
print '
<script>
function mostrarMotivos() {
    var estado = document.getElementById("estado").value;
    var motivosRow = document.getElementById("motivos-row");
    if (estado == "1" || estado == "9") {
        motivosRow.style.display = "table-row";  // Muestra el campo de motivos
    } else {
        motivosRow.style.display = "none";  // Oculta el campo de motivos
    }
}

// Llamamos a la función al cargar la página para asegurarnos de que el campo de motivos se muestra correctamente al principio
document.addEventListener("DOMContentLoaded", function() {
    mostrarMotivos();
});
</script>';
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = dias_permisoPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDias_permiso'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="'.dol_buildpath('/recursoshumanos/dias_permiso_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled)) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
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
			if (empty($user->socid)) {
				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken(), '', $permissiontoadd);

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction($langs->trans('Disable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Enable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction($langs->trans('Cancel'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Re-Open'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
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

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->recursoshumanos->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->rights->recursoshumanos->dias_permiso->read; // If you can read, you can build the PDF to read content
			$delallowed = $user->rights->recursoshumanos->dias_permiso->write; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('recursoshumanos:Dias_permiso', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('dias_permiso'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/recursoshumanos/dias_permiso_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'dias_permiso';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->recursoshumanos->dir_output;
	$trackid = 'dias_permiso'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
