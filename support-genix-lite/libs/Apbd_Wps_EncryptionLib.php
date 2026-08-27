<?php

/**
 * Encryption library.
 *
 * Authenticated encryption for short-lived guest ticket tokens. Wire format:
 * base64( versionTag(2) . nonce/iv . [tag] . ciphertext ). decrypt*() return null
 * on any tampering, truncation, or format mismatch rather than trusting input.
 */

defined('ABSPATH') || exit;

class Apbd_Wps_EncryptionLib
{
    public $key = "APBDWPS";

    function __construct($key = "APBDWPS")
    {
        $this->key = $key;
    }

    static function getInstance($key)
    {
        return new self($key);
    }

    private function randomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        return openssl_random_pseudo_bytes($length);
    }

    private function deriveKey($password)
    {
        return substr(hash('sha256', $password, true), 0, 32);
    }

    function encrypt($plainText, $password = '')
    {
        if (empty($password)) {
            $password = $this->key;
        }
        $key = $this->deriveKey($password);

        // libsodium secretbox, bundled with core via sodium_compat so always available.
        if (function_exists('sodium_crypto_secretbox')) {
            $nonce  = $this->randomBytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plainText, $nonce, $key);
            return base64_encode('s1' . $nonce . $cipher);
        }

        if (in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $iv     = $this->randomBytes(12);
            $tag    = '';
            $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
            if (false === $cipher) {
                return '';
            }
            return base64_encode('g1' . $iv . $tag . $cipher);
        }

        $iv     = $this->randomBytes(16);
        $cipher = openssl_encrypt($plainText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (false === $cipher) {
            return '';
        }
        $macKey = hash('sha256', $password . '|mac', true);
        $mac    = hash_hmac('sha256', $iv . $cipher, $macKey, true);
        return base64_encode('c1' . $iv . $mac . $cipher);
    }

    function decrypt($encrypted, $password = '')
    {
        if (empty($password)) {
            $password = $this->key;
        }
        $key = $this->deriveKey($password);

        $raw = base64_decode($encrypted, true);
        if (false === $raw || strlen($raw) < 3) {
            return null;
        }

        $version = substr($raw, 0, 2);
        $body    = substr($raw, 2);

        if ('s1' === $version && function_exists('sodium_crypto_secretbox_open')) {
            $nlen = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
            if (strlen($body) <= $nlen) {
                return null;
            }
            $nonce  = substr($body, 0, $nlen);
            $cipher = substr($body, $nlen);
            $plain  = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            return (false === $plain) ? null : $plain;
        }

        if ('g1' === $version) {
            if (strlen($body) <= 28) { // 12-byte IV + 16-byte tag
                return null;
            }
            $iv     = substr($body, 0, 12);
            $tag    = substr($body, 12, 16);
            $cipher = substr($body, 28);
            $plain  = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            return (false === $plain) ? null : $plain;
        }

        if ('c1' === $version) {
            if (strlen($body) <= 48) { // 16-byte IV + 32-byte MAC
                return null;
            }
            $iv       = substr($body, 0, 16);
            $mac      = substr($body, 16, 32);
            $cipher   = substr($body, 48);
            $macKey   = hash('sha256', $password . '|mac', true);
            $expected = hash_hmac('sha256', $iv . $cipher, $macKey, true);
            if (! hash_equals($expected, $mac)) {
                return null;
            }
            $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            return (false === $plain) ? null : $plain;
        }

        // Unknown or legacy format — reject.
        return null;
    }

    function encryptObj($obj)
    {
        $text = wp_json_encode($obj);
        if (false === $text) {
            return '';
        }
        return $this->encrypt($text);
    }

    function decryptObj($ciphertext)
    {
        $text = $this->decrypt($ciphertext);
        if (null === $text || '' === $text) {
            return null;
        }
        $obj = json_decode($text);
        return (JSON_ERROR_NONE === json_last_error()) ? $obj : null;
    }
}
