<?php

declare(strict_types=1);

namespace OrderApi\Bootstrap;

use JetBrains\PhpStorm\NoReturn;
use OrderApi\Services\LogService;
use Throwable;

class ErrorHandler
{
  public static function register(string $logDir): void
  {
    // Регистрируем обработчик исключений
    set_exception_handler(function (Throwable $e) {
      self::handle($e);
    });

    // Регистрируем обработчик фатальных ошибок
    register_shutdown_function(function () {
      $error = error_get_last();
      // Ловим только критические ошибки
      if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        self::handleFatal($error);
      }
    });

    // Инициализируем логгер.
    LogService::setLogDir($logDir);
  }

  private static function handle(Throwable $e): void
  {
    try {
      LogService::error($e);
    } catch (Throwable $loggingError) {
    }

    $code = $e->getCode();
    if (!is_int($code) || $code < 400 || $code >= 600) {
      $code = 500;
    }

    self::renderJson([
      'status' => 'error',
      'message' => $e->getMessage(),
      'type' => get_class($e),
      'file' => $e->getFile(),
      'line' => $e->getLine()
    ], $code);
  }

  private static function handleFatal(array $error): void
  {
    try {
      LogService::error("FATAL: " . $error['message'], $error);
    } catch (Throwable $e) {
    }

    self::renderJson([
      'status' => 'error',
      'message' => $error['message'],
      'type' => 'fatal_error',
      'file' => $error['file'] ?? '?',
      'line' => $error['line'] ?? 0
    ], 500);
  }

  #[NoReturn]
  private static function renderJson(array $data, int $code): void
  {
    if (!headers_sent()) {
      http_response_code($code);
      header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }
}