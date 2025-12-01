<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use GuzzleHttp\Client;
use OrderApi\Config\ApiConfig;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Repositories\DealerUserRepository;
use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\Services\Auth\Session\AuthSession;
use Psr\Http\Message\ResponseInterface;

/**
 * Сервис для взаимодействия с 1C (фейковый webhook)
 */
final class Integration1CService
{
  private Client $httpClient;

  public function __construct(
    private readonly UserDTO $currentUser,
  )
  {
    $this->httpClient = new Client();
  }

  /**
   * Отправить заказ в 1C и получить номер
   */
  public function sendOrder(
    int $orderId
  ): ?string
  {
    $order = OrderRepository::getById($orderId);
    $files = OrderFileRepository::getByOrderId($orderId) ?? [];

    $requestData = $this->buildRequestData($order, $files);

    try {
      $response = $this->httpClient->post(ApiConfig::INTEGRATION_1C_ORDER_URL, [
        'json' => $requestData,
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'Authorization' => 'Basic ' . base64_encode('http_service:L3g2n0_2')
        ],
        'timeout' => 119,
      ]);

      return $this->parseResponse($response);

    } catch (\Throwable $e) {
      global $logger;
      $logger->error("1C Integration Error: " .$e->getMessage());
      return null;
    }
  }

  /**
   * Сформировать данные для запроса в 1C
   */
  public function buildRequestData(array $order, array $files): array
  {
    // Формируем данные пользователя дилера в зависимости от роли текущего пользователя
    $dealerUserData = $this->getDealerUserData($order);

    // Формируем order_number
    $orderNumber = $this->generateOrderNumber($dealerUserData);

    // Формируем URL файлов
    $fileUrls = $this->buildFileUrls($order, $files);

    return [
      'buildVersion' => ApiConfig::API_DATE_VERSION,
      'releaseType' => 'dev', //ToDo prod
      'date' => date('d.m.Y'),
      'order_link' => ApiConfig::APP_ORDERS_PAGE . '/' . $order['id'],
      'order_type' => 112,
      'client_in_number' => [
        'name' => $dealerUserData['name'] ?? '',
        'phone' => $dealerUserData['phone'] ?? '',
        'email' => $dealerUserData['email'] ?? '',
        'comment' => $order['comment'] ?? ''
      ],
      'manager' => $dealerUserData['manager'],
      'salon' => $dealerUserData['salon_code'],
      'client' => $dealerUserData['inn'],
      'order_number' => $orderNumber,
      'order_link_jpg' => $fileUrls,
      'tab_elements' => new \stdClass()
    ];
  }

  /**
   * Получить данные дилера по dealer_prefix + dealer_user_id
   */
  private function getDealerUserData($order): array
  {
    $dealerUserData = [];

    if ($this->currentUser->isDealer()) {
      $filteredManagers = array_filter(AuthSession::getManagers(), function($user) {
        return isset($user['role']) && $user['role'] === UserRole::OFFICE_MANAGER;
      });

      $officeManager = !empty($filteredManagers) ? $filteredManagers[0] : [];

      $dealerUserData['name'] = $this->currentUser->name;
      $dealerUserData['email'] = $this->currentUser->email ?? '';
      $dealerUserData['phone'] = $this->currentUser->phone ?? '';
      $dealerUserData['salon_name'] = AuthSession::getSalonName();
      $dealerUserData['salon_code'] = AuthSession::getSalonCode();
      $dealerUserData['inn'] = AuthSession::getInn();
      $dealerUserData['manager'] =  $officeManager['code_user'] ?? '';

    } else {
      // Определяем данные дилера из orderData
      $dealerUserId = $order['dealer_user_id'];
      $dealerPrefix = $order['dealer_prefix'];


      // Получаем данные конкретного дилера (того, от чьего лица создается заказ)
      $dealer = DealerUserRepository::getDealerByPrefix($dealerPrefix, ['select' => ['id']]);

      if (!$dealer) {
        throw new \RuntimeException("Не найден префикс дилера $dealerPrefix");
      }

      if ($dealer && $dealer['id']) {
        $dealerId = $dealer['id'];

        $dealerUser = DealerUserRepository::findDetailedUserByIds($dealerUserId, $dealerId);

        $filteredManagers = array_filter($dealerUser['managers'], function($user) {
          return isset($user['role']) && $user['role'] === UserRole::OFFICE_MANAGER;
        });

        $officeManager = !empty($filteredManagers) ? $filteredManagers[0] : [];

        $dealerUserData['name'] = $dealerUser['name'] ?? '';
        $dealerUserData['email'] =  $dealerUser['email'] ?? '';
        $dealerUserData['phone'] =  $dealerUser['phone'] ?? '';
        $dealerUserData['salon_name'] = $dealerUser['salon_name'] ?? '';
        $dealerUserData['salon_code'] = $dealerUser['salon_code'] ?? '';
        $dealerUserData['inn'] = $dealerUser['inn'] ?? '';
        $dealerUserData['manager'] =  $officeManager['code_user'] ?? '';
      }

    }

    return $dealerUserData;
  }

  /**
   * Сгенерировать order_number: dealer_inn + salon_code + yy + rand(1000,9999) + -1
   */
  private function generateOrderNumber($userData): string
  {
    $year = date('y');
    $random = rand(1000, 9999);

    return $userData['inn'] . $userData['salon_code'] . $year . $random . '-1';
  }


  /**
   * Сформировать URL файлов из загруженных или загружаемых файлов
   */
  private function buildFileUrls(array $order, array $files): array
  {
    $fileUrls = [];

    foreach ($files as $file) {
      $fileUrls[] =  ApiConfig::SITE_ROOT_URL . $file['path'] . $file['name'];
    }

    return $fileUrls;
  }

  /**
   * Парсить ответ от 1C
   */
  private function parseResponse(ResponseInterface $response): ?string
  {
    $data = json_decode($response->getBody()->getContents(), true);

    return $data['ligron_number'] ?? $data['data']['ligron_number'] ?? null;
  }
}