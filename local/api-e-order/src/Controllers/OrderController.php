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
  )
  {
  }


  // POST /orders

  /**
   * @throws \Exception
   */
  public function create(ServerRequestInterface $request): ResponseInterface
  {
    $data = $request->getParsedBody() ?? [];

    $isDraft = (bool)$data['is_draft'];
    if (isset($data['is_draft'])) unset($data['is_draft']);

    $uploadedFiles = $request->getUploadedFiles()['file'] ?? [];

    if ($uploadedFiles && !is_array($uploadedFiles)) {
      $uploadedFiles = [$uploadedFiles];
    }

    $result = $this->orderService->createOrder($data, $uploadedFiles, $isDraft);

    if (!$result->success) {
      return $this->error($result->orderError ?? 'Ошибка создания заказа', 400);
    }

    $order = $result->order;

    $responseData = [
      'order' => $order,
      'files' => array_values($result->getSuccessfulFiles()), // Только успешные
    ];

    // Определяем статус и сообщение
    if ($result->allFilesFailed()) {
      $status = 'error';
      $message = 'Заказ создан, но файлы не загружены';
      $code = 400;
    } elseif ($result->hasFileErrors()) {
      $failedNames = $result->getFailedOriginalNames();
      $list = implode(', ', $failedNames);
      $status = 'partial';
      $message = "Заказ создан. Файлы загружены частично: {$list}";
      $code = 207;
    } else {
      $status = 'success';
      $message = 'Заказ создан';
      $code = 201;
    }

    return $this->json([
      'status' => $status,
      'message' => $message,
      'data' => $responseData,
    ], $code);
  }

  /**
   * @throws \Exception
   */
  // POST /orders/{id}/send-to-ligron'
  public function sendToLigron(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $orderId = (int)$args['id'];

    $order = $this->orderService->sendToLigron($orderId);

    if (!$order) {
      return $this->error('Произошла ошибка при отправке заказа в Лигрон');
    }

    return $this->success('Заказ отправлен в Лигрон', ['order' => $order]);
  }

  // GET /orders/{id}/send-to-ligron/json
  public function getLigronRequestData(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $orderId = (int)$args['id'];

    $data = $this->orderService->getLigronRequestData($orderId);

    if (!$data) {
      return $this->error('Заказ не найден или произошла непредвиденная ошибка');
    }

    return $this->success('Заказ отправлен в Лигрон', $data);
  }

  // GET /orders/{id}
  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $orderId = (int)$args['id'];
      $order = $this->orderService->getOrder($orderId);

      $orderId = $order['id'];

      if (!$orderId) {
        return $this->error('Заказ не найден', 404);
      }

      $files = $this->orderService->getFilesByOrderId($orderId);

      return $order
        ? $this->success('Детали заказа', ['order' => $order, 'files' => $files])
        : $this->error('Заказ не найден', 404);
    } catch (\Exception $e) {
      return $this->handleError($e);
    }
  }

  // GET /orders/number/{number}
  public function getByNumber(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $number = (string)($args['number'] ?? '');

      if (empty($number)) {
        return $this->error('Номер заказа обязателен', 400);
      }

      $order = $this->orderService->getOrderByNumber($number);

      if (!$order) {
        return $this->error('Заказ не найден', 404);
      }

      return $this->success('Детали заказа', ['order' => $order]);
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
      $order = $this->orderService->updateOrder($orderId, $data);

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
    $orderId = (int)$args['id'];
    $files = $request->getUploadedFiles()['file'] ?? [];

    if (!is_array($files)) {
      $files = [$files];
    }

    if (empty($files)) {
      return $this->error('Файлы не переданы', 400);
    }

    $order = $this->orderService->getOrder($orderId);
    if (!$order) {
      return $this->error('Заказ не найден', 404);
    }

    $results = $this->orderService->uploadFilesToOrder($order, $files);

    $successfulFiles = array_values(array_filter(
      array_map(fn($r) => $r->isSuccess() ? $r->file : null, $results),
      fn($f) => $f !== null
    ));

    $failedNames = array_values(array_filter(
      array_map(fn($r) => !$r->isSuccess() ? $r->originalName : null, $results),
      fn($n) => $n !== null
    ));

    if (empty($failedNames)) {
      return $this->success('Все файлы загружены', [
        'files' => $successfulFiles
      ], 201);
    }

    if (empty($successfulFiles)) {
      return $this->json([
        'status' => 'error',
        'message' => 'Ни один файл не был загружен',
        'data' => ['files' => []]
      ], 400);
    }

    $list = implode(', ', $failedNames);
    return $this->json([
      'status' => 'partial',
      'message' => "Файлы загружены частично: {$list}",
      'data' => ['files' => $successfulFiles]
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
    $isDraft = (bool)$data['is_draft']; // '0', '1'

    if ($isDraft) {
      $filter['=status_id'] = null;
    } else {
      $filter = FilterParser::parse($filterString);
      $filter['!=status_id'] = null;
    }

    try {
      $result = $this->orderService->getOrders($filter, $limit, $offset);

      return $this->json([
        'status' => 'success',
        'message' => 'Orders list',
        'data' => $result

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