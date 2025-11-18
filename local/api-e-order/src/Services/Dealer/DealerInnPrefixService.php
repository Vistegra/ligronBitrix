<?php

declare(strict_types=1);

namespace OrderApi\Services\Dealer;

use OrderApi\DB\Repositories\DealerRepository;
use OrderApi\DB\Repositories\Traits\CacheableTrait;

final class DealerInnPrefixService
{
  use CacheableTrait;

  private const string CACHE_ID  = 'dealer_inn_prefix_map';
  private const int CACHE_TTL = 86400; // 24 часа
  private const string CACHE_DIR = '/order_api/dealers/inn_prefix';

  public static function getInnToPrefixMap(): array
  {
    return self::cache(
      cacheId: self::CACHE_ID,
      ttl: self::CACHE_TTL,
      callback: fn() => DealerRepository::getInnToPrefixMap(),
      cacheDir: self::CACHE_DIR
    );
  }

  public static function getPrefixByInn(string $inn): ?string
  {
    $inn = trim($inn);

    return $inn === '' ? null : (self::getInnToPrefixMap()[$inn] ?? null);
  }

  public static function clearCache(): void
  {
    self::clearCache(self::CACHE_ID, self::CACHE_DIR);
  }
}