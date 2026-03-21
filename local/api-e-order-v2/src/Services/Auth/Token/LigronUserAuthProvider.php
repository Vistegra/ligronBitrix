<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use Firebase\JWT\JWT;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\Constants\ProviderType;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DB\Repositories\UserRepository;
use OrderApiV2\DTO\Auth\{JwtPayload, UserDTO};

class LigronUserAuthProvider implements AuthProviderInterface
{
  public const string PROVIDER = ProviderType::LIGRON;

  public function login(string $login, string $password): ?array
  {
    if (!$login || !$password) return null;

    $user = UserRepository::findLigronUserByLogin($login);

    if (!$user || trim((string)$user['password']) !== $password) {
      return null;
    }

    $userDTO = self::normalizeUser($user);
    $token = $this->generateJwt($userDTO);

    return [
      'user' => $userDTO->toArray(),
      'token' => $token,
      'expires_in' => ApiConfig::JWT_EXPIRE,
      'token_type' => 'Bearer',
      'provider' => self::PROVIDER,
    ];
  }

  public function loginByToken(string $token): ?array
  {
    //ToDo реализовать поиск по токену в ligron_users
    return null;
  }

  public static function validatePayload(array $payload): bool
  {
    $userData = $payload['user'] ?? [];

    $allowedRoles = [
      UserRole::LIGRON_MANAGER,
      UserRole::LIGRON_OFFICE_MANAGER
    ];

    return ($userData['provider'] ?? '') === self::PROVIDER
      && in_array($userData['role'] ?? '', $allowedRoles, true);
  }

  private function generateJwt(UserDTO $user): string
  {
    $now = new \DateTimeImmutable();
    $payload = new JwtPayload(
      iss: ApiConfig::API_NAME,
      iat: $now->getTimestamp(),
      exp: $now->modify('+' . ApiConfig::JWT_EXPIRE . ' seconds')->getTimestamp(),
      user: $user
    );
    return JWT::encode($payload->toArray(), ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO);
  }

  public static function normalizeUser(array $user): UserDTO
  {
    return new UserDTO(
      id:        (int)$user['id'],
      login:     trim((string)$user['username']),
      name:      trim((string)$user['name']),
      provider:  self::PROVIDER,
      role:      trim((string)$user['role_code']),
      email:     trim((string)($user['email'] ?? '')),
      phone:     trim((string)($user['phone'] ?? '')),
      user_code: trim((string)$user['user_code']),
    );
  }
}