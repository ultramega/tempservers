<?php
/**
 * Session class
 *
 * Handles all session data.
 * Represents a session.
 */
class Session {
    public $sid, $email, $tz, $active;
    protected $uid, $user, $pass, $admin, $live = false;

    /**
     * Initializes the session and loads data
     */
    public function __construct() {
        session_start();
        $this->loadSession();
    }

    /* Getters */
    /**
     * Check if user is logged in
     *
     * @return <bool> TRUE if user is logged in
     */
    public function loggedIn() {
        return $this->live;
    }
    /**
     * Check if user has admin access
     *
     * @return <bool> TRUE if user is admin
     */
    public function isAdmin() {
        return $this->admin;
    }
    /**
     * Check if user is active
     *
     * @return <bool> TRUE if user is activated
     */
    public function isActive() {
        return $this->active;
    }
    /**
     * Get the user ID
     *
     * @return <int> User ID
     */
    public function getUid() {
        return $this->uid;
    }
    /**
     * Get the user name
     *
     * @return <string> User name
     */
    public function getUser() {
        return $this->user;
    }
    /**
     * Get the password hash
     *
     * @return <string> Password hash
     */
    public function getPass() {
        return $this->pass;
    }

    /* Setters */
    /**
     * Set the user password
     *
     * @param <string> $pass The new password
     */
    public function setPass($pass) {
        $pass = md5($pass);
        $this->pass = $pass;
        $_SESSION['pass'] = $pass;
    }
    /**
     * Set the session ID
     *
     * @param <int> $sid The session ID
     */
    public function setSid($sid) {
        $this->sid = $sid;
        $_SESSION['sid'] = $sid;
    }
    /**
     * Set the user's email address
     *
     * @param <string> $email The new email address
     */
    public function setEmail($email) {
        $this->email = $email;
        $_SESSION['email'] = $email;
    }
    /**
     * Set the user's time zone
     *
     * @param <string> $tz The new time zone
     */
    public function setTz($tz) {
        $this->tz = $tz;
        $_SESSION['tz'] = $tz;
    }
    /**
     * Set the user active/inactive
     *
     * @param <bool> $active TRUE if user is active
     */
    public function setActive($active) {
        $this->active = $active;
        $_SESSION['active'] = $active;
    }
    /**
     * Set a global message for the session
     *
     * @param <string> $msg The content of the mess
     */
    public function setMessage($msg) {
        $_SESSION['message'] = $msg;
    }

    /* Functions */
    /**
     * Authenticate a user
     *
     * Compares the provided user credentials against the database and creates
     * the session if a match is found.
     *
     * @param <string> $user User name
     * @param <string> $passwd Password
     * @param <bool> $save TRUE to store a cookie
     * @return <bool> TRUE on successful authentication
     */
    public function authUser($user, $passwd, $save = false) {
        $db = DB::get();
        $user = $db->escape_string($user);
        $pass = md5($passwd);
        if($result = $db->query("SELECT * FROM `users` WHERE `user` = '" . $user . "' AND `pass` = '" . $pass . "'")) {
            if($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $result->close();
                $this->uid = (int)$row['id'];
                $this->sid = 0;
                $this->user = $row['user'];
                $this->pass = $row['pass'];
                $this->email = $row['email'];
                $this->tz = $row['timezone'];
                $this->admin = (bool)$row['admin'];
                $this->active = (bool)$row['active'];

                $this->live = true;

                $this->createSession();

                if($save) {
                    $this->setCookie($passwd);
                }
                log_action('LOGIN');
                return true;
            }
            else {
                $result->close();
                log_action('LOGIN', $user);
                return false;
            }
        }
        return false;
    }
    /**
     * Show the stored message
     *
     * Outputs any stored message and removes it from the queue
     */
    public function showMessage() {
        if(isset($_SESSION['message'])) {
            $msg = htmlspecialchars($_SESSION['message']);
            echo '<p class="attn">' . $msg . '</p>';
            unset($_SESSION['message']);
        }
    }
    /**
     * Load the stored session variables
     *
     * First checks if a session exists, and assigns local variables if present.
     * If no session exists, it will check for and load any cookie.
     */
    private function loadSession() {
        if(isset($_SESSION['uid'])) {
            $this->uid = $_SESSION['uid'];
            $this->sid = $_SESSION['sid'];
            $this->user = $_SESSION['user'];
            $this->pass = $_SESSION['pass'];
            $this->email = $_SESSION['email'];
            $this->tz = $_SESSION['tz'];
            $this->admin = $_SESSION['admin'];
            $this->active = $_SESSION['active'];

            $this->live = true;
        }
        elseif(isset($_COOKIE['auth'])) {
            $this->readCookie();
        }
    }
    /**
     * Create a session from local variables
     *
     * Assigns all local variables to session variables.
     */
    private function createSession() {
        $_SESSION['uid'] = $this->uid;
        $_SESSION['sid'] = $this->sid;
        $_SESSION['user'] = $this->user;
        $_SESSION['pass'] = $this->pass;
        $_SESSION['email'] = $this->email;
        $_SESSION['tz'] = $this->tz;
        $_SESSION['admin'] = $this->admin;
        $_SESSION['active'] = $this->active;
    }
    /**
     * Close and destroy the session
     *
     * Unsets all session variables, destroys the session, and removes any
     * cookie.
     */
    public function destroySession() {
        unset($_SESSION['uid'],
                $_SESSION['sid'],
                $_SESSION['user'],
                $_SESSION['pass'],
                $_SESSION['email'],
                $_SESSION['tz'],
                $_SESSION['admin'],
                $_SESSION['active']);
        session_destroy();
        $this->live = false;
        if(isset($_COOKIE['auth'])) {
            unset($_COOKIE['auth']);
            setcookie('auth', false, time()-60, '/');
        }
    }
    /**
     * Create a cookie to store credentials
     *
     * Creates a cookie called 'auth' that stores an encoded and encrypted
     * string of user name and password.
     *
     * @param <string> $passwd Password as plaintext
     */
    private function setCookie($passwd) {
        $cipher = new Cipher();
        $data = sprintf("%s|%s", base64_encode($this->user), base64_encode($passwd));
        $data = $cipher->encrypt($data);
        setcookie('auth', $data, time()+60*60*24, '/');
    }
    /**
     * Load stored cookie data
     *
     * Decrypts the user credentials from the cookie and attempts to
     * authenticate the user.
     */
    private function readCookie() {
        $cipher = new Cipher();
        $auth = $cipher->decrypt($_COOKIE['auth']);
        list($user, $pass) = explode('|', $auth);
        $this->authUser(base64_decode($user), base64_decode($pass));
    }
}