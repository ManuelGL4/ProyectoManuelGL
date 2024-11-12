<?php
$notes_user = $user->id; //mine
$tuser = new User($db);
$maxlist = 300;

$qfilter = "";
if ($action=='project') {
	$sql = "SELECT DISTINCT t.* FROM ".MAIN_DB_PREFIX."projet as t
			LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ect ON ect.element_id = t.rowid
		WHERE ect.fk_c_type_contact IN (160,161) AND ect.fk_socpeople = ".$user->id."
		AND (LENGTH(note_private)>5 OR LENGTH(note_public)>5)
		ORDER BY t.datec DESC LIMIT $maxlist";

} else if ($action=='task') {
	$sql = "SELECT DISTINCT t.* FROM ".MAIN_DB_PREFIX."projet_task as t
			LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ect ON ect.element_id = t.rowid
		WHERE ect.fk_c_type_contact IN (180,181)  AND ect.fk_socpeople = ".$user->id."
		AND (LENGTH(note_private)>5 OR LENGTH(note_public)>5)
		ORDER BY t.datec DESC LIMIT $maxlist";
}


//
// echo $sql;
$result = $db->query($sql);
$nbtotalofrecords = $db->num_rows($result);
$projnote = new Project($db);
$tasknote = new Task($db);

if ($nbtotalofrecords>0) {
	print '<div class="dashboard_column">';


	$i = 0;
	while ($i<$nbtotalofrecords && $i<$maxlist) {
		$obj = $db->fetch_object($result);

		if ($action=='project') {

			$projnote->fetch($obj->rowid);
			$titletoshow = $projnote->getNomUrl(1, 'note', '1');
			if ($projnote->hasDelay()) {
				$titletoshow .=  img_warning("Late");
			}

		} else if ($action=='task') {

			$tasknote->id = $obj->rowid;
			$tasknote->ref = $obj->ref;
			$tasknote->label = $obj->label;
			$titletoshow = $tasknote->getNomUrl(1, 'withproject', 'note', 1);
		}


		?>
		<figure>
		<div class="dash_in notes">
			<div class='title' style='font-size: 1.1rem;'>
				<?php echo $titletoshow; ?>
			</div>

			<?php if (!empty($obj->note_public)) { ?>
			<b>Public:</b>
			<div class="note_truncate">
				<?php echo $obj->note_public; ?>
			</div>
			<?php } ?>

			<?php if (!empty($obj->note_public) && !empty($obj->note_private)) { ?>
			<hr>
			<?php } ?>

			<?php if (!empty($obj->note_private)) { ?>
			<b>Private:</b>
			<div class="note_truncate">
				<?php echo $obj->note_private; ?>
			</div>
			<?php } ?>

		</div>
		</figure>

		<?php
		$i++;
	}

	print '</div>'; //end dashboard_column

}

$db->free($result);

?>
</div>

