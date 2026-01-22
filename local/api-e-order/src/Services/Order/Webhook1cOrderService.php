<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use OrderApi\DB\Models\OrderTable;
use OrderApi\DB\Repositories\DealerUserRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;
use OrderApi\Services\LogService;

final readonly class Webhook1cOrderService
{
  /**
   * Обновляет заказ на основе данных из 1С.
   * Принимает сырой массив, валидирует и обновляет статус/поля.
   *
   * @param array $data
   * @return array
   * @throws \RuntimeException
   * @throws \Exception
   */
  public function updateOrderFrom1C(array $data): array
  {
    $ligronNumber = (string)($data['ligron_number'] ?? '');
    if (empty($ligronNumber)) {
      throw new \RuntimeException('Не передан номер заказа (ligron_number)!', 400);
    }

    $statusCode = (string)($data['status_code'] ?? '');
    if (empty($statusCode)) {
      throw new \RuntimeException('Не передан код статуса (status_code)!', 400);
    }

    // 1. Поиск заказа
    $order = OrderRepository::getByNumber($ligronNumber);
    if (!$order) {
      throw new \RuntimeException("Заказ с номером {$ligronNumber} не найден в системе!");
    }

    // 2. Поиск статуса
    $status = $this->findStatusByCodeOrThrow($statusCode);

    // 3. Подготовка полей к обновлению
    $fieldsToUpdate = [];

    // Логика смены статуса
    if ((int)$order['status_id'] !== (int)$status['id']) {
      $fieldsToUpdate['status_id'] = (int)$status['id'];

      // Дата статуса или текущая
      $statusDate = !empty($data['status_date']) ? (string)$data['status_date'] : date('d.m.Y H:i:s');

      $newHistoryItem = $this->createHistoryItem((int)$status['id'], $status['code'], $statusDate);

      $fieldsToUpdate['status_history'] = array_merge(
        [$newHistoryItem],
        $order['status_history'] ?? []
      );
    }

    // Дополнительные поля
    if (!empty($data['name'])) {
      $newName = trim((string)$data['name']);
      $fieldsToUpdate['name'] = $newName;
    }

    if (!empty($data['production_date'])) {
      $fieldsToUpdate['ready_date'] = $this->parseDate($data['production_date']);
    }

    if (isset($data['production_time'])) {
      $fieldsToUpdate['production_time'] = (int)$data['production_time'];
    }

    if (isset($data['percent_payment'])) {
      $fieldsToUpdate['percent_payment'] = (int)$data['percent_payment'];
    }

    // Проверка на наличие изменений
    if (empty($fieldsToUpdate)) {
      throw new \RuntimeException(
        "Для заказа №{$ligronNumber} статус '{$statusCode}' уже установлен, и нет дополнительных данных для обновления."
      );
    }

    return OrderRepository::update((int)$order['id'], $fieldsToUpdate);
  }

  /**
   * Создает заказ на основе данных из 1С.
   * @throws \RuntimeException
   */
  public function createOrderFrom1C(array $data): array
  {
    $ligronNumber = (string)($data['ligron_number'] ?? '');

    if (empty($ligronNumber)) {
      throw new \RuntimeException('Не передан обязательный параметр ligron_number', 400);
    }

    $existingOrder = OrderRepository::getByNumber($ligronNumber);
    if ($existingOrder) {
      throw new \RuntimeException("Заказ с номером {$ligronNumber} уже существует.", 409);
    }

    // Маппинг и Валидация данных
    $fields = $this->map1CDataToInternal($data);

    $fields['number'] = $ligronNumber;

    LogService::info("1C Create: Заказ {$ligronNumber}", $fields, 'webhook_1c');

    return OrderRepository::create($fields);
  }

  /**
   * Преобразование внешних данных (ИНН, Код салона) во внутренние ID и валидация.
   */
  private function map1CDataToInternal(array $data): array
  {
    $requiredFields = ['client', 'salon', 'name', 'origin_type', 'status_code', 'status_date', 'date'];
    foreach ($requiredFields as $field) {
      if (empty($data[$field])) {
        throw new \RuntimeException("Не передан обязательный параметр: {$field}", 400);
      }
    }

    $originType = (int)$data['origin_type'];
    if (!in_array($originType, [OrderTable::ORIGIN_TYPE_1C, OrderTable::ORIGIN_TYPE_CALC], true)) {
      throw new \RuntimeException("Недопустимый origin_type: {$originType}. Ожидалось: 1 (1C) или 2 (Calc)", 400);
    }

    $dealerData = $this->resolveDealerAndUser(
      (string)$data['client'],
      (string)$data['salon']
    );

    $status = $this->findStatusByCodeOrThrow((string)$data['status_code']);

    // Формируем начальную историю статусов
    $historyItem = $this->createHistoryItem(
      (int)$status['id'],
      $status['code'],
      (string)$data['status_date']
    );

    $readyDate = $this->parseDate($data['production_date'] ?? null);

    return [
      'name' => (string)$data['name'],
      'comment' => (string)($data['comment'] ?? ''),

      // Привязки
      'dealer_prefix' => $dealerData['prefix'],
      'dealer_user_id' => $dealerData['user_id'],
      'manager_id' => isset($data['manager_id']) ? (int)$data['manager_id'] : null,

      // Статус
      'status_id' => (int)$status['id'],
      'status_history' => [$historyItem],

      // Числовые поля
      'production_time' => (int)($data['production_time'] ?? 0),
      'percent_payment' => (int)($data['percent_payment'] ?? 0),
      'ready_date' => $readyDate,

      // Место создания
      'origin_type' => $originType,

      // Технические поля
      'created_by' => 0,
      'created_by_id' => 0,
    ];
  }

  // Хелперы

  private function resolveDealerAndUser(string $inn, string $salonCode): array
  {
    $dealerInfo = DealerUserRepository::getDealerByInn($inn);

    if (!$dealerInfo) {
      LogService::error("1C Webhook: Дилер не найден", ['inn' => $inn], 'webhook_1c');
      throw new \RuntimeException("Дилер с ИНН {$inn} не найден в системе.");
    }

    $dealerPrefix = $dealerInfo['prefix'];
    $dealerId = (int)$dealerInfo['id'];

    $dealerUserId = DealerUserRepository::findUserIdBySalonCode($dealerId, $dealerPrefix, $salonCode);

    if (!$dealerUserId) {
      LogService::error(
        "1C Webhook: Пользователь не найден по коду салона",
        ['dealer_id' => $dealerId, 'salon_code' => $salonCode],
        'webhook_1c'
      );
      throw new \RuntimeException("1C Webhook: Пользователь не найден по коду салона $salonCode");
    }

    return [
      'prefix' => $dealerPrefix,
      'user_id' => $dealerUserId
    ];
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  private function findStatusByCodeOrThrow(string $code): array
  {
    $status = OrderStatusRepository::findByCode($code);
    if (!$status) {
      throw new \RuntimeException("Статус с кодом '{$code}' не найден в системе!");
    }
    return $status;
  }

  private function createHistoryItem(int $id, string $code, string $date): array
  {
    return [
      'id' => $id,
      'code' => $code,
      'date' => $date,
    ];
  }

  private function parseDate(?string $dateString): ?Date
  {
    if (empty($dateString)) {
      return null;
    }
    try {
      return new Date($dateString, 'd.m.Y');
    } catch (\Throwable) {
      return null;
    }
  }
}