<?php

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
	$fields = array();
	while($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if ($row['name'] == 'game' || $row['name'] == 'id' || preg_match('/_id$/',$row['name'])) continue;
		$fields[$row['name']] = $row['type'];
	}
	return $fields;
}

function bool2yesno($bool) {
	return $bool ? 'yes' : 'no';
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

?>