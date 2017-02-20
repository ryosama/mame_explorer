<?php
// rom page presentation
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

if (isset($_GET['name']) && strlen($_GET['name'])>0) { // a game is specify
	list($game_name,$game_console) = explode('/',$_GET['name']);

	if (strlen($game_console)<=0) // if no console is specify --> arcade by default
		$game_console = 'arcade';

} else { // no game specify --> find a random game
	$res = $database->query("SELECT name,console FROM games WHERE cloneof is NULL and runnable='1' ORDER BY random() LIMIT 0,1") or die("Unable to query database : ".array_pop($database->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$game_name 		= $row['name'];
	$game_console 	= $row['console'];
}	

// extract info about the game
$sql = <<<EOT
SELECT 	
		G.description, G.name, G.cloneof, G.manufacturer, G.year, G.runnable, G.sourcefile, G.console,
		SL.description as softwarelist_description
FROM 	
		games G
		LEFT JOIN softwarelist SL
			ON G.console=SL.name
WHERE
		G.name=?
	AND G.console=?
EOT;

$res = $database->prepare($sql);
$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));

$row_game = $res->fetch(PDO::FETCH_ASSOC);

$arcade_game 			= $row_game['console'] == 'arcade' ? true : false;
$sufix_media_directory 	= $arcade_game ? '':'_SL/'.$row_game['console'];

// get some clone info to display menu
$cloneof = '';
if ($row_game['cloneof'])
	$cloneof = $row_game['cloneof'];

$res = $database->prepare("SELECT count(*) as nb_child_clones FROM games WHERE cloneof=? AND console=?");
$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC);
$nb_child_clones = $row['nb_child_clones'];

$res = $database->prepare("SELECT count(*) as nb_brother_clones FROM games WHERE name<>? AND console=? AND cloneof=?");
$res->execute(array($game_name,$game_console,$cloneof)) or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC);
$nb_brother_clones = $row['nb_brother_clones'];

// get somes infos about the game for displaying menus
$has_info = game_has_info($game_name,$row_game['console']);

?><html>
<head>
<title>Mame game : <?=$row_game['name']?> : <?=$row_game['description']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/app.css">
<link rel="stylesheet" type="text/css" href="css/color.css">
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/app.js"></script>
<script type="text/javascript" src="js/jquery.lightbox.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.lightbox.css" media="screen" />
<script type="text/javascript" src="js/stacktable.min.js"></script>

<script language="javascript">

function show_media(link,media_type) {
	$('#snapshot').html(
		$(link).text() +
		'<br/>'+
		'<a href="<?=MEDIA_PATH?>/'+media_type+'/<?=$game_name?>.'+(media_type=='icons'?'ico':'png')+'">' +
		'<img src="<?=MEDIA_PATH?>/'+media_type+'/<?=$game_name?>.'+(media_type=='icons'?'ico':'png')+'" class="media"/></a>'
	);
	$('#snapshot a').lightBox({fixedNavigation:true});
}


function show_video() {
	var embed_video_html = $('#video').html().replace(/\bautoplay=0\b/,'autoplay=1');

	$('#snapshot').html(
		'Video' +
		'<br/>'+
		embed_video_html // display html from hidden place
	);
}

// when ready, look for a video
$(document).ready(function() {
<?php if ($arcade_game)	{ ?>
	look_for_video('<?= $cloneof ? $cloneof : $game_name ?>');
<?php } ?>
});

</script>

</head>
<body>

<?php 	// eventualy include analytics
		if (file_exists('js/analyticstracking.js'))
			include_once('js/analyticstracking.js');
?>

<?php include_once('search_bar.php'); ?>

<?php if (file_exists(MEDIA_PATH."/titles$sufix_media_directory/$game_name.png")) { ?>
	<div id="fake-background" style="background-image:url(<?=MEDIA_PATH."/titles$sufix_media_directory/$game_name.png"?>) ;"></div>
<?php } ?>

<h1>
	<?php	if ($arcade_game && file_exists(MEDIA_PATH."/icons/$game_name.ico")) { ?>
				<img src="<?=MEDIA_PATH?>/icons/<?=$game_name?>.ico" id="icon"/>
	<?php	} ?>
	<?=$row_game['description']?>
</h1>

