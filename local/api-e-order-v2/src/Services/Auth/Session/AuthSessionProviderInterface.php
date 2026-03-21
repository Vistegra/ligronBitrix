<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Session;

use OrderApiV2\DTO\Auth\UserDTO;

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