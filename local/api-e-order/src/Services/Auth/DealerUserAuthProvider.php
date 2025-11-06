<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use OrderApi\Config\ApiConfig;

use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Repositories\DealerUserRepository;

class DealerUserAuthProvider implements AuthProviderInterface
{
  public const string PROVIDER = ProviderType::DEALER;

  public function login(string $login, string $password): ?array
  {
    if (!$login || !$password) return null;

    $user = DealerUserRepository::findUserByLogin($login);

    if (!$user || !password_verify($password, $user['password'])) {
      return null;
    }

    $token = $this->generateJwt($user);

    // Нормализуем данные пользователя
    $normalizedUser = self::normalizeUser($user);

    return [
      'user'          => $normalizedUser,
      'token'         => $token,
      'expires_in'    => ApiConfig::JWT_EXPIRE,
      'token_type'    => 'Bearer',
      'provider'      => self::PROVIDER,
    ];
  }

  public static function validatePayload(array $payload): bool
  {
    return ($payload['provider'] ?? '') === self::PROVIDER
      && !empty($payload['dealer_id'])
      && !empty($payload['dealer_prefix']);
  }

  private function generateJwt(array $user): string
  {
    $now = new DateTimeImmutable();
    $payload = [
      'iss' => ApiConfig::API_NAME,
      'iat' => $now->getTimestamp(),
      'exp' => $now->modify('+' . ApiConfig::JWT_EXPIRE . ' seconds')->getTimestamp(),
      'sub' => $user['ID'],
      'dealer_id'     => $user['dealer_id'],
      'dealer_prefix' => $user['dealer_prefix'],
      'login'         => $user['login'],
      'name'          => $user['name'] ?? '',
      'provider'      => self::PROVIDER,      
    ];

    return JWT::encode($payload, ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO);
  }

  public static function normalizeUser(array $user): array
  {
    $contacts = @json_decode($user['contacts'], true) ?? [];

    return [
      'id' => (int)$user['ID'],
      'login' => $user['login'],
      'name' => $user['name'] ?? '',
      'email' => $contacts['email'] ?? '',
      'phone' => $contacts['phone'] ?? '',
      'dealer_id' => (int)$user['dealer_id'],
      'dealer_prefix' => $user['dealer_prefix'],
      'provider' => self::PROVIDER,
      'role' => UserRole::DEALER
    ];
  }
}