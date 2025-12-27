<?php

declare(strict_types=1);

// Загрузка ядра Bitrix и Composer библиотек
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/vendor/autoload.php';

use DI\Container;
use OrderApi\Bootstrap\ErrorHandler;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\Middleware\{
  CorsMiddleware,
  GlobalErrorMiddleware,
  JsonResponseMiddleware,
  TrailingSlashMiddleware
};
use Slim\Factory\AppFactory;
use OrderApi\Config\ApiConfig;

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