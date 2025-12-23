<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;

use OrderApi\Config\ApiConfig;
use OrderApi\DB\Repositories\DealerUserRepository;
use OrderApi\DTO\Auth\UserDTO;

final readonly class SsoLinkGeneratorService
{

  public function __construct(
    private UserDTO $user
  )
  {
  }

  /**
   * Генерирует ссылку на корень раздела дилера
   */
  public function generateLink(): string
  {
    $this->ensureIsDealer();

    // /dealer/{id}
    return $this->buildSsoUrl('');
  }

  /**
   * Генерирует ссылку на конкретный заказ
   */
  public function generateOrderLink(string $ligronNumber): string
  {
    $this->ensureIsDealer();

    // Формируем "хвост" ссылки
    $redirectSuffix = '?ligron_number=' . $ligronNumber;

    return $this->buildSsoUrl($redirectSuffix);
  }

  /**
   * URL для входа
   */
  private function buildSsoUrl(string $redirectSuffix): string
  {
    // Базовый URL авторизации (всегда в корень, параметры передаем в payload)
    $baseUrl = rtrim(ApiConfig::CALC_URL, '/');

    // Формируем зашифрованный параметр
    $encryptedParam = $this->buildEncryptedPayload($redirectSuffix);

    return $baseUrl . '/?mode=auth&cmsAction=sso&param=' . urlencode($encryptedParam);
  }

  /**
   * Создает зашифрованный payload с 5 параметрами
   */
  private function buildEncryptedPayload(string $redirectSuffix): string
  {
    // Формат: LOGIN | PREFIX | DEALER_ID | REDIRECT_SUFFIX | TIMESTAMP
    $payload = sprintf(
      '%s|%s|%d|%s|%d',
      $this->user->login,
      $this->user->dealer_prefix,
      $this->user->dealer_id,
      $redirectSuffix, // Например: "?ligron_number=123" или ""
      time()
    );

    return $this->encrypt($payload);
  }

  private function ensureIsDealer(): void
  {
    if (!$this->user->isDealer() || empty($this->user->dealer_id)) {
      throw new \RuntimeException('Переход в калькулятор разрешен только для пользователей дилера.', 403);
    }
  }

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