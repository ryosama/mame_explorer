<div id="footer">
<a href="https://github.com/ryosama/mame_explorer" target="_blank">Power by Mame explorer v1 (Github)</a> - 
<?php
$res = $database->query("SELECT * from get_last_version") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC);
?>
Based on MAME v<?=$row['version']?> (<?=$row['date_build']?>) -
<?php
	$mame_created = date_create("1997-02-05");
	$date_diff = date_diff($mame_created, date_create('now') );
	echo $date_diff->format('%Y years %m month %d days');
?> since MAME exists
</div>