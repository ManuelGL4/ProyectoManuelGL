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
$form = new Form($db);

llxHeader('', $langs->trans(utf8_encode("Inserci�n Delegaci�n")));

print load_fiche_titre($langs->trans(utf8_encode("Inserci�n Delegaci�n")), '', 'companies');

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$action='';
if(isset($_GET['action'])){
	$action = $_GET['action'];
}

if($action == 'crear'){
    $nombre = '';
    $telef1 = '';
    $telef2 = '';
    $direccion = '';
    $cp = '';
    $localidad = '';
    $provincia = '';
    $pais ='';
    $direccion_material = '';
    $direccion_factura = '';

    if(isset($_POST['btnAdd'])){
         $nombre = $_POST['nombre'];
         $telef1 = $_POST['telef1'];
         $telef2 = $_POST['telef2'];
         $direccion = $_POST['direccion'];
         $cp = $_POST['cp'];
         $localidad = $_POST['localidad'];
         $provincia = $_POST['provincia'];
         $pais = $_POST['country_id'];
         $direccion_material = $_POST['direccion_material'];
         $direccion_factura = $_POST['direccion_factura'];

         $sql = "INSERT INTO ".MAIN_DB_PREFIX."delegacion (nombre, telef1, telef2, direccion, cp, localidad, provincia, pais, direccion_material, direccion_factura";
         $sql.= ") VALUES ('".$nombre ."','".$telef1 . "', '".$telef2 . "','".$direccion . "','".$cp . "','".$localidad . "', '".$provincia . "', '".$pais . "', '".$direccion_material . "', '".$direccion_factura . "')";

         $insertar = $db->query($sql);

		if($insertar && $error==0){
			setEventMessages(utf8_encode("Delegaci�n creada"), null, 'mesgs');
            header('Location: /societe/delegacionindex.php');
            die();
			
		}else{
			setEventMessages(utf8_encode("Error al crear la delegaci�n"), null, 'errors');
		}
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Inserci�n Delegaci�n</title>
    </head>
    <form id='form1'action='"; print $_SERVER['PHP_SELF']."?action=crear' method='post'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
			    <tr>
                    <td>
                        <span class='fieldrequired' >Nombre: </span>
                    </td>
                    <td>
                        <input required name='nombre' id='nombre' value = '' autofocus='autofocus'/>
                    </td>
                </tr>

                <tr>
                    <td>
                        <span>" .utf8_encode("Tel�fono 1: ") . "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef1' id='telef1' value = ''/>
                    </td
		        </tr>
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Tel�fono 2: ") . "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef2' id='telef2' value = ''/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n: ") . "</span>
                    </td>
                    <td>
                        <input type='text' name='direccion' id='direccion'>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class='' >CP: </span>
                    </td>
                    <td>
                        <input type='number' name='cp' id='cp' value = ''/>
                    </td>
		        </tr>

			    <tr>
                    <td>
                        <span class='' >". utf8_encode('Localidad: ') . "</span>
                    </td>
                    <td>
                        <input name='localidad' id='localidad' value = ''/>
                    </td>
                </tr>

			    <tr>
                    <td>
                        <span class='' >Provincia: </span>
                    </td>
                    <td> " . img_picto('', 'state') . "
                        <select name='provincia' class='form-control'>
                            <option value=''>Elige Provincia</option>
                            <option value='�lava/Araba'>" .utf8_encode("�lava") . "/Araba</option>
                            <option value='Albacete'>Albacete</option>
                            <option value='Alicante'>Alicante</option>
                            <option value='Almer�a'>" .utf8_encode("Almer�a") . "</option>
                            <option value='Asturias'>Asturias</option>
                            <option value='�vila'>" .utf8_encode("�vila") . "</option>
                            <option value='Badajoz'>Badajoz</option>
                            <option value='Baleares'>Baleares</option>
                            <option value='Barcelona'>Barcelona</option>
                            <option value='Burgos'>Burgos</option>
                            <option value='C�ceres'>" .utf8_encode("C�ceres") . "</option>
                            <option value='C�diz'>" .utf8_encode("C�diz") . "</option>
                            <option value='Cantabria'>Cantabria</option>
                            <option value='Castell�n'>" .utf8_encode("Castell�n") . "</option>
                            <option value='Ceuta'>Ceuta</option>
                            <option value='Ciudad Real'>Ciudad Real</option>
                            <option value='C�rdoba'>" .utf8_encode("C�rdoba") . "</option>
                            <option value='Cuenca'>Cuenca</option>
                            <option value='Gerona/Girona'>Gerona/Girona</option>
                            <option value='Granada'>Granada</option>
                            <option value='Guadalajara'>Guadalajara</option>
                            <option value='Guip�zcoa/Gipuzkoa'>" .utf8_encode("Guip�zcoa/Guipuzkoa") . "</option>
                            <option value='Huelva'>Huelva</option>
                            <option value='Huesca'>Huesca</option>
                            <option value='Ja�n'>" .utf8_encode("Ja�n") . "</option>
                            <option value='" .utf8_encode("La Coru�a") . "</option>
                            <option value='La Rioja'>La Rioja</option>
                            <option value='Las Palmas'>Las Palmas</option>
                            <option value='Le�n'>" .utf8_encode("Le�n") . "</option>
                            <option value='L�rida/Lleida'>" .utf8_encode("�L�rida") . "/Lleida</option>
                            <option value='Lugo'>Lugo</option>
                            <option value='Madrid'>Madrid</option>
                            <option value='M�laga'>" .utf8_encode("M�laga") . "</option>
                            <option value='Melilla'>Melilla</option>
                            <option value='Murcia'>Murcia</option>
                            <option value='Navarra'>Navarra</option>
                            <option value='Orense/Ourense'>Orense/Ourense</option>
                            <option value='Palencia'>Palencia</option>
                            <option value='Pontevedra'>Pontevedra</option>
                            <option value='Salamanca'>Salamanca</option>
                            <option value='Segovia'>Segovia</option>
                            <option value='Sevilla'>Sevilla</option>
                            <option value='Soria'>Soria</option>
                            <option value='Tarragona'>Tarragona</option>
                            <option value='Tenerife'>Tenerife</option>
                            <option value='Teruel'>Teruel</option>
                            <option value='Toledo'>Toledo</option>
                            <option value='Valencia'>Valencia</option>
                            <option value='Valladolid'>Valladolid</option>
                            <option value='Vizcaya/Bizkaia'>Vizcaya/Bizkaia</option>
                            <option value='Zamora'>Zamora</option>
                            <option value='Zaragoza'>Zaragoza</option>
                          </select>
                    </td>
                </tr>";

                print '<tr><td width="25%">' . $langs->trans('Country') . '</td><td>';
				print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);

                print "
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n Env�o Material: ") . "</span>
                    </td>
                    <td>
                        <textarea style='resize:none;width: 400px;' type='text' name='direccion_material' id='direccion_material'></textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n Env�o Factura: ") . "</span>
                    </td>
                    <td>
                        <textarea style='resize:none;width: 400px;' type='text' name='direccion_factura' id='direccion_factura'></textarea>
                    </td>
		        </tr>
    
		    </tbody>
	    </table>
    </div>
    <div>
    <div>
        <input type='submit' class='button' name='btnAdd' value='" .utf8_encode("A�adir") . "'>
        <input type='reset' class='button' name='btnAn' value='Anular' >
    </div>
    </div>
    </form>
    ";
}

