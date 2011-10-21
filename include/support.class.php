<?php
/**
 * Support class
 *
 * Contains all core functionality of the support system.
 * Represents a support ticket.
 */
class Support {
    /**
     * ID of the current support ticket
     *
     * @var <int> Ticket ID
     */
    public $tid;
    /**
     * Initializes object by setting the Ticket ID
     *
     * @param <int> $tid Ticket ID
     */
    public function __construct($tid = null) {
        self::setTid($tid);
    }
    /**
     * Sets the Ticket ID
     *
     * @param <int> $tid Ticket ID
     */
    public function setTid($tid) {
        $tid = DB::get()->escape_string($tid);
        $this->tid = $tid;
    }
    /**
     * Creates a new ticket
     *
     * @param <int> $uid User ID
     * @param <int> $catId Category ID
     * @param <string> $title Title (subject) of ticket
     * @param <string> $message Body of ticket
     * @return <bool> TRUE on success
     */
    public function createTicket($uid, $catId, $title, $message) {
        if(self::isValidCat($catId)) {
            $uid = DB::get()->escape_string($uid);
            $catId = DB::get()->escape_string($catId);
            $title = DB::get()->escape_string($title);
            $message = DB::get()->escape_string($message);
            if(DB::get()->query("INSERT INTO `support_thread` VALUES ('', '" . $uid . "', '" . $catId . "', '" . $title . "', '0', '0')")) {
                self::setTid(DB::get()->insert_id);
                return (DB::get()->query("INSERT INTO `support` VALUES ('', '" . $this->tid . "', '" . $message . "', '" . $uid . "', '" . time() . "')"));
            }
        }
        return false;
    }
    /**
     * Returns a list of tickets
     *
     * Returns an array of tickets, optionally filtered by user.
     *
     * @param <int> $uid User ID (0 for admin)
     * @return <mixed> Array of tickets, or FALSE on failure
     */
    public static function listTickets($uid = 0) {
        $query = "SELECT * FROM `support_thread` a LEFT JOIN `support_cat` b ON a.`catID` = b.`catID`";
        if($uid > 0) {
            $uid = DB::get()->escape_string($uid);
            $query = sprintf("%s WHERE a.`uid` = '%s'", $query, $uid);
        }
        $query .= " ORDER BY a.`status`, a.`id` DESC";
        if($result = DB::get()->query($query)) {
            if($result->num_rows > 0) {
                $tickets = array();
                while($row = $result->fetch_assoc()) {
                    $tickets[] = $row;
                }
                $result->close();
                return $tickets;
            }
        }
        return false;
    }
    /**
     * Returns the content of the ticket
     *
     * @return <mixed> Array of posts in the ticket, or FALSE on failure
     */
    public function getTicket() {
        if($this->tid != null) {
            if($result = DB::get()->query("SELECT a.*, b.*, c.*, d.`user`, d.`email`, d.`admin` FROM `support_thread` a LEFT JOIN (`support` b CROSS JOIN `support_cat` c CROSS JOIN `users` d) ON (a.`id` = b.`tid` AND a.`catID` = c.`catID` AND b.`poster` = d.`id`) WHERE a.`id` = '" . $this->tid . "' ORDER BY b.`id` ASC")) {
                if($result->num_rows > 0) {
                    $posts = array();
                    while($row = $result->fetch_assoc()) {
                        $posts[] = $row;
                    }
                    $result->close();
                    return $posts;
                }
            }
        }
        return false;
    }
    /**
     * Check if a user has access to the ticket
     *
     * @param <int> $uid User ID
     * @return <bool> TRUE is user has access
     */
    public function checkAccess($uid) {
        if($this->tid != null) {
            return (DB::get()->query("SELECT `uid` FROM `support_thread` WHERE `id` = '" . $this->tid . "' AND `uid` = '" . $uid . "'")->num_rows > 0);
        }
        return false;
    }
    /**
     * Adds a message (reply) to the ticket
     *
     * @param <int> $uid User ID
     * @param <string> $message Body of message
     * @return <bool> TRUE on success
     */
    public function addMessage($uid, $message) {
        if($this->tid != null) {
            $uid = DB::get()->escape_string($uid);
            $message = DB::get()->escape_string($_POST['message']);
            if(DB::get()->query("INSERT INTO `support` VALUES ('', '" . $this->tid . "', '" . $message . "', '" . $uid . "', '" . time() . "')")) {
                return DB::get()->query("UPDATE `support_thread` SET `replies` = `replies`+1 WHERE `id` = '" . $this->tid . "'");
            }
        }
        return false;
    }
    /**
     * Sets the status of the ticket
     *
     * @param <mixed> $status Name (new/open/closed) or ID of status
     * @return <bool> TRUE on success
     */
    public function setStatus($status) {
        if($this->tid != null) {
            if(!is_int($status)) {
                switch($status) {
                    case 'new': $status = 0;
                        break;
                    case 'open': $status = 1;
                        break;
                    case 'closed': $status = 2;
                        break;
                }
            }
            if($status >= 0 && $status < 3) {
                return DB::get()->query("UPDATE `support_thread` SET `status` = '" . $status . "' WHERE `id` = '" . $this->tid . "'");
            }
        }
        return false;
    }
    /**
     * Check if a category exists and is valid
     *
     * @param <int> $catId Category ID
     * @return <bool> TRUE if category is valid
     */
    public static function isValidCat($catId) {
        $catId = DB::get()->escape_string($catId);
        return (DB::get()->query("SELECT * FROM `support_cat` WHERE `catID` = '" . $catId . "'")->num_rows > 0);
    }
}