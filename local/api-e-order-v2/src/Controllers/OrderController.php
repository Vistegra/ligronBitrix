<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use Exception;
use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DTO\Auth\UserDTO;
use OrderApiV2\Helpers\FilterParser;
use OrderApiV2\Services\Order\OrderManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Контроллер для работы с заказами V2.
 */
final class OrderController extends AbstractController
{
  public function __construct(
    private readonly OrderManager $orderManager
  )
  {
  }

  /**
   * POST /orders - Создание заказа
   * @throws Exception
   */
  public function create(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];
    $isDraft = (bool)($data['is_draft'] ?? false);
    unset($data['is_draft']);

    $uploadedFiles = $request->getUploadedFiles()['file'] ?? [];

    if ($uploadedFiles && !is_array($uploadedFiles)) {
      $uploadedFiles = [$uploadedFiles];
    }

    $result = $this->orderManager->createOrder($data, $uploadedFiles, $isDraft);

    if (!$result->success) {
      return $this->error($result->orderError ?? 'Ошибка создания заказа', 400);
    }

    $responseData = [
      'order' => $result->order,
      'files' => array_values($result->getSuccessfulFiles()),
    ];

    return match (true) {

      $result->allFilesFailed() => $this->json([
          'status' => 'error',
          'message' => 'Заказ создан, но файлы не загружены',
          'data' => $responseData]
        , 400),

      $result->hasFileErrors() => $this->json([
        'status' => 'partial',
        'message' => 'Заказ создан. Файлы загружены частично',
        'data' => $responseData],
        207),

      default => $this->json([
        'status' => 'success',
        'message' => 'Заказ создан',
        'data' => $responseData],
        201),

    };
  }

  /**
   * GET /orders/{id} - Получить детальные данные
   */
  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $data = $this->orderManager->getOrder($id);

      // Извлекаем файлы из общего массива, чтобы разделить order и files
      $files = $data['files'] ?? [];
      unset($data['files']);

      return $this->success('Детали заказа', [
        'order' => $data,
        'files' => $files
      ]);

    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * GET /orders/number/{number} - Поиск по номеру Лигрон
   */
  public function getByNumber(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $number = (string)($args['number'] ?? '');
      if (!$number) return $this->error('Номер заказа обязателен', 400);

      $data = $this->orderManager->getOrderByNumber($number);

      $files = $data['files'] ?? [];
      unset($data['files']);

      return $this->success('Детали заказа по номеру', [
        'order' => $data,
        'files' => $files
      ]);

    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * PUT /orders/{id} - Обновление данных
   */
  public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $input = $request->getParsedBody() ?? [];

      $order = $this->orderManager->updateOrder($id, $input);

      return $this->success('Заказ обновлен', [
        'order' => $order
      ]);

    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * GET /orders - Список заказов
   */
  public function getAll(ServerRequestInterface $request): ResponseInterface
  {
    $params = $request->getQueryParams();
    $filter = FilterParser::parse($params['filter'] ?? '');

    $search = trim($params['search'] ?? '');

    if ($search !== '') {
      $filter[] = [
        'LOGIC' => 'OR',
        ['%name' => $search],
        ['%number' => $search]
      ];
    }

    $isDraft = ($params['is_draft'] ?? '0') === '1';

    if ($isDraft) {
      // Черновик - это заказ без статуса (status_id IS NULL)
      $filter['=status_id'] = false;

      // Черновики видит только их автор

      /** @var UserDTO $user */
      $user = $request->getAttribute('user');
      $filter['=author_id'] = $user->id;
      $filter['=created_by'] = $user->isDealer() ? OrderTable::CREATED_BY_DEALER : OrderTable::CREATED_BY_MANAGER;

    } else {
      // Исключаем черновики из общего списка, если фильтр по статусу не передан явно с фронтенда
      if (!isset($filter['=status_id'])) {
        $filter['!=status_id'] = false; // status_id IS NOT NULL
      }
    }

    $sortArray = ['updated_at' => 'desc'];

    if (!empty($params['sort']) && str_contains($params['sort'], ':')) {
      [$field, $dir] = explode(':', $params['sort'], 2);
      $sortArray = [trim($field) => (strtolower(trim($dir)) === 'asc' ? 'asc' : 'desc')];
    }

    try {
      $result = $this->orderManager->getOrders(
        $filter,
        (int)($params['limit'] ?? 20),
        (int)($params['offset'] ?? 0),
        $sortArray
      );

      return $this->success('Список заказов', $result);
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * POST /orders/{id}/status - Смена статуса
   */
  public function changeStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = (int)$args['id'];
    $data = $request->getParsedBody() ?? [];
    $status = (string)($data['status'] ?? '');

    if (!$status) return $this->error('Статус обязателен', 400);

    try {
      $this->orderManager->changeStatus($id, $status, $data['comment'] ?? null);
      $updatedOrder = $this->orderManager->getOrder($id);

      $files = $updatedOrder['files'] ?? [];
      unset($updatedOrder['files']);

      return $this->success('Статус изменен', [
        'order' => $updatedOrder,
        'files' => $files
      ]);
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * POST /orders/{id}/send-to-ligron
   */
  public function sendToLigron(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $order = $this->orderManager->sendToLigron((int)$args['id']);
      return $order
        ? $this->success('Заказ отправлен в Лигрон', ['order' => $order])
        : $this->error('Ошибка при синхронизации с 1С');
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * Справочник статусов
   */
  public function getStatuses(): ResponseInterface
  {
    try {
      return $this->success('Список статусов', $this->orderManager->getStatuses());
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * Удаление файла
   */
  public function deleteFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $this->orderManager->deleteFile((int)$args['id'], (int)$args['fileId']);
      return $this->success('Файл удален');
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

  /**
   * Удаление заказа
   */
  public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $this->orderManager->deleteOrder($id);
      return $this->success('Заказ удален');
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

}