if(isset($_POST['modificar'])){
         $nombre = $_POST['nombre'];
         $telef1 = $_POST['telef1'];
         $telef2 = $_POST['telef2'];
         $direccion = $_POST['direccion'];
         $cp = $_POST['cp'];
         $localidad = $_POST['localidad'];
         $provincia = $_POST['provincia'];
         $pais =$_POST['country_id'];
         $direccion_material = $_POST['direccion_material'];
         $direccion_factura = $_POST['direccion_factura'];
	$sql = "UPDATE  ".MAIN_DB_PREFIX."delegacion SET
	    nombre='".$nombre."'
        , telef1='".$telef1."'
        , telef2='".$telef2."'
        , direccion='".$direccion."'
        , cp='".$cp."'
        , localidad='".$localidad."'
        , provincia='". utf8_encode($provincia)."'
        , pais='". utf8_encode($pais)."'
        , direccion_material='".$direccion_material."'
        , direccion_factura='".$direccion_factura."'";

	$sql.="  WHERE id=".$_GET['id'];
	$update = $db->query($sql);
	if($update && $error==0){
		setEventMessages(utf8_encode("Delegaci�n ") . 'modificada', null, 'mesgs');
        header('Location: /societe/delegacionindex.php');
        die();
	}else{
        echo $sql;
		setEventMessages( 'Error al modificar la Delegaci�n', null, 'errors');
	}
}



