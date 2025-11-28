<?php

declare(strict_types=1);

namespace OrderApi\Services\Order;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use OrderApi\Config\ApiConfig;
use OrderApi\DB\Repositories\DealerUserRepository;

use OrderApi\DB\Repositories\OrderFileRepository;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DTO\Auth\UserDTO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

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
   * Использует уже готовые данные заказа и файлы
   */
  public function sendOrder(
    int $orderId
  ): ?string
  {
    $order = OrderRepository::getById($orderId);
    $files = OrderFileRepository::getByOrderId($orderId);

    $requestData = $this->buildRequestData($order, $files);

    try {
      $response = $this->httpClient->post('/fake-1c-webhook', [
        'json' => $requestData,
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'timeout' => 10,
      ]);

      return $this->parseResponse($response);

    } catch (RequestException $e) {
      //ToDo log
      error_log("C1 Integration Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Сформировать данные для запроса в 1C
   * Использует $order и $files из момента создания
   */
  private function buildRequestData(array $order, array $files): array
  {
    // Определяем данные дилера из orderData
    $dealerUserId = $order['dealer_user_id'];
    $dealerPrefix = $order['dealer_prefix'];

    // Получаем данные конкретного дилера (того, от чьего лица создается заказ)
    $dealer = DealerUserRepository::getDealerByPrefix($dealerPrefix, ['select' => ['id']]);

    if (!$dealer) {
      throw new \RuntimeException("Не найден префикс дилера $dealerPrefix");
    }
    // Если текущий пользователь получить данные из сессии
    DealerUserRepository::findDetailedUserByIds($dealerUserId, $dealer['id']);

    $dealerUserData = $this->getDealerUserData($dealerPrefix, $dealerUserId);

    // Формируем order_number
    $orderNumber = $this->generateOrderNumber($dealerPrefix, $dealerUserId);

    // Формируем URL файлов (уже загруженных или загружаемых)
    $fileUrls = $this->buildFileUrls($order, $files);

    return [
      'buildVersion' => ApiConfig::API_DATE_VERSION,
      'releaseType' => 'prod',
      'date' => date('d.m.Y'),
      'order_link' => ApiConfig::APP_ORDERS_PAGE . '/' . $order['id'],
      'order_type' => 112,
      'client_in_number' => [
        'name' => $dealerUserData['login'] ?? '',
        'phone' => $dealerUserData['phone'] ?? '',
        'email' => $dealerUserData['email'] ?? '',
        'comment' => $order['comment'] ?? ''
      ],
      'manager' => 'Ligron_admin',
      'salon' => $this->getSalonCode($dealerPrefix, $dealerUserId),
      'client' => $dealerPrefix,
      'order_number' => $orderNumber,
      'order_link_jpg' => $fileUrls
    ];
  }

  /**
   * Получить данные дилера по dealer_prefix + dealer_user_id
   */
  private function getDealerUserData(string $dealerPrefix, int $dealerUserId): array
  {
    return
  }

  /**
   * Сгенерировать order_number: dealer_inn + salon_code + yy + rand(1000,9999) + -1
   */
  private function generateOrderNumber(string $dealerPrefix, int $dealerUserId): string
  {
    $salonCode = $this->getSalonCode($dealerPrefix, $dealerUserId);
    $year = date('y');
    $random = rand(1000, 9999);

    return $dealerPrefix . $salonCode . $year . $random . '-1';
  }

  /**
   * Получить salon_code для дилера
   */
  private function getSalonCode(string $dealerPrefix, int $dealerUserId): string
  {

  }

  /**
   * Сформировать URL файлов из загруженных или загружаемых файлов
   */
  private function buildFileUrls(array $order, array $files): array
  {
    $fileUrls = [];
    $baseUrl = 'https://ligron.ru';
    $uploadDir = ApiConfig::UPLOAD_FILES_DIR .
      "{$order['dealer_prefix']}/{$order['dealer_user_id']}/{$order['id']}/";

    foreach ($files as $file) {
      if ($file instanceof UploadedFileInterface && $file->getError() === UPLOAD_ERR_OK) {

        $fileName = $file->getClientFilename();
        $fileUrls[] = $baseUrl . $uploadDir . $fileName;
      }
    }

    return $fileUrls;
  }

  /**
   * Парсить ответ от 1C
   */
  private function parseResponse(ResponseInterface $response): ?string
  {
    $data = json_decode($response->getBody()->getContents(), true);

    return $data['data']['ligron_number'] ?? null;
  }
}