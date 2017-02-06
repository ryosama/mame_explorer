<div id="footer">
Power by Mame explorer v1 -
<a href="https://github.com/ryosama/mame_explorer" target="_blank">Sources on Github</a> -
Page generated in <?=sprintf('%0.4f', microtime() - $page_start_timer)?> sec - 
<?php
	$mame_created = date_create("1997-02-05");
	$date_diff = date_diff($mame_created, date_create('now') );
	echo $date_diff->format('%Y years %m month %d days');
?> since MAME exists
</div>