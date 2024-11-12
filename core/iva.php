<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/tools.php
 *       \brief      Home page for top menu tools
 */

require '../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other"));
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$action2 = (GETPOST('action2', 'alpha') ? GETPOST('action2', 'alpha') : 'view');
$action3 = (GETPOST('action3', 'alpha') ? GETPOST('action3', 'alpha') : 'view');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}



/*
 * View
 */

$socstatic = new Societe($db);

llxHeader("", $langs->trans("IVA"), "");

$text = $langs->trans("Info de IVA");
if ($action == "regimen") {
    $text = $langs->trans("Régimenes de IVA");
}
if ($action == "tipo") {
    $text = $langs->trans("Tipos de IVA");
}
 
print load_fiche_titre($text, '', 'wrench');

// Show description of content
print '<div class="justify opacitymedium">'.$langs->trans("Toda la información relacionada con los regímenes y tipos de IVA del sistema").'</div><br><br>';

if ($action == "regimen") {

    $regimenes = " SELECT * FROM ".MAIN_DB_PREFIX."regimen_iva ";

    $resultRegi = $db->query($regimenes);

	print '<div class="fichecenter">';
	print '<div class="fichethirdleft">';
	
	print "
		<div class='div-table-responsive'>
			<table class='tagtable liste'>
				<tbody>
					<tr class='liste_titre'>
						<th class='center liste_titre'>ID Régimen</th>
						<th class='center liste_titre'>Régimen</th>
                        <th class='center liste_titre'>Genera IVA</th>
                        <th class='center liste_titre'>Genera RE</th>
                        <th class='center liste_titre'>Porcentaje</th>
                        <th class='center liste_titre'></th>
					</tr>";

                $cont = 0;
                while ($regi = $db->fetch_object($resultRegi)) {
                    if ($regi->genera_iva == 1) {
                        $iva = "Si";
                    } else {
                        $iva = "No";
                    }

                    if ($regi->genera_re == 1) {
                        $re = "Si";
                    } else {
                        $re = "No";
                    }

                    if ($regi->porcentaje == "") {
                        $regi->porcentaje = "Variable";
                    }

                    if ($cont == 0) {
                        print "<tr class='oddeven'>
                            <td class='center'><b>".$regi->id_regimen."</b></td>
                            <td class='center'><b>".$regi->regimen_iva."</b></td>
                            <td class='center'><b>".$iva."</b></td>
                            <td class='center'><b>".$re."</b></td>
                            <td class='center'><b>".$regi->porcentaje." %</b></td>
                            <td><a href='". $_SERVER["PHP_SELF"] ."?action=regimen&action2=editar&id=" . $regi->rowid . "' title='Modificar'>".img_edit()."</a></td>";	
                        print "</tr>";
                    } else {
                        print "<tr class='oddeven'>
                            <td class='center'>".$regi->id_regimen."</td>
                            <td class='center'>".$regi->regimen_iva."</td>
                            <td class='center'>".$iva."</td>
                            <td class='center'>".$re."</td>";

                            if ($regi->porcentaje == "Variable") {
                                print "<td class='center'>".$regi->porcentaje."</td>";
                            } else {
                                print "<td class='center'>".$regi->porcentaje." %</td>";
                            }
                            
                            //if ($cont == 5) {
                                //print "<td><a href='". $_SERVER["PHP_SELF"] ."?action=regimen&action2=editar&id=" . $regi->rowid . "' title='Modificar'>".img_edit()."</a></td>";	
                            //} else {
                               print "<td></td>";
                            //}

                            print "</tr>";
                    }

                    $cont++;
                }
					

				print "</tbody>
			</table>
		</div>";

	print '</div>';
	print '</div>';
}

if ($action2 == "editar") {

    $idRegimen = $_GET['id'];

    $consulta = " SELECT * FROM ".MAIN_DB_PREFIX."regimen_iva ";
    $consulta.= " WHERE rowid = ".$idRegimen;

    $resultConsulta = $db->query($consulta);
    $regimen = $db->fetch_object($resultConsulta);

    print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=regimen" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar IVA</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 60.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Porcentaje</span>
							</td>
							<td>
                                <input type="text" class="center" name="porcentaje" value="'.$regimen->porcentaje.'">
							</td>
						</tr>
                        <tr>
                            <td>
                                <input type="hidden" class="center" name="regimen" value="'.$regimen->id_regimen.'">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="hidden" class="center" name="porcentaje_anterior" value="'.$regimen->porcentaje.'">
                            </td>
                        </tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editado">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';
}


