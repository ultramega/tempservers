<?php
/**
 * Installer class
 *
 * Contains static functions for installing, uninstalling, and reinstalling game
 * servers.
 */
class Installer {
    /**
     * Install a game server
     *
     * @param <int> $sid Reservation ID
     * @param <int> $host Host ID
     * @param <string> $pass Rcon password
     * @param <string> $game Game identifier
     */
    public static function install($sid, $host, $pass, $game) {
        $db = DB::get();
        $suser = $db->escape_string('ts' . $sid);
        $spass = $db->escape_string($pass);
        $db->select_db('proftp');
        $sql = sprintf("INSERT INTO `ftpuser` (`userid`, `passwd`, `homedir`) VALUES ('%s', ENCRYPT('%s'), '%s/users/%s')", $suser, $spass, GAMEPATH, $suser);
        $db->query($sql);
        $db->select_db(MYSQL_DB);

        $cgame = escapeshellarg($game);
        $cmd = sprintf("nohup ./install.sh install -s %d -g %s &", $sid, $cgame);
        ssh($cmd, $host);
    }
    /**
     * Uninstall a game server
     *
     * @param <int> $sid Reservation ID
     * @param <int> $host Host ID
     */
    public static function uninstall($sid, $host) {
        $db = DB::get();
        $suser = $db->escape_string('ts' . $sid);
        $db->select_db('proftp');
        $sql = sprintf("DELETE FROM `ftpuser` WHERE `userid` = '%s'", $suser);
        $db->query($sql);
        $db->select_db(MYSQL_DB);

        $cmd = sprintf("nohup ./install.sh uninstall -s %d &", $sid);
        ssh($cmd, $host);
    }
    /**
     * Reinstall a game server
     *
     * @param <int> $sid Reservation ID
     * @param <int> $host Host ID
     * @param <string> $game Game identifier
     */
    public static function reinstall($sid, $host, $game) {
        $cgame = escapeshellarg($game);
        $cmd = sprintf("nohup ./install.sh reinstall -s %d -g %s &", $sid, $cgame);
        ssh($cmd, $host);
    }
}