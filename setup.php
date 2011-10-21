<?php
require('include/common.php');
check_mm();

if($userData->loggedIn()) {
    $tz = $userData->tz;
}
else {
    $tz = DEFAULT_TZ;
}
$dtZone = new DateTimeZone($tz);
$tzone = reset($dtZone->getTransitions());
$tzone = $tzone['abbr'];
$dt = new DateTime('now', $dtZone);

if(isset($_POST['submit'])) {
    if(isset($_POST['user'], $_POST['pass']) && !$userData->loggedIn()) {
        $userData->authUser($_POST['user'], $_POST['pass']);
    }
    $date_start = explode('/', $_POST['date_start']);
    $date_end = explode('/', $_POST['date_end']);
    if(!$userData->loggedIn()) {
        $msg = 'You must be logged in to book a server.';
    }
    elseif(empty($_POST['date_start']) || empty($_POST['date_end'])
       || !isset($_POST['time_start']) || $_POST['time_start'] < 0 || $_POST['time_start'] > 23
       || !isset($_POST['time_end']) || $_POST['time_end'] < 0 || $_POST['time_end'] > 23
       || empty($_POST['rcon_password'])
       || empty($_POST['game']) || strcmp($_POST['game'], '--Select Game--') == 0) {
        $msg = 'Please fill in all required fields.';
    }
    elseif(count($date_start) != 3 || !checkdate($date_start[0], $date_start[1], $date_start[2])
          || count($date_end) != 3 || !checkdate($date_end[0], $date_end[1], $date_end[2])) {
        $msg = 'Error: Invalid date entered.';
    }
    elseif(!array_key_exists($_POST['game'], $gamelist)) {
        $msg = 'Error: Selected game is currently unavailable.';
    }
    elseif(preg_match('/^[0-9A-Z]+$/i', $_POST['rcon_password']) == 0) {
        $msg = 'Please limit the Rcon password to alphanumeric characters (0-9, A-Z).';
    }
    else {
        $db = DB::get();

        $dt->setDate($date_start[2], $date_start[0], $date_start[1]);
        $dt->setTime($_POST['time_start'], 0, 0);
        $start = $dt->format('U');

        $dt->setDate($date_end[2], $date_end[0], $date_end[1]);
        $dt->setTime($_POST['time_end'], 0, 0);
        $end = $dt->format('U');

        $length = ($end-$start)/3600;

        $credits = 0;
        if($result = $db->query("SELECT `credits` FROM `users` WHERE `id` = '" . $userData->getUid() . "'")) {
            $row = $result->fetch_assoc();
            $result->close();
            $user_credits = $row['credits'];
            if($result = $db->query("SELECT SUM(`credits_used`) as `credits_used` FROM `trans` WHERE `sid` IN (SELECT `sid` FROM `schedule` WHERE `uid` = '" . $userData->getUid() . "' AND `active` = '0')")) {
                $row = $result->fetch_assoc();
                $result->close();
                $user_credits -= $row['credits_used'];
            }
            if($user_credits > $length) {
                $credits = $length;
            }
            elseif($user_credits > 0) {
                $credits = $user_credits;
            }
        }

        $cost = ($length - $credits) * PRICE;
        $rcon = $db->escape_string($_POST['rcon_password']);
        $game = $db->escape_string($_POST['game']);
        if(time() > $start) {
            $msg = 'Error: Selected time is in the past! Please choose a time in the future.';
        }
        elseif($length <= 0) {
            $msg = 'Error: End time is before the start time. Time can only go forward in this universe!';
        }
        elseif($length < MIN_HOURS || $length > MAX_HOURS) {
            $msg = 'Error: Number of hours must be between ' . MIN_HOURS . ' and ' . MAX_HOURS . '.';
        }
        elseif($db->query("SELECT * FROM `schedule` WHERE `end` >= '" . $start . "' AND `start` <= '" . $end . "'")->num_rows >= SERVER_LIMIT) {
            $msg = 'Error: All available servers are booked within this timeslot.';
        }
        elseif($db->query("INSERT INTO `schedule` VALUES ('', '" . $userData->getUid() . "', '0', '" . $start . "', '" . $end . "', '" . $rcon . "', '" . $game . "', '')")) {
            $sid = $db->insert_id;
            $userData->setSid($sid);
            if($db->query("INSERT INTO `trans` VALUES ('', '', '" . $sid . "', '" . $cost . "', '" . $credits . "', '', '" . time() . "')")) {
                $config = new ServerConfig($sid);
                if(!empty($_POST['hostname']) || !empty($_POST['sv_password'])) {
                    $opts = array();
                    $vals = array();
                    if(!empty($_POST['hostname'])) {
                        $opts[] = 'hostname';
                        $vals[] = $_POST['hostname'];
                    }
                    if(!empty($_POST['sv_password'])) {
                        $opts[] = 'sv_password';
                        $vals[] = $_POST['sv_password'];
                    }
                    $config->set($opts, $vals);
                }
                $config->store();

                $mcObj = new Mapcycle($sid);
                $mcObj->loadDefaultMapcycle($_POST['game']);

                log_action('RESERVE', $sid, $start, $end, $game);
                header("Location: order");
                exit;
            }
            else {
                $msg = 'Error: There was a problem with your request. Please contact support.';
            }
        }
        else {
            $msg = 'Error: There was a problem with your request. Please contact support.';
        }
    }
}