if($action=='editar'){

    $nombre = '';
    $telef1 = '';
    $telef2 = '';
    $direccion = '';
    $cp = '';
    $localidad = '';
    $provincia = '';
    $pais = '';
    $direccion_material = '';
    $direccion_factura = '';

	if(isset($_GET['id'])){
		$id = $_GET['id'];
		$error=0;
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegacion WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);
		$nombre = $obj->nombre;
		$telef1 = $obj->telef1;
		$telef2 = $obj->telef2;
		$direccion = $obj->direccion;
		$cp = $obj->cp;
		$localidad = $obj->localidad;
		$provincia = $obj->provincia;
        $pais = $obj->pais;
		$direccion_material = $obj->direccion_material;
		$direccion_factura = $obj->direccion_factura;
	}

	print "
	<form id='form1'action='"; print $_SERVER['PHP_SELF']."?action=editar&id="; print $id."' method='post'>
	<div  class='tabBar tabBarWithBottom' >

		<table class='border centpercent'>
		    <tbody>
			    <tr>
                    <td>
                        <span class='fieldrequired' >Nombre: </span>
                    </td>
                    <td>
                        <input required name='nombre' id='nombre' value = '";print $nombre."' autofocus='autofocus'/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>" .utf8_encode("Tel�fono 1: ") . "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef1' id='telef1' value = '";print $telef1."'/>
                    </td
		        </tr>
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Tel�fono 2: ") . "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef2' id='telef2' value = '";print $telef2."'/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n: ") . "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none; width: 400px;' type='text' name='direccion' id='direccion'>"; print $direccion . "</textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class='' >CP: </span>
                    </td>
                    <td>
                        <input type='number' name='cp' id='cp' value = '";print $cp."'/>
                    </td>
		        </tr>

			    <tr>
                    <td>
                        <span class='' >Localidad: </span>
                    </td>
                    <td>
                        <input name='localidad' id='localidad' value = '";print $localidad."'/>
                    </td>
                </tr>

			    <tr>
                    <td>
                        <span class='' >Provincia: </span>
                    </td>
                    <td> " . img_picto('', 'state') . "
                        <select name='provincia' class='form-control'>
                            <option value= '";print $provincia."'>"; print $provincia . "</option>
                            <option value=''>Elige Provincia</option>
                            <option value='�lava/Araba'>" .utf8_encode("�lava") . "/Araba</option>
                            <option value='Albacete'>Albacete</option>
                            <option value='Alicante'>Alicante</option>
                            <option value='Almer�a'>" .utf8_encode("Almer�a") . "</option>
                            <option value='Asturias'>Asturias</option>
                            <option value='�vila'>" .utf8_encode("�vila") . "</option>
                            <option value='Badajoz'>Badajoz</option>
                            <option value='Baleares'>Baleares</option>
                            <option value='Barcelona'>Barcelona</option>
                            <option value='Burgos'>Burgos</option>
                            <option value='C�ceres'>" .utf8_encode("C�ceres") . "</option>
                            <option value='C�diz'>" .utf8_encode("C�diz") . "</option>
                            <option value='Cantabria'>Cantabria</option>
                            <option value='Castell�n'>" .utf8_encode("Castell�n") . "</option>
                            <option value='Ceuta'>Ceuta</option>
                            <option value='Ciudad Real'>Ciudad Real</option>
                            <option value='C�rdoba'>" .utf8_encode("C�rdoba") . "</option>
                            <option value='Cuenca'>Cuenca</option>
                            <option value='Gerona/Girona'>Gerona/Girona</option>
                            <option value='Granada'>Granada</option>
                            <option value='Guadalajara'>Guadalajara</option>
                            <option value='Guip�zcoa/Gipuzkoa'>" .utf8_encode("Guip�zcoa/Guipuzkoa") . "</option>
                            <option value='Huelva'>Huelva</option>
                            <option value='Huesca'>Huesca</option>
                            <option value='Ja�n'>" .utf8_encode("Ja�n") . "</option>
                            <option value='" .utf8_encode("La Coru�a") . "</option>
                            <option value='La Rioja'>La Rioja</option>
                            <option value='Las Palmas'>Las Palmas</option>
                            <option value='Le�n'>" .utf8_encode("Le�n") . "</option>
                            <option value='L�rida/Lleida'>" .utf8_encode("�L�rida") . "/Lleida</option>
                            <option value='Lugo'>Lugo</option>
                            <option value='Madrid'>Madrid</option>
                            <option value='M�laga'>" .utf8_encode("M�laga") . "</option>
                            <option value='Melilla'>Melilla</option>
                            <option value='Murcia'>Murcia</option>
                            <option value='Navarra'>Navarra</option>
                            <option value='Orense/Ourense'>Orense/Ourense</option>
                            <option value='Palencia'>Palencia</option>
                            <option value='Pontevedra'>Pontevedra</option>
                            <option value='Salamanca'>Salamanca</option>
                            <option value='Segovia'>Segovia</option>
                            <option value='Sevilla'>Sevilla</option>
                            <option value='Soria'>Soria</option>
                            <option value='Tarragona'>Tarragona</option>
                            <option value='Tenerife'>Tenerife</option>
                            <option value='Teruel'>Teruel</option>
                            <option value='Toledo'>Toledo</option>
                            <option value='Valencia'>Valencia</option>
                            <option value='Valladolid'>Valladolid</option>
                            <option value='Vizcaya/Bizkaia'>Vizcaya/Bizkaia</option>
                            <option value='Zamora'>Zamora</option>
                            <option value='Zaragoza'>Zaragoza</option>
                          </select>
                    </td>
                </tr>
                ";

				print '<tr><td width="25%">' . $langs->trans('Country') . '</td><td>';
				print $form->select_country($pais);
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);

                print "
                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n Env�o Material: ") . "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none;width: 400px;' type='text' name='direccion_material' id='direccion_material'>"; print $direccion_material . "</textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class='' >" .utf8_encode("Direcci�n Env�o Factura: ") . "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none;width: 400px;' type='text' name='direccion_factura' id='direccion_factura'>"; print $direccion_factura . "</textarea>
                    </td>
		        </tr>
    
		    </tbody>
	    </table>
    </div>
    <div>
		<div class='center'><input type='submit' class='button' name='modificar' value='Modificar' id='btnModal'></div>
    </div>
    </form>

	";
}

ob_end_flush();
// End of page
llxFooter();
$db->close();
ob_flush();