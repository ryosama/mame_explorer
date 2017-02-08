<?php

// page de resultat de recherchye de rom
include_once('inc/config.php');
include_once('inc/functions.php');
$database = load_database();

/////////// GET A MANUFACTURER LIST BASE ON KEYBOARD TYPPING ///////////////
if (	isset($_GET['what']) && $_GET['what']=='get_manufacturer'
	&& 	isset($_GET['val']) && strlen($_GET['val'])>0) {
	$results = array();

	$sql = "SELECT DISTINCT(manufacturer) as manufacturer FROM games WHERE manufacturer like '%".sqlite_escape_string($_GET['val'])."%' AND manufacturer IS NOT NULL AND manufacturer<>'' and manufacturer NOT LIKE '<%>' ORDER BY manufacturer ASC";

	$res = $database->query($sql) or die("Unable to select manufacturer : ".array_pop($database->errorInfo()));
	while($row = $res->fetch(PDO::FETCH_ASSOC))
		$results[] = $row['manufacturer'];

	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode(array(	'response'=> array( 'manufacturers' => $results),
							'request' => array(	'what'			=> $_GET['what'],
												'val'			=> $_GET['val'])
			));



/////////// SEARCH ON VIDEO WEB SITE FOR VIDEO ABOUT A GAME ///////////////
} elseif (	isset($_GET['what']) && $_GET['what']=='get_video'
		&& 	isset($_GET['game']) && strlen($_GET['game'])>0) {

	$game = $_GET['game'];

	// search on youtube with thoses terms : mame gameplay video snapshot rom name <game_name>
	// only search for parents
	// https://www.youtube.com/results?search_query=mame+gameplay+video+snapshot+rom+name+<game_name>
	$search_results = join('',file('https://www.youtube.com/results?search_query=mame+gameplay+video+snapshot+rom+name+'.urlencode($game)));

	/*
	youtube link look like that
	<a href="/watch?v=b96RF0xj-N0" class="yt-uix-tile-link yt-ui-ellipsis yt-ui-ellipsis-2 yt-uix-sessionlink      spf-link " data-sessionlink="itct=CEQQ3DAYACITCMu-vveJgNICFQXSHAodKpIPlSj0JFItbWFtZSBnYW1lcGxheSB2aWRlbyBzbmFwc2hvdCByb20gbmFtZSBhc3Rlcml4"  title="<game_description> MAME Gameplay video Snapshot -Rom name <game_name>-" rel="spf-prefetch" aria-describedby="description-id-883038" dir="ltr"><game_description> MAME Gameplay video Snapshot -Rom name <game_name>-</a>
	*/

	// extract link and foreach one
	// search for some thing in url "MAME Gameplay video Snapshot rom name <game_name>"
	//              .+?MAME\s+Gameplay\s+video\s+Snapshot\s+-?Rom\s+name\s+$game-?\s*
	// Asterix ver EAD MAME Gameplay video Snapshot -Rom name asterix-

	$nb_videos_links = preg_match("/<a\s+href=\"\/watch\?v=([^\"]+)\"[^>]+>(.+?MAME\s+Gameplay\s+video\s+Snapshot\s+-?Rom\s+name\s+$game-?\s*)<\/a>/i", $search_results, $regs);

	$video_found	= false;
	$video_id 		= '';
	$video_title 	= '';
	$video_website 	= '';
	$video_url 		= '';
	$video_html 	= '';
	if ($nb_videos_links > 0) { // found a video
		$video_found	= true;
		$video_id 		= $regs[1];
		$video_title 	= $regs[2];
		$video_website 	= 'youtube';
		$video_url 		= 'https://www.youtube.com/watch?v='.urlencode($video_id);
		$video_html 	= '<iframe width="320" height="240" src="https://www.youtube.com/embed/'.urlencode($video_id).'?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe>';
	}

	header('Content-type: text/json');
	echo json_encode(array(	'response'=> array( 'video_found'	=> $video_found,
												'video_id'		=> $video_id,
												'video_title'	=> $video_title,
												'video_website' => $video_website,
												'video_url'		=> $video_url,
												'video_html'	=> $video_html),

							'request' => array(	'what'			=> $_GET['what'],
												'game'			=> $_GET['game'])
			));


} else {
	header('Content-type: text/text');
	echo "No valide action";
}