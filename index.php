<?php
require('include/common.php');

$template = new Template('Home');
$template->appendToHead('<script type="text/javascript" src="js/jquery.slides.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/home.js"></script>');
$template->head();
?>
  <div id="sidebar">
    <div class="block">
      <div class="top">
        <h1>Pricing</h1>
      </div>
      <div class="content">
        <p class="price">All servers <span>30&cent;</span> per hour!</p>
      </div>
    </div>
    <div class="block">
      <div class="top">
        <h1>Available Games</h1>
      </div>
      <div class="content">
        <ul class="arrow">
          <li>Counter-Strike 1.6</li>
          <li>Counter-Strike: Source</li>
          <li>Day of Defeat 1.3</li>
          <li>Day of Defeat: Source</li>
          <li>Insurgency: Source</li>
          <li><strong>Left 4 Dead</strong></li>
          <li><strong>Left 4 Dead 2</strong></li>
          <li>Team Fortress Classic</li>
          <li>Team Fortress 2</li>
        </ul>
      </div>
    </div>
    <div class="block">
      <div class="top">
        <h1>News</h1>
      </div>
      <div class="content">
        <ul class="feed">
<?php
if($result = DB::get()->query("SELECT `text`, `time` FROM `news` ORDER BY `id` DESC LIMIT 5")) {
	$patterns = array('#(http\://[^\s]*)#i',
					'/@([a-z1-9_]+)/i',
					'/(#[a-z1-9_]+)/i');
	$replace = array('<a href="$1" target="_blank">$1</a>',
					'@<a href="http://twitter.com/$1" target=_blank">$1</a>',
					'<a href="http://twitter.com/search?q=$1" target=_blank">$1</a>');
	while($row = $result->fetch_assoc()) {
		$entry = preg_replace($patterns, $replace, $row['text']);
		if($userData->loggedIn()) {
			$dtZone = new DateTimeZone($userData->tz);
			$dtStart = new DateTime(date('r', $row['time']));
			$dtStart->setTimeZone($dtZone);
			$time = $dtStart->format(DFORMAT . ' ' . TFORMAT);
		}
		else {
			$time = date(DFORMAT . ' ' . TFORMAT, $row['time']);
		}
?>
          <li><div class="caption"><?php echo $time; ?></div><?php echo $entry?></li>
<?php
	}
	$result->close();
}
?>
        </ul>
        <div class="center">
          <a href="http://twitter.com/tempservers" target="_blank">Follow on Twitter</a>
        </div>
      </div>
    </div>
  </div>
  <div id="body">
    <div class="block">
      <h1>TempServers - Hourly Game Server Rental</h1>
      <div class="features">
        <h2>Features</h2>
        <ul>
          <li><strong>Instant Setup</strong> once payment is received</li>
          <li>Simple and powerful <strong>Server Control Panel</strong></li>
          <li>Access to server configuration files</li>
          <li>Full <strong>FTP</strong> and <strong>Rcon</strong> access</li>
          <li>Support for SourceTV</li>
        </ul>
      </div>
    </div>
    <div class="block" id="slideshow">
      <img src="images/splash.gif" alt="Supported Games" width="496" height="232" />
    </div>
    <div class="block">
      <h2>About TempServers</h2>
      <p>TempServers was designed from the ground up to be simple and fast, while giving you full control over your server.</p>
      <p>Reserving a server is easy: just log in to your account, choose a time, choose a game, and check out.</p>
      <p>You get access to the server configuration directly from the Server CP, or via FTP. You are also able to restart the server or switch game on the fly!</p>
    </div>
  </div>
<?php
$template->foot();
?>