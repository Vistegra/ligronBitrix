<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Order;

use Exception;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Repositories\FileDiskRepository;
use OrderApiV2\DB\Repositories\OrderFileRepository;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\DTO\Order\FileUploadResult;
use Psr\Http\Message\UploadedFileInterface;

final readonly class OrderFileManager
{
  public function __construct(
    private UserDTO $user
  ) {}

  /**
   * Загрузить файлы в папку заказа
   */
  public function uploadFilesToOrder(array $order, array $files): array
  {
    $results = [];

    // /upload/e-order/files/{order_id}/
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . ApiConfig::UPLOAD_FILES_DIR . "{$order['id']}/";

    $uploadedBy = $this->user->isDealer()
      ? OrderTable::CREATED_BY_DEALER
      : OrderTable::CREATED_BY_MANAGER;

    foreach ($files as $file) {
      $diskResult = FileDiskRepository::upload($file, $uploadDir);

      if (!$diskResult->isSuccess()) {
        $results[] = $diskResult;
        continue;
      }

      try {
        $fileData = $diskResult->file;
        $savedFileData = OrderFileRepository::add(
          orderId:      (int)$order['id'],
          name:         $fileData['name'],
          path:         $fileData['path'],
          size:         $fileData['size'],
          mime:         $fileData['mime'],
          uploadedBy:   $uploadedBy,
          uploadedById: $this->user->id
        );

        $results[] = new FileUploadResult(file: $savedFileData);
      } catch (\Throwable $e) {
        // Если база отвалилась, пытаемся подчистить файл, чтобы не мусорить
        @unlink($uploadDir . $diskResult->file['name']);

        $results[] = new FileUploadResult(
          originalName: $file->getClientFilename(),
          error: "Ошибка записи в БД: " . $e->getMessage()
        );
      }
    }

    return $results;
  }

  /**
   * Удалить файл
   * @throws Exception
   */
  public function deleteFile(int $fileId): bool
  {
    $file = OrderFileRepository::getById($fileId);
    if (!$file) return false;

    // Берем путь из БД ($file['path'])
    FileDiskRepository::deleteFile($file['path'], $file['name']);

    return OrderFileRepository::delete($fileId);
  }

  public function deleteAllOrderFiles(int $orderId): void
  {
    $files = OrderFileRepository::getByOrderId($orderId);
    if ($files) {
      FileDiskRepository::deleteFiles($files);
    }
  }
}