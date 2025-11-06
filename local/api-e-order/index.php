<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/vendor/autoload.php';

// Глобальные обработчики
$logPath = __DIR__ . '/storage/logs/api.log';
@mkdir(dirname($logPath), 0755, true);

// 1. Исключения
set_exception_handler(function (Throwable $e) use ($logPath) {
  $log = new \Monolog\Logger('api');
  $log->pushHandler(new \Monolog\Handler\StreamHandler($logPath));
  $log->error($e);

  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');

  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage(),
    'type' => $e::class,
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ], JSON_UNESCAPED_UNICODE);
  exit;
});

// 2. Фатальные ошибки
register_shutdown_function(function () use ($logPath) {
  $error = error_get_last();
  if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    $log = new \Monolog\Logger('api');
    $log->pushHandler(new \Monolog\Handler\StreamHandler($logPath));
    $log->error("FATAL: " . $error['message'], $error);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
      'status' => 'error',
      'message' => $error['message'],
      'type' => 'fatal_error',
      'file' => $error['file'] ?? '?',
      'line' => $error['line'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
});

use DI\Container;
use OrderApi\Config\ApiConfig;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

use OrderApi\Middleware\{GlobalErrorMiddleware,
  CorsMiddleware,
  JsonResponseMiddleware,
  AuthMiddleware,
  TrailingSlashMiddleware};
use OrderApi\Controllers\{AuthController};

// DI
$container = new Container();
$container->set('logs', $logPath);

AppFactory::setContainer($container);


$app = AppFactory::create();
$app->setBasePath('/local/api-e-order');

// убираем слеш — до всех маршрутов
$app->add(TrailingSlashMiddleware::class);


$app->add(CorsMiddleware::class);

$app->options('/{routes:.+}', function ($request, $response) {
  return $response;
});

$app->add(GlobalErrorMiddleware::class);

$app->add(JsonResponseMiddleware::class);
$app->addBodyParsingMiddleware();

$app->add(function ($request, $handler) use ($logPath) {
  return $handler->handle($request->withAttribute('logPath', $logPath));
});


$app->post('/auth/login', AuthController::class . ':login');
$app->get('/auth/logout', AuthController::class . ':logout');

$app->get('', function ($request, $response) {
  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!'], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;//->withHeader('Content-Type', 'application/json');
});


$app->group('', function (RouteCollectorProxy $group) {
  $group->get('/orders', function ($request, $response) {
    $payload = json_encode(['status' => 'success', 'message' => 'orders!'], JSON_UNESCAPED_UNICODE);
    $response->getBody()->write($payload);
    return $response;
  });
})->add(AuthMiddleware::class);



$app->run();