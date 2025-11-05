<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use OrderApi\DB\Models\OrderTable;
use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DB\Repositories\OrderStatusRepository;
use OrderApi\Services\Auth\AuthService;

class OrderService
{
  private array $user;

  public function __construct(array $user)
  {
    $this->user = $user;
  }

  public static function fromHeader(): self
  {
    $payload = AuthService::validateFromHeader();
    if (!$payload) {
      throw new \Exception('Unauthorized', 401);
    }
    return new self($payload);
  }

  private function isDealer(): bool
  {
    return $this->user['provider'] === 'dealer';
  }

  private function isManager(): bool
  {
    return $this->user['provider'] === 'ligron' && $this->user['role'] === 'manager';
  }

  private function isOfficeManager(): bool
  {
    return $this->user['provider'] === 'ligron' && $this->user['role'] === 'office_manager';
  }

  public function createOrder(array $data): ?int
  {
    $data['created_by_id'] = $this->user['sub'] ?? $this->user['id'];

    if ($this->isDealer()) {
      $data['created_by'] = OrderTable::CREATED_BY_DEALER;
      $data['dealer_prefix'] = $this->user['dealer_prefix'];
      $data['dealer_user_id'] = $this->user['sub'];
    } elseif ($this->isManager() || $this->isOfficeManager()) {
      $data['created_by'] = OrderTable::CREATED_BY_MANAGER;
      $data['manager_id'] = $this->user['sub'];
    } else {
      throw new \Exception('Invalid user role');
    }

    return OrderRepository::create($data);
  }

  public function getOrder(int $id): ?array
  {
    $order = OrderRepository::getById($id);
    if (!$order) {
      return null;
    }

    if ($this->isDealer() && ($order['dealer_prefix'] !== $this->user['dealer_prefix'] || $order['dealer_user_id'] !== $this->user['sub'])) {
      throw new \Exception('Access denied', 403);
    } elseif ($this->isManager() || $this->isOfficeManager()) {
      // Менеджеры могут просматривать все или, при необходимости, фильтровать
    } else {
      throw new \Exception('Access denied', 403);
    }

    return $order;
  }

  public function updateOrder(int $id, array $data): bool
  {
    $order = $this->getOrder($id); // Проверяет доступ
    if (!$order) {
      return false;
    }

    return OrderRepository::update($id, $data);
  }

  public function deleteOrder(int $id): bool
  {
    $order = $this->getOrder($id); // Проверяет доступ
    if (!$order) {
      return false;
    }

    return OrderRepository::delete($id);
  }

  public function changeStatus(int $id, string $newStatusCode, ?string $comment = null): bool
  {
    $order = $this->getOrder($id); // Проверяет доступ
    if (!$order) {
      return false;
    }

    // При необходимости проводятся дополнительные проверки ролей, например, только менеджеры могут переходить на определенные статусы
    return OrderRepository::changeStatus($id, $newStatusCode, $comment);
  }

  public function listOrders(array $filter = [], int $limit = 20, int $offset = 0): array
  {
    if ($this->isDealer()) {
      return OrderRepository::getByDealer($this->user['dealer_prefix'], $this->user['sub'], $limit, $offset);
    } elseif ($this->isManager() || $this->isOfficeManager()) {
      return OrderRepository::getByManager($this->user['sub'], $limit, $offset);
    } else {
      throw new \Exception('Access denied', 403);
    }
  }

  public function addFile(int $orderId, string $name, string $path, ?int $size = null, ?string $mime = null): ?int
  {
    $order = $this->getOrder($orderId); // Проверяет доступ
    if (!$order) {
      return null;
    }

    $uploadedBy = $this->isDealer() ? 1 : 2;
    $uploadedById = $this->user['sub'];

    return OrderFileRepository::add($orderId, $name, $path, $size, $mime, (int)$uploadedBy, $uploadedById);
  }

  public function deleteFile(int $fileId): bool
  {
    $file = OrderFileRepository::getById($fileId);
    if (!$file) {
      return false;
    }

    $order = $this->getOrder($file['order_id']); // Проверяет доступ
    if (!$order) {
      return false;
    }

    return OrderFileRepository::delete($fileId);
  }

  public function getStatuses(): array
  {
    return OrderStatusRepository::getAll();
  }
}