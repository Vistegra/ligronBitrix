<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Контроллер-заглушка для имитации ответов 1С
 */
final class Fake1CWebhookController extends AbstractController
{
  /**
   * POST /fake-1c-webhook
   */
  public function post(ServerRequestInterface $request): ResponseInterface
  {
    $input = $request->getParsedBody() ?? [];

    if (empty($input['order_number']) || empty($input['client'])) {
      return $this->error('Недостаточно данных для имитации ответа 1С (нужны order_number и client)', 400);
    }

    $ligronNumber = 'FK' . date('Ymd') . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT);

    $responseData = [
      'ligron_number' => $ligronNumber,
      'error'         => false,
      'message'       => 'Заказ успешно создан в (фейковой) 1С',
      'date'          => date('d.m.Y'),

      'status_zakaza' => [
        'status_code' => 100, //Получен (https://ligron.ru/local/api-e-order/docs/statuses)
        'status_date' => date('d.m.Y H:i:s'),
      ],

      'order_number' => $input['order_number'],
      'client'       => $input['client'],
      'salon'        => $input['salon'] ?? 'unknown',
    ];

    return $this->json($responseData);
  }
}