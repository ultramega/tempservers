<?php
require('include/common.php');

$template = new Template('FAQ');
$template->appendToHead('<script type="text/javascript" src="js/faq.js"></script>');
$template->head();
?>
<div id="body-wide">
  <div class="block">
    <h1>TempServers FAQ</h1>
    <div id="faq">
      <h2>General</h2>
      <h3><a href="#">What is TempServers?</a></h3>
      <div>
        <p>TempServers is a game server host that, unlike most companies, provides service at an hourly rate instead of traditional monthly per-slot rates. This is more cost effective for people who only need a server for a few hours, such as those hosting an event or a match.</p>
      </div>
      <h3><a href="#">Where are the servers located?</a></h3>
      <div>
        <p>All servers are currently hosted in San Diego, California, USA. More locations may be added in the future as needed.</p>
      </div>
      <h3><a href="#">What games are available?</a></h3>
      <div>
        <p>The following games are currently available:</p>
        <ul class="arrow">
<?php
$games = array_values($gamelist);
foreach($games as $game) {
	$game = htmlspecialchars($game);
	echo '          <li>' . $game . '</li>' . "\n";
}
?>
        </ul>
      </div>
      <h2>Account/Payments</h2>
      <h3><a href="#">What payment options are available?</a></h3>
      <div>
        <p>We currently accept all payments via PayPal.</p>
      </div>
      <h3><a href="#">What are server credits and how do I get them?</a></h3>
      <div>
        <p>Server credits are each worth 1 free hour of server time. When you reserve a server, the amount equivelent to the number of credits on your account is deducted from your total. Credits can be earned via promotions or contests.</p>
      </div>
      <h2>Game Servers</h2>
      <h3><a href="#">How many player slots do the servers come with?</a></h3>
      <div>
        <p>Servers come with 32 slots except for TF2 servers, which have 24 slots.</p>
      </div>
      <h3><a href="#">How do I use Rcon to manage my server?</a></h3>
      <div>
        <p>You can use the in-game console commands or 3rd-party tools to control your server via Rcon. This applies to all Half-Life or Source based servers (all games that we currently host).</p>
        <ul class="arrow">
          <li><strong>Using the console:</strong> While connected to your server, open your console (the '~' key by default, may need to be enabled in the game's settings) and type rcon_password &quot;<em>Password Here</em>&quot;. After that, you can run any server command prefixed with &quot;rcon&quot; (i.e. &quot;rcon changelevel cp_dustbowl&quot;).</li>
          <li><strong>Using a 3rd-party tool:</strong> We recommend HLSW (<a href="http://www.hlsw.net/" target="_blank">www.hlsw.net</a>). This allows you to run Rcon commands remotely without being on the server.</li>
        </ul>
      </div>
      <h3><a href="#">Can I access the game server files via FTP?</a></h3>
      <div>
        <p>Yes, full FTP access is provided to upload addons or custom configuration files.</p>
      </div>
    </div>
  </div>
</div>
<?php
$template->foot();
?>