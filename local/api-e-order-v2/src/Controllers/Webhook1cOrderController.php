<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use OrderApiV2\Helpers\BodyParser;
use OrderApiV2\Services\LogService;
use OrderApiV2\Services\Order\Webhook1cOrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Контроллер для обработки входящих вебхуков от 1С.
 */
final class Webhook1cOrderController extends AbstractController
{
  public function __construct(
    private readonly Webhook1cOrderService $webhook1cOrderService,
  ) {
  }

  /**
   * POST /webhook/1c/orders
   */
  public function post(ServerRequestInterface $request): ResponseInterface
  {
    $query = [];
    $body = [];

    try {
      [$query, $body] = $this->logRequest($request, 'POST');

      $action = $body['action'] ?? null;
      $type   = $body['type'] ?? null;

      return match (true) {
        // Создание заказа
        $type === 'ORDER' && $action === 'CREATE' => $this->handleCreateOrder($body, $query),

        // Обновление статуса или данных заказа
        in_array($type, ['STATUS', 'ORDER'], true) && $action === 'UPDATE' => $this->handleUpdateOrder($body, $query),

        // Неизвестное действие
        default => $this->success('Данные получены, но действие не распознано', [
          'received_at' => date('c'),
          'action'      => $action,
          'type'        => $type,
          'body'        => $body,
        ]),
      };

    } catch (\Throwable $e) {
      LogService::error($e, [
        'query' => $query,
        'body'  => $body
      ], 'webhook_1c');

      $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 400;

      return $this->error('Ошибка обработки вебхука: ' . $e->getMessage(), $code);
    }
  }

  /**
   * Обработка создания заказа
   * @throws \Exception
   */
  private function handleCreateOrder(array $body, array $query): ResponseInterface
  {
    $order = $this->webhook1cOrderService->createOrderFrom1C($body);

    return $this->success(
      'Заказ успешно создан в API',
      [
        'received_at' => date('c'),
        'order'       => $order,
        'body'        => $body
      ],
      201
    );
  }

  /**
   * Обработка обновления статуса заказа
   */
  private function handleUpdateOrder(array $body, array $query): ResponseInterface
  {
    $updatedOrder = $this->webhook1cOrderService->updateOrderFrom1C($body);

    return $this->success(
      'Данные заказа обновлены',
      [
        'received_at' => date('c'),
        'order'       => $updatedOrder
      ]
    );
  }

  /**
   * Вспомогательный метод для логов и парсинга
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

  // Методы GET, PUT, DELETE как заглушки
  public function get(ServerRequestInterface $request): ResponseInterface
  {
    return $this->json(['message' => 'Only POST is supported for webhooks'], 405);
  }

}