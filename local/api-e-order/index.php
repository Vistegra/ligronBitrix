<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/vendor/autoload.php';

// Глобальные обработчики
$logPath = __DIR__ . '/storage/logs/api.log';
@mkdir(dirname($logPath), 0755, true);

$logger = new \Monolog\Logger('api');
$logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath));

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

use OrderApi\DB\Models\WebFillingTable;
use OrderApi\DTO\Auth\UserDTO;
use DI\Container;
use OrderApi\Config\ApiConfig;
use OrderApi\Services\Order\OrderService;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Routing\RouteCollectorProxy;

use OrderApi\Middleware\{GlobalErrorMiddleware,
  CorsMiddleware,
  JsonResponseMiddleware,
  AuthMiddleware,
  TrailingSlashMiddleware};
use OrderApi\Controllers\{AuthController, OrderController, Webhook1CController};

// DI
$container = new Container();

$container->set(UserDTO::class, function () {
  return null;
  //ToDo
});

$container->set('logs', $logPath);

AppFactory::setContainer($container);


$app = AppFactory::create();
$app->setBasePath('/local/api-e-order');
// Включаем аргументы ввыде массива
//$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseArgs());
// убираем слеш — до всех маршрутов
$app->add(TrailingSlashMiddleware::class);

/*$app->options('/{routes:.+}', function ($request, $response) {
  return $response;
});*/

$app->add(GlobalErrorMiddleware::class);
$app->add(CorsMiddleware::class);
$app->add(JsonResponseMiddleware::class);

$app->addBodyParsingMiddleware();

$app->add(function ($request, $handler) use ($logPath) {
  return $handler->handle($request->withAttribute('logPath', $logPath));
});


$app->post('/auth/login', AuthController::class . ':login');
$app->post('/auth/login-by-token', AuthController::class . ':loginByToken');
$app->get('/auth/check', AuthController::class . ':check')->add(AuthMiddleware::class);
$app->get('/auth/me', AuthController::class . ':me')->add(AuthMiddleware::class);
$app->post('/auth/crypt', AuthController::class . ':crypt');

$app->get('', function ($request, $response) {
  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!'], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;//->withHeader('Content-Type', 'application/json');
});

$app->get('/web_users', function ($request, $response) {
  $data = \OrderApi\DB\Models\WebUserTable::getList(['limit' => 20])->fetchAll();

  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!',
    'data' => $data], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;//->withHeader('Content-Type', 'application/json');
});

$app->get('/user_detailed', function ($request, $response) {
  $query = $request->getQueryParams();
  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!', 'data' => \OrderApi\DB\Repositories\DealerUserRepository::findDetailedUserByIds((int)$query['userId'],(int)$query['dealerId'])], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;//->withHeader('Content-Type', 'application/json');
});

$app->get('/session', function ($request, $response) {

  $payload = json_encode([

    'status' => 'success',
    'message' => 'Api is working!',
    'data' => \OrderApi\Services\Auth\Session\AuthSession::all(),
    'salon_code' => \OrderApi\Services\Auth\Session\AuthSession::getSalonCode()
    ]);

  $response->getBody()->write($payload);
  return $response;//->withHeader('Content-Type', 'application/json');
})->add(AuthMiddleware::class);

$app->group('', function (RouteCollectorProxy $group) {
  $group->get('/statuses', OrderController::class . ':getStatuses');
  $group->post('/orders', OrderController::class . ':create');                    // Создать заказ + файлы
  $group->get('/orders', OrderController::class . ':getAll');                     // Список заказов
  $group->get('/orders/{id}', OrderController::class . ':get');                   // Получить заказ
  $group->put('/orders/{id}', OrderController::class . ':update');                // Обновить заказ
  $group->delete('/orders/{id}', OrderController::class . ':delete');             // Удалить заказ

  $group->post('/orders/{id}/status', OrderController::class . ':changeStatus');  // Сменить статус

  $group->post('/orders/{id}/files', OrderController::class . ':uploadFiles');    // Загрузить файлы к заказу
  $group->delete('/orders/{id}/files/{fileId}', OrderController::class . ':deleteFile'); // Удалить файл

  $group->post('/orders/{id}/send-to-ligron', OrderController::class . ':sendToLigron'); //Преобразовать в заказ, получить номер

})->add(AuthMiddleware::class);

// Вебхук от 1С — без авторизации, отдельные методы
$app->get(    '/webhook/1c/orders', Webhook1CController::class . ':get');
$app->post(   '/webhook/1c/orders', Webhook1CController::class . ':post');
$app->put(    '/webhook/1c/orders', Webhook1CController::class . ':put');
$app->delete( '/webhook/1c/orders', Webhook1CController::class . ':delete');



$app->run();