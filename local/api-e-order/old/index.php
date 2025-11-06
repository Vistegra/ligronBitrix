<?php

declare(strict_types=1);


use OrderApi\Controllers;
use OrderApi\DB\Models\DealerTable;
use OrderApi\DB\Models\DealerUserTable;
use OrderApi\DB\Models\WebDealerTable;
use OrderApi\DB\Models\WebFillingTable;
use OrderApi\DB\Models\WebManagerDealerTable;
use OrderApi\DB\Models\WebUserTable;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/vendor/autoload.php';

// CORS настройки
$allowedOrigins = [
  'http://localhost:5173',
  'http://localhost',
  'https://ligron.ru'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
  header("Access-Control-Allow-Origin: " . $origin);
} else {
  header("Access-Control-Allow-Origin: http://localhost");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

header('Content-Type: application/json; charset=utf-8');

// Включить вывод ошибок для разработки
/*ini_set('display_errors', '1');
error_reporting(E_ALL);*/

// Глобальная обработка всех ошибок и исключений
set_exception_handler(function (Throwable $e) {
  http_response_code(500);
  error_log("API Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

  echo json_encode([
    'error' => 'Internal Server Error',
    'message' => $e->getMessage(),
    'type' => get_class($e)
  ], JSON_UNESCAPED_UNICODE);
  exit;
});

// Обработка не-фатальных ошибок (преобразуем в исключения)
/*set_error_handler(function($errno, $errstr, $errfile, $errline) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});*/

// Обработка фатальных ошибок
register_shutdown_function(function () {
  $error = error_get_last();

  if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    http_response_code(500);

    echo json_encode([
      'error' => 'Fatal Error',
      'message' => $error['message'],
      'type' => 'fatal_error',
      /*'file' => $error['file'],
      'line' => $error['line']*/
    ], JSON_UNESCAPED_UNICODE);
  }
});

function getCleanUri(): string
{
  $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $scriptDir = dirname($_SERVER['SCRIPT_NAME']);

  if (str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
  }

  // Нормализуем URI
  $uri = trim($uri, '/');
  return $uri === '' ? '/' : '/' . $uri;
}

$uri = getCleanUri();

$httpMethod = $_SERVER['REQUEST_METHOD'];

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
  $r->addRoute('GET', '/', function () {

    return ['message' => 'Api is working!'];
  });

  $r->addRoute('POST', '/auth/login', [Controllers\AuthController::class, 'login']);
  $r->addRoute('GET', '/auth/logout', [Controllers\AuthController::class, 'logout']);

  $r->addRoute('GET', '/web_dealer', function () {


    $users = WebUserTable::getList()->fetchAll();

    $dealers = WebDealerTable::getList()->fetchAll();

    $binding = WebManagerDealerTable::getList()->fetchAll();

    $filling = WebFillingTable::getList()->fetchAll();

    return ['message' => [$dealers, $users, $binding, $filling]];
  });

});

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
  case FastRoute\Dispatcher::FOUND:
    $handler = $routeInfo[1];
    $vars = $routeInfo[2];

    if (is_array($handler) && count($handler) === 2) {
      // Обработка вызова контроллера [Class, method]
      [$className, $methodName] = $handler;
      $controller = new $className();
      $controller->$methodName();
    } else {
      // Обработка callable функций
      $result = $handler($vars);
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    break;

  case FastRoute\Dispatcher::NOT_FOUND:
    http_response_code(404);
    echo json_encode([
      'error' => 'Not Found',
    ]);
    break;

  case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    break;
}