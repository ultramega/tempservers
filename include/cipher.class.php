<?php
/**
 * Cipher class
 *
 * Handles all encryption and decryption.
 */
class Cipher {
    private $key, $iv;
    /**
     * Initialize the key and IV
     */
    function __construct() {
        $this->key = hash('sha256', CRYPT_KEY, true);
        $this->iv = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);
    }
    /**
     * Encrypt a string
     *
     * @param <string> $input Data to encrypt
     * @return <string> Encrypted data
     */
    function encrypt($input) {
        $output = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $input, MCRYPT_MODE_ECB, $this->iv);
        return base64_encode($output);
    }
    /**
     * Decrypt a string
     *
     * @param <string> $input Encrypted data
     * @return <string> Decrypted data
     */
    function decrypt($input) {
        $output = base64_decode($input);
        $output = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $output, MCRYPT_MODE_ECB, $this->iv);
        return trim($output);
    }
}