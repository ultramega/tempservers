<?php
/**
 * User class
 *
 * Contains all functions relating to users, meant to be called statically.
 */
class User {
    /**
     * Check if a user ID exists
     *
     * @param <int> $uid User ID to check
     * @return <bool> TRUE if user ID exists
     */
    public static function uidExists($uid) {
        $uid = intval($uid);
        return DB::get()->query("SELECT `id` FROM `users` WHERE `id` = " . $uid)->num_rows > 0;
    }
    /**
     * Check if a user name exists
     *
     * @param <string> $user User name to check
     * @return <bool> TRUE if user name exists
     */
    public static function userExists($user) {
        $user = DB::get()->escape_string($user);
        return DB::get()->query("SELECT `id` FROM `users` WHERE `user` = '" . $user . "'")->num_rows > 0;
    }
    /**
     * Check if an email address exists
     *
     * @param <string> $email Email address to check
     * @return <bool> TRUE if email address exists
     */
    public static function emailExists($email) {
        $email = DB::get()->escape_string($email);
        return DB::get()->query("SELECT `id` FROM `users` WHERE `email` = '" . $email . "'")->num_rows > 0;
    }
    /**
     * Add a new user to the database
     *
     * @param <string> $user User name
     * @param <string> $pass User password
     * @param <string> $email Email address
     * @param <string> $tz Time Zone
     * @return <bool> TRUE on success
     */
    public static function register($user, $pass, $email, $tz) {
        $db = DB::get();

        $user = $db->escape_string($user);
        $pass = md5($pass);
        $email = $db->escape_string($email);
        $tz = $db->escape_string($tz);

        $key = gen_key();

        if($db->query("INSERT INTO `users` VALUES ('', '" . $user . "', '" . $pass . "', '" . $email . "', '" . $tz . "', '', 0, '" . time() . "', 0, '" . $key . "', 0)")) {
            $key = $db->insert_id . ':' . $key;
            self::sendActivation($user, $email, $key);

            log_action('REGISTER', $user, $email, $tz);

            return true;
        }
        return false;
    }
    /**
     * Activates a user account
     *
     * Used to activate new users or rectivate users after an email change.
     *
     * @param <string> $hash Secret hash for verification
     * @param <object> $session Instance of the Session class
     * @param <bool> $newuser TRUE if this is a new user
     * @return <bool> TRUE on success
     */
    public static function activate($hash, &$session, $newuser = true) {
        list($id, $key) = explode(':', $hash, 2);
        if(is_numeric($id)) {
            $db = DB::get();

            $key = $db->escape_string($key);
            $id = intval($id);

            if($result = $db->query("SELECT `user`, `email` FROM `users` WHERE `id` = " . $id . " AND `key` = '" . $key . "'")) {
                if($result->num_rows == 1) {
                    $row = $result->fetch_assoc();

                    $db->query("UPDATE `users` SET `active` = 1, `key` = '' WHERE `id` = " . $id);
                    $session->setActive(true);

                    if($newuser) {
                        $db->query("UPDATE `users` SET `credits` = " . SIGNUP_BONUS . " WHERE `id` = " . $id);
                        $mail = new Mailer($row['email'], ADMIN_EMAIL, 'Welcome to TempServers!');
                        $mail->writeTemplate('newuser', array('user' => $row['user']));
                        $mail->send();
                    }

                    log_action('USER_ACTIVATE', $row['user'], $row['email']);

                    $result->close();
                    return true;
                }
                $result->close();
            }
        }
        return false;
    }
    /**
     * Re-send the activation email
     *
     * @param <int> $uid User ID
     * @return <bool> TRUE on success
     */
    public static function resendActivation($uid) {
        if(is_int($uid)) {
            $db = DB::get();
            if($result = $db->query("SELECT `user`, `email`, `key` FROM `users` WHERE `active` = 0 AND `id` = " . $uid)) {
                if($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $result->close();
                    $key = $uid . ':' . $row['key'];
                    self::sendActivation($row['user'], $row['email'], $key);
                    return true;
                }
                $result->close();
            }
        }
        return false;
    }
    /**
     * Change a user's email address
     *
     * @param <string> $email New email address
     * @param <object> $session Instance of the Session class
     */
    public static function changeEmail($email, &$session) {
        $db = DB::get();
        $key = gen_key();
        $email = $db->escape_string($email);
        $db->query("UPDATE `users` SET `email` = '" . $email . "', `active` = 0, `key` = '" . $key . "' WHERE `id` = " . $session->getUid());

        $session->setEmail($email);
        $session->setActive(false);

        $key = $session->getUid() . ':' . $key;
        $mail = new Mailer($email, ADMIN_EMAIL, 'Please Confirm Your Email Address');
        $mail->writeTemplate('change_email', array('user' => $session->getUser(), 'key' => $key));
        $mail->send();
    }
    /**
     * Send a password reset request
     *
     * Starts the password recovery process by generating a secret key and
     * sending a confirmation email.
     *
     * @param <string> $user User name
     * @return <bool> TRUE on success
     */
    public static function changePassRequest($user) {
        $db = DB::get();
        $user = $db->escape_string($user);
        if($result = $db->query("SELECT `id`, `email` FROM `users` WHERE `user` = '" . $user . "'")) {
            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $result->close();
                $key = gen_key();
                if($db->query("UPDATE `users` SET `key` = '" . $key . "' WHERE `id` = '" . $row['id'] . "'")) {
                    $key = $row['id'] . ':' . $key;
                    $mail = new Mailer($row['email'], ADMIN_EMAIL, 'Confirm Password Reset');
                    $mail->writeTemplate('reset_password', array('user' => $user, 'key' => $key));
                    $mail->send();
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Reset user's password after verification
     *
     * @param <string> $hash Secret hash for verification
     * @return <bool> TRUE on success
     */
    public static function changePassConfirm($hash) {
        list($id, $key) = explode(':', $hash, 2);
        if(is_numeric($id)) {
            $db = DB::get();
            $key = $db->escape_string($key);
            $id = intval($id);
            if($result = $db->query("SELECT `id`, `user`, `email` FROM `users` WHERE `id` = " . $id . " AND `key` = '" . $key . "'")) {
                if($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $result->close();
                    $pass = gen_key(8);
                    $db->query("UPDATE `users` SET `pass` = '" . md5($pass) . "', `key` = '' WHERE `id` = " . $row['id']);

                    $mail = new Mailer($row['email'], ADMIN_EMAIL, 'New Password');
                    $mail->writeTemplate('new_password', array('user' => $row['user'], 'pass' => $pass));
                    $mail->send();

                    return true;
                }
                $result->close();
            }
        }
        return false;
    }
    /**
     * Send user activation email
     *
     * @param <string> $user User name
     * @param <string> $email User's email address
     * @param <string> $key Secret hash
     */
    private static function sendActivation($user, $email, $key) {
        $mail = new Mailer($email, ADMIN_EMAIL, 'Action Required to Complete Registration');
        $mail->writeTemplate('activate_user', array('user' => $user, 'key' => $key));
        $mail->send();
    }
}