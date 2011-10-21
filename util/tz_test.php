<?php
require('../includes/config.php');

$zones = array_keys($zonelist);
foreach($zones as $zone) {
	try {
		$obj = new DateTimeZone($zone);
		echo $zone . '<br />';
	}
	catch(Exception $e) {
		echo $e->getMessage() . '<br />';
	}
}
?>