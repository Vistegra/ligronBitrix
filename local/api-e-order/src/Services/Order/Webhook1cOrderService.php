<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;

final readonly class Webhook1cOrderService
{
  /**
   * Обновляет статус заказа и дополнительные поля по номеру заказа.
   *
   * @param string $orderNumber Номер заказа из 1С.
   * @param string $statusCode Код нового статуса.
   * @param string $statusDate Дата установки статуса.
   * @param array $extraData Массив дополнительных полей для обновления (ключ => значение).
   *
   * @return array|null Обновленный массив заказа или null.
   * @throws \RuntimeException Если заказ или статус не найдены, или нет данных для обновления.
   * @throws \Exception Произошла ошибка обновления в БД
   */
  public function updateStatusByNumber(
    string $orderNumber,
    string $statusCode,
    string $statusDate,
    array $extraData = []
  ): ?array {

    $order = OrderRepository::getByNumber($orderNumber);
    if (!$order) {
      throw new \RuntimeException("Заказ с номером {$orderNumber} не найден в системе!");
    }

    $status = OrderStatusRepository::findByCode($statusCode);
    if (!$status) {
      throw new \RuntimeException("Статус с кодом {$statusCode} не найден в системе!");
    }

    $fieldsToUpdate = [];

    // Проверяем, изменился ли статус
    if ((int)$order['status_id'] !== (int)$status['id']) {
      $fieldsToUpdate['status_id'] = $status['id'];
      $fieldsToUpdate['status_history'] = array_merge(
        [ //новый статус добавляется в начало
          [
            'id'   => $status['id'],
            'code' => $statusCode,
            'date' => $statusDate,
          ]
        ],
        $order['status_history'] ?? []
      );
    }

    // Добавляем дополнительные данные (если есть)
    if (!empty($extraData)) {
      $fieldsToUpdate = array_merge($fieldsToUpdate, $extraData);
    }

    // Финальная проверка: есть ли что обновлять?
    if (empty($fieldsToUpdate)) {
      throw new \RuntimeException(
        "Для заказа №{$orderNumber} статус '{$statusCode}' уже установлен, и нет дополнительных данных для обновления."
      );
    }

    // Сохранение в БД
    return OrderRepository::update((int)$order['id'], $fieldsToUpdate);
  }

}