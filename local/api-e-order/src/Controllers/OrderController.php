<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\Services\Order\OrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class OrderController extends AbstractController
{
  public function __construct(
    private readonly OrderService $orderService
  ) {}

  // GET /orders
  public function list(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getQueryParams();
    $filter = $data['filter'] ?? [];
    $limit = (int)($data['limit'] ?? 20);
    $offset = (int)($data['offset'] ?? 0);

    try {
      $orders = $this->orderService->listOrders($filter, $limit, $offset);
      return $this->json(['status' => 'success', 'message' => 'Orders list', 'data' => $orders]);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // GET /orders/{id}
  public function get(int $id): ResponseInterface
  {
    try {
      $order = $this->orderService->getOrder($id);
      return $order
        ? $this->success('Order details', $order)
        : $this->error('Order not found', 404);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // POST /orders
  public function create(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];

    if (empty($data['name'])) {
      return $this->error('Name is required', 400);
    }

    try {
      $orderId = $this->orderService->createOrder($data);
      if (!$orderId) {
        return $this->error('Failed to create order', 500);
      }

      $order = $this->orderService->getOrder($orderId);
      return $this->success('Order created', $order, 201);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // PUT /orders/{id}
  public function update(int $id, ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];

    try {
      if (!$this->orderService->updateOrder($id, $data)) {
        return $this->error('Failed to update order', 500);
      }

      $order = $this->orderService->getOrder($id);
      return $this->success('Order updated', $order);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // DELETE /orders/{id}
  public function delete(int $id): ResponseInterface
  {
    try {
      if (!$this->orderService->deleteOrder($id)) {
        return $this->error('Failed to delete order', 500);
      }
      return $this->success('Order deleted',[],204);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // POST /orders/{id}/status
  public function changeStatus(int $id, ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];
    $status = $data['status'] ?? '';
    $comment = $data['comment'] ?? null;

    if (!$status) {
      return $this->error('Status is required', 400);
    }

    try {
      if (!$this->orderService->changeStatus($id, $status, $comment)) {
        return $this->error('Failed to change status', 500);
      }

      $order = $this->orderService->getOrder($id);
      return $this->success('Status changed', $order);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // POST /orders/{id}/files
  public function uploadFiles(int $id, ServerRequestInterface $request): ResponseInterface
  {
    $files = $request->getUploadedFiles();
    $uploadedFiles = $files['file'] ?? [];

    // Поддержка: file (один) или file[] (массив)
    if (empty($uploadedFiles)) {
      return $this->error('No files uploaded', 400);
    }

    // Приводим к массиву (если один файл — делаем массив)
    if (!is_array($uploadedFiles)) {
      $uploadedFiles = [$uploadedFiles];
    }

    $uploadedIds = [];
    $errors = [];

    foreach ($uploadedFiles as $file) {
      if ($file->getError() !== UPLOAD_ERR_OK) {
        $errors[] = [
          'name' => $file->getClientFilename(),
          'error' => 'Upload error: ' . $file->getError()
        ];
        continue;
      }

      try {
        $fileId = $this->orderService->uploadFile($id, $file);
        if ($fileId) {
          $uploadedIds[] = $fileId;
        } else {
          $errors[] = [
            'name' => $file->getClientFilename(),
            'error' => 'Failed to save'
          ];
        }
      } catch (\Exception $e) {
        $errors[] = [
          'name' => $file->getClientFilename(),
          'error' => $e->getMessage()
        ];
      }
    }

    // Успех: все файлы загружены
    if (empty($errors)) {
      return $this->success('Files uploaded', ['file_ids' => $uploadedIds], 201);
    }

    // Частичный успех
    if (!empty($uploadedIds)) {
      return $this->json([
        'status' => 'partial',
        'message' => 'Some files uploaded, some failed',
        'data' => ['uploaded' => $uploadedIds],
        'errors' => $errors
      ], 207); // 207 Multi-Status
    }

    // Все провалились
    return $this->json([
      'status' => 'error',
      'message' => 'All files failed to upload',
      'errors' => $errors
    ], 400);
  }

  // DELETE /orders/{id}/files/{fileId}
  public function deleteFile(int $id, int $fileId): ResponseInterface
  {
    try {
      if (!$this->orderService->deleteFile($fileId)) {
        return $this->error('Failed to delete file',500);
      }
      return $this->success('File deleted', [], 204);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // GET /statuses
  public function getStatuses(): ResponseInterface
  {
    try {
      $statuses = $this->orderService->getStatuses();
      return $this->success('Order statuses', $statuses);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

}