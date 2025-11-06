<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OrderApi\Config\ApiConfig;

class AuthService
{
  public function login(string $login, string $password, string $providerType): ?array
  {
    $provider = $this->getAuthProvider($providerType);
    return $provider?->login($login, $password);
  }

  public function logout(): void
  {
    // Ничего не делаем — клиент сам очистит localStorage
  }

  private function getAuthProvider(string $providerType): ?AuthProviderInterface
  {
    return match ($providerType) {
      DealerUserAuthProvider::PROVIDER => new DealerUserAuthProvider(),
      LigronUserAuthProvider::PROVIDER  => new LigronUserAuthProvider(),
      default => null,
    };
  }

  /**
   * Универсальная валидация по заголовку Authorization
   */
  public static function validateFromHeader(): ?array
  {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s(\S+)/', $header, $matches)) {
      return null;
    }

    $token = $matches[1];

    try {
      $decoded = JWT::decode($token, new Key(ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO));
      $payload = (array)$decoded;

      $provider = $payload['provider'] ?? null;
      $service  = match ($provider) {
        DealerUserAuthProvider::PROVIDER => DealerUserAuthProvider::class,
        LigronUserAuthProvider::PROVIDER  => LigronUserAuthProvider::class,
        default => null,
      };

      if ($service && $service::validatePayload($payload)) {
        return $payload;
      }

      return null;
    } catch (\Throwable $e) {
      return null;
    }
  }
}