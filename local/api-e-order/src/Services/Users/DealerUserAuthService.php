<?php

declare(strict_types=1);

namespace OrderApi\Services\Users;

use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\SystemException;
use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;

class DealerUserAuthService
{
  /**
   * Получить пользователя по логину
   *
   * @param string $login
   * @return array|null
   * @throws SystemException
   */
  public function getUserByLogin(string $login): ?array
  {
    if (empty($login)) {
      return null;
    }

    $dealers = DealerTable::getList([
      'select' => ['ID', 'cms_param'],
      'filter' => ['=activity' => 1],
    ]);

    while ($dealer = $dealers->fetch()) {
      $cmsParam = $dealer['cms_param'];
      $prefix = $cmsParam['prefix'] ?? null;

      if (!$prefix || !is_string($prefix)) {
        continue;
      }

      $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);

      $userResult = $dataClass::getList([
        'select' => ['ID', 'login', 'password', 'name', 'activity'],
        'filter' => [
          '=login' => $login,
          '=activity' => 1,
        ],
        'limit' => 1,
      ]);

      $user = $userResult->fetch();
      if ($user) {
        $user['dealer_prefix'] = $prefix;
        $user['dealer_id'] = $dealer['ID'];
        return $user;
      }
    }

    return null;
  }
}