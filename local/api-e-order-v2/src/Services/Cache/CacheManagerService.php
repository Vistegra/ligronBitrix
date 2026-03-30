<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Cache;

use Bitrix\Main\Data\Cache;
use OrderApiV2\Config\CacheConfig;

/**
 * Централизованный сервис для управления (сброса) кэша
 */
final class CacheManagerService
{
  /**
   * Сбросить кэш прав доступа и иерархии (пользователи, дилеры, салоны)
   */
  public function clearHierarchyCache(): void
  {
    $this->cleanDir(CacheConfig::DIR_HIERARCHY);
  }

  /**
   * Сбросить кэш ORM для статусов
   */
  public function clearStatusesCache(): void
  {
    $this->cleanDir(CacheConfig::DIR_STATUSES);

    // очищаем тегированный кэш Bitrix
    if (defined('BX_COMP_MANAGED_CACHE')) {
      global $CACHE_MANAGER;
      $CACHE_MANAGER->ClearByTag('vs_e_order_status');
    }

  }

  /**
   * Полный сброс всего постоянного кэша API V2
   */
  public function clearAllAppCache(): void
  {
    $this->clearHierarchyCache();
    $this->clearStatusesCache();
  }

  /**
   * Очистка директории кэша средствами Bitrix
   */
  private function cleanDir(string $dir): void
  {
    $cache = Cache::createInstance();
    $cache->cleanDir($dir);
  }
}