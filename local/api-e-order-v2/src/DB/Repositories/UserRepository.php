<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Repositories;

use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\LigronUserTable;

class UserRepository
{

  /**
   * Поиск пользователя дилера по логину (username)
   */
  public static function findDealerUserByLogin(string $login): ?array
  {
    try {
      $user = DealerUserTable::getList([
        'select' => ['*'],
        'filter' => ['=username' => $login, '=active' => 1],
        'limit' => 1,
      ])->fetch();

      return $user ?: null;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Поиск менеджера Лигрон по логину (username)
   */
  public static function findLigronUserByLogin(string $login): ?array
  {
    try {
      $user = LigronUserTable::getList([
        'select' => ['*'],
        'filter' =>['=username' => $login, '=active' => 1],
        'limit' => 1,
      ])->fetch();

      return $user ?: null;
    } catch (\Throwable $e) {
      return null;
    }
  }

}