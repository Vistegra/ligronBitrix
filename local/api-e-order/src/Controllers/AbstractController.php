<?php

namespace OrderApi\Controllers;

abstract class AbstractController
{
  /**
   * Отправляет успешный JSON-ответ
   *
   * @param string $message Сообщение для ответа
   * @param array $data Данные для ответа (по умолчанию пустой массив)
   * @param int $statusCode HTTP статус код (по умолчанию 200)
   *
   * @return void
   */
  protected function sendResponse(string $message, array $data = [], int $statusCode = 200): void
  {
    $response = [
      'status' => 'success',
      'message' => $message,
    ];

    if (!empty($data)) {
      $response['data'] = $data;
    }

    $this->sendJsonResponse($response, $statusCode);
  }

  /**
   * Отправляет JSON-ответ c ошибкой
   *
   * @param string $message Сообщение об ошибке
   * @param int $statusCode HTTP статус код ошибки (по умолчанию 400)
   * @param array $data Дополнительные данные (по умолчанию пустой массив)
   *
   * @return void
   */
  protected function sendError(string $message, int $statusCode = 400, array $data = []): void
  {
    $response = [
      'status' => 'error',
      'message' => $message,
    ];

    if (!empty($data)) {
      $response['data'] = $data;
    }

    $this->sendJsonResponse($response, $statusCode);
  }

  /**
   * Отправляет JSON-ответ и завершает выполнение скрипта
   *
   * @param array $response Ответ в формате массива
   * @param int $statusCode HTTP статус код
   *
   * @return void
   */
  private function sendJsonResponse(array $response, int $statusCode): void
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
  }

  /**
   * Получает данные из запроса (GET, POST, PUT, DELETE, PATCH)
   * Объединяет параметры из разных источников
   *
   * @return array Данные запроса
   */
  protected function getRequestData(): array
  {
    $data = [];
    $method = $_SERVER['REQUEST_METHOD'];

    // Получаем данные из GET запроса
    if (!empty($_GET)) {
      $data = array_merge($data, $_GET);
    }

    // Получаем данные из POST запроса
    if (!empty($_POST)) {
      $data = array_merge($data, $_POST);
    }

    // Для методов с телом запроса (POST, PUT, PATCH, DELETE)
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
      $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

      // Если это JSON, парсим его
      if (strpos($contentType, 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        $jsonData = json_decode($jsonInput, true) ?? [];
        $data = array_merge($data, $jsonData);
      }
      // Если это form-data или x-www-form-urlencoded
      else if (empty($_POST) && in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
        parse_str(file_get_contents('php://input'), $inputData);
        $data = array_merge($data, $inputData);
      }
    }

    return $data;
  }

  /**
   * Получает RAW JSON данные из тела запроса
   *
   * @return array
   */
  protected function getJsonInput(): array
  {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
  }

  /**
   * Получает параметры из URL (для GET запросов)
   *
   * @return array
   */
  protected function getQueryParams(): array
  {
    return $_GET;
  }

  /**
   * Получает заголовки запроса
   *
   * @return array
   */
  protected function getHeaders(): array
  {
    return getallheaders();
  }

  /**
   * Получает конкретный заголовок
   *
   * @param string $name Название заголовка
   * @param string|null $default Значение по умолчанию
   * @return string|null
   */
  protected function getHeader(string $name, ?string $default = null): ?string
  {
    $headers = $this->getHeaders();
    $name = strtolower($name);

    foreach ($headers as $key => $value) {
      if (strtolower($key) === $name) {
        return $value;
      }
    }

    return $default;
  }

  /**
   * Обрабатывает исключение и отправляет сообщение об ошибке
   *
   * @param \Throwable $e Исключение
   * @param int $defaultCode HTTP код по умолчанию
   * @return void
   */
  protected function handleException(\Throwable $e, int $defaultCode = 500): void
  {
    $code = $e->getCode() ?: $defaultCode;

    // Убедимся, что код валидный HTTP статус
    if ($code < 100 || $code >= 600) {
      $code = $defaultCode;
    }

    $this->sendError($e->getMessage(), $code);
  }

  /**
   * Проверяет, является ли запрос AJAX
   *
   * @return bool
   */
  protected function isAjax(): bool
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
  }

  /**
   * Получает метод HTTP запроса
   *
   * @return string
   */
  protected function getMethod(): string
  {
    return $_SERVER['REQUEST_METHOD'];
  }

  /**
   * Получает IP адрес клиента
   *
   * @return string
   */
  protected function getClientIp(): string
  {
    return $_SERVER['HTTP_CLIENT_IP'] ??
      $_SERVER['HTTP_X_FORWARDED_FOR'] ??
      $_SERVER['REMOTE_ADDR'] ??
      'unknown';
  }
}