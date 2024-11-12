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

llxHeader('', $langs->trans(utf8_encode("Inserci�n Ruta")));

print load_fiche_titre($langs->trans(utf8_encode("Inserci�n Ruta")), '', 'companies');

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

$action='';

if(isset($_GET['action'])){
	$action = $_GET['action'];
}

if($action == 'crear'){
    /**
    $name ='';
    $id = '';

    if(isset($_POST['btnAdd'])){
         $name = $_POST['name'];

         $sql = "INSERT INTO ".MAIN_DB_PREFIX."ruta (name";
         $sql.= ") VALUES ('".$name . "')";

         $insertar = $db->query($sql);

		if($insertar && $error==0){
			setEventMessages( 'Ruta creada', null, 'mesgs');
            //header('Location: /societe/rutaindex.php');
            //die();
		}else{
            echo $sql;
			setEventMessages( 'Error al crear la ruta', null, 'errors');
		}
    }
    */

    print "
    <head>
        <meta charset='UTF-8'>
        <title>Escandallo Servicios: </title>
    </head>
    <form id='form1' method='post' enctype='multipart/form-data'>
    <div class='tabBar tabBarWithBottom' >
	    <table class='border centpercent'>
		    <tbody>

                <tr>
                    <td class='fieldrequired'>" .utf8_encode("Servicios Internos: ") . "</span></td>
                    <td>
                        <table><tbody>
                            <tr>
                                <td>" .utf8_encode("C�digo") . "</td>
                                <td>" .utf8_encode("Art�culo") . "</td>
                                <td>" .utf8_encode("Magnitud: ") . "</td>
                                <td>" .utf8_encode("T.Estimado: ") . "</td>
                                <td>" .utf8_encode("Categor�a: ") . "</td>
                                <td>" .utf8_encode("Precio Hora: ") . "</td>
                                <td>" .utf8_encode("Coste: ") . "</td>
                            </tr>
                        </tbody></table>
                    </td>
		        </tr>
                <tr>
                    <td>Nombre: </span>
                    </td>
                    <td>
                         <input style='width:400px;' type='text' name='nombre' maxlength='100' id='nombre' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>" .utf8_encode("Descripci�n: ") . "</span></td>
                    <td>
                         <textarea style='width:400px;height:50px;'name='descripcion' maxlength='600' id='descripcion' value = ''></textarea>
                    </td>
		        </tr>

                <tr>
                    <td>" .utf8_encode("Observaciones: ") . "</span></td>
                    <td>
                         <textarea style='width:400px;height:50px;'name='observaciones' maxlength='600' id='observaciones' value = ''></textarea>
                    </td>
		        </tr>

                <tr>
                    <td>" .utf8_encode("Referencia N�Serie: ") . "</span></td>
                    <td>
                         <input style='width:200px;' type='text' name='ref_serie' maxlength='100' id='ref_serie' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>". utf8_encode("Versi�n: ")."</span></td>
                    <td>
                         <input style='width:200px;' type='text' name='version' maxlength='100' id='version' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Naturaleza: </span></td>
                    <td>
                         <input style='width:400px;' type='text' name='naturaleza' maxlength='200' id='naturaleza' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Familia: </span></td>
                    <td>
                         <input style='width:400px;' type='text' name='familia' maxlength='200' id='familia' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Tipo de I.V.A.: </span></td>
                    <td>
                         <input style='width:200px;' type='text' name='tipo_iva' maxlength='100' id='tipo_iva' value = ''/>%
                    </td>
		        </tr>

                <tr>
                    <td>Magnitud: </span></td>
                    <td>
                         <input style='width:400px;' type='text' name='magnitud' maxlength='200' id='magnitud' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Unidad de Medida: </span></td>
                    <td>
                         <input style='width:200px;' type='text' name='unidad_medida' maxlength='200' id='unidad_medida' value = ''/>
                         <select style='margin-left:30px;'><option>Facturar por Unidades</option></select>
                    </td>
		        </tr>

                <tr>
                    <td>Tipo Impuesto: </span></td>
                    <td>
                         <input style='width:400px;' type='text' name='tipo_impuesto' maxlength='200' id='tipo_impuesto' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Cantidad Fija: </span></td>
                    <td>
                         <input style='width:200px;' type='number' name='cantidad_fija' maxlength='200' id='cantidad_fija' value = ''/>
                    </td>
                    <td>Porcentaje sobre Precio:</td>
                    <td>
                        <input style='width:200px;' type='number' name='porcentaje_precio' maxlength='200' id='porcentaje_precio' value = ''/>%
                    </td>
		        </tr>

                <tr>
                    <td>Mantenimiento: </span></td>
                    <td>
                         <input type='checkbox' name='mantenimiento' id='mantenimiento' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Bloqueado: </span></td>
                    <td>
                         <input type='checkbox' name='bloqueado' id='bloqueado' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>Trazabilidad: </span></td>
                    <td>
                         <input type='radio' name='trazabilidad' value='1'>Sin Trazabilidad

                        <br>

                        <input type='radio' name='trazabilidad' value='2' checked>Trazabilidad Unitaria

                        <br>

                        <input type='radio' name='trazabilidad' value='3'>Trazabilidad Por Lote
                    </td>
		        </tr>
                </br>
                <tr>
                        <td>Costes: </span></td>
                        <td>
                            <table><tbody>
                                <tr>
                                    <td></td>
                                    <td><input type='radio' name='costes' value='1'>" .utf8_encode("Est�ndar") . "</td>
                                    <td><input style='width:200px;' type='number' name='coste_estandar' maxlength='200' id='coste_estandar' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='coste_estandar_fecha' maxlength='200' id='coste_estandar' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>" . utf8_encode("Te�rico") . "</td>
                                    <td><input style='width:200px;' type='number' name='coste_teorico' maxlength='200' id='coste_teorico' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='coste_teorico_fecha' maxlength='200' id='coste_teorico' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><input type='radio' name='costes' value='3'>" .utf8_encode("Medio de Almac�n") . "</td>
                                    <td><input style='width:200px;' type='number' name='medio_almacen' maxlength='200' id='medio_almacen' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='medio_almacen_fecha' maxlength='200' id='medio_almacen' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><input type='radio' name='costes' value='4'>" .utf8_encode("�ltimo de Compra") . "</td>
                                    <td><input style='width:200px;' type='number' name='ultimo_compra' maxlength='200' id='ultimo_compra' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='ultimo_compra_fecha' maxlength='200' id='ultimo_compra' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><input type='radio' name='costes' value='4'>" .utf8_encode("�ltimo de Fabricaci�n") . "</td>
                                    <td><input style='width:200px;' type='number' name='ultimo_fabricacion' maxlength='200' id='ultimo_fabricacion' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='ultimo_fabricacion_fecha' maxlength='200' id='ultimo_fabricacion' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>".utf8_encode("Estimado Transporte") . "</td>
                                    <td><input style='width:200px;' type='number' name='estimado_transporte' maxlength='200' id='estimado_transporte' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='estimado_transporte_fecha' maxlength='200' id='estimado_transporte' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>".utf8_encode("Real Transporte") . "</td>
                                    <td><input style='width:200px;' type='number' name='real_transporte' maxlength='200' id='real_transporte' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='real_transporte_fecha' maxlength='200' id='real_transporte' value = ''/></td>
                                </tr>
                            </tbody></table>
                        <td>
                </tr>

                <tr>
                    <td>Tiempo Realizaci�n: </span></td>
                        <td>
                            <table><tbody>
                                <tr>
                                    <td></td>
                                    <td><input type='radio' name='costes' value='1'>" .utf8_encode("Tiempo Estimado (fabricaci�n)") . "</td>
                                    <td><input style='width:200px;' type='number' name='coste_estandar' maxlength='200' id='coste_estandar' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='coste_estandar_fecha' maxlength='200' id='coste_estandar' value = ''/></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>" . utf8_encode("Tiempo Estimado (compras)") . "</td>
                                    <td><input style='width:200px;' type='number' name='coste_teorico' maxlength='200' id='coste_teorico' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='coste_teorico_fecha' maxlength='200' id='coste_teorico' value = ''/></td>
                                </tr>
                                <tr>
                                    <td>" . utf8_encode("Tiempo Medio (fabricacion)") . "</td>
                                    <td><input type='radio' name='costes' value='3'>" .utf8_encode("Medio de Almac�n") . "</td>
                                    <td><input style='width:200px;' type='number' name='medio_almacen' maxlength='200' id='medio_almacen' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='medio_almacen_fecha' maxlength='200' id='medio_almacen' value = ''/></td>
                                </tr>
                                <tr>
                                    <td>" . utf8_encode("Tiempo Medio (compras)") . "</td>
                                    <td><input type='radio' name='costes' value='4'>" .utf8_encode("�ltimo de Compra") . "</td>
                                    <td><input style='width:200px;' type='number' name='ultimo_compra' maxlength='200' id='ultimo_compra' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='ultimo_compra_fecha' maxlength='200' id='ultimo_compra' value = ''/></td>
                                </tr>
                                <tr>
                                    <td>" . utf8_encode("Tiempo Real (fabricacion)") . "</td>
                                    <td><input type='radio' name='costes' value='4'>" .utf8_encode("�ltimo de Fabricaci�n") . "</td>
                                    <td><input style='width:200px;' type='number' name='ultimo_fabricacion' maxlength='200' id='ultimo_fabricacion' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='ultimo_fabricacion_fecha' maxlength='200' id='ultimo_fabricacion' value = ''/></td>
                                </tr>
                                <tr>
                                    <td>" . utf8_encode("Tiempo Real (fabricacion)") . "</td>
                                    <td>".utf8_encode("Estimado Transporte") . "</td>
                                    <td><input style='width:200px;' type='number' name='estimado_transporte' maxlength='200' id='estimado_transporte' value = ''/></td>
                                    <td><input style='width:200px;' type='date' name='estimado_transporte_fecha' maxlength='200' id='estimado_transporte' value = ''/></td>
                                </tr>
                            </tbody></table>
                        <td>
                </tr>

                <tr>
                    <td>Precio Venta</span></td>
                    <td>
                         <input style='width:200px;' type='number' name='precio_venta' maxlength='200' id='precio_venta' value = ''/>
                    </td>
                    <td>" .utf8_encode("Existencias Almac�n: ") . "</td>
                    <td>
                        <input style='width:200px;' type='number' name='existencia_almacen' maxlength='200' id='existencia_almacen' value = ''/>%
                    </td>
		        </tr>
                <tr>
                    <td>" .utf8_encode("Existencia Te�rica: ") . "</span></td>
                    <td>
                         <input style='width:200px;' type='number' name='existencia_teorica' maxlength='200' id='existencia_teorica' value = ''/>
                    </td>
                    <td>Existencia Real:</td>
                    <td>
                        <input style='width:200px;' type='number' name='existencia_real' maxlength='200' id='existencia_real' value = ''/>%
                    </td>
		        </tr>
                <tr>
                    <td>Existencias Pendientes: </span></td>
                    <td>
                         <input style='width:200px;' type='number' name='existencias_pendientes' maxlength='200' id='existencias_pendientes' value = ''/>
                    </td>
                    <td>Existencias Reservadas:</td>
                    <td>
                        <input style='width:200px;' type='number' name='existencias_reservadas' maxlength='200' id='existencias_reservadas' value = ''/>%
                    </td>
		        </tr>
                <tr>
                    <td>" .utf8_encode("Stock M�nimo: ") . "</span></td>
                    <td>
                         <input style='width:200px;' type='number' name='min_stock' maxlength='200' id='min_stock' value = ''/>
                    </td>
		        </tr>

                <tr>
                    <td>" .utf8_encode("Equivalencias: ") . "</span></td>
                    <td>
                         <textarea style='width:400px;height:50px;'name='equivalencias' maxlength='600' id='equivalencias' value = ''></textarea>
                    </td>
		        </tr>
		    </tbody>
	    </table>
    </div>
    <div>
        <input type='submit' class='button' name='btnAdd' value='" .utf8_encode("A�adir") . "'>
        <input type='reset' class='button' name='btnAn' value='Anular' >
    </div>
    </form>
    ";
}
/**
if(isset($_POST['modificar'])){
    $name = $_POST['name'];
    $sql = 'UPDATE ".MAIN_DB_PREFIX."ruta SET
            name="'.$name.'"';

    $sql.="  WHERE id=".$_GET['id'];

    $update = $db->query($sql);
	if($update && $error==0){
		    setEventMessages( 'Ruta modificada', null, 'mesgs');
            header('Location: /societe/rutaindex.php');
            die();
	    }else{
		    setEventMessages( 'Error al modificar la Ruta', null, 'errors');
	    }
}

if($action == 'editar'){
    $name = '';

    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $error=0;
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."ruta WHERE id=".$id;
		$respuesta = $db->query($sql);
		$num= $db->num_rows($sql);
		$aux = 0;
		$obj = $db->fetch_object($respuesta);
		$name = $obj->name;
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
		    </tbody>
	    </table>
    </div>
    <div>
        <div class='center'><input type='submit' class='button' name='modificar' value='Modificar' id='btnModal'></div>
    </div>
    </form>
    ";
}
*/

ob_end_flush();
// End of page
llxFooter();
$db->close();
ob_flush();