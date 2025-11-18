<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Webhook1CController extends AbstractController
{
  private function logRequest(ServerRequestInterface $request, string $methodLabel): array
  {
    $query = $request->getQueryParams();
    $body  = $request->getParsedBody() ?? [];

    $logPath = $request->getAttribute('logPath');
    if ($logPath) {
      $logger = new Logger('webhook_1c');
      $logger->pushHandler(new StreamHandler($logPath));

      $logger->info("1C WEBHOOK [{$methodLabel}]", [
        'DATA_GET' => $query,
        'DATA_POST' => $body
      ]);
    }

    return [$query, $body];
  }

  public function get(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'GET');

    return $this->success('Данные получены', ['received_at' => date('c'), 'method' => 'get', 'query' => $query, 'body' => $body]);
  }

  public function post(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'POST');

    return $this->success('Данные получены', ['received_at' => date('c'), 'method' => 'post', 'query' => $query, 'body' => $body]);
  }

  public function put(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'PUT');
    return $this->success('Данные получены', ['received_at' => date('c'), 'method' => 'put', 'query' => $query, 'body' => $body]);
  }

  public function delete(ServerRequestInterface $request): ResponseInterface
  {
    [$query, $body] = $this->logRequest($request, 'DELETE');

    return $this->success('Данные получены', ['received_at' => date('c'), 'method' => 'delete', 'query' => $query, 'body' => $body]);
  }

}