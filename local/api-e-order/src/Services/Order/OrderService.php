<?php
declare(strict_types=1);

namespace OrderApi\Services\Order;

use Bitrix\Bizproc\Api\Response\Error;
use Bitrix\Main\Type\DateTime;
use OrderApi\Config\ApiConfig;
use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Models\OrderTable;
use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\DTO\Order\FileUploadResult;
use OrderApi\DTO\Order\OrderCreateResult;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Сервис для работы с заказами
 */
final class OrderService
{
  public function __construct(
    private readonly UserDTO $user
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
   */
  public function createOrder(array $data, array $uploadedFiles = []): OrderCreateResult
  {
    $data['created_by_id'] = $this->user->id;

    // Определяем тип создателя и заполняем связи
    if ($this->user->isDealer()) {
      $data['created_by'] = OrderTable::CREATED_BY_DEALER;
      $data['dealer_prefix'] = $this->user->dealer_prefix;
      $data['dealer_user_id'] = $this->user->id;
    } elseif ($this->user->isManager() || $this->user->isOfficeManager()) { //ToDo кто может создавать заказ за пользователя дилера?
      throw new \Error('Функционал не реализован'); //ToDo
      $data['created_by'] = OrderTable::CREATED_BY_MANAGER;
      $data['manager_id'] = $this->user->id;
      $data['dealer_prefix'] = null; //ToDo get subordinate dealer
      $data['dealer_user_id'] = null; //ToDo get subordinate dealer user
    } else {
      return new OrderCreateResult(
        success: false,
        orderError: 'Не указана роль пользователя'
      );
    }

    $statusData = $this->getDefaultStatusData();
    $data['status_id'] = $statusData['status_id'];
    $data['status_history'] = $statusData['status_history'];

    // Создаём заказ
    try {
      $order = OrderRepository::create($data);

    } catch (\Throwable $e) {
      return new OrderCreateResult(
        success: false,
        orderError: 'Ошибка создания заказа в базе данных: ' . $e->getMessage()
      );
    }

    // Обрабатываем файлы (если есть)
    $fileResults = [];
    if (!empty($uploadedFiles)) {
      $fileResults = $this->uploadFilesToOrder($order, $uploadedFiles);
    }

    return new OrderCreateResult(
      success: true,
      orderId: (int)$order['id'],
      fileResults: $fileResults
    );
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

    if ($this->user->isDealer()) {
      if (
        $order['dealer_prefix'] !== $this->user->dealer_prefix ||
        $order['dealer_user_id'] !== $this->user->id
      ) {
        throw new \Exception('Access denied', 403);
      }
    }
    // Менеджеры видят все заказы
    // ToDo permission

    return $order;
  }

  /**
   * Обновить заказ
   */
  public function updateOrder(int $id, array $data): bool
  {
    $this->getOrder($id); // проверка доступа
    return OrderRepository::update($id, $data);
  }

  /**
   * Удалить заказ
   */
  public function deleteOrder(int $id): bool
  {
    //ToDo permission
    $this->getOrder($id);
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
      $filter['=manager_id'] = $this->user->id;
    } else {
      throw new \Exception('Access denied', 403);
    }

    return OrderRepository::queryList([
      'filter' => $filter,
      'limit' => $limit,
      'offset' => $offset,
    ]);
  }

  /**
   * Добавить файл к заказу (отдельный вызов)
   */
  public function addFile(
    int     $orderId,
    string  $name,
    string  $path,
    ?int    $size = null,
    ?string $mime = null
  ): ?int
  {
    $this->getOrder($orderId);
    $uploadedBy = $this->user->isDealer() ? OrderTable::CREATED_BY_DEALER : OrderTable::CREATED_BY_MANAGER;
    return OrderFileRepository::add(
      $orderId,
      $name,
      $path,
      $size,
      $mime,
      $uploadedBy,
      $this->user->id
    );
  }

  /**
   * Удалить файл
   */
  public function deleteFile(int $fileId): bool
  {
    $file = OrderFileRepository::getById($fileId);
    if (!$file) {
      return false;
    }

    $this->getOrder($file['order_id']);
    return OrderFileRepository::delete($fileId);
  }

  /**
   * Загрузить один файл (вызывается из uploadFilesToOrder)
   *
   * @throws \Exception при ошибке
   */
  private function uploadFileToOrder(array $order, UploadedFileInterface $file): ?int
  {
    $dealerUserId = $order['dealer_user_id'] ?? 'unknown';
    $dealerPrefix = $order['dealer_prefix'] ?? 'shared';
    $orderId = $order['id'];

    $relativeUploadDir = ApiConfig::UPLOAD_FILES_DIR . "$dealerPrefix/$dealerUserId/$orderId/";
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . $relativeUploadDir;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
      throw new \Exception("Не удалось создать директорию: {$uploadDir}");
    }

    $filename = $this->sanitizeFilename($file->getClientFilename(), $uploadDir);
    $path = $uploadDir . $filename;

    try {
      $file->moveTo($path);
    } catch (\Throwable $e) {
      throw new \Exception("Не удалось переместить файл: " . $e->getMessage());
    }

    $uploadedBy = $this->user->isDealer() ? OrderTable::CREATED_BY_DEALER : OrderTable::CREATED_BY_MANAGER;

    return OrderFileRepository::add(
      orderId: $orderId,
      name: $filename,
      path: $relativeUploadDir,
      size: $file->getSize(),
      mime: $file->getClientMediaType(),
      uploadedBy: $uploadedBy,
      uploadedById: $this->user->id
    );
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

    foreach ($files as $index => $file) {
      $originalName = $file->getClientFilename() ?: 'unknown_file_' . $index;

      // PHP-ошибки загрузки
      if ($file->getError() !== UPLOAD_ERR_OK) {
        $results[] = new FileUploadResult(
          fileId: null,
          originalName: $originalName,
          error: 'Ошибка загрузки файла ' . $originalName . ', код ошибки: ' . $file->getError()
        );
        continue;
      }

      // Попытка загрузки
      try {
        $fileId = $this->uploadFileToOrder($order, $file);
        $results[] = new FileUploadResult(
          fileId: $fileId,
          originalName: $originalName
        );
      } catch (\Throwable $e) {
        $results[] = new FileUploadResult(
          fileId: null,
          originalName: $originalName,
          error: "Ошибка загрузки файла: " . $e->getMessage()
        );
      }
    }

    return $results;
  }


  /**
   * Санитайз имени файла + избежание коллизий
   */
  private function sanitizeFilename(string $original, string $dir): string
  {
    $original = basename($original);
    $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);

    $info = pathinfo($sanitized);
    $base = $info['filename'];
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    $counter = 1;
    $candidate = $sanitized;

    while (file_exists($dir . $candidate)) {
      $candidate = "{$base}_{$counter}{$ext}";
      $counter++;
    }

    return $candidate;
  }

  /**
   * Получить все статусы заказов
   */
  public function getStatuses(): array
  {
    return OrderStatusRepository::getAll();
  }
}