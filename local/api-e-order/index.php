<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/vendor/autoload.php';

use DI\Container;
use OrderApi\Controllers\{AuthController, DocsController, OrderController, Webhook1cOrderController};
use OrderApi\DTO\Auth\UserDTO;

use OrderApi\Middleware\{AuthMiddleware,
  CorsMiddleware,
  GlobalErrorMiddleware,
  JsonResponseMiddleware,
  TrailingSlashMiddleware};

use OrderApi\Services\LogService;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;


//Инициализация логгера приложения
$logDir = __DIR__ . '/storage/logs/';
LogService::setLogDir($logDir);

// Глобальные обработчики
// 1. Исключения
set_exception_handler(function (Throwable $e) {
  LogService::error($e);

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
register_shutdown_function(function () {
  $error = error_get_last();
  if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {

    LogService::error("FATAL: " . $error['message'], $error);

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



// DI
$container = new Container();

$container->set(UserDTO::class, function () {
  return null;
});

AppFactory::setContainer($container);


$app = AppFactory::create();
$app->setBasePath('/local/api-e-order');


$app->add(TrailingSlashMiddleware::class);
$app->add(GlobalErrorMiddleware::class);
$app->add(CorsMiddleware::class);
$app->add(JsonResponseMiddleware::class);

$app->addBodyParsingMiddleware();

$app->get('', function ($request, $response) {
  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!'], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;
});


$app->post('/auth/login', AuthController::class . ':login');
$app->post('/auth/login-by-token', AuthController::class . ':loginByToken');
$app->get('/auth/me', AuthController::class . ':me')->add(AuthMiddleware::class);
$app->get('/auth/sso', AuthController::class . ':sso')->add(AuthMiddleware::class);
$app->post('/auth/crypt', AuthController::class . ':crypt');

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

  $group->get('/orders/number/{number}', [OrderController::class, 'getByNumber']); //получить заказ по номеру
  $group->get('/orders/{id}/ligron-request-data', OrderController::class . ':getLigronRequestData'); //Получить json данные отправки в Ligron

})->add(AuthMiddleware::class);

// Вебхук от 1С — без авторизации, отдельные методы
$app->get(    '/webhook/1c/orders', Webhook1cOrderController::class . ':get');
$app->post(   '/webhook/1c/orders', Webhook1cOrderController::class . ':post');
$app->put(    '/webhook/1c/orders', Webhook1cOrderController::class . ':put');
$app->delete( '/webhook/1c/orders', Webhook1cOrderController::class . ':delete');

// Документация
$app->group('/docs', function (RouteCollectorProxy $group) {
  // Главная страница документации
  $group->get('/', [DocsController::class, 'index']);
  $group->get('', [DocsController::class, 'index']);

  // Конкретная страница
  $group->get('/{page}', [DocsController::class, 'page']);
});


/** ТЕСТОВЫЕ ЭНДПОИНТЫ */
$app->post('/fake-1c-webhook', \OrderApi\Controllers\Fake1CWebhookController::class . ':post');

$app->get('/integration/send', function ($request, $response) {
  $query = $request->getQueryParams();
  $orderId = (int)$query['orderId'];

  $service = new \OrderApi\Services\Order\Integration1CService($request->getAttribute('user'));

  $result = $service->sendOrder($orderId);

  $payload = json_encode(
    ['data' => $result],
    JSON_UNESCAPED_UNICODE
  );

  $response->getBody()->write($payload);
  return $response;
})->add(AuthMiddleware::class);

$app->get('/integration', function ($request, $response) {
  $query = $request->getQueryParams();
  $orderId = (int)$query['orderId'];
  $order = \OrderApi\DB\Repositories\OrderRepository::getById($orderId);
  $files = \OrderApi\DB\Repositories\OrderFileRepository::getByOrderId($orderId);

  $service = new \OrderApi\Services\Order\Integration1CService($request->getAttribute('user'));

  $result = $service->buildRequestData($order, $files);

  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!',
    'data' => [
      'order' => $order,
      'files' => $files,
      'result' => $result
    ]],
    JSON_UNESCAPED_UNICODE
  );

  $response->getBody()->write($payload);
  return $response;
})->add(AuthMiddleware::class);


$app->get('/web_users', function ($request, $response) {
  $dealer = \OrderApi\DB\Repositories\DealerUserRepository::getDealerByPrefix('pin', ['select' => ['id']]);
  $users =  \OrderApi\DB\Models\WebUserTable::getList(['limit' => 20])->fetchAll();
  $links =  \OrderApi\DB\Models\WebManagerDealerTable::getList(['limit' => 20])->fetchAll();

  $payload = json_encode(['status' => 'success', 'message' => 'Api is working!',
    'data' => ['dealer' => $dealer,'users' => $users, 'links' => $links]], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response;
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

/** /ТЕСТОВЫЕ ЭНДПОИНТЫ */

$app->run();