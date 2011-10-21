<?php
/**
 * Mapcycle class
 *
 * Handles loading and saving mapcycles.
 */
class Mapcycle {
    public $sid, $mapcycle;
    /**
     * Initialize the reservation ID
     *
     * @param <int> $sid Reservation ID
     */
    public function __construct($sid) {
        $this->sid = $sid;
    }
    /**
     * Return the mapcycle
     *
     * @param <bool> $array Set to TRUE to return an array
     * @return <mixed> List or array of maps
     */
    public function getMapcycle($array = false) {
        if(!isset($this->mapcycle)) {
            if($result = DB::get()->query("SELECT `mapcycle` FROM `config` WHERE `sid` = '" . $this->sid . "'")) {
                $row = $result->fetch_assoc();
                $result->close();
                $this->mapcycle = trim($row['mapcycle']);
            }
        }
        if($array) {
            return explode("\n", $this->mapcycle);
        }
        else {
            return $this->mapcycle;
        }
        return false;
    }
    /**
     * Return the default mapcycle for a game
     *
     * @param <string> $gameId Game identifier
     * @param <bool> $array Set to TRUE to return an array
     * @return <mixed> List or array of maps
     */
    public function getDefaultMapcycle($gameId, $array = false) {
        if(isset($gameId)) {
            $mapcycleFile = sprintf('%s/serverdata/default/mapcycle/%s.txt', PATH, $gameId);
            if(file_exists($mapcycleFile)) {
                $mapcycleData = trim(file_get_contents($mapcycleFile));
                if($array) {
                    return explode("\n", $mapcycleData);
                }
                else {
                    return $mapcycleData;
                }
            }
        }
        return false;
    }
    /**
     * Save a mapcycle
     *
     * @param <string> $mapcycleData The mapcycle data
     * @return <bool> TRUE on success
     */
    public function storeMapcycle($mapcycleData) {
        if(isset($this->sid, $mapcycleData)) {
            $mapcycleData = DB::get()->escape_string($mapcycleData);
            return DB::get()->query("INSERT INTO `config` VALUES ('" . $this->sid . "', '', '', '" . $mapcycleData . "') ON DUPLICATE KEY UPDATE `mapcycle` = '" . $mapcycleData . "'");
        }
        return false;
    }
    /**
     * Load and save the default mapcycle for a game
     *
     * @param <string> $gameId Game identifier
     * @return <bool> TRUE on success
     */
    public function loadDefaultMapcycle($gameId) {
        if($mapcycleData = self::getDefaultMapcycle($gameId, false)) {
            return self::storeMapcycle($mapcycleData);
        }
        return false;
    }
}