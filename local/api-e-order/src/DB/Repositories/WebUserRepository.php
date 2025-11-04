<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\DB\Models\WebUserTable;

class WebUserRepository
{
  public static function findUserByLogin(string $login): ?array
  {
    try {
      $result = WebUserTable::getList([
        'select' => [
          'id',
          'login' => 'username',
          'password',
          'name',
          'email',
          'phone',
          'active',
          'manager',
        ],
        'filter' => [
          '=username' => $login,
          '=active' => 1,
        ],
        'limit' => 1,
      ]);

      return $result->fetch() ?: null;
    } catch (\Throwable $e) {
      error_log('Ligron auth error: ' . $e->getMessage());
      return null;
    }
  }

}