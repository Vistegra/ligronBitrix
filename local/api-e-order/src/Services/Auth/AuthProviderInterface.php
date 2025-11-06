<?php

namespace OrderApi\Services\Auth;

interface AuthProviderInterface
{
  public const string PROVIDER = '';
  public function login(string $login, string $password): ?array;
  public static function validatePayload(array $payload): bool;

  public static function normalizeUser(array $user): array;
}