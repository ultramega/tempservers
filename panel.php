<?php
require('include/common.php');

if(!$userData->loggedIn()) {
	header('Location: login?redir=panel');
	exit;
}

$db = DB::get();
$admin = $userData->isAdmin();

$dtZone = new DateTimeZone($userData->tz);

$default_cvars = array('hostname', 'sv_password', 'mp_timelimit');

if(isset($_GET['sid'])) {
	if($admin || Reservation::checkAccess($_GET['sid'], $userData->getUid())) {
		$userData->setSid($_GET['sid']);
	}
}
$template = new Template('Server Control Panel');
$template->appendToHead('<link href="css/ui.multiselect.css" rel="stylesheet" type="text/css" />');
$template->appendToHead('<script type="text/javascript" src="js/jquery.cookie.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/ui.multiselect.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/functions.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/cp.js"></script>');
$template->head();

$userData->showMessage();
if($admin) {
	$result = Reservation::listAll();
}
else {
	$result = Reservation::listUser($userData->getUid());
}
if($result !== false) {
	$num = count($result);
	if($num == 1) {
		$row = $result[0];
		$userData->setSid($result[0]['sid']);
	}
	else {
?>
<div id="body-wide">
  <div class="block">
<?php
		if($num > 1) {
?>
  <h1>Server Schedule</h1>
  <table cellspacing="0">
    <tr><th>Game</th><th>Time Slot</th><?php if($userData->isAdmin) { echo '<th>User</th>'; } ?></tr>
<?php
			foreach($result as $row) {
				$dtStart = new DateTime(date('r', $row['start']));
				$dtStart->setTimeZone($dtZone);
				$start = $dtStart->format(TFORMAT);
				$date = $dtStart->format(DFORMAT);
				$dtEnd = new DateTime(date('r', $row['end']));
				$dtEnd->setTimeZone($dtZone);
				$end = $dtEnd->format(TFORMAT);
				if($admin) {
					$user = htmlspecialchars($row['user']);
				}
?>
    <tr<?php if($row['sid'] == $userData->sid) { echo ' class="focus"'; } ?>>
      <td><a href="panel?sid=<?php echo $row['sid']; ?>"><?php echo htmlspecialchars($gamelist[$row['game']]); ?></a></td>
      <td><?php echo $start; ?> - <?php echo $end; ?> on <?php echo $date; ?></td>
<?php if(isset($user)) { echo '      <td>' . $user . '</td>' . "\n"; } ?>
    </tr>
<?php
			}
?>
  </table>
<?php
		}
		else {
?>
    <p class="attn">There are no servers reserved for this account. <a href="setup">Reserve a server here</a>.</p>
<?php
		}
?>
  </div>
</div>
<?php
	}
}
if($userData->sid != 0) {
	if($res = new Reservation($userData->sid)) {
		$info = $res->getInfo();
		$now = time();
		if($info['active'] == 0) {
?>
<div id="body-center">
  <div class="block">
    <p class="attn">This reservation has not yet been activated or does not exist.</p>
  </div>
</div>
<?php
		}
		elseif($info['active'] == 2) {
?>
<div id="body-center">
  <div class="block">
    <p class="attn">This reservation has expired.</p>
  </div>
</div>
<?php
		}
		else {
			if(empty($info['ip'])) {
				$info['ip'] = 'TBA';
			}
			$diff = $info['end']-$now;
			if($diff > 0) {
				$timeleft = format_time($diff);
			}
			else {
				$timeleft = 'None';
			}
			$dtStart = new DateTime(date('r', $info['start']));
			$dtStart->setTimeZone($dtZone);
			$start = $dtStart->format(TFORMAT);
			$date = $dtStart->format(DFORMAT);
			$dtEnd = new DateTime(date('r', $info['end']));
			$dtEnd->setTimeZone($dtZone);
			$end = $dtEnd->format(TFORMAT);
			
			$configObj = new ServerConfig($userData->sid);
			$configObj->fetch();
			$scfg = $configObj->get(true);
			
			$showMc = false;
                        $mcObj = new Mapcycle($userData->sid);
			if($default_maps = $mcObj->getDefaultMapcycle($info['game'], true)) {
                            $mapcycle = $mcObj->getMapcycle(true);
                            $showMc = true;
                        }
			
			$ftp_host = substr($info['ip'], 0, strpos($info['ip'], ':'));
			$ftp_user = 'ts' . $userData->sid;
			$ftp_pass = $info['rcon'];
			$ftp_link = 'ftp://' . rawurlencode($ftp_user) . ':' . rawurlencode($ftp_pass) . '@' . $ftp_host;
?>
<div id="sidebar">
  <div class="block">
    <div class="top">
      <h1>Server Restart</h1>
    </div>
    <div class="content center"> If your server becomes unresponsive or otherwise needs to restart, use this button:
      <form id="serverrestart" method="post" action="cp">
        <div>
          <input type="hidden" name="mode" value="restart" />
          <input type="submit" name="restart" id="restart" value="Restart Server" />
          <div class="loading" id="restartstatus"><img src="images/ajax-loader.gif" alt="Loading" /> Working...</div>
        </div>
      </form>
    </div>
  </div>
  <div class="block">
    <div class="top">
      <h1>Switch Game</h1>
    </div>
    <div class="content center"> Choose a game from the list below to switch your server to that game:
      <form id="servergame" method="post" action="cp">
        <div>
          <input type="hidden" name="mode" value="switch" />
          <select name="game" id="game">
            <option selected="selected">--Select Game--</option>
<?php
			foreach($gamelist as $game => $title) {
?>
            <option value="<?php echo $game; ?>"><?php echo htmlspecialchars($title); ?></option>
<?php
			}
?>
          </select><br />
          <input type="submit" name="gameswitch" id="gameswitch" value="Switch Game" />
          <div class="loading" id="gameswitchstatus"><img src="images/ajax-loader.gif" alt="Loading" /> Working...</div>
        </div>
      </form>
      <div id="switchdialog" title="Confirm Game Switch" class="hidden">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Switching games will reset your configuration and mapcycle. Are you sure?</p>
      </div>
    </div>
  </div>
</div>
<div id="body">
  <div class="block">
    <h1>Server Info</h1>
    <table cellspacing="0">
      <tr><td>Server IP: </td><td><strong><?php echo $info['ip']; ?></strong></td></tr>
      <tr><td>Time Slot: </td><td><strong><?php echo $start; ?></strong> - <strong><?php echo $end; ?></strong> on <strong><?php echo $date; ?></strong> <?php if($info['start'] <= $now) { echo '(<span class="status" id="status_timeleft">' . $timeleft . '</span> remaining)'; } ?></td></tr>
      <tr><td>Game: </td><td><span class="status" id="status_game"><?php echo htmlspecialchars($gamelist[$info['game']]); ?></span></td></tr>
      <tr><td>Rcon Password: </td><td><strong><?php echo htmlspecialchars($info['rcon']); ?></strong></td></tr>
	  <tr><th colspan="2">FTP Access</th></tr>
	  <tr><td>Hostname: </td><td><strong><?php echo htmlspecialchars($ftp_host); ?></strong></td></tr>
	  <tr><td>Username: </td><td><strong><?php echo htmlspecialchars($ftp_user); ?></strong></td></tr>
	  <tr><td>Password: </td><td><strong><?php echo htmlspecialchars($ftp_pass); ?></strong></td></tr>
	  <tr><td>FTP Link: </td><td><a href="<?php echo $ftp_link; ?>" target="_blank"><?php echo $ftp_link; ?></a></td></tr>
    </table>
  </div>
  <div class="block">
    <h1>Server Config</h1>
    <div id="scfg">
      <ul class="hidden">
        <li><a href="#scfgsimple"><span>Basic Mode</span></a></li>
        <li><a href="#scfgraw"><span>Text Mode</span></a></li>
<?php
                        if($showMc) {
?>
        <li><a href="#mcycle"><span>Mapcycle</span></a></li>
<?php
                        }
?>
      </ul>
      <div id="scfgsimple" class="hidden">
        <form id="serverconfig" method="post" action="cp">
          <div>
            <input type="hidden" name="mode" value="config" />
            <label for="hostname" class="float">Server Name </label>
            <input type="text" name="hostname" id="hostname" value="<?php if(isset($scfg['hostname'])) { echo htmlspecialchars($scfg['hostname']); } ?>" /><br />
            <label for="sv_password" class="float">Server Password </label>
            <input type="text" name="sv_password" id="sv_password" value="<?php if(isset($scfg['sv_password'])) { echo htmlspecialchars($scfg['sv_password']); } ?>" /><br />
            <label for="mp_timelimit" class="float">Map Timelimit </label>
            <input type="text" name="mp_timelimit" id="mp_timelimit" value="<?php if(isset($scfg['mp_timelimit'])) { echo htmlspecialchars($scfg['mp_timelimit']); } ?>" /><br />
<?php
			foreach($scfg as $key => $value) {
				if(!in_array($key, $default_cvars)) {
?>
            <label for="<?php echo $key; ?>" class="float"><?php echo $key; ?> </label>
            <input type="text" name="<?php echo $key; ?>" id="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>" /><br />
<?php
				}
			}
?>
            <input type="submit" name="configserver" id="configserver" value="Submit" />
            <input type="button" name="addcvar" id="addcvar" value="Add Cvar" />
            <span class="loading" id="configstatus"><img src="images/ajax-loader.gif" alt="Loading" /> Working...</span><br />
          </div>
        </form>
        <div id="cvardialog" title="Add Cvar" class="hidden">
          <label for="cvar">Cvar:&nbsp;</label>
          <input type="text" name="cvar" id="cvar" /><br />
          <label for="value">Value: </label>
          <input type="text" name="value" id="value" />
        </div>
      </div>
      <div id="scfgraw">
        <form id="serverconfig2" method="post" action="cp">
          <div>
            <input type="hidden" name="mode" value="config" />
            <input type="hidden" name="raw" value="raw" />
            <textarea name="config" id="config" rows="10" cols="50" class="fullwide"><?php echo htmlspecialchars($configObj->get()); ?></textarea>
            <input type="submit" name="configserver" id="configserver2" value="Submit" />
            <span class="loading" id="configstatus2"><img src="images/ajax-loader.gif" alt="Loading" /> Working...</span><br />
          </div>
        </form>
      </div>
<?php
                        if($showMc) {
?>
      <div id="mcycle" class="hidden">
        <form id="cycle" method="post" action="cp">
          <div>
            <input type="hidden" name="mode" value="mapcycle" />
            <textarea name="mapcycle" id="mapcycle" rows="10" cols="50" class="fullwide"><?php echo htmlspecialchars($mcObj->getMapcycle()); ?></textarea>
            <input type="submit" name="setcycle" id="setcycle" value="Submit" />
            <input type="button" name="openbuilder" id="openbuilder" value="Mapcycle Editor" />
            <span class="loading" id="cyclestatus"><img src="images/ajax-loader.gif" alt="Loading" /> Working...</span><br />
          </div>
        </form>
        <div id="mcdialog" title="Mapcycle Editor">
          <select id="mcbuilder" multiple="multiple" class="multiselect">
<?php
                            foreach($default_maps as $map) {
?>
            <option value="<?php echo $map; ?>"<?php if(in_array($map, $mapcycle)) { echo ' selected="selected"'; } ?>><?php echo $map; ?></option>
<?php
                            }
?>
          </select>
        </div>
      </div>
<?php
                        }
?>
    </div>
  </div>
</div>
<?php
		}
	}
}
$template->foot();
?>