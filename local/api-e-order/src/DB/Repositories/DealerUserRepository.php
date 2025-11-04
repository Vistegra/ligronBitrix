<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;


use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;

class DealerUserRepository
{

  public static function findUserByLogin(string $login): ?array
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
          'select' => ['ID', 'login', 'password', 'contacts', 'name', 'activity'],
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