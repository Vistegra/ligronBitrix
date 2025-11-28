<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;


use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;
use OrderApi\Traits\CacheableTrait;

class DealerUserRepository
{
  use CacheableTrait;

  private const string CACHE_ID = 'inn_dealer_id_map';
  private const int CACHE_TTL = 86400; // 24 часа
  private const string CACHE_DIR = '/order_api/dealers/inn_dealer_id_map';

  public static function getInnToDealerCacheMap(): array
  {
    return self::cache(
      cacheId: self::CACHE_ID,
      ttl: self::CACHE_TTL,
      callback: fn() => self::getInnToDealerMap(),
      cacheDir: self::CACHE_DIR
    );
  }

  public static function getDealerByInn(string $inn): ?string
  {
    $inn = trim($inn);

    return $inn === '' ? null : (self::getInnToDealerMap()[$inn] ?? null);
  }

  public static function findUserByLogin(string $login): ?array
  {
    $dealers = DealerTable::getList([
      'select' => ['id', 'cms_param'],
      'filter' => ['=activity' => 1],
    ]);

    while ($dealer = $dealers->fetch()) {
      $prefix = $dealer['cms_param']['prefix'] ?? null;
      if (!$prefix || !is_string($prefix)) continue;

      try {
        $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);
        $user = $dataClass::getList([
          'select' => ['id', 'login', 'password', 'contacts', 'name', 'activity'],
          'filter' => ['=login' => $login, '=activity' => 1],
          'limit' => 1,
        ])->fetch();

        if ($user) {
          $user['dealer_id'] = $dealer['id'];
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
    if (!$userId || !$dealerId) {
      return null;
    }

    // 1. Получаем дилера + префикс
    $dealer = DealerTable::getList([
      'select' => ['name', 'cms_param', 'settings'],
      'filter' => ['=ID' => $dealerId, '=activity' => 1],
      'limit' => 1,
    ])->fetch();

    if (!$dealer) {
      return null;
    }

    $prefix = $dealer['cms_param']['prefix'] ?? null;
    if (!is_string($prefix) || trim($prefix) === '') {
      return null;
    }

    $prefix = trim($prefix);

    $salonsMap = self::getDealerSalonsMapBySettings($dealer['settings']);
    $inn = $dealer['settings']['prop_tin'] ?? '';

    try {
      // 2. Получаем класс таблицы {prefix}_users
      $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);

      // 3. Ищем пользователя
      $user = $dataClass::getList([
        'select' => ['id', 'login', 'password', 'contacts', 'name'],
        'filter' => ['=ID' => $userId, '=activity' => 1],
        'limit' => 1,
      ])->fetch();

      if (!$user) {
        return null;
      }

      $result = [];

      // 4. Извлекаем название салона
      $salonName = $user['contacts']['code'] ?? null;
      $salonName = is_string($salonName) ? trim($salonName) : null;

      // 5. Ищем код салона в settings дилера
      $salonCode = $salonName
        ? $salonsMap[mb_strtolower($salonName)]
        : null;

      // 6. Формируем результат
      $result['dealer_name'] = $dealer['name'];
      $result['salon_name'] = $salonName;
      $result['salon_code'] = $salonCode;
      $result['password'] = $user['password'];
      $result['fetched_at'] = time();
      $result['inn'] = $inn;
      $result['managers'] = WebUserRepository::getManagerDetailsByInn($inn);

      return $result;

    } catch (\Throwable $e) {

      return null;
    }
  }

  public static function getDealerByPrefix(string $prefix, $params = []): array|false
  {
    $params = array_merge($params, ['filter' => ['%cms_param' => $prefix], 'limit' => 1]);

    return DealerTable::getList($params)->fetch();
  }

  /**
   * Вспомогательный метод: ищет код салона по названию в settings['prop_dealercode']
   */
  public static function resolveSalonCodeFromDealerSettings($settings, string $searchName): ?string
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

  /**
   * Возвращает готовую карту: [INN => PREFIX]
   * Только активные дилеры с заполненным INN и prefix
   *
   * @return array<string, string>  ['7701234567' => 'dea_', ...]
   */
  public static function getInnToDealerMap(): array
  {
    $result = DealerTable::getList([
      'select' => [
        'id',
        'cms_param',
        'settings',
        'name'
      ],
      'filter' => ['=activity' => 1],
      'cache' => ['ttl' => 60], // кэшируем сам запрос на 1 минуту //ToDo TTL level constants
    ]);

    $map = [];

    while ($dealer = $result->fetch()) {

      $inn = $dealer['settings']['prop_tin'] ?? null;

      if (
        is_string($inn) && $inn !== ''
      ) {
        $map[trim($inn)] = [
          'id' => $dealer['id'],
          'prefix' => $dealer['cms_param']['prefix'],
          'name' => $dealer['name'],
        ];
      }
    }

    return $map;
  }

  /**
   * Строит карту слонов из settings['prop_dealercode'] [salon_name => salon_code, ...]
   * salon_name - в нижнем регистре!
   */
  private static function getDealerSalonsMapBySettings(array $settings): array
  {
    if (empty($settings['prop_dealercode'])) {
      return [];
    }

    $map = [];
    foreach ($settings['prop_dealercode'] as $item) {
      if (!is_array($item)) {
        continue;
      }

      $name = '';
      $code = '';

      if (isset($item['name'], $item['code'])) {
        $name = trim($item['name']);
        $code = trim($item['code']);
      } elseif (count($item) >= 2) {
        $name = trim($item[0] ?? '');
        $code = trim($item[1] ?? '');
      }

      if ($name !== '' && $code !== '') {
        $map[mb_strtolower($name)] = $code;
      }
    }

    return $map;
  }

}