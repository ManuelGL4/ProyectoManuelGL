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

llxHeader('', $langs->trans(utf8_encode("Nueva Ruta")));

print load_fiche_titre($langs->trans(utf8_encode("Nueva Ruta")), '', 'companies');

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$action='';

if(isset($_GET['action'])){
	$action = $_GET['action'];
}

if($action == 'crear'){
    $name ='';
    $id = '';
    $codigo ='';
    $analitica = '';
    $contable ='';
    $coste = '';

    if(isset($_POST['btnAdd'])){
         $name = $_POST['name'];
         $codigo = $_POST['codigo'];
         $analitica = $_POST['analitica'];
         $contable = $_POST['contable'];
         $coste = $_POST['coste'];

         if ($codigo == "") {
            $codigo = NULL;
         }

         if ($analitica == "") {
            $analitica = 0;
         }

         if ($contable == "") {
            $contable = NULL;
         }

         if ($coste == "") {
            $coste = 0;
         }

         $sql = "INSERT INTO ".MAIN_DB_PREFIX."ruta (ruta, codigo, dist_analitica, cta_contable, centro_coste";
         $sql.= ") VALUES ('".$name ."', '".$codigo ."', ".$analitica .", '".$contable ."', ".$coste .")";

         $insertar = $db->query($sql);

		if($insertar && $error==0){
			setEventMessages( 'Ruta creada', null, 'mesgs');
            header('Location: rutaindex.php');
            die();
		}else{
            echo $sql;
			setEventMessages( 'Error al crear la ruta', null, 'errors');
		}
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Nueva Ruta</title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
                <tr>
                    <td>
                        <span class= 'fieldrequired'>Nombre Ruta: </span>
                    </td>
                    <td>
                         <input required type='text' name='name' style='width:600px' id='name' value = ''/>
                    </td>
		        </tr>
                <tr>
                    <td>
                        <span class= 'field'>Codigo: </span>
                    </td>
                    <td>
                        <input type='text' name='codigo' maxlength='200' id='codigo' value = ''/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class= 'field'>Dist. Analítica </span>
                    </td>
                    <td>
                        <input type='text' name='analitica' maxlength='200' id='analitica' value = ''/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class= 'field'>Cuenta Contable </span>
                    </td>
                    <td>
                        <input type='text' name='contable' maxlength='200' id='contable' value = ''/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class= 'field'>Centro Coste </span>
                    </td>
                    <td>
                        <input type='number' name='coste' id='coste' value = ''/>
                    </td>
                </tr>
		    </tbody>
	    </table>
    </div>
    <div>
        <input type='submit' class='button' name='btnAdd' value='Añadir'>
        <input type='reset' class='button' name='btnAn' value='Anular' >
    </div>
    </form>
    ";
}

if(isset($_POST['modificar'])){
    $name = $_POST['name'];
    $codigo = $_POST['codigo'];
    $analitica = $_POST['analitica'];
    $contable = $_POST['contable'];
    $coste = $_POST['coste'];

    if ($codigo == "") {
        $codigo = NULL;
     }

     if ($analitica == "") {
        $analitica = 0;
     }

     if ($contable == "") {
        $contable = NULL;
     }

     if ($coste == "") {
        $coste = 0;
     }

    $sql = 'UPDATE '.MAIN_DB_PREFIX.'ruta SET
            ruta="'.$name.'", codigo="'.$codigo.'", dist_analitica='.$analitica.', cta_contable="'.$contable.'", centro_coste='.$coste.' ';

    $sql.="  WHERE id=".$_GET['id'];

    $update = $db->query($sql);
	if($update && $error==0){
		    setEventMessages( 'Ruta modificada', null, 'mesgs');
            header('Location: rutaindex.php');
            die();
	    }else{
		    setEventMessages( 'Error al modificar la Ruta', null, 'errors');
	    }
}

if($action == 'editar'){
    $name = '';
    $codigo ='';
    $analitica = '';
    $contable ='';
    $coste = '';

    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $error=0;
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."ruta WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);
		$name = $obj->ruta;
    }

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Inserci�n Ruta</title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div  class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>
                <tr>
                    <td>
                        <span class= 'fieldrequired'>Nombre Ruta: </span>
                    </td>
                    <td>
                         <input required type='text' name='name' maxlength='200' id='name' value = '";print $name."'/>
                    </td>
		        </tr>
                <tr>
                <td>
                    <span class= 'field'>Codigo: </span>
                </td>
                <td>
                    <input type='text' name='codigo' maxlength='200' id='codigo' value = '".$obj->codigo."'/>
                </td>
            </tr>
            <tr>
                <td>
                    <span class= 'field'>Dist. Analítica </span>
                </td>
                <td>
                    <input type='text' name='analitica' maxlength='200' id='analitica' value = '".$obj->dist_analitica."'/>
                </td>
            </tr>
            <tr>
                <td>
                    <span class= 'field'>Cuenta Contable </span>
                </td>
                <td>
                    <input type='text' name='contable' maxlength='200' id='contable' value = '".$obj->cta_contable."'/>
                </td>
            </tr>
            <tr>
                <td>
                    <span class= 'field'>Centro Coste </span>
                </td>
                <td>
                    <input type='number' name='coste' id='coste' value = '".$obj->centro_coste."'/>
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