<?php
ob_start();

$form = new Form($db);
$notes_user = $user->id; //mine
$tuser = new User($db);

require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

print '<style>
.wrapper {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(18%, 1fr));
	grid-gap: 1rem;
}
.category_name {
	font-weight: bold;
}
.dashboard_column {
	display: grid;
	grid-gap: 0rem;
}
.dash_in.notes {
	word-break: break-word;
	white-space: pre-wrap;
	cursor: grab;
}
.dash_in.notes:active {
    cursor: grabbing; 
}
.note_user {
	font-size: 15px;
	float:right;
	border-radius: 5px;
	font-weight: bold;
	color: white;
}
.pictodelete {
	margin-left: 10px;
}
.notes .arrow {
	background: rgba(0, 0, 0, 0);
}
.dashboard_column2 {
    display: flex;
    flex-wrap: wrap;
    grid-gap: 0rem;
}
.truncate-text {
	display: -webkit-box;
	-webkit-line-clamp: 3;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
  }
  
</style>';

// Si eres admin
if ($user->admin) {

	if (isset($_GET['delTasks'])) {
		$delTasks = $_GET['delTasks'];
	} else {
		$delTasks = "";
	}

	if (!isset($_GET["delTasks"])) {

		// TODAS LAS TAREAS ACTIVAS

		$sqlCategory = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota_categories ORDER BY rowid DESC";
		$resultCategory = $db->query($sqlCategory);

		// PARA LOS PERMISOS DEL GRUPO TELETRABAJO

		$sqlRights = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
		$sqlRights.= "WHERE module LIKE 'notas' ";
		$sqlRights.= "LIMIT 1";

		$result = $db->query($sqlRights);
		$idRight = $db->fetch_object($result);

		$sqlGroup = "SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_rights ";
		$sqlGroup.= "WHERE fk_id = ".$idRight->id."";
		
		$result = $db->query($sqlGroup);
		$group = $db->fetch_object($result);

		$sqlUs = "SELECT u.rowid, u.firstname, u.lastname FROM ".MAIN_DB_PREFIX."user u ";
		$sqlUs.= "INNER JOIN ".MAIN_DB_PREFIX."usergroup_user gu ";
		$sqlUs.= "ON u.rowid = gu.fk_user ";
		$sqlUs.= "WHERE gu.fk_usergroup = ".$group->fk_usergroup."";
		$resultUs = $db->query($sqlUs);

		if (isset($_GET['userCh'])) {
			$usuarioE = $_GET['userCh'];
		} else {
			$usuarioE = "";
		}

		if (isset($_GET['usuBusc'])) {
			$usuarioE2 = $_GET['usuBusc'];
		} else {
			$usuarioE2 = "";
		}

		if (isset($_GET['noteCh'])) {
			$notaE = $_GET['noteCh'];
		} else {
			$notaE = "";
		}

		// Para buscar por usuario
		print '    <form method="GET" action="' . $_SERVER['PHP_SELF'] . '">
					<label for="userCh">Nombre de usuario:</label>
					<select class="select-user" style="width:200px" name="userCh">
					<option value="0">Todos</option>';

					while ($userA = $db->fetch_object($resultUs)) {

						if ($userA->rowid == $usuarioE) {
							print '<option value = "'.$userA->rowid.'" selected>'.$userA->firstname.' '.$userA->lastname.'</option>';
						} else {
							print '<option value = "'.$userA->rowid.'">'.$userA->firstname.' '.$userA->lastname.'</option>';
						}

					}



		print '		</select>
					<label for="noteCh">Nombre de la nota:</label>
					<input type="text" class="buscar-nota" style="width:195px" name="noteCh">
					<button class="butAction" type="submit">Buscar</button>
					</form>';

		print '<br>';

		print "<script>

		$(document).ready(function() {
			$('.select-user').select2();
		});
		</script>";

		print '<br>';

		print '<div class="wrapper">';

		while( $data = $db->fetch_object($resultCategory) ){

			// Usuario: Todos
			// Nota: Vacio
			if (($usuarioE == 0) && ($notaE == "")) {
				$sql1 = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota as t ";
				$sql1.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.category=". $data->rowid ." ";
				$sql1.= "ORDER BY priority ";
				$result = $db->query($sql1);
				$nbtotalofrecords = $db->num_rows($result);

			// Usuario: Uno concreto
			// Nota: Vacio
			} else if (($usuarioE != 0) && ($notaE == "")) {
				$idUsuarioElegido = $_GET['userCh'];

				$sqlBusc = "SELECT u.rowid FROM ".MAIN_DB_PREFIX."user u WHERE rowid = '".$idUsuarioElegido."'";
				$result = $db->query($sqlBusc);
				$idBusc = $db->fetch_object($result);
				$idUser = $idBusc->rowid;

				$sql2 = "SELECT t.* FROM ".MAIN_DB_PREFIX."notas_nota as t ";
				$sql2.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
				$sql2.= "ON t.rowid = nu.idnote ";
				$sql2.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.category=". $data->rowid ." AND nu.iduser = ".$idUser." ";
				$sql2.= "ORDER BY priority ";
				$result = $db->query($sql2);
				$nbtotalofrecords = $db->num_rows($result);

			// Usuario: Todos
			// Nota: Una concreta
			} else if (($usuarioE == 0) && ($notaE != "")) {
				$sql3 = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota as t ";
				$sql3.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.category=". $data->rowid ." ";
				$sql3.= "AND label LIKE '%".$notaE."%' ";
				$sql3.= "ORDER BY priority ";

				$result = $db->query($sql3);
				$nbtotalofrecords = $db->num_rows($result);

			// Usuario: Uno concreto
			// Nota: Una concreta
			} else {
				$idUsuarioElegido = $_GET['userCh'];

				$sqlBusc = "SELECT u.rowid FROM ".MAIN_DB_PREFIX."user u WHERE rowid = '".$idUsuarioElegido."'";
				$result = $db->query($sqlBusc);
				$idBusc = $db->fetch_object($result);
				$idUser = $idBusc->rowid;

				$sql4 = "SELECT t.* FROM ".MAIN_DB_PREFIX."notas_nota as t ";
				$sql4.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
				$sql4.= "ON t.rowid = nu.idnote ";
				$sql4.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.category=". $data->rowid ." AND nu.iduser = ".$idUser." ";
				$sql4.= "AND label LIKE '%".$notaE."%' ";
				$sql4.= "ORDER BY priority ";
				$result = $db->query($sql4);
				$nbtotalofrecords = $db->num_rows($result);

			}

			print '<div class="dash_in" ondrop="drop(event)" ondragover="allowDrop(event)">';
			print '<label class="category_name">'. $data->name .'</label>';
			print '<hr>';
			print '<div class="dashboard_column">';

			// Código para mostrar las notas
			$i = 0;
			while ($i < $nbtotalofrecords) {

				$obj = $db->fetch_object($result);
				
				if ($obj->deleted == "") {

					$sqlPro = "SELECT p.title FROM ".MAIN_DB_PREFIX."projet as p ";
					$sqlPro.= "WHERE p.rowid = ".$obj->fk_project."";
					$resul = $db->query($sqlPro);
					$proyectof = $db->fetch_object($resul);

					$proSiglas = substr($proyectof->title, 0, 3);
					$proSiglasMay = strtoupper($proSiglas);

					$noteid = $obj->rowid;
					$url = 'nota_card.php?id='.$noteid;

					print '<figure>';
					print '<div class="dash_in notes" draggable="true" ondragstart="drag(event)" id='.$noteid.'>';
					print '<a href="'. $url .'">';
					print '<span class="right arrow">'.$obj->priority.'</span>';
					if (!empty($obj->label)) {
						print '<div class="title">';
						print $proSiglasMay." - ".$obj->label;
						print '</div>';
					}
					print '</a>';
					print '<div class="note_truncate truncate-text">';
					print $obj->note;
					print '</div>';
					print '<hr>';

					$userB = $obj->fk_user;

					$sql = "SELECT u.rowid, firstname, lastname FROM ".MAIN_DB_PREFIX."user u ";
					$sql.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
					$sql.= "ON u.rowid = nu.iduser ";
					$sql.= "WHERE nu.idnote = ".$noteid."";

					$resultado = $db->query($sql);
					while ($campo = $db->fetch_object($resultado)) {

						// Para generar color aleatorio
						$coloresArcoiris = array("1" => '#F80808', "2" => '#003AFF', "3" => '#FE75D6', "4" => '#149030', "5" => '#FFAC43', "6" => '#EDD90E', "7" => '#8EE40A', "8" =>'#43CEBB', "9" => '#8700FF', "10" => '#000000'); // Colores del arco iris en orden
						if ($campo->rowid > count($coloresArcoiris)) {
							$color = $coloresArcoiris[3];
						} else {
							$color = $coloresArcoiris[$campo->rowid];
						}

						$nombre = substr($campo->firstname, 0, 1);
						$apellido = substr($campo->lastname, 0, 1);

						print '<label class="note_user" style="background-color:'.$color.';border:1px solid black">'.$nombre.''.$apellido.'</label>';


					}

					// Botones de acción
					print '<a class="fas fa-pencil-alt edit" style=" color: #444;" title="Modificar" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $noteid . '&userCh='.$usuarioE.'&noteCh='.$notaE.'"></a>';
					print '<a class="fas fa-trash pictodelete" style="" title="Eliminar" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $noteid . '&userCh='.$usuarioE.'&noteCh='.$notaE.'"></a>';
					print '<a class="fas fa-clock pictodelete" style="" title="Imputar tiempo" href="' . $_SERVER["PHP_SELF"] . '?action=add&id=' . $noteid . '&userCh='.$usuarioE.'&noteCh='.$notaE.'"></a>';
					print '</div>';
					print '</figure>';

				}

				$i++;

			}

			print '</div>';
			print '</div>';
		}

		print '</div>';

	} else {

		// TAREAS ELIMINADAS

		if ($user->admin) {

			print '<div class="wrapper">';

			$sql1 = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota as t ";
			$sql1.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.deleted IS NOT NULL ";
			$sql1.= "ORDER BY deleted DESC";
			$result = $db->query($sql1);
			$nbtotalofrecords = $db->num_rows($result);

				print '<div class="dash_in" ondrop="drop(event)" ondragover="allowDrop(event)">';
				print '<label class="category_name">Cerradas</label>';
				print '<hr>';
				print '<div class="dashboard_column2">';

				// Código para mostrar las notas
				$i = 0;
				while ($i < $nbtotalofrecords) {

					$obj = $db->fetch_object($result);

					$sqlPro = "SELECT p.title FROM ".MAIN_DB_PREFIX."projet as p ";
					$sqlPro.= "WHERE p.rowid = ".$obj->fk_project."";
					$resul = $db->query($sqlPro);
					$proyectof = $db->fetch_object($resul);

					$proSiglas = substr($proyectof->title, 0, 3);
					$proSiglasMay = strtoupper($proSiglas);

					$noteid = $obj->rowid;
					$url = 'nota_card.php?id='.$noteid;

					print '<figure>';
					print '<div class="dash_in notes" style="width:210px" draggable="true" ondragstart="drag(event)" id='.$noteid.'>';
					print '<a href="'. $url .'">';
					print '<span class="right arrow">'.$obj->priority.'</span>';
					if (!empty($obj->label)) {
						print '<div class="title">';
						print $proSiglasMay." - ".$obj->label;
						print '</div>';
					}
					print '</a>';
					print '<div class="note_truncate">';
					print $obj->note;
					print '</div>';
					print '<hr>';

					$userB = $obj->fk_user;

					$sql = "SELECT u.rowid, firstname, lastname FROM ".MAIN_DB_PREFIX."user u ";
					$sql.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
					$sql.= "ON u.rowid = nu.iduser ";
					$sql.= "WHERE nu.idnote = ".$noteid."";

					$resultado = $db->query($sql);
					while ($campo = $db->fetch_object($resultado)) {

						// Para generar color aleatorio
						$coloresArcoiris = array("1" => '#F80808', "2" => '#003AFF', "3" => '#FE75D6', "4" => '#149030', "5" => '#FFAC43', "6" => '#EDD90E', "7" => '#8EE40A', "8" =>'#43CEBB', "9" => '#8700FF', "10" => '#000000'); // Colores del arco iris en orden
						if ($campo->rowid > count($coloresArcoiris)) {
							$color = $coloresArcoiris[3];
						} else {
							$color = $coloresArcoiris[$campo->rowid];
						}

						$nombre = substr($campo->firstname, 0, 1);
						$apellido = substr($campo->lastname, 0, 1);

						print '<label class="note_user" style="background-color:'.$color.';border:1px solid black">'.$nombre.''.$apellido.'</label>';

					}

					// Botones de acción
					print '<a class="fas fa-backward" style=" color: #444;" title="Recuperar nota" href="' . $_SERVER["PHP_SELF"] . '?action=recover&id=' . $noteid . '&delTasks"></a>';
					print '</div>';
					print '</figure>';

					$i++;

				}

				print '</div>';
				print '</div>';

			print '</div>';

		}

	}

// Si no eres admin
// Solo muestra TUS tareas asignadas. No se pueden tampoco borrar ni editar
} else {

	$sqlCategory = "SELECT * FROM ".MAIN_DB_PREFIX."notas_nota_categories ORDER BY rowid DESC";
	$resultCategory = $db->query($sqlCategory);

	print '<div class="wrapper">';

	while( $data = $db->fetch_object($resultCategory) ){

		$idUsuarioElegido = $notes_user;

		$sqlBusc = "SELECT u.rowid FROM ".MAIN_DB_PREFIX."user u WHERE rowid = '".$idUsuarioElegido."'";
		$result = $db->query($sqlBusc);
		$idBusc = $db->fetch_object($result);
		$idUser = $idBusc->rowid;

		$sql2 = "SELECT t.* FROM ".MAIN_DB_PREFIX."notas_nota as t ";
		$sql2.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
		$sql2.= "ON t.rowid = nu.idnote ";
		$sql2.= "WHERE (fk_user_creat='$notes_user' OR fk_user IS NULL OR fk_user=0) AND t.category=". $data->rowid ." AND nu.iduser = ".$idUser." ";
		$sql2.= "ORDER BY priority ";
		$result = $db->query($sql2);
		$nbtotalofrecords = $db->num_rows($result);

		print '<div class="dash_in" ondrop="drop(event)" ondragover="allowDrop(event)">';
		print '<label class="category_name">'. $data->name .'</label>';
		print '<hr>';
		print '<div class="dashboard_column">';

		// Código para mostrar las notas
		$i = 0;
		while ($i < $nbtotalofrecords) {
			$obj = $db->fetch_object($result);

			if ($obj->deleted == "") {

				$sqlPro = "SELECT p.title FROM ".MAIN_DB_PREFIX."projet as p ";
				$sqlPro.= "WHERE p.rowid = ".$obj->fk_project."";
				$resul = $db->query($sqlPro);
				$proyectof = $db->fetch_object($resul);

				$proSiglas = substr($proyectof->title, 0, 3);
				$proSiglasMay = strtoupper($proSiglas);

				$noteid = $obj->rowid;
				$url = 'nota_card.php?id='.$noteid;

				print '<figure>';
				print '<div class="dash_in notes" draggable="true" ondragstart="drag(event)" id='.$id.'>';
				print '<a href="'. $url .'">';
				print '<span class="right arrow">'.$obj->priority.'</span>';
				if (!empty($obj->label)) {
					print '<div class="title">';
					print $proSiglasMay." - ".$obj->label;
					print '</div>';
				}
				print '</a>';
				print '<div class="note_truncate">';
				print $obj->note;
				print '</div>';
				print '<hr>';

				$userB = $obj->fk_user;

				$sql = "SELECT u.rowid, firstname, lastname FROM ".MAIN_DB_PREFIX."user u ";
				$sql.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
				$sql.= "ON u.rowid = nu.iduser ";
				$sql.= "WHERE nu.idnote = ".$noteid."";

				//Para generar color aleatorio
				$resultado = $db->query($sql);
				while ($campo = $db->fetch_object($resultado)) {

					// Para generar color aleatorio
					$coloresArcoiris = array("1" => '#F80808', "2" => '#003AFF', "3" => '#FE75D6', "4" => '#149030', "5" => '#FFAC43', "6" => '#EDD90E', "7" => '#8EE40A', "8" =>'#43CEBB', "9" => '#8700FF', "10" => '#000000'); // Colores del arco iris en orden
					if ($campo->rowid > count($coloresArcoiris)) {
						$color = $coloresArcoiris[3];
					} else {
						$color = $coloresArcoiris[$campo->rowid];
					}

					$nombre = substr($campo->firstname, 0, 1);
					$apellido = substr($campo->lastname, 0, 1);

					print '<label class="note_user" style="background-color:'.$color.';border:1px solid black">'.$nombre.''.$apellido.'</label>';


				}

				// Botones de acción
				print '<a class="fas fa-pencil-alt edit" style=" color: #444;" title="Modificar" href="' . $_SERVER["PHP_SELF"] . '?action=edit2&id=' . $noteid . '"></a>';
				print '<a class="fas fa-clock pictodelete" style="" title="Imputar tiempo" href="' . $_SERVER["PHP_SELF"] . '?action=add&id=' . $noteid . '"></a>';
				print '</div>';
				print '</figure>';

			}

			$i++;
		}

		print '</div>';
		print '</div>';
	}

	print '</div>';

}

