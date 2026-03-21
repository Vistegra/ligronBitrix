<?php

declare(strict_types=1);

namespace OrderApiV2\Permissions;

use Exception;
use OrderApiV2\Constants\OrderAction;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Services\Auth\Session\AuthSession;

/**
 * Класс управления правами доступа к заказам.
 */
final readonly class OrderPermission
{
  private const array FIELD_POLICIES = [
    'DEALER_BASIC' =>['name', 'comment'],
    'LIGRON_MANAGER' =>['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
    'LIGRON_OFFICE' => ['name', 'comment'],
  ];

  public function __construct(
    private UserDTO $user
  ) {
  }

  /**
   * @throws Exception
   */
  public function verify(string $action, array $order, array $data =[]): void
  {
    $granted = match ($action) {
      OrderAction::VIEW => $this->canView($order),
      OrderAction::UPDATE => $this->canUpdate($order, $data),
      OrderAction::DELETE => $this->canDelete($order),
      OrderAction::CHANGE_STATUS => $this->canChangeStatus($order),
      OrderAction::SEND_TO_1C => $this->canSendTo1C($order),
      default => false,
    };

    if (!$granted) {
      throw new Exception("У вас недостаточно прав для выполнения действия: {$action}", 403);
    }
  }

  /**
   * Генерация фильтра для списков (Bitrix ORM)
   */
  public function getAccessFilter(): array
  {
    // Офис-менеджер Лигрон (OML) видит абсолютно всё
    if ($this->user->isOfficeManager()) {
      return[];
    }

    // Менеджер Лигрон (ML) видит заказы только своих закрепленных дилеров (по ИНН)
    if ($this->user->isLigronStaff()) {
      $inns = AuthSession::getAvailableInns() ?:[];
      return empty($inns) ? ['=id' => 0] : ['=inn_dealer' => $inns];
    }

    // Дилеры (и их салоны)
    if ($this->user->isDealer()) {
      $availableSalons = AuthSession::getAvailableSalons() ?:[];
      $availableInns = AuthSession::getAvailableInns() ?:[];

      if (empty($availableSalons) && empty($availableInns)) {
        return ['=id' => 0]; // Заглушка, если нет привязанных салонов/дилеров
      }

      // Игнорируем роли и ID пользователя.
      // Отдаем все заказы, принадлежащие доступным ИНН или Салонам.
      return [
        'LOGIC' => 'OR',['=salon_code' => $availableSalons],
        ['=inn_dealer' => $availableInns]
      ];
    }

    // если тип пользователя не распознан
    return ['=id' => 0];
  }

  // Приватные политики
  private function canView(array $order): bool
  {
    if ($this->user->isOfficeManager()) {
      return true;
    }

    if ($this->user->isLigronStaff()) {
      return $this->isManagedByLigron($order);
    }

    if ($this->user->isDealer()) {
      return $this->canViewDealerOrder($order);
    }

    return false;
  }

  private function canUpdate(array $order, array $data): bool
  {
    if (!$this->canView($order)) {
      return false;
    }

    // Дилер не может обновлять заказ, если ему уже присвоен номер Лигрон (он отправлен)
    if ($this->user->isDealer() && !empty($order['number'])) {
      return false;
    }

    return $this->validateFields($data);
  }

  private function canDelete(array $order): bool
  {
    // Удалять может только тот, кто имеет доступ к заказу, и только если заказ еще черновик
    return $this->canView($order)
      && empty($order['number'])
      && (int)($order['children_count'] ?? 0) === 0;
  }

  private function canChangeStatus(array $order): bool
  {
    // Дилеры не могут менять статус напрямую (только Лигрон или 1С)
    return $this->user->isLigronStaff();
  }

  private function canSendTo1C(array $order): bool
  {
    if ($this->user->isLigronStaff()) {
      return true;
    }

    // Дилер может отправить заказ только если он является черновиком и он имеет к нему доступ
    return $this->canViewDealerOrder($order) && empty($order['number']);
  }

  /**
   * Проверка прав на просмотр заказа для пользователя Дилера
   */
  private function canViewDealerOrder(array $order): bool
  {
    if (!$this->user->isDealer()) {
      return false;
    }

    $salons = AuthSession::getAvailableSalons() ?: [];
    $inns = AuthSession::getAvailableInns() ?:[];

    // Проверяем только принадлежность заказа к салону или ИНН
    if (!empty($order['salon_code']) && in_array($order['salon_code'], $salons, true)) {
      return true;
    }

    if (!empty($order['inn_dealer']) && in_array($order['inn_dealer'], $inns, true)) {
      return true;
    }

    return false;
  }

  /**
   * Проверка прав на просмотр заказа для Менеджера Лигрон
   */
  private function isManagedByLigron(array $order): bool
  {
    $inns = AuthSession::getAvailableInns() ?: [];
    return !empty($order['inn_dealer']) && in_array($order['inn_dealer'], $inns, true);
  }

  /**
   * Проверка полей, которые пользователь пытается обновить
   */
  private function validateFields(array $data): bool
  {
    if (empty($data)) return true;
    $allowedFields = match (true) {
      $this->user->isManager() => self::FIELD_POLICIES['LIGRON_MANAGER'],
      $this->user->isOfficeManager() => self::FIELD_POLICIES['LIGRON_OFFICE'],
      $this->user->isDealer() => self::FIELD_POLICIES['DEALER_BASIC'],
      default => [],
    };
    foreach (array_keys($data) as $field) {
      if (!in_array($field, $allowedFields, true)) return false;
    }
    return true;
  }

}