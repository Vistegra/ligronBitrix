<?php

declare(strict_types=1);

namespace OrderApiV2\Config;

/**
 * Единый реестр настроек кэширования
 */
final class CacheConfig
{
  // -------------------
  // Время жизни (TTL) в секундах
  // -------------------

  public const int TTL_HIERARCHY = 3600;  // 1 час для прав доступа
  public const int TTL_STATUSES  = 86400; // 24 часа для справочников

  // -------------------
  // Директории кэша
  // -------------------

  public const string DIR_HIERARCHY = '/order_api_v2/hierarchy';
  public const string DIR_STATUSES  = '/order_api_v2/statuses';

  // -------------------
  // Генераторы ключей
  // -------------------

  public static function getDealerHierarchyKey(string $salonCode): string
  {
    return 'hierarchy_dealer_' . md5($salonCode);
  }

  public static function getLigronHierarchyKey(string $userCode): string
  {
    return 'hierarchy_ligron_' . md5($userCode);
  }
}