<?php
/**
 * SSH class
 *
 * Contains all SSH related functions.
 * Represents an SSH2 connection.
 */
class SSH {
    /**
     * Holds the link to the open connection
     *
     * @var <resource> An SSH connection link identifier
     */
    private $ssh = null;
    /**
     * Initializes an SSH2 connection
     *
     * @param <array> $hostinfo Associative array containing address and host key
     * @return <bool> TRUE on success
     */
    public function __construct($hostinfo) {
        $addr = $hostinfo['addr'];
        $hostkey = $hostinfo['key'];
        if($ssh = ssh2_connect($addr)) {
            if(ssh2_fingerprint($ssh) != $hostkey) {
                return false;
            }
            if(ssh2_auth_pubkey_file($ssh, SSH_USER, SSH_KEY . '.pub', SSH_KEY, SSH_PASS)) {
                $this->ssh = $ssh;
                return true;
            }
        }
        return false;
    }
    /**
     * Executes a command on the remote server
     *
     * @param <string> $cmd Command to execute
     * @return <bool> TRUE on success
     */
    public function exec($cmd) {
        if(!isset($this->ssh)) {
            return false;
        }
        if($stream = ssh2_exec($this->ssh, $cmd)) {
            stream_set_blocking($stream, true);
            while(!feof($stream)) {
                fgets($stream);
            }
            fclose($stream);
            return true;
        }
        return false;
    }
    /**
     * Send a file to the remote server over SCP
     *
     * @param <string> $localf Path to the local file
     * @param <string> $remotef Path to destination on remote server
     * @return <bool> TRUE on success
     */
    public function sendFile($localf, $remotef) {
        if(!isset($this->ssh)) {
            return false;
        }
        return ssh2_scp_send($this->ssh, $localf, $remotef);
    }
}