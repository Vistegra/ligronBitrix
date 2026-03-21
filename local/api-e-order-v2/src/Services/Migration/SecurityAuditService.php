<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Migration;

use OrderApiV2\DB\Models\DealerTable;
use OrderApiV2\DB\Models\SalonTable;
use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\DealerSalonTable;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Repositories\AccessRepository;
use OrderApiV2\Permissions\OrderPermission;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Constants\OrderAction;

class SecurityAuditService
{
  /**
   * Запуск полного аудита безопасности
   */
  public function runFullAudit(): array
  {
    // 1. Загружаем иерархию из MSSQL
    $dealers = DealerTable::getList(['filter' => ['=active' => 1]])->fetchAll();
    $salonsMap = array_column(SalonTable::getList(['filter' => ['=active' => 1]])->fetchAll(), null, 'salon_code');
    $usersRaw = DealerUserTable::getList(['filter' => ['=active' => 1]])->fetchAll();

    $salonUsersMap = [];
    foreach ($usersRaw as $u) {
      $salonUsersMap[$u['salon_code']][] = $u;
    }

    $links = DealerSalonTable::getList()->fetchAll();
    $dealerToSalons = [];
    foreach ($links as $link) {
      $dealerToSalons[$link['inn_dealer']][] = $link['salon_code'];
    }

    // 2. Загружаем заказы из MySQL (Игнорируем DEALER_USER_ID)
    $allOrders = OrderTable::getList([
      'select' => ['ID', 'INN_DEALER', 'SALON_CODE']
    ])->fetchAll();

    $tree = [];
    foreach ($dealers as $d) {
      $inn = $d['inn_dealer'];
      $dealerNode = ['name' => $d['name'], 'inn' => $inn, 'salons' => []];

      $attachedCodes = $dealerToSalons[$inn] ?? [];
      foreach ($attachedCodes as $code) {
        if (!isset($salonsMap[$code])) continue;

        $usersInSalon = $salonUsersMap[$code] ?? [];
        $auditedUsers = [];

        foreach ($usersInSalon as $u) {
          $auditedUsers[] = $this->auditSingleUser($u, $allOrders);
        }

        $dealerNode['salons'][] = [
          'name' => $salonsMap[$code]['name'],
          'code' => $code,
          'users' => $auditedUsers
        ];
      }
      $tree[] = $dealerNode;
    }

    return $tree;
  }

  /**
   * Аудит одного пользователя
   */
  private function auditSingleUser(array $u, array $allOrders): array
  {
    // Создаем DTO "нового" пользователя
    $userDTO = new UserDTO(
      id: (int)$u['id'], // Это новый ID из MSSQL
      login: trim($u['username']),
      name: $u['name'],
      provider: 'dealer',
      role: $u['role_code'],
      salon_code: $u['salon_code']
    );

    // Получаем BFS-облако для этого пользователя
    $hierarchy = AccessRepository::getDealerHierarchy($u['salon_code']);
    $allowedSalons = $hierarchy['salon_codes'];

    $permission = new OrderPermission($userDTO);

    $totalVisible = 0;
    $ownOrders = 0;
    $sharedOrders = 0;
    $leaks = 0;

    foreach ($allOrders as $order) {
      // Приводим ключи к нижнему регистру для стабильности (Bitrix/MySQL)
      $o = array_change_key_case($order, CASE_LOWER);

      // ИМИТАЦИЯ ЛОГИКИ ДОСТУПА V2 (БЕЗ УЧЕТА DEALER_USER_ID)
      $hasAccess = false;

      // 1. Связь по логину (Личный заказ)
      if (!empty($o['dealer_username']) && $o['dealer_username'] === $userDTO->login) {
        $hasAccess = true;
        $ownOrders++;
      }
      // 2. Связь по салону из BFS-облака (Общий заказ)
      elseif (!empty($o['salon_code']) && in_array($o['salon_code'], $allowedSalons)) {
        $hasAccess = true;
        $sharedOrders++;
      }

      if ($hasAccess) {
        $totalVisible++;
        // Финальная сверка через метод verify класса OrderPermission
        try {
          $permission->verify(OrderAction::VIEW, $order);
        } catch (\Throwable $e) {
          $leaks++; // Если verify запретил то, что разрешил наш расчет
        }
      }
    }

    return [
      'name' => $u['name'],
      'username' => $u['username'],
      'role' => $u['role_code'],
      'stats' => [
        'visible' => $totalVisible,
        'own' => $ownOrders,
        'shared' => $sharedOrders,
        'leaks' => $leaks,
        'cloud_size' => count($allowedSalons),
        'status' => ($leaks === 0) ? 'OK' : 'FAIL'
      ]
    ];
  }
}