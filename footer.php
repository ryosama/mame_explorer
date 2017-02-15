<div id="footer">

<!-- LINK TO STATS PAGE -->
<span id="stats-link">
	<a href="stats.php">Statistics</a> - 
</span>

<!-- LINK TO GITHUB -->
<span id="github-link">
	<a href="https://github.com/ryosama/mame_explorer" target="_blank">Power by Mame explorer (Github)</a> - 
</span>

<!-- LAST MAME version -->
<?php if ($res = $database->query("SELECT date_build, version from get_last_version")) {
		$row = $res->fetch(PDO::FETCH_ASSOC); ?>
		<span id="mame-last-build">
		Based on MAME v<?=$row['version']?> (<?=$row['date_build']?>) -
		</span>
<?php } ?>

<!-- MAME CREATION DATE -->
<?php
	if ($res = $database->query("SELECT date_build from get_first_version")) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$mame_created = date_create($row['date_build']);
		$date_diff = date_diff($mame_created, date_create('now') );
?>
		<span id="mame-anniversary">
			<?=$date_diff->format('%Y years %m month %d days')?> since MAME exists
		</span>
<?php } ?>

</div>