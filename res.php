<?php
require('include/common.php');

if(isset($_POST['date_start'], $_POST['date_end'], $_POST['time_start'], $_POST['time_end'], $_POST['rcon'])) {
    $dtZone = new DateTimeZone($userData->tz);
    $dt = new DateTime('now', $dtZone);
    $date_start = explode('/', $_POST['date_start']);
    $date_end = explode('/', $_POST['date_end']);
    if(count($date_start) != 3 || !checkdate($date_start[0], $date_start[1], $date_start[2])
            || count($date_end) != 3 || !checkdate($date_end[0], $date_end[1], $date_end[2])) {
        echo 3;
    }
    else {
        $dt->setDate($date_start[2], $date_start[0], $date_start[1]);
        $dt->setTime($_POST['time_start'], 0, 0);
        $start = $dt->format('U');
        $dt->setDate($date_end[2], $date_end[0], $date_end[1]);
        $dt->setTime($_POST['time_end'], 0, 0);
        $end = $dt->format('U');

        if(DB::get()->query("SELECT * FROM `schedule` WHERE `end` >= '" . $start . "' AND `start` <= '" . $end . "'")->num_rows >= SERVER_LIMIT) {
            echo 4;
        }
    }
    if(preg_match('/^[0-9A-Z]+$/i', $_POST['rcon']) == 0) {
        echo 5;
    }
}
?>