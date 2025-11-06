<?php

declare(strict_types=1);

namespace OrderApi\Config;

final class ApiConfig
{
  public const string JWT_SECRET = 'ghSiBVUEWx5FZcK6BzFHDTrbdQjexAck';
  public const string JWT_ALGO = 'HS256';
  public const int JWT_EXPIRE = 3600; // 1 час

  public const string API_VERSION = '1.0';
  public const string API_NAME = 'Order API';

  private function __construct() {}
}