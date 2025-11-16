<?php
declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\DB\Helpers\FilterParser;
use OrderApi\DB\Repositories\OrderRepository;
use OrderApi\DTO\Order\FileUploadResult;
use OrderApi\DTO\Order\OrderCreateResult;
use OrderApi\Services\Order\OrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use OrderApi\DTO\Auth\UserDTO;
/**
 * Контроллер для работы с заказами
 */
final class OrderController extends AbstractController
{
  public function __construct(
    private readonly OrderService $orderService
  ) {}

  // POST /orders
  public function create(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];

    $uploadedFiles = $request->getUploadedFiles()['file'] ?? [];

    if ($uploadedFiles && !is_array($uploadedFiles)) {
      $uploadedFiles = [$uploadedFiles];
    }

    /** @var UserDTO $user */
    $user = $request->getAttribute('user');
    if (!$user) {
      return $this->error('Unauthorized', 401);
    }

    // Создаём заказ с файлами
    $result = $this->orderService->createOrder($data, $uploadedFiles);

    if (!$result->success) {
      return $this->error($result->orderError ?? 'Ошибка создания заказа', 400);
    }

    // Получаем заказ для ответа
    $order = $this->orderService->getOrder($result->orderId);

    if (!$order) {
      return $this->error('Заказ создан, но не найден при чтении', 500);
    }

    // Формируем ответ
    $responseData = [
      'order' => $order,
    ];

    if (!empty($result->fileResults)) {
      $responseData['files'] = array_map(
        fn(FileUploadResult $r) => $r->toArray(),
        $result->fileResults
      );
    }

    // Определяем HTTP-статус
    $statusCode = 201;
    $message = 'Заказ создан';

    if ($result->hasFileErrors()) {
      $statusCode = $result->allFilesFailed() ? 400 : 207;
      $message = $result->allFilesFailed()
        ? 'Заказ создан, но файлы не загружены'
        : 'Заказ создан, часть файлов не загружена';
    }

    return $this->json([
      'status'  => $statusCode === 201 ? 'success' : ($statusCode === 207 ? 'partial' : 'error'),
      'message' => $message,
      'data'    => $responseData,
    ], $statusCode);
  }

  // GET /orders/{id}
  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $orderId = (int)$args['id'];
      $order = $this->orderService->getOrder($orderId);

      return $order
        ? $this->success('Детали заказа', ['order' => $order])
        : $this->error('Заказ не найден', 404);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // PUT /orders/{id}
  public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $orderId = (int)$args['id'];
    $data = $request->getParsedBody() ?? [];

    try {
      if (!$this->orderService->updateOrder($orderId, $data)) {
        return $this->error('Не удалось обновить заказ', 500);
      }

      $order = $this->orderService->getOrder($orderId);
      return $this->success('Заказ обновлен', ['order' => $order]);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // DELETE /orders/{id}
  public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $orderId = (int)$args['id'];
      if (!$this->orderService->deleteOrder($orderId)) {
        return $this->error('Failed to delete order', 500);
      }
      return $this->success('Order deleted');
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // POST /orders/{id}/status
  public function changeStatus(int $id, ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];
    $status = $data['status'] ?? '';
    $comment = $data['comment'] ?? null;

    if (!$status) {
      return $this->error('Status is required', 400);
    }

    try {
      if (!$this->orderService->changeStatus($id, $status, $comment)) {
        return $this->error('Failed to change status', 500);
      }

      $order = $this->orderService->getOrder($id);
      return $this->success('Status changed', $order);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // POST /orders/{id}/files

  /**
   * @throws \Exception
   */
  public function uploadFiles(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $files = $request->getUploadedFiles()['file'] ?? [];

    if (!is_array($files)) {
      $files = [$files];
    }

    if (empty($files)) {
      return $this->error('No files uploaded', 400);
    }

    $orderId = (int)$args['id'];

    $order = $this->orderService->getOrder($orderId);
    if (!$order) {
      return $this->error('Order not found', 404);
    }

    $results = $this->orderService->uploadFilesToOrder($order, $files);

    $successful = array_filter($results, fn($r) => $r->isSuccess());
    $failed = array_filter($results, fn($r) => !$r->isSuccess());

    if (empty($failed)) {
      return $this->success('Files uploaded', [
        'files' => array_map(fn($r) => $r->toArray(), $results)
      ], 201);
    }

    if (empty($successful)) {
      return $this->json([
        'status' => 'error',
        'message' => 'All files failed to upload',
        'files' => array_map(fn($r) => $r->toArray(), $results)
      ], 400);
    }

    return $this->json([
      'status' => 'partial',
      'message' => 'Some files uploaded, some failed',
      'files' => array_map(fn($r) => $r->toArray(), $results)
    ], 207);
  }

  // DELETE /orders/{id}/files/{fileId}
  public function deleteFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $orderId = (int)$args['id'];
    $fileId = (int)$args['fileId'];
    try {
      if (!$this->orderService->deleteFile($orderId, $fileId)) {
        return $this->error('Не удалось удалить файл', 500);
      }
      return $this->success('Файл удален', [], 204);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // GET /orders
  public function getAll(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getQueryParams();
    $filterString = $data['filter'] ?? '';
    $limit = (int)($data['limit'] ?? 20);
    $offset = (int)($data['offset'] ?? 0);

    $filter = FilterParser::parse($filterString);

    try {
      $orders = $this->orderService->getOrders($filter, $limit, $offset);
      return $this->json([
        'status' => 'success',
        'message' => 'Orders list',
        'data' =>
          [
            'order' => $orders,
            'pagination' => [
              'limit' => $limit,
              'offset' => $offset,
              'total' => OrderRepository::getTotalCount($filter)
            ]
          ]

      ]);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // GET /statuses
  public function getStatuses(): ResponseInterface
  {
    try {
      $statuses = $this->orderService->getStatuses();
      return $this->success('Order statuses', $statuses);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }
}