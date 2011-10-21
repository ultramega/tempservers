<?php
require('include/common.php');

$template = new Template('Recover Lost Password');
$template->head();
?>
<div id="body-center">
  <div class="login">
    <h1>Password Reset</h1>
<?php
if(isset($_GET['key'])) {
	if(User::changePassConfirm($_GET['key'])) {
		$userData->setMessage('Your password has successfully been reset. Check your email for the new password.');
		header('Location: login');
		exit;
	}
}
elseif(isset($_POST['user'])) {
	if(User::changePassRequest($_POST['user'], $userData)) {
		echo '      <div class="attn">An email has been dispatched to confirm your request. Please check your registered email for instructions.</div>' . "\n";
	}
	else {
		show_form('Error: User not found');
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

function show_form($msg = '') {
	echo '    <form method="post" action="passreset">';
	if(!empty($msg)){
		echo '      <div class="error">' . $msg . '</div>' . "\n";
	}
	echo '      <p>Please enter your username to start the password recovery process.<p>
      <div>
        <label for="user">Username: </label>
        <input type="text" name="user" id="user" /><br />
      </div>
      <div class="submit">
        <input type="submit" name="submit" id="submit" value="Continue" />
      </div>
    </form>';
}
?>