<!-- SUMMARY -->
<ol id="summary">
	<li><a href="#game_info">Game infos</a></li>
	<?php if ($cloneof || $nb_child_clones>0) {		?><li><a href="#clones_info">Parent and Clones</a></li><?php } ?>
<?php	if ($arcade_game) { ?>
													  <li><a href="#sound_info">Sound</a></li>
													  <li><a href="#driver_info">Driver</a></li>
													  <li><a href="#input_info">Inputs</a></li>
	<?php if ($has_info['games_control']) { 		?><li><a href="#control_info">Controls</a></li><?php } ?>
	<?php if ($has_info['games_display']) { 		?><li><a href="#display_info">Display</a></li><?php } ?>
	<?php if ($has_info['games_adjuster']) { 		?><li><a href="#adjuster_info">Adjusters</a></li><?php } ?>
	<?php if ($has_info['games_configuration']) { 	?><li><a href="#configuration_info">Configurations</a></li><?php } ?>
	<?php if ($has_info['games_dipswitch']) { 		?><li><a href="#dipswitch_info">Dipswitchs</a></li><?php } ?>
<?php } else { ?>
													  <li><a href="#software_info">Software Infos</a></li>
													  <li><a href="#feature_info">Features</a></li>
<?php } ?>
	<?php if ($has_info['games_rom']) {				?><li><a href="#rom_list">Roms list</a></li><?php } ?>
<?php	if ($arcade_game) { ?>
	<?php if ($has_info['games_biosset']) { 		?><li><a href="#biosset_list">BIOS set</a></li><?php } ?>
	<?php if ($has_info['games_chip']) { 			?><li><a href="#chip_list">Chips list</a></li><?php } ?>
	<?php if ($has_info['games_sample']) {			?><li><a href="#sample_list">Samples list</a></li><?php } ?>
	<?php if ($has_info['games_disk']) { 			?><li><a href="#disk_list">Disks list</a></li><?php } ?>
	<?php if ($has_info['games_series']) { 			?><li><a href="#serie_info">Serie</a></li><?php } ?>
	<?php if ($has_info['categories']) { 			?><li><a href="#categories_info">Categories</a></li><?php } ?>
	<?php if ($has_info['mameinfo']) { 				?><li><a href="#mameinfo_info">MAMEinfo</a></li><?php } ?>
	<?php if ($has_info['games_histories']) { 		?><li><a href="#stories_info">History</a></li><?php } ?>
	<?php if ($has_info['games_command']) { 		?><li><a href="#command_list">Commands list</a></li><?php } ?>
	<?php if ($has_info['cheats']) { 				?><li><a href="#cheats_list">Cheats</a></li><?php } ?>
	<?php if ($has_info['stories']) { 				?><li><a href="#highscore_info">High scores</a></li><?php } ?>
<?php } ?>
</ol>

<?php
$row_rom_size = array();
$row_rom_size['romset_size'] = $row_rom_size['romset_file'] = $row_rom_size['romset_zip'] = '';
$res = $database->prepare("SELECT romset_size,romset_file,romset_zip FROM mameinfo WHERE game=?");
if ($arcade_game && $res->execute(array($game_name))) {
	$row_rom_size = $res->fetch(PDO::FETCH_ASSOC);
}

$row_version = array();
$row_version['categorie'] = '';
$res = $database->prepare("SELECT * FROM categories WHERE game=? AND version_added='1'");
if ($arcade_game && $res->execute(array($game_name))) {
	$row_version = $res->fetch(PDO::FETCH_ASSOC);
}
?>

<!-- DOWNLOAD LINK -->
<?php
if ($arcade_game) {
$add_in_mame = preg_replace('/^(\.\d{3}).*/','$1',$row_version['categorie']);
if ($add_in_mame <= 0.161) { // archives.org stop at v0.161 ?>
	<div id="download">
		<a class="btn" href="https://archive.org/download/MAME_0_161_ROMs/MAME_0.161_ROMs.tar/MAME 0.161 ROMs/<?=urlencode($game_name)?>.zip">
			<i class="fa fa-download fa-small"></i> Download <?=$game_name?>.zip (<?=HumanReadableFilesize($row_rom_size['romset_size'] * 1024)?>)
		</a>
	</div>
<?php } // < 0.161
} else { // not an arcade game ?>
	<div id="download">
		<a class="btn" href="https://archive.org/download/MESS_0.151_Software_List_ROMs/<?=$row_game['console']?>.zip/MESS 0.151 Software List ROMs/<?=$row_game['console']?>/<?=urlencode($game_name)?>.zip">
			<i class="fa fa-download fa-small"></i> Download <?=$game_name?>.zip
		</a>
	</div>
<?php } ?>


