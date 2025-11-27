<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OrderApi\Config\ApiConfig;
use OrderApi\DTO\Auth\{JwtPayload, UserDTO};
use OrderApi\Services\Auth\Session\AuthSession;

class AuthService
{
  public function login(string $login, string $password, string $providerType): ?array
  {
    AuthSession::clear();
    $provider = $this->getAuthProvider($providerType);
    return $provider?->login($login, $password);
  }

  public function logout(): void
  {
    AuthSession::clear();
  }

  private function getAuthProvider(string $providerType): ?AuthProviderInterface
  {
    return match ($providerType) {
      DealerUserAuthProvider::PROVIDER => new DealerUserAuthProvider(),
      LigronUserAuthProvider::PROVIDER => new LigronUserAuthProvider(),
      default => null,
    };
  }

  /**
   * Валидация JWT токена
   */
  public static function validateToken(string $token): ?JwtPayload
  {
    try {
      $decoded = JWT::decode($token, new Key(ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO));
      $payload = JwtPayload::fromStdClass($decoded);
      $payloadArray = $payload->toArray();

      // Проверяем структуру payload
      if (!isset($payloadArray['user']) || !is_array($payloadArray['user'])) {
        return null;
      }

      $userData = $payloadArray['user'];
      $provider = $userData['provider'] ?? null;

      $service = match ($provider) {
        DealerUserAuthProvider::PROVIDER => DealerUserAuthProvider::class,
        LigronUserAuthProvider::PROVIDER => LigronUserAuthProvider::class,
        default => null,
      };

      if ($service && $service::validatePayload($payloadArray)) {
        return JwtPayload::fromArray($payloadArray);
      }

      return null;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Получить UserDTO из JWT payload
   */
  public static function getUserFromToken(string $token): ?UserDTO
  {
    $payload = self::validateToken($token);
    return $payload?->user;
  }

  public function loginByToken(string $token): ?array
  {
    //реализована только для пользователей Ligron (WebUser)
    $provider = new LigronUserAuthProvider();
    return $provider->loginByToken($token);
  }
}