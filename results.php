<?php
// Result search page
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();
?><html>
<head>
<title>Search results MAME games</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/app.css">
<link rel="stylesheet" type="text/css" href="css/color.css">
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/app.js"></script>
</head>
<body>

<?php 	// eventualy include analytics
		if (file_exists('js/analyticstracking.js'))
			include_once('js/analyticstracking.js');
?>

<?php include_once('search_bar.php'); ?>

<!-- RESULT LIST -->
<?php
	$_SESSION['rom_name'] = trim($_SESSION['rom_name']);
	$params = [];

	// build SQL request	
	$tables = ['games LEFT JOIN softwarelist ON games.console=softwarelist.name'];
	$fields = ['games.*, softwarelist.description as softwarelist_description'];

	$where = [];

	// rom or game name
	if (strlen($_SESSION['rom_name'])>0) {

		$phrase = preg_split('/ +/',$_SESSION['rom_name']); // split words
		$and  = [];
		$mots = [];
		foreach ($phrase as $mot)
			if ($mot) {
				$and[]  = "games.description LIKE ?";
				$mots[] = '%'.$mot.'%';
			}
		
		$and = join(" AND ",$and);

		// CRC ?
		$crc = '';
		if (strlen($_SESSION['rom_name']) == 8 &&  ctype_xdigit($_SESSION['rom_name'])) { // maybe a CRC code
			$tables[] = "LEFT JOIN games_rom ON games.name=games_rom.game AND games.console=games_rom.console"; // add table rom
			$crc  = "OR (games_rom.crc = ?)";
		}

		$sha1 = '';
		if (strlen($_SESSION['rom_name']) == 40 &&  ctype_xdigit($_SESSION['rom_name'])) { // maybe a SHA1 code
			$tables[] = "LEFT JOIN games_rom ON games.name=games_rom.game AND games.console=games_rom.console"; // add table rom
			$crc  = "OR (games_rom.sha1 = ?)";
		}

		$where[]  = "((games.name LIKE ?) OR ($and) $crc)";
		$params[] = '%'.$_SESSION['rom_name'].'%';
		$params   = array_merge( $params, $mots );

		if (strlen($crc)>0)
			$params[] = strtolower($_SESSION['rom_name']);

		if (strlen($sha1)>0)
			$params[] = strtolower($_SESSION['rom_name']);
	}

	//hide clones
	if ($_SESSION['hide_clones'])
		$where[] = "games.cloneof is NULL";

	// manufacturer
	if (strlen($_SESSION['manufacturer'])>0) {
		$where[]  = "games.manufacturer LIKE ?";
		$params[] = $_SESSION['manufacturer'];
	}

	// year from
	if (is_numeric($_SESSION['from_year'])) {
		$where[]  = "games.year >= ?";
		$params[] = $_SESSION['from_year'];
	}

	// year to
	if (is_numeric($_SESSION['to_year'])) {
		$where[]  = "games.year <= ?";
		$params[] = $_SESSION['to_year'];
	}

	// sourcefile
	if (strlen($_SESSION['sourcefile'])>0) {
		$where[]  = "games.sourcefile = ?";
		$params[] = $_SESSION['sourcefile'];
	}

	// nplayers
	if (strlen($_SESSION['nplayers'])>0) {
		$tables[] = "LEFT JOIN nplayers ON games.name=nplayers.game"; // add table nplayers
		$where[]  = "nplayers.players = ?";
		$params[] = $_SESSION['nplayers'];
	}

	// nplayers
	if (strlen($_SESSION['categorie'])>0) {
		$tables[] = "LEFT JOIN categories ON games.name=categories.game"; // add table categories
		$where[]  = "categories.categorie = ?";
		$params[] = $_SESSION['categorie'];
	}

	// language
	if (strlen($_SESSION['language'])>0) {
		$tables[] = "LEFT JOIN games_languages ON games.name=games_languages.game LEFT JOIN languages ON games_languages.language_id=languages.id"; // add table language
		$where[]  = "languages.language = ?";
		$params[] = $_SESSION['language'];
	}

	// evaluation
	if (strlen($_SESSION['evaluation'])>0) {
		$tables[] = "LEFT JOIN bestgames ON games.name=bestgames.game"; // add table language
		$where[]  = "bestgames.evaluation = ?";
		$params[] = $_SESSION['evaluation'];
	}

	// mature
	if (strlen($_SESSION['mature'])>0 && $_SESSION['mature']=='on') {
		$tables[] = "INNER JOIN mature ON games.name=mature.game"; // add table mature
	}

	// genre
	if (strlen($_SESSION['genre'])>0) {
		$tables[] = "LEFT JOIN genre ON games.name=genre.game"; // add table genre
		$where[]  = "genre.genre = ?";
		$params[] = $_SESSION['genre'];
	}

	// console
	if (strlen($_SESSION['console'])>0) {
		$where[] = "games.console = ?";
		$params[] = $_SESSION['console'];
	}

	// only runnables
	$where[] = "games.runnable = '1'";

	// join where clause
	$where = join(' AND ',$where);
	$where = $where ? " WHERE $where " : '';

	// order
	if ( in_array($_SESSION['order_by'], ['name','description','year','console','manufacturer'] ) ) {
		$order_by = ' '.$_SESSION['order_by'].' ';
	} else {
		$order_by = ' name ';
	}
	
	// order orientation
	$order_by .= $_SESSION['reverse_order'] ? ' DESC ': ' ASC ';

	// limit
	if (is_numeric($_SESSION['limit'])) {
		$limit = ($pageno - 1) * $_SESSION['limit'] .",$_SESSION[limit] ";
	} else {
		$limit = ' 0,20 ';
	}

	$fields = join(',',$fields);
	$tables = join(' ',$tables);

	$sql = <<<EOT
