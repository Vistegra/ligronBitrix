<?php

declare(strict_types=1);

// Загрузка ядра Bitrix и Composer библиотек
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/vendor/autoload.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/api-e-order/src/DB/MssqlConnectionTrust.php'; //ToDo удалить после миграции

use DI\Container;
use OrderApiV2\Bootstrap\ErrorHandler;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Middleware\{
  CorsMiddleware,
  GlobalErrorMiddleware,
  JsonResponseMiddleware,
  TrailingSlashMiddleware
};
use Slim\Factory\AppFactory;
use OrderApiV2\Config\ApiConfig;

/** РЕГИСТРАЦИЯ ОБРАБОТЧИКА ОШИБОК и ЛОГГЕРА */
ErrorHandler::register(__DIR__ . ApiConfig::APP_STORAGE_PATH);

/**  СБОРКА ПРИЛОЖЕНИЯ */

// DI Container
$container = new Container();
$container->set(UserDTO::class, fn() => null);
AppFactory::setContainer($container);

// App Instance
$app = AppFactory::create();
$app->setBasePath(ApiConfig::APP_PATH);

// Middleware (LIFO порядок)
$app->add(TrailingSlashMiddleware::class);
$app->add(GlobalErrorMiddleware::class); // Ловит ошибки 4xx от Slim
$app->add(CorsMiddleware::class);
$app->add(JsonResponseMiddleware::class);
$app->addBodyParsingMiddleware();

// Подключение маршрутов (из файла src/Config/routes.php)
$routes = require __DIR__ . '/routes.php';
$routes($app);

// Запуск приложения
$app->run();