<?php

// page de resultat de recherchye de rom
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

if (	isset($_GET['what']) && $_GET['what']=='get_manufacturer'
	&& 	isset($_GET['val']) && strlen($_GET['val'])>0) {
	$results = array();

	$sql = "SELECT DISTINCT(manufacturer) as manufacturer FROM games WHERE manufacturer like '%".sqlite_escape_string($_GET['val'])."%' ORDER BY manufacturer ASC LIMIT 0,20";

	$res = $database->query($sql) or die("Unable to select manufacturer : ".array_pop($database->errorInfo()));
	while($row = $res->fetch(PDO::FETCH_ASSOC))
		$results[] = $row['manufacturer'];

	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode(array(	'response'=> array( 'manufacturers' => $results),
							'request' => array(	'what'			=> $_GET['what'],
												'val'			=> $_GET['val'])
			));

} else {
	header('Content-type: text/text');
	echo "No valide action";
}