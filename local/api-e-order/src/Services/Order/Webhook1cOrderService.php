<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;

final readonly class Webhook1cOrderService
{
  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   * @throws \Exception
   */
  function updateStatusByNumber(string $orderNumber, string $statusCode, string $statusDate): ?array
  {
    $order = OrderRepository::getByNumber($orderNumber);

    if (!$order) {
      throw new \RuntimeException("Заказ с номером $orderNumber не найден в системе!");
    }

    $status = OrderStatusRepository::findByCode($statusCode);

    if (!$status) {
      throw new \RuntimeException("Статус с кодом $statusCode не найден в системе!");
    }

    if ($status['id'] === $order['status_id']) {
      throw new \RuntimeException("Статус с кодом $statusCode уже установлен для заказа №$orderNumber!");
    }

    $orderId = $order['id'];
    $currentHistory = $order['status_history'] ?? [];

    $newHistoryItem = [[
      'id' => $status['id'],
      'code' => $status['code'],
      'date' => $statusDate,
    ]];

    $newHistory = array_merge($newHistoryItem, $currentHistory);

    $updateData = [
      'status_id' => $status['id'],
      'status_history' => $newHistory
    ];

    return OrderRepository::update($orderId, $updateData);
  }

  /*public function updateStatusByNumber(string $orderNumber, string $statusCode, string $statusDate, array $extraData = []): ?array
  {
    $order = OrderRepository::getByNumber($orderNumber);

    if (!$order) {
      throw new \RuntimeException("Заказ с номером $orderNumber не найден в системе!");
    }

    $status = OrderStatusRepository::findByCode($statusCode);

    if (!$status) {
      throw new \RuntimeException("Статус с кодом $statusCode не найден в системе!");
    }

    $updateData = [];

    // 1. Логика смены статуса
    // Если статус отличается от текущего - обновляем его и историю
    if ($status['id'] !== $order['status_id']) {
      $currentHistory = $order['status_history'] ?? [];

      $newHistoryItem = [[
        'id' => $status['id'],
        'code' => $status['code'],
        'date' => $statusDate,
      ]];

      $updateData['status_id'] = $status['id'];
      $updateData['status_history'] = array_merge($newHistoryItem, $currentHistory);
    }
    // Если статус тот же, и нет дополнительных полей - выбрасываем ошибку
    elseif (empty($extraData)) {
      throw new \RuntimeException("Статус с кодом $statusCode уже установлен для заказа №$orderNumber, и нет данных для обновления.");
    }

    // 2. Обработка дополнительных полей
    if (!empty($extraData)) {

    }

    if (empty($updateData)) {
      return $order;
    }

    return OrderRepository::update($order['id'], $updateData);
  }*/
}