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

$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
//$socid = GETPOST('socid', 'int');
$socid=$_GET["socid"];
$lista = $_GET["list"];
$dele = $_GET["dele"];

/*
 * View
 */
$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new Adherent($db);

llxHeader('', $langs->trans("Inserción Delegación"));

print load_fiche_titre($langs->trans("Inserción Delegación"), '', 'companies');

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$countrynotdefined = $langs->trans("ErrorSetACountryFirst");

$action='';
if(isset($_GET['action'])){
	$action = $_GET['action'];
    //$socId = $_GET['action'];
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
    $responsable = '';
    $forma_envio='';
    $tlf_transp='';
    $ruta='';

    if(isset($_POST['btnAdd'])){

        $socid=$_POST["fk_tercero"];
        $nombre = $_POST['nombre'];
        $responsible_name = $_POST['responsible_name'];
        $responsable = $_POST['fk_responsable'];
        $telef1 = $_POST['telef1'];
        $telef2 = $_POST['telef2'];
        $direccion = $_POST['direccion'];
        $cp = $_POST['zipcode'];
        $localidad = $_POST['town'];
        $provincia = $_POST['state_id'];
        $pais = $_POST['country_id'];
        $direccion_material = $_POST['direccion_material'];
        $direccion_factura = $_POST['direccion_factura'];
        $fk_tercero = $_POST['fk_tercero'];
        $fk_tipo_delegacion = $_POST['fk_tipo_delegacion'];
        $iva = $_POST['iva'];
        $codigo_delegacion = $_POST['codigo_delegacion'];
        $email=$_POST["email"];
        $web=$_POST["web"];
        $forma_envio=$_POST["forma_envio"];
        $tlf_transp=$_POST["tlf_transp"];
        $facturaPapel=$_POST["factura_papel"];
        $facturaCorreo=$_POST["factura_correo"];
        $facturaElectronica=$_POST["factura_electronica"];
        $ruta=$_POST["fk_ruta"];

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."delegacion (nombre, responsible_name, fk_representante, telef1, telef2, direccion, cp, localidad,provincia, pais, direccion_material, direccion_factura, fk_tercero, fk_tipo_delegacion, iva, codigo_delegacion";
        
        if ($email!="") $sql.=", email";
        if ($web!="") $sql.=", web";
        if ($forma_envio!="") $sql.=", forma_envio";
        if ($tlf_transp!="") $sql.=", tlf_transp";
        if ($ruta!="") $sql.=", fk_ruta";
        if ($facturaPapel!=null) $sql.=", factura_papel";
        if ($facturaCorreo!=null) $sql.=", factura_correo";
        if ($facturaElectronica!=null) $sql.=", factura_electronica";
         
        $sql.= ") VALUES ('".$nombre ."','".$responsible_name."', ".$responsable.", '".$telef1 . "', '".$telef2 . "','".$direccion."','".$cp."','".$localidad."','".$provincia . "', ".$pais . ", '".$direccion_material . "', '".$direccion_factura ."',".$fk_tercero.",".$fk_tipo_delegacion.",".$iva.",'".$codigo_delegacion."'";
        
        if ($email!="") $sql.=", '".$email."'";
        if ($web!="") $sql.=", '".$web."'";
        if ($forma_envio!="") $sql.=", '".$forma_envio."'";
        if ($tlf_transp!="") $sql.=", '".$tlf_transp."'";
        if ($ruta!="") $sql.=", ".$ruta."";
        if ($facturaPapel!="") $sql.=", ".$facturaPapel;
        if ($facturaCorreo!="") $sql.=", ".$facturaCorreo;
        if ($facturaElectronica!="") $sql.=", ".$facturaElectronica;

        $sql.= " )";

        $insertar = $db->query($sql);

		if($insertar && $error==0){
            if ($lista == 1) {
                setEventMessages("Delegación creada", null, 'mesgs');
                header('Location: delegacionindex.php');
            } else {
                setEventMessages("Delegación creada", null, 'mesgs');
                header('Location: delegacionCard.php?socid='.$socid.'');
            }
			
		}else{
			setEventMessages("Error al crear la delegación", null, 'errors');
		}
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Inserción Delegación</title>
    </head>
    <form id='form1'action='"; print $_SERVER['PHP_SELF']."?action=crear' method='post'>
        <div  class='tabBar tabBarWithBottom' >
            <table class='border centpercent'>
                <tbody>
                    <tr>
                        <td>
                            <span class='fieldrequired' >" ."Tercero asociado: ". "</span>
                        </td>
                        <td>
                            <select name='fk_tercero' class='fk_tercero'>";
                            echo "<option class='optiongrey' value='-1'>&nbsp;</option>";
                            $sql = 'SELECT rowid,nom  FROM '. MAIN_DB_PREFIX . 'societe';
                            $result = $db->query($sql);
                            while ($datos = $db->fetch_object($result)) {
                                
                                echo "<option class='optiongrey' value='" . $datos->rowid. "'";
                                if ($socid == $datos->rowid) {
                                    echo " selected";
                                }
                                echo " >" . $datos->nom . " </option> ";

                            }
                        print "</select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >" ."Tipo de delegación: ". "</span>
                        </td>
                        <td>
                            <select name='fk_tipo_delegacion' class='fk_tipo_delegacion'>";
                            echo "<option class='optiongrey' value='-1'>&nbsp;</option>";
                            $sql = 'SELECT id,nombre  FROM '. MAIN_DB_PREFIX . 'tipo_delegacion';
                            $result = $db->query($sql);
                            while ($datos = $db->fetch_object($result)) {
                                
                                echo "<option class='optiongrey' value='" . $datos->id. "'";
                                // if ($socid == $datos->id) {
                                // 	echo " selected";
                                // }
                                echo " >" . $datos->nombre . " </option> ";

                            }
                        print "</select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='fieldrequired' >" ."IVA: ". "</span>
                        </td>
                        <td>";

                            if ($socid != "") {

                                $sqlIva = " SELECT porc_iva FROM ". MAIN_DB_PREFIX . "societe_extrafields ";
                                $sqlIva.= " WHERE fk_object = ".$socid." ";
                                $resultIva = $db->query($sqlIva);
                                $iva = $db->fetch_object($resultIva);

                            }

                            if ($iva != "") {
                                print "<input required type='number' step='0.01' name='iva' value=".number_format($iva->porc_iva,2).">";
                            } else {
                                print "<input required type='number' step='0.01' name='iva'>";
                            }

                            print "
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='fieldrequired' >" ."Razón social: ". "</span>
                        </td>
                        <td>
                            <input required type='text' name='codigo_delegacion'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Nombre encargado: </span>
                        </td>
                        <td>
                            <input name='responsible_name' id='responsible_name' value = ''/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='fieldrequired' >Nombre delegación: </span>
                        </td>
                        <td>
                            <input required name='nombre' id='nombre' value = ''/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='fieldrequired' >" ."Dirección: ". "</span>
                        </td>
                        <td>
                            <input required type='text' name='direccion' id='direccion'>
                        </td>
                    </tr>
                    ";
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
                    /*if (empty($conf->global->MEMBER_DISABLE_STATE)) {
                        print '<tr><td class="fieldrequired">' . $langs->trans('State') . '</td><td>';
                        if ($object->country_id) {
                            print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
                        } else {
                            print $countrynotdefined;
                        }
                        print '</td></tr>';
                    }*/
                    print "<tr>
                        <td>
                            <span class='fieldrequired' >Provincia: </span>
                        </td>
                        <td>
                            <input required name='state_id' id='state_id' value = ''/>
                        </td>
                    </tr>";
                        

                    // print '<tr><td width="25%">' . $langs->trans('Country') . '</td><td>';
                    // print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
                    // if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);

                    print "
                    <tr>
                        <td>
                            <span class='fieldrequired' >Responsable: </span>
                        </td>
                        <td>
                            <select name='fk_responsable' class='fk_responsable'>";
                            echo "<option class='optiongrey' value='-1'>&nbsp;</option>";
                            $sql = 'SELECT rowid,nombre  FROM '. MAIN_DB_PREFIX . 'representantes';
                            $result = $db->query($sql);
                            while ($datos = $db->fetch_object($result)) {
                                
                                echo "<option class='optiongrey' value='" . $datos->rowid. "'";
                                // if ($socid == $datos->id) {
                                // 	echo " selected";
                                // }
                                echo " >" . $datos->nombre . "</option> ";

                            }
                        print "</select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Ruta: </span>
                        </td>
                        <td>
                            <select name='fk_ruta' class='fk_ruta'>";
                            echo "<option class='optiongrey' value='-1'>&nbsp;</option>";
                            $sql = 'SELECT id,ruta  FROM '. MAIN_DB_PREFIX . 'ruta';
                            $result = $db->query($sql);
                            while ($datos = $db->fetch_object($result)) {
                                
                                echo "<option class='optiongrey' value='" . $datos->id. "'";
                                // if ($socid == $datos->id) {
                                // 	echo " selected";
                                // }
                                echo " >" . $datos->ruta."</option> ";

                            }
                        print "</select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span>" ."Teléfono 1: ". "</span>
                        </td>
                        <td>
                            " . img_picto('', 'object_phoning') . "<input type='tel' name='telef1' id='telef1' value = ''/>
                        </td
                    </tr>
                    <tr>
                        <td>
                            <span class='' >" ."Fax: " . "</span>
                        </td>
                        <td>
                            " . img_picto('', 'object_phoning') . "<input type='tel' name='telef2' id='telef2' value = ''/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >" ."Dirección Envío Material: ". "</span>
                        </td>
                        <td>
                            <textarea style='resize:none;width: 400px;' type='text' name='direccion_material' id='direccion_material'></textarea>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class='' >" ."Dirección Envío Factura: ". "</span>
                        </td>
                        <td>
                            <textarea style='resize:none;width: 400px;' type='text' name='direccion_factura' id='direccion_factura'></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Email:</span>
                        </td>
                        <td>
                            <input type='email' name='email'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Web:</span>
                        </td>
                        <td>
                            <input type='text' name='web'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Forma envío:</span>
                        </td>
                        <td>
                            <input type='text' name='forma_envio'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Teléfono transportista:</span>
                        </td>
                        <td>
                            <input type='text' name='tlf_transp'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Factura a papel:</span>
                        </td>
                        <td>
                            <input type='checkbox' value='1' name='factura_papel'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Factura con correo:</span>
                        </td>
                        <td>
                            <input type='checkbox' value='1' name='factura_correo'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='' >Factura electrónica:</span>
                        </td>
                        <td>
                            <input type='checkbox' value='1' name='factura_electronica'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='hidden' name='socid' value='".$socid."'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='hidden' name='socid' value='".$lista."'>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class='center'>
            <input type='submit' class='button' name='btnAdd' value='" ."Añadir". "'>
            <input type='reset' class='button'  name='btnAn' value='Anular' onClick='javascript:history.go(-1)'>
        </div>
        
    </form>
    ";
}

if(isset($_POST['modificar'])){
        $socid=$_POST["socid"];
        $nombre = $_POST['nombre'];
        $representante = $_POST['fk_responsable'];
        $responsible_name = $_POST['responsible_name'];
        $telef1 = $_POST['telef1'];
        $telef2 = $_POST['telef2'];
        $direccion = $_POST['direccion'];
        $cp = $_POST['zipcode'];
        $localidad = $_POST['town'];
        $provincia = $_POST['state_id'];
        $pais =$_POST['country_id'];
        $direccion_material = $_POST['direccion_material'];
        $direccion_factura = $_POST['direccion_factura'];
        $fk_tercero = $_POST['fk_tercero'];
        $fk_tipo_delegacion = $_POST['fk_tipo_delegacion'];
        $iva = $_POST['iva'];
        $codigo_delegacion = $_POST['codigo_delegacion'];
        $email=$_POST["email"];
        $web=$_POST["web"];
        $forma_envio=$_POST["forma_envio"];
        $tlf_transp=$_POST["tlf_transp"];
        $facturaPapel=$_POST["factura_papel"];
        $facturaCorreo=$_POST["factura_correo"];
        $facturaElectronica=$_POST["factura_electronica"];
        $ruta=$_POST["fk_ruta"];

        $lista = $_POST["lista"];
        $dele = $_POST["dele"];

	$sql = "UPDATE  ".MAIN_DB_PREFIX."delegacion SET
	    nombre='".$nombre."'
        , fk_representante=".$representante."
        , telef1='".$telef1."'
        , responsible_name='".$responsible_name."'
        , telef2='".$telef2."'
        , direccion='".$direccion."'
        , cp='".$cp."'
        , localidad='".$localidad."'
        , provincia='".$provincia."'
        , pais='".$pais."'
        , direccion_material='".$direccion_material."'
        , direccion_factura='".$direccion_factura."'
        , fk_tercero='".$fk_tercero."'
        , fk_tipo_delegacion='".$fk_tipo_delegacion."'
        , iva='".$iva."'
        , fk_ruta=".$ruta."
        , codigo_delegacion='".$codigo_delegacion."'";

    if ($email!="") $sql.=", email='".$email."'";
    if ($web!="") $sql.=", web='".$web."'";
    if ($forma_envio!="") $sql.=", forma_envio='".$forma_envio."'";
    if ($tlf_transp!="") $sql.=", tlf_transp='".$tlf_transp."'";
    if ($ruta!="") $sql.=", fk_ruta=".$ruta."";
    if ($facturaPapel!=null){
        $sql.=", factura_papel=".$facturaPapel;
    }else{
        $sql.=", factura_papel=0";
    }  
    if ($facturaCorreo!=null){
        $sql.=", factura_correo=".$facturaCorreo;
    }else{
        $sql.=", factura_correo=0";
    }
    if ($facturaElectronica!=null){
        $sql.=", factura_electronica=".$facturaElectronica; 
    }else{
        $sql.=", factura_electronica=0"; 
    }

	$sql.="  WHERE id=".$_GET['id'];
	$update = $db->query($sql);
    
	if($update && $error==0){
        if ($lista == 1) {
            setEventMessages("Delegación ". 'modificada', null, 'mesgs');

            if ($dele == "") {
                header('Location: delegacionindex.php');
            } else {
                header('Location: delegacionindex.php?delegacionE='.$dele.'');
            }

        } else {
            setEventMessages("Delegación ". 'modificada', null, 'mesgs');
            header('Location: delegacionCard.php?socid='.$socid.'');
        }

	}else{
		setEventMessages( 'Error al modificar la Delegación', null, 'errors');
	}
}


if($action=='editar'){

    $nombre = '';
    $representante = '';
    $telef1 = '';
    $telef2 = '';
    $direccion = '';
    $cp = '';
    $localidad = '';
    $provincia = '';
    $pais = '';
    $direccion_material = '';
    $direccion_factura = '';
    $forma_envio = '';
    $tlf_transp = '';
    $ruta = '';


	if(isset($_GET['id'])){
		$id = $_GET['id'];
		$error=0;
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."delegacion WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);
		$nombre = $obj->nombre;
        $representante = $obj->fk_representante;
		$responsible_name = $obj->responsible_name;
        $apellidos=$obj->apellidos;
		$telef1 = $obj->telef1;
		$telef2 = $obj->telef2;
		$direccion = $obj->direccion;

		$cp = $obj->cp;
        $object->zip=$cp;
		$localidad = $obj->localidad;
        $object->town=$localidad;
		$provincia = $obj->provincia;
        $object->state_id=$provincia;
        $pais = $obj->pais;
        $object->country_id=$pais;

		$direccion_material = $obj->direccion_material;
		$direccion_factura = $obj->direccion_factura;

        $fk_tercero = $obj->fk_tercero;
        $fk_tipo_delegacion = $obj->fk_tipo_delegacion;
        $codigo_delegacion = $obj->codigo_delegacion;
        $iva = $obj->iva;

        $email=$obj->email;
        $web=$obj->web;

        $ruta = $obj->fk_ruta;

        $forma_envio=$obj->forma_envio;
        $tlf_transp=$obj->tlf_transp;

        $facturaPapel=$obj->factura_papel;
        $cheekPapel = ($facturaPapel==1) ? "checked" : "";
        $facturaCorreo=$obj->factura_correo;
        $cheekCorreo = ($facturaCorreo==1) ? "checked" : "";
        $facturaElectronica=$obj->factura_electronica;
        $cheekElectronica = ($facturaElectronica==1) ? "checked" : "";

	}

	print "
	<form id='form1'action='"; print $_SERVER['PHP_SELF']."?action=editar&id="; print $id."' method='post'>
	<div  class='tabBar tabBarWithBottom' >

		<table class='border centpercent'>
		    <tbody>
                <tr>
                    <td>
                        <span class='fieldrequired' >" ."Tercero asociado: ". "</span>
                    </td>
                    <td>
                        <select name='fk_tercero' class='fk_tercero'>";
                        $sql = 'SELECT rowid,nom  FROM '. MAIN_DB_PREFIX . 'societe';
						$result = $db->query($sql);
						while ($datos = $db->fetch_object($result)) {
							
							echo "<option class='optiongrey' value='" . $datos->rowid. "'";
							if ($fk_tercero == $datos->rowid) {
								echo " selected";
							}
							echo " >" . $datos->nom . " </option> ";

						}
                       print "</select>
                    </td>
		        </tr>
                <tr>
                    <td>
                        <span class='' >" ."Tipo de delegación: ". "</span>
                    </td>
                    <td>
                        <select name='fk_tipo_delegacion' class='fk_tipo_delegacion'>";
                        $sql = 'SELECT id,nombre  FROM '. MAIN_DB_PREFIX . 'tipo_delegacion';
						$result = $db->query($sql);
						while ($datos = $db->fetch_object($result)) {
							
							echo "<option class='optiongrey' value='" . $datos->id. "'";
							if ($fk_tipo_delegacion == $datos->id) {
								echo " selected";
							}
							echo " >" . $datos->nombre . " </option> ";

						}
                       print "</select>
                    </td>
		        </tr>
                <tr>
                    <td>
                        <span class='fieldrequired' >" ."IVA: ". "</span>
                    </td>
                    <td>
                        <input value='".$iva."' required type='number' step='0.01' name='iva'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='fieldrequired' >" ."Razón social: ". "</span>
                    </td>
                    <td>
                        <input type='text' name='codigo_delegacion' value='".$codigo_delegacion."'>
                    </td>
		        </tr>
                <tr>
                    <td>
                        <span class='' >Nombre encargado: </span>
                    </td>
                    <td>
                        <input name='responsible_name' id='responsible_name' value = '".$responsible_name."'/>
                    </td>
                </tr>
			    <tr>
                    <td>
                        <span class='fieldrequired' >Nombre delegación: </span>
                    </td>
                    <td>
                        <input required name='nombre' id='nombre' value = '";print $nombre."'/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='fieldrequired' >" ."Dirección: ". "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none; width: 400px;' type='text' name='direccion' id='direccion'>"; print $direccion . "</textarea>
                    </td>
		        </tr>";

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
                /*if (empty($conf->global->MEMBER_DISABLE_STATE)) {
                    print '<tr><td class="fieldrequired">' . $langs->trans('State') . '</td><td>';
                    if ($object->country_id) {
                        print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
                    } else {
                        print $countrynotdefined;
                    }
                    print '</td></tr>';
                }*/
                print "<tr>
                    <td>
                        <span class='fieldrequired' >Provincia: </span>
                    </td>
                    <td>
                        <input required name='state_id' id='state_id' value = '".$obj->provincia."'/>
                    </td>
                </tr>";

                print "
                <tr>
                    <td>
                        <span class='fieldrequired' >" ."Responsable: ". "</span>
                    </td>
                    <td>
                        <select name='fk_responsable' class='fk_responsable'>";
                        echo "<option class='optiongrey' value='-1'></option>";
                        $sql = 'SELECT rowid,firstname,lastname  FROM '. MAIN_DB_PREFIX . 'user';
                        $result = $db->query($sql);
                        while ($datos = $db->fetch_object($result)) {
                            
                            echo "<option class='optiongrey' value='" . $datos->rowid. "'";
                                if ($representante == $datos->rowid) {
                                    echo " selected";
                                }
                            echo " >" . $datos->firstname . " ".$datos->lastname."</option> ";

                        }
                    print "</select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Ruta: </span>
                    </td>
                    <td>
                        <select name='fk_ruta' class='fk_ruta' style='width:180px'>";
                        echo "<option class='optiongrey' value='-2'>&nbsp;</option>";
                        $sql = 'SELECT id,ruta  FROM '. MAIN_DB_PREFIX . 'ruta';
                        $result = $db->query($sql);
                        while ($datos = $db->fetch_object($result)) {
                            
                            echo "<option class='optiongrey' value='" . $datos->id. "'";
                                if ($ruta == $datos->id) {
                                    echo " selected";
                                }
                            echo " >" . $datos->ruta."</option> ";

                        }
                    print "</select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>" ."Teléfono 1: ". "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef1' id='telef1' value = '";print $telef1."'/>
                    </td
		        </tr>
                <tr>
                    <td>
                        <span class='' >" ."Teléfono 2: ". "</span>
                    </td>
                    <td>
                         " . img_picto('', 'object_phoning') . "<input type='tel' name='telef2' id='telef2' value = '";print $telef2."'/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >" ."Dirección Envío Material: ". "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none;width: 400px;' type='text' name='direccion_material' id='direccion_material'>"; print $direccion_material . "</textarea>
                    </td>
		        </tr>

                <tr>
                    <td>
                        <span class='' >" ."Dirección Envío Factura: ". "</span>
                    </td>
                    <td>
                        <textarea type='text' style='resize:none;width: 400px;' type='text' name='direccion_factura' id='direccion_factura'>"; print $direccion_factura . "</textarea>
                    </td>
		        </tr>
                <tr>
                    <td>
                        <span class='' >Email:</span>
                    </td>
                    <td>
                        <input type='email' name='email' value='".$email."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Web:</span>
                    </td>
                    <td>
                        <input type='text' name='web' value='".$web."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Forma envío:</span>
                    </td>
                    <td>
                        <input type='text' name='forma_envio' value='".$forma_envio."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Teléfono transportista:</span>
                    </td>
                    <td>
                        <input type='text' name='tlf_transp' value='".$tlf_transp."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Factura a papel:</span>
                    </td>
                    <td>
                        <input type='checkbox' ".$cheekPapel." value='1' name='factura_papel'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Factura con correo:</span>
                    </td>
                    <td>
                        <input type='checkbox' ".$cheekCorreo." value='1' name='factura_correo'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class='' >Factura electrónica:</span>
                    </td>
                    <td>
                        <input type='checkbox' ".$cheekElectronica." value='1' name='factura_electronica'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type='hidden' name='socid' value='".$socid."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type='hidden' name='lista' value='".$lista."'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type='hidden' name='dele' value='".$dele."'>
                    </td>
                </tr>
    
		    </tbody>
	    </table>
    </div>
    <div>
		<div class='center'>
            <input type='submit' class='button' name='modificar' value='Modificar' id='btnModal'>
            <input type='reset' class='button'  name='btnAn' value='Anular' onClick='javascript:history.go(-1)'>
        </div>
    </div>
    </form>

	";
}

print "<script>

$(document).ready(function() {
	$('.fk_tercero').select2();
    $('.fk_tipo_delegacion').select2();
    $('.fk_responsable').select2();
    $('.fk_ruta').select2();
});


</script>";

ob_end_flush();
// End of page
llxFooter();
$db->close();
ob_flush();