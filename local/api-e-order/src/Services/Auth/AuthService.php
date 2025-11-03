<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OrderApi\Config\ApiConfig;

class AuthService
{
  public const string COOKIE_NAME = 'auth_token';
  private const string COOKIE_PATH = '/local/api-e-order/';  // ТОЧНО как в .htaccess
  private const string COOKIE_DOMAIN = '';
  private const bool COOKIE_SECURE = false; //ToDo Для Localhost
  private const bool COOKIE_HTTPONLY = true;
  private const string COOKIE_SAMESITE = 'Lax'; //none //ToDo

  /**
   * Универсальный логин
   */
  public function login(string $login, string $password, string $providerType): ?array
  {
    $authProvider = $this->getAuthProvider($providerType);

    if (!$authProvider) {
      return null;
    }

    $result = $authProvider->login($login, $password);

    if ($result) {
      self::setAuthCookie($result['token']);
      return $result;
    }

    return null;
  }


  public function logout(): void
  {
    self::clearAuthCookie();
  }

  /**
   * Получить сервис провайдер
   */
  private function getAuthProvider(string $providerType): ?AuthServiceInterface
  {
    return match ($providerType) {
      DealerUserAuthService::PROVIDER => new DealerUserAuthService(),
      LigronUserAuthService::PROVIDER => new LigronUserAuthService(),
      default => null,
    };
  }

  /**
   * Установка HttpOnly куки
   */
  public static function setAuthCookie(string $token): void
  {
    setcookie(self::COOKIE_NAME, $token, [
      'expires'  => time() + ApiConfig::JWT_EXPIRE,
      'path'     => self::COOKIE_PATH,
      'domain'   => self::COOKIE_DOMAIN,
      'secure'   => self::COOKIE_SECURE,
      'httponly' => self::COOKIE_HTTPONLY,
      'samesite' => self::COOKIE_SAMESITE
    ]);
  }

  /**
   * Удаление куки
   */
  public static function clearAuthCookie(): void
  {
    setcookie(self::COOKIE_NAME, '', [
      'expires'  => time() - 3600,
      'path'     => self::COOKIE_PATH,
      'domain'   => self::COOKIE_DOMAIN,
      'secure'   => self::COOKIE_SECURE,
      'httponly' => self::COOKIE_HTTPONLY,
      'samesite' => self::COOKIE_SAMESITE
    ]);
  }

  /**
   * Валидация токена (универсальная)
   */
  public static function validateFromCookie(): ?array
  {
    $token = $_COOKIE[self::COOKIE_NAME] ?? null;
    if (!$token) {
      return null;
    }

    try {
      $decoded = JWT::decode($token, new Key(ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO));
      $payload = (array) $decoded;

      $provider = $payload['provider'] ?? null;

      $service = match ($provider) {
        DealerUserAuthService::PROVIDER => DealerUserAuthService::class,
        LigronUserAuthService::PROVIDER => LigronUserAuthService::class,
        default => null,
      };

      if ($service && $service::validateFromCookie() !== null) {
        return $payload;
      }

      self::clearAuthCookie();
      return null;

    } catch (\Exception $e) {

      self::clearAuthCookie();
      return null;
    }
  }

}