<?php
require('config.php');

function __autoload($class) {
    require_once(strtolower($class) . '.class.php');
}

$userData = new Session();

/**
 * Convert seconds into readable time
 *
 * @param <int> $secs Time in seconds
 * @return <string> Formatted string representing time
 */
function format_time($secs) {
    $times = array(3600, 60, 1);
    $time = '';
    $tmp = '';
    for($i = 0; $i < 3; $i++) {
        $tmp = floor($secs / $times[$i]);
        if($tmp < 1) {
            $tmp = '00';
        }
        elseif($tmp < 10) {
            $tmp = '0' . $tmp;
        }
        $time .= $tmp;
        if($i < 2) {
            $time .= ':';
        }
        $secs = $secs % $times[$i];
    }
    return $time;
}

/**
 * End execution if maintenence mode is enabled
 */
function check_mm() {
    if(MNTNC_MODE) {
        $template = new Template('Service Unavailable');
        $template->head();
        echo '<div id="body-center"><div class="block"><p class="attn">' . MNTNC_MESSAGE . '</p></div></div>';
        $template->foot();
        exit;
    }
}

/**
 * Generate random string
 *
 * @param <int> $length Optional length (default 6)
 * @return <string> Random string
 */
function gen_key($length = 6) {
    return substr(md5(uniqid(mt_rand(), true)), 0, $length);
}

/**
 * Executes a single command over SSH2
 *
 * @param <string> $cmd Command to execute
 * @param <int> $host Host ID
 * @return <bool> TRUE on success
 */
function ssh($cmd, $host) {
    global $hostlist;
    if($ssh = new SSH($hostlist[$host])) {
        return $ssh->exec($cmd);
    }
    return false;
}

/**
 * Logs an action
 *
 * @param <string> $action Action to log
 */
function log_action($action) {
    $msg = 'Message';
    switch($action) {
        case 'LOGIN':
            if(func_num_args() == 2) {
                $user = func_get_arg(1);
                $msg = sprintf('User <%s><%s> failed to log in', $user, $_SERVER['REMOTE_ADDR']);
            }
            else {
                $msg = sprintf('User <%s><%s> successfully logged in', $_SESSION['user'], $_SERVER['REMOTE_ADDR']);
            }
            break;
        case 'REGISTER':
            if(func_num_args() == 4) {
                $user = func_get_arg(1);
                $email = func_get_arg(2);
                $tz = func_get_arg(3);
                $msg = sprintf('User <%s><%s><%s><%s> registered', $user, $_SERVER['REMOTE_ADDR'], $email, $tz);
            }
            break;
        case 'USER_ACTIVATE':
            if(func_num_args() == 3) {
                $user = func_get_arg(1);
                $email = func_get_arg(2);
                $msg = sprintf('User <%s><%s><%s> activated account', $user, $_SERVER['REMOTE_ADDR'], $email);
            }
            break;
        case 'RESERVE':
            if(func_num_args() == 5) {
                $sid = func_get_arg(1);
                $start = func_get_arg(2);
                $end = func_get_arg(3);
                $game = func_get_arg(4);
                $msg = sprintf('User <%s><%s> submitted reservation <%d><%d-%d><%s>', $_SESSION['user'], $_SERVER['REMOTE_ADDR'], $sid, $start, $end, $game);
            }
            break;
        case 'ACTIVATE':
            if(func_num_args() == 3) {
                $sid = func_get_arg(1);
                $server = func_get_arg(2);
                $msg = sprintf('Reservation #%d activated on server %s', $sid, $server);
            }
            break;
        case 'RSTATE':
            if(func_num_args() == 3) {
                $event = func_get_arg(1);
                $sid = func_get_arg(2);
                $msg = sprintf('Reservation #%d %s', $sid, $event);
            }
            break;
        case 'RESTART':
            $msg = sprintf('User <%s><%s> restarted server at res #%d', $_SESSION['user'], $_SERVER['REMOTE_ADDR'], $_SESSION['sid']);
            break;
        case 'GAMESWITCH':
            if(func_num_args() == 2) {
                $game = func_get_arg(1);
                $msg = sprintf('User <%s><%s> switched game to %s at res #%d', $_SESSION['user'], $_SERVER['REMOTE_ADDR'], $game, $_SESSION['sid']);
            }
            break;
        case 'PPERROR':
            if(func_num_args() == 7) {
                $sid = func_get_arg(1);
                $txn_id = func_get_arg(2);
                $receiver = func_get_arg(3);
                $payer = func_get_arg(4);
                $amount = func_get_arg(5);
                $status = func_get_arg(6);
                $msg = sprintf('Payment error <SID #%s><TXN %s><RES %s><PID %s><AMT %s><%s>', $sid, $txn_id, $receiver, $payer, $amount, $status);
            }
            break;
        default:
            return;
    }
    $file = sprintf('%s/log/%s.log', PATH, date('Ymd'));
    $msg = sprintf("%s - %s:	%s.\r\n", date('m/d/Y H:i:s'), $action, $msg);
    file_put_contents($file, $msg, FILE_APPEND);
}