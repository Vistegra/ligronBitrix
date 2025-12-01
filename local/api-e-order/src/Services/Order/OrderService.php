<?php
declare(strict_types=1);

namespace OrderApi\Services\Order;

use Bitrix\Bizproc\Api\Response\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\Event\Session;
use Bitrix\Sale\Order;
use MongoDB\Driver\Exception\RuntimeException;
use OrderApi\Config\ApiConfig;
use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Models\OrderTable;
use OrderApi\DB\Repositories\FileDiskRepository;
use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\DTO\Order\FileUploadResult;
use OrderApi\DTO\Order\OrderCreateResult;
use OrderApi\Permissions\OrderPermission;
use OrderApi\Services\Auth\Session\AuthSession;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Сервис для работы с заказами
 */
final readonly class OrderService
{
  public function __construct(
    private UserDTO         $user,
    private OrderPermission $permission,
  )
  {
  }

  /**
   * Создать заказ с возможной загрузкой файлов
   *
   * @param array $data Поля заказа (name, comment, etc.)
   * @param array $uploadedFiles Массив UploadedFileInterface (может быть пустым)
   *
   * @return OrderCreateResult Результат создания заказа и загрузки файлов
   * @throws \Exception
   */
  public function createOrder(array $data, array $uploadedFiles = [], bool $isDraft = false): OrderCreateResult
  {
    $data['created_by_id'] = $this->user->id;

    if ($this->user->isDealer()) {
      $data['created_by'] = OrderTable::CREATED_BY_DEALER;
      $data['dealer_prefix'] = $this->user->dealer_prefix;
      $data['dealer_user_id'] = $this->user->id;

    } elseif ($this->user->isManager()) {
      $data['created_by'] = OrderTable::CREATED_BY_MANAGER;
      if (!$data['dealer_prefix'] || !$data['dealer_user_id']) {
        throw new \RuntimeException('Не переданы данные пользователя');
      }

    } elseif ($this->user->isOfficeManager()) {
      throw new \RuntimeException('Функционал не реализован');

    } else {
      return new OrderCreateResult(success: false, orderError: 'Не указана роль пользователя');
    }

    try {
      $order = OrderRepository::create($data);
    } catch (\Throwable $e) {
      return new OrderCreateResult(success: false, orderError: 'Ошибка создания заказа: ' . $e->getMessage());
    }


    $fileResults = !empty($uploadedFiles)
      ? $this->uploadFilesToOrder($order, $uploadedFiles)
      : [];

    if (!$isDraft) {
      $updatedOrder = $this->sendToLigron($order['id']);
      //ToDo отправить в 1С
      //ToDo обработать ошибки
    }

    return new OrderCreateResult(
      success: true,
      order: $updatedOrder ?? $order,
      fileResults: $fileResults
    );
  }

  /**
   * @throws \Exception
   */
  public function sendToLigron(int $orderId): ?array
  {

    $integrationService = new Integration1CService($this->user);
    $ligronNumber = $integrationService->sendOrder($orderId);

    if (!$ligronNumber) {
      return null;
    }
    $statusData = $this->getDefaultStatusData();
    $data['status_id'] = $statusData['status_id'];
    $data['status_history'] = $statusData['status_history'];
    $data['number'] = $ligronNumber;

    return OrderRepository::update($orderId, $data);
  }

  /**
   * Возвращает данные статуса заказа по умолчанию
   *
   * @return array{
   *     status_id: int,
   *     status_history: array
   * }
   *
   * @throws \RuntimeException Если не удалось получить статус по умолчанию
   */
  private function getDefaultStatusData(): array
  {
    $status = OrderStatusRepository::getDefaultStatus();

    return [
      'status_id' => $status['id'],
      'status_history' => [
        [
          'id' => $status['id'],
          'date' => (new DateTime())->toString(),
        ]
      ]
    ];
  }

  /**
   * Получить заказ по ID с проверкой доступа
   *
   * @throws \Exception если доступ запрещён
   */
  public function getOrder(int $id): ?array
  {
    $order = OrderRepository::getById($id);
    if (!$order) {
      return null;
    }

    $this->permission->canView($order);

    return $order;
  }

  /**
   * Обновить заказ
   * @throws \Exception
   */
  public function updateOrder(int $id, array $data): ?array
  {
    // проверка доступа
    $order = $this->getOrder($id);

    $this->permission->canUpdate($order, $data);

    return OrderRepository::update($id, $data);
  }

  /**
   * Удалить заказ
   * @throws \Exception
   */
  public function deleteOrder(int $id): bool
  {
    $order = $this->getOrder($id);

    //настройка доступа в получении заказа
    if ($order['CHILDREN_COUNT'] > 0) {
      //ToDo возможно не нужно, будет триггер
      throw new \RuntimeException('Нельзя удалить заказ с дочерними');
    }

    // Строки в бд по файлам удаляются спомощью тригеров
    // удаляем файлы только физически
    FileDiskRepository::deleteFiles($order['files']);

    return OrderRepository::delete($id);
  }


  /**
   * Сменить статус заказа
   */
  public function changeStatus(int $id, string $newStatusCode, ?string $comment = null): bool
  {
    //ToDo permission
    $this->getOrder($id);
    return OrderRepository::changeStatus($id, $newStatusCode, $comment);
  }

  /**
   * Получить заказы с пагинацией и фильтром
   * @throws \Exception
   */
  public function getOrders(array $filter = [], int $limit = 20, int $offset = 0): array
  {
    // Добавляем условия доступа
    if ($this->user->isDealer()) {
      $filter = array_merge($filter, [
        '=dealer_prefix' => $this->user->dealer_prefix,
        '=dealer_user_id' => $this->user->id,
      ]);
    } elseif ($this->user->isLigronStaff()) {
      $managedDealers = AuthSession::getManagedDealers();
      $dealersPrefix = array_column($managedDealers, 'dealer_prefix');

      $filterPrefix = $filter['=dealer_prefix'] ?? $filter['dealer_prefix'] ?? null;

      if ($filterPrefix) {
        // Проверяем валидность префикса(ов)
        if (is_string($filterPrefix) && !in_array($filterPrefix, $dealersPrefix)) {
          throw new \Exception('Access denied', 403);

        } elseif (is_array($filterPrefix)) {
          $invalidPrefixes = array_diff($filterPrefix, $dealersPrefix);
          if (!empty($invalidPrefixes)) {
            throw new \Exception('Access denied', 403);
          }
        }
      } else {
        // Если фильтр не указан - используем все доступные префиксы
        $filter['=dealer_prefix'] = $dealersPrefix;
      }
    } else {
      throw new \Exception('Access denied', 403);
    }

    $orders = OrderRepository::queryList([
      'filter' => $filter,
      'limit' => $limit,
      'offset' => $offset,
    ]);

    $pagination = [
      'limit' => $limit,
      'offset' => $offset,
      'total' => OrderRepository::getTotalCount($filter)
    ];

    return [
      'orders' => $orders,
      'pagination' => $pagination,
    ];
  }

  public function getFilesByOrderId($orderId): ?array
  {
    return OrderFileRepository::getByOrderId($orderId);
  }

  /**
   * Удалить файл
   * @throws \Exception
   */
  public function deleteFile(int $orderId, int $fileId): bool
  {
    if (!$orderId) {
      throw new \Exception('Не передан id заказа', 404);
    }

    if (!$fileId) {
      throw new \Exception('Не передан id файла', 404);
    }

    // Проверка доступа
    $order = $this->getOrder($orderId);

    if (!$order) {
      throw new \Exception("Не найден заказ id={$orderId}", 404);
    }

    $file = OrderFileRepository::getById($fileId);

    // Удаляем физический файл с диска
    FileDiskRepository::deleteFile($file['path'], $file['name']);

    // Удаляем запись о файле из БД
    return OrderFileRepository::delete($fileId);
  }

  /**
   * Обработать все загруженные файлы
   *
   * @param array $order
   * @param UploadedFileInterface[] $files
   * @return array
   */
  public function uploadFilesToOrder(array $order, array $files): array
  {
    $results = [];
    $uploadDir = $_SERVER['DOCUMENT_ROOT']
      . ApiConfig::UPLOAD_FILES_DIR
      . "{$order['dealer_prefix']}/{$order['dealer_user_id']}/{$order['id']}/";

    $uploadedBy = $this->user->isDealer()
      ? OrderTable::CREATED_BY_DEALER
      : OrderTable::CREATED_BY_MANAGER;

    foreach ($files as $file) {
      // 1. Загружаем на диск — получаем FileUploadResult
      $diskResult = FileDiskRepository::upload($file, $uploadDir);

      // Если ошибка на диске — возвращаем её
      if (!$diskResult->isSuccess()) {
        $results[] = $diskResult;
        continue;
      }

      // 2. Сохраняем в БД
      $fileData = $diskResult->file;

      $savedFileData = OrderFileRepository::add(
        orderId: $order['id'],
        name: $fileData['name'],
        path: $fileData['path'],
        size: $fileData['size'],
        mime: $fileData['mime'],
        uploadedBy: $uploadedBy,
        uploadedById: $this->user->id
      );

      // 3. Возвращаем полные данные из БД
      $results[] = new FileUploadResult(
        file: $savedFileData,  // Полный массив из БД
        originalName: null,
        error: null
      );
    }

    return $results;
  }

  /**
   * Получить все статусы заказов
   */
  public function getStatuses(): array
  {
    return OrderStatusRepository::getAll();
  }
}