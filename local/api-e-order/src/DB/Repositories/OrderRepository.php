<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use Bitrix\Main\Type\DateTime;
use OrderApi\DB\Events\OrderEvent;
use OrderApi\DB\Models\OrderTable;

final class OrderRepository
{
  private const int DEFAULT_LIMIT = 20;
  private const int DEFAULT_CACHE_TTL = 3600;
  private const array defaultQueryParams = [
      'select' => [
        '*',
        'status_code' => 'status.code',
        'status_name' => 'status.name',
        'status_color' => 'status.color',
        'parent_number' => 'parent.number',
        'parent_id' => 'parent.id',
      ],
      'order' => ['id' => 'desc'],
      'limit' => self::DEFAULT_LIMIT,
      'offset' => 0,
    ];

  /**
   * Универсальная выборка с пагинацией
   */
  private static function queryList(array $params = []): array
  {
    $params = array_merge(self::defaultQueryParams, $params);

    $result = OrderTable::getList($params);
    return $result->fetchAll();
  }

  /**
   * Создать заказ
   * @throws \Exception
   */
  public static function create(array $data): ?int
  {
    if (empty($data['name'])) {
      throw new \InvalidArgumentException('name обязательно');
    }

    $result = OrderTable::add($data);
    if (!$result->isSuccess()) {
      return null;
    }

    $orderId = $result->getId();
    $order = self::getById($orderId);

    OrderEvent::onCreated($orderId, $order);

    return $orderId;
  }

  /**
   * Обновить заказ
   * @throws \Exception
   */
  public static function update(int $id, array $data): bool
  {
    $result = OrderTable::update($id, $data);
    return $result->isSuccess();
  }

  /**
   * Удалить заказ (с проверкой дочерних)
   */
  public static function delete(int $id): bool
  {
    $order = self::getById($id);
    if (!$order) {
      return false;
    }

    if ($order['CHILDREN_COUNT'] > 0) {
      //ToDo возможно не нужно, будет триггер
      throw new \RuntimeException('Нельзя удалить заказ с дочерними');
    }

    $result = OrderTable::delete($id);
    return $result->isSuccess();
  }

  /**
   * Получить заказ по ID
   */
  public static function getById(int $id): ?array
  {

    $params = array_merge(self::defaultQueryParams, ['filter' => ['=id' => $id]]);

    $result = OrderTable::getList($params);

    $order = $result->fetch();

    if (!$order) {
      return null;
    }

    $order['files'] = OrderFileRepository::getByOrderId($order['id']);

    return $order;
  }


  /**
   * Полное количество (с фильтром)
   */
  public static function getTotalCount(array $filter = []): int
  {
    $result = OrderTable::getList([
      'filter' => $filter,
      'count_total' => true,
      'cache' => ['ttl' => 300],
    ]);

    return $result->getCount();
  }

  /**
   * Сменить статус
   */
  public static function changeStatus(int $orderId, string $newStatusCode, ?string $comment = null): bool
  {
    $status = OrderStatusRepository::findByCode($newStatusCode);
    if (!$status) {
      return false;
    }

    $order = self::getById($orderId);
    if (!$order) {
      return false;
    }

    $oldStatusCode = $order['status_code'] ?? null;

    $history = $order['status_history'] ?: [];
    $history[] = [
      'date' => (new DateTime())->toString(),
      'from' => $oldStatusCode,
      'to' => $newStatusCode,
      'comment' => $comment,
    ];

    $updateResult = OrderTable::update($orderId, [
      'status_id' => $status['id'],
      'status_history' => $history,
    ]);

    if ($updateResult->isSuccess()) {
      $updatedOrder = self::getById($orderId);
      OrderEvent::onStatusChanged($orderId, $oldStatusCode, $newStatusCode, $updatedOrder);
      return true;
    }

    return false;
  }

  /**
   * Дочерние заказы с пагинацией
   */
  public static function getChildren(int $parentId, int $limit = 50, int $offset = 0): array
  {
    return self::queryList([
      'filter' => ['=parent_id' => $parentId],
      'order' => ['id' => 'asc'],
      'limit' => $limit,
      'offset' => $offset,
    ]);
  }

  /**
   * Корневые заказы с пагинацией
   */
  public static function getRootOrders(array $filter = [], int $limit = 50, int $offset = 0): array
  {
    $filter['=parent_id'] = null;
    return self::queryList([
      'filter' => $filter,
      'limit' => $limit,
      'offset' => $offset,
    ]);
  }

  /**
   * Поиск по имени с пагинацией
   */
  public static function searchByName(string $query, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
  {
    return self::queryList([
      'filter' => ['%name' => $query],
      'limit' => $limit,
      'offset' => $offset,
      'cache' => ['ttl' => 600],
    ]);
  }

  /**
   * Заказы дилера с пагинацией
   */
  public static function getByDealer(string $prefix, int $userId, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
  {
    return self::queryList([
      'filter' => [
        '=dealer_prefix' => $prefix,
        '=dealer_user_id' => $userId,
      ],
      'limit' => $limit,
      'offset' => $offset,
    ]);
  }

  /**
   * Заказы менеджера с пагинацией
   */
  public static function getByManager(int $managerId, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
  {
    return self::queryList([
      'filter' => ['=manager_id' => $managerId],
      'limit' => $limit,
      'offset' => $offset,
    ]);
  }
}