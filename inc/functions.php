<?php
session_start();

include_once('config.php');

function load_database() {
	if (file_exists(DATABASE_FILENAME)) {
		try {
			$sqlite = new PDO('sqlite:'.DATABASE_FILENAME); // success
			//$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
			return $sqlite;
		} catch (PDOException $exception) {
			die ("Can't open database : '".DATABASE_FILENAME."' ".$exception->getMessage());
		}
	} else {
		die ("Can't find database : '".DATABASE_FILENAME."'");
	}
} // end function load_database


function get_fields_info($table_name) {
	global $database ;
	$res = $database->query("PRAGMA table_info('$table_name')") or die("Unable to query database : ".array_pop($database->errorInfo()));
	$fields = [];
	while($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if ($row['name'] == 'game' || $row['name'] == 'id' || preg_match('/_id$/',$row['name'])) continue;
		$fields[$row['name']] = $row['type'];
	}
	return $fields;
}

function bool2yesno($bool) {
	return $bool ? 'yes' : 'no';
}

function game_has_info($game,$game_type) {
	global $database;
	$has_info = [
		'games_configuration'=>'','games_control'=>'','games_display'=>'','games_dipswitch'=>'','games_adjuster'=>'',
		'games_rom'=>'','games_biosset'=>'','games_chip'=>'','games_sample'=>'','games_disk'=>'','games_series'=>'','categories'=>'',
		'mameinfo'=>'','games_histories'=>'','games_command'=>'','cheats'=>'','stories'=>'','bestgames'=>'','languages'=>'','nplayers'=>'','mature'=>'','genre'=>''
	];

	if ($game_type == 'arcade') {
		$informations = [
			'games_configuration','games_control','games_display','games_dipswitch','games_adjuster',
			'games_rom','games_biosset','games_chip','games_sample','games_disk','games_series','categories',
			'mameinfo','games_histories','games_command','cheats','stories','bestgames','games_languages','nplayers','mature','genre'
		];
	} else {
		$informations = ['games_rom'];
	}

	foreach ($informations as $table) {
		$has_info[$table] = '';

		// check if table exists
		$res = $database->prepare("SELECT count(*) as table_exists FROM sqlite_master WHERE type='table' AND name=?");
		$res->execute([$table]);
		$row = $res->fetch(PDO::FETCH_ASSOC);

		if ($row['table_exists']) {
			$res = $database->prepare("SELECT count(*) as has_info FROM $table WHERE game=?") or die("Can't prepare : ".array_pop($database->errorInfo()));
			if ($res && $res->execute([$game])) {
				$row = $res->fetch(PDO::FETCH_ASSOC);
				$has_info[$table] = $row['has_info'];
			}
		}
	}

	return $has_info;
}


function reset_session_except() {
	static $criterias = ['sourcefile','nplayers','categorie','language','evaluation','mature','genre','console']; // all criterias
	$except_criterias = func_get_args(); // do not reset thoses criterias
    for ($i=0; $i < sizeof($criterias) ; $i++) {
    	if (!in_array($criterias[$i],$except_criterias))
    		$_SESSION[$criterias[$i]] = '';
    }
}


/**
 * Returns a human readable filesize
 *
 * @author      wesman20 (php.net)
 * @author      Jonas John
 * @version     0.3
 * @link        http://www.jonasjohn.de/snippets/php/readable-filesize.htm
 */
function HumanReadableFilesize($size) {
	$mod = 1024;
	$units = explode(' ','B KB MB GB TB PB');
	for ($i = 0; $size > $mod; $i++) {
		$size /= $mod;
	}
	return round($size, 2) . ' ' . $units[$i];
}