<?php

declare(strict_types=1);

namespace OrderApiV2\Permissions;

use Exception;
use OrderApiV2\Constants\OrderAction;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Services\Auth\Session\AuthSession;

/**
 * Управление правами доступа к заказам.
 */
final readonly class OrderPermission
{
  private const array FIELD_NAMES_RU = [
    'name' => 'Название/имя клиента',
    'comment' => 'Комментарий',
    'production_time' => 'Срок производства (дней)',
    'ready_date' => 'Дата готовности',
    'status_history' => 'История статусов',
    'due_payment' => 'Остаток оплаты',
    'percent_payment' => 'Процент оплаты',
    'status_id' => 'Статус заказа',
  ];

  public function __construct(private UserDTO $user)
  {
  }

  /**
   * Настройки прав в зависимости от роли по UserRole
   */
  public function getRolePolicy(): array
  {
    return match ($this->user->role) {

      // 1. РЕЖИМЫ БОГА
      UserRole::GOD_LIGRON,
      UserRole::GOD_DEALER => [
        'view_all' => true,
        'create_for_any' => true,
        'allowed_update_fields' => ['*'],
        'can_change_status' => true,
        'can_send_to_1c' => true,
        'can_update_sent_order' => true,
        'can_delete_sent_order' => true,
      ],

      // 2. ОФИС-МЕНЕДЖЕР ЛИГРОН (OML)
      UserRole::LIGRON_OFFICE_MANAGER => [
        'view_all' => true,
        'create_for_any' => true,
        'allowed_update_fields' => ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ],

      // 3. ОБЫЧНЫЙ МЕНЕДЖЕР ЛИГРОН (ML)
      UserRole::LIGRON_MANAGER => [
        'view_all' => false,
        'create_for_any' => false,
        'allowed_update_fields' => ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ],

      // 4. ВСЕ МЕНЕДЖЕРЫ ДИЛЕРА (M, MS, LM)
      UserRole::DEALER_SALON_MANAGER,
      UserRole::DEALER_LIGRON_MANAGER,
      UserRole::DEALER_MANAGER => [
        'view_all' => false,
        'create_for_any' => false,
        'allowed_update_fields' => ['name', 'comment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ],

      default => [
        'view_all' => false, 'create_for_any' => false, 'allowed_update_fields' => [],
        'can_change_status' => false, 'can_send_to_1c' => false,
        'can_update_sent_order' => false, 'can_delete_sent_order' => false,
      ],
    };
  }

  /**
   * Главный метод проверки прав
   * @throws Exception
   */
  public function verify(string $action, array $order = [], array $data = []): void
  {
    $policy = $this->getRolePolicy();
    $o = array_change_key_case($order, CASE_LOWER);

    match ($action) {
      OrderAction::CREATE => $this->assertCanCreate($data, $policy),
      OrderAction::VIEW => $this->assertCanView($o, $policy),
      OrderAction::UPDATE => $this->assertCanUpdate($o, $data, $policy),
      OrderAction::DELETE => $this->assertCanDelete($o, $policy),
      OrderAction::CHANGE_STATUS => $this->assertCanChangeStatus($o, $policy),
      OrderAction::SEND_TO_1C => $this->assertCanSendTo1C($o, $policy),
      default => throw new Exception("Неизвестное действие: {$action}", 400),
    };
  }

  /**
   * Генерация фильтра для списков (SQL).
   */
  public function getAccessFilter(bool $isDraft): array
  {
    $policy = $this->getRolePolicy();

    // 1. Режим Бога или Офис-менеджер видят все реальные заказы
    if ($policy['view_all'] && !$isDraft) return [];

    // 2. Логика для черновиков
    // Строго по author_id и провайдеру (created_by)
    if ($isDraft) {
      return [
        '=author_id' => $this->user->id,
        '=created_by' => $this->user->isDealer() ? 1 : 2,
        '=status_id' => false // IS NULL
      ];
    }

    // 3. Логика для обычных заказов
    $inns = AuthSession::getAvailableInns() ?: [];
    $salons = AuthSession::getAvailableSalons() ?: [];

    if ($this->user->isLigronStaff()) {
      return empty($inns) ? ['=id' => 0] : ['=inn_dealer' => $inns];
    }

    if ($this->user->isDealer()) {
      return (empty($inns) && empty($salons))
        ? ['=id' => 0]
        : ['LOGIC' => 'OR', ['=salon_code' => $salons], ['=inn_dealer' => $inns]];
    }

    return ['=id' => 0];
  }

  // -----------------
  // Приватные хелперы
  // -----------------

  private function isDraft(array $o): bool
  {
    return empty($o['status_id']) && empty($o['number']);
  }

  private function isContextAllowed(string $inn, string $salon): bool
  {
    $inns = AuthSession::getAvailableInns() ?: [];
    $salons = AuthSession::getAvailableSalons() ?: [];
    return in_array($inn, $inns, true) || in_array($salon, $salons, true);
  }
  // ---------------------
  // Проверки
  // ---------------------

  /** @throws Exception */
  private function assertCanCreate(array $data, array $policy): void
  {
    if ($policy['create_for_any']) return;

    $inn = (string)($data['inn_dealer'] ?? '');
    $salon = (string)($data['salon_code'] ?? '');

    if ($inn === '' || $salon === '') {
      throw new Exception('Необходимо указать ИНН дилера и код салона.', 400);
    }

    if (!$this->isContextAllowed($inn, $salon)) {
      throw new Exception('У вас нет доступа к выбранному дилеру или салону.', 403);
    }
  }

  /** @throws Exception */
  private function assertCanView(array $o, array $policy): void
  {
    // Защита черновика
    if ($this->isDraft($o)) {
      if ((int)($o['author_id'] ?? 0) === $this->user->id) return;
      throw new Exception('Доступ к чужому черновику запрещен.', 403);
    }

    // Режим Бога / OML
    if ($policy['view_all']) return;

    // Проверка облака
    if ($this->isContextAllowed((string)($o['inn_dealer'] ?? ''), (string)($o['salon_code'] ?? ''))) {
      return;
    }

    throw new Exception('У вас нет прав на просмотр данного заказа.', 403);
  }

  /** @throws Exception */
  private function assertCanUpdate(array $o, array $data, array $policy): void
  {
    $this->assertCanView($o, $policy);

    if (!empty($o['number']) && !$policy['can_update_sent_order']) {
      throw new Exception('Редактирование заказа в производстве запрещено.', 403);
    }

    if (empty($data)) return;

    $allowed = $policy['allowed_update_fields'];
    if (in_array('*', $allowed, true)) return;

    foreach (array_keys($data) as $field) {
      if (!in_array($field, $allowed, true)) {
        throw new Exception("Нет прав на изменение поля '{$this->getFieldName($field)}'.", 403);
      }
    }
  }

  /** @throws Exception */
  private function assertCanDelete(array $o, array $policy): void
  {
    $this->assertCanView($o, $policy);

    if ((int)($o['children_count'] ?? 0) > 0) {
      throw new Exception('Сначала удалите вложенные заказы.', 403);
    }

    if (!empty($o['number']) && !$policy['can_delete_sent_order']) {
      throw new Exception('Удаление отправленного заказа запрещено.', 403);
    }
  }

  /** @throws Exception */
  private function assertCanChangeStatus(array $o, array $policy): void
  {
    if (!$policy['can_change_status']) throw new Exception('Нет прав на смену статуса.', 403);
    $this->assertCanView($o, $policy);
  }

  /** @throws Exception */
  private function assertCanSendTo1C(array $o, array $policy): void
  {
    if (!$policy['can_send_to_1c']) throw new Exception('Нет прав на отправку в Лигрон.', 403);
    if (!empty($o['number'])) throw new Exception('Заказ уже был отправлен.', 400);
    $this->assertCanView($o, $policy);
  }

  private function getFieldName(string $field): string
  {
    return self::FIELD_NAMES_RU[$field] ?? $field;
  }
}