<?php
/**
 * ServerConfig class
 *
 * Handles generating and parsing game server configuration files. Specifically
 * designed to work with Valve dedicated servers.
 */
class ServerConfig {
    public $sid, $cfgData, $isRaw = false;
    /**
     * Restricted cvars
     *
     * @var <array> List of variables to block
     */
    private $restrictedCvars = array('rcon_password');
    /**
     * Default cvar values
     *
     * @var <array> Associative array of default cvar values
     */
    private $defaultCvars = array('hostname' => 'TempServers.com');
    /**
     * Initialize the configuration
     *
     * @param <int> $sid Reservation ID
     */
    public function __construct($sid) {
        $this->sid = $sid;
        self::set(array_keys($this->defaultCvars), array_values($this->defaultCvars));
    }
    /**
     * Set a list of cvars
     *
     * @param <array> $cvars List of cvars to set
     * @param <array> $vals List of associated values
     */
    public function set($cvars, $vals) {
        $cfgData = '';
        foreach($cvars as $i => $cvar) {
            $cvar = trim($cvar);
            if(!in_array($cvar, $this->restrictedCvars)) {
                $cfgData .= sprintf('%s "%s"%s', $cvar, $vals[$i], "\n");
            }
        }
        $this->cfgData = $cfgData;
    }
    /**
     * Parse a configuration file and strip restricted cvars
     *
     * @param <string> $cfgInput Raw configuration data
     */
    public function parseCfg($cfgInput) {
        $cfgDataList = preg_split('/[\n\r;]/', $cfgInput, -1, PREG_SPLIT_NO_EMPTY);
        $cfgData = '';
        foreach($cfgDataList as $line) {
            if(!in_array(substr(ltrim($line), 0, strpos($line, ' ')), $this->restrictedCvars)) {
                $cfgData .= $line . "\n";
            }
        }
        $this->cfgData = $cfgData;
        $this->isRaw = true;
    }
    /**
     * Get the full configuration
     *
     * @param <bool> $array Set to TRUE to return an array
     * @return <mixed> Configuration data as text or array
     */
    public function get($array = false) {
        if($array) {
            $cfgDataList = explode("\n", $this->cfgData);
            $cfgDataArray = array();
            foreach($cfgDataList as $line) {
                if(preg_match('#^([a-z_]+)\ \"?(.*?)\"?(?:\s*)?(?://.*)?$#i', $line, $matches) > 0) {
                    $cfgDataArray[$matches[1]] = $matches[2];
                }
            }
            return $cfgDataArray;
        }
        else {
            return $this->cfgData;
        }
    }
    /**
     * Check if configuration is raw
     *
     * @return <bool> TRUE if object contains raw configuration data
     */
    public function isRaw() {
        return $this->isRaw;
    }
    /**
     * Load the configuration from the database
     *
     * @return <bool> TRUE on success
     */
    public function fetch() {
        if($result = DB::get()->query("SELECT `cfg`, `raw` FROM `config` WHERE `sid` = '" . $this->sid . "'")) {
            $row = $result->fetch_assoc();
            $result->close();
            $this->cfgData = $row['cfg'];
            $this->isRaw = (bool)$row['raw'];
            return true;
        }
        return false;
    }
    /**
     * Save the configuration in the database
     *
     * @return <bool> TRUE on success
     */
    public function store() {
        $cfgData = DB::get()->escape_string($this->cfgData);
        return DB::get()->query("INSERT INTO `config` VALUES ('" . $this->sid . "', '" . $cfgData . "', '" . $this->isRaw . "', '') ON DUPLICATE KEY UPDATE `cfg` = '" . $cfgData . "', `raw` = '" . $this->isRaw . "'");
    }
    /**
     * Reset configuration to default
     *
     * @return <bool> TRUE on success
     */
    public function resetConfig() {
        if(self::fetch()) {
            $newConfigArray = array();
            $oldConfigArray = self::get(true);
            if(array_key_exists('hostname', $oldConfigArray)) {
                $newConfigArray['hostname'] = $oldConfigArray['hostname'];
            }
            else {
                $newConfigArray['hostname'] = $this->defaultCvars['hostname'];
            }
            if(array_key_exists('sv_password', $oldConfigArray)) {
                $newConfigArray['sv_password'] = $oldConfigArray['sv_password'];
            }
            if(array_key_exists('mp_timelimit', $oldConfigArray)) {
                $newConfigArray['mp_timelimit'] = $oldConfigArray['mp_timelimit'];
            }
            self::set(array_keys($newConfigArray), array_values($newConfigArray));
            self::store();
            return true;
        }
        return false;
    }
}