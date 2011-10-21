<?php
/**
 * DB class
 *
 * Singleton class containing an instance of MySQLi, created on demand.
 */
class DB {
    private static $instance;
    private function __construct() { }
    /**
     * Return the MySQLi object
     *
     * @return <object> Instance of MySQLi
     */
    public static function get() {
        if(!isset(self::$instance)) {
            self::$instance = new MySQLi(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
            if(self::$instance->connect_error) {
                die('MySQL connection failed: ' . self::$instance->connect_error);
            }
        }
        return self::$instance;
    }
}