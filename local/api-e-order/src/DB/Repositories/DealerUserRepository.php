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

  /**
   * Найти пользователя по ID и ID дилера + вернуть полные данные с salon_code
   */
  public static function findDetailedUserByIds(int $userId, int $dealerId): ?array
  {
    if (!$userId  || !$dealerId) {
      return null;
    }

    // 1. Получаем дилера + префикс
    $dealer = DealerTable::getList([
      'select' => ['cms_param', 'settings'],
      'filter' => ['=ID' => $dealerId, '=activity' => 1],
      'limit'  => 1,
    ])->fetch();

    if (!$dealer) {
      return null;
    }

    $prefix = $dealer['cms_param']['prefix'] ?? null;
    if (!is_string($prefix) || trim($prefix) === '') {
      return null;
    }

    $prefix = trim($prefix);

    try {
      // 2. Получаем класс таблицы {prefix}_users
      $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);

      // 3. Ищем пользователя
      $user = $dataClass::getList([
        'select' => ['ID', 'login', 'password', 'contacts', 'name', 'activity'],
        'filter' => ['=ID' => $userId, '=activity' => 1],
        'limit'  => 1,
      ])->fetch();

      if (!$user) {
        return null;
      }

      // 4. Извлекаем название салона
      $salonName = $user['contacts']['code'] ?? null;
      $salonName = is_string($salonName) ? trim($salonName) : null;

      // 5. Ищем код салона в settings дилера
      $salonCode = $salonName
        ? self::resolveSalonCodeFromDealerSettings($dealer['settings'], $salonName)
        : null;

      // 6. Формируем результат
      $user['dealer_id']       = $dealerId;
      $user['dealer_prefix']   = $prefix;
      $user['salon_name']      = $salonName;
      $user['salon_code']      = $salonCode;

      return $user;

    } catch (\Throwable $e) {
      // Логируем при необходимости
      return null;
    }
  }

  /**
   * Вспомогательный метод: ищет код салона по названию в settings['prop_dealercode']
   */
  private static function resolveSalonCodeFromDealerSettings($settings, string $searchName): ?string
  {
    if (!is_array($settings) || empty($settings['prop_dealercode'])) {
      return null;
    }

    foreach ($settings['prop_dealercode'] as $item) {
      if (!is_array($item)) {
        continue;
      }

      $name = '';
      $code = '';

      if (isset($item['name'], $item['code'])) {
        $name = trim($item['name']);
        $code = trim($item['code']);
      } elseif (count($item) >= 2 && isset($item[0], $item[1])) {
        $name = trim($item[0]);
        $code = trim($item[1]);
      }

      if ($name !== '' && strcasecmp($name, $searchName) === 0) {
        return $code !== '' ? $code : null;
      }
    }

    return null;
  }

}