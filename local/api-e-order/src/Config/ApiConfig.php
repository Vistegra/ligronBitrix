<?php

declare(strict_types=1);

namespace OrderApi\Config;

final class ApiConfig
{
  public const string JWT_SECRET = 'ghSiBVUEWx5FZcK6BzFHDTrbdQjexAck';
  public const string MANAGER_SECRET = 'h74zh2yLsQsKbwsarnfLANuFUBMziYeX'; //Нельзя изменять!
  public const string JWT_ALGO = 'HS256';
  public const int JWT_EXPIRE = 3600; // 1 час

  public const string API_VERSION = '1.0';
  public const string API_NAME = 'Order API';
  public const string UPLOAD_FILES_DIR = '/upload/e-order/files/';
  public const string API_DATE_VERSION = '28.11.2025';
  public const string  APP_ORDERS_PAGE = 'https://ligron.ru/e-order/#/orders';

  private function __construct() {}
}