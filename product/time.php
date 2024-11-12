<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry	  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *   \file       htdocs/product/time.php
 *   \brief      Tab for times on products
 *   \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Product($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

$permissionnote = $user->rights->produit->creer; // Used by the include of actions_setnotes.inc.php

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once


/*
 *	View
 */

$form = new Form($db);

$help_url = '';
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

$title = $langs->trans('ProductServiceCard');
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Notes');
	$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Notes');
	$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	/*
	 * Affichage onglets
	 */
	if (!empty($conf->notification->enabled)) {
		$langs->load("mails");
	}

	$head = product_prepare_head($object);
	$titre = $langs->trans("CardProduct".$object->type);
	$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

	print dol_get_fiche_head($head, 'time', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = " fk_product_type = ".$object->type;

	$shownav = 1;
	if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
		$shownav = 0;
	}

    if ($action != "edit") {

        dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

        $cssclass = 'titlefield';
        //if ($action == 'editnote_public') $cssclass='titlefieldcreate';
        //if ($action == 'editnote_private') $cssclass='titlefieldcreate';

        //print '<div class="fichecenter">';

        $sql = "SELECT tiempo_min_est";
        $sql.= ", tiempo_min_real";
        $sql.= ", tiempo_med_est";
        $sql.= ", tiempo_med_real";
        $sql.= ", tiempo_max_est";
        $sql.= ", tiempo_max_real";
        $sql.= ", fecha_min_est";
        $sql.= ", fecha_min_real";
        $sql.= ", fecha_med_est";
        $sql.= ", fecha_med_real";
        $sql.= ", fecha_max_est";
        $sql.= ", fecha_max_real ";
        $sql.= "FROM " . MAIN_DB_PREFIX . "product_extrafields ";
        $sql.= " WHERE fk_object = ".$id."";

        $result = $db->query($sql);
        $produ = $db->fetch_object($result);

        if ($produ->fecha_min_est == NULL) {
            $fechaMinEst = "";
        } else {
            $fechaMinEst = date('d-m-Y', strtotime($produ->fecha_min_est));
        }

        if ($produ->fecha_min_real == NULL) {
            $fechaMinReal = "";
        } else {
            $fechaMinReal = date('d-m-Y', strtotime($produ->fecha_min_real));
        }

        if ($produ->fecha_med_est == NULL) {
            $fechaMedEst = "";
        } else {
            $fechaMedEst = date('d-m-Y', strtotime($produ->fecha_med_est));
        }

        if ($produ->fecha_med_real == NULL) {
            $fechaMedReal = "";
        } else {
            $fechaMedReal = date('d-m-Y', strtotime($produ->fecha_med_real));
        }

        if ($produ->fecha_max_est == NULL) {
            $fechaMaxEst = "";
        } else {
            $fechaMaxEst = date('d-m-Y', strtotime($produ->fecha_max_est));
        }

        if ($produ->fecha_max_real == NULL) {
            $fechaMaxReal = "";
        } else {
            $fechaMaxReal = date('d-m-Y', strtotime($produ->fecha_max_real));
        }

        print "
        <form>
        <div  class='fichecenter' >
        <div class='underbanner clearboth'></div>
        <table class='border tableforfield centpercent'>
            <tbody>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo mínimo (Estimado)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_1' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_min_est."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_1' class='valuefield product_extras_tiempo wordbreak'>".$fechaMinEst."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo mínimo (Real)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_2' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_min_real."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_2' class='valuefield product_extras_tiempo wordbreak'>".$fechaMinReal."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo medio (Estimado)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_3' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_med_est."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_3' class='valuefield product_extras_tiempo wordbreak'>".$fechaMedEst."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo medio (Real)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_4' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_med_real."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_4' class='valuefield product_extras_tiempo wordbreak'>".$fechaMedReal."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo máximo (Estimado)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_5' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_max_est."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_5' class='valuefield product_extras_tiempo wordbreak'>".$fechaMaxEst."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Tiempo máximo (Real)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_tiempo_6' class='valuefield product_extras_tiempo wordbreak'>".$produ->tiempo_max_real."</td>
                    </td>
                </tr>
                <tr class='trextrafields_collapse_1'>
                    <td class='titlefield'>
                        <table class='nobordernopadding centpercent'>
                            <tbody>
                                <tr>
                                    <td class>Fecha estimacion</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <td id='product_extras_fecha_6' class='valuefield product_extras_tiempo wordbreak'>".$fechaMaxReal."</td>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
        </div>
        <div class='tabsAction'>
        <a class='butAction' href='/product/time.php?action=edit&id=".$id."'>Modificar</a>
        </div>
        </form>			
        ";

    }

}

