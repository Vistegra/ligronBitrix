<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;

use OrderApi\Config\ApiConfig;

class AuthCrypto
{
  public static function encrypt(string $data): string
  {
    $secret = ApiConfig::MANAGER_SECRET;

    $key = substr(hash('sha256', $secret, true), 0, 16);
    $encrypted = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
  }

  public static function decrypt(string $encryptedData): false|string
  {
    $secret = ApiConfig::MANAGER_SECRET;

    $key = substr(hash('sha256', $secret, true), 0, 16);
    $data = base64_decode(str_replace(['-', '_'], ['+', '/'], $encryptedData));

    return openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
  }

}