$template = new Template('Server Booking');
$template->appendToHead('<script type="text/javascript">var tsPrice = ' . PRICE . ', tsMax = ' . MAX_HOURS . ', tsMin = ' . MIN_HOURS . ';</script>');
$template->appendToHead('<script type="text/javascript" src="js/functions.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/setup.js"></script>');
$template->head();
?>
<div id="sidebar">
  <div class="block">
    <div class="top">
      <h1>Notes</h1>
    </div>
    <div class="content">
      <ul class="arrow">
        <li>Fields marked with * are required</li>
        <li>Selected game can be changed at any time after reservation</li>
        <li>We accept all payments via <strong>PayPal</strong></li>
      </ul>
      <div class="center"><img src="images/paypal.gif" alt="Payments by PayPal" width="150" height="40" /></div>
    </div>
  </div>
</div>
<div id="body">
  <div class="block">
    <h1>Server Booking</h1>
    <p>To book a server, fill out this form with details about your
    account, desired time slot, and game. Click <strong>Book Server</strong>
    to verify and submit your reservation.</p>
<?php
$userData->showMessage();
if(isset($msg)) {
    echo '<div class="error">' . $msg . '</div>';
}
?>
    <div id="error" class="error hidden"></div>
    <form id="setup" method="post" action="setup">
      <fieldset>
      <legend>Account</legend>
<?php
if(!$userData->loggedIn()) {
?>
      <div id="userstatus" class="nologon">Not logged in.</div>
      <div id="modalform" title="Please Login">
          <form method="post" action="login">
              <div class="error hidden" id="loginerror">Error: Invalid username or password</div>
              <label for="user" class="float">Username*: </label>
              <input type="text" name="user" id="user" /><br />
              <label for="pass" class="float">Password* : </label>
              <input type="password" name="pass" id="pass" /><br />
              <div><a id="registerlink" href="register">Register</a> | <a href="passreset">Lost your password?</a></div>
          </form>
      </div>
<?php
}
else {
?>
      <div id="userstatus">Logged in as <strong><?php echo $userData->getUser(); ?></strong>.</div>
<?php
}
?>
      </fieldset>
      <fieldset>
      <legend>Time Slot</legend>
      <label for="date_start" class="float">Start Time*: </label>
      <input type="text" id="date_start" name="date_start"<?php if(!empty($_POST['date_start'])) { echo ' value="' . htmlspecialchars($_POST['date_start']) . '"'; } ?> />
      <select id="time_start" name="time_start" class="right">
        <option value="-1"<?php if(!isset($_POST['time_start']) || !($isnum = is_numeric($_POST['time_start']))) { echo ' selected="selected"'; } ?>>--Select Time--</option>
<?php
for($i = 0, $t = 12, $ap = 'A'; $i < 24; $i++) {
?>
        <option value="<?php echo $i; ?>"<?php if(isset($_POST['time_start']) && $isnum && $_POST['time_start'] == $i) { echo ' selected="selected"'; } ?>><?php echo $t . ':00 ' . $ap . 'M'; ?></option>
<?php
    if($t == 12) {
        $t = 1;
    }
    else {
        $t++;
    }
    if($i == 11) {
        $ap = 'P';
    }
}
?>
      </select>
      <br />
      <label for="date_start" class="float">End Time*: </label>
      <input type="text" id="date_end" name="date_end"<?php if(!empty($_POST['date_start'])) { echo ' value="' . htmlspecialchars($_POST['date_start']) . '"'; } ?> />
      <select id="time_end" name="time_end" class="right">
        <option value="-1"<?php if(!isset($_POST['time_end']) || !($isnum = is_numeric($_POST['time_end']))) { echo ' selected="selected"'; } ?>>--Select Time--</option>
<?php
for($i = 0, $t = 12, $ap = 'A'; $i < 24; $i++) {
?>
        <option value="<?php echo $i; ?>"<?php if(isset($_POST['time_end']) && $isnum && $_POST['time_end'] == $i) { echo ' selected="selected"'; } ?>><?php echo $t . ':00 ' . $ap . 'M'; ?></option>
<?php
    if($t == 12) {
        $t = 1;
    }
    else {
        $t++;
    }
    if($i == 11) {
        $ap = 'P';
    }
}
?>
      </select>
	  <div id="previewtime" class="center"></div>
      <div class="note">Times are in <?php echo $tzone; ?>. The time is currently <strong><?php echo $dt->format('g:i A'); ?></strong>.</div>
      </fieldset>
      <fieldset>
      <legend>Game Settings</legend>
      <label for="game" class="float">Game*: </label>
      <select id="game" name="game">
        <option<?php if(empty($_POST['game']) || $_POST['game'] == '--Select Game--') { echo ' selected="selected"'; } ?>>--Select Game--</option>
<?php
foreach($gamelist as $game => $title) {
?>
        <option value="<?php echo $game; ?>"<?php if(!empty($_POST['game']) && $_POST['game'] == $game) { echo ' selected="selected"'; } ?>><?php echo htmlspecialchars($title); ?></option>
<?php
}
?>
      </select>
      <br />
      <label for="rcon_password" class="float">Rcon Password*: </label>
      <input type="text" id="rcon_password" name="rcon_password" maxlength="64"<?php if(!empty($_POST['rcon_password'])) { echo ' value="' . htmlspecialchars($_POST['rcon_password']) . '"'; } ?> />
      <br />
      <label for="hostname" class="float">Server Title: </label>
      <input type="text" id="hostname" name="hostname"<?php if(!empty($_POST['hostname'])) { echo ' value="' . htmlspecialchars($_POST['hostname']) . '"'; } ?> />
      <br />
      <label for="sv_password" class="float">Server Password: </label>
      <input type="text" id="sv_password" name="sv_password"<?php if(!empty($_POST['sv_password'])) { echo ' value="' . htmlspecialchars($_POST['sv_password']) . '"'; } ?> />
      </fieldset>
      <div class="submit">
        <input type="submit" id="submit" name="submit" value="Book Server" />
      </div>
    </form>
  </div>
</div>
<?php
$template->foot();
?>