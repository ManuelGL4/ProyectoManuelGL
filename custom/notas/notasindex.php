<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       notas/notasindex.php
 *	\ingroup    notas
 *	\brief      Home page of notas top menu
 */

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("notas@notas"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->notas->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

 $form = new Form($db);
 $formfile = new FormFile($db);
 
 llxHeader("", $langs->trans("Tablero"));
 ?>
 <table class="centpercent notopnoleftnoright table-fiche-title">
	 <tr>
		 <td class="nobordernopadding widthpictotitle valignmiddle col-picto">
			 <span class="far fa-clipboard infobox-project valignmiddle pictotitle widthpictotitle" style=""></span>
		 </td>
		 <td class="nobordernopadding valignmiddle col-title">
			 <div class="titre inline-block">
				 <span style="padding: 0px; padding-right: 3px !important;"><?php echo $langs->trans("Tablero"); ?></span>
			 </div>
		 </td>
		 <?php if ($user->rights->notas->nota->write) { ?>
			 <?php if ($user->admin) { ?>
		 <td class="nobordernopadding valignmiddle col-title" align="right">
			 <a class="btnTitle btnTitlePlus" href="<?php echo DOL_URL_ROOT; ?>/custom/notas/nota_card.php?action=create" title="New Note"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>
		 </td>
		 <?php } ?>
		 <?php } ?>
	 </tr>
 </table>
 
 <div class="fichecenter">
 <div >
 
 <?php include('notasview.php');?>
 
 </div>
 </div>
 
 <?php
 // End of page
 llxFooter();
 $db->close();
 