<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Session;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Repositories\WebUserRepository;
use OrderApi\DTO\Auth\UserDTO;

final class LigronManagerAuthSessionProvider implements AuthSessionProviderInterface
{
  public function supports(UserDTO $user): bool
  {
    return $user->provider === ProviderType::LIGRON
      && ($user->role === UserRole::MANAGER || $user->role === UserRole::OFFICE_MANAGER);
  }


  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  public function fetchDetailedData(UserDTO $user): array
  {
    $codeUserRole = $user->role === UserRole::MANAGER ? 'code_user' : 'code_user_manager';

    return WebUserRepository::fetchDetailedByUserId($user->id);
  }
}