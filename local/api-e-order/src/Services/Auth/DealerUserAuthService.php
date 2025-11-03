<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OrderApi\Config\ApiConfig;
use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;

class DealerUserAuthService implements AuthServiceInterface
{
  public const string PROVIDER = 'dealer';

  /**
   * Авторизация и генерация JWT
   */
  public function login(string $login, string $password): ?array
  {
    if (empty($login) || empty($password)) {
      return null;
    }

    $user = $this->getUserByLogin($login);
    if (!$user || !password_verify($password, $user['password'])) {
      return null;
    }

    $token = $this->generateJwt($user);

    unset($user['password']);

    return [
      'user' => $user,
      'expires_in' => ApiConfig::JWT_EXPIRE,
      'token_type' => 'Bearer', //Todo ?
      'token' => $token,
      'auth_provider' => self::PROVIDER //Todo ?
    ];
  }

  /**
   * Генерация JWT с провайдером
   */
  private function generateJwt(array $user): string
  {
    $now = new DateTimeImmutable();

    $payload = [
      'iss' => ApiConfig::API_NAME,
      'iat' => $now->getTimestamp(),
      'exp' => $now->modify('+' . ApiConfig::JWT_EXPIRE . ' seconds')->getTimestamp(),
      'sub' => $user['ID'],
      'dealer_id' => $user['dealer_id'],
      'dealer_prefix' => $user['dealer_prefix'],
      'login' => $user['login'],
      'name' => $user['name'] ?? '',
      'provider' => self::PROVIDER,
    ];

    return JWT::encode($payload, ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO);
  }

  /**
   * Валидация токена из куки
   */
  public static function validateFromCookie(): ?array
  {
    $token = $_COOKIE[AuthService::COOKIE_NAME] ?? null;
    if (!$token) {
      return null;
    }

    try {
      $decoded = JWT::decode($token, new Key(ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO));
      $payload = (array)$decoded;

      // Проверяем, что токен от этого провайдера
      return ($payload['provider'] ?? '') === self::PROVIDER ? $payload : null;
    } catch (\Exception $e) {
      self::clearAuthCookie();
      return null;
    }
  }

  /**
   * Удаление куки
   */
  public static function clearAuthCookie(): void
  {
    setcookie('auth_token', '', [
      'expires' => time() - ApiConfig::JWT_EXPIRE,
      'path' => '/', // '/local/api-e-order/' ToDo thinking
      'domain' => '',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'None'
    ]);
  }

  /**
   * Поиск пользователя по логину среди дилеров
   */
  private function getUserByLogin(string $login): ?array
  {
    if (empty($login)) {
      return null;
    }

    $dealers = DealerTable::getList([
      'select' => ['ID', 'cms_param'],
      'filter' => ['=activity' => 1],
    ]);

    while ($dealer = $dealers->fetch()) {
      $cmsParam = $dealer['cms_param'];
      $prefix = $cmsParam['prefix'] ?? null;

      if (!$prefix || !is_string($prefix)) {
        continue;
      }

      try {
        $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);

        $userResult = $dataClass::getList([
          'select' => ['ID', 'login', 'password', 'name', 'activity'],
          'filter' => [
            '=login' => $login,
            '=activity' => 1,
          ],
          'limit' => 1,
        ]);

        $user = $userResult->fetch();
        if ($user) {
          $user['dealer_prefix'] = $prefix;
          $user['dealer_id'] = $dealer['ID'];
          return $user;
        }
      } catch (\Exception $e) {
        continue;
      }
    }

    return null;
  }
}