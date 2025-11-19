<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Session;

use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DTO\Auth\UserDTO;

final class LigronManagerAuthSessionProvider implements AuthSessionProviderInterface
{
  public function supports(UserDTO $user): bool
  {
    return $user->provider === ProviderType::LIGRON
      && ($user->role === UserRole::MANAGER || $user->role === UserRole::OFFICE_MANAGER);
  }

  public function fetchDetailedData(UserDTO $user): array
  {
    throw new \RuntimeException('Not implemented');
//    return [
//      'managed_dealers' => //Todo
//      'managed_users'   =>  //ToDo
//    ];
  }
}