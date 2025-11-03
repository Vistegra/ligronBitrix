<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class LigronUserAuthService implements AuthServiceInterface
{
  public const string PROVIDER = 'ligron';

  public function login(string $login, string $password): ?array
  {
    // TODO: Реализовать позже
    return null;
  }

  public static function validateFromCookie(): ?array
  {
    $token = $_COOKIE['auth_token'] ?? null;
    if (!$token) return null;

    try {
      $payload = JWT::decode($token, new Key(\OrderApi\Config\ApiConfig::JWT_SECRET, 'HS256'));
      $data = (array) $payload;
      return ($data['provider'] ?? '') === 'ligron' ? $data : null;
    } catch (\Exception $e) {
      self::clearAuthCookie();
      return null;
    }
  }

}