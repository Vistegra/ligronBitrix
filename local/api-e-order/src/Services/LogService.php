<?php

declare(strict_types=1);

namespace OrderApi\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Централизованный сервис логирования
 */
class LogService
{
  private static array $loggers = [];

  // Путь к папке логов, который устанавливается из index.php
  public static string $logDir = '';

  /**
   * Установить базовую директорию для логов
   */
  public static function setLogDir(string $dir): void
  {
    self::$logDir = rtrim($dir, '/') . '/';
  }

  /**
   * Получить логгер для определенного канала
   */
  public static function get(string $channel = 'api'): Logger
  {
    if (!isset(self::$loggers[$channel])) {

      if (!self::$logDir) {
        throw new \RuntimeException('Не установлена дефолтная папка для логирования LogService::setLogDir()!');
      }

      $logger = new Logger($channel);

      $logPath = self::$logDir . $channel . '.log';

      // Создаем папку, если нет
      if (!is_dir(dirname($logPath))) {
        @mkdir(dirname($logPath), 0755, true);
      }

      $logger->pushHandler(new StreamHandler($logPath));
      self::$loggers[$channel] = $logger;
    }

    return self::$loggers[$channel];
  }

  public static function error(string|\Throwable $message, array $context = [], string $channel = 'api'): void
  {
    if ($message instanceof \Throwable) {
      $context['exception'] = $message;
      $text = $message->getMessage();
      $context['file'] = $message->getFile();
      $context['line'] = $message->getLine();
    } else {
      $text = $message;
    }

    self::get($channel)->error($text, $context);
  }

  public static function info(string $message, array $context = [], string $channel = 'api'): void
  {
    self::get($channel)->info($message, $context);
  }

  public static function warn(string $message, array $context = [], string $channel = 'api'): void
  {
    self::get($channel)->warning($message, $context);
  }
}