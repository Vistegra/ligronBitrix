<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Bitrix\Main\Type\DateTime;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OrderApi\Services\LogService;
use OrderApi\Services\Order\Webhook1cOrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Webhook1cOrderController extends AbstractController
{
  public function __construct(
    private readonly Webhook1cOrderService $webhook1cOrderService,
  )
  {
  }
  private function logRequest(ServerRequestInterface $request, string $methodLabel): array
  {
    $query = $request->getQueryParams();
    $body  = $request->getParsedBody() ?? [];
    $origin = $request->getHeaderLine('Origin');

    LogService::info(
      "1C WEBHOOK [{$methodLabel}]",
      [
        'DATA_GET' => $query,
        'DATA_POST' => $body,
        'ORIGIN' => $origin,
      ],
      'webhook_1c'
    );

    return [$query, $body];
  }

  public function get(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'GET');

    return $this->success('Данные получены, но не обработаны', ['received_at' => date('c'), 'method' => 'get', 'query' => $query, 'body' => $body]);
  }

  public function post(ServerRequestInterface $request): ResponseInterface
  {

    [$query, $body] = $this->logRequest($request, 'POST');

    try {
      //ToDo "action":"UPDATE","type":"STATUS"
      $orderNumber = $body['ligron_number'];

      if (!$orderNumber) {
        throw new \RuntimeException('Не передан номер заказа!');
      }

      $statusCode = $body['status_code'];
      $statusDate = $body['status_date'] ?? (new DateTime())->toString();

      if (!$statusCode) {
        throw new \RuntimeException('Не передан статус заказа!');
      }

      $updatedOrder = $this->webhook1cOrderService->updateStatusByNumber((string)$orderNumber, (string)$statusCode, $statusDate);

      return $this->success('Данные получены, и обработаны. Статус заказа обновлен.',
        [
          'received_at' => date('c'),
          'method' => 'post',
          'query' => $query,
          'body' => $body,
          'order' => $updatedOrder,
        ]
      );

    } catch (\Throwable $e) {
      return $this->error('Данные получены, но произошла ошибка: ' . $e->getMessage());
    }

  }

  public function put(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'PUT');
    return $this->success('Данные получены', ['received_at' => date('c'), 'method' => 'put', 'query' => $query, 'body' => $body]);
  }

  public function delete(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'DELETE');

    return $this->success('Данные получены, но не обработаны', ['received_at' => date('c'), 'method' => 'delete', 'query' => $query, 'body' => $body]);
  }

}