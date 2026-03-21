<?php

namespace OrderApiV2\Services\Auth\Token;

use OrderApiV2\DTO\Auth\UserDTO;

interface AuthProviderInterface
{
  public const string PROVIDER = '';
  public function login(string $login, string $password): ?array;
  public static function validatePayload(array $payload): bool;
  public static function normalizeUser(array $user): UserDTO;
}