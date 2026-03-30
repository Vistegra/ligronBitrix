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
   * Получить детальные данные для сохранения в сессию.
   *
   * Если у пользователя нет прав/салона, должен вернуться пустой массив.
   *
   * @param UserDTO $user
   * @return array{
   *     hierarchy?: array<int, array{
   *         inn: string,
   *         name: string,
   *         is_substituted: bool,
   *         salons: array<int, array{salon_code: string, name: string}>
   *     }>,
   *     available_inns?: string[],
   *     available_salons?: string[],
   *     salon_code?: string,
   *     salon_name?: string,
   *     inn?: string,
   *     dealer_name?: string,
   *     managers?: array<int, array{
   *         code_user: string,
   *         name: string,
   *         email: string,
   *         phone: string,
   *         role: string,
   *         is_substitute: bool
   *     }>,
   *     substituting_codes?: string[]
   * }|array{}
   */
  public function fetchDetailedData(UserDTO $user): array;
}