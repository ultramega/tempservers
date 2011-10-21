<?php
require('include/common.php');
if(isset($_POST['mode'])) {
    switch($_POST['mode']) {
        case 'checkuser':
            if(isset($_POST['username'])) {
                echo (User::userExists($_POST['username'])) ? 1 : 0;
            }
            break;
        case 'checkemail':
            if(isset($_POST['email'])) {
                if(mailer::isValidEmail($_POST['email'])) {
                    echo (User::emailExists($_POST['email'])) ? 2 : 0;
                }
                else {
                    echo 1;
                }
            }
            break;
        case 'login':
            if(isset($_POST['user'], $_POST['pass'])) {
                if($userData->authUser($_POST['user'], $_POST['pass'])) {
                    echo 0;
                }
                else {
                    echo 1;
                }
            }
            break;
        case 'register':
            if(isset($_POST['user'], $_POST['email'], $_POST['pass'], $_POST['tz'], $_POST['code'])) {
                if(strcasecmp($_POST['code'], $_SESSION['captcha']) == 0
                && array_key_exists($_POST['tz'], $zonelist)
                && mailer::isValidEmail($_POST['email'])
                && !User::emailExists($_POST['email'])
                && !User::userExists($_POST['user'])) {
                    if(User::register($_POST['user'], $_POST['pass'], $_POST['email'], $_POST['tz'])) {
                        $userData->authUser($_POST['user'], $_POST['pass']);
                        echo 0;
                    }
                    else {
                        echo 1;
                    }
                }
                else {
                    echo 1;
                }
            }
            break;
        case 'checkpass':
            if(isset($_POST['pass']) && $userData->loggedIn()) {
                if($userData->getPass() == md5($_POST['pass'])) {
                    echo 0;
                }
                else {
                    echo 1;
                }
            }
            break;
        case 'tz':
            if(isset($_POST['offset'], $_POST['dst'])) {
                $zones = array_keys($zonelist);
                foreach($zones as $zone) {
                    $obj = new DateTimeZone($zone);
                    $dtinfo = reset($obj->getTransitions());
                    if($dtinfo['offset'] == -($_POST['offset']) && $dtinfo['isdst'] == $_POST['dst']) {
                        echo $zone;
                        break;
                    }
                }
            }
            break;
        case 'captcha':
            if(isset($_POST['code'])) {
                if(strcasecmp($_POST['code'], $_SESSION['captcha']) == 0) {
                    echo 0;
                }
                else {
                    echo 1;
                }
            }
            break;
        case 'form':
            if($_POST['type'] == 'register') {
?>
    <div id="registererror" class="error hidden"></div>
    <form id="register" method="post" action="register">
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
      <div>Already registered? <a href="login" id="loginlink">Login</a></div>
    </form>
<?php
            }
            else {
?>
          <form method="post" action="login">
              <div class="error hidden" id="loginerror">Error: Invalid username or password</div>
              <label for="user" class="float">Username*: </label>
              <input type="text" name="user" id="user" /><br />
              <label for="pass" class="float">Password* : </label>
              <input type="password" name="pass" id="pass" /><br />
              <div><a id="registerlink" href="register">Register</a> | <a href="passreset">Lost your password?</a></div>
          </form>
<?php
            }
            break;
    }
}
?>