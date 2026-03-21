<?php
declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Session;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApiV2\Constants\ProviderType;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\DB\Repositories\AccessRepository;

final class DealerAuthSessionProvider implements AuthSessionProviderInterface
{
  public function supports(UserDTO $user): bool
  {
    return $user->provider === ProviderType::DEALER;
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public function fetchDetailedData(UserDTO $user): array
  {
    if (!$user->salon_code) return [];

    $data = AccessRepository::getDealerHierarchy($user->salon_code);

    // Ищем имя текущего салона
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
      'salon_code'       => $user->salon_code,
      'salon_name'       => $currentSalonName,
      'inn'              => $primary ? $primary['inn'] : '',
      'dealer_name'      => $primary ? $primary['name'] : '',
      'managers'         => $managers,
      'available_salons' => $data['available_salons'],
      'available_inns'   => $data['available_inns'],
      'hierarchy'        => $data['managed_dealers'],
    ];
  }
}