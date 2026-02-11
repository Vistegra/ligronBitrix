<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use OrderApi\Controllers\{AuthController,
  DocsController,
  LogViewerController,
  NotificationController,
  OrderController,
  Webhook1cOrderController};
use OrderApi\Middleware\AuthMiddleware;

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


  /** ТЕСТОВЫЕ ЭНДПОИНТЫ И ОТЛАДКА */
  // Публичные отладочные роуты (БД)
  $app->get('/web_users', function ($request, $response) {
    // Осторожно: прямой доступ к репозиториям в роуте (только для дебага)
    $dealer = \OrderApi\DB\Repositories\DealerUserRepository::getDealerByPrefix('pin', ['select' => ['id']]);
    $users = \OrderApi\DB\Models\WebUserTable::getList(['limit' => 20])->fetchAll();
    $links = \OrderApi\DB\Models\WebManagerDealerTable::getList(['limit' => 20])->fetchAll();

    $payload = json_encode([
      'status' => 'success',
      'data' => ['dealer' => $dealer, 'users' => $users, 'links' => $links]
    ], JSON_UNESCAPED_UNICODE);

    $response->getBody()->write($payload);
    return $response;
  });

};