SELECT $fields
FROM $tables
$where
ORDER BY $order_by
LIMIT $limit
EOT;

	//echo "<pre>$sql</pre>";
	//var_dump($params);

	$sql = preg_replace('/ +LIMIT +\d+ *, *\d+ *$/i','',$sql);

	$sql_count = preg_replace('/^ *SELECT\s+.+\s+FROM\s+/i','SELECT count(*) as nb_rows FROM ',$sql);
	$sql_count = preg_replace('/ *ORDER\s+BY\s+.+\s+(?:ASC|DESC)/i',' ',$sql_count);
	$sql_count = preg_replace('/ *LIMIT +\d+ *, *\d+ */i',' ',$sql_count);
	$res_count = $database->prepare($sql_count) or die("Unable to prepare query : ".array_pop($database->errorInfo()));
	$res_count->execute($params) or die("Unable to query1 ($sql_count) database : ".array_pop($database->errorInfo()));
	$row_count = $res_count->fetch(PDO::FETCH_ASSOC);
	$row_count = $row_count['nb_rows'];
	$lastpage  = ceil($row_count / $_SESSION['limit']);

	

	$res  = $database->prepare($sql) or die("Unable to prepare query : ".array_pop($database->errorInfo()));
	$res->execute($params) or die("Unable to query2 ($sql) database : ".array_pop($database->errorInfo()));
	$rows = $res->fetchAll();

	if ($row_count == 1) { // redirect to rom page
		$row = $rows[0];
?>
		<script type="text/javascript">
		$(document).ready(function(){
			// redirect to rom page
			goToGame('<?=$row['name']?>','<?=$row['console']?>');
		});
		</script>
<?php } ?>

<table id="results">
	<tr>
		<th class="icon">Icon</th>
		<th class="name">Name</th>
		<th class="description">Description</th>
		<th class="year">Year</th>
		<th class="console">System</th>
		<th class="manufacturer">Manufacturer</th>
		<th class="cloneof">Clone of</th>
	</tr>

<?php	// display each roms
	foreach ($rows as $row) {
		$lien = 'index.php?name='.urlencode($row['name']).'/'.urlencode($row['console']);
?>
		<tr>
			<td class="icon"><!-- icon -->
				<a href="<?=$lien?>">
<?php			if ($row['console']=='arcade' && file_exists(MEDIA_PATH."/icons/$row[name].ico")) { ?>
					<img src="<?=MEDIA_PATH?>/icons/<?=$row['name']?>.ico" class="icon"/>
<?php			} ?>
				</a>
			</td>
			<td class="name"><a href="<?=$lien?>"><?=$row['name']?></a></td>
			<td class="description"><a href="<?=$lien?>"><?=$row['description']?></a></td>
			<td class="year"><a href="<?=$lien?>"><?=$row['year']?></a></td>
			<td class="console" title="<?=$row['softwarelist_description']?>">
				<a href="<?=$lien?>">
<?php			if (file_exists("images/consoles/$row[console].png")) { ?>
					<img class="console-icon" src="images/consoles/<?=$row['console']?>.png"/>
<?php			} else { ?>
					<?=$row['console']?>
<?php 			} ?>
				</a>
			</td>
			<td class="manufacturer"><a href="<?=$lien?>"><?=$row['manufacturer']?></a></td>
			<td class="cloneof"><a href="<?=$lien?>"><?=$row['cloneof']?></a></td>
		</tr>
<?php } // end while for each rom ?>
</table>

<div class="pagination">
<?php if ($row_count > 0) {
	if ($pageno > 1) { ?>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=1">&lt;&lt;FIRST</a>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno - 1?>">&lt;PREV</a>
<?php } ?>

<span class="nombre"><?=$row_count?></span> game<?=$row_count>1?'s':''?>

<!-- menu déroulant pour accedez à la page de son choix -->
<select name="pageno" onchange="change_page(this);">
<?php 	for($i=1 ; $i<=$lastpage ; $i++) { ?>
			<option value="<?=$i?>"<?= $i==$pageno?' selected':'' ?>>Page <?=$i?></option>
<?php 	} ?>
</select>

of <span class="nombre"><?=$lastpage?></span>
<?php if ($pageno < $lastpage) { ?>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno + 1?>">NEXT&gt;</a>
	<a href="<?=$_SERVER['PHP_SELF']?>?pageno=<?=$lastpage?>">LAST&gt;&gt;</a>
<?php } 
} ?>
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

<?php include_once('footer.php'); ?>

</body>
</html>