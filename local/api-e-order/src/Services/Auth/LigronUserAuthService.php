<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use OrderApi\Config\ApiConfig;
use OrderApi\DB\Repositories\WebUserRepository;

class LigronUserAuthService implements AuthServiceInterface
{
  public const string PROVIDER = 'ligron';

  public function login(string $login, string $password): ?array
  {
    if (!$login || !$password) {
      return null;
    }

    $user = WebUserRepository::findUserByLogin($login);

    if (!$user || $user['password'] !== $password) {
      return null;
    }


    $token = $this->generateJwt($user);

   // Нормализуем данные пользователя
    $normalizedUser = self::normalizeUser($user);

    return [
      'user'       => $normalizedUser,
      'token'      => $token,
      'expires_in' => ApiConfig::JWT_EXPIRE,
      'token_type' => 'Bearer',
      'provider'   => self::PROVIDER,
    ];
  }

  public static function validatePayload(array $payload): bool
  {
    return ($payload['provider'] ?? '') === self::PROVIDER
      && in_array($payload['role'] ?? '', ['manager', 'office_manager'], true);
  }

  private function generateJwt(array $user): string
  {
    $now = new DateTimeImmutable();
    $role = $user['manager'] ? 'manager' : 'office_manager';

    $payload = [
      'iss'      => ApiConfig::API_NAME,
      'iat'      => $now->getTimestamp(),
      'exp'      => $now->modify('+' . ApiConfig::JWT_EXPIRE . ' seconds')->getTimestamp(),
      'sub'      => $user['id'],
      'login'    => $user['login'],
      'name'     => $user['name'] ?? '',
      'email'    => $user['email'] ?? '',
      'phone'    => $user['phone'] ?? '',
      'provider' => self::PROVIDER,
      'role'     => $role,
    ];

    return JWT::encode($payload, ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO);
  }


  public static function normalizeUser(array $user): array
  {
    $role = $user['manager'] ? 'manager' : 'office_manager';

    return [
      'id' => (int)$user['id'],
      'login' => $user['login'],
      'name' => $user['name'] ?? '',
      'email' => $user['email'] ?? '',
      'phone' => $user['phone'] ?? '',
      'role' => $role,
      'provider' => self::PROVIDER,
    ];
  }
}