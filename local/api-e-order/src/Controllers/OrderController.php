<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\Services\Order\OrderService;

class OrderController extends AbstractController
{
  private OrderService $service;

  public function __construct()
  {
    try {
      $this->service = OrderService::fromHeader();
    } catch (\Exception $e) {
      $this->sendError($e->getMessage(), $e->getCode() ?: 401);
    }
  }

  // GET /orders
  public function list(): void
  {
    $data = $this->getRequestData();
    $filter = $data['filter'] ?? [];
    $limit = (int)($data['limit'] ?? 20);
    $offset = (int)($data['offset'] ?? 0);

    try {
      $orders = $this->service->listOrders($filter, $limit, $offset);
      $this->sendResponse('Orders list', $orders);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // GET /orders/{id}
  public function get(int $id): void
  {
    try {
      $order = $this->service->getOrder($id);
      if (!$order) {
        $this->sendError('Order not found', 404);
      }
      $this->sendResponse('Order details', $order);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // POST /orders
  public function create(): void
  {
    $data = $this->getRequestData();

    if (empty($data['name'])) {
      $this->sendError('Name is required', 400);
    }

    try {
      $id = $this->service->createOrder($data);
      if (!$id) {
        $this->sendError('Failed to create order', 500);
      }
      $order = $this->service->getOrder($id);
      $this->sendResponse('Order created', $order, 201);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // PUT /orders/{id}
  public function update(int $id): void
  {
    $data = $this->getRequestData();

    try {
      if (!$this->service->updateOrder($id, $data)) {
        $this->sendError('Failed to update order', 500);
      }
      $order = $this->service->getOrder($id);
      $this->sendResponse('Order updated', $order);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // DELETE /orders/{id}
  public function delete(int $id): void
  {
    try {
      if (!$this->service->deleteOrder($id)) {
        $this->sendError('Failed to delete order', 500);
      }
      $this->sendResponse('Order deleted');
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // POST /orders/{id}/status
  public function changeStatus(int $id): void
  {
    $data = $this->getRequestData();
    $newStatusCode = $data['status'] ?? '';
    $comment = $data['comment'] ?? null;

    if (empty($newStatusCode)) {
      $this->sendError('Status is required', 400);
    }

    try {
      if (!$this->service->changeStatus($id, $newStatusCode, $comment)) {
        $this->sendError('Failed to change status', 500);
      }
      $order = $this->service->getOrder($id);
      $this->sendResponse('Status changed', $order);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // POST /orders/{id}/files
  public function uploadFile(int $id): void
  {
    if (empty($_FILES['file'])) {
      $this->sendError('No file uploaded', 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
      $this->sendError('File upload error', 400);
    }

    // Assume upload directory and logic
    $uploadDir = '/path/to/uploads/' . $id . '/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }
    $path = $uploadDir . basename($file['name']);
    if (!move_uploaded_file($file['tmp_name'], $path)) {
      $this->sendError('Failed to save file', 500);
    }

    try {
      $fileId = $this->service->addFile($id, $file['name'], $path, $file['size'], $file['type']);
      if (!$fileId) {
        $this->sendError('Failed to add file record', 500);
      }
      $this->sendResponse('File uploaded', ['file_id' => $fileId], 201);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // DELETE /orders/{id}/files/{fileId}
  public function deleteFile(int $id, int $fileId): void
  {
    try {
      $file = OrderFileRepository::getById($fileId);
      if ($file && $file['order_id'] == $id && file_exists($file['path'])) {
        unlink($file['path']);
      }
      if (!$this->service->deleteFile($fileId)) {
        $this->sendError('Failed to delete file', 500);
      }
      $this->sendResponse('File deleted');
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }

  // GET /statuses
  public function getStatuses(): void
  {
    try {
      $statuses = $this->service->getStatuses();
      $this->sendResponse('Order statuses', $statuses);
    } catch (\Exception $e) {
      $this->handleException($e);
    }
  }
}