<?php
// page de resultat de recherchye de rom
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

session_start();

// defautl value
$pageno = isset($_GET['pageno']) ? $_GET['pageno'] : 1;

if (isset($_POST['new_search']) && $_POST['new_search']==1)
	$pageno = 1;

// build the session info
// init session key
foreach (array('rom_name','hide_clones','manufacturer','from_year','to_year','order_by','reverse_order','limit','pageno') as $key) {
	if (!isset($_SESSION[$key])) $_SESSION[$key] = '';
	if (isset($_POST[$key])) 	 $_SESSION[$key] = $_POST[$key];
	if (isset($_GET[$key])) 	 $_SESSION[$key] = $_GET[$key];
}

if ($_SESSION['from_year'] > $_SESSION['to_year']) {
	$tmp = $_SESSION['from_year'];
	$_SESSION['from_year'] = $_SESSION['to_year'];
	$_SESSION['to_year'] = $tmp;
	unset($tmp);
}

if (!isset($_SESSION['limit']) || $_SESSION['limit'] <= 0 || !is_numeric($_SESSION['limit']))
	$_SESSION['limit']=20;

if (!isset($_SESSION['order_by']) || $_SESSION['order_by'] == '')
	$_SESSION['order_by']='name';


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



?><html>
<head>
<title>Search results MAME games</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/app.css">
<link rel="stylesheet" type="text/css" href="css/search-bar.css">
<link rel="stylesheet" type="text/css" href="css/game.css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/app.js"></script>
</head>
<body>

<? include_once('search_bar.php'); ?>

<!-- RESULT LIST -->
<table id="results">
<?php
	// build SQL request	
	$tables = 'games';
	$fields = '*';

	$where = array();

	// rom or game name
	if (strlen($_SESSION['rom_name'])>0) {
		$value_escape = sqlite_escape_string($_SESSION['rom_name']);
		array_push($where,"(games.name LIKE '%$value_escape%' OR games.description LIKE '%$value_escape%')");
	}

	//hide clones
	if ($_SESSION['hide_clones'])
		array_push($where,"games.cloneof=''");

	// manufacturer
	if (strlen($_SESSION['manufacturer'])>0)
		array_push($where,"games.manufacturer = '".sqlite_escape_string($_SESSION['manufacturer'])."'");

	// year from
	if (is_numeric($_SESSION['from_year']))
		array_push($where,"games.year >= '".sqlite_escape_string($_SESSION['from_year'])."'");

	// year to
	if (is_numeric($_SESSION['to_year']))
		array_push($where,"games.year <= '".sqlite_escape_string($_SESSION['to_year'])."'");

	// join where clause
	$where = join(' AND ',$where);
	$where = $where ? " WHERE $where" : '';

	// order
	$order_by = sqlite_escape_string($_SESSION['order_by']).' ';
	
	// order orientation
	$order_by .= $_SESSION['reverse_order'] ? ' DESC ': ' ASC ';

	// limit
	$limit = ($pageno - 1) * $_SESSION['limit'] .",$_SESSION[limit] ";

	$sql = <<<EOT
SELECT $fields
FROM $tables
$where
ORDER BY $order_by
LIMIT $limit
EOT;

	$sql = preg_replace('/ +LIMIT +\d+ *, *\d+ *$/i','',$sql);
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
		$sql_count = preg_replace('/^ *SELECT\s+.+\s+FROM\s+/i','SELECT count(*) as nb_rows FROM ',$sql);
		$sql_count = preg_replace('/ *ORDER\s+BY\s+.+\s+(?:ASC|DESC)/i',' ',$sql_count);
		$sql_count = preg_replace('/ *LIMIT +\d+ *, *\d+ */i',' ',$sql_count);
		$res_count = $database->query($sql_count) or die("Unable to query1 ($sql_count) database : ".array_pop($database->errorInfo()));
		$row_count = $res_count->fetch(PDO::FETCH_ASSOC);
		$row_count = $row_count['nb_rows'];
		$lastpage  = ceil($row_count / $_SESSION['limit']);

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

<span class="nombre"><?=$row_count?></span> game<?=$row_count>1?'s':''?>

<!-- menu déroulant pour accedez à la page de son choix -->
<select name="pageno" onchange="change_page(this);">
<?	for($i=1 ; $i<=$lastpage ; $i++) { ?>
		<option value="<?=$i?>"<?= $i==$pageno?' selected':'' ?>>Page <?=$i?></option>
<?	} ?>
</select>

of <span class="nombre"><?=$lastpage?></span>
<? if ($pageno < $lastpage) { ?>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno + 1?>">NEXT&gt;</a>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$lastpage?>">LAST&gt;&gt;</a>
<? } ?>

<?	} ?>
</div>

<div id="suggest-manufacturer">
	<div class="header">
		Manufacturers
		<i id="close-suggest-manufacturer" class="fa fa-close fa-small" title="Close"></i>
	</div>
	<div class="suggest-container"></div>
</div>

<?php
/*
echo "<pre>$sql</pre>";
echo "<br/><br/>";
echo "<pre>$sql_count</pre>";
echo "<pre>_SESSION\n" ; print_r($_SESSION); echo "</pre>" ;
echo "<pre>_POST\n" ; print_r($_POST); echo "</pre>" ;
echo "<pre>_GET\n" ; print_r($_GET); echo "</pre>" ;
*/
?>

</body>
</html>