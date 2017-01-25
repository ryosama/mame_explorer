<?php
// formulaire de recherche de rom

include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

// extract game info
//$res = $database->query("SELECT ".join(',',array_keys($fields)).",cloneof FROM games WHERE name='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
//$row = $res->fetch(PDO::FETCH_ASSOC);

?><html>
<head>
<title>Search MAME games</title>
<script type="text/javascript" src="js/jquery.js"></script>
<!-- pour le chosen -->
<script language="javascript" src="js/chosen/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="js/chosen/chosen.css" />

<style>
body {
	background-color:#336699;
	font-family:Verdana;
	font-size:11px;
}

select {
	color:black;
	background-color:#336699;
	color:black;
}

</style>

<script type="text/javascript">
// when page is ready
$(document).ready(function() {
	// plugin chosen
	$(".chzn-select").chosen();
});
</script>
</head>
<body>

<form name="rom_search" method="POST" action="search_results.php" target="game">

<input type="submit" value="Search"/>
<br/><br/>

<label for="rom_name">Rom or game name</label><br/>
<input type="text" name="rom_name" value="" /><br/>
<input type="hidden" name="hide_clones[]" value=""/>
<label for="hide_clones">Hide clones</label>&nbsp;<input type="checkbox" name="hide_clones[]" value="1"/>
<br/><br/>


<label for="manufacturer">Manufacturer</label><br/>
<select name="manufacturer[]" multiple="multiple" class="chzn-select" style="width:90%;">
<?php 	$res = $database->query("SELECT DISTINCT(manufacturer) as manufacturer FROM games ORDER BY manufacturer ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
		while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
			<option value="<?=$row['manufacturer']?>"><?=$row['manufacturer']?></option>
<?php	} ?>
</select>
<br/><br/>


<label for="from_year">From</label>
<select name="from_year">
<?php 	$res = $database->query("SELECT DISTINCT(year) as year FROM games ORDER BY year ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
		$i = 0;
		while($row = $res->fetch(PDO::FETCH_ASSOC)) {
			if ($row['year'] && strpos($row['year'],'?') === false) { ?>
				<option value="<?=$row['year']?>"<?=$i==0?' selected="selected"':''?>><?=$row['year']?></option>
<?php			$i++;
			}
		} ?>
</select>
<br/>
<label for="to_year">to</label>
<select name="to_year">
<?php 	$res = $database->query("SELECT DISTINCT(year) as year FROM games ORDER BY year ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
		$j = 0;
		while($row = $res->fetch(PDO::FETCH_ASSOC)) {
			if ($row['year'] && strpos($row['year'],'?') === false) { ?>
				<option value="<?=$row['year']?>"<?=$j+1==$i?' selected="selected"':''?>><?=$row['year']?></option>
<?php			$j++;
			}
		} ?>
</select>
<!--<br/>
<label for="unknown_year">Include unknown</label>&nbsp;<input type="checkbox" name="unknown_year"/>-->
<br/><br/>


<br/><br/>
<label for="order_by">Order by</label><br/>
<select name="order_by">
	<option value="name">Name</option>
	<option value="description">Description</option>
	<option value="year">Year</option>
	<option value="manufacturer">Manufacturer</option>
	<option value="cloneof">Parent</option>
</select>
<input type="hidden" name="reverse_order[]" value=""/>
<label for="reverse_order">Reverse order</label>&nbsp;<input type="checkbox" name="reverse_order[]" value="1"/>


<br/><br/>
<label for="limit">Results per page</label>&nbsp<input type="text" name="limit" value="20" size="3"/>

</form>

</body>
</html>