<!-- MEDIA LIST -->
<div id="media">
<?php	
		if ($arcade_game) {
			$media_type = array(
				'snap'		=> 'Snapshot',		'titles'	=> 'Title',			'bosses'	=> 'Boss',
				'ends'		=> 'Ending',		'gameover'	=> 'Game Over',		'howto'		=> 'How To',
				'logo'		=> 'Logo',			'scores'	=> 'Score',			'select'	=> 'Select',
				'versus'	=> 'Versus',		'marquees'	=> 'Marquee',		'flyers'	=> 'Flyer',
				'cabinets'	=> 'Cabinet',		'cpanel'	=> 'Control panel',	'pcb'		=> 'PCB',
				'icons'		=> 'Icon'
			);
		} else {
			$media_type = array(
				'snap_SL/'   .$row_game['console']	=> 'Snapshot',
				'titles_SL/' .$row_game['console']	=> 'Title',
				'covers_SL/' .$row_game['console']	=> 'Covers',
				'manuels_SL/'.$row_game['console']	=> 'Manual'
			);
		}
?>
	<ul id="media-list">
<?php	foreach ($media_type as $media_id => $media_name) {
			if (file_exists(MEDIA_PATH."/$media_id/$game_name.".($media_id == 'icons' ? 'ico':'png'))) { ?>
				<li onclick="show_media(this,'<?=$media_id?>')"><?=$media_name?></li>
<?php 		}
		} ?>
	</ul>

	<div id="snapshot">
<?php	if (file_exists(MEDIA_PATH."/snap$sufix_media_directory/$game_name.png")) { ?>
			Snapshot<br/>
			<a href="<?=MEDIA_PATH?>/snap<?=$sufix_media_directory?>/<?=$game_name?>.png"><img src="<?=MEDIA_PATH?>/snap<?=$sufix_media_directory?>/<?=$game_name?>.png" class="media"/></a>
<?php 	} elseif (file_exists(MEDIA_PATH."/titles$sufix_media_directory/$game_name.png")) { ?>
			Title<br/>
			<a href="<?=MEDIA_PATH?>/titles<?=$sufix_media_directory?>/<?=$game_name?>.png"><img src="<?=MEDIA_PATH?>/titles<?=$sufix_media_directory?>/<?=$game_name?>.png" class="media"/></a>
<?php 	} ?>
	</div>
	<div id="video" style="display:none;"></div>
</div>


<!-- GAME INFO -->
<div id="game_info" class="infos">
<h2><a name="game_info">Game infos</a></h2>

<div id="game_description" class="info">
	<span class="labels">Description</span>
	<span class="values"><?=$row_game['description']?></span>
</div>

<div id="game_name" class="info">
	<span class="labels">Name</span>
	<span class="values"><?=$row_game['name']?></span>
</div>

<div id="game_manufacturer" class="info">
	<span class="labels">Manufacturer</span>
	<span class="values"><a href="results.php?manufacturer=<?=urlencode($row_game['manufacturer'])?>"><?=$row_game['manufacturer']?></a></span>
</div>

<div id="game_year" class="info">
	<span class="labels">Year</span>
	<span class="values"><a href="results.php?year=<?=$row_game['year']?>"><?=$row_game['year']?></a>
	</span>
</div>

<div id="game_runnable" class="info">
	<span class="labels">Runnable</span>
	<span class="values"><?=bool2yesno($row_game['runnable'])?></span>
</div>

<div id="game_system" class="info">
	<span class="labels">System</span>
	<span class="values">
		<a href="results.php?console=<?=$row_game['console']?>">
		<?=$row_game['console']?> / <?=$row_game['softwarelist_description']?>
<?php	if (file_exists("images/consoles/$row_game[console].png")) { ?>
			<img class="console-icon" src="images/consoles/<?=$row_game['console']?>.png"/>
<?php	} ?>
		</a>
	</span>
</div>


<?php 	$res = $database->prepare("SELECT * FROM nplayers WHERE game=?");
	 	if ($arcade_game && $res->execute(array($game_name))) {
			while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
				<div id="game_nplayers" class="info">
					<span class="labels">Number of players</span>
					<span class="values"><a href="results.php?nplayers=<?=urlencode($row['players'])?>"><?=$row['players']?></a></span>
				</div>
<?php		}	
		} ?>

<?php if ($arcade_game) { ?>
		<div id="game_add_in_mame" class="info">
			<span class="labels">Added to MAME</span>
			<span class="values"><a href="results.php?categorie=<?=urlencode($row_version['categorie'])?>"><?=$row_version['categorie']?></a></span>
		</div>

		<div id="game_romset_size" class="info">
			<span class="labels">Romset size</span>
			<span class="values"><?=HumanReadableFilesize($row_rom_size['romset_size'] * 1024)?></span>
		</div>
		<div id="game_romset_size" class="info">
			<span class="labels">Romset file</span>
			<span class="values"><?=$row_rom_size['romset_file']?> files</span>
		</div>
		<div id="game_romset_size" class="info">
			<span class="labels">Romset zip</span>
			<span class="values"><?=HumanReadableFilesize($row_rom_size['romset_zip'])?></span>
		</div>

<?php } else { // console game
	$res = $database->prepare("SELECT SUM(size) as romset_size FROM games_rom WHERE game=? AND console=?");
	$res->execute(array($game_name,$game_console));
	$row = $res->fetch(PDO::FETCH_ASSOC)
?>
		<div id="game_romset_size" class="info">
			<span class="labels">Romset size</span>
			<span class="values"><?=HumanReadableFilesize($row['romset_size'])?></span>
		</div>
<?php } ?>


<?php	$res = $database->prepare("SELECT L.language FROM languages L LEFT JOIN games_languages GL ON L.id=GL.language_id WHERE GL.game=?");
		if ($arcade_game && $res->execute(array($game_name))) { ?>
			<div id="game_language" class="info">
				<span class="labels">Language</span>
				<span class="values">
<?php 				$html_languages = array();
					while($row = $res->fetch(PDO::FETCH_ASSOC))
						$html_languages[] = '<a href="results.php?language='.urlencode($row['language']).'">'.$row['language'].'</a>';
					echo join(' / ',$html_languages); ?>
				</span>
			</div>
<?php 	} ?>

<?php	$res = $database->prepare("SELECT evaluation FROM bestgames WHERE game=?");
		if ($arcade_game && $res->execute(array($game_name))) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if (strlen($row['evaluation'])>0) { ?>
				<div id="game_evaluation" class="info">
					<span class="labels">Evaluation</span>
					<span class="values"><a href="results.php?evaluation=<?=urlencode($row['evaluation'])?>"><?=$row['evaluation']?></a></span>
				</div>
<?php   	}
		} ?>

<?php	$res = $database->prepare("SELECT count(*) as mature FROM mature WHERE game=?");
		if ($arcade_game && $res->execute(array($game_name))) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if ($row['mature'] > 0) { ?>
				<div id="game_mature" class="info">
					<span class="labels">Mature</span>
					<span class="values"><a href="results.php?mature=on">This game is for adults only</a></span>
				</div>
<?php   	}
		} ?>

<?php	$res = $database->prepare("SELECT genre FROM genre WHERE game=?");
		if ($arcade_game && $res->execute(array($game_name))) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if (strlen($row['genre']) > 0) { ?>
				<div id="game_genre" class="info">
					<span class="labels">Genre</span>
					<span class="values"><a href="results.php?genre=<?=urlencode($row['genre'])?>"><?=$row['genre']?></a></span>
				</div>
<?php   	}
		} ?>
</div>


<!-- PARENT AND CLONES INFO -->
<?php if ($cloneof || $nb_child_clones > 0) { ?>
<div id="clones_info" class="infos">
<h2><a name="clones_info">Parent and clones</a></h2>
	<div id="parent">
		<span class="labels">Parent</span>
<?php		$res = $database->prepare("SELECT description,year FROM games WHERE name=? AND console=?");
			$res->execute(array($cloneof,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if ($cloneof) { // if this game is a clone ?>
				<a href="?console=<?=$game_console?>&name=<?=$cloneof?>"><?=$cloneof?> : <?=$row['description']?> (<?=$row['year']?>)</a>

<?php			if ($nb_brother_clones>0) { // and this clone has brothers ?>
					<ul><span class="labels">Other clones</span>
<?php 					$res = $database->prepare("SELECT name,description,year FROM games WHERE cloneof=? AND console=? ORDER BY year ASC,description ASC");
						$res->execute(array($cloneof,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
						while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
							<li><a href="?console=<?=$game_console?>&name=<?=$row['name']?>"><?=$row['name']?> : <?=$row['description']?> (<?=$row['year']?>)</a></li>
<?php					} ?>
					</ul>
<?php			}

			} else { ?>
				This game is the parent

<?php 			if ($nb_child_clones>0) { // and this parent has clones ?>
					<ul><span class="labels">Clones</span>
<?php 					$res = $database->prepare("SELECT name,description,year FROM games WHERE cloneof=? AND console=? ORDER BY year ASC,description ASC");
						$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
						while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
							<li><a href="?console=<?=$game_console?>&name=<?=$row['name']?>"><?=$row['name']?> : <?=$row['description']?> (<?=$row['year']?>)</a></li>
<?php					} ?>
					</ul>
<?php				}
	 		} ?>
	</div>
</div>
<?php } // has clone ?>


<!-- SOUND INFO -->
<?php if ($arcade_game) { ?>
<div id="sound_info" class="infos">
<h2><a name="sound_info">Sound infos</a></h2>
<?php
	$fields = array('sound_channels'=>'INTEGER');
	$res = $database->prepare("SELECT ".join(',',array_keys($fields))." FROM games WHERE name=? AND console=?");
	$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	foreach ($fields as $field_name => $field_type) {
		if ($row[$field_name] != '') { // si qqchose a afficher ?>
			<div id="game_<?=$field_name?>" class="info">
				<span class="labels"><?=ucfirst($field_name)?></span>
				<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
			</div>
<?php
		}
	} ?>
</div>
<?php } // has sound info ?>



<!-- DRIVERS INFO -->
<?php if ($arcade_game) { ?>
<div id="driver_info" class="infos">
<h2><a name="driver_info">Driver infos</a></h2>
<?php 	$fields = array('driver_status'=>'VARCHAR','driver_emulation'=>'VARCHAR','driver_color'=>'VARCHAR','driver_sound'=>'VARCHAR','driver_graphic'=>'VARCHAR','driver_cocktail'=>'VARCHAR','driver_protection'=>'VARCHAR','driver_savestate'=>'BOOL');
		$res = $database->prepare("SELECT ".join(',',array_keys($fields))." FROM games WHERE name=? AND console=?");
		$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
		$row = $res->fetch(PDO::FETCH_ASSOC);
		foreach ($fields as $field_name => $field_type) { ?>
			<div id="game_<?=$field_name?>" class="info">
				<span class="labels"><?=ucfirst(str_replace('_',' ',$field_name))?></span>
				<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
			</div>
<?php	} ?>
</div>
<?php } // has driver info ?>



<!-- INPUT INFO -->
<?php if ($arcade_game) { ?>
<div id="input_info" class="infos">
<h2><a name="input_info">Inputs infos</a></h2>
<?php	$fields = array('input_service'=>'BOOL','input_tilt'=>'BOOL','input_players'=>'INTEGER','input_buttons'=>'INTEGER','input_coins'=>'INTEGER');
		$res = $database->prepare("SELECT ".join(',',array_keys($fields))." FROM games WHERE name=? AND console=?");
		$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
		$row = $res->fetch(PDO::FETCH_ASSOC);
		foreach ($fields as $field_name => $field_type) { ?>
			<div id="game_<?=$field_name?>" class="info">
				<span class="labels"><?=ucfirst(str_replace('_',' ',$field_name))?></span>
				<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
			</div>
<?php 	} ?>
</div>
<?php } // has input info ?>


<?php if ($arcade_game && $has_info['games_control']) { ?>
<div id="control_info" class="infos">
<h2><a name="control_info">Controls infos</a></h2>
<?php 
	$fields = get_fields_info('games_control');
	$res = $database->prepare("SELECT * FROM games_control WHERE game=?");
	$res->execute(array($game_name)); ?>
	<table>
		<tr>
<?php 	foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php 	} ?>
		</tr>
<?php 	while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
<?php 	foreach ($fields as $field_name => $field_type) { ?>
			<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
<?php 	} ?>
		</tr>
<?php 	} ?>
	</table>
</div>
<?php } // has game info ?>



<!-- DISPLAY INFO -->
<?php
if ($arcade_game && $has_info['games_display']) {
$fields = get_fields_info('games_display');
$res = $database->prepare("SELECT * FROM games_display WHERE game=?");
$res->execute(array($game_name)); ?>
<div id="display_info" class="infos">
<h2><a name="display_info">Display infos</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
		<th><?=$field_name?></th>
<?php } ?>
	</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
			<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // has game info ?>


<!-- CONFIGURATION INFO -->
<?php
if ($arcade_game && $has_info['games_configuration']) {
	$fields = get_fields_info('games_configuration');
	$res = $database->prepare("SELECT * FROM games_configuration WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="configuration_info" class="infos">
<h2><a name="configuration_info">Configuration</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
		<th><?=$field_name?></th>
<?php } ?>
	</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
<?php
	$i=0;
	foreach ($fields as $field_name => $field_type) { ?>
		<td>
			<?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?>
<?php		if ($i==0) { // 1er champ du tableau
				$fields2 = get_fields_info('games_configuration_confsetting');
				$res2 = $database->prepare("SELECT * FROM games_configuration_confsetting WHERE configuration_id=?");
				$res2->execute(array($row['id']));
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<div class="sousinfo">
<?php					foreach ($fields2 as $field_name2 => $field_type2) { ?>
						<div id="games_configuration_confsetting_<?=$field_name2?>" class="info">
							<span class="labels"><?=ucfirst(str_replace('_',' ',$field_name2))?></span>
							<span class="values"><?=$fields2[$field_name2]=='BOOL' ? bool2yesno($row2[$field_name2]) : $row2[$field_name2] ?></span>
						</div>
<?php				} // fin foreach field ?>
					</div>
<?php			} // fin while row
			} // fin if $i==0 ?><br/>
		</td>
<?php	$i++;
	} ?>
		</tr>
<?php
} ?>
</table>
</div>
<?php } // game has info ?>


<!-- DIPSWITCH INFO -->
<?php
if ($arcade_game && $has_info['games_dipswitch']) {
	$res = $database->prepare("SELECT * FROM games_dipswitch WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="dipswitch_info" class="infos">
<h2><a name="dipswitch_info">Dipswitchs</a></h2>
	<ul>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<li>
			<?=$row['name']?>
			<ul>
<?php			$res2 = $database->prepare("SELECT * FROM games_dipswitch_dipvalue WHERE dipswitch_id=?");
				$res2->execute(array($row['id']));
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><?=$row2['name']?></li>
<?php			} ?>
			</ul>
		</li>
<?php } ?>
	</ul>
</div>
<?php } // game has info ?>


<!-- ADJUSTER INFO -->
<?php
if ($arcade_game && $has_info['games_adjuster']) {
	$fields = get_fields_info('games_adjuster');
	$res = $database->prepare("SELECT * FROM games_adjuster WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="adjuster_info" class="infos">
<h2><a name="adjuster_info">Adjusters</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- SOFTWARE INFO -->
<?php if (!$arcade_game) { ?>
<div id="software_info" class="infos">
<h2><a name="software_info">Software Infos</a></h2>
<?php	$res = $database->prepare("SELECT name,value FROM software_info WHERE type='info' AND game=? AND console=?");
		$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
			<div id="game_software_info_<?=$row['name']?>" class="info">
				<span class="labels"><?=ucfirst($row['name'])?></span>
				<span class="values"><?=$row['value']?></span>
			</div>
<?php 	} ?>
</div>
<?php } ?>


<!-- FEATURES INFO -->
<?php if (!$arcade_game) { ?>
<div id="feature_info" class="infos">
<h2><a name="feature_info">Feature Infos</a></h2>
<?php	$res = $database->prepare("SELECT name,value FROM software_info WHERE type='feature' AND game=? AND console=?");
		$res->execute(array($game_name,$game_console)) or die("Unable to query database : ".array_pop($database->errorInfo()));
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
			<div id="game_software_info_<?=$row['name']?>" class="info">
				<span class="labels"><?=ucfirst($row['name'])?></span>
				<span class="values"><?=$row['value']?></span>
			</div>
<?php 	} ?>
</div>
<?php } ?>


<!-- ROM LIST -->
<?php
if ($has_info['games_rom']) {
	$fields = get_fields_info('games_rom');
	$res = $database->prepare("SELECT * FROM games_rom WHERE game=? and console=?");
	$res->execute(array($game_name,$game_console)); ?>
<div id="rom_info" class="infos">
<h2><a name="rom_list">Roms list</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
		<th class="<?=$field_name?>"><?=$field_name?></th>
<?php } ?>
		</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
			<td class="<?=$field_name?>"><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- BIOS SET -->
<?php
if ($arcade_game && $has_info['games_biosset']) {
	$fields = get_fields_info('games_biosset');
	$res = $database->prepare("SELECT * FROM games_biosset WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="biosset_list" class="infos">
<h2><a name="biosset_list">BIOS set</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?> 
		<th><?=$field_name?></th>
<?php } ?>
	</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
			<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- CHIP LIST -->
<?php
if ($arcade_game && $has_info['games_chip']) {
	$fields = get_fields_info('games_chip');
	$res = $database->prepare("SELECT * FROM games_chip WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="chip_info" class="infos">
<h2><a name="chip_list">Chips list</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
		<th><?=$field_name?></th>
<?php } ?>
	</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
			<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- SAMPLE LIST -->
<?php
if ($arcade_game && $has_info['games_sample']) {
	$fields = get_fields_info('games_sample');
	$res = $database->prepare("SELECT * FROM games_sample WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="sample_info" class="infos">
<h2><a name="sample_list">Samples list</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
		<th><?=$field_name?></th>
<?php } ?>
	</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- DISK LIST -->
<?php
if ($arcade_game && $has_info['games_disk']) {
	$fields = get_fields_info('games_disk');
	$res = $database->prepare("SELECT * FROM games_disk WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="disk_info" class="infos">
<h2><a name="disk_list">Disks list</a></h2>
<table>
	<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
	</tr>
<?php } ?>
</table>
</div>
<?php } // game has info ?>


<!-- SERIES LIST -->
<?php
if ($arcade_game && $has_info['games_series']) {
	$res = $database->prepare("SELECT * FROM games_series GS,series S WHERE GS.game=? AND GS.serie_id=S.id");
	$res->execute(array($game_name));
	$row = $res->fetch(PDO::FETCH_ASSOC);
?>
<div id="serie_info" class="infos">
<h2><a name="serie_info">Serie</a></h2>
	<div id="serie_title">Serie : <?=$row['serie']?></div>
	<ol class="series">
<?php
$res = $database->prepare("SELECT G.description,G.name,G.year FROM games_series GS, games G WHERE GS.game=G.name AND GS.serie_id=? ORDER BY G.year ASC");
$res->execute(array($row['id']));
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<li>
<?php	if ($row['name']==$game_name) { // this game ?>
			<?=$row['description']?> (<?=$row['year']?>)
<?php	} else { // not this game ?>
			<a href="?console=<?=$game_console?>&name=<?=$row['name']?>"><?=$row['description']?></a> (<?=$row['year']?>)	
<?php	} ?>
		</li>
<?php } ?>
	</ol>
</div>
<?php } // game has info ?>


<!-- CATEGORIES LIST -->
<?php
if ($arcade_game && $has_info['categories']) {
	$res = $database->prepare("SELECT * FROM categories WHERE game=? ORDER BY version_added DESC");
	$res->execute(array($game_name)); ?>
<div id="categories_info" class="infos">
<h2><a name="categories_info">Categories</a></h2>
	<ul class="categories">
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { 
	if ($row['version_added'] == 1) { ?>
		<li><a href="results.php?categorie=<?=urlencode($row['categorie'])?>">Added to Mame in version <?=$row['categorie']?></a></li>
<?php } else { ?>
		<li><a href="results.php?categorie=<?=urlencode($row['categorie'])?>"><?=$row['categorie']?></a></li>
<?php }
	} ?>
	</ul>
</div>
<?php } // game has info ?>


<!-- MAMEINFO LIST -->
<?php
if ($arcade_game && $has_info['mameinfo']) {
	$res = $database->prepare("SELECT * FROM mameinfo WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="mameinfo_info" class="infos">
<h2><a name="mameinfo_info">MAMEinfo</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) {
	echo preg_replace('/\n/','<br/>',$row['info']);
} ?>
</div>
<?php } // game has info ?>


<!-- HISTORIES -->
<?php
if ($arcade_game && $has_info['games_histories']) {
	$res = $database->prepare("SELECT * FROM games_histories GH,histories H WHERE GH.game=? AND GH.history_id=H.id");
	$res->execute(array($game_name)); ?>
<div id="stories_info" class="infos">
<h2><a name="stories_info">History</a></h2>
<?php $row = $res->fetch(PDO::FETCH_ASSOC) ?>
	<div class="history">
		<a href="<?=$row['link']?>" target="_blank"><?=$row['link']?></a><br>
		<?=preg_replace('/\n/','<br/>',$row['history'])?>
	</div>
</div>
<?php } // game has info ?>


<!-- COMMAND LIST -->
<?php
if ($arcade_game && $has_info['games_command']) {
	$res = $database->prepare("SELECT * FROM games_command GC,command C WHERE GC.game=? and GC.command_id=C.id");
	$res->execute(array($game_name)); ?>
<div id="command_list" class="infos">
<h2><a name="command_list">Commands list</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<pre class="command"><?
		$command = $row['command'];

		// minusculs
		$command = preg_replace('/_([abcdefghijklmnopqrstuvwxyz])/','<img src="images/keys-min/_$1.png"/> ',$command);
		$command = preg_replace('/\^([xxx])/','<img src="images/keys-min/^$1.png"/> ',$command);

		// majusculs
		$command = preg_replace('/_([ABCDGHIKLMNOPQRSXZ])/','<img src="images/keys-maj/_$1.png"/> ',$command);
		$command = preg_replace('/\^([EFGHIJMSTUVW])/','<img src="images/keys-maj/^$1.png"/> ',$command);

		// other symbols
		$command = preg_replace('/_([#\$%&\(\)\-@\[\]\^`\{\}~=+\.123456789!])/','<img src="images/keys-others/_$1.png"/> ',$command);
		$command = preg_replace('/\^([12346789!\-=])/','<img src="images/keys-others/^$1.png"/> ',$command);

		$command = str_replace('^?','<img src="images/keys-others/^interogation.png"/> ',$command);
		$command = str_replace('^*','<img src="images/keys-others/^star.png"/> ',$command);
		$command = str_replace('_<','<img src="images/keys-others/_inferior.png"/> ',$command);
		$command = str_replace('_?','<img src="images/keys-others/_interogation.png"/> ',$command);
		$command = str_replace('_*','<img src="images/keys-others/_star.png"/> ',$command);
		$command = str_replace('_>','<img src="images/keys-others/_superior.png"/> ',$command);
		
		echo $command;
	?></pre>
<?php } ?>
</div>
<?php } // game has info ?>


<!-- CHEATS LIST -->
<?php
if ($arcade_game && $has_info['cheats']) {
	$res = $database->prepare("SELECT * FROM cheats WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="cheats_list" class="infos">
<h2><a name="cheats_list">Cheats</a></h2>
	<ul>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if (trim($row['cheat']))  { ?>
		<li>
			<?=$row['cheat']?>
			<ul>
<?php			$res2 = $database->prepare("SELECT * FROM cheats_options WHERE cheat_id=?");
				$res2->execute(array($row['id']));
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><?=$row2['option']?></li>
<?php			} ?>
			</ul>
		</li>
<?php	}
	} ?>
	</ul>
</div>
<?php } // game has info ?>


<!-- STORIES LIST -->
<?php
if ($arcade_game && $has_info['stories']) {
	$res = $database->prepare("SELECT * FROM stories WHERE game=?");
	$res->execute(array($game_name)); ?>
<div id="highscore_info" class="infos">
<h2><a name="highscore_info">High scores</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<pre class="command"><?=$row['score']?></pre>
<?php } ?>
</div>
<?php } // game has info ?>

<?php include_once('footer.php'); ?>

</body>
</html>