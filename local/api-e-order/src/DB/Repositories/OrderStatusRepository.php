<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApi\DB\Models\OrderStatusTable;

final class OrderStatusRepository
{
  private const int CACHE_TTL = 86400; // 24 часа

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function getAll(): array
  {
    $result = OrderStatusTable::getList([
      'select' => ['*'],
      'order' => ['sort' => 'asc'],
      'cache' => ['ttl' => self::CACHE_TTL],
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
      'cache' => ['ttl' => self::CACHE_TTL],
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
      'cache' => ['ttl' => self::CACHE_TTL],
    ]);

    return $result->fetch() ?: null;
  }


  public static function getDefaultStatus(): array
  {
    $result = OrderStatusTable::getList([
      'select' => ['id', 'name', 'code', 'color'],
      'limit' => 1,
      'order' => ['sort' => 'asc'],
      'cache' => ['ttl' => self::CACHE_TTL],
    ]);

    return $result->fetch() ?: [];
  }


}