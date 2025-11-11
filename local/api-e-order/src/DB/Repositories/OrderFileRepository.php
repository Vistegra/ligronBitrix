<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\DB\Models\OrderFileTable;

final class OrderFileRepository
{

  /**
   * Добавить файл
   */
  public static function add(
    int $orderId,
    string $name,
    string $path,
    ?int $size = null,
    ?string $mime = null,
    int $uploadedBy = 1,
    int $uploadedById = 0
  ): ?int
  {
    $result = OrderFileTable::add([
      'order_id' => $orderId,
      'name' => $name,
      'path' => $path,
      'size' => $size,
      'mime' => $mime,
      'uploaded_by' => $uploadedBy,
      'uploaded_by_id' => $uploadedById,
    ]);

    return $result->isSuccess() ? $result->getId() : null;
  }

  /**
   * Удалить файл
   * @throws \Exception
   */
  public static function delete(int $fileId): bool
  {
    $result = OrderFileTable::delete($fileId);
    return $result->isSuccess();
  }

  /**
   * Получить файл по ID
   */
  public static function getById(int $id): ?array
  {
    $result = OrderFileTable::getList([
      'select' => ['*'],
      'filter' => ['=id' => $id],
    ]);

    return $result->fetch() ?: null;
  }

  public static function getByOrderId(int $orderId): ?array
  {
    $result = OrderFileTable::getList([
      'select' => ['*'],
      'filter' => ['=order_id' => $orderId],
    ]);

    return $result->fetchAll() ?: null;
  }

}