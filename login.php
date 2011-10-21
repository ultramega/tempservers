<?php
require('include/common.php');

if(isset($_GET['logout'])) {
	$userData->destroySession();
	header('Location: home');
	exit;
}
if(isset($_POST['user'], $_POST['pass'])) {
	if($userData->authUser($_POST['user'], $_POST['pass'], isset($_POST['remember']))) {
		if(isset($_POST['redir'])) {
			header('Location: ' . $_POST['redir']);
		}
		else {
			header('Location: home');
		}
		exit;
	}
	else {
		$msg = 'Error: Invalid user and/or password';
	}
}
$template = new Template('Login');
$template->head();
?>
<div id="body-center">
  <div class="login">
    <h1>Account Login</h1>
    <form method="post" action="login">
    <?php
$userData->showMessage();
if(isset($msg)){
	echo '      <div class="error">' . $msg . '</div>' . "\n";
}
if(isset($_REQUEST['redir'])) {
	echo '      <input type="hidden" name="redir" id="redir" value="' . $_REQUEST['redir'] . '" />' . "\n";
}
?>
      <div>
        <label for="user">Username: </label>
        <input type="text" name="user" id="user" /><br />
        <label for="pass">Password: </label>
        <input type="password" name="pass" id="pass" /><br />
        <label for="remember">Remember: </label>
        <input type="checkbox" name="remember" id="remember" /><br />
      </div>
      <div class="submit">
        <input type="submit" name="submit" id="submit" value="Login" />
      </div>
      <div><a href="register">Register</a> | <a href="passreset">Lost your password?</a></div>
    </form>
  </div>
</div>
<?php
$template->foot();
?>
