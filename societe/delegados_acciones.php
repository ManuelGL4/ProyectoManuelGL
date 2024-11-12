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

require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'other', 'commercial'));

$mesg = ''; $error = 0; $errors = array();

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');

/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new Adherent($db);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$action='';

if(isset($_GET['action'])){
	$action = $_GET['action'];
}

if($action == 'crear'){

    llxHeader('', $langs->trans(utf8_encode("Inserción Delegado")));

    print load_fiche_titre($langs->trans("Inserción Delegado"), '', 'companies');

    if(isset($_POST['btnAdd'])){
         $nombre = $_POST['nombre'];
         $apellidos = $_POST['apellidos'];
         $direccion = $_POST['direccion'];
         $email = $_POST['email'];
         $telefono = $_POST['telefono'];
         $zip = $_POST['zipcode'];
         $pais = $_POST['country_id'];
         $poblacion = $_POST['town'];
         $provincia = $_POST['state_id'];
         $ruta = $_POST['ruta'];

         $sql = "INSERT INTO ".MAIN_DB_PREFIX."delegados (nombre,apellidos,telefono,email,direccion,cod_postal,poblacion,provincia,pais,ruta)";
         $sql.= " VALUES ('".$nombre."','".$apellidos."','".$telefono."','".$email."','".$direccion."','".$zip."','".$poblacion."',".$provincia.",".$pais.",".$ruta.")";

         $insertar = $db->query($sql);

		if($insertar && $error==0){
			setEventMessages( 'Delegado creado', null, 'mesgs');
            header('Location: delegados.php');
            die();
		}else{
            echo $sql;
			setEventMessages( 'Error al crear el delegado', null, 'errors');
		}
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Inserción Delegado</title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
                ";

                // Firstname
				print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="nombre" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : $object->firstname) . '"></td>';
				print '</tr>';

                // Lastname
				print '<tr><td class="fieldrequired" id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="apellidos" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : $object->lastname) . '"></td>';
				print '</tr>';

                // Address
				print '<tr><td class="fieldrequired">' . $langs->trans("Address") . '</td><td>';
				print '<textarea name="direccion" wrap="soft" required class="quatrevingtpercent" rows="2">' . (GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : $object->address) . '</textarea>';
				print '</td></tr>';

                // Email
				print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Email") . '</td><td><input type="email" name="email" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('email') ? GETPOST('email', 'alphanohtml') : $object->email) . '"></td>';
				print '</tr>';

                // Phone
				print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Phone") . '</td><td><input type="tel" name="telefono" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alphanohtml') : $object->phone) . '"></td>';
				print '</tr>';

                // Zip / Town
				print '<tr><td class="fieldrequired">' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
				print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
				print ' ';
				print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
				print '</td></tr>';
				// Country
				$object->country_id = $object->country_id ? $object->country_id : $mysoc->country_id;
				print '<tr><td class="fieldrequired" width="25%">' . $langs->trans('Country') . '</td><td>';
				print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				print '</td></tr>';

				// State
				if (empty($conf->global->MEMBER_DISABLE_STATE)) {
					print '<tr><td class="fieldrequired">' . $langs->trans('State') . '</td><td>';
					if ($object->country_id) {
						print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
					} else {
						print $countrynotdefined;
					}
					print '</td></tr>';
				}

                print "

                <tr>
					<td>
						<span class='fieldrequired'>Ruta</span>
					</td>
					<td>
						<select name='ruta'>";
						$sql = 'SELECT *  FROM '. MAIN_DB_PREFIX . 'ruta';
						$result = $db->query($sql);
						$num = $db->num_rows($result);
						while ($num > 0) {
							$datos = $db->fetch_object($result);
							echo "<option class='optiongrey' value='" . $datos->id. "'";
							// if ($actividad == $datos->nombre) {
							// 	echo " selected";
							// }
							echo " >" . $datos->name . " </option> ";
							$num --;

						}
						print "
								</select>
					</td>
				</tr>

		    </tbody>
	    </table>
    </div>
    <div>
        <input type='submit' class='button' name='btnAdd' value='Añadir'>
        <a href='delegados.php' class='button'>Anular</a>
    </div>
    </form>
    ";
}

