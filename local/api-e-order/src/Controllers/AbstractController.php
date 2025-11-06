<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

abstract class AbstractController
{
  /**
   * Универсальный JSON-ответ
   * @param array $data
   * @param int $code
   * @return ResponseInterface
   */
  protected function json(array $data, int $code = 200): ResponseInterface
  {
    $response = new Response($code);
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
  }

  /**
   * Успешный ответ
   * @param string $message
   * @param array $data
   * @param int $code
   * @return ResponseInterface
   */
  protected function success( string $message, array $data = [],  int $code = 200 ): ResponseInterface
  {
    return $this->json([
      'status' => 'success',
      'message' => $message,
      'data' => $data
    ], $code);
  }

  /**
   * Ошибка
   * @param string $message
   * @param int $code
   * @param string|null $type
   * @return ResponseInterface
   */
  protected function error(string $message, int $code = 400,  ?string $type = null): ResponseInterface
  {
    return $this->json([
      'status' => 'error',
      'message' => $message,
      'type' => $type
    ], $code);
  }

  /**
   * Обработка исключений
   * @param \Throwable $e
   * @return ResponseInterface
   */
  protected function handleError(\Throwable $e): ResponseInterface
  {
    $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    return $this->error($e->getMessage(), $code, $e::class);
  }
}