<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use OrderApiV2\Controllers\{AuthController,
  CacheController,
  DocsController,
  LogViewerController,
  OrderController,
  Webhook1cOrderController};
use OrderApiV2\Middleware\AuthMiddleware;

return function (App $app) {

  /** ГЛАВНАЯ СТРАНИЦА */
  $app->get('', function ($request, $response) {
    $payload = json_encode(['status' => 'success', 'message' => 'Api is working!'], JSON_UNESCAPED_UNICODE);
    $response->getBody()->write($payload);
    return $response;
  });

  /** АВТОРИЗАЦИЯ (ПУБЛИЧНЫЕ МЕТОДЫ) */
  $app->post('/auth/login', AuthController::class . ':login');
  $app->post('/auth/login-by-token', AuthController::class . ':loginByToken');
  $app->post('/auth/crypt', AuthController::class . ':crypt');

  /** АВТОРИЗАЦИЯ (ЗАЩИЩЕННЫЕ МЕТОДЫ) */
  $app->get('/auth/me', AuthController::class . ':me')->add(AuthMiddleware::class);
  $app->get('/auth/sso', AuthController::class . ':sso')->add(AuthMiddleware::class);


  /** ГРУППА ЗАЩИЩЕННЫХ МАРШРУТОВ (API) */
  $app->group('', function (RouteCollectorProxy $api) {

    // Справочники
    $api->get('/statuses', OrderController::class . ':getStatuses');

    /** ЗАКАЗЫ */
    $api->group('/orders', function (RouteCollectorProxy $orders) {

      // Список и Создание
      $orders->get('', OrderController::class . ':getAll');
      $orders->post('', OrderController::class . ':create');

      // Получить заказ по номеру Ligron
      $orders->get('/number/{number}', [OrderController::class, 'getByNumber']);

      // Операции с конкретным заказом по ID (валидация: только цифры)
      $orders->group('/{id:[0-9]+}', function (RouteCollectorProxy $order) {
        // CRUD операции
        $order->get('', OrderController::class . ':get');
        $order->put('', OrderController::class . ':update');
        $order->delete('', OrderController::class . ':delete');

        // действия
        $order->post('/status', OrderController::class . ':changeStatus');
        $order->post('/send-to-ligron', OrderController::class . ':sendToLigron');
        $order->get('/ligron-request-data', OrderController::class . ':getLigronRequestData');

        // Работа с файлами заказа
        $order->post('/files', OrderController::class . ':uploadFiles');
        $order->delete('/files/{fileId}', OrderController::class . ':deleteFile');
      });
    });

    // Тест отправки заказа
    $api->post('/fake-1c-webhook', \OrderApiV2\Controllers\Fake1CWebhookController::class . ':post');

    /** УВЕДОМЛЕНИЯ */
    /*$api->group('/notifications', function (RouteCollectorProxy $notify) {
      $notify->get('', NotificationController::class . ':index');                        // Список
      $notify->get('/unread-count', NotificationController::class . ':getUnreadCount');  // Счетчик
      $notify->post('/read-all', NotificationController::class . ':readAll');            // Прочитать все
      $notify->post('/{id}/read', NotificationController::class . ':readOne');           // Прочитать одно
    });*/

  })->add(AuthMiddleware::class);

  $app->get('/logs/webhook', LogViewerController::class . ':index');


  /** ПУБЛИЧНЫЕ ВЕБХУКИ (1С) */
  $app->group('/webhook/1c/orders', function (RouteCollectorProxy $group) {
    $group->get('', Webhook1cOrderController::class . ':get');
    $group->post('', Webhook1cOrderController::class . ':post');
    $group->put('', Webhook1cOrderController::class . ':put');
    $group->delete('', Webhook1cOrderController::class . ':delete');
  });


  /** ДОКУМЕНТАЦИЯ */
  $app->group('/docs', function (RouteCollectorProxy $group) {
    $group->get('/', [DocsController::class, 'index']);
    $group->get('', [DocsController::class, 'index']);
    $group->get('/{page}', [DocsController::class, 'page']);
  });

  /** ИНСТРУМЕНТЫ */
  $app->group('/tools', function (RouteCollectorProxy $group) {
    $group->get('/cache/clear', CacheController::class . ':clear');
  });

  $app->group('/tools/migration', function (RouteCollectorProxy $group) {
    //ToDo! удалить после ввода в эксплуатацию
    $group->get('/dictionaries', \OrderApiV2\Controllers\MigrationCheckController::class . ':dictionaries');
    $group->get('/orders', \OrderApiV2\Controllers\MigrationCheckController::class . ':orders');
    $group->get('/tree', \OrderApiV2\Controllers\MigrationCheckController::class . ':hierarchy');
    $group->get('/tree-check', \OrderApiV2\Controllers\MigrationCheckController::class . ':hierarchyCheck');
  });

};