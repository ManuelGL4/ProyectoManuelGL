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
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Josep Lluís Amador      <joseplluis@lliuretic.cat>
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

llxHeader('', $langs->trans(utf8_encode("Inserción Licitador")));

print load_fiche_titre($langs->trans(utf8_encode("Inserción Licitador")), '', 'companies');

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$action='';
if(isset($_GET['action'])){
	$action = $_GET['action'];
}

if($action == 'crear'){
    $representante ='';
    $nombre = '';
    $descripcion = '';
    $clasificacion = '';
    $fecha_inicio = '';
    $fecha_fin = '';
    $observaciones = '';
    $abjudicatario = '';

    if(isset($_POST['btnAdd'])){
         $representante = $_POST['representante'];
         $nombre = $_POST['nombre'];
         $descripcion = $_POST['descripcion'];
         $clasificacion = $_POST['clasificacion'];
         $fecha_inicio = $_POST['fecha_inicio'];
         $fecha_fin = $_POST['fecha_fin'];
         $observaciones = $_POST['observaciones'];
         $abjudicatario = $_POST['abjudicatario'];

         $sql = "INSERT INTO khns_licitador (representante, nombre, descripcion, clasificacion, fecha_inicio, fecha_fin, observaciones, abjudicatario";
         $sql.= ") VALUES ('".$representante . "', '".$nombre . "', '".$descripcion . "','".$clasificacion . "','".$fecha_inicio . "','".$fecha_fin . "', '".$observaciones . "', '".$abjudicatario . "')";

         $insertar = $db->query($sql);

		if($insertar && $error==0){
			setEventMessages('Licitador creado', null, 'mesgs');
            header('Location: /projet/licitadorlista.php');
            die();
			
		}else{
            echo $sql;
			setEventMessages( 'Error al crear el licitador', null, 'errors');
		}
    }


    print "
    <head>
        <meta charset='UTF-8'>
        <title>Inserción Licitador</title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
                <tr>
                    <td>
                        <span class= 'fieldrequired'>Representante: </span>
                    </td>
                    <td>
                         <input required type='text' name='representante' maxlength='200' id='representante' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class= 'fieldrequired'>Nombre: </span>
                    </td>
                    <td>
                         <input required type='text' name='nombre' maxlength='200' id='nombre' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>" . utf8_encode('Descripción:') . "</span>
                    </td>
                    <td>
                         <textarea style='width:400px; height:50px;' type='text' name='descripcion' maxlength='400' id='descripcion' value = ''/></textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>" . utf8_encode('Clasificación:') . "</span>
                    </td>
                    <td>
                         <input type='text' name='clasificacion' maxlength='200' id='clasificacion' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Fecha Inicio: </span>
                    </td>
                    <td>
                         <input type='date' name='fecha_inicio' id='fecha_inicio' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Fecha Fin: </span>
                    </td>
                    <td>
                         <input type='date' name='fecha_fin' id='fecha_fin' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Observaciones: </span>
                    </td>
                    <td>
                         <textarea style='width:400px; height:50px;' type='text' name='observaciones' maxlength='400' id='observaciones' value = ''/></textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Abjudicatario: </span>
                    </td>
                    <td>
                         <select name='abjudicatario'>
                              <option value='". utf8_encode('Sí') . "'>" . utf8_encode('Sí') . "</option>
                              <option value='No' selected>No</option>
                         </select>
                    </td>
		        </tr>
		    </tbody>
	    </table>
    </div>
    <div>
        <input type='submit' class='button' name='btnAdd' value='" .utf8_encode("Añadir") . "'>
        <input type='reset' class='button' name='btnAn' value='Anular' >
    </div>
    </form>
    ";
}

if(isset($_POST['modificar'])){
         $representante = $_POST['representante'];
         $nombre = $_POST['nombre'];
         $descripcion = $_POST['descripcion'];
         $clasificacion = $_POST['clasificacion'];
         $fecha_inicio = $_POST['fecha_inicio'];
         $fecha_fin = $_POST['fecha_fin'];
         $observaciones = $_POST['observaciones'];
         $abjudicatario = $_POST['abjudicatario'];
	$sql = "UPDATE  khns_licitador SET
        representante='".$representante."'
	    , nombre='".$nombre."'
        , descripcion='".$descripcion."'
        , clasificacion='".$clasificacion."'
        , fecha_inicio='".$fecha_inicio."'
        , fecha_fin='".$fecha_fin."'
        , observaciones='".$observaciones."'
        , abjudicatario='". $abjudicatario."'";

	$sql.="  WHERE id=".$_GET['id'];
	$update = $db->query($sql);
	if($update && $error==0){
		setEventMessages( 'Licitador modificado', null, 'mesgs');
        header('Location: /projet/licitadorlista.php');
        die();
	}else{
        echo $sql;
		setEventMessages( 'Error al modificar el licitador', null, 'errors');
	}
}

if($action=='editar'){

    $representante ='';
    $nombre = '';
    $descripcion = '';
    $clasificacion = '';
    $fecha_inicio = '';
    $fecha_fin = '';
    $observaciones = '';
    $abjudicatario = '';

	if(isset($_GET['id'])){
		$id = $_GET['id'];
		$error=0;
		$sql = "SELECT * FROM khns_licitador WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);
        $nombre = $obj->nombre;
        $representante = $obj->representante;
		$descripcion = $obj->descripcion;
		$clasificacion = $obj->clasificacion;
		$fecha_inicio = $obj->fecha_inicio;
		$fecha_fin = $obj->fecha_fin;
		$observaciones = $obj->observaciones;
		$abjudicatario = $obj->abjudicatario;
	}

	print "
	    <head>
        <meta charset='UTF-8'>
        <title>Inserción Licitador</title>
    </head>
    <form id='form1'action='"; print $_SERVER['PHP_SELF']."?action=editar&id="; print $id."' method='post'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
                <tr>
                    <td>
                        <span class= 'fieldrequired'>Representante: </span>
                    </td>
                    <td>
                         <input required type='text' name='representante' maxlength='200' id='representante' value = '";print $representante."'/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class= 'fieldrequired'>Nombre: </span>
                    </td>
                    <td>
                         <input required type='text' name='nombre' maxlength='200' id='nombre' value = '";print $nombre."'/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>" . utf8_encode('Descripción:') . "</span>
                    </td>
                    <td>
                         <textarea type='text' style='width:400px; height:50px;' type='text' name='descripcion' maxlength='400' id='descripcion'/>"; print $descripcion . "</textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>" . utf8_encode('Clasificación:') . "</span>
                    </td>
                    <td>
                         <input type='text' name='clasificacion' maxlength='200' id='clasificacion' value = '";print $clasificacion."'/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Fecha Inicio: </span>
                    </td>
                    <td>
                         <input type='date' name='fecha_inicio' id='fecha_inicio' value = '";print $fecha_inicio."'/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Fecha Fin: </span>
                    </td>
                    <td>
                         <input type='date' name='fecha_fin' id='fecha_fin' value = '";print $fecha_fin."'/>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Observaciones: </span>
                    </td>
                    <td>
                         <textarea type='text' style='width:400px; height:50px;' type='text' name='observaciones' maxlength='400' id='observaciones' value = ''/>"; print $observaciones . "</textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span>Abjudicatario: </span>
                    </td>
                    <td>
                         <select name='abjudicatario'>
                            <option value= '";print $abjudicatario."'>"; print $abjudicatario . "</option>
                              <option value='". utf8_encode('Sí') . "'>" . utf8_encode('Sí') . "</option>
                              <option value='No' selected>No</option>
                         </select>
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