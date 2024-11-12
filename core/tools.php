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

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}



/*
 * View
 */

$socstatic = new Societe($db);

llxHeader("", $langs->trans("Tools"), "");

$text = $langs->trans("Tools");

print load_fiche_titre($text, '', 'wrench');

// Show description of content
print '<div class="justify opacitymedium">'.$langs->trans("ToolsDesc").'</div><br><br>';

if ($user->admin == 1) {
	print '<a href="pruebaimportacionCLI.php">EXCEL CLIENTES</a>';
	print '<br>';
	print '<a href="pruebaimportacionPROV.php">EXCEL PROVEEDORES</a>';
	print '<br>';
	print '<a href="pruebaimportacionPRO.php">EXCEL PRODUCTOS</a>';
	print '<br>';
	print '<a href="pruebasql2.php">ARCHIVO SQL PRODUCTOS</a>';
	print '<br>';
	print '<a href="pruebacompuestos.php">ARCHIVO SQL COMPUESTOS</a>';
	print '<br>';
	print '<a href="pruebasql3.php">ARCHIVO SQL DELEGACIONES</a>';
	print '<br>';
	print '<a href="pruebasql4.php">ARCHIVO SQL DELEGACIONES PROVEEDORES</a>';
	print '<br>';
	print '<a href="pruebasql5.php">CONTRATOS MANTENIMIENTO</a>';
	print '<br>';
	print '<a href="pruebasql6.php">CONTRATOS MANTENIMIENTO EQUIPOS</a>';
	print '<br>';
	print '<a href="pruebasql7.php">CONTRATOS MANTENIMIENTO FASES (INFORMES)</a>';
	print '<br>';
	print '<a href="pruebasql24.php">AJUSTES (INFORMES)</a>';
	print '<br>';
	print '<a href="pruebasql16.php">CONTRATOS MANTENIMIENTO FASES (INFORMES) EQUIPOS</a>';
	print '<br>';
	print '<a href="pruebasql17.php">CONTRATOS MANTENIMIENTO FASES (INFORMES) SIG. VISITA</a>';
	print '<br>';
	print '<a href="pruebasql8.php">CONTRATOS FASES</a>';
	print '<br>';
	print '<a href="pruebasql9.php">REPRESENTANTES</a>';
	print '<br>';
	print '<a href="pruebasql10.php">FORMAS DE PAGO</a>';
	print '<br>';
	print '<a href="pruebasql11.php">RUTAS</a>';
	print '<br>';
	print '<a href="pruebasql12.php">CONTACTOS CLIENTES</a>';
	print '<br>';
	print '<a href="pruebasql13.php">CONTACTOS PROVEEDORES</a>';
	print '<br>';
	print '<a href="pruebasql14.php">CONTRATOS TAREAS (REPUESTOS A 0)</a>';
	print '<br>';
	print '<a href="pruebasql15.php">CONTRATOS TAREAS (REPUESTOS A 0) TODOS</a>';
	print '<br>';
	print '<a href="pruebasql19.php">FACTURAS DE VENTA</a>';
	print '<br>';
	print '<a href="pruebasql20.php">FACTURAS DE VENTA LINEAS</a>';
	print '<br>';
	print '<a href="pruebasql18.php">FACTURA ELECTRÓNICA</a>';
	print '<br>';
	print '<a href="pruebasql21.php">PROYECTOS</a>';
	print '<br>';
	print '<a href="pruebasql22.php">PROYECTOS-MATERIALES RELACIONES </a>';
	print '<br>';
	print '<a href="pruebasql22.php">PROYECTOS MATERIALES</a>';
	print '<br>';
	print '<a href="pruebasql23.php">AJUSTES DE STOCKS</a>';
	print '<br>';
	print '<a href="pruebasql25.php">AJUSTES CóDIGO PROVEEDORES</a>';
	print '<br>';
	print '<a href="pruebasql26.php">AJUSTES PRECIOS DE COMPRA Y PMP PRODUCTOS</a>';
	print '<br>';
	print '<a href="pruebasql27.php">NÚMEROS DE SERIE Y LOTES</a>';
	print '<br>';
	print '<a href="pruebasql28.php">PEDIDOS A PROVEEDORES</a>';
	print '<br>';
	print '<a href="pruebasql29.php">PEDIDOS A PROVEEDORES LINEAS</a>';
	print '<br>';
	print '<a href="pruebasql30.php">PEDIDOS A CLIENTES</a>';
	print '<br>';
	print '<a href="pruebasql31.php">PEDIDOS A CLIENTES LINEAS</a>';
	print '<br>';
	print '<a href="pruebasql32.php">AJUSTES FECHAS PREVISTAS PEDIDOS</a>';
	print '<br>';
	print '<a href="pruebasql33.php">ALBARANES CLIENTES (ENVÍOS)</a>';
	print '<br>';
	print '<a href="pruebasql34.php">ALBARANES CLIENTES LÍNEAS (ENVÍOS)</a>';
	print '<br>';
	print '<a href="pruebasql35.php">OFERTAS CLIENTES (PRESUPUESTOS)</a>';
	print '<br>';
	print '<a href="pruebasql36.php">OFERTAS CLIENTES LÍNEAS (PRESUPUESTOS)</a>';
	print '<br>';
	print '<a href="pruebasql37.php">NUM SERIE Y LOTES</a>';
	print '<br>';
	print '<a href="pruebasql38.php">AJUSTES CONTACTOS CONTRATOS</a>';
	print '<br>';
	print '<a href="pruebasql39.php">AJUSTES FACTURAS (ESTADOS)</a>';
	print '<br>';
	print '<br>';
	print '<br>';
	print '<a href="facegenerar.php">GENERAR FACTURA FACE</a>';
	$numero = "HOLA";
	if (ctype_digit($numero)) {
		print 'TRUE';
	}
}


// Show logo
print '<div class="center"><div class="logo_setup"></div></div>';


llxFooter();

$db->close();
