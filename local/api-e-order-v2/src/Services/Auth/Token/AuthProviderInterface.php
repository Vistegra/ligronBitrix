<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use OrderApiV2\DTO\Auth\UserDTO;

interface AuthProviderInterface
{
  public const string PROVIDER = '';

  /**
   * Выполняет авторизацию пользователя и возвращает данные с JWT-токеном.
   *
   * @param string $login
   * @param string $password
   * @return array{
   *     user: array,
   *     token: string,
   *     expires_in: int,
   *     token_type: string,
   *     provider: string
   * }|null Возвращает null, если логин/пароль неверные
   */
  public function login(string $login, string $password): ?array;

    /**
     * Авторизация пользователя по логину (после валидации внешнего токена)
     */
    public function loginWithoutPassword(string $login): ?array;

  /**
   * Проверяет, подходит ли расшифрованный Payload токена для данного провайдера.
   *
   * @param array $payload Декодированный массив из JWT (содержит ['user' => ...])
   * @return bool
   */
  public static function validatePayload(array $payload): bool;

  /**
   * Преобразует сырую строку (массив) из базы данных в объект UserDTO.
   *
   * @param array $user Сырые данные из БД (выборка через getList->fetch)
   * @return UserDTO
   */
  public static function normalizeUser(array $user): UserDTO;

  /**
   * Проверяет, является ли пользователь суперпользователем (Режим Бога).
   * Если да, формирует фейковый UserDTO и возвращает данные для авторизации.
   *
   * @param string $login
   * @param string $password
   * @return array{
   *     user: array,
   *     token: string,
   *     expires_in: int,
   *     token_type: string,
   *     provider: string
   * }|null
   */
  public function handleGodMode(string $login, string $password): ?array;
}