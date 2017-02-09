<?php
// statistics page
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

?><html>
<head>
<title>Mame game : <?=$row_game['name']?> : <?=$row_game['description']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso8859-1">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/app.css">
<link rel="stylesheet" type="text/css" href="css/color.css">
<link rel="stylesheet" type="text/css" href="css/mobile.css">
</head>
<body>

<?php 	// eventualy include analytics
	if (file_exists('js/analyticstracking.js'))
		include_once('js/analyticstracking.js');
?>

<?php include_once('search_bar.php'); ?>

<!-- GAMES STATS -->
<div id="games_stats" class="infos">
<h2><a name="games_info">Games statistics</a></h2>
<?php
	$sql = <<<EOT
SELECT
	(SELECT count(*) FROM games) as total_games,
	(SELECT count(*) FROM games WHERE cloneof IS NULL) as parent_games,
	(SELECT count(*) FROM games WHERE cloneof IS NOT NULL) as clone_games
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
	$row_games = $res->fetch(PDO::FETCH_ASSOC);

		$sql = <<<EOT
SELECT
	SUBSTR(description,1,1) as letter,
	count(*) as nb_game
FROM
	games
WHERE
		description IS NOT NULL
	AND cloneof IS NULL
GROUP BY	letter
ORDER BY 	nb_game DESC
LIMIT 		0,10
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
?>
	<table class="stats">
		<tr><th>Database size</th><td><?=HumanReadableFilesize(filesize(DATABASE_FILENAME))?></td></tr>
		<tr><th>Total games</th><td><?=$row_games['total_games']?></td></tr>
		<tr><th>Parents</th><td><?=$row_games['parent_games']?></td></tr>
		<tr><th>Clones</th><td><?=$row_games['clone_games']?></td></tr>
		<tr><th>Average clones/game</th><td><?=sprintf('%0.2f',$row_games['clone_games'] / $row_games['parent_games'])?></td></tr>
		<tr><th>Top 10 letter name starting with</th><th>Games</th></tr>
		<?php while($row_best_letters = $res->fetch(PDO::FETCH_ASSOC)) { ?>
				<tr>
					<td><?=$row_best_letters['letter']?></td>
					<td><?=$row_best_letters['nb_game']?></td>
				</tr>
		<? } ?>
	</table>
</div>


<!-- MANUFACTURERS STATS -->
<div id="manufacturers_stats" class="infos">
<h2><a name="manufacturers_info">Manufacturers statistics</a></h2>
<?php
	$sql = <<<EOT
SELECT count(DISTINCT(manufacturer)) as total_manufacturers FROM games WHERE manufacturer IS NOT NULL
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
	$row_manufacturers = $res->fetch(PDO::FETCH_ASSOC);

	$sql = <<<EOT
SELECT
	manufacturer,
	count(*) as nb_game
FROM
	games
WHERE
		manufacturer IS NOT NULL
	AND cloneof IS NULL
GROUP BY	manufacturer
ORDER BY 	nb_game DESC
LIMIT 		0,10
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
?>
	<table class="stats">
		<tr><th>Total manufacturers</th><td><?=$row_manufacturers['total_manufacturers']?></td></tr>
		<tr><th>Average games/manufacturer</th><td><?=sprintf('%0.2f',$row_games['parent_games'] / $row_manufacturers['total_manufacturers'])?></td></tr>
		<tr><th>Top 10 manufacturers</th><th>Games</th></tr>
		<?php while($row_best_manufacturers = $res->fetch(PDO::FETCH_ASSOC)) { ?>
				<tr>
					<td><?=$row_best_manufacturers['manufacturer']?></td>
					<td><?=$row_best_manufacturers['nb_game']?></td>
				</tr>
		<? } ?>
	</table>
</div>


<!-- YEARS STATS -->
<div id="years_stats" class="infos">
<h2><a name="years_info">Years statistics</a></h2>
<?php
	$no_empty_years = " year IS NOT NULL AND year NOT LIKE '%?%' ";
	$sql = <<<EOT
SELECT 
	(SELECT count(DISTINCT(year)) FROM games WHERE $no_empty_years) as total_years,
	(SELECT DISTINCT(year) FROM games WHERE $no_empty_years ORDER BY year ASC LIMIT 0,1) as first_year,
	(SELECT DISTINCT(year) FROM games WHERE $no_empty_years ORDER BY year DESC LIMIT 0,1) as last_year
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
	$row_years = $res->fetch(PDO::FETCH_ASSOC);

	$sql = <<<EOT
SELECT
	year,
	count(*) as nb_game
FROM
	games
WHERE
		$no_empty_years
	AND cloneof IS NULL
GROUP BY	year
ORDER BY 	nb_game DESC
LIMIT 		0,10
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
?>
	<table class="stats">
		<tr><th>Total years</th><td><?=$row_years['total_years']?></td></tr>
		<tr><th>First one</th><td><?=$row_years['first_year']?></td></tr>
		<tr><th>Last one</th><td><?=$row_years['last_year']?></td></tr>
		<tr><th>Average games/year</th><td><?=sprintf('%0.2f',$row_games['parent_games'] / $row_years['total_years'])?></td></tr>
		<tr><th>Top 10 years</th><th>Games</th></tr>
		<?php while($row_best_years = $res->fetch(PDO::FETCH_ASSOC)) { ?>
				<tr>
					<td><?=$row_best_years['year']?></td>
					<td><?=$row_best_years['nb_game']?></td>
				</tr>
		<? } ?>
	</table>
</div>


<!-- ROMS STATS -->
<div id="roms_stats" class="infos">
<h2><a name="roms_info">ROMS statistics</a></h2>
<?php
	$sql = <<<EOT
SELECT 
	(SELECT count(*)     FROM games_rom GR LEFT JOIN games G ON GR.game=G.name WHERE G.cloneof IS NULL) as total_roms,
	(SELECT SUM(GR.size) FROM games_rom GR LEFT JOIN games G ON GR.game=G.name WHERE G.cloneof IS NULL) as total_roms_size
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
	$row_roms = $res->fetch(PDO::FETCH_ASSOC);

	$sql = <<<EOT
SELECT
	count(*) as nb_roms,
	description
FROM
	games_rom GR
	LEFT JOIN games G
		ON GR.game=G.name
WHERE
		G.cloneof IS NULL
GROUP BY 	GR.game
ORDER BY 	nb_roms DESC
LIMIT 		0,10
EOT;
	$res = $database->query($sql) or die("Unable to query database : ".array_pop($database->errorInfo())."<br/>$sql"); 
?>
	<table class="stats">
		<tr><th>Total ROMS</th><td><?=$row_roms['total_roms']?></td></tr>
		<tr><th>Average ROMS/game</th><td><?=sprintf('%0.2f',$row_roms['total_roms'] / $row_games['parent_games'])?></td></tr>
		<tr><th>Total ROMS size</th><td><?=HumanReadableFilesize($row_roms['total_roms_size'])?></td></tr>
		<tr><th>Average size of ROMS</th><td><?=HumanReadableFilesize(sprintf('%0.2f',$row_roms['total_roms_size'] / $row_roms['total_roms']))?></td></tr>
		<tr><th>Top 10 games with more ROMS</th><th>ROMS</th></tr>
		<?php while($row_best_roms = $res->fetch(PDO::FETCH_ASSOC)) { ?>
				<tr>
					<td><?=$row_best_roms['description']?></td>
					<td><?=$row_best_roms['nb_roms']?></td>
				</tr>
		<? } ?>
	</table>
</div>

<?php include_once('footer.php'); ?>

</body>
</html>