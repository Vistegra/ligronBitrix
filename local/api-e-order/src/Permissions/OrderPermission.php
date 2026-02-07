<?php

declare(strict_types=1);

namespace OrderApi\Permissions;

use OrderApi\DTO\Auth\UserDTO;
use OrderApi\Services\Auth\Session\AuthSession;

final readonly class OrderPermission
{
  /**
   * Константы с наборами полей для каждой роли
   */
  public const array ALLOWED_FIELDS_DEALER = [
    'name',
    'comment'
  ];

  public const  array ALLOWED_FIELDS_MANAGER = [
    'name',
    'comment',
    'production_time',
    'ready_date',
    'status_history',
    'due_payment'
  ];

  public const  array ALLOWED_FIELDS_OFFICE_MANAGER = [
    'name',
    'comment'
  ];

  public function __construct(
    protected UserDTO $user
  ) {}

  /**
   * Бросить исключение, если доступ запрещён
   */
  protected function deny(): never
  {
    throw new \Exception('Access denied', 403);
  }

  /**
   * @throws \Exception
   */
  public function canView(array $order): void
  {
    //Офисный менеджер может смотреть все заказы
    if ($this->user->isOfficeManager()) {
      return;
    }

    // Если это Дилер-владелец
    if ($this->isDealerOwner($order)) {
      return;
    }

    // Если это Менеджер Лигрон и заказ принадлежит его дилеру
    if ($this->isManagerOfOrder($order)) {
      return;
    }

    $this->deny();
  }


  public function isDealerOwner($order): bool
  {
    return $this->user->isDealer()
      && $order['dealer_prefix'] === $this->user->dealer_prefix
      && $order['dealer_user_id'] == $this->user->id;
  }

  /**
   * Проверяет, курирует ли текущий менеджер дилера, оформившего заказ
   */
  public function isManagerOfOrder(array $order): bool
  {

    if (!$this->user->isLigronStaff()) {
      return false;
    }

    $managedDealers = AuthSession::getManagedDealers();

    if (empty($managedDealers)) {
      return false;
    }

    $allowedPrefixes = array_column($managedDealers, 'dealer_prefix');

    $orderPrefix = $order['dealer_prefix'] ?? null;

    // Проверяем, есть ли префикс заказа в списке разрешенных
    return $orderPrefix && in_array($orderPrefix, $allowedPrefixes, true);
  }


  /**
   * Проверяет разрешённые поля для обновления заказа
   * @throws \Exception
   */
  public function validateUpdateFields(array $data): void
  {
    $allowedFields = $this->getAllowedFieldsForCurrentUser();
    $requestedFields = array_keys($data);

    foreach ($requestedFields as $field) {
      if (!in_array($field, $allowedFields)) {
        throw new \Exception(
          "Поле '{$field}' не разрешено для изменения вашей ролью",
          400
        );
      }
    }
  }

  /**
   * Проверяет, может ли пользователь изменять конкретное поле
   */
  public function canUpdateField(string $field): bool
  {
    $allowedFields = $this->getAllowedFieldsForCurrentUser();
    return in_array($field, $allowedFields);
  }

  /**
   * Полный метод проверки прав на обновление заказа
   * @throws \Exception
   */
  public function canUpdate(array $order, array $data): void
  {
    $this->validateUpdateFields($data);
  }

  /**
   * Получить список всех разрешённых полей для текущего пользователя
   */
  private function getAllowedFieldsForCurrentUser(): array
  {
    if ($this->user->isDealer()) {
      return self::ALLOWED_FIELDS_DEALER;
    }

    if ($this->user->isManager()) {
      return self::ALLOWED_FIELDS_MANAGER;
    }

    if ($this->user->isOfficeManager()) {
      return self::ALLOWED_FIELDS_OFFICE_MANAGER;
    }

    return [];
  }


}