if(isset($_POST['modificar'])){

    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $direccion = $_POST['direccion'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $zip = $_POST['zipcode'];
    $pais = $_POST['country_id'];
    $poblacion = $_POST['town'];
    $provincia = $_POST['state_id'];
    $ruta = $_POST['ruta'];

    $sql = 'UPDATE '.MAIN_DB_PREFIX.'delegados SET
            nombre="'.$nombre.'",apellidos="'.$apellidos.'",direccion="'.$direccion.'",email="'.$email.'",
            telefono="'.$telefono.'",cod_postal="'.$zip.'",pais='.$pais.',poblacion="'.$poblacion.'",
            provincia='.$provincia.',ruta='.$ruta.'';
    
    $sql.="  WHERE id=".$_GET['id'];

    $update = $db->query($sql);
	if($update && $error==0){
		    setEventMessages( 'Delegado modificado', null, 'mesgs');
            header('Location: delegados.php');
            die();
	    }else{
		    setEventMessages( 'Error al modificar el delegado', null, 'errors');
	    }
}

if($action == 'editar'){
    llxHeader('', $langs->trans(utf8_encode("Edición Delegado")));

    print load_fiche_titre($langs->trans("Edición Delegado"), '', 'companies');

    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $error=0;
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegados WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);

		$object->firstname = $obj->nombre;
        $object->lastname = $obj->apellidos;
        $object->address = $obj->direccion;
        $object->email = $obj->email;
        $object->phone = $obj->telefono;
        $object->zip = $obj->cod_postal;
        $object->town = $obj->poblacion;
        $object->country_id = $obj->pais;
        $object->state_id=$obj->provincia;
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Edición Delegado</title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>";
                // Firstname
                print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="nombre" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : $object->firstname) . '"></td>';
                print '</tr>';

                // Lastname
                print '<tr><td class="fieldrequired" id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="apellidos" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : $object->lastname) . '"></td>';
                print '</tr>';

                // Address
                print '<tr><td class="fieldrequired">' . $langs->trans("Address") . '</td><td>';
                print '<textarea name="direccion" wrap="soft" required class="quatrevingtpercent" rows="2">' . (GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : $object->address) . '</textarea>';
                print '</td></tr>';

                // Email
                print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Email") . '</td><td><input type="email" name="email" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('email') ? GETPOST('email', 'alphanohtml') : $object->email) . '"></td>';
                print '</tr>';

                // Phone
                print '<tr><td class="fieldrequired" id="tdfirstname">' . $langs->trans("Phone") . '</td><td><input type="tel" name="telefono" required class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alphanohtml') : $object->phone) . '"></td>';
                print '</tr>';

                // Zip / Town
                print '<tr><td class="fieldrequired">' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
                print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
                print ' ';
                print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
                print '</td></tr>';
                // Country
                $object->country_id = $object->country_id ? $object->country_id : $mysoc->country_id;
                print '<tr><td class="fieldrequired" width="25%">' . $langs->trans('Country') . '</td><td>';
                print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
                if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
                print '</td></tr>';

                // State
                if (empty($conf->global->MEMBER_DISABLE_STATE)) {
                    print '<tr><td class="fieldrequired">' . $langs->trans('State') . '</td><td>';
                    if ($object->country_id) {
                        print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
                    } else {
                        print $countrynotdefined;
                    }
                    print '</td></tr>';
                }

                print "

                <tr>
					<td>
						<span class='fieldrequired'>Ruta</span>
					</td>
					<td>
						<select name='ruta'>";
						$sql = 'SELECT *  FROM '. MAIN_DB_PREFIX . 'ruta';
						$result = $db->query($sql);
						$num = $db->num_rows($result);
						while ($num > 0) {
							$datos = $db->fetch_object($result);
							echo "<option class='optiongrey' value='" . $datos->id. "'";
							if ($obj->ruta == $datos->id) {
								echo " selected";
							}
							echo " >" . $datos->name . " </option> ";
							$num --;

						}
                        print "
                    </td>
                </tr>
		    </tbody>
	    </table>
    </div>
    <div>
        <div class='center'><input type='submit' class='button' name='modificar' value='Modificar' id='btnModal'><a href='delegados.php' class='button'>Anular</a></div>
    </div>
    </form>
    ";
}


ob_end_flush();
// End of page
llxFooter();
$db->close();
//ob_flush();