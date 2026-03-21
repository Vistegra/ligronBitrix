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
use OrderApiV2\Services\Auth\Session\AuthSession;
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
  ) {}


  // CRUD операции (с проверкой прав)

  /**
   * Создать новый заказ
   * @throws Exception
   */
  public function createOrder(array $data, array $uploadedFiles = [], bool $isDraft = false): OrderCreateResult
  {
    $data['created_by_id'] = $this->user->id;

    if ($this->user->isDealer()) {
      $data['created_by']      = OrderTable::CREATED_BY_DEALER;
      $data['inn_dealer']      = AuthSession::getInn();
      $data['salon_code']      = $this->user->salon_code;
      $data['dealer_username'] = $this->user->login;

      $data['dealer_user_id'] = $this->user->id;
    } else {
      $data['created_by'] = OrderTable::CREATED_BY_MANAGER;
      // Менеджеры Лигрон должны присылать inn_dealer и salon_code в $data
    }

    try {
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

    return new OrderCreateResult(true, $order, $fileResults);
  }

  /**
   * Получить заказ по ID
   */
  public function getOrder(int $id): array
  {
    $order = OrderRepository::getById($id);
    if (!$order) throw new Exception("Заказ #{$id} не найден", 404);

    $this->permission->verify('view', $order); //ToDo|| создать класс констанд для permission 'view', 'update', и т.д
    $order['files'] = OrderFileRepository::getByOrderId($id) ?: [];

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

    $this->permission->verify('view', $order);
    $order['files'] = OrderFileRepository::getByOrderId((int)$order['id']) ?: [];

    return $order;
  }

  /**
   * Список заказов с ACL-фильтром
   */
  public function getOrders(array $filter = [], int $limit = 20, int $offset = 0, array $sort =['updated_at' => 'desc']): array
  {
    $accessFilter = $this->permission->getAccessFilter();

    // Защита от затирания фильтров URL фильтрами прав доступа
    $finalFilter =[];
    if (!empty($filter) && !empty($accessFilter)) {
      $finalFilter =[
        'LOGIC' => 'AND',
        $filter,
        $accessFilter
      ];
    } elseif (!empty($filter)) {
      $finalFilter = $filter;
    } elseif (!empty($accessFilter)) {
      $finalFilter = $accessFilter;
    }

    return [
      'orders'     => OrderRepository::queryList([
        'filter' => $finalFilter,
        'limit' => $limit,
        'offset' => $offset,
        'order' => $sort
      ]),
      'pagination' =>[
        'limit'  => $limit,
        'offset' => $offset,
        'total'  => OrderRepository::getTotalCount($finalFilter)
      ],
    ];
  }

  /**
   * Обновить данные заказа
   * @throws Exception
   */
  public function updateOrder(int $id, array $data): array
  {
    $order = $this->getOrder($id); // Внутри встроен verify('view')
    $this->permission->verify('update', $order, $data);

    return OrderRepository::update($id, $data);
  }

  /**
   * Удалить заказ и его файлы
   * @throws Exception
   */
  public function deleteOrder(int $id): bool
  {
    $order = $this->getOrder($id);
    $this->permission->verify('delete', $order);

    if ((int)($order['children_count'] ?? 0) > 0) {
      throw new \RuntimeException('Нельзя удалить заказ, у которого есть дочерние заказы');
    }

    $this->fileManager->deleteAllOrderFiles($id);
    return OrderRepository::delete($id);
  }

  // СТАТУСЫ И ИСТОРИЯ

  /**
   * Сменить статус заказа вручную
   * @throws Exception
   */
  public function changeStatus(int $id, string $newStatusCode, ?string $comment = null): bool
  {
    $order = $this->getOrder($id);
    $this->permission->verify('change_status', $order);

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
        'id'   => $status['id'],
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
    // Только тот, кто может видеть заказ, может его отправить
    $this->getOrder($orderId);

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
          'id'   => $status['id'],
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

    return OrderRepository::update($orderId, $updateData);
  }

  /**
   * Получить JSON, для отправки в 1С (для отладки на фронте)
   * @throws Exception
   */
  public function getLigronRequestData(int $orderId): array
  {
    $this->getOrder($orderId); // Внутри встроен verify('view')
    return $this->integration1c->buildRequestData($orderId);
  }

  // Работа с файлами
  /**
   * @throws Exception
   */
  public function getFilesByOrderId(int $orderId): array
  {
    $this->getOrder($orderId);  // Внутри встроен verify('view')
    return OrderFileRepository::getByOrderId($orderId) ?: [];
  }

  /**
   * @throws Exception
   */
  public function deleteFile(int $orderId, int $fileId): bool
  {
    $order = $this->getOrder($orderId); // Проверка доступа к заказу (VIEW)
    $this->permission->verify(OrderAction::UPDATE, $order);

    $file = OrderFileRepository::getById($fileId);
    if (!$file || (int)$file['order_id'] !== $orderId) {
      throw new Exception("Файл не найден или не принадлежит данному заказу", 404);
    }

    return $this->fileManager->deleteFile($fileId);
  }

}