// Para editar nota
if ($_GET["action"] == "edit") {

	$id = $_GET['id'];

	// LISTA DE CATEGORÍAS
	$sqlLista = "SELECT * FROM " . MAIN_DB_PREFIX . "notas_nota_categories ";
	$categorias = $db->query($sqlLista);

	// CATEGORÍA DE LA NOTA
	$sqlCat = "SELECT c.rowid FROM " . MAIN_DB_PREFIX . "notas_nota_categories c ";
	$sqlCat.= "INNER JOIN " . MAIN_DB_PREFIX . "notas_nota n ";
	$sqlCat.= "ON c.rowid = n.category ";
	$sqlCat.= "AND n.rowid = ".$id;
	$catElegida = $db->query($sqlCat);
	$cat = $db->fetch_object($catElegida);

	// TÍTULO DE LA NOTA
	$sqlDatos = "SELECT label, note, priority FROM " . MAIN_DB_PREFIX . "notas_nota ";
	$sqlDatos.= "WHERE rowid = ".$id;
	$datos = $db->query($sqlDatos);
	$dat = $db->fetch_object($datos);

	// LISTA USUARIOS
	$sqlRights = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
	$sqlRights.= "WHERE module LIKE 'notas' ";
	$sqlRights.= "LIMIT 1";
	$result = $db->query($sqlRights);
	$idRight = $db->fetch_object($result);

	// PERMISOS DE GRUPO TELETRABAJO
	$sqlGroup = "SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_rights ";
	$sqlGroup.= "WHERE fk_id = ".$idRight->id."";
	
	$result = $db->query($sqlGroup);
	$group = $db->fetch_object($result);

	$sqlUs = "SELECT u.rowid, u.firstname, u.lastname FROM ".MAIN_DB_PREFIX."user u ";
	$sqlUs.= "INNER JOIN ".MAIN_DB_PREFIX."usergroup_user gu ";
	$sqlUs.= "ON u.rowid = gu.fk_user ";
	$sqlUs.= "WHERE gu.fk_usergroup = ".$group->fk_usergroup."";
	$listUsuarios = $db->query($sqlUs);

	// USUARIOS DE LA NOTA
	$sqlUsuarios = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user u ";
	$sqlUsuarios.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota_user nu ";
	$sqlUsuarios.= "ON u.rowid = nu.iduser ";
	$sqlUsuarios.= "WHERE nu.idnote = ".$id."";
	$datos = $db->query($sqlUsuarios);

	// PROYECTO DE LA NOTA
	$sqlPro = "SELECT p.rowid, p.title FROM " . MAIN_DB_PREFIX . "projet p ";
	$sqlPro.= "INNER JOIN ".MAIN_DB_PREFIX."notas_nota n ";
	$sqlPro.= "ON p.rowid = n.fk_project ";
	$sqlPro.= "WHERE n.rowid = ".$id."";
	$datos = $db->query($sqlPro);
	$proNota = $db->fetch_object($datos);

	// LISTA DE PROYECTOS
	$sqlListaPro = "SELECT * FROM " . MAIN_DB_PREFIX . "projet ";
	$listaPro = $db->query($sqlListaPro);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&userCh='.$usuarioE.'&noteCh='.$notaE.'&labelA='.$dat->label.'" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Nota</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 290.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="field">Título</span>
							</td>
							<td>
								<input type="text" name="tit" value="'.$dat->label.'">
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Descripción</span>
							</td>
							<td>
								<textarea name="desc" rows=3 cols=35>'.$dat->note.'</textarea>
							</td>
						</tr>
						<tr>
						<td>
							<span class="field">Prioridad</span>
						</td>
						<td>
							<select class="select-priority" name="prioridad">';

							$arrayPrio = array("1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "10" => 10);

							foreach ($arrayPrio as $clave => $valor) {
								print '<option value='.$clave.' ';

								if ($valor == $dat->priority) {
									print ' selected ';
								}

								print '>'.$valor.'</option>';

							}

						print '</select>
						</td>
						</tr>
						<tr>
						<td>
							<input type="hidden" name="proyecto" value="'.$proNota->rowid.'">
						</td>
					</tr>
						<tr>
							<td>
								<span class="fieldrequired">Categoría</span>
							</td>
							<td>
								<select class="select-category" style="width: 200px" name="category" id="">';
								while ($categoria = $db->fetch_object($categorias)) {

									if ($categoria->rowid==$cat->rowid) {

										print ' <option selected value="' . $categoria->rowid . '">' . $categoria->name . '</option>';

									}else{

										print ' <option value="' . $categoria->rowid . '">' . $categoria->name . '</option>';
									}
								}
								print '
								</select>
							</td>
						</tr>
						<tr>
						<td>
							<span class="fieldrequired">Usuarios asignados</span>
						</td>
						<td>';

							$sql = "SELECT iduser FROM " . MAIN_DB_PREFIX . "notas_nota_user ";
							$sql.= "WHERE idnote = ".$id."";
							$result = $db->query($sql);

							$arrayIds = array();

							while ($userB = $db->fetch_object($result)) {
								$arrayIds[] = $userB->iduser;
							}

							// PARA CREAR EL ARRAY DE IDS QUE NO DEBE MOSTRAR
							$sqlRights = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
							$sqlRights.= "WHERE module LIKE 'notas' ";
							$sqlRights.= "LIMIT 1";
							$result = $db->query($sqlRights);
							$idRight = $db->fetch_object($result);

							$sqlListaUsu = "SELECT u.* FROM " . MAIN_DB_PREFIX . "user u ";
							$sqlListaUsu.= "LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user ug ";
							$sqlListaUsu.= "ON u.rowid = ug.fk_user ";
							$sqlListaUsu.= "AND ug.fk_usergroup = ".$group->fk_usergroup." ";
							$sqlListaUsu.= "WHERE ug.fk_user IS NULL ";
							$listUsuarios = $db->query($sqlListaUsu);

							$arrayIdsExc = array();

							while ($userExc = $db->fetch_object($listUsuarios)) {
								$arrayIdsExc[] = $userExc->rowid;
							}

							print $form->select_dolusers($arrayIds, 'usuarios', 0, $arrayIdsExc, 0, '', '', 0, 0, 0, '', 0, '', $val['css'], 0, 0, true);

							print '

						</td>
					</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="edit">
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

// Si pulsamos en confirmar edición
if (isset($_POST['edit'])) {
	$id = $_GET['id'];
	$titulo = $_POST['tit'];
	$desc = $_POST['desc'];
	$category = $_POST['category'];
	$usuarios = $_POST['usuarios'];
	$prioridad = $_POST['prioridad'];
	$proyecto = $_POST['proyecto'];
	$labelA = $_GET['labelA'];

	if ($usuarios == "" ) {

		$message = "Selecciona al menos un usuario";

		setEventMessage($message, 'errors');

	} else {

		// Borrado e inserción en tabla de usuarios de notas
		$db->begin();
		try {

			$sqlBorrado = "DELETE FROM " . MAIN_DB_PREFIX . "notas_nota_user ";
			$sqlBorrado.= "WHERE idnote = ".$id;

			$resultBorrado = $db->query($sqlBorrado);

			if (!$resultBorrado) {
				throw new Exception ("Error en la edición del usuario de la nota");
			}

			foreach ($usuarios as $clave => $valor) {
				$sqlInsert = "INSERT INTO " . MAIN_DB_PREFIX . "notas_nota_user ";
				$sqlInsert.= "(rowid, ";
				$sqlInsert.= "idnote, ";
				$sqlInsert.= "iduser) ";
				$sqlInsert.= "VALUES ";
				$sqlInsert.= "(NULL, ";
				$sqlInsert.= "".$id.", ";
				$sqlInsert.= "".$valor.")";

				$resultInsert = $db->query($sqlInsert);

				if (!$resultInsert) {
					throw new Exception ("Error en la edición del usuario de la nota");
				}

			}

			if ($proyecto == 0) {
				$proyecto = "NULL";
			}

			// Actualización tabla nota
			$sqlEdit = "UPDATE " . MAIN_DB_PREFIX . "notas_nota ";
			$sqlEdit.= "SET label = '".$titulo."', note = '".$desc."', category = '".$category."', priority = ".$prioridad.", fk_project = ".$proyecto." ";
			$sqlEdit.= "WHERE rowid = ".$id;

			$resultEdit = $db->query($sqlEdit);

			if (!$resultEdit) {
				throw new Exception ("Error en la edición de la nota");
			}

			$sqlEditTask = "UPDATE " . MAIN_DB_PREFIX . "projet_task ";
			$sqlEditTask.= "SET label = '".$titulo."', ";
			$sqlEditTask.= "description = '".$desc."' ";
			$sqlEditTask.= "WHERE fk_projet = ".$proyecto." ";
			$sqlEditTask.= "AND label = '".$labelA."'";

			$resultEditTask = $db->query($sqlEditTask);

			if (!$resultEditTask) {
				throw new Exception ("Error en la edición de la tarea");
			}


			// PARA EDITAR LOS CONTACTOS DE LA TAREA
			// Obtenemos el ID de la tarea, y hacemos el proceso
			$sql = "SELECT pt.rowid ";
			$sql.= "FROM " . MAIN_DB_PREFIX . "notas_nota nn ";
			$sql.= "JOIN " . MAIN_DB_PREFIX . "projet p ON nn.fk_project = p.rowid ";
			$sql.= "JOIN " . MAIN_DB_PREFIX . "projet_task pt ON p.rowid = pt.fk_projet ";
			$sql.= "WHERE nn.rowid = ".$id." ";
			$sql.= "AND pt.label = nn.label ";

			$result = $db->query($sql);
			$datos = $db->fetch_object($result);

			$idTarea = $datos->rowid;

			$sqlRespo = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_type_contact ";
			$sqlRespo.= "WHERE source = 'internal' ";
			$sqlRespo.= "AND code LIKE 'TASKEXECUTIVE'";

			$resultRespo = $db->query($sqlRespo);
			$datos2 = $db->fetch_object($resultRespo);

			$sqlBorrado = "DELETE FROM " . MAIN_DB_PREFIX . "element_contact ";
			$sqlBorrado.= "WHERE element_id = ".$idTarea." ";
			$sqlBorrado.= "AND fk_c_type_contact = ".$datos2->rowid."";

			$result = $db->query($sqlBorrado);

			if (!$result) {
				throw new Exception ("Error en la edición de los contactos de la tarea");
			}

			$fecha = date("Y-m-d H:i:s");

			foreach ($usuarios as $clave => $valor) {
				$sqlInsert = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact ";
				$sqlInsert.= "(datecreate, ";
				$sqlInsert.= "statut, ";
				$sqlInsert.= "element_id, ";
				$sqlInsert.= "fk_c_type_contact, ";
				$sqlInsert.= "fk_socpeople) ";
				$sqlInsert.= "VALUES ";
				$sqlInsert.= "('".$fecha."', ";
				$sqlInsert.= "4, ";
				$sqlInsert.= "".$idTarea.", ";
				$sqlInsert.= "180, ";
				$sqlInsert.= "".$valor.")";

				$resultInsert = $db->query($sqlInsert);

				if (!$resultInsert) {
					throw new Exception ("Error en la edición de los contactos de la tarea");
				}

			}

		} catch (Exception $e) {

			$db->rollback();
			setEventMessage($e->getMessage(), "errors");

		}

		$db->commit();

		$destination_url = 'notasindex.php?userCh='.$usuarioE.'&noteCh='.$notaE.'';

		print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

		//header('Location: notasindex.php?userCh='.$usuarioE.'&noteCh='.$notaE.'');
	}

}


// Para editar nota sin serad min
if ($_GET["action"] == "edit2") {

	$id = $_GET['id'];

	// LISTA DE CATEGORÍAS
	$sqlLista = "SELECT * FROM " . MAIN_DB_PREFIX . "notas_nota_categories ";
	$categorias = $db->query($sqlLista);

	// CATEGORÍA DE LA NOTA
	$sqlCat = "SELECT c.rowid FROM " . MAIN_DB_PREFIX . "notas_nota_categories c ";
	$sqlCat.= "INNER JOIN " . MAIN_DB_PREFIX . "notas_nota n ";
	$sqlCat.= "ON c.rowid = n.category ";
	$sqlCat.= "AND n.rowid = ".$id;
	$catElegida = $db->query($sqlCat);
	$cat = $db->fetch_object($catElegida);

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Editar Nota</span>
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
								<span class="fieldrequired">Categoría</span>
							</td>
							<td>
								<select class="select-category" style="width: 200px" name="category" id="">';
								while ($categoria = $db->fetch_object($categorias)) {

									if ($categoria->rowid==$cat->rowid) {

										print ' <option selected value="' . $categoria->rowid . '">' . $categoria->name . '</option>';

									}else{

										print ' <option value="' . $categoria->rowid . '">' . $categoria->name . '</option>';
									}
								}
								print '
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="edit2">
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


// Si pulsamos en confirmar edición
if (isset($_POST['edit2'])) {
	$id = $_GET['id'];
	$category = $_POST['category'];

	$db->begin();
	try {

		$sqlEdit = "UPDATE " . MAIN_DB_PREFIX . "notas_nota ";
		$sqlEdit.= "SET category = ".$category." ";
		$sqlEdit.= "WHERE rowid = ".$id;

		$resultEdit = $db->query($sqlEdit);

		if (!$resultEdit) {
			throw new Exception ("Error en la edición de la categoría de la nota");
		}

	} catch (Exception $e) {

		$db->rollback();
		setEventMessage($e->getMessage(), "errors");

	}

	$db->commit();

	$destination_url = 'notasindex.php';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

	//header('Location: notasindex.php');

}



// Para borrar nota
if ($_GET["action"] == "delete") {
	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&userCh='.$usuarioE.'&noteCh='.$notaE.'" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Borrar Nota</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 90.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<input type="text" name="tit" value="¿Desea borrar esta nota?">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="Borrar">
						Borrar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}

// Si pulsamos en confirmar borrado
if (isset($_POST['Borrar'])) {

	$id = $_GET['id'];
	$ahora = date('Y-m-d H:i:s');

	$db->begin();
	try {

		$sqlUpd = "UPDATE " . MAIN_DB_PREFIX . "notas_nota ";
		$sqlUpd.= "SET deleted = '".$ahora."' ";
		$sqlUpd.= "WHERE rowid = ".$id;
		
		$resultUpd = $db->query($sqlUpd);

		if (!$resultUpd) {
			throw new Exception ("Error en el borrado de la nota");
		}

		$sqlClose = "SELECT pt.rowid FROM " . MAIN_DB_PREFIX . "projet_task pt ";
		$sqlClose.= "INNER JOIN " . MAIN_DB_PREFIX . "projet p ";
		$sqlClose.= "ON pt.fk_projet = p.rowid ";
		$sqlClose.= "INNER JOIN " . MAIN_DB_PREFIX . "notas_nota nn ";
		$sqlClose.= "ON p.rowid = nn.fk_project ";
		$sqlClose.= "WHERE nn.rowid = ".$id." ";
		$sqlClose.= "AND nn.label = pt.label ";

		$resultClose = $db->query($sqlClose);
		$closeId = $db->fetch_object($resultClose);

		$sqlCerrar = "UPDATE " . MAIN_DB_PREFIX . "projet_task ";
		$sqlCerrar.= "SET datee = '".$ahora."' ";
		$sqlCerrar.= "WHERE rowid = ".$closeId->rowid."";

		$resultCerrar = $db->query($sqlCerrar);

	} catch (Exception $e) {

		$db->rollback();
		setEventMessage($e->getMessage(), "errors");

	}

	$db->commit();

	$destination_url = 'notasindex.php?userCh='.$usuarioE.'&noteCh='.$notaE.'';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

	//header('Location: notasindex.php?userCh='.$usuarioE.'');

}

// Para imputar tiempos
if ($_GET["action"] == "add") {

	$id = $_GET['id'];

	$sql = "SELECT n.fk_project, p.rowid, p.title, p.ref FROM " . MAIN_DB_PREFIX . "notas_nota n ";
	$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "projet p ";
	$sql.= "ON n.fk_project = p.rowid ";
	$sql.= "WHERE n.rowid = ".$id."";
	$resultAdd = $db->query($sql);

	if ($db->num_rows($resultAdd) > 0) {

		$pro = $db->fetch_object($resultAdd);

		$sql1 = "SELECT pt.rowid, pt.label, pt.dateo, pt.datee, pt.fk_projet, pt.fk_task_parent, pt.description FROM " . MAIN_DB_PREFIX . "projet_task pt ";
		$sql1.= "INNER JOIN " . MAIN_DB_PREFIX . "notas_nota en ";
		$sql1.= "ON en.fk_project = pt.fk_projet ";
		$sql1.= "WHERE en.label = pt.label ";
		$sql1.= "AND pt.fk_projet = ".$pro->fk_project." ";
		$sql1.= "AND en.rowid = ".$id."";
		$result1 = $db->query($sql1);
		$tarea1 = $db->fetch_object($result1);

		$sql2 = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "projet_task pt ";
		$sql2.= "WHERE fk_projet = ".$tarea1->fk_projet."";
		$result2 = $db->query($sql2);

		// PARA LAS HORAS TOTALES IMPUTADAS EN ESA TAREA
		$sqlTotalH = "SELECT SUM(ptt.task_duration) AS total FROM " . MAIN_DB_PREFIX . "projet_task_time ptt ";
		$sqlTotalH.= "WHERE ptt.fk_task = ".$tarea1->rowid." ";
		$result = $db->query($sqlTotalH);
		$horasT = $db->fetch_object($result);

		$minT = $horasT->total / 60;

		// Para sacar horas y minutos
		$horas = floor($minT / 60);
		$minutosRest = $minT % 60;

		// PARA LAS HORAS DE HOY DE ESE USUARIO IMPUTADAS EN ESA TAREA
		$fechaActual = date("Y-m-d");

		$sqlTotalUserH = "SELECT SUM(ptt.task_duration) AS total FROM " . MAIN_DB_PREFIX . "projet_task_time ptt ";
		$sqlTotalUserH.= "WHERE ptt.fk_task = ".$tarea1->rowid." ";
		$sqlTotalUserH.= "AND ptt.fk_user = ".$user->id." ";
		$sqlTotalUserH.= "AND ptt.task_date = '".$fechaActual."'";
		$result = $db->query($sqlTotalUserH);
		$horasT2 = $db->fetch_object($result);

		$minT2 = $horasT2->total / 60;

		// Para sacar horas y minutos
		$horas2 = floor($minT2 / 60);
		$minutosRest2 = $minT2 % 60;

		print '
		<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&userCh='.$usuarioE.'&noteCh='.$notaE.'&idTask='.$tarea1->rowid.'" name="formfilter" autocomplete="off">
			<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
				<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
					<span id="ui-id-1" class="ui-dialog-title">Imputar tiempo en tarea</span>
					<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
						<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
						<span class="ui-button-icon-space"> </span>
						Close
					</button>
				</div>
				<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 300.928px;" class="ui-dialog-content ui-widget-content">
					<div class="confirmquestions">
					</div>
					<div class="">
						<table>
							<tr>
								<td>
									<span class="fieldrequired">Proyecto</span>
								</td>
								<td>
									<input type="text" name="projectT" value="'.$pro->ref.' - '.$pro->title.'" readonly style="width:260px">
								</td>
							</tr>
							<tr>
								<td>
									<span class="fieldrequired">Tarea</span>
								</td>
								<td>
									<input type="text" name="taskT" value="'.$tarea1->ref.' '.$tarea1->label.'" readonly style="width:260px">
								</td>
							</tr>
							<tr>
								<td>
									<span class="fieldrequired">Total imp.</span>
								</td>
								<td>
									<input type="text" name="taskT" value="'.$horas.' H : '.$minutosRest.' min" readonly>
								</td>
							</tr>
							<tr>
								<td>
									<span class="fieldrequired">Total imp. hoy por ti</span>
								</td>
								<td>
									<input type="text" name="taskT" value="'.$horas2.' H : '.$minutosRest2.' min" readonly>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<hr>
								</td>
							</tr>
							<tr>
								<td>
									<span class="field">Hora de inicio</span>
								</td>
								<td>
									<select class="flat valignmiddle maxwidth50" id="timehour" name="timehour">
									<option value="-1">&nbsp</option>';
										
									for ($i = 0; $i <= 23; $i ++) {

										if ($i < 10) {

											print "<option value=$i>0$i</option>";

										} else {

											print "<option value=$i>$i</option>";

										}

									}


							print '</select>
									:
									<select class="flat valignmiddle maxwidth50" id="timemin" name="timemin">
									<option value="-1">&nbsp</option>';
										
									for ($i = 0; $i <= 59; $i ++) {

										if ($i < 10) {

											print "<option value=$i>0$i</option>";

										} else {

											print "<option value=$i>$i</option>";

										}

									}


							print '</td>
							</tr>
							<tr>
								<td>
									<span class="fieldrequired">Tiempo dedicado</span>
								</td>
								<td>
									<input class="flat maxwidth50 inputhour" placeholder="H" type="number" min="0" name="horasT">
									<span class="hideonsmartphone">:</span>
									<span class="hideonsmartphone">&nbsp;</span>
									<input class="flat maxwidth50 inputminute" placeholder="min" type="number" min="0" name="minutosT">
								</td>
							</tr>
							<tr>
								<td>
									<span class="field">Nota</span>
								</td>
								<td>
									<textarea name="notaT" rows=3 cols=35></textarea>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
					<div class="ui-dialog-buttonset">
						<button type="submit" class="ui-button ui-corner-all ui-widget" name="add">
							Guardar
						</button>
						<button type="submit" class="ui-button ui-corner-all ui-widget">
							Salir
						</button>
					</div>
				</div>
			</div>
		</form>';

	} else {

		$message = "La nota no tiene ningún proyecto asociado";

		setEventMessage($message, 'errors');

	}

}

// Si pulsamos en confirmar imputar tiempo
if (isset($_POST['add'])) {

	$idTask = $_GET['idTask'];

	$fechaT = date("Y-m-d");

	$timehour = $_POST['timehour'];
	$timemin = $_POST['timemin'];

	if ($timehour == -1) {
		$timehour = 0;
	}

	if ($timemin == -1) {
		$timemin = 0;
	}

	// Fecha completa uniendo dia actual + hora de inicio + minuto de inicio

	$fecha_completa = sprintf("%s %02d:%02d", $fechaT, $timehour, $timemin);

	$notaT = $_POST['notaT'];
	$horasT = $_POST['horasT'];
	$minutosT = $_POST['minutosT'];

	if (($horasT == "") && ($minutosT == "")) {

		$message = "Para poder imputar tiempos debes introducir una cantidad de tiempo";

		setEventMessage($message, 'errors');

	} else {

		// Fecha sin hora

		$timestamp = strtotime($fechaT);
		$fechaSinHora = date('Y-m-d', $timestamp);

		// Duración total del tiempo imputado, en segundos

		if ($horasT == "") {
			$horasT = 0;
		}

		if ($minutosT == "") {
			$minutosT = 0;
		}

		$duracionTotal = (($horasT * 60) + $minutosT) * 60;

		// Inserción en la tabla de tiempos de las tareas
		$db->begin();
		try {

			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet_task_time ";
			$sql.= "(fk_task, ";
			$sql.= "task_date, ";
			$sql.= "task_datehour, ";
			$sql.= "task_date_withhour, ";
			$sql.= "task_duration, ";
			$sql.= "fk_user, ";
			$sql.= "note) ";
			$sql.= "VALUES ";
			$sql.= "(".$idTask.", ";
			$sql.= "'".$fechaT."', ";
			$sql.= "'".$fecha_completa."', ";
			$sql.= "'1', ";
			$sql.= "".$duracionTotal.", ";
			$sql.= "".$user->id.", ";
			$sql.= "'".$notaT."')";

			$resulInsert = $db->query($sql);

			if (!$resulInsert) {
				throw new Exception ("Error en la imputación de tiempo");
			}

			// Actualizar el tiempo total dedicado a la tarea

			$sql2 = "SELECT duration_effective FROM " . MAIN_DB_PREFIX . "projet_task ";
			$sql2.= "WHERE rowid = ".$idTask."";
			$result = $db->query($sql2);
			$datos = $db->fetch_object($result);

			$duracion = $datos->duration_effective;
			$duracion = $duracion + $duracionTotal;

			$sql3 = "UPDATE " . MAIN_DB_PREFIX . "projet_task ";
			$sql3.= "SET duration_effective = ".$duracion." ";
			$sql3.= "WHERE rowid = ".$idTask."";

			$resultUpdate = $db->query($sql3);

			if (!$resultUpdate) {
				throw new Exception ("Error en la imputación de tiempo total en la tarea");
			}

		} catch (Exception $e) {

			$db->rollback();
			setEventMessage($e->getMessage(), "errors");

		}

		$db->commit();

		$message = "Hora imputada correctamente";

		setEventMessage($message);

	}

}

// Para recuperar nota borrada
if ($_GET["action"] == "recover") {

	$id = $_GET['id'];

	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&delTasks" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Recuperar Nota</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 90.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<input type="text" name="tit" value="¿Recuperar esta nota?">
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="Recuperar">
						Recuperar
					</button>
					<button type="submit" class="ui-button ui-corner-all ui-widget">
						Salir
					</button>
				</div>
			</div>
		</div>
	</form>';

}

// Si pulsamos en confirmar recuperar nota borrada
if (isset($_POST['Recuperar'])) {

	$id = $_GET['id'];

	// Para recuperar una nota
	$db->begin();
	try {

		$sql = "UPDATE " . MAIN_DB_PREFIX . "notas_nota ";
		$sql.= "SET deleted = NULL ";
		$sql.= "WHERE rowid = ".$id."";

		$resulReco = $db->query($sql);

		if (!$resulReco) {
			throw new Exception ("Error en la recuperación de la nota");
		}

		$sqlTarea = "SELECT pt.rowid FROM " . MAIN_DB_PREFIX . "projet_task pt ";
		$sqlTarea.= "INNER JOIN " . MAIN_DB_PREFIX . "projet p ";
		$sqlTarea.= "ON pt.fk_projet = p.rowid ";
		$sqlTarea.= "INNER JOIN " . MAIN_DB_PREFIX . "notas_nota nn ";
		$sqlTarea.= "ON p.rowid = nn.fk_project ";
		$sqlTarea.= "WHERE nn.rowid = ".$id." ";
		$sqlTarea.= "AND nn.label = pt.label ";
		$resultTarea = $db->query($sqlTarea);
		$tareaId = $db->fetch_object($resultTarea);

		$sqlUpd2 = "UPDATE " . MAIN_DB_PREFIX . "projet_task ";
		$sqlUpd2.= "SET datee = NULL ";
		$sqlUpd2.= "WHERE rowid = ".$tareaId->rowid."";

		$resulUpd = $db->query($sqlUpd2);

	} catch (Exception $e) {

		$db->rollback();
		setEventMessage($e->getMessage(), "errors");

	}

	$db->commit();

	$destination_url = 'notasindex.php?delTasks';

	print '<meta http-equiv="refresh" content="0; url=' . $destination_url . '">';

}

if ($_GET['action'] == "reunion") {

	// NOMBRE PROYECTO: DELTANET - Tareas internas
	// NOMBRE TAREA: Reuniones y gestión interna

	// Para sacar el proyecto
	$sqlPro = "SELECT rowid, ref, title FROM " . MAIN_DB_PREFIX . "projet ";
	$sqlPro.= "WHERE title LIKE 'DELTANET - Tareas internas'";
	$resulPro = $db->query($sqlPro);
	$pro = $db->fetch_object($resulPro);

	// Para sacar la tarea
	$sqlTask = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "projet_task ";
	$sqlTask.= "WHERE fk_projet = ".$pro->rowid." ";
	$sqlTask.= "AND label LIKE 'Reuniones y gestión interna'";
	$resulTask = $db->query($sqlTask);
	$task = $db->fetch_object($resulTask);

	// PARA LAS HORAS TOTALES IMPUTADAS EN ESA TAREA
	$sqlTotalH = "SELECT SUM(ptt.task_duration) AS total FROM " . MAIN_DB_PREFIX . "projet_task_time ptt ";
	$sqlTotalH.= "WHERE ptt.fk_task = ".$task->rowid." ";
	$result = $db->query($sqlTotalH);
	$horasT = $db->fetch_object($result);

	$minT = $horasT->total / 60;

	// Para sacar horas y minutos
	$horas = floor($minT / 60);
	$minutosRest = $minT % 60;

	// PARA LAS HORAS DE HOY DE ESE USUARIO IMPUTADAS EN ESA TAREA
	$fechaActual = date("Y-m-d");

	$sqlTotalUserH = "SELECT SUM(ptt.task_duration) AS total FROM " . MAIN_DB_PREFIX . "projet_task_time ptt ";
	$sqlTotalUserH.= "WHERE ptt.fk_task = ".$task->rowid." ";
	$sqlTotalUserH.= "AND ptt.fk_user = ".$user->id." ";
	$sqlTotalUserH.= "AND ptt.task_date = '".$fechaActual."'";
	$result = $db->query($sqlTotalUserH);
	$horasT2 = $db->fetch_object($result);

	$minT2 = $horasT2->total / 60;

	// Para sacar horas y minutos
	$horas2 = floor($minT2 / 60);
	$minutosRest2 = $minT2 % 60;


	print '
	<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?idTask='.$task->rowid.'" name="formfilter" autocomplete="off">
		<div tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: auto; width: 500px; top: 230.503px; left: 600.62px; z-index: 101;">
			<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
				<span id="ui-id-1" class="ui-dialog-title">Imputar tiempo en reunión</span>
				<button type="submit" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">
					<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
					<span class="ui-button-icon-space"> </span>
					Close
				</button>
			</div>
			<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; height: 300.928px;" class="ui-dialog-content ui-widget-content">
				<div class="confirmquestions">
				</div>
				<div class="">
					<table>
						<tr>
							<td>
								<span class="fieldrequired">Proyecto</span>
							</td>
							<td>
								<input type="text" name="projectT" value="'.$pro->ref.' - '.$pro->title.'" readonly style="width:300px">
							</td>
						</tr>
						<tr>
							<td>
								<span class="fieldrequired">Tarea</span>
							</td>
							<td>
								<input type="text" name="taskT" value="'.$task->ref.' '.$task->label.'" readonly style="width:300px">
							</td>
						</tr>
						<tr>
							<td>
								<span class="fieldrequired">Total imp.</span>
							</td>
							<td>
								<input type="text" name="taskT" value="'.$horas.' H : '.$minutosRest.' min" readonly>
							</td>
						</tr>
						<tr>
							<td>
								<span class="fieldrequired">Total imp. hoy por ti</span>
							</td>
							<td>
								<input type="text" name="taskT" value="'.$horas2.' H : '.$minutosRest2.' min" readonly>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<hr>
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Hora de inicio</span>
							</td>
							<td>
								<select class="flat valignmiddle maxwidth50" id="timehour" name="timehour">
								<option value="-1">&nbsp</option>';
									
								for ($i = 0; $i <= 23; $i ++) {

									if ($i < 10) {

										print "<option value=$i>0$i</option>";

									} else {

										print "<option value=$i>$i</option>";

									}

								}


						print '</select>
								:
								<select class="flat valignmiddle maxwidth50" id="timemin" name="timemin">
								<option value="-1">&nbsp</option>';
									
								for ($i = 0; $i <= 59; $i ++) {

									if ($i < 10) {

										print "<option value=$i>0$i</option>";

									} else {

										print "<option value=$i>$i</option>";

									}

								}


						print '</td>
						</tr>
						<tr>
							<td>
								<span class="fieldrequired">Tiempo dedicado</span>
							</td>
							<td>
								<input class="flat maxwidth50 inputhour" placeholder="H" type="number" min="0" name="horasT">
								<span class="hideonsmartphone">:</span>
								<span class="hideonsmartphone">&nbsp;</span>
								<input class="flat maxwidth50 inputminute" placeholder="min" type="number" min="0" name="minutosT">
							</td>
						</tr>
						<tr>
							<td>
								<span class="field">Nota</span>
							</td>
							<td>
								<textarea name="notaT" rows=3 cols=35></textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
				<div class="ui-dialog-buttonset">
					<button type="submit" class="ui-button ui-corner-all ui-widget" name="addReu">
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

// Si pulsamos en confirmar imputar tiempo
if (isset($_POST['addReu'])) {

	$idTask = $_GET['idTask'];

	$fechaT = date("Y-m-d");

	$timehour = $_POST['timehour'];
	$timemin = $_POST['timemin'];

	if ($timehour == -1) {
		$timehour = 0;
	}

	if ($timemin == -1) {
		$timemin = 0;
	}

	// Fecha completa uniendo dia actual + hora de inicio + minuto de inicio

	$fecha_completa = sprintf("%s %02d:%02d", $fechaT, $timehour, $timemin);

	$notaT = $_POST['notaT'];
	$horasT = $_POST['horasT'];
	$minutosT = $_POST['minutosT'];

	if (($horasT == "") && ($minutosT == "")) {

		$message = "Para poder imputar tiempos debes introducir una cantidad de tiempo";

		setEventMessage($message, 'errors');

	} else {

		// Fecha sin hora

		$timestamp = strtotime($fechaT);
		$fechaSinHora = date('Y-m-d', $timestamp);

		// Duración total del tiempo imputado, en segundos

		if ($horasT == "") {
			$horasT = 0;
		}

		if ($minutosT == "") {
			$minutosT = 0;
		}

		$duracionTotal = (($horasT * 60) + $minutosT) * 60;

		// Inserción en la tabla de tiempos de las tareas
		$db->begin();
		try {

			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet_task_time ";
			$sql.= "(fk_task, ";
			$sql.= "task_date, ";
			$sql.= "task_datehour, ";
			$sql.= "task_date_withhour, ";
			$sql.= "task_duration, ";
			$sql.= "fk_user, ";
			$sql.= "note) ";
			$sql.= "VALUES ";
			$sql.= "(".$idTask.", ";
			$sql.= "'".$fechaT."', ";
			$sql.= "'".$fecha_completa."', ";
			$sql.= "'1', ";
			$sql.= "".$duracionTotal.", ";
			$sql.= "".$user->id.", ";
			$sql.= "'".$notaT."')";

			$resulInsert = $db->query($sql);

			if (!$resulInsert) {
				throw new Exception ("Error en la imputación de tiempo");
			}

			// Actualizar el tiempo total dedicado a la tarea
			$sql2 = "SELECT duration_effective FROM " . MAIN_DB_PREFIX . "projet_task ";
			$sql2.= "WHERE rowid = ".$idTask."";
			$result = $db->query($sql2);
			$datos = $db->fetch_object($result);

			$duracion = $datos->duration_effective;
			$duracion = $duracion + $duracionTotal;

			$sql3 = "UPDATE " . MAIN_DB_PREFIX . "projet_task ";
			$sql3.= "SET duration_effective = ".$duracion." ";
			$sql3.= "WHERE rowid = ".$idTask."";

			$resultUpdate = $db->query($sql3);

			if (!$resultUpdate) {
				throw new Exception ("Error en la imputación de tiempo total en la reunión");
			}

		} catch (Exception $e) {

			$db->rollback();
			setEventMessage($e->getMessage(), "errors");

		}

		$db->commit();

		$message = "Hora imputada correctamente";

		setEventMessage($message);

	}

}

// Scripts para los select
print "<script>

$(document).ready(function() {
	$('.select-priority').select2();
});
$(document).ready(function() {
	$('.select-project').select2();
});
$(document).ready(function() {
	$('.select-category').select2();
});
$(document).ready(function() {
	$('.select-parent').select2();
});

function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev) {
    ev.dataTransfer.setData('text', ev.target.id);
}

function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData('text');
    var draggedElement = document.getElementById(data);
    ev.target.appendChild(draggedElement);
    
    // Obtener el nombre de la categoría y el ID de la nota
    var categoryName = ev.target.closest('.dash_in').querySelector('.category_name').textContent;
    var notaId = ev.dataTransfer.getData('text');

    console.log(notaId);
    console.log(categoryName);

    // Enviar la nota a actualizar
    sendUpdateRequest(notaId, categoryName);
}

function sendUpdateRequest(notaId, categoryName) {
    // Realizar la solicitud AJAX
    $.ajax({
        url: 'actualizar_nota.php',
        method: 'POST',
        data: { notaId: notaId, categoryName: categoryName },
        success: function(response) {
            console.log(response);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
        }
    });
}
</script>";


$db->free($result);
ob_flush();
?>

</div>
