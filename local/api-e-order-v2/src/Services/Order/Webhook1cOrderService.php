<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Order;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Exception;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\LigronUserTable;
use OrderApiV2\DB\Models\DealerSalonTable;
use OrderApiV2\DB\Repositories\OrderRepository;
use OrderApiV2\DB\Repositories\OrderStatusRepository;
use OrderApiV2\Services\LogService;
use RuntimeException;

final readonly class Webhook1cOrderService
{
  /**
   * Обновление заказа из 1С
   */
  public function updateOrderFrom1C(array $data): array
  {
    $this->validateRequired($data, ['ligron_number', 'status_code']);

    $ligronNumber = (string)$data['ligron_number'];
    $order = OrderRepository::getByNumber($ligronNumber);

    if (!$order) {
      throw new RuntimeException("Заказ {$ligronNumber} не найден", 404);
    }

    $status = $this->findStatusOrThrow((string)$data['status_code']);
    $fieldsToUpdate = [];

    // Логика смены статуса и истории
    if ((int)$order['status_id'] !== (int)$status['id']) {
      $fieldsToUpdate['status_id'] = (int)$status['id'];
      $statusDate = $data['status_date'] ?? date('d.m.Y H:i:s');

      $fieldsToUpdate['status_history'] = array_merge(
        [['id' => (int)$status['id'], 'code' => $status['code'], 'date' => $statusDate]],
        $order['status_history'] ??[]
      );
    }

    // Маппинг обновляемых полей
    if (isset($data['name'])) $fieldsToUpdate['name'] = trim((string)$data['name']);
    if (isset($data['production_date'])) $fieldsToUpdate['ready_date'] = $this->parseDate($data['production_date']);
    if (isset($data['production_time'])) $fieldsToUpdate['production_time'] = (int)$data['production_time'];
    if (isset($data['percent_payment'])) $fieldsToUpdate['percent_payment'] = (int)$data['percent_payment'];
    if (isset($data['due_payment'])) $fieldsToUpdate['due_payment'] = (float)$data['due_payment'];

    if (empty($fieldsToUpdate)) return $order;

    return OrderRepository::update((int)$order['id'], $fieldsToUpdate);
  }

  /**
   * Создание заказа из 1С
   * @throws Exception
   */
  public function createOrderFrom1C(array $data): array
  {
    $this->validateRequired($data, ['ligron_number', 'client', 'salon', 'name', 'status_code']);

    $ligronNumber = (string)$data['ligron_number'];
    if (OrderRepository::getByNumber($ligronNumber)) {
      throw new RuntimeException("Заказ {$ligronNumber} уже существует", 409);
    }

    // Маппинг внешних данных во внутренние
    $fields = $this->map1CDataToInternal($data);
    $fields['number'] = $ligronNumber;

    LogService::info("1C Webhook Create: {$ligronNumber}", $fields, 'webhook_1c');

    return OrderRepository::create($fields);
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  private function map1CDataToInternal(array $data): array
  {
    $innDealer = (string)$data['client'];
    $salonCode = (string)$data['salon'];

    // Проверяем, что связка Дилер-Салон существует в БД
    $this->verifyDealerSalonLink($innDealer, $salonCode);

    // Определяем, кто является автором заказа, на основе переданного логина
    $authorInfo = $this->resolveAuthorInfo($salonCode, $data['manager_username'] ?? null);

    $status = $this->findStatusOrThrow((string)$data['status_code']);

    return [
      'name'            => (string)$data['name'],
      'comment'         => (string)($data['comment'] ?? ''),
      'inn_dealer'      => $innDealer,
      'salon_code'      => $salonCode,
      'author_id'       => $authorInfo['author_id'],
      'created_by'      => $authorInfo['created_by'],
      'status_id'       => (int)$status['id'],

      'status_history'  => [
        ['id' => (int)$status['id'],
          'code' => $status['code'],
          'date' => $data['status_date'] ?? date('d.m.Y H:i:s')]
      ],

      'origin_type'     => (int)($data['origin_type'] ?? OrderTable::ORIGIN_TYPE_1C),
      'production_time' => (int)($data['production_time'] ?? 0),
      'percent_payment' => (int)($data['percent_payment'] ?? 0),
      'due_payment'     => (float)($data['due_payment'] ?? 0.00),
      'ready_date'      => $this->parseDate($data['production_date'] ?? null),
    ];

  }

  /**
   * Находит информацию об авторе заказа (ID и тип) по логину, присланному из 1С.
   * Если логин не передан или не найден, заказ считается общим (созданным Системой/Менеджером).
   */
  private function resolveAuthorInfo(string $salonCode, ?string $providedUsername): array
  {

    $defaultAuthor =[
      'author_id'  => null,
      'created_by' => OrderTable::CREATED_BY_MANAGER,
    ];

    if (empty($providedUsername)) {
      return $defaultAuthor;
    }

    $username = trim($providedUsername);

    // Ищем среди Дилеров с привязкой к указанному салону
    $dealerUser = DealerUserTable::getList([
      'select' => ['id'],
      'filter' =>[
        '=username'   => $username,
        '=salon_code' => $salonCode,
        '=active'     => 1
      ],
      'limit' => 1
    ])->fetch();

    if ($dealerUser) {
      return[
        'author_id'  => (int)$dealerUser['id'],
        'created_by' => OrderTable::CREATED_BY_DEALER,
      ];
    }

    // Если это не дилер, ищем среди Менеджеров Лигрон
    $ligronUser = LigronUserTable::getList([
      'select' => ['id'],
      'filter' =>[
        '=username' => $username,
        '=active'   => 1
      ],
      'limit' => 1
    ])->fetch();

    if ($ligronUser) {
      return[
        'author_id'  => (int)$ligronUser['id'],
        'created_by' => OrderTable::CREATED_BY_MANAGER,
      ];
    }

    // Если пользователь вообще не найден
    return $defaultAuthor;
  }

  private function verifyDealerSalonLink(string $inn, string $salonCode): void
  {
    $link = DealerSalonTable::getList([
      'filter' =>['=inn_dealer' => $inn, '=salon_code' => $salonCode],
      'limit' => 1
    ])->fetch();

    if (!$link) {
      throw new RuntimeException("Связка ИНН {$inn} и Салона {$salonCode} не найдена", 400);
    }
  }

  private function validateRequired(array $data, array $fields): void
  {
    foreach ($fields as $field) {
      if (empty($data[$field])) {
        throw new RuntimeException("Отсутствует обязательное поле: {$field}", 400);
      }
    }
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  private function findStatusOrThrow(string $code): array
  {
    $status = OrderStatusRepository::findByCode($code);
    if (!$status) throw new RuntimeException("Код статуса '{$code}' не найден", 400);
    return $status;
  }

  private function parseDate(?string $dateStr): ?Date
  {
    if (!$dateStr) return null;
    try {
      return new Date($dateStr, 'd.m.Y');
    } catch (\Throwable $e) {
      return null;
    }
  }

}