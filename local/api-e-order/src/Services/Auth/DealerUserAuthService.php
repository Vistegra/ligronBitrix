<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use OrderApi\Config\ApiConfig;
use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;

class DealerUserAuthService implements AuthServiceInterface
{
  public const string PROVIDER = 'dealer';

  public function login(string $login, string $password): ?array
  {
    if (!$login || !$password) return null;

    $user = $this->findUserByLogin($login);
    if (!$user || !password_verify($password, $user['password'])) {
      return null;
    }

    $token = $this->generateJwt($user);
    unset($user['password']);

    return [
      'user'          => $user,
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

  private function findUserByLogin(string $login): ?array
  {
    $dealers = DealerTable::getList([
      'select' => ['ID', 'cms_param'],
      'filter' => ['=activity' => 1],
    ]);

    while ($dealer = $dealers->fetch()) {
      $prefix = $dealer['cms_param']['prefix'] ?? null;
      if (!$prefix || !is_string($prefix)) continue;

      try {
        $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);
        $user = $dataClass::getList([
          'select' => ['ID', 'login', 'password', 'name', 'activity'],
          'filter' => ['=login' => $login, '=activity' => 1],
          'limit'  => 1,
        ])->fetch();

        if ($user) {
          $user['dealer_id']     = $dealer['ID'];
          $user['dealer_prefix'] = $prefix;
          return $user;
        }
      } catch (\Throwable) {
        continue;
      }
    }

    return null;
  }
}