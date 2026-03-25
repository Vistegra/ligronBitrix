<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Order;

use GuzzleHttp\Client;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\LigronUserTable;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Repositories\AccessRepository;
use OrderApiV2\DB\Repositories\OrderFileRepository;
use OrderApiV2\DB\Repositories\OrderRepository;
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
      'timeout' => 119,
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode('http_service:L3g2n0_2') //ToDo перенести в конфиг
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
      'buildVersion' => ApiConfig::API_DATE_VERSION,
      'releaseType' => ApiConfig::API_MODE,
      'date' => date('d.m.Y'),
      'order_link' => ApiConfig::APP_ORDERS_PAGE . '/' . $order['id'],
      'order_type' => 112,
      'client_in_number' => [
        'name' => $context['user_name'],
        'phone' => $context['user_phone'],
        'email' => $context['user_email'],
        'comment' => $order['comment'] ?? ''
      ],
      'manager' => $context['office_manager_code'],
      'salon' => $context['salon_code'],
      'client' => $context['inn_dealer'],
      'order_number' => $this->generateOrderNumber($context),
      'order_link_jpg' => $this->buildFileUrls($files),
    ];
  }

  /**
   * Собрать контекст заказа (пользователь, дилер, офис-менеджер)
   */
  private function resolveOrderContext(array $order): array
  {
    $inn = (string)($order['inn_dealer'] ?? '');
    $salon = (string)($order['salon_code'] ?? '');
    $authorId = (int)($order['author_id'] ?? 0);
    $createdBy = (int)($order['created_by'] ?? 0);

    if (!$inn || !$salon || !$authorId) {
      throw new \RuntimeException("Данные заказа №{$order['id']} неполные (ИНН, Салон или ID автора отсутствуют)");
    }

    // Определяем данные автора (Дилер или Менеджер Лигрон)
    $authorData = ['name' => 'Не указано', 'phone' => '', 'email' => '', 'username' => ''];

    if ($createdBy === OrderTable::CREATED_BY_DEALER) {
      $user = DealerUserTable::getByPrimary($authorId)->fetch();
      if ($user) {
        $authorData = [
          'name' => $user['name'] ?? 'Не указано',
          'phone' => $user['phone'] ?? '',
          'email' => $user['email'] ?? '',
          'username' => $user['username'] ?? '',
        ];
      }
    } elseif ($createdBy === OrderTable::CREATED_BY_MANAGER) {
      $user = LigronUserTable::getByPrimary($authorId)->fetch();
      if ($user) {
        $authorData = [
          'name' => $user['name'] ?? 'Не указано',
          'phone' => $user['phone'] ?? '',
          'email' => $user['email'] ?? '',
          'username' => $user['username'] ?? '',
        ];
      }
    }

    // Ищем офис-менеджера Лигрон для этого ИНН (для передачи в 1С как "manager")
    $managers = AccessRepository::getLigronManagersForInns([$inn]);
    $officeManagerCode = '';

    foreach ($managers as $m) {
      // Ищем именно офис-менеджера по его коду роли ('OML')
      if ($m['role'] === UserRole::LIGRON_OFFICE_MANAGER) {
        $officeManagerCode = $m['code_user'];
        break;
      }
    }

    return [
      'inn_dealer' => $inn,
      'salon_code' => $salon,
      'office_manager_code' => $officeManagerCode,
      'user_name' => $authorData['name'],
      'user_phone' => $authorData['phone'],
      'user_email' => $authorData['email'],
      'manager_username' => $authorData['username'] // Отправляем логин обратно в 1С
    ];
  }

  private function generateOrderNumber(array $ctx): string
  {
    $year = date('y');
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