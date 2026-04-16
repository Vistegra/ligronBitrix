<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Order;

use Bitrix\Main\Type\DateTime;
use Exception;
use OrderApiV2\Constants\OrderAction;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Repositories\OrderFileRepository;
use OrderApiV2\DB\Repositories\OrderRepository;
use OrderApiV2\DB\Repositories\OrderStatusRepository;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\DTO\Order\OrderCreateResult;
use OrderApiV2\Permissions\OrderPermission;
use OrderApiV2\Services\LogService;

/**
 * Центральный менеджер заказов V2.
 */
final readonly class OrderManager
{
  public function __construct(
    private UserDTO              $user,
    private OrderPermission      $permission,
    private OrderFileManager     $fileManager,
    private Integration1CService $integration1c,
  )
  {
  }

  // --------------------------------
  // CRUD операции (с проверкой прав)
  // --------------------------------

  /**
   * Создать новый заказ
   * @throws Exception
   */
  public function createOrder(array $data, array $uploadedFiles = [], bool $isDraft = false): OrderCreateResult
  {
    // Кто конкретно создает заказ
    $data['author_id'] = $this->user->id;
    $data['created_by'] = $this->user->isDealer() ? OrderTable::CREATED_BY_DEALER : OrderTable::CREATED_BY_MANAGER;

    try {
      // Имеет ли этот author_id право
      // создавать заказ на переданные в $data['inn_dealer'] и $data['salon_code']
      $this->permission->verify(OrderAction::CREATE, [], $data);

      $order = OrderRepository::create($data);
    } catch (\Throwable $e) {
      return new OrderCreateResult(success: false, orderError: $e->getMessage());
    }

    $fileResults = !empty($uploadedFiles)
      ? $this->fileManager->uploadFilesToOrder($order, $uploadedFiles)
      : [];

    if (!$isDraft) {
      $order = $this->sendToLigron((int)$order['id']) ?? $order;
    }

    // права для фронтенда
    $order['_permissions'] = $this->permission->getFrontendPermissions($order);

    return new OrderCreateResult(true, $order, $fileResults);
  }

  /**
   * Получить заказ по ID
   * @throws Exception
   */
  public function getOrder(int $id): array
  {
    $order = OrderRepository::getById($id);
    if (!$order) throw new Exception("Заказ №{$id} не найден", 404);

    $this->permission->verify(OrderAction::VIEW, $order);

    $order['files'] = OrderFileRepository::getByOrderId($id) ?: [];

    // права для фронтенда
    $order['_permissions'] = $this->permission->getFrontendPermissions($order);

    return $order;
  }

  /**
   * Получить заказ по номеру Лигрон
   * @throws Exception
   */
  public function getOrderByNumber(string $number): array
  {
    $order = OrderRepository::getByNumber($number);
    if (!$order) throw new Exception("Заказ с номером {$number} не найден", 404);

    $this->permission->verify(OrderAction::VIEW, $order);

    $order['files'] = OrderFileRepository::getByOrderId((int)$order['id']) ?: [];

    // права для фронтенда
    $order['_permissions'] = $this->permission->getFrontendPermissions($order);

    return $order;
  }

  /**
   * Список заказов с ACL-фильтром
   */
  public function getOrders(array $filter, bool $isDraft, int $limit, int $offset, array $sort): array
  {
    $accessFilter = $this->permission->getAccessFilter($isDraft);

    // Если фильтр безопасности пустой (Режим Бога), используем только фильтр пользователя
    if (empty($accessFilter)) {
      $finalFilter = $filter;
    } // Если пользователь не ввел своих фильтров (просто открыл список), используем только безопасность
    elseif (empty($filter)) {
      $finalFilter = $accessFilter;
    } // Если есть и то и другое — объединяем
    else {
      $finalFilter = [
        'LOGIC' => 'AND',
        $filter,
        $accessFilter
      ];
    }

    $orders = OrderRepository::queryList([
      'filter' => $finalFilter,
      'limit' => $limit,
      'offset' => $offset,
      'order' => $sort
    ]);

    // Обогащаем каждый заказ в списке правами для кнопок в таблице
    foreach ($orders as &$order) {
      $order['_permissions'] = $this->permission->getFrontendPermissions($order);
    }
    unset($order);

    return [
      'orders' => $orders,
      'pagination' => [
        'limit' => $limit,
        'offset' => $offset,
        'total' => (int)OrderRepository::getTotalCount($finalFilter)
      ],
    ];
  }

  /**
   * Обновить данные заказа
   * @throws Exception
   */
  public function updateOrder(int $id, array $data): array
  {
    $order = $this->getOrder($id); // Внутри встроен verify(VIEW)
    $this->permission->verify(OrderAction::UPDATE, $order, $data);

    $updatedOrder = OrderRepository::update($id, $data);

    // Возвращаем обновленный заказ вместе с актуальными правами
    $updatedOrder['_permissions'] = $this->permission->getFrontendPermissions($updatedOrder);

    return $updatedOrder;
  }

  /**
   * Удалить заказ и его файлы
   * @throws Exception
   */
  public function deleteOrder(int $id): bool
  {
    $order = $this->getOrder($id); // Внутри встроен verify(VIEW)

    // Проверит и права, и наличие children_count, и статус 1С
    $this->permission->verify(OrderAction::DELETE, $order);

    $this->fileManager->deleteAllOrderFiles($id);
    return OrderRepository::delete($id);
  }

  // ------------------
  // СТАТУСЫ И ИСТОРИЯ
  // ------------------

  /**
   * Сменить статус заказа вручную
   * @throws Exception
   */
  public function changeStatus(int $id, string $newStatusCode, ?string $comment = null): bool
  {
    $order = $this->getOrder($id); // Внутри встроен verify(VIEW)
    $this->permission->verify(OrderAction::CHANGE_STATUS, $order);

    return OrderRepository::changeStatus($id, $newStatusCode, $comment);
  }

  /**
   * Получить список всех доступных в системе статусов
   */
  public function getStatuses(): array
  {
    return OrderStatusRepository::getAll();
  }

  /**
   * Получить данные статуса по умолчанию (Helper)
   */
  private function getDefaultStatusData(): array
  {
    $status = OrderStatusRepository::getDefaultStatus();
    return [
      'status_id' => $status['id'],
      'status_history' => [[
        'id' => $status['id'],
        'code' => $status['code'],
        'date' => (new DateTime())->toString(),
      ]]
    ];
  }

  // ИНТЕГРАЦИЯ С 1С

  /**
   * Отправить заказ в 1С и обновить локальные данные (номер, статус)
   * @throws Exception
   */
  public function sendToLigron(int $orderId): ?array
  {
    $order = $this->getOrder($orderId); // Проверяет VIEW
    $this->permission->verify(OrderAction::SEND_TO_1C, $order);

    $responseData = $this->integration1c->sendOrder($orderId);

    if (!$responseData || empty($responseData['ligron_number'])) {
      LogService::error("1С не вернула номер для заказа #{$orderId}", $responseData ?? []);
      return null;
    }

    // Логика маппинга ответа 1С в БД
    $updateData = ['number' => $responseData['ligron_number']];

    if (!empty($responseData['status_zakaza']['status_code'])) {
      $status = OrderStatusRepository::findByCode(trim($responseData['status_zakaza']['status_code']));
      if ($status) {
        $updateData['status_id'] = $status['id'];
        $updateData['status_history'] = [[
          'id' => $status['id'],
          'code' => $status['code'],
          'date' => $responseData['status_zakaza']['status_date'] ?? (new DateTime())->toString(),
        ]];
      }
    }

    // Если 1С не прислала статус, ставим дефолтный
    if (!isset($updateData['status_id'])) {
      $default = $this->getDefaultStatusData();
      $updateData['status_id'] = $default['status_id'];
      $updateData['status_history'] = $default['status_history'];
    }

    $updatedOrder = OrderRepository::update($orderId, $updateData);

    // Добавляем права
    $updatedOrder['_permissions'] = $this->permission->getFrontendPermissions($updatedOrder);

    return $updatedOrder;
  }

  /**
   * Получить JSON, для отправки в 1С (для отладки на фронте)
   * @throws Exception
   */
  public function getLigronRequestData(int $orderId): array
  {
    $this->getOrder($orderId); // Внутри встроен verify(VIEW)
    return $this->integration1c->buildRequestData($orderId);
  }

  // Работа с файлами

  /**
   * @throws Exception
   */
  public function getFilesByOrderId(int $orderId): array
  {
    $this->getOrder($orderId);  // Внутри встроен verify(VIEW)
    return OrderFileRepository::getByOrderId($orderId) ?: [];
  }

  /**
   * @throws Exception
   */
  public function deleteFile(int $orderId, int $fileId): bool
  {
    $order = $this->getOrder($orderId); // Внутри встроен verify(VIEW)
    $this->permission->verify(OrderAction::UPDATE, $order);

    $file = OrderFileRepository::getById($fileId);
    if (!$file || (int)$file['order_id'] !== $orderId) {
      throw new Exception("Файл не найден или не принадлежит данному заказу", 404);
    }

    return $this->fileManager->deleteFile($fileId);
  }

}