<?php

declare(strict_types=1);

namespace OrderApi\Config;

final class ApiConfig
{
  public const string JWT_SECRET = 'ghSiBVUEWx5FZcK6BzFHDTrbdQjexAck';
  public const string MANAGER_SECRET = 'h74zh2yLsQsKbwsarnfLANuFUBMziYeX'; //Нельзя изменять!
  public const string SSO_CALC_ENCRYPT_KEY = 'eKey';
  public const string SSO_CALC_ALGO = 'aes-256-cbc';
  public const string JWT_ALGO = 'HS256';
  public const int    JWT_EXPIRE = 36000; // 10 часов

  public const string API_VERSION = '1.0';
  public const string API_NAME = 'Order API';
  public const string UPLOAD_FILES_DIR = '/upload/e-order/files/';
  public const string API_DATE_VERSION = '03.12.2025';
  public const string SITE_ROOT_URL = 'https://ligron.ru';
  public const string  APP_ORDERS_PAGE = 'https://ligron.ru/e-order/orders';
  public const string CALC_URL = 'https://calculator.ligron.ru';

  //'https://ligron.ru/local/api-e-order/fake-1c-webhook'
  public const string  INTEGRATION_1C_ORDER_URL = 'http://193.43.248.24:8989/transitDB/hs/transit1c/get_d';

  public const string API_MODE = 'dev';
  public const string APP_PATH = '/local/api-e-order';
  public const string APP_STORAGE_PATH = '/storage/logs/';

  private function __construct()
  {
  }
}