<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Token;


use Firebase\JWT\JWT;
use OrderApi\Config\ApiConfig;
use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Repositories\DealerUserRepository;
use OrderApi\DTO\Auth\{JwtPayload, UserDTO};

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
      && !empty($userData['dealer_id'])
      && !empty($userData['dealer_prefix']);
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
    $contacts = $user['contacts'];

    return new UserDTO(
      id: (int)$user['ID'],
      login: $user['login'],
      name: $user['name'] ?? '',
      provider: self::PROVIDER,
      role: UserRole::DEALER,
      email: $contacts['email'] ?? '',
      phone: $contacts['phone'] ?? '',
      dealer_id: (int)$user['dealer_id'],
      dealer_prefix: $user['dealer_prefix']
    );
  }
}