<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Repositories;

use Bitrix\Main\Type\DateTime;
use Exception;
use OrderApiV2\DB\Events\OrderEvent;
use OrderApiV2\DB\Models\OrderTable;

/**
 * Репозиторий для работы с таблицей заказов vs_e_order.
 */
final class OrderRepository
{
  private const int DEFAULT_LIMIT = 20;

  /**
   * Стандартный набор полей для выборки
   */
  private const array DEFAULT_SELECT =[
    '*',
    'status_code'         => 'status.code',
    'status_name'         => 'status.name',
    'status_color'        => 'status.color',
    'parent_order_number' => 'parent.number',
    'parent_order_id'     => 'parent.id',
  ];

  /**
   * Универсальная выборка заказов
   */
  public static function queryList(array $params =[]): array
  {
    $params['select'] = array_merge(self::DEFAULT_SELECT, $params['select'] ?? []);
    $params['order']  = $params['order'] ??['updated_at' => 'desc'];
    $params['limit']  = $params['limit'] ?? self::DEFAULT_LIMIT;
    $params['offset'] = $params['offset'] ?? 0;

    return OrderTable::getList($params)->fetchAll();
  }

  /**
   * Получить заказ по ID
   */
  public static function getById(int $id): ?array
  {
    $result = OrderTable::getList([
      'select' => self::DEFAULT_SELECT,
      'filter' =>['=id' => $id],
      'limit'  => 1
    ])->fetch();

    return $result ?: null;
  }

  /**
   * Создать новый заказ
   * @throws Exception
   */
  public static function create(array $data): array
  {
    if (empty($data['name'])) {
      throw new \InvalidArgumentException('Не указано имя заказа');
    }

    $result = OrderTable::add($data);

    if (!$result->isSuccess()) {
      throw new \RuntimeException('Ошибка создания заказа: ' . implode(', ', $result->getErrorMessages()));
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
   * Обновить существующий заказ
   * @throws Exception
   */
  public static function update(int $id, array $data): array
  {
    $result = OrderTable::update($id, $data);

    if (!$result->isSuccess()) {
      throw new \RuntimeException('Ошибка обновления заказа: ' . implode(', ', $result->getErrorMessages()));
    }

    return self::getById($id) ?? throw new \RuntimeException('Ошибка получения данных после обновления');
  }

  /**
   * Получить общее количество заказов для пагинации (с учетом фильтров)
   */
  public static function getTotalCount(array $filter =[]): ?string
  {
    $query = OrderTable::query();
    $query->setSelect(['id']); // ToDo||  test id or ID
    $query->setFilter($filter);

    return (string)$query->queryCountTotal();
  }

  /**
   * Сменить статус заказа с записью в историю
   * @throws Exception
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

    $history[] =[
      'date'    => (new DateTime())->toString(),
      'from'    => $oldStatusCode,
      'to'      => $newStatusCode,
      'comment' => $comment,
    ];

    $updateResult = OrderTable::update($orderId,[
      'status_id'      => $status['id'],
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
   * Удалить заказ
   * @throws Exception
   */
  public static function delete(int $id): bool
  {
    $result = OrderTable::delete($id);
    return $result->isSuccess();
  }

  /**
   * Поиск по номеру Лигрон (number)
   */
  public static function getByNumber(string $number): ?array
  {
    return OrderTable::getList([
      'select' => self::DEFAULT_SELECT,
      'filter' => ['=number' => $number],
      'limit'  => 1
    ])->fetch() ?: null;
  }

}