if (isset($_POST['editado'])) {
    
    $porcentaje = $_POST['porcentaje'];
    $regimen = $_POST['regimen'];
    $porcentaje_anterior = $_POST['porcentaje_anterior'];

    $update1 = " UPDATE ".MAIN_DB_PREFIX."regimen_iva ";
    $update1.= " SET porcentaje = ".$porcentaje." ";
    $update1.= " WHERE id_regimen = ".$regimen." ";

    $db->query($update1);

    $update2 = " UPDATE ".MAIN_DB_PREFIX."societe_extrafields ";
    $update2.= " SET porc_iva = ".$porcentaje." ";
    $update2.= " WHERE id_regimen_iva = ".$regimen." ";

    $db->query($update2);

    $update3 = " UPDATE ".MAIN_DB_PREFIX."delegacion ";
    $update3.= " SET iva = ".$porcentaje." ";
    $update3.= " WHERE iva = ".$porcentaje_anterior." ";

    $db->query($update3);

    print '<meta http-equiv="refresh" content="0; url="' . $_SERVER['PHP_SELF'] . '?action=regimen">';

}


if ($action == "tipo") {

    $tipos = " SELECT * FROM ".MAIN_DB_PREFIX."tipos_iva ";

    $resultTipos = $db->query($tipos);

    print '<div class="fichecenter">';
	print '<div class="fichethirdleft">';
	
	print "
		<div class='div-table-responsive'>
			<table class='tagtable liste'>
				<tbody>
					<tr class='liste_titre'>
						<th class='center liste_titre'>ID Tipo de IVA</th>
						<th class='center liste_titre'>Porcentaje IVA</th>
                        <th class='center liste_titre'>Porcentaje RE</th>
                        <th></th>
					</tr>";

                while ($tipo = $db->fetch_object($resultTipos)) {

                    print "<tr class='oddeven'>
                        <td class='center'>".$tipo->id_tipo_iva."</td>
                        <td class='center'>".$tipo->porc_iva." %</td>
                        <td class='center'>".$tipo->porc_recargo." %</td>
                        <td><a href='". $_SERVER["PHP_SELF"] ."?action=tipo&action3=editar&id=" . $tipo->rowid . "' title='Modificar'>".img_edit()."</a></td>";		
                    print "</tr>";

                }
			
				print "</tbody>
			</table>
		</div>";

	print '</div>';
	print '</div>';
}



if ($action3 == "editar") {

    $idTipo = $_GET['id'];

    $consulta = " SELECT * FROM ".MAIN_DB_PREFIX."tipos_iva ";
    $consulta.= " WHERE rowid = ".$idTipo;

    $resultConsulta = $db->query($consulta);
    $tipo = $db->fetch_object($resultConsulta);

    print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=tipo" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar IVA</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 80.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Porcentaje IVA</span>
							</td>
							<td>
                                <input type="text" class="center" name="porcentaje_iva" value="'.$tipo->porc_iva.'">
							</td>
						</tr>
                        <tr>
                            <td>
                                <span class="fieldrequired">Porcentaje Recargo</span>
                            </td>
                            <td>
                                <input type="text" class="center" name="porcentaje_recargo" value="'.$tipo->porc_recargo.'">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="hidden" class="center" name="rowid" value="'.$tipo->rowid.'">
                            </td>
                        </tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="editado2">
						Guardar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';
}


if (isset($_POST['editado2'])) {
    
    $rowid = $_POST['rowid'];
    $porcentaje_iva = $_POST['porcentaje_iva'];
    $porcentaje_recargo = $_POST['porcentaje_recargo'];

    if ($porcentaje_iva == "") {
        $porcentaje_iva = "NULL";
    }

    if ($porcentaje_recargo == "") {
        $porcentaje_recargo = "NULL";
    }

    $update1 = " UPDATE ".MAIN_DB_PREFIX."tipos_iva ";
    $update1.= " SET porc_iva = ".$porcentaje_iva.", porc_recargo = ".$porcentaje_recargo." ";
    $update1.= " WHERE rowid = ".$rowid." ";

    $db->query($update1);

    print '<meta http-equiv="refresh" content="0; url="' . $_SERVER['PHP_SELF'] . '?action=tipo">';

}



llxFooter();

$db->close();
