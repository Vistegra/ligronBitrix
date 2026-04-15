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
   * Настройки прав в зависимости от роли
   */
  public function getRolePolicy(): RolePolicy
  {
    return match ($this->user->role) {

      // 1. РЕЖИМЫ БОГА
      UserRole::GOD_LIGRON,
      UserRole::GOD_DEALER => new RolePolicy(
        viewAll: true,
        createForAny: true,
        allowedUpdateFields: ['*'],
        canChangeStatus: true,
        canSendTo1c: true,
        canUpdateSentOrder: true,
        canDeleteSentOrder: true
      ),

      // 2. ОФИС-МЕНЕДЖЕР ЛИГРОН (OML)
      UserRole::LIGRON_OFFICE_MANAGER => new RolePolicy(
        viewAll: true,
        createForAny: true,
        allowedUpdateFields: ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        canChangeStatus: false,
        canSendTo1c: true,
        canUpdateSentOrder: false,
        canDeleteSentOrder: false
      ),

      // 3. ОБЫЧНЫЙ МЕНЕДЖЕР ЛИГРОН (ML)
      UserRole::LIGRON_MANAGER => new RolePolicy(
        viewAll: false,
        createForAny: false,
        allowedUpdateFields: ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        canChangeStatus: false,
        canSendTo1c: true,
        canUpdateSentOrder: false,
        canDeleteSentOrder: false
      ),

      // 4. ВСЕ МЕНЕДЖЕРЫ ДИЛЕРА (M, MS, LM)
      UserRole::DEALER_SALON_MANAGER,
      UserRole::DEALER_LIGRON_MANAGER,
      UserRole::DEALER_MANAGER => new RolePolicy(
        viewAll: false,
        createForAny: false,
        allowedUpdateFields: ['name', 'comment'],
        canChangeStatus: false,
        canSendTo1c: true,
        canUpdateSentOrder: false,
        canDeleteSentOrder: false
      ),

      // ДЕФОЛТ
      default => new RolePolicy(
        viewAll: false,
        createForAny: false,
        allowedUpdateFields: [],
        canChangeStatus: false,
        canSendTo1c: false,
        canUpdateSentOrder: false,
        canDeleteSentOrder: false
      ),
    };
  }

  /**
   * Главный метод защиты API-эндпоинтов
   * @throws Exception
   */
  public function verify(string $action, array $order = [], array $data = []): void
  {
    $policy = $this->getRolePolicy();
    $o = array_change_key_case($order, CASE_LOWER);

    $result = match ($action) {
      OrderAction::CREATE => $this->checkCanCreate($data, $policy),
      OrderAction::VIEW => $this->checkCanView($o, $policy),
      OrderAction::UPDATE => $this->checkCanUpdate($o, $data, $policy),
      OrderAction::DELETE => $this->checkCanDelete($o, $policy),
      OrderAction::CHANGE_STATUS => $this->checkCanChangeStatus($o, $policy),
      OrderAction::SEND_TO_1C => $this->checkCanSendTo1C($o, $policy),
      default => throw new Exception("Неизвестное действие: {$action}", 400),
    };

    if (!$result->isAllowed) {
      // Для ошибки CREATE (отсутствие полей) возвращаем 400, для остальных доступов 403
      $code = str_contains((string)$result->errorMessage, 'Необходимо указать') ? 400 : 403;
      throw new Exception($result->errorMessage, $code);
    }
  }

  /**
   * Возвращает карту разрешений (действий) для отрисовки кнопок на фронтенде
   */
  public function getFrontendPermissions(array $order): array
  {
    $o = array_change_key_case($order, CASE_LOWER);
    $policy = $this->getRolePolicy();

    $inn = (string)($o['inn_dealer'] ?? '');
    $isAttached = in_array($inn, AuthSession::getAvailableInns() ?: [], true);

    // Права на калькулятор: заказ из калькулятора + (Пользователь Бог ИЛИ привязан к дилеру)
    $canOpenCalc = ($o['origin_type'] == 2) && ($this->user->isGod() || $isAttached);

    return [
      'can_open_calc' => $canOpenCalc,
      // Пустой массив данных в Update означает проверку возможности редактирования
      'can_update' => $this->checkCanUpdate($o, [], $policy)->isAllowed,
      'can_delete' => $this->checkCanDelete($o, $policy)->isAllowed,
      'can_change_status' => $this->checkCanChangeStatus($o, $policy)->isAllowed,
      'can_send_to_1c' => $this->checkCanSendTo1C($o, $policy)->isAllowed,
    ];
  }

  /**
   * Генерация фильтра для списков (SQL).
   */
  public function getAccessFilter(bool $isDraft): array
  {
    $policy = $this->getRolePolicy();

    // 1. Режим Бога или Офис-менеджер видят все реальные заказы
    if ($policy->viewAll && !$isDraft) return [];

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

  // ---------------------------------------------------------
  // БАЗОВАЯ ЛОГИКА ПРОВЕРОК (Возвращают PermissionResult)
  // ---------------------------------------------------------

  private function checkCanCreate(array $data, RolePolicy $policy): PermissionResult
  {
    if ($policy->createForAny) return PermissionResult::allow();

    $inn = (string)($data['inn_dealer'] ?? '');
    $salon = (string)($data['salon_code'] ?? '');

    if ($inn === '' || $salon === '') {
      return PermissionResult::deny('Необходимо указать ИНН дилера и код салона.');
    }

    if (!$this->isContextAllowed($inn, $salon)) {
      return PermissionResult::deny('У вас нет доступа к выбранному дилеру или салону.');
    }

    return PermissionResult::allow();
  }

  private function checkCanView(array $o, RolePolicy $policy): PermissionResult
  {
    // Защита черновика
    if ($this->isDraft($o)) {
      if ((int)($o['author_id'] ?? 0) === $this->user->id) {
        return PermissionResult::allow();
      }
      return PermissionResult::deny('Доступ к чужому черновику запрещен.');
    }

    // Режим Бога / OML
    if ($policy->viewAll) return PermissionResult::allow();

    // Проверка облака (привязан ли дилер/салон к текущему юзеру)
    if ($this->isContextAllowed((string)($o['inn_dealer'] ?? ''), (string)($o['salon_code'] ?? ''))) {
      return PermissionResult::allow();
    }

    return PermissionResult::deny('У вас нет прав на просмотр данного заказа.');
  }

  private function checkCanUpdate(array $o, array $data, RolePolicy $policy): PermissionResult
  {
    $viewCheck = $this->checkCanView($o, $policy);
    if (!$viewCheck->isAllowed) return $viewCheck;

    if (!empty($o['number']) && !$policy->canUpdateSentOrder) {
      return PermissionResult::deny('Редактирование заказа в производстве запрещено.');
    }

    if (empty($data)) return PermissionResult::allow();

    $allowed = $policy->allowedUpdateFields;
    if (in_array('*', $allowed, true)) return PermissionResult::allow();

    foreach (array_keys($data) as $field) {
      if (!in_array($field, $allowed, true)) {
        return PermissionResult::deny("Нет прав на изменение поля '{$this->getFieldName($field)}'.");
      }
    }

    return PermissionResult::allow();
  }

  private function checkCanDelete(array $o, RolePolicy $policy): PermissionResult
  {
    $viewCheck = $this->checkCanView($o, $policy);
    if (!$viewCheck->isAllowed) return $viewCheck;

    if ((int)($o['children_count'] ?? 0) > 0) {
      return PermissionResult::deny('Сначала удалите вложенные заказы.');
    }

    if (!empty($o['number']) && !$policy->canDeleteSentOrder) {
      return PermissionResult::deny('Удаление отправленного заказа запрещено.');
    }

    return PermissionResult::allow();
  }

  private function checkCanChangeStatus(array $o, RolePolicy $policy): PermissionResult
  {
    if (!$policy->canChangeStatus) {
      return PermissionResult::deny('Нет прав на смену статуса.');
    }
    return $this->checkCanView($o, $policy);
  }

  private function checkCanSendTo1C(array $o, RolePolicy $policy): PermissionResult
  {
    if (!$policy->canSendTo1c) {
      return PermissionResult::deny('Нет прав на отправку в Лигрон.');
    }
    if (!empty($o['number'])) {
      return PermissionResult::deny('Заказ уже был отправлен.');
    }
    return $this->checkCanView($o, $policy);
  }

  // -----------------
  // ХЕЛПЕРЫ
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

  private function getFieldName(string $field): string
  {
    return self::FIELD_NAMES_RU[$field] ?? $field;
  }

}