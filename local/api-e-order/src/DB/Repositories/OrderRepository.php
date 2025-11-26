<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use Bitrix\Main\ObjectPropertyException;
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
        'parent_order_number' => 'parent.number',
        'parent_order_id' => 'parent.id',
      ],
      'order' => ['updated_at' => 'desc'],
      'limit' => self::DEFAULT_LIMIT,
      'offset' => 0,
    ];

  /**
   * Универсальная выборка с пагинацией
   */
  public static function queryList(array $params = []): array
  {
    $params = array_merge(self::defaultQueryParams, $params);

    $result = OrderTable::getList($params);
    return $result->fetchAll();
  }

  /**
   * Создать заказ
   */
  public static function create(array $data): ?array
  {
    if (empty($data['name'])) {
      throw new \InvalidArgumentException('Не указано имя заказа');
    }

    $result = OrderTable::add($data);
    if (!$result->isSuccess()) {
      $errors = $result->getErrorMessages();
      throw new \RuntimeException('Ошибка создания заказа: ' . implode(', ', $errors));
    }

    $id = (int)$result->getId();

    $order = self::getById($id);
    if (!$order) {
      throw new \RuntimeException('Заказ создан, но не найден при чтении');
    }

    OrderEvent::onCreated($order);

    return $order;
  }

  /**
   * Обновить заказ
   * @throws \Exception
   */
  public static function update(int $id, array $data): ?array
  {
    $result = OrderTable::update($id, $data);

    if (!$result->isSuccess()) {
      $errors = $result->getErrorMessages();

      throw new \RuntimeException('Ошибка обновления заказа: ' .implode(', ', $errors));
    }

    return self::getById($id);
  }

  /**
   * Удалить заказ (с проверкой дочерних)
   */
  public static function delete(int $id): bool
  {
    $result = OrderTable::delete($id);

    return $result->isSuccess();
  }

  /**
   * Получить заказ по ID
   */
  public static function getById(int $id): ?array
  {

    $params = array_merge(self::defaultQueryParams, ['filter' => ['=id' => $id], 'limit' => 1]);

    $result = OrderTable::getList($params);

    $order = $result->fetch();

    if (!$order) {
      return null;
    }

    return $order;
  }


  /**
   * Полное количество (с фильтром)
   * @throws ObjectPropertyException
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
   * @throws \Exception
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
  public static function getChildren(int $parentId, int $limit = 100, int $offset = 0): array
  {
    return self::queryList([
      'filter' => ['=parent_id' => $parentId],
      'order' => ['id' => 'asc'],
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