<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApiV2\Config\CacheConfig;
use OrderApiV2\DB\Models\OrderStatusTable;

final class OrderStatusRepository
{
  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function getAll(): array
  {
    $result = OrderStatusTable::getList([
      'select' => ['*'],
      'order' =>['sort' => 'asc'],
      'cache' => ['ttl' => CacheConfig::TTL_STATUSES],
    ]);

    return $result->fetchAll();
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function findByCode(string $code): ?array
  {
    $result = OrderStatusTable::getList([
      'select' => ['*'],
      'filter' => ['=code' => $code],
      'limit' => 1,
      'cache' => ['ttl' => CacheConfig::TTL_STATUSES],
    ]);

    return $result->fetch() ?: null;
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function findById(int $id): ?array
  {
    $result = OrderStatusTable::getByPrimary($id, [
      'select' => ['*'],
      'cache' => ['ttl' => CacheConfig::TTL_STATUSES],
    ]);

    return $result->fetch() ?: null;
  }


  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function getDefaultStatus(): array
  {
    $result = OrderStatusTable::getList([
      'select' => ['id', 'name', 'code', 'color'],
      'limit' => 1,
      'order' => ['sort' => 'asc'],
      'cache' => ['ttl' => CacheConfig::TTL_STATUSES],
    ]);

    return $result->fetch() ?: [];
  }


}