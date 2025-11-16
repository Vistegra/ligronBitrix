<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\Config\ApiConfig;

final class FileDiskRepository
{
  /**
   * Удаляет все файлы заказа из файловой системы
   */
  public static function deleteFiles(?array $files): bool
  {
    if (empty($files)) {
      return false;
    }

    $success = true;
    foreach ($files as $file) {
      if (!self::deleteFile($file['path'], $file['name'])) {
        $success = false;
      }
    }

    // Пытаемся удалить пустую директорию заказа
    if (!empty($files[0]['path'])) {
      $orderDirectory = $_SERVER['DOCUMENT_ROOT'] . $files[0]['path'];
      self::removeEmptyDirectory($orderDirectory);
    }

    return $success;
  }

  /**
   * Удаляет физический файл
   */
  public static function deleteFile(string $path, string $filename): bool
  {
    if (empty($path) || empty($filename)) {
      return false;
    }

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path . $filename;
    $fileRealPath = realpath($fullPath);

    // Проверки безопасности
    $uploadRoot = realpath($_SERVER['DOCUMENT_ROOT'] . ApiConfig::UPLOAD_FILES_DIR);

    if (
      $fileRealPath === false ||
      $uploadRoot === false ||
      strpos($fileRealPath, $uploadRoot) !== 0
    ) {
      return false;
    }

    if (is_file($fileRealPath)) {
      return @unlink($fileRealPath);
    }

    return false;
  }

  /**
   * Удаляет директорию, если она пуста
   */
  public static function removeEmptyDirectory(string $directory): bool
  {
    $dirRealPath = realpath($directory);
    $uploadRoot = realpath($_SERVER['DOCUMENT_ROOT'] . ApiConfig::UPLOAD_FILES_DIR);

    if (
      $dirRealPath === false ||
      $uploadRoot === false ||
      strpos($dirRealPath, $uploadRoot) !== 0 ||
      !is_dir($dirRealPath)
    ) {
      return false;
    }

    $files = array_diff(scandir($dirRealPath), ['.', '..']);
    if (empty($files)) {
      return @rmdir($dirRealPath);
    }

    return false;
  }

}