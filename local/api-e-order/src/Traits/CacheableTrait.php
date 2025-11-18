<?php

declare(strict_types=1);

namespace OrderApi\Traits;

use Bitrix\Main\Data\Cache;
use Closure;

/**
 * Универсальный трейт для кэширования в Bitrix
 * Подключай в любой сервис/репозиторий
 */
trait CacheableTrait
{
  private const string DEFAULT_CACHE_DIR = '/order_api';

  /**
   * Получить данные из кэша или выполнить callback и закэшировать
   *
   * @param string      $cacheId   Уникальный ID кэша
   * @param int         $ttl       Время жизни в секундах
   * @param Closure     $callback  Функция, которая вернёт данные
   *
   * @return mixed
   */
  protected static function cache(
    string   $cacheId,
    int      $ttl,
    Closure  $callback,
    ?string  $cacheDir = null
  ): mixed {
    $cacheDir ??= self::DEFAULT_CACHE_DIR;
    $cache = Cache::createInstance();

    if ($cache->initCache($ttl, $cacheId, $cacheDir)) {
      return $cache->getVars();
    }

    $cache->startDataCache();
    $data = $callback();
    $cache->endDataCache($data);

    return $data;
  }

  /**
   * Очистить конкретный кэш
   */
  protected static function clearCache(string $cacheId, ?string $cacheDir = null): void
  {
    $cacheDir ??= self::DEFAULT_CACHE_DIR;
    $cache = Cache::createInstance();
    $cache->clean($cacheId, $cacheDir);
  }

  /**
   * Очистить всю папку кэша
   */
  protected static function clearCacheDir(?string $cacheDir = null): void
  {
    $cacheDir ??= self::DEFAULT_CACHE_DIR;
    $cache = Cache::createInstance();
    $cache->cleanDir($cacheDir);
  }
}