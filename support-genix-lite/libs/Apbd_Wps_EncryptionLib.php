<?php

/**
 * Encryption library.
 */

defined('ABSPATH') || exit;

class Apbd_Wps_EncryptionLib
{
    public $key = "APBDWPS";
    private $cipher = "AES-256-CBC";
    function __construct($key = "APBDWPS")
    {
        $this->key = $key;
    }
    static function getInstance($key)
    {
        return new self($key);
    }

    function encrypt($plainText, $password = '')
    {
        if (empty($password)) {
            $password = $this->key;
        }
        $plainText = wp_rand(10, 99) . $plainText . wp_rand(10, 99);
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', $password, true), 0, 32);
        $iv = substr(strtoupper(md5($password)), 0, 16);
        return base64_encode(openssl_encrypt($plainText, $method, $key, OPENSSL_RAW_DATA, $iv));
    }
    function decrypt($encrypted, $password = '')
    {
        if (empty($password)) {
            $password = $this->key;
        }
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', $password, true), 0, 32);
        $iv = substr(strtoupper(md5($password)), 0, 16);
        $plaintext = openssl_decrypt(base64_decode($encrypted), $method, $key, OPENSSL_RAW_DATA, $iv);
        return substr($plaintext, 2, -2);
    }

    function encryptObj($obj)
    {
        $text = serialize($obj);
        return $this->encrypt($text);
    }
    function decryptObj($ciphertext)
    {
        $text = $this->decrypt($ciphertext);
        return unserialize($text);
    }
}
