<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Session;

use OrderApiV2\Constants\ProviderType;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\DB\Repositories\AccessRepository;

final class LigronManagerAuthSessionProvider implements AuthSessionProviderInterface
{
  public function supports(UserDTO $user): bool
  {
    return $user->provider === ProviderType::LIGRON;
  }

  public function fetchDetailedData(UserDTO $user): array
  {
    if ($user->role === UserRole::GOD_LIGRON) {
      return $this->getGotDetail();
    }

    if (!$user->user_code) return [];

    $data = AccessRepository::getLigronHierarchy($user->user_code);

    return [
      'hierarchy' => $data['managed_dealers'],
      'available_inns' => $data['available_inns'],
      'available_salons' => $data['available_salons'],
      'substituting_codes' => $data['substituting_codes'] ?? [],
    ];
  }

  public function getGotDetail(): ?array
  {
    return [
      //ToDo Проверить поля менеджера Лигрон как Бога
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