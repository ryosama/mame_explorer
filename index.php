<?php
// page de repr�sentation d'une rom

include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

session_start();

if (isset($_GET['name']) && $_GET['name']) {
	$game_name = $_GET['name'];
} else {
	// find a random game
	$res = $database->query("SELECT name FROM games WHERE cloneof='' ORDER BY random() LIMIT 0,1") or die("Unable to query database : ".array_pop($database->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$game_name = $row['name'];
}	

$game_name_escape = sqlite_escape_string($game_name);

// extract game info
$fields = array('description'=>'VARCHAR','name'=>'VARCHAR','manufacturer'=>'VARCHAR','year'=>'INTEGER','runnable'=>'BOOL','sourcefile'=>'VARCHAR');
$res = $database->query("SELECT ".join(',',array_keys($fields)).",cloneof FROM games WHERE name='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row_game = $res->fetch(PDO::FETCH_ASSOC);

?><html>
<head>
<title>Mame game : <?=$row_game['name']?> : <?=$row_game['description']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/app.css">
<link rel="stylesheet" type="text/css" href="css/search-bar.css">
<link rel="stylesheet" type="text/css" href="css/game.css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/app.js"></script>
<script type="text/javascript" src="js/jquery.lightbox.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.lightbox.css" media="screen" />
</head>
<body>

<? include_once('search_bar.php'); ?>

<h1>
	<?php	if (file_exists(MEDIA_PATH."/icons/$game_name.ico")) { ?>
				<img src="<?=MEDIA_PATH?>/icons/<?=$game_name?>.ico" id="icon"/>
	<?php	} ?>
	<?=$row_game['description']?>
</h1>

<!-- SUMMARY -->
<ol id="summary">
	<li><a href="#game_info">Game infos</a></li>
	<li><a href="#clones_info">Parent and clones</a></li>
	<li><a href="#sound_info">Sound</a></li>
	<li><a href="#driver_info">Driver</a></li>
	<li><a href="#input_info">Input</a></li>
	<li><a href="#display_info">Display</a></li>
	<li><a href="#adjuster_info">Adjuster</a></li>
	<li><a href="#configuration_info">Configuration</a></li>
	<li><a href="#dipswitch_info">Dipswitch</a></li>
	<li><a href="#rom_list">Roms list</a></li>
	<li><a href="#biosset_list">BIOS set</a></li>
	<li><a href="#chip_list">Chips list</a></li>
	<li><a href="#sample_list">Sample list</a></li>
	<li><a href="#disk_list">Disks list</a></li>
	<li><a href="#serie_info">Serie</a></li>
	<li><a href="#categories_info">Categories</a></li>
	<li><a href="#mameinfo_info">MAMEinfo</a></li>
	<li><a href="#stories_info">History</a></li>
	<li><a href="#command_list">Commands list</a></li>
	<li><a href="#cheats_list">Cheats</a></li>
	<li><a href="#highscore_info">High scores</a></li>
</ol>

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

$(document).ready(function(){
	$(function() {
		$('#snapshot a').lightBox({fixedNavigation:true});
	});
});

</script>

<div id="media">
	<ul>
		<?php	if (file_exists(MEDIA_PATH."/snap/$game_name.png")) { ?>
					<li onclick="show_media(this,'snap')">Snapshot</li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/titles/$game_name.png")) { ?>
					<li onclick="show_media(this,'titles')">Title</li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/marquees/$game_name.png")) { ?>
					<li onclick="show_media(this,'marquees')">Marquee</li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/flyers/$game_name.png")) { ?>
					<li onclick="show_media(this,'flyers')">Flyer</a></li>
		<?php	} ?>
		
		<?php	if (file_exists(MEDIA_PATH."/cabinets/$game_name.png")) { ?>
					<li onclick="show_media(this,'cabinets')">Cabinet</a></li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/cpanel/$game_name.png")) { ?>
					<li onclick="show_media(this,'cpanel')">Control panel</a></li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/pcb/$game_name.png")) { ?>
					<li onclick="show_media(this,'pcb')">PCB</a></li>
		<?php	} ?>

		<?php	if (file_exists(MEDIA_PATH."/icons/$game_name.ico")) { ?>
					<li onclick="show_media(this,'icons')">Icon</a></li>
		<?php	} ?>	
	</ul>
	<div id="snapshot">
<?php	if (file_exists(MEDIA_PATH."/snap/$game_name.png")) { ?>
			Snapshot<br/>
			<a href="<?=MEDIA_PATH?>/snap/<?=$game_name?>.png"><img src="<?=MEDIA_PATH?>/snap/<?=$game_name?>.png" class="media"/></a>
<?php } ?>
	</div>
</div>


<!-- GAME INFO -->
<div id="game_info" class="infos">
<h2><a name="game_info">Game infos</a></h2>
<?php
$cloneof = '';
if ($row_game['cloneof'])
	$cloneof = $row_game['cloneof'];


$search_info = array('manufacturer','year','sourcefile');
foreach ($fields as $field_name => $field_type) {
	if ($row_game[$field_name] != '') { // si qqchose a afficher ?>
		<div id="game_<?=$field_name?>" class="info">
			<span class="labels row"><?=ucfirst($field_name)?></span>
			<span class="values">
<?php			if (in_array($field_name,$search_info)) { // if search criteria ?>
					<a href="search.php?<?=$field_name?>=<?=$row_game[$field_name]?>"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row_game[$field_name]) : $row_game[$field_name] ?></a>
<?php			} else { ?>
					<?=$fields[$field_name]=='BOOL' ? bool2yesno($row_game[$field_name]) : $row_game[$field_name] ?>
<?php			} ?>
			</span>
		</div>
<?php
	}
}

$res = $database->query("SELECT * FROM nplayers WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<div id="game_nplayers" class="info">
			<span class="labels row">Number of players</span>
			<span class="values"><?=$row['players']?></span>
		</div>
<?php
}

$res = $database->query("SELECT * FROM categories WHERE game='$game_name_escape' AND version_added=1") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC); ?>
		<div id="game_add_in_mame" class="info">
			<span class="labels row">Added to MAME in version</span>
			<span class="values"><?=$row['categorie']?></span>
		</div>

<?php
$res = $database->query("SELECT romset_size,romset_file,romset_zip FROM mameinfo WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC); ?>
		<div id="game_romset_size" class="info">
			<span class="labels row">Romset size</span>
			<span class="values"><?=HumanReadableFilesize($row['romset_size'] * 1024)?></span>
		</div>
		<div id="game_romset_size" class="info">
			<span class="labels row">Romset file</span>
			<span class="values"><?=$row['romset_file']?> files</span>
		</div><div id="game_romset_size" class="info">
			<span class="labels row">Romset zip</span>
			<span class="values"><?=HumanReadableFilesize($row['romset_zip'])?></span>
		</div>
</div>


<!-- PARENT AND CLONES INFO -->
<div id="clones_info" class="infos">
<h2><a name="clones_info">Parent and clones</a></h2>
	<div id="parent">
		<span class="labels">Parent</span>
<?php		$res = $database->query("SELECT description FROM games WHERE name='$cloneof'") or die("Unable to query database : ".array_pop($database->errorInfo())); 
			$row = $res->fetch(PDO::FETCH_ASSOC);
			echo $cloneof ? "<a href=\"$_SERVER[PHP_SELF]?name=$cloneof\">$cloneof</a> : $row[description]" : 'This game is a parent';
?>
	</div>
<?php	if (!$cloneof) { // if parent ?>
			<ul><span class="labels">Clones</span>
<?php 			$res = $database->query("SELECT name,description,year FROM games WHERE cloneof='$game_name_escape' ORDER BY year ASC,description ASC") or die("Unable to query database : ".array_pop($database->errorInfo())); 
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><a href="<?=$_SERVER['PHP_SELF']?>?name=<?=$row['name']?>"><?=$row['name']?></a> : <?=$row['description']?> (<?=$row['year']?>)</li>
<?php			} ?>
			</ul>
<?php	} else { // if clone ?>
			<ul><span class="labels">Other clones</span>
<?php 			$res = $database->query("SELECT name,description,year FROM games WHERE cloneof='$cloneof' ORDER BY year ASC,description ASC") or die("Unable to query database : ".array_pop($database->errorInfo())); 
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><a href="<?=$_SERVER['PHP_SELF']?>?name=<?=$row['name']?>"><?=$row['name']?></a> : <?=$row['description']?> (<?=$row['year']?>)</li>
<?php			} ?>
			</ul>
<?php	} ?>
</div>



