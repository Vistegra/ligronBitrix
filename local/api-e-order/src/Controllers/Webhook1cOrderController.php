<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\Helpers\BodyParser;
use OrderApi\Services\LogService;
use OrderApi\Services\Order\Webhook1cOrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Webhook1cOrderController extends AbstractController
{
  public function __construct(
    private readonly Webhook1cOrderService $webhook1cOrderService,
  ) {
  }

  /**
   * @throws \JsonException ошибка парсинга тела запроса
   */
  private function logRequest(ServerRequestInterface $request, string $methodLabel): array
  {
    $query = $request->getQueryParams();
    $body = BodyParser::parse($request);

    LogService::info(
      "1C WEBHOOK [{$methodLabel}]",
      ['DATA_GET' => $query, 'DATA_POST' => $body],
      'webhook_1c'
    );

    return [$query, $body];
  }


  public function post(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'POST');

    try {

      $action = $body['action'] ?? null;
      $type   = $body['type'] ?? null;

      return match (true) {
        // 1. Создание заказа
        $type === 'ORDER' && $action === 'CREATE' => $this->handleCreateOrder($body, $query),

        // 2. Обновление статуса (и заказа)
        $type === 'STATUS' && $action === 'UPDATE' => $this->handleUpdateStatus($body, $query),

        // 3. Неизвестное действие
        default => $this->success('Данные получены, но действие не распознано', [
          'received_at' => date('c'),
          'method'      => 'post',
          'action'      => $action,
          'type'        => $type,
          'query'       => $query,
          'body'        => $body,
        ]),
      };

    } catch (\Throwable $e) {
      LogService::error($e, [
        'query' => $query,
        'body' => $body],
        'webhook_1c');

      return $this->error('Ошибка обработки вебхука: ' . $e->getMessage());
    }

  }


  /**
   * Обработка создания заказа
   */
  private function handleCreateOrder(array $body, array $query): ResponseInterface
  {
    $order = $this->webhook1cOrderService->createOrderFrom1C($body);

    return $this->success(
      'Заказ успешно создан',
      [
        'received_at' => date('c'),
        'method'      => 'post',
        'query'       => $query,
        'body'        => $body,
        'order'    => $order
      ],
      201
    );
  }

  /**
   * Обработка обновления статуса заказа
   * @throws \Exception
   */
  private function handleUpdateStatus(array $body, array $query): ResponseInterface
  {
    $updatedOrder = $this->webhook1cOrderService->updateOrderFrom1C($body);

    return $this->success(
      'Статус заказа успешно обновлен',
      [
        'received_at' => date('c'),
        'method'      => 'post',
        'query'       => $query,
        'body'        => $body,
        'order'    => $updatedOrder
      ]
    );
  }


  //Незадействованные методы
  public function get(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'GET');

    return $this->success('Данные получены, но не обработаны', [
      'received_at' => date('c'),
      'method'      => 'get',
      'query'       => $query,
      'body'        => $body
    ]);
  }

  public function put(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'PUT');

    return $this->success('Данные получены, но не обработаны', [
      'received_at' => date('c'),
      'method'      => 'put',
      'query'       => $query,
      'body'        => $body
    ]);
  }

  public function delete(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'DELETE');

    return $this->success('Данные получены, но не обработаны', [
      'received_at' => date('c'),
      'method'      => 'delete',
      'query'       => $query,
      'body'        => $body
    ]);
  }
}