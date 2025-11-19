<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Session;

use OrderApi\DTO\Auth\UserDTO;

interface AuthSessionProviderInterface
{
    /**
     * Подходит ли этот провайдер для данного пользователя?
     */
    public function supports(UserDTO $user): bool;

    /**
     * Получить детальные данные для сохранения в сессию
     *
    */
    public function fetchDetailedData(UserDTO $user): array;
}