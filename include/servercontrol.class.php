<?php
/**
 * ServerControl class
 *
 * Contains all core game server control functionality.
 * Represents a server reservation.
 */
class ServerControl {
    public $sid, $serverId, $gameId, $ip, $port, $host, $maxplayers, $map, $rcon;
    /**
     * Set up options for the game server instance
     *
     * @param <int> $sid Reservation ID
     * @param <int> $serverId Server ID
     * @param <string> $gameId Game identifier
     * @param <string> $rcon Rcon password
     */
    public function __construct($sid, $serverId, $gameId, $rcon = '') {
        $this->sid = (int)$sid;
        $this->serverId = (int)$serverId;
        $this->gameId = $gameId;
        $this->rcon = $rcon;
        $this->maxplayers = 32;
        $this->loadServer();
        switch($gameId) {
            case 'dods':
                $this->map = 'dod_avalanche';
                break;
            case 'tf':
                $this->maxplayers = 24;
                $this->map = 'ctf_2fort';
                break;
            case 'css':
                $this->map = 'de_dust';
                break;
            case 'ins':
                $this->map = 'ins_almaden';
                break;
            case 'l4d1':
                $this->maxplayers = 18;
                $this->map = 'l4d_hospital01_apartment';
                break;
            case 'l4d2':
                $this->maxplayers = 18;
                $this->map = 'c1m1_hotel';
                break;
            case 'cstrike':
                $this->map = 'de_dust';
                break;
            case 'dod':
                $this->map = 'dod_avalanche';
                break;
            case 'tfc':
                $this->map = '2fort';
                break;
            default:
                exit;
        }
    }
    /**
     * Load game server details
     */
    private function loadServer() {
        if($result = DB::get()->query("SELECT `host`, `ip` FROM `servers` WHERE `serverID` = " . $this->serverId)) {
            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                list($this->ip, $this->port) = explode(':', $row['ip']);
                $this->host = (int)$row['host'];
            }
            $result->close();
        }
    }
    /**
     * Generate local configuration files
     */
    public function prepareCfg() {
        if($result = DB::get()->query("SELECT * FROM `config` WHERE `sid` = '" . $this->sid . "'")) {
            $row = $result->fetch_assoc();
            $result->close();

            $file = PATH . '/serverdata/server' . $this->sid . '.cfg';
            if(!file_exists($file)) {
                touch($file);
                chmod($file, 0777);
            }
            file_put_contents($file, $row['cfg']);

            $file = PATH . '/serverdata/mapcycle' . $this->sid . '.txt';
            if(!file_exists($file)) {
                touch($file);
                chmod($file, 0777);
            }
            file_put_contents($file, $row['mapcycle']);
        }
    }
    /**
     * Send configuration files to remote host
     *
     * @return <bool> TRUE on success
     */
    public function createCfg() {
        global $hostlist;
        $hostinfo = $hostlist[$this->host];
        $this->prepareCfg();
        $src = PATH . '/serverdata';
        $dest = GAMEPATH . '/users/ts' . $this->sid;
        if($ssh = new SSH($hostinfo)) {
            $ret1 = $ssh->sendFile($src . '/server' . $this->sid . '.cfg', $dest . '/server.cfg');
            $ret2 = $ssh->sendFile($src . '/mapcycle' . $this->sid . '.txt', $dest . '/mapcycle.txt');
            return $ret1 && $ret2;
        }
    }
    /**
     * Send a command to the game server console
     *
     * @param <string> $cmd Command to send
     */
    public function send($cmd) {
        $cmd = escapeshellarg($cmd);
        $cmd = sprintf("screen -S ts_%d -p 0 -X stuff %s%c", $this->sid, $cmd, 13);
        ssh($cmd, $this->host);
    }
    /**
     * Start the game server
     */
    public function start() {
        $cmd = sprintf("./control.sh start %s -game %s -ip %s -port %d -maxplayers %d -map %s -rcon %s", $this->sid, $this->gameId, $this->ip, $this->port, $this->maxplayers, $this->map, $this->rcon);
        ssh($cmd, $this->host);
        DB::get()->query("UPDATE `servers` SET `status` = 1 WHERE `serverID` = '" . $this->serverId . "'");
    }
    /**
     * Stop the game server
     */
    public function stop() {
        $cmd = sprintf("./control.sh stop %s -game %s", $this->sid, $this->gameId);
        ssh($cmd, $this->host);
        DB::get()->query("UPDATE `servers` SET `status` = 0 WHERE `serverID` = '" . $this->serverId . "'");
    }
    /**
     * Restart the game server
     */
    public function restart() {
        $cmd = sprintf("./control.sh restart %s -game %s -ip %s -port %d -maxplayers %d -map %s -rcon %s", $this->sid, $this->gameId, $this->ip, $this->port, $this->maxplayers, $this->map, $this->rcon);
        ssh($cmd, $this->host);
        log_action('RESTART');
    }
}