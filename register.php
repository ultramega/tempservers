<?php
require('include/common.php');
check_mm();

if(isset($_GET['activate'])) {
	if(User::activate($_GET['activate'], $userData)) {
		$userData->setMessage('Your email address has successfully been confirmed.');
	}
	header('Location: account');
	exit;
}
$template = new Template('Account Registration');
$template->appendToHead('<script type="text/javascript" src="js/functions.js"></script>');
$template->appendToHead('<script type="text/javascript" src="js/register.js"></script>');
$template->head();
?>
<div id="body-center">
  <div class="block">
    <h1>Account Registration</h1>
<?php
$userData->showMessage();
if(isset($_POST['submit'])) {
	if(empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
		$msg = 'Please fill in all fields.';
	}
	elseif(strcasecmp($_POST['captcha'], $_SESSION['captcha']) != 0) {
		$msg = 'Error: Incorrect image validation code entered.';
	}
	elseif(strcmp($_POST['password'], $_POST['password2']) != 0) {
		$msg = 'Error: Password did not match.';
	}
	elseif(!array_key_exists($_POST['tz'], $zonelist)) {
		$msg = 'Error: Invalid time zone.';
	}
	elseif(!mailer::isValidEmail($_POST['email'])) {
		$msg = 'Error: Invalid email address.';
	}
	elseif(User::emailExists($_POST['email'])) {
		$msg = 'That email address has already been registered.';
	}
	elseif(User::userExists($_POST['username'])) {
		$msg = 'That username has already been taken. Please try another.';
	}
	else {
		if(User::register($_POST['username'], $_POST['password'], $_POST['email'], $_POST['tz'])) {
			$userData->authUser($_POST['username'], $_POST['password']);
			echo '    <div class="attn"><h2>Registration Complete</h2><p>Your account has successfully been registered. An email has been dispatched to confirm your address. Please check your email and follow the instructions to gain full access to the site.</p></div>';
		}
	}
	if(isset($msg)) {
		echo '    <div class="error">' . $msg . '</div>';
		show_form();
	}
}
else {
	show_form();
}
?>
  </div>
</div>
<?php
$template->foot();

function show_form() {
	global $zonelist;
?>
    <div id="registererror" class="error hidden"></div>
    <form id="register" method="post" action="register">
      <div>
        <label for="username" class="float">User Name: </label>
        <input type="text" id="username" name="username" maxlength="64"<?php if(!empty($_POST['username'])) { echo ' value="' . htmlspecialchars($_POST['username']) . '"'; } ?> />
        <span id="namestatus" class="status"></span><br />
        <label for="email" class="float">Email Address: </label>
        <input type="text" id="email" name="email" maxlength="128"<?php if(!empty($_POST['email'])) { echo ' value="' . htmlspecialchars($_POST['email']) . '"'; } ?> />
        <span id="emailstatus" class="status"></span><br />
        <label for="password" class="float">Password: </label>
        <input type="password" id="password" name="password" />
        <span id="pwstatus" class="status"></span><br />
        <label for="password2" class="float">Confirm Password: </label>
        <input type="password" id="password2" name="password2" /><br />
        <label for="tz" class="float">Time Zone: </label>
        <select name="tz" id="tz">
          <option value="Pacific/Honolulu">(GMT-10:00) Hawaii</option>
          <option value="America/Anchorage">(GMT-09:00) Alaska</option>
          <option value="America/Los_Angeles" selected="selected">(GMT-08:00) Pacific Time (US &amp; Canada)</option>
          <option value="America/Phoenix">(GMT-07:00) Arizona</option>
          <option value="America/Denver">(GMT-07:00) Mountain Time (US &amp; Canada)</option>
          <option value="America/Chicago">(GMT-06:00) Central Time (US &amp; Canada)</option>
          <option value="America/New_York">(GMT-05:00) Eastern Time (US &amp; Canada)</option>
          <option value="America/Indiana/Indianapolis">(GMT-05:00) Indiana (East)</option>
          <option disabled="disabled">&#8212;&#8212;&#8212;&#8212;&#8212;&#8212;&#8211;</option>
<?php
	foreach($zonelist as $key => $value) {
?>
          <option value="<?php echo $key; ?>"<?php if(isset($_POST['tz']) && $_POST['tz'] == $key) { echo ' selected="selected"'; } ?>><?php echo $value; ?></option>
<?php
	}
?>
      </select>
      <hr />
      <div id="captchaimg">
        <label for="captcha">Enter the text seen in the image below: </label>
        <input type="text" id="captcha" name="captcha" /><br />
        <a href="#" id="reloadcaptcha">Reload</a>
        <img src="captcha.gif" width="120" height="30" alt="Captcha" />
      </div>
      <hr />
      <div class="submit">
        <input type="submit" name="submit" id="submit" value="Register" />
      </div>
    </div>
    </form>
<?php
}
?>