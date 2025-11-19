<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Session;

use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\DB\Repositories\DealerUserRepository;

final class DealerAuthSessionProvider implements AuthSessionProviderInterface
{
    public function supports(UserDTO $user): bool
    {
        return $user->provider === ProviderType::DEALER && $user->role === UserRole::DEALER;
    }

    public function fetchDetailedData(UserDTO $user): array
    {
      return DealerUserRepository::findDetailedUserByIds(
          userId:   $user->id,
          dealerId: $user->dealer_id
      );
    }
}