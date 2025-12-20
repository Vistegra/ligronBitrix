<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;
use OrderApi\Traits\CacheableTrait;

/**
 * Репозиторий для работы с данными Дилеров и их Пользователей.
 * Отвечает за маппинг ИНН, поиск салонов и получение детальной информации.
 */
class DealerUserRepository
{
  use CacheableTrait;

  private const string CACHE_ID = 'inn_dealer_id_map';
  private const int CACHE_TTL = 86400; // 24 часа
  private const string CACHE_DIR = '/order_api/dealers/inn_dealer_id_map';

  /**
   * Возвращает кэшированную карту всех активных дилеров по ИНН.
   *
   * @return array<string, array{id: int, prefix: string, name: string}>
   *         Ключ массива - ИНН (string).
   */
  public static function getInnToDealerCacheMap(): array
  {
    return self::cache(
      cacheId: self::CACHE_ID,
      ttl: self::CACHE_TTL,
      callback: fn() => self::getInnToDealerMap(),
      cacheDir: self::CACHE_DIR
    );
  }

  /**
   * Получить информацию о дилере по ИНН (использует кэш).
   *
   * @param string $inn ИНН дилера
   * @return array{id: int, prefix: string, name: string}|null Данные дилера или null, если не найден
   */
  public static function getDealerByInn(string $inn): ?array
  {
    $inn = trim($inn);
    if ($inn === '') {
      return null;
    }

    $map = self::getInnToDealerCacheMap();
    return $map[$inn] ?? null;
  }

