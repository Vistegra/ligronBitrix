<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Fake1CWebhookController extends AbstractController
{
  // POST /fake-1c-webhook
  public function post(ServerRequestInterface $request): ResponseInterface
  {
    $input = $request->getParsedBody() ?? [];

    // Валидация минимальная (для фейка опционально)
    if (empty($input['order_number']) || empty($input['client']) || empty($input['salon'])) {
      return $this->error('Недостаточно данных для обработки', 400);
    }

    // Генерация случайного ligron_number (от 1,000,000)
    $ligronNumber = (string) rand(1000000, 9999999);

    // Дата - используем текущую или из входных данных
    $date = $input['date'] ?? date('d.m.Y');

    // Формируем ответ
    $responseData = [
      'date' => $date,
      'order_number' => $input['order_number'],
      'client_in_number' => '',
      'ligron_number' => $ligronNumber,
      'client' => $input['client'],
      'salon' => $input['salon'],
    ];

    return $this->json($responseData);
  }
}