<?php
require('../include/common.php');
$template = new Template2('home');
$games = array();
foreach(array_values($gamelist) as $game) {
	$games[] = array('GAME' => $game);
}
$template->insertValue('GAMES', $games);

$news = array();
if($result = DB::get()->query("SELECT `text`, `time` FROM `news` ORDER BY `id` DESC LIMIT 5")) {
	while($row = $result->fetch_assoc()) {
		$entry = htmlspecialchars($row['text']);
		if(isset($_SESSION['uid'])) {
			$dtZone = new DateTimeZone($_SESSION['tz']);
			$dtStart = new DateTime(date('r', $row['time']));
			$dtStart->setTimeZone($dtZone);
			$time = $dtStart->format(DFORMAT . ' ' . TFORMAT);
		}
		else {
			$time = date(DFORMAT . ' ' . TFORMAT, $row['time']);
		}
		$news[] = array('TIME' => $time, 'ENTRY' => $entry);
	}
	$result->close();
}
$template->insertValue('FEED', $news);

$template->output();
?>