  /**
   * Находит ID пользователя дилера по Коду Салона (приходящему из 1С).
   *
   * Алгоритм:
   * 1. Загружает настройки конкретного дилера.
   * 2. Находит "Имя Салона", соответствующее переданному "Коду".
   * 3. Загружает всех активных пользователей этого дилера.
   * 4. Проверяет поле `contacts['code']` на совпадение с Именем (регистронезависимо).
   *
   * @param int    $dealerId  ID дилера
   * @param string $prefix    Префикс таблицы дилера (например, 'dea')
   * @param string $salonCode Код салона из 1С (например, '017587980')
   *
   * @return int|null ID найденного пользователя или null
   */
  public static function findUserIdBySalonCode(int $dealerId, string $prefix, string $salonCode): ?int
  {
    if (!$dealerId || empty($prefix) || empty($salonCode)) {
      return null;
    }

    // 1. Получаем настройки и находим Имя салона по Коду
    $settings = self::getDealerSettings($dealerId);
    $map = self::buildCodeToNameMap($settings);

    $targetSalonName = $map[$salonCode] ?? null;

    if (!$targetSalonName) {
      return null; // Код салона не найден в настройках дилера
    }

    // Приводим к нижнему регистру для надежного сравнения с данными пользователя
    $targetSalonNameLower = mb_strtolower($targetSalonName);

    try {
      $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);

      // 2. Выбираем всех активных пользователей (обычно их < 50)
      $users = $dataClass::getList([
        'select' => ['id', 'contacts'],
        'filter' => ['=activity' => 1],
      ])->fetchAll();

      // 3. Ищем совпадение в памяти
      foreach ($users as $user) {
        // Поле contacts уже декодировано в массив моделью
        $userSalonName = $user['contacts']['code'] ?? '';

        if (is_string($userSalonName) && mb_strtolower(trim($userSalonName)) === $targetSalonNameLower) {
          return (int)$user['id'];
        }
      }

      return null;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Находит пользователя для Аутентификации по Логину.
   * Перебирает таблицы всех активных дилеров.
   *
   * @param string $login Логин пользователя
   * @return array|null Массив данных пользователя с добавленными dealer_id и dealer_prefix
   */
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
          $user['dealer_id'] = (int)$dealer['id'];
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
   * Получает детальную информацию о пользователе для профиля (Frontend).
   * Определяет код салона пользователя по его имени, используя настройки дилера.
   *
   * @param int $userId   ID пользователя
   * @param int $dealerId ID дилера
   * @return array|null   Детальный массив данных или null
   */
  public static function findDetailedUserByIds(int $userId, int $dealerId): ?array
  {
    if (!$userId || !$dealerId) {
      return null;
    }

    // Получаем дилера целиком (нужны settings для карты салонов)
    $dealer = DealerTable::getByPrimary($dealerId, [
      'select' => ['name', 'cms_param', 'settings']
    ])->fetch();

    if (!$dealer) {
      return null;
    }

    $prefix = $dealer['cms_param']['prefix'] ?? null;
    if (!is_string($prefix) || trim($prefix) === '') {
      return null;
    }
    $prefix = trim($prefix);

    // Строим обратную карту: [ИмяLower => Код]
    $salonsMap = self::buildNameToCodeMap($dealer['settings'] ?? []);

    $inn = $dealer['settings']['prop_tin'] ?? '';

    try {
      $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);
      $user = $dataClass::getList([
        'select' => ['id', 'login', 'password', 'contacts', 'name'],
        'filter' => ['=ID' => $userId, '=activity' => 1],
        'limit' => 1,
      ])->fetch();

      if (!$user) {
        return null;
      }

      $salonName = $user['contacts']['code'] ?? null;
      $salonName = is_string($salonName) ? trim($salonName) : null;

      // Ищем код по имени
      $salonCode = $salonName ? ($salonsMap[mb_strtolower($salonName)] ?? null) : null;

      return [
        'name'        => $user['name'],
        'phone'       => $user['contacts']['phone'] ?? '',
        'email'       => $user['contacts']['email'] ?? '',
        'dealer_name' => $dealer['name'],
        'salon_name'  => $salonName,
        'salon_code'  => $salonCode,
        'password'    => $user['password'],
        'fetched_at'  => time(),
        'inn'         => $inn,
        'managers'    => WebUserRepository::getManagerDetailsByInn($inn),
      ];

    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Получает данные дилера по префиксу таблицы (вспомогательный метод).
   *
   * @param string $prefix
   * @param array  $params Дополнительные параметры для getList
   * @return array|false
   */
  public static function getDealerByPrefix(string $prefix, array $params = []): array|false
  {
    $params = array_merge($params, ['filter' => ['%cms_param' => $prefix], 'limit' => 1]);
    return DealerTable::getList($params)->fetch();
  }

  /**
   * Выполняет прямой запрос к БД для построения карты [INN => DealerData].
   * Используется внутри кэширующего метода.
   *
   * @return array<string, array{id: int, prefix: string, name: string}>
   */
  public static function getInnToDealerMap(): array
  {
    $result = DealerTable::getList([
      'select' => ['id', 'cms_param', 'settings', 'name'],
      'filter' => ['=activity' => 1],
      'cache'  => ['ttl' => 60], // Микро-кэш SQL запроса
    ]);

    $map = [];
    while ($dealer = $result->fetch()) {
      $inn = $dealer['settings']['prop_tin'] ?? null;
      if (is_string($inn) && $inn !== '') {
        $map[trim($inn)] = [
          'id'     => (int)$dealer['id'],
          'prefix' => $dealer['cms_param']['prefix'],
          'name'   => $dealer['name'],
        ];
      }
    }
    return $map;
  }

  // =========================================================================
  // ПРИВАТНЫЕ ХЕЛПЕРЫ И ПАРСЕРЫ
  // =========================================================================

  /**
   * Получает массив settings из БД по ID дилера.
   *
   * @param int $dealerId
   * @return array
   */
  private static function getDealerSettings(int $dealerId): array
  {
    $dealer = DealerTable::getByPrimary($dealerId, [
      'select' => ['settings']
    ])->fetch();

    return $dealer['settings'] ?? [];
  }

  /**
   * Извлекает пару [name, code] из элемента настроек, учитывая разные форматы хранения.
   *
   * @param mixed $item Элемент массива prop_dealercode
   * @return array{name: string, code: string}|null
   */
  private static function extractSalonData(mixed $item): ?array
  {
    if (!is_array($item)) {
      return null;
    }

    $name = '';
    $code = '';

    // Вариант 1: Ассоциативный массив ['name' => ..., 'code' => ...]
    if (isset($item['name'], $item['code'])) {
      $name = trim((string)$item['name']);
      $code = trim((string)$item['code']);
    }
    // Вариант 2: Индексный массив ['Имя', 'Код']
    elseif (count($item) >= 2) {
      $name = trim((string)($item[0] ?? ''));
      $code = trim((string)($item[1] ?? ''));
    }

    if ($name === '' || $code === '') {
      return null;
    }

    return ['name' => $name, 'code' => $code];
  }

  /**
   * Строит карту [Code => Name].
   * Используется для интеграции с 1С (поиск Имени по Коду).
   *
   * @param array $settings
   * @return array<string, string>
   */
  private static function buildCodeToNameMap(array $settings): array
  {
    $list = $settings['prop_dealercode'] ?? [];
    if (!is_array($list)) return [];

    $map = [];
    foreach ($list as $item) {
      $data = self::extractSalonData($item);
      if ($data) {
        // Ключ: Код, Значение: Имя (оригинал)
        $map[$data['code']] = $data['name'];
      }
    }
    return $map;
  }

  /**
   * Строит карту [NameLower => Code].
   * Используется для API фронтенда (поиск Кода по Имени пользователя).
   *
   * @param array $settings
   * @return array<string, string>
   */
  private static function buildNameToCodeMap(array $settings): array
  {
    $list = $settings['prop_dealercode'] ?? [];
    if (!is_array($list)) return [];

    $map = [];
    foreach ($list as $item) {
      $data = self::extractSalonData($item);
      if ($data) {
        // Ключ: Имя (в нижнем регистре), Значение: Код
        $map[mb_strtolower($data['name'])] = $data['code'];
      }
    }
    return $map;
  }
}