<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Bitrix\Main\Type\DateTime;
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
    $body = $request->getParsedBody();

    // Если стандартный парсер Slim не нашел данные (из-за BOM или отсутствия Content-Type)
    if (empty($body)) {
      $rawContent = (string)$request->getBody();

      if ($rawContent !== '') {
        // удаляем BOM (Byte Order Mark)
        $cleanContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

        $decoded = json_decode($cleanContent, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          $body = $decoded;
        } else {
          // Логируем ошибку парсинга JSON
          LogService::warn("1C WEBHOOK [{$methodLabel}]: JSON Decode Fail", ['raw' => $rawContent], 'webhook_1c');
        }
      }

    }

    // Гарантируем, что body - массив
    $body = is_array($body) ? $body : [];

    LogService::info(
      "1C WEBHOOK [{$methodLabel}]",
      [
        'DATA_GET' => $query,
        'DATA_POST' => $body,
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
      $action = $body['action'] ?? null;
      $type = $body['type'] ?? null;

      // Маршрутизация на основе action и type
      return match (true) {
        $action === 'UPDATE' && $type === 'STATUS' => $this->handleUpdateStatus($body, $query),

        default => $this->success('Данные получены, но действие не распознано или не требует обработки', [
          'received_at' => date('c'),
          'method' => 'post',
          'query' => $query,
          'body' => $body,
        ])
      };

    } catch (\Throwable $e) {
      LogService::error($e, [
        'query' => $query,
        'body' => $body],
        'webhook_1c');

      return $this->error('Ошибка обработки вебхука: ' . $e->getMessage());
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

  /**
   * Обработка обновления статуса заказа
   */
  private function handleUpdateStatus(array $body, array $query): ResponseInterface
  {
    $orderNumber = $body['ligron_number'] ?? null;

    if (!$orderNumber) {
      throw new \RuntimeException('Не передан номер заказа (ligron_number)!');
    }

    $statusCode = $body['status_code'] ?? null;
    $statusDate = $body['status_date'] ?? (new DateTime())->toString();

    if (!$statusCode) {
      throw new \RuntimeException('Не передан код статуса (status_code)!');
    }


    $extraData = [];

    if (!empty($body['production_date'])) {
      $extraData['ready_date'] = (string)$body['production_date'];
    }

    if (isset($body['production_time'])) {
      $extraData['production_time'] = (int)$body['production_time'];
    }

    if (isset($body['percent_payment'])) {
      $extraData['percent_payment'] = (int)$body['percent_payment'];
    }

    $updatedOrder = $this->webhook1cOrderService->updateStatusByNumber(
      (string)$orderNumber,
      (string)$statusCode,
      (string)$statusDate,
      $extraData
    );

    return $this->success('Статус заказа успешно обновлен',
      [
        'received_at' => date('c'),
        'method' => 'post',
        'query' => $query,
        'body' => $body,
        'order' => $updatedOrder,
      ]
    );
  }

}