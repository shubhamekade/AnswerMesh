<?php
/**
 * Symmetric encryption utility for sensitive credentials (API keys).
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Encryption
{
    private const CIPHER = 'aes-256-cbc';

    /**
     * Derive encryption key from WordPress salt constants.
     */
    private static function get_key(): string
    {
        $salt = defined('AUTH_KEY') && AUTH_KEY ? AUTH_KEY : 'answermesh-default-fallback-salt';
        $secondary = defined('SECURE_AUTH_SALT') && SECURE_AUTH_SALT ? SECURE_AUTH_SALT : 'secondary-salt-seed';
        return hash('sha256', $salt . $secondary, true);
    }

    /**
     * Encrypt plaintext string.
     *
     * @param string $plaintext Data to encrypt.
     * @return string Base64 encoded payload: iv:tag:ciphertext or empty string on failure.
     */
    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $key = self::get_key();
        $iv_length = openssl_cipher_iv_length(self::CIPHER);
        if ($iv_length === false) {
            return '';
        }

        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt ciphertext string.
     *
     * @param string $ciphertext Base64 encoded payload.
     * @return string Original plaintext or empty string on failure.
     */
    public static function decrypt(string $ciphertext): string
    {
        if ($ciphertext === '') {
            return '';
        }

        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false) {
            return '';
        }

        $key = self::get_key();
        $iv_length = openssl_cipher_iv_length(self::CIPHER);
        if ($iv_length === false || strlen($decoded) <= $iv_length) {
            return '';
        }

        $iv = substr($decoded, 0, $iv_length);
        $raw_ciphertext = substr($decoded, $iv_length);

        $decrypted = openssl_decrypt($raw_ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Mask an API key for safe UI display (e.g. sk-••••••••1234).
     */
    public static function mask_key(string $key): string
    {
        $len = strlen($key);
        if ($len === 0) {
            return '';
        }
        if ($len <= 6) {
            return str_repeat('•', $len);
        }
        $prefix = substr($key, 0, 3);
        $suffix = substr($key, -4);
        return $prefix . '••••••••' . $suffix;
    }
}
