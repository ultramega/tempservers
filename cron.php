<?php
require('include/common.php');

$db = DB::get();

if($result = $db->query("SELECT * FROM `schedule` a LEFT JOIN `servers` b ON a.`serverID` = b.`serverID` WHERE a.`active` = '1'")) {
    $now = time();
    while($row = $result->fetch_assoc()) {
        if($row['status'] != 1 && ($row['start'] <= $now && $row['end'] >= $now)) {
            $serverObj = new ServerControl($row['sid'], $row['serverID'], $row['game'], $row['rcon']);
            $serverObj->createCfg();
            $serverObj->start();
            log_action('RSTATE', 'begins', $row['sid']);
        }
        elseif($row['active'] == 1 && $row['end'] <= $now) {
            if($row['status'] == 1) {
                $serverObj = new ServerControl($row['sid'], $row['serverID'], $row['game']);
                $serverObj->stop();
                sleep(1);
            }
            $db->query("UPDATE `schedule` SET `active` = '2' WHERE `sid` = '" . $row['sid'] . "'");
            Installer::uninstall($row['sid'], $row['host']);
            log_action('RSTATE', 'ends', $row['sid']);
        }
    }
    $result->close();
}

$url = 'http://twitter.com/statuses/user_timeline.xml?screen_name=tempservers';
if($result = $db->query("SELECT `twid` FROM `news` ORDER BY `id` DESC LIMIT 1")) {
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last = $row['twid'];
        $url = sprintf('%s&since_id=%s', $url, $last);
    }
    $result->close();
}
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$data = curl_exec($curl);
curl_close($curl);

$xmlDoc = DOMDocument::loadXML($data);
$statuses = $xmlDoc->getElementsByTagName('status');
if($statuses->length > 0) {
    $updates = array();
    foreach($statuses as $status) {
        $id = $status->getElementsByTagName('id')->item(0)->nodeValue;
        $text = $status->getElementsByTagName('text')->item(0)->nodeValue;
        $date = strtotime($status->getElementsByTagName('created_at')->item(0)->nodeValue);
        $updates[] = array($id, $text, $date);
    }
    krsort($updates);
    foreach($updates as $update) {
        if(substr($update[1], 0, 1) != '@') {
            $id = $db->escape_string($update[0]);
            $value = $db->escape_string($update[1]);
            $time = $db->escape_string($update[2]);
            $db->query("INSERT INTO `news` VALUES ('', '" . $id . "', '" . $value . "', '" . $time . "')");
        }
    }
}
?>