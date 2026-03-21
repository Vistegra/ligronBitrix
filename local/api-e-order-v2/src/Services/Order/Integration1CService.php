<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Order;

use GuzzleHttp\Client;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Repositories\AccessRepository;
use OrderApiV2\DB\Repositories\OrderFileRepository;
use OrderApiV2\DB\Repositories\OrderRepository;
use OrderApiV2\DB\Repositories\UserRepository;
use OrderApiV2\Services\LogService;
use Psr\Http\Message\ResponseInterface;

/**
 * Сервис интеграции с 1С.
 */
final class Integration1CService
{
  private Client $httpClient;

  public function __construct()
  {
    $this->httpClient = new Client([
      'timeout' => 120,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => 'Basic ' . base64_encode('http_service:L3g2n0_2')
      ]
    ]);
  }

  /**
   * Отправить заказ в 1C
   */
  public function sendOrder(int $orderId): ?array
  {
    $requestData = $this->buildRequestData($orderId);
    if (empty($requestData)) return null;

    try {
      $response = $this->httpClient->post(ApiConfig::INTEGRATION_1C_ORDER_URL, [
        'json' => $requestData
      ]);

      return $this->parseResponse($response);

    } catch (\Throwable $e) {
      LogService::error($e, ['order_id' => $orderId], '1c_integration');
      return null;
    }
  }

  /**
   * Сформировать данные для запроса в 1C
   */
  public function buildRequestData(int $orderId): array
  {
    $order = OrderRepository::getById($orderId);
    if (!$order) return [];

    $files = OrderFileRepository::getByOrderId($orderId) ?? [];
    $context = $this->resolveOrderContext($order);

    return [
      'buildVersion'     => ApiConfig::API_DATE_VERSION,
      'releaseType'      => ApiConfig::API_MODE,
      'date'             => date('d.m.Y'),
      'order_link'       => ApiConfig::APP_ORDERS_PAGE . '/' . $order['id'],
      'order_type'       => 112,
      'client_in_number' => [
        'name'    => $context['user_name'],
        'phone'   => $context['user_phone'],
        'email'   => $context['user_email'],
        'comment' => $order['comment'] ?? ''
      ],
      'manager'        => $context['office_manager_code'],
      'salon'          => $context['salon_code'],
      'client'         => $context['inn_dealer'],
      'order_number'   => $this->generateOrderNumber($context),
      'order_link_jpg' => $this->buildFileUrls($files),
    ];
  }

  private function resolveOrderContext(array $order): array
  {
    $inn    = (string)($order['inn_dealer'] ?? '');
    $salon  = (string)($order['salon_code'] ?? '');
    $userId = (int)($order['dealer_user_id'] ?? 0);

    if (!$inn || !$salon || !$userId) {
      throw new \RuntimeException("Данные заказа #{$order['id']} неполные (ИНН, Салон или ID пользователя отсутствуют)");
    }

    // Ищем данные пользователя по ID в новой таблице
    $dealerUser = DealerUserTable::getByPrimary($userId)->fetch();

    $managers = AccessRepository::getLigronManagersForInns([$inn]);

    $officeManagerCode = '';
    foreach ($managers as $m) {
      if ($m['role'] === 'office_manager') {
        $officeManagerCode = $m['code_user'];
        break;
      }
    }

    return [
      'inn_dealer'          => $inn,
      'salon_code'          => $salon,
      'office_manager_code' => $officeManagerCode,
      'user_name'           => $dealerUser['name'] ?? 'Не указано',
      'user_phone'          => $dealerUser['phone'] ?? '',
      'user_email'          => $dealerUser['email'] ?? '',
      'manager_username'    => $dealerUser['username'] ?? '' // Отправляем логин обратно в 1С
    ];
  }

  private function generateOrderNumber(array $ctx): string
  {
    $year   = date('y');
    $random = rand(1000, 9999);
    return $ctx['inn_dealer'] . $ctx['salon_code'] . $year . $random . '-1';
  }

  private function buildFileUrls(array $files): array
  {
    $root = rtrim(ApiConfig::SITE_ROOT_URL, '/');
    return array_map(fn($f) => $root . $f['path'] . $f['name'], $files);
  }

  private function parseResponse(ResponseInterface $response): ?array
  {
    $contents = $response->getBody()->getContents();
    $data = json_decode($contents, true);

    if (!$data || ($data['error'] ?? false)) {
      LogService::error('Error 1C response', ['raw' => $contents], '1c_integration');
      return null;
    }

    return $data;
  }
}