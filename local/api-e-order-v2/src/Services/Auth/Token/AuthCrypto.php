<?php
declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use OrderApiV2\Config\ApiConfig;
use RuntimeException;

class AuthCrypto
{
    /**
     * Шифрование для SSO ссылок
     */
    public static function encryptSso(string $value): string
    {
        $key = ApiConfig::SSO_CALC_ENCRYPT_KEY ?: 'eKey';
        $algorithm = ApiConfig::SSO_CALC_ALGO ?: 'aes-256-cbc';

        $ivLength = openssl_cipher_iv_length($algorithm);
        if ($ivLength === false) {
            throw new RuntimeException("Invalid algorithm: $algorithm");
        }

        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($value, $algorithm, $key, 0, $iv);

        if ($encrypted === false) {
            throw new RuntimeException("Encryption failed: " . openssl_error_string());
        }

        $result = $encrypted . '::' . $iv;

        return base64_encode($result);
    }

    /**
     * Дешифрование SSO токенов
     */
    public static function decryptSso(string $token): ?string
    {
        $decoded = base64_decode($token);
        if ($decoded === false || !str_contains($decoded, '::')) {
            return null;
        }

        [$encrypted, $iv] = explode('::', $decoded, 2);

        $key = ApiConfig::SSO_CALC_ENCRYPT_KEY ?: 'eKey';
        $algorithm = ApiConfig::SSO_CALC_ALGO ?: 'aes-256-cbc';

        $decrypted = openssl_decrypt($encrypted, $algorithm, $key, 0, $iv);

        return $decrypted !== false ? $decrypted : null;
    }

}