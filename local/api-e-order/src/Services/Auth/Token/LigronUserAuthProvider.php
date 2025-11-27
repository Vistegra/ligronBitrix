<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;

use Firebase\JWT\JWT;
use OrderApi\Config\ApiConfig;
use OrderApi\Constants\ProviderType;
use OrderApi\DB\Repositories\WebUserRepository;
use OrderApi\DTO\Auth\{JwtPayload, UserDTO};

class LigronUserAuthProvider implements AuthProviderInterface
{
  public const string PROVIDER = ProviderType::LIGRON;

  public function loginByToken(string $token): ?array
  {
    if (!$token) {
      return null;
    }

    $user = WebUserRepository::findUserByToken($token);

    if (!$user) {
      return null;
    }

    $userDTO = self::normalizeUser($user);

    $jwtToken = $this->generateJwt($userDTO);

    return [
      'user' => $userDTO->toArray(),
      'token' => $jwtToken,
      'expires_in' => ApiConfig::JWT_EXPIRE,
      'token_type' => 'Bearer',
      'provider' => self::PROVIDER,
    ];
  }

  public function login(string $login, string $password): ?array
  {
    if (!$login || !$password) {
      return null;
    }

    $user = WebUserRepository::findUserByLogin($login);

    if (!$user || $user['password'] !== $password) {
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

  public static function validatePayload(array $payload): bool
  {
    $userData = $payload['user'] ?? [];
    return ($userData['provider'] ?? '') === self::PROVIDER
      && in_array($userData['role'] ?? '', ['manager', 'office_manager'], true);
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

  public static function normalizeUser(array $user):  UserDTO
  {
    $role = $user['manager'] ? 'manager' : 'office_manager';

    return new UserDTO(
      id: (int)$user['id'],
      login: $user['login'],
      name: $user['name'] ?? '',
      provider: self::PROVIDER,
      role: $role,
      email: $user['email'] ?? '',
      phone: $user['phone'] ?? ''
    );
  }
}