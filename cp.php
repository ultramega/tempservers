<?php
require('include/common.php');

if(isset($_REQUEST['mode']) && $userData->loggedIn() && $userData->sid > 0) {
    $db = DB::get();
    switch($_REQUEST['mode']) {
        case 'time':
            if($result = $db->query("SELECT `end` FROM `schedule` WHERE `sid` = '" . $userData->sid . "'")) {
                $data = $result->fetch_assoc();
                echo $data['end']-time();
                $result->close();
            }
            break;
        case 'info':
            if($result = $db->query("SELECT `game` FROM `schedule` WHERE `sid` = '" . $userData->sid . "'")) {
                $data = $result->fetch_assoc();
                echo '{items:[{"field":"game","value":"' . $gamelist[$data['game']] . '"}]}';
                $result->close();
            }
            break;
        case 'getcfg':
            $cfgObj = new ServerConfig($userData->sid);
            $cfgObj->fetch();
            $data = array('raw' => $cfgObj->get(false), 'array' => $cfgObj->get(true));
            echo json_encode($data);
            break;
        case 'restart':
            if($res = new Reservation($userData->sid)) {
                if($res->isCurrent()) {
                    $row = $res->getInfo();
                    $serverObj = new ServerControl($row['sid'], $row['serverID'], $row['game'], $row['rcon']);
                    $serverObj->restart();
                }
            }
            if(isset($_GET['mode'])) {
                echo 'done';
            }
            elseif(isset($_POST['mode'])) {
                header('Location: panel');
                exit;
            }
            break;
        case 'config':
            if($res = new Reservation($userData->sid)) {
                $row = $res->getInfo();
                if($row['active'] < 2) {
                    $configObj = new ServerConfig($userData->sid);
                    if(isset($_REQUEST['raw'], $_REQUEST['config'])) {
                        $configObj->parseCfg($_REQUEST['config']);
                    }
                    else {
                        if(isset($_POST['configserver'])) {
                            $cvars = $_POST;
                        }
                        elseif(isset($_GET['configserver'])) {
                            $cvars = $_GET;
                        }
                        $keys = array();
                        $vals = array();
                        foreach($cvars as $key => $value) {
                            if(strtolower($key) != 'mode' && strtolower($key) != 'configserver' && strtolower($key) != 'addcvar' && strtolower($key) != 'phpsessid' && ($value != '' || $key == 'sv_password')) {
                                $keys[] = $key;
                                $vals[] = $value;
                            }
                        }
                        $configObj->set($keys, $vals);
                    }
                    $configObj->store();
                    $serverObj = new ServerControl($userData->sid, $row['serverID'], $row['game']);
                    $serverObj->createCfg();
                    if($row['status'] == 1) {
                        $serverObj->send('exec server.cfg');
                    }
                }
                if(isset($_GET['mode'])) {
                    echo 'done';
                }
            }
            if(isset($_POST['mode'])) {
                header('Location: panel');
                exit;
            }
            break;
        case 'mapcycle':
            if($res = new Reservation($userData->sid)) {
                $row = $res->getInfo();
                if($row['active'] < 2) {
                    $mcObj = new Mapcycle($userData->sid);
                    $mcObj->storeMapcycle($_REQUEST['mapcycle']);
                    $serverObj = new ServerControl($userData->sid, $row['serverID'], $row['game']);
                    $serverObj->createCfg();
                }
                if(isset($_GET['mode'])) {
                    echo 'done';
                }
            }
            if(isset($_POST['mode'])) {
                header('Location: panel');
                exit;
            }
            break;
        case 'switch':
            if(isset($_REQUEST['game'], $gamelist[$_REQUEST['game']])) {
                $game = $db->escape_string($_REQUEST['game']);
                if($res = new Reservation($userData->sid)) {
                    $mcObj = new Mapcycle($userData->sid);
                    $mcObj->loadDefaultMapcycle($_REQUEST['game']);
                    $cfgObj = new ServerConfig($userData->sid);
                    $cfgObj->resetConfig();
                    $row = $res->getInfo();
                    if($row['status'] == 1) {
                        $serverObj = new ServerControl($row['sid'], $row['serverID'], $row['game']);
                        $serverObj->stop();
                        sleep(1);
                    }
                    Installer::reinstall($userData->sid, $row['host'], $_REQUEST['game']);
                    if($res->isCurrent()) {
                        sleep(1);
                        $serverObj = new ServerControl($row['sid'], $row['serverID'], $game, $row['rcon']);
                        $serverObj->prepareCfg();
                        $serverObj->start();
                    }
                    $db->query("UPDATE `schedule` SET `game` = '" . $game . "' WHERE `sid` = '" . $userData->sid . "'");
                    log_action('GAMESWITCH', $_REQUEST['game']);
                }
                if(isset($_GET['mode'])) {
                    echo 'done';
                }
            }
            if(isset($_POST['mode'])) {
                header('Location: panel');
                exit;
            }
            break;
    }
}
?>