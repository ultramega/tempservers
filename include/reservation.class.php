<?php
/**
 * Reservation class
 *
 * Contains all core reservation handling functionality, including activating
 * and retreiving information.
 */
class Reservation {
    public $sid, $uid, $active, $start, $end, $rcon, $gameId, $serverId, $host, $ip, $status;
    /**
     * Initialize reservation information
     *
     * @param <int> $sid Reservatio ID
     * @return <bool> TRUE on success
     */
    public function __construct($sid) {
        if($result = DB::get()->query("SELECT * FROM `schedule` a LEFT JOIN `servers` b ON a.`serverID` = b.`serverID` WHERE `sid` = '" . $sid . "'")) {
            if($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $result->close();
                $this->sid = $sid;
                $this->uid = $row['uid'];
                $this->active = $row['active'];
                $this->start = $row['start'];
                $this->end = $row['end'];
                $this->rcon = $row['rcon'];
                $this->gameId = $row['game'];
                $this->serverId = $row['serverID'];
                $this->host = $row['host'];
                $this->ip = $row['ip'];
                $this->status = $row['status'];
                return true;
            }
        }
        return false;
    }
    /**
     * Activate the reservation
     *
     * Assigns the first available server to the reservation, sets its status to
     * active, updates the user's credits, installs the game server, and
     * notifies the user via email;
     *
     * @return <bool> TRUE on success
     */
    public function activate() {
        global $gamelist;
        if($this->active == 0) {
            if($result = DB::get()->query("SELECT * FROM `servers` WHERE `serverID` NOT IN (SELECT `serverID` FROM `schedule` WHERE `end` >= '" . $this->start . "' AND `start` <= '" . $this->end . "') ORDER BY `serverID` ASC LIMIT 1")) {
                $server = $result->fetch_assoc();
                $result->close();

                DB::get()->query("UPDATE `schedule` SET `active` = '1', `serverID` = '" . $server['serverID'] . "' WHERE `sid` = '" . $this->sid . "'");
                $this->active = 1;
                $this->serverId = $server['serverID'];
                $this->host = $server['host'];
                $this->ip = $server['ip'];

                if($result = DB::get()->query("SELECT `credits_used` FROM `trans` WHERE `sid` = '" . $this->sid . "'")) {
                    $credit = $result->fetch_assoc();
                    $result->close();
                    if($credit['credits_used'] > 0) {
                        DB::get()->query("UPDATE `users` SET `credits` = `credits`-" . $credit['credits_used'] . " WHERE `id` = '" . $this->uid . "'");
                    }
                }

                Installer::install($this->sid, $this->host, $this->rcon, $this->gameId);

                if($result = DB::get()->query("SELECT `user`, `email`, `timezone` FROM `users` WHERE `id` = '" . $this->uid . "'")) {
                    $userData = $result->fetch_assoc();
                    $result->close();
                }

                $dtZone = new DateTimeZone($userData['timezone']);

                $dtStart = new DateTime(date('r', $this->start));
                $dtStart->setTimeZone($dtZone);
                $start = $dtStart->format(TFORMAT);
                $date = $dtStart->format(DFORMAT);

                $dtEnd = new DateTime(date('r', $this->end));
                $dtEnd->setTimeZone($dtZone);
                $end = $dtEnd->format(TFORMAT);

                $timeslot = sprintf("%s - %s on %s", $start, $end, $date);

                $mail = new Mailer($userData['email'], ADMIN_EMAIL, 'TempServers Reservation Confirmation');
                $hostname = substr($this->ip, 0, strpos($this->ip, ':'));
                $mail->writeTemplate('reservation_confirm', array('user' => $userData['user'], 'ip' => $this->ip, 'game' => $gamelist[$this->gameId], 'timeslot' => $timeslot, 'rcon' => $this->rcon, 'host' => $hostname, 'sid' => $this->sid));
                $mail->send();

                log_action('ACTIVATE', $this->sid, $this->ip);
                return true;
            }
        }
        return false;
    }
    /**
     * Check if the reservation is currently active
     *
     * @return <bool> TRUE if the reservation is current
     */
    public function isCurrent() {
        $now = time();
        return ($this->start <= $now && $this->end >= $now);
    }
    /**
     * Get the reservation details
     *
     * @return <array> Associative array of reservation details
     */
    public function getInfo() {
        return array('sid' => $this->sid,
                'uid' => $this->uid,
                'active' => $this->active,
                'start' => $this->start,
                'end' => $this->end,
                'rcon' => $this->rcon,
                'game' => $this->gameId,
                'serverID' => $this->serverId,
                'host' => $this->host,
                'ip' => $this->ip,
                'status' => $this->status);
    }
    /**
     * Get list of all active reservations
     *
     * @return <array> List of all active reservations
     */
    public static function listAll() {
        if($result = DB::get()->query("SELECT a.*, b.`user` FROM `schedule` a LEFT JOIN `users` b ON a.`uid` = b.`id` WHERE a.`active` = '1' ORDER BY a.`sid` DESC")) {
            $list = array();
            while($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
            $result->close();
            return $list;
        }
        return false;
    }
    /**
     * Get the list of all active reservations for a particular user
     *
     * @param <int> $uid User ID
     * @return <array> List of user's active reservations
     */
    public static function listUser($uid) {
        $uid = DB::get()->escape_string($uid);
        if($result = DB::get()->query("SELECT * FROM `schedule` WHERE `uid` = '" . $uid . "' AND `active` = '1' ORDER BY `sid` DESC")) {
            $list = array();
            while($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
            $result->close();
            return $list;
        }
        return false;
    }
    /**
     * Check if a user owns a reservation
     *
     * @param <int> $sid Reservation ID
     * @param <int> $uid User ID
     * @return <bool> TRUE if user has access
     */
    public static function checkAccess($sid, $uid) {
        $sid = DB::get()->escape_string($sid);
        $uid = DB::get()->escape_string($uid);
        return (DB::get()->query("SELECT `sid` FROM `schedule` WHERE `sid` = '" . $sid . "' AND `uid` = '" . $uid . "'")->num_rows > 0);
    }
}