if ($action == "edit") {

    $sql = "SELECT tiempo_min_est";
    $sql.= ", tiempo_min_real";
    $sql.= ", tiempo_med_est";
    $sql.= ", tiempo_med_real";
    $sql.= ", tiempo_max_est";
    $sql.= ", tiempo_max_real";
    $sql.= ", fecha_min_est";
    $sql.= ", fecha_min_real";
    $sql.= ", fecha_med_est";
    $sql.= ", fecha_med_real";
    $sql.= ", fecha_max_est";
    $sql.= ", fecha_max_real ";
    $sql.= "FROM " . MAIN_DB_PREFIX . "product_extrafields ";
    $sql.= " WHERE fk_object = ".$id."";

    $result = $db->query($sql);
    $produ = $db->fetch_object($result);

    //$fechaMinEst = date('d-m-Y', strtotime($produ->fecha_min_est));
    //$fechaMinReal = date('d-m-Y', strtotime($produ->fecha_min_real));
    //$fechaMedEst = date('d-m-Y', strtotime($produ->fecha_med_est));
    //$fechaMedReal = date('d-m-Y', strtotime($produ->fecha_med_real));
    //$fechaMaxEst = date('d-m-Y', strtotime($produ->fecha_max_est));
    //$fechaMaxReal = date('d-m-Y', strtotime($produ->fecha_max_real));
    
    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST" name="formtime">'."\n";
    print '<table class="border allwidth">';
        
        print '<tr><td class="titlefieldcreate">Tiempo mínimo (Estimado)</td><td colspan="3"><input name="TiempoMinEst" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_min_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst1" class="maxwidth200" maxlength="128" value="'.$produ->fecha_min_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Tiempo mínimo (Real)</td><td colspan="3"><input name="TiempoMinReal" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_min_real.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst2" class="maxwidth200" maxlength="128" value="'.$produ->fecha_min_real.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Tiempo medio (Estimado)</td><td colspan="3"><input name="TiempoMedEst" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_med_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst3" class="maxwidth200" maxlength="128" value="'.$produ->fecha_med_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Tiempo medio (Real)</td><td colspan="3"><input name="TiempoMedReal" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_med_real.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst4" class="maxwidth200" maxlength="128" value="'.$produ->fecha_med_real.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Tiempo máximo (Estimado)</td><td colspan="3"><input name="TiempoMaxEst" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_max_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst5" class="maxwidth200" maxlength="128" value="'.$produ->fecha_max_est.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Tiempo máximo (Real)</td><td colspan="3"><input name="TiempoMaxReal" class="maxwidth200" maxlength="128" value="'.$produ->tiempo_max_real.'"></td></tr>';
        print '<tr><td class="titlefieldcreate">Fecha estimacion</td><td colspan="3"><input type="date" name="FechaEst6" class="maxwidth200" maxlength="128" value="'.$produ->fecha_max_real.'"></td></tr>';
        
    print '</table>';
    print '<div class="center">';
    print '<input type="submit" class="button button-save" name="Editar" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';
    print '</form>';

}

if (isset($_POST['Editar'])) {

    $tiempo_min_est = $_POST['TiempoMinEst'];
    $tiempo_min_real = $_POST['TiempoMinReal'];
    $tiempo_med_est = $_POST['TiempoMedEst'];
    $tiempo_med_real = $_POST['TiempoMedReal'];
    $tiempo_max_est = $_POST['TiempoMaxEst'];
    $tiempo_max_real = $_POST['TiempoMaxReal'];

    $fecha_est_1 = $_POST['FechaEst1'];
    $fecha_est_2 = $_POST['FechaEst2'];
    $fecha_est_3 = $_POST['FechaEst3'];
    $fecha_est_4 = $_POST['FechaEst4'];
    $fecha_est_5 = $_POST['FechaEst5'];
    $fecha_est_6 = $_POST['FechaEst6'];

    $sql = "UPDATE " . MAIN_DB_PREFIX . "product_extrafields ";
    $sql.= "SET ";

    if ($tiempo_min_est != "") {
        $sql.= " tiempo_min_est = ".$tiempo_min_est.",";
    } else {
        $sql.= " tiempo_min_est = NULL,";
    }

    if ($tiempo_min_real != "") {
        $sql.= " tiempo_min_real = ".$tiempo_min_real.",";
    } else {
        $sql.= " tiempo_min_real = NULL,";
    }

    if ($tiempo_med_est != "") {
        $sql.= " tiempo_med_est = ".$tiempo_med_est.",";
    } else {
        $sql.= " tiempo_med_est = NULL,";
    }

    if ($tiempo_med_real != "") {
        $sql.= " tiempo_med_real = ".$tiempo_med_real.",";
    } else {
        $sql.= " tiempo_med_real = NULL,";
    }
    
    if ($tiempo_max_est != "") {
        $sql.= " tiempo_max_est = ".$tiempo_max_est.",";
    } else {
        $sql.= " tiempo_max_est = NULL,";
    }

    if ($tiempo_max_real != "") {
        $sql.= " tiempo_max_real = ".$tiempo_max_real.",";
    } else {
        $sql.= " tiempo_max_real = NULL,";
    }

    if ($fecha_est_1 != "") {
        $sql.= " fecha_min_est = '".$fecha_est_1."',";
    } else {
        $sql.= " fecha_min_est = NULL,";
    }

    if ($fecha_est_2 != "") {
        $sql.= " fecha_min_real = '".$fecha_est_2."',";
    } else {
        $sql.= " fecha_min_real = NULL,";
    }

    if ($fecha_est_3 != "") {
        $sql.= " fecha_med_est = '".$fecha_est_3."',";
    } else {
        $sql.= " fecha_med_est = NULL,";
    }

    if ($fecha_est_4 != "") {
        $sql.= " fecha_med_real = '".$fecha_est_4."',";
    } else {
        $sql.= " fecha_med_real = NULL,";
    }

    if ($fecha_est_5 != "") {
        $sql.= " fecha_max_est = '".$fecha_est_5."',";
    } else {
        $sql.= " fecha_max_est = NULL,";
    }
    
    if ($fecha_est_6 != "") {
        $sql.= " fecha_max_real = '".$fecha_est_6."'";
    } else {
        $sql.= " fecha_max_real = NULL,";
    }

    if (substr($sql, -1) === ',') {
        $sql = substr($sql, 0, -1);
    }
    
    $sql.= " WHERE fk_object = ".$id."";

    $db->query($sql);

    $destination_url = 'time.php?id='.$id.'';

    print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

    //header('Location: time.php?id='.$id.'');
    
}

// End of page
llxFooter();
$db->close();
