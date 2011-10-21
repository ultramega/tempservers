<?php
require('include/common.php');
check_mm();

if(isset($_GET['activate'])) {
	if(User::activate($_GET['activate'], $userData, false)) {
		$userData->setMessage('Your new email address has successfully been confirmed.');
	}
}

if(!$userData->loggedIn()) {
	header('Location: login?redir=account');
	exit;
}

if(isset($_GET['resend']) && !$userData->isActive()) {
	if(User::resendActivation($userData->getUid())) {
		$userData->setMessage('Your confirmation email has been re-sent.');
	}
}

$db = DB::get();
if(isset($_POST['submit'])) {
	$query = '';
	if($_POST['tz'] != $userData->tz) {
		if(array_key_exists($_POST['tz'], $zonelist)) {
			$query = sprintf("%s `timezone` = '%s',", $query, $db->escape_string($_POST['tz']));
			$userData->setTz($_POST['tz']);
		}
		else {
			$msg = 'Error: Invalid Time Zone.';
		}
	}
	if($_POST['email'] != $userData->email) {
		if(md5($_POST['password']) != $userData->getPass()) {
			$msg = 'Error: Incorrect password.';
		}
		elseif(!mailer::isValidEmail($_POST['email'])) {
			$msg = 'Error: Invalid email address.';
		}
		else {
			User::changeEmail($_POST['email'], $userData);
		}
	}
	if(!empty($_POST['npassword'])) {
		if(md5($_POST['password']) != $userData->getPass()) {
			$msg = 'Error: Incorrect password.';
		}
		elseif($_POST['npassword'] != $_POST['npassword2']) {
			$msg = 'Error: Password did not match.';
		}
		else {
			$query = sprintf("%s `pass` = '%s',", $query, md5($_POST['npassword']));
			$userData->setPass($_POST['npassword']);
		}
	}
	if(!empty($query)) {
		$query = sprintf("UPDATE `users` SET%s WHERE `id` = '%d'", substr($query, 0, -1), $userData->getUid());
		$db->query($query);
	}
}
$template = new Template('My Account');
$template->appendToHead('<script type="text/javascript" src="js/functions.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/account.js"></script>');
$template->head();

if($result = $db->query("SELECT * FROM `users` WHERE `id` = '" . $userData->getUid() . "'")) {
	$userinfo = $result->fetch_assoc();
	$result->close();
}

$dtZone = new DateTimeZone($userData->tz);

$dtJoined = new DateTime(date('r', $userinfo['join_date']));
$dtJoined->setTimeZone($dtZone);
$joindate = $dtJoined->format(DFORMAT);
?>
<div id="sidebar">
  <div class="block">
    <div class="top">
      <h1>Your Profile</h1>
    </div>
    <div class="content"> 
      <ul class="arrow">
        <li>Member Since: <?php echo $joindate; ?></li>
        <li>Server Credits: <?php echo $userinfo['credits']; ?> hours</li>
      </ul>
    </div>
  </div>
</div>
<div id="body">
  <div class="block">
    <h1>My Account</h1>
<?php
$userData->showMessage();
if(!$userData->isActive()) {
	echo '    <div class="error">Your email address has not been confirmed. Check your email for instructions in order to access all of the site features. <a href="account?resend=1">Resend email</a></div>';
}
if(isset($msg)){
	echo '    <div class="error">' . $msg . '</div>';
}
?>
    <div id="error" class="error hidden"></div>
    <form id="account" method="post" action="account">
      <div>
        <label for="username" class="float">User Name: </label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData->getUser()); ?>" disabled="disabled" /><br />
        <label for="email" class="float">Email Address: </label>
        <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($userData->email); ?>" />
        <span id="emailstatus" class="status"></span><br />
        <label for="password" class="float">Current Password*: </label>
        <input type="password" id="password" name="password" /><br />
        <label for="npassword" class="float">New Password: </label>
        <input type="password" id="npassword" name="npassword" />
        <span id="pwstatus" class="status"></span><br />
        <label for="npassword2" class="float">Confirm Password: </label>
        <input type="password" id="npassword2" name="npassword2" /><br />
        <label for="tz" class="float">Time Zone: </label>
        <select name="tz" id="tz">
          <option value="Pacific/Honolulu">(GMT-10:00) Hawaii</option>
          <option value="America/Anchorage">(GMT-09:00) Alaska</option>
          <option value="America/Los_Angeles">(GMT-08:00) Pacific Time (US &amp; Canada)</option>
          <option value="America/Phoenix">(GMT-07:00) Arizona</option>
          <option value="America/Denver">(GMT-07:00) Mountain Time (US &amp; Canada)</option>
          <option value="America/Chicago">(GMT-06:00) Central Time (US &amp; Canada)</option>
          <option value="America/New_York">(GMT-05:00) Eastern Time (US &amp; Canada)</option>
          <option value="America/Indiana/Indianapolis">(GMT-05:00) Indiana (East)</option>
          <option disabled="disabled">&#8212;&#8212;&#8212;&#8212;&#8212;&#8212;&#8211;</option>
<?php
foreach($zonelist as $key => $value) {
	echo '          <option value="' . $key . '"';
	if($key == $userData->tz) {
		echo ' selected="selected"';
	}
	echo '>' . $value . '</option>' . "\n";
}
?>
        </select><br />
      </div>
      <div class="submit">
        <input type="submit" name="submit" id="submit" value="Update" />
      </div>
      <div class="note">* Your current password is required to change your password or email address.</div>
    </form>
  </div>
</div>
<?php
$template->foot();
?>