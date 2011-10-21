<?php
require('../include/common.php');
$db = DB::get();
$data = file_get_contents('test.xml');
$xmlDoc = DOMDocument::loadXML($data);
$statuses = $xmlDoc->getElementsByTagName('status');
$updates = array();
foreach($statuses as $status) {
	$id = $status->getElementsByTagName('id')->item(0)->nodeValue;
	$text = $status->getElementsByTagName('text')->item(0)->nodeValue;
	$updates[$id] = $text;
}
ksort($updates);
foreach($updates as $id => $value) {
	$id = $db->escape_string($id);
	$value = $db->escape_string($value);
	$db->query("INSERT INTO `news` VALUES ('', '" . $id . "', '" . $value . "')");
}
?>