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
 *	\file       easynotes/easynotesindex.php
 *	\ingroup    easynotes
 *	\brief      Home page of easynotes top menu
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
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

// Load translation files required by the page
$langs->loadLangs(array("easynotes@easynotes"));

$action = GETPOST('action', 'aZ09')?GETPOST('action', 'aZ09'):"project";
$actlist = "";
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

llxHeader("", $langs->trans("AutoNotes"));
$head = notes_prepare_dasboard_head('');
print dol_get_fiche_head($head, $action, '', -1, '');

?>

<div class="fichecenter">
<div >

<?php
if ($user->admin) {
	//make sure to use listed module only
	foreach ($head as $h=>$item) {
		if ($item[2] == $action ) {
			include('autonotesview.php');
			break;
		}
	}
} else {
	$destination_url = 'easynotesindex.php?idmenu=202&mainmenu=ecm&leftmenu=';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';
}
?>

</div>
</div>

<?php
// End of page
llxFooter();
$db->close();

function notes_prepare_dasboard_head($object) {
	global $langs, $conf, $user, $form;
	global $actlist;

	$h = 0;
	$head = array();

	$helptext = $langs->trans("Notes from projects");
	$head[$h][0] = 'autonotes.php?mainmenu=ecm&action=project';
	$head[$h][1] = $langs->trans("Projects").$form->textwithpicto('', $helptext, 1, 'info', '', 0, 3);
	$head[$h][2] = 'project';
	$h++;


	$helptext = $langs->trans("Notes from tasks");
	$head[$h][0] = 'autonotes.php?mainmenu=ecm&action=task';
	$head[$h][1] = $langs->trans("Tasks").$form->textwithpicto('', $helptext, 1, 'info', '', 0, 3);
	$head[$h][2] = 'task';
	$h++;

	return $head;
}

function in_array_r($needle, $haystack) {
    foreach ($haystack as $item) {
        if ($item[2] === $needle ) {
            return true;
        }
    }

    return false;
}
