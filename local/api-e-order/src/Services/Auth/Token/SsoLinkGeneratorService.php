<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;

use OrderApi\Config\ApiConfig;
use OrderApi\DTO\Auth\UserDTO;

final readonly class SsoLinkGeneratorService
{

  public function __construct(
    private UserDTO $user
  ) {}

  /**
   * Генерирует ссылку для SSO авторизации в калькуляторе
   */
  public function generateLink(): string
  {
    // Формат: LOGIN | PREFIX | DEALER_ID | TIMESTAMP
    $payload = sprintf(
      '%s|%s|%d|%d',
      $this->user->login,
      $this->user->dealer_prefix,
      $this->user->dealer_id,
      time()
    );

    $encryptedParam = $this->encrypt($payload);

    return sprintf(
      '%s/?mode=auth&cmsAction=sso&param=%s',
      rtrim(ApiConfig::CALC_URL, '/'),
      urlencode($encryptedParam)
    );
  }

  /**
   * Шифрует данные особым алгоритмом
   */

  private function encrypt(string $value): string
  {
    $key = ApiConfig::SSO_CALC_ENCRYPT_KEY ?: 'eKey';
    $algorithm = ApiConfig::SSO_CALC_ALGO ?: 'AES-256-CBC';

    $ivLength = openssl_cipher_iv_length($algorithm);
    if ($ivLength === false) {
      throw new \RuntimeException("Invalid algorithm: $algorithm");
    }

    $iv = openssl_random_pseudo_bytes($ivLength);

    $encrypted = openssl_encrypt($value, $algorithm, $key, 0, $iv);

    if ($encrypted === false) {
      throw new \RuntimeException("Encryption failed: " . openssl_error_string());
    }

    $result = $encrypted . '::' . $iv;

    return base64_encode($result);
  }
}