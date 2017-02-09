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
<span id="mame-last-build">
<?php
$res = $database->query("SELECT date_build, version from get_last_version");
$row = $res->fetch(PDO::FETCH_ASSOC);
?>
Based on MAME v<?=$row['version']?> (<?=$row['date_build']?>) -
</span>

<!-- MAME CREATION DATE -->
<span id="mame-anniversary">
<?php
	$res = $database->query("SELECT date_build from get_first_version");
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$mame_created = date_create($row['date_build']);
	$date_diff = date_diff($mame_created, date_create('now') );
	echo $date_diff->format('%Y years %m month %d days');
?> since MAME exists
</span>

</div>