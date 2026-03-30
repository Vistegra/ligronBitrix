<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Repositories;

use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\DealerTable;

/**
 * Репозиторий для работы с пользователями дилеров.
 */
class DealerUserRepository
{
  /**
   * Поиск пользователя для авторизации.
   * Используется в DealerUserAuthProvider.
   */
  public static function findByUsername(string $username): ?array
  {
    return DealerUserTable::getList([
      'select' => ['*', 'role_name' => 'role.name'],
      'filter' => ['=username' => trim($username), '=active' => 1],
      'limit' => 1
    ])->fetch() ?: null;
  }

  /**
   * Получение расширенных данных для профиля (me).
   * Использует AccessRepository для получения иерархии доступов.
   */
  public static function findDetailedById(int $userId): ?array
  {
    $user = DealerUserTable::getList([
      'select' => ['*', 'salon_name' => 'salon.name'],
      'filter' => ['=id' => $userId, '=active' => 1],
      'limit' => 1,
    ])->fetch();

    if (!$user) return null;

    $hierarchy = AccessRepository::getDealerHierarchy($user['salon_code']);

    $managers = AccessRepository::getLigronManagersForInns($hierarchy['available_inns']);

    return [
      'name' => $user['name'],
      'phone' => $user['phone'] ?? '',
      'email' => $user['email'] ?? '',
      'salon_name' => $user['salon_name'],
      'salon_code' => $user['salon_code'],
      'inn' => $hierarchy['available_inns'][0] ?? '', // Основной ИНН
      'dealer_name' => $hierarchy['managed_dealers'][0]['name'] ?? '',
      'managers' => $managers,
      'available_salons' => $hierarchy['available_salons'],
      'available_inns' => $hierarchy['available_inns'],
      'fetched_at' => time(),
    ];
  }

  /**
   * Поиск дилера (для проверки ИНН)
   */
  public static function getDealerByInn(string $inn): ?array
  {
    return DealerTable::getList([
      'select' => ['id', 'inn_dealer', 'name'],
      'filter' => ['=inn_dealer' => trim($inn), '=active' => 1],
      'limit' => 1
    ])->fetch() ?: null;
  }

}