<!-- SOUND INFO -->
<?php
$fields = array('sound_channels'=>'INTEGER');
$res = $database->query("SELECT ".join(',',array_keys($fields))." FROM games WHERE name='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC)
?>
<div id="sound_info" class="infos">
<h2><a name="sound_info">Sound infos</a></h2>
<?php
foreach ($fields as $field_name => $field_type) {
	if ($row[$field_name] != '') { // si qqchose a afficher ?>
		<div id="game_<?=$field_name?>" class="info">
			<span class="labels row"><?=ucfirst($field_name)?></span>
			<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
		</div>
<?php
	}
} ?>
</div>


<!-- DRIVERS INFO -->
<?php
$fields = array('driver_status'=>'VARCHAR','driver_emulation'=>'VARCHAR','driver_color'=>'VARCHAR','driver_sound'=>'VARCHAR','driver_graphic'=>'VARCHAR','driver_cocktail'=>'VARCHAR','driver_protection'=>'VARCHAR','driver_savestate'=>'BOOL','driver_palettesize'=>'INTEGER');
$res = $database->query("SELECT ".join(',',array_keys($fields))." FROM games WHERE name='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC)
?>
<div id="sound_info" class="infos">
<h2><a name="driver_info">Driver infos</a></h2>
<?php
foreach ($fields as $field_name => $field_type) { ?>
	<div id="game_<?=$field_name?>" class="info">
		<span class="labels row"><?=ucfirst(str_replace('_',' ',$field_name))?></span>
		<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
	</div>
<?php
} ?>
</div>



<!-- INPUT INFO -->
<?php
$fields = array('input_service'=>'BOOL','input_tilt'=>'BOOL','input_players'=>'INTEGER','input_buttons'=>'INTEGER','input_coins'=>'INTEGER');
$res = $database->query("SELECT ".join(',',array_keys($fields))." FROM games WHERE name='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC);
?>
<div id="input_info" class="infos">
<h2><a name="input_info">Input infos</a></h2>
<?php
foreach ($fields as $field_name => $field_type) {
?>
	<div id="game_<?=$field_name?>" class="info">
		<span class="labels row"><?=ucfirst(str_replace('_',' ',$field_name))?></span>
		<span class="values"><?=$fields[$field_name]=='BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></span>
	</div>
<?php
}

$fields = get_fields_info('games_control');
//print_r($fields);
$res = $database->query("SELECT * FROM games_control WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<table>
	<thead>
		<tr>
<?php
// table header
foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
	<?php foreach ($fields as $field_name => $field_type) { ?>
			<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
	<?php } ?>
			</tr>
<?php } ?>
	</tbody>
</table>
</div>



<!-- DISPLAY INFO -->
<?php
$fields = get_fields_info('games_display');
$res = $database->query("SELECT * FROM games_display WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="display_info" class="infos">
<h2><a name="display_info">Display infos</a></h2>
<table>
	<thead>
		<tr>
<?php
// table header
foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
			<?php foreach ($fields as $field_name => $field_type) { ?>
					<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
			<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>



<!-- CONFIGURATION INFO -->
<?php
$fields = get_fields_info('games_configuration');
$res = $database->query("SELECT * FROM games_configuration WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="configuration_info" class="infos">
<h2><a name="configuration_info">Configuration</a></h2>
<table>
	<thead>
		<tr>
<?php
// table header
foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
<?php
	$i=0;
	foreach ($fields as $field_name => $field_type) { ?>
		<td>
			<?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?>
<?php		if ($i==0) { // 1er champ du tableau
				$fields2 = get_fields_info('games_configuration_confsetting');
				$res2 = $database->query("SELECT * FROM games_configuration_confsetting WHERE configuration_id='$row[id]'") or die("Unable to query database : ".array_pop($database->errorInfo()));		
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<div class="sousinfo">
<?php					foreach ($fields2 as $field_name2 => $field_type2) { ?>
						<div id="games_configuration_confsetting_<?=$field_name2?>" class="info">
							<span class="labels row"><?=ucfirst(str_replace('_',' ',$field_name2))?></span>
							<span class="values"><?=$fields2[$field_name2]=='BOOL' ? bool2yesno($row2[$field_name2]) : $row2[$field_name2] ?></span>
						</div>
<?php				} // fin foreach field ?>
					</div>
<?php			} // fin while row
			} // fin if $i==0 ?><br/>
		</td>
<?php
		$i++;
	} ?>
		</tr>
<?php
} ?>
	</tbody>
</table>
</div>


<!-- DIPSWITCH INFO -->
<?php
$res = $database->query("SELECT * FROM games_dipswitch WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="dipswitch_info" class="infos">
<h2><a name="dipswitch_info">Dipswitch</a></h2>
	<ul>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<li>
			<?=$row['name']?>
			<ul>
<?php			$res2 = $database->query("SELECT * FROM games_dipswitch_dipvalue WHERE dipswitch_id='$row[id]'") or die("Unable to query database : ".array_pop($database->errorInfo()));
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><?=$row2['name']?></li>
<?				} ?>
			</ul>
		</li>
<? } ?>
	</ul>
</div>



<!-- ADJUSTER INFO -->
<?php
$fields = get_fields_info('games_adjuster');
$res = $database->query("SELECT * FROM games_adjuster WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="adjuster_info" class="infos">
<h2><a name="adjuster_info">Adjuster</a></h2>
<table>
	<thead>
		<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
				</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- ROM LIST -->
<?php
$fields = get_fields_info('games_rom');
$res = $database->query("SELECT * FROM games_rom WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="rom_info" class="infos">
<h2><a name="rom_list">Roms list</a></h2>
<table>
	<thead>
		<tr>
<?php
// table header
foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- BIOS SET -->
<?php
$fields = get_fields_info('games_biosset');
$res = $database->query("SELECT * FROM games_biosset WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="biosset_list" class="infos">
<h2><a name="biosset_list">BIOS set</a></h2>
<table>
	<thead>
		<tr>
<?php foreach ($fields as $field_name => $field_type) { ?> 
		<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- CHIP LIST -->
<?php
$fields = get_fields_info('games_chip');
$res = $database->query("SELECT * FROM games_chip WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="chip_info" class="infos">
<h2><a name="chip_list">Chips list</a></h2>
<table>
	<thead>
		<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
// table rows
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- SAMPLE LIST -->
<?php
$fields = get_fields_info('games_sample');
$res = $database->query("SELECT * FROM games_sample WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="sample_info" class="infos">
<h2><a name="sample_list">Samples list</a></h2>
<table>
	<thead>
		<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- DISK LIST -->
<?php
$fields = get_fields_info('games_disk');
$res = $database->query("SELECT * FROM games_disk WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="disk_info" class="infos">
<h2><a name="disk_list">Disks list</a></h2>
<table>
	<thead>
		<tr>
<?php foreach ($fields as $field_name => $field_type) { ?>
			<th><?=$field_name?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr>
		<?php foreach ($fields as $field_name => $field_type) { ?>
				<td><?= $fields[$field_name] == 'BOOL' ? bool2yesno($row[$field_name]) : $row[$field_name] ?></td>
		<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
</div>


<!-- SERIES LIST -->
<?php
//$fields = get_fields_info('games_sample');
$res = $database->query("SELECT * FROM games_series GS,series S WHERE GS.game='$game_name_escape' AND GS.serie_id=S.id") or die("Unable to query database : ".array_pop($database->errorInfo()));
$row = $res->fetch(PDO::FETCH_ASSOC);
?>
<div id="serie_info" class="infos">
<h2><a name="serie_info">Serie</a></h2>
	<div id="serie_title">Serie : <?=$row['serie']?></div>
	<ol class="series">
<?php
$serie_id = $row['id'];
$res = $database->query("SELECT G.description,G.name,G.year FROM games_series GS, games G WHERE GS.game=G.name AND GS.serie_id='$serie_id' ORDER BY G.year ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<li>
<?php	if ($row['name']==$game_name) { // this game ?>
			<?=$row['description']?> (<?=$row['year']?>)
<?php	} else { // not this game ?>
			<a href="<?=$_SERVER['PHP_SELF']?>?name=<?=$row['name']?>"><?=$row['description']?></a> (<?=$row['year']?>)	
<?php	} ?>
		</li>
<?php } ?>
	</ol>
</div>


<!-- CATEGORIES LIST -->
<?php
$res = $database->query("SELECT * FROM categories WHERE game='$game_name_escape' ORDER BY version_added DESC") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="categories_info" class="infos">
<h2><a name="categories_info">Categories</a></h2>
	<ul class="categories">
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { 
	if ($row['version_added'] == 1) { ?>
		<li>Added to Mame in version <a href="search.php?categorie=<?=$row['categorie']?>"><?=$row['categorie']?></a></li>	
<?php } else { ?>
		<li><a href="search.php?categorie=<?=$row['categorie']?>"><?=$row['categorie']?></a></li>
<?php }
	} ?>
	</ul>
</div>


<!-- MAMEINFO LIST -->
<?php
//$fields = get_fields_info('games_sample');
$res = $database->query("SELECT * FROM mameinfo WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="mameinfo_info" class="infos">
<h2><a name="mameinfo_info">MAMEinfo</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) {
	echo preg_replace('/\n/','<br/>',$row['info']);
} ?>
</div>


<!-- HISTORIES -->
<?php
$res = $database->query("SELECT * FROM games_histories GH,histories H WHERE GH.game='$game_name_escape' AND GH.history_id=H.id") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="stories_info" class="infos">
<h2><a name="stories_info">History</a></h2>
<?php $row = $res->fetch(PDO::FETCH_ASSOC) ?>
	<div class="history">
		<a href="<?=$row['link']?>" target="_blank"><?=$row['link']?></a><br>
		<?=preg_replace('/\n/','<br/>',$row['history'])?>
	</div>
</div>


<!-- COMMAND LIST -->
<?php
//$fields = get_fields_info('games_sample');
$res = $database->query("SELECT * FROM games_command GC,command C WHERE GC.game='$game_name_escape' and GC.command_id=C.id") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="command_list" class="infos">
<h2><a name="command_list">Commands list</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<pre class="command"><?=$row['command']?></pre>
<?php } ?>
</div>


<!-- CHEATS LIST -->
<?php
$res = $database->query("SELECT * FROM cheats WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="cheats_list" class="infos">
<h2><a name="cheats_list">Cheats</a></h2>
	<ul>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if (trim($row['cheat']))  { ?>
		<li>
			<?=$row['cheat']?>
			<ul>
<?php			$res2 = $database->query("SELECT * FROM cheats_options WHERE cheat_id='$row[id]'") or die("Unable to query database : ".array_pop($database->errorInfo()));
				while($row2 = $res2->fetch(PDO::FETCH_ASSOC)) { ?>
					<li><?=$row2['option']?></li>
<?				} ?>
			</ul>
		</li>
<?		}
	} ?>
	</ul>
</div>


<!-- STORIES LIST -->
<?php
$res = $database->query("SELECT * FROM stories WHERE game='$game_name_escape'") or die("Unable to query database : ".array_pop($database->errorInfo()));
?>
<div id="highscore_info" class="infos">
<h2><a name="highscore_info">High scores</a></h2>
<?php while($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
	<pre class="command"><?=$row['score']?></pre>
<?php } ?>
</div>

</body>
</html>