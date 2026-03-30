<?php

declare(strict_types=1);

namespace OrderApiV2\Permissions;

use Exception;
use OrderApiV2\Constants\OrderAction;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Services\Auth\Session\AuthSession;

/**
 * Управление правами доступа к заказам.
 * Вся конфигурация прав для ролей хранится в методе getRolePolicy().
 */
final readonly class OrderPermission
{
  /**
   * Карта названий полей.
   */
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

  public function __construct(
    private UserDTO $user
  )
  {
  }

  /**
   * Вспомогательный метод для получения чпу поля
   */
  private function getFieldName(string $field): string
  {
    return self::FIELD_NAMES_RU[$field] ?? $field;
  }

  /**
   * Единый реестр прав (Policy) для всех ролей.
   */
  private function getRolePolicy(): array
  {
    // ---------------------------
    // РЕЖИМЫ БОГА
    // ---------------------------
    if ($this->user->role === 'GOD_LIGRON') {
      return [
        'view_all' => true,
        'create_for_any' => true,
        'allowed_update_fields' => ['*'],
        'can_change_status' => true,
        'can_send_to_1c' => true,
        'can_update_sent_order' => true,
        'can_delete_sent_order' => true,
      ];
    }

    if ($this->user->role === 'GOD_DEALER') {
      return [
        'view_all' => false,
        'create_for_any' => false,
        'allowed_update_fields' => ['*'],
        'can_change_status' => true,
        'can_send_to_1c' => true,
        'can_update_sent_order' => true,
        'can_delete_sent_order' => true,
      ];
    }

    // ---------------------------
    // СТАНДАРТНЫЕ РОЛИ
    // ---------------------------
    if ($this->user->isOfficeManager()) {
      return [
        'view_all' => true,
        'create_for_any' => true,
        'allowed_update_fields' => ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ];
    }

    if ($this->user->isManager()) {
      return [
        'view_all' => false,
        'create_for_any' => false,
        'allowed_update_fields' => ['name', 'comment', 'production_time', 'ready_date', 'status_history', 'due_payment', 'percent_payment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ];
    }

    if ($this->user->isDealer()) {
      return [
        'view_all' => false,
        'create_for_any' => false,
        'allowed_update_fields' => ['name', 'comment'],
        'can_change_status' => false,
        'can_send_to_1c' => true,
        'can_update_sent_order' => false,
        'can_delete_sent_order' => false,
      ];
    }

    return [
      'view_all' => false,
      'create_for_any' => false,
      'allowed_update_fields' => [],
      'can_change_status' => false,
      'can_send_to_1c' => false,
      'can_update_sent_order' => false,
      'can_delete_sent_order' => false,
    ];
  }

  /**
   * Главный метод проверки прав.
   * Перенаправляет на конкретные методы assert..., которые сами выбросят детальную ошибку.
   *
   * @throws Exception
   */
  public function verify(string $action, array $order = [], array $data = []): void
  {
    $policy = $this->getRolePolicy();

    match ($action) {
      OrderAction::CREATE => $this->assertCanCreate($data, $policy),
      OrderAction::VIEW => $this->assertCanView($order, $policy),
      OrderAction::UPDATE => $this->assertCanUpdate($order, $data, $policy),
      OrderAction::DELETE => $this->assertCanDelete($order, $policy),
      OrderAction::CHANGE_STATUS => $this->assertCanChangeStatus($order, $policy),
      OrderAction::SEND_TO_1C => $this->assertCanSendTo1C($order, $policy),
      default => throw new Exception("Неизвестное действие: {$action}", 400),
    };
  }

  public function getAccessFilter(): array
  {
    $policy = $this->getRolePolicy();

    if ($policy['view_all']) {
      return [];
    }

    $availableInns = AuthSession::getAvailableInns() ?: [];

    if ($this->user->isLigronStaff()) {
      return empty($availableInns) ? ['=id' => 0] : ['=inn_dealer' => $availableInns];
    }

    if ($this->user->isDealer()) {
      $availableSalons = AuthSession::getAvailableSalons() ?: [];

      if (empty($availableSalons) && empty($availableInns)) {
        return ['=id' => 0];
      }

      return [
        'LOGIC' => 'OR',
        ['=salon_code' => $availableSalons], ['=inn_dealer' => $availableInns]
      ];
    }

    return ['=id' => 0];
  }

  // -----------------------------------------------------------
  // Внутренние проверки (Выбрасывают подробные исключения)
  // -----------------------------------------------------------

  /**
   * @throws Exception
   */
  private function assertCanCreate(array $data, array $policy): void
  {
    if ($policy['create_for_any']) {
      return; // Режимы с полным доступом
    }

    $inn = (string)($data['inn_dealer'] ?? '');
    $salon = (string)($data['salon_code'] ?? '');

    if ($inn === '' || $salon === '') {
      throw new Exception('Для оформления заказа необходимо указать ИНН дилера и код салона.', 400);
    }

    $availableInns = AuthSession::getAvailableInns() ?: [];
    $availableSalons = AuthSession::getAvailableSalons() ?: [];

    // Единая логика для Дилеров и Менеджеров Лигрон:
    // Переданные ИНН и Салон должны быть в списке доступных из сессии (собрано BFS)
    if (!in_array($inn, $availableInns, true) || !in_array($salon, $availableSalons, true)) {
      throw new Exception('У вас нет доступа к оформлению заказа на выбранного дилера или салон.', 403);
    }
  }

  /**
   * @throws Exception
   */
  private function assertCanView(array $order, array $policy): void
  {
    if ($policy['view_all']) {
      return;
    }

    $inn = (string)($order['inn_dealer'] ?? '');
    $salon = (string)($order['salon_code'] ?? '');
    $availableInns = AuthSession::getAvailableInns() ?:[];

    // --- ЛОГИКА ДЛЯ ЛИГРОН ---
    if ($this->user->isLigronStaff()) {
      if ($inn !== '' && in_array($inn, $availableInns, true)) {
        return;
      }

      // ========== ДЕБАГ БЛОК ==========
      // Собираем типы и длины строк, чтобы найти невидимые символы
      $debugInfo =[
        'error_reason' => 'Сработал дебаг для Лигрон (in_array вернул false)',
        'order_inn_value' => $inn,
        'order_inn_length' => mb_strlen($inn),
        'order_inn_type' => gettype($inn),
        'available_inns' => $availableInns,
        'available_inns_types' => array_map('gettype', $availableInns),
        // Проверим, сработает ли нестрогое сравнение (без учета типа)
        'is_in_array_loose' => in_array($inn, $availableInns, false),
      ];

      throw new Exception(json_encode($debugInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 403);
      // ================================
    }

    // --- ЛОГИКА ДЛЯ ДИЛЕРА ---
    if ($this->user->isDealer()) {
      $availableSalons = AuthSession::getAvailableSalons() ?:[];

      $isMySalon = $salon !== '' && in_array($salon, $availableSalons, true);
      $isMyInn = $inn !== '' && in_array($inn, $availableInns, true);

      if ($isMySalon || $isMyInn) {
        return;
      }

      // ========== ДЕБАГ БЛОК ==========
      $debugInfo =[
        'error_reason' => 'Сработал дебаг для Дилера',
        'order_inn' => $inn,
        'order_salon' => $salon,
        'available_inns' => $availableInns,
        'available_salons' => $availableSalons,
      ];
      throw new Exception(json_encode($debugInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 403);
      // ================================
    }

    throw new Exception('У вас нет прав на просмотр данного заказа.', 403);
  }
  /**
   * @throws Exception
   */
/*  private function assertCanView(array $order, array $policy): void
  {
    if ($policy['view_all']) {
      return;
    }

    $inn = (string)($order['inn_dealer'] ?? '');
    $salon = (string)($order['salon_code'] ?? '');
    $availableInns = AuthSession::getAvailableInns() ?: [];

    if ($this->user->isLigronStaff() && $inn !== '' && in_array($inn, $availableInns, true)) {
      return;
    }

    if ($this->user->isDealer()) {
      $availableSalons = AuthSession::getAvailableSalons() ?: [];
      $isMySalon = $salon !== '' && in_array($salon, $availableSalons, true);
      $isMyInn = $inn !== '' && in_array($inn, $availableInns, true);

      if ($isMySalon || $isMyInn) {
        return;
      }
    }

    throw new Exception('У вас нет прав на просмотр данного заказа.', 403);
  }*/

  /**
   * @throws Exception
   */
  private function assertCanUpdate(array $order, array $data, array $policy): void
  {
    // Сначала проверяем, видит ли он вообще этот заказ
    $this->assertCanView($order, $policy);

    $isSentTo1c = !empty($order['number']);

    if ($isSentTo1c && !$policy['can_update_sent_order']) {
      throw new Exception('Редактирование заказа запрещено, так как он уже передан в производство (присвоен номер Лигрон).', 403);
    }

    if (empty($data)) {
      return;
    }

    $allowedFields = $policy['allowed_update_fields'];
    if (in_array('*', $allowedFields, true)) {
      return;
    }

    foreach (array_keys($data) as $field) {
      if (!in_array($field, $allowedFields, true)) {
        $fieldName = $this->getFieldName($field);
        throw new Exception("У вас нет прав на изменение поля '{$fieldName}'.", 403);
      }
    }
  }

  /**
   * @throws Exception
   */
  private function assertCanDelete(array $order, array $policy): void
  {
    $this->assertCanView($order, $policy);

    $hasChildren = (int)($order['children_count'] ?? 0) > 0;
    if ($hasChildren) {
      throw new Exception('Нельзя удалить заказ, у которого есть вложенные подзаказы. Сначала удалите их.', 403);
    }

    $isSentTo1c = !empty($order['number']);
    if ($isSentTo1c && !$policy['can_delete_sent_order']) {
      throw new Exception('Нельзя удалить заказ, который уже передан в производство (присвоен номер Лигрон).', 403);
    }
  }

  /**
   * @throws Exception
   */
  private function assertCanChangeStatus(array $order, array $policy): void
  {
    if (!$policy['can_change_status']) {
      throw new Exception('У вашей роли нет прав на ручную смену статуса заказа.', 403);
    }

    $this->assertCanView($order, $policy);
  }

  /**
   * @throws Exception
   */
  private function assertCanSendTo1C(array $order, array $policy): void
  {
    if (!$policy['can_send_to_1c']) {
      throw new Exception('У вас нет прав на отправку заказов в Лигрон.', 403);
    }

    if (!empty($order['number'])) {
      throw new Exception('Этот заказ уже был отправлен в Лигрон ранее.', 400);
    }

    $this->assertCanView($order, $policy);
  }
}