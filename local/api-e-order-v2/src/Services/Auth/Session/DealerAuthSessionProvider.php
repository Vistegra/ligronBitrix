<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Session;

use OrderApiV2\Config\ApiConfig;
use OrderApiV2\Constants\ProviderType;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\DB\Repositories\AccessRepository;

final class DealerAuthSessionProvider implements AuthSessionProviderInterface
{
  public function supports(UserDTO $user): bool
  {
    return $user->provider === ProviderType::DEALER;
  }

  public function fetchDetailedData(UserDTO $user): array
  {
    if ($user->role === UserRole::GOD_DEALER) {
      return $this->getGotDetail();
    }

    if (!$user->salon_code) return [];

    $data = AccessRepository::getDealerHierarchy($user->salon_code);

    // Ищем название текущего салона для вывода в профиле
    $currentSalonName = '';
    foreach ($data['managed_dealers'] as $dealerNode) {
      foreach ($dealerNode['salons'] as $s) {
        if ($s['salon_code'] === $user->salon_code) {
          $currentSalonName = $s['name'];
          break 2;
        }
      }
    }

    $primary = $data['managed_dealers'][0] ?? null;
    $managers = AccessRepository::getLigronManagersForInns($data['available_inns']);

    return [
      // Данные иерархии
      'hierarchy' => $data['managed_dealers'],
      'available_inns' => $data['available_inns'],
      'available_salons' => $data['available_salons'],

      // Контекст конкретного пользователя (для профиля)
      'salon_code' => $user->salon_code,
      'salon_name' => $currentSalonName,
      'inn' => $primary ? $primary['inn'] : '',
      'dealer_name' => $primary ? $primary['name'] : '',
      'managers' => $managers,
    ];
  }

  public function getGotDetail(): ?array
  {
    return [
      'hierarchy' => [],
      'available_inns' => [],
      'available_salons' => [],
      'salon_code' => 'GOD',
      'salon_name' => 'Все салоны',
      'inn' => 'GOD',
      'dealer_name' => 'Полный доступ (Бог)',
      'managers' => [],
    ];
  }
}