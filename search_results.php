<?php
// page de resultat de recherchye de rom
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

session_start();

$pageno = isset($_GET['pageno']) ? $_GET['pageno'] : 1;

echo "<pre>_SESSION PREVIOUS\n" ; print_r($_SESSION); echo "</pre>" ; 
echo "<pre>_POST\n" ; print_r($_POST); echo "</pre>" ;
echo "<pre>_GET\n" ; print_r($_GET); echo "</pre>" ;

?><html>
<head>
<title>Search results MAME games</title>
<style>
body {
	background-color:#336699;
	font-family:Verdana;
	font-size:11px;
}

#results {
	border-spacing: 5px;
	border-collapse: collapse;
	color:white;
	width:100%;
}

th {
	border-top:solid 1px black;
	border-bottom:solid 1px black;
	text-align:left;
}

tr {
	text-align:left;
	background-color:#6699ff;
	height:32px;
	border-bottom:solid 1px grey;
	cursor:pointer;
}

tr:hover {
	text-shadow: black 3px 3px 5px;
}

tr:nth-child(2n) {
	background-color:#6699cc;
}

th:first-child,td:first-child {
	border-left:solid 1px black;
}

th:last-child,td:last-child {
	border-right:solid 1px black;
}

tr:last-child {
	border-bottom:solid 1px black;
}

div.pagination {
	margin-top:5px;
	text-align:center;
	color:white;
}

span.nombre {
	font-weight:bold;
}

div.pagination select {
	color:black;
}

div.pagination a {
	color:white;
	text-decoration:none;
	font-weight:bold;
}

</style>
<script type="text/javascript" src="js/jquery.js"></script>
<script language="javascript">
function goToGame(game) {
	document.location.href='game.php?name='+game;
}
</script>
</head>
<body>

<!-- RESULT LIST -->
<table id="results">
<?php

	// build the session info
	// init session key
	foreach (array('rom_name','hide_clones','manufacturer','from_year','to_year','order_by','limit','pageno') as $key) {
		if (!isset($_SESSION[$key])) $_SESSION[$key] = '';
		if (isset($_POST[$key])) 	 $_SESSION[$key] = $_POST[$key];
		if (isset($_GET[$key])) 	 $_SESSION[$key] = $_GET[$key];
	}

	// build checkbox
	foreach (array('hide_clones','reverse_order') as $checkbox) {
		if (!isset($_SESSION[$checkbox])) $_SESSION[$checkbox] = '';

		if (isset($_POST[$checkbox])) {
			$check = sizeof(array_unique($_POST[$checkbox]));
			if 		($check==2)
				$_SESSION[$checkbox] = true;
			elseif 	($check==1)
				$_SESSION[$checkbox] = false;
		}

		if (isset($_GET[$checkbox])) {
			$check = sizeof(array_unique($_GET[$checkbox]));
			if 		($check==2)
				$_SESSION[$checkbox] = true;
			elseif 	($check==1)
				$_SESSION[$checkbox] = false;
		}
	}

	echo "<pre>_SESSION AFTER\n" ; print_r($_SESSION); echo "</pre>" ; 

	// build SQL request
	$sql = "SELECT * FROM games";
	$where = array();

	// rom or game name
	if (strlen($_SESSION['rom_name'])>0) {
		$value_escape = sqlite_escape_string($_SESSION['rom_name']);
		array_push($where,"(name LIKE '%$value_escape%' OR description LIKE '%$value_escape%')");
	}

	//hide clones
	if ($_SESSION['hide_clones']) {
		array_push($where,"cloneof=''");
	}

	// manufacturer
	if (is_array($_SESSION['manufacturer'])) {
		$manufacturers = array();
		foreach ($_SESSION['manufacturer'] as $manufacturer) {
			array_push($manufacturers,"manufacturer='".sqlite_escape_string($manufacturer)."'");
		}
		array_push($where,'('.join(' OR ',$manufacturers).')');
	}

	// year from
	if (is_numeric($_SESSION['from_year'])) {
		array_push($where,"year >= '".sqlite_escape_string($_SESSION['from_year'])."'");
	}

	// year to
	if (is_numeric($_SESSION['to_year'])) {
		array_push($where,"year <= '".sqlite_escape_string($_SESSION['to_year'])."'");
	}

	// join where clause
	$where = join(' AND ',$where);
	$sql .= $where ? " WHERE $where" : '';

	// order
	if (strlen($_SESSION['order_by'])) {
		$order_by = ' ORDER BY '.sqlite_escape_string($_SESSION['order_by']).' ';

		if ($_SESSION['reverse_order']) {
			$order_by .= ' DESC ';
		} else {
			$order_by .= ' ASC ';
		}
	}

	$pageno = is_numeric($_SESSION['pageno']) ? $_SESSION['pageno'] : 0;

	// change order
	if (strlen($_SESSION['order_by'])) {
		$sql = preg_replace('/ +ORDER +BY +(.+?) +(ASC|DESC) *$/i'," ORDER BY $_SESSION[order_by] ",$sql);
		$pageno = 1; // reset page naviguation
	}

	// limit
	$row_per_page = 20; // default
	if (is_numeric($_SESSION['limit'])) {
		$rows_per_page = (int)$_SESSION['limit'];
		$sql = preg_replace('/ +LIMIT +\d+ *, *\d+ *$/i','',$sql);
		$sql .= ' LIMIT '.($pageno - 1) * $rows_per_page .",$rows_per_page ";
	}

	echo $sql;
?>
	<theader>
		<tr>
			<th>Icon</th>
			<th>Name</th>
			<th>Description</th>
			<th>Year</th>
			<th>Manufacturer</th>
			<th>Clone of</th>
		</tr>
	</theader>
	<tbody>
<?php
		$sql_count = preg_replace('/^ *SELECT +\* +FROM +games +/i','SELECT count(*) as nb_rows FROM games ',$sql);
		$sql_count = preg_replace('/ *LIMIT +\d+ *, *\d+ */i',' ',$sql_count);
		echo "<br/>\$sql_count=$sql_count<br/>";
		$res_count = $database->query($sql_count) or die("Unable to query1 ($sql_count) database : ".array_pop($database->errorInfo()));
		$row_count = $res_count->fetch(PDO::FETCH_ASSOC);
		$row_count = $row_count['nb_rows'];
		//echo "<br/>\$rows_per_page=$rows_per_page<br/>\$row_count :".$row_count;
		$lastpage  = ceil($row_count / $rows_per_page);

		// search for roms
		$res = $database->query($sql) or die("Unable to query2 ($sql) database : ".array_pop($database->errorInfo()));
		while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
			<tr onclick="goToGame('<?=$row['name']?>')">
				<td><!-- icon -->
					<?php	if (file_exists(MEDIA_PATH."/icons/$row[name].ico")) { ?>
								<img src="<?=MEDIA_PATH?>/icons/<?=$row['name']?>.ico" class="icon"/>
					<?php	} ?>
				</td>
				<td><?=$row['name']?></td>
				<td><?=$row['description']?></td>
				<td><?=$row['year']?></td>
				<td><?=$row['manufacturer']?></td>
				<td><?=$row['cloneof']?></td>
			</tr>
<?php	} // end while for each rom ?>
	</tbody>
</table>

<div class="pagination">
<? if ($row_count > 0) {
	if ($pageno > 1) { ?>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=1">&lt;&lt;FIRST</a>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno - 1?>">&lt;PREV</a>
<? } ?>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="nombre"><?=$row_count?></span> game<?=$row_count>1?'s':''?>&nbsp;&nbsp;&nbsp;

<!-- menu déroulant pour accedez à la page de son choix -->
<select name="pageno" onchange="change_page(this);">
<?	for($i=1 ; $i<=$lastpage ; $i++) { ?>
		<option value="<?=$i?>"<?= $i==$pageno?' selected':'' ?>>Page <?=$i?></option>
<?	} ?>
</select>

of <span class="nombre"><?=$lastpage?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<? if ($pageno < $lastpage) { ?>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno + 1?>">NEXT&gt;</a>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$lastpage?>">LAST&gt;&gt;</a>
<? } ?>

<?	} ?>
</div>

<script language="javascript">
function change_page(select_obj) {
	//alert('<?=$_SERVER['PHP_SELF']?>?pageno=' + select_obj[select_obj.selectedIndex].value + '&limit=<?=$rows_per_page?>&where=<?=base64_encode($where)?>');
	document.location.href='<?=$_SERVER['PHP_SELF']?>?pageno=' + select_obj[select_obj.selectedIndex].value ;
}
</script>

</body>
</html>