<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\Config\ApiConfig;
use OrderApi\DTO\Order\FileUploadResult;
use Psr\Http\Message\UploadedFileInterface;

final class FileDiskRepository
{
  /**
   * Загружает файл на диск и возвращает результат
   */
  public static function upload(
    UploadedFileInterface $file,
    string $uploadDir
  ): FileUploadResult
  {
    $originalName = $file->getClientFilename() ?: 'unknown_file_' . uniqid();

    // Проверка ошибки загрузки
    if ($file->getError() !== UPLOAD_ERR_OK) {
      return new FileUploadResult(
        file: null,
        originalName: $originalName,
        error: self::getUploadErrorMessage($file->getError())
      );
    }

    // Создаём директорию
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
      return new FileUploadResult(
        file: null,
        originalName: $originalName,
        error: 'Не удалось создать директорию для загрузки'
      );
    }

    // Санитизация имени
    $filename = self::sanitizeFilename($originalName, $uploadDir);
    $fullPath = $uploadDir . $filename;
    $relativeDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $uploadDir);
    $relativeDir = rtrim($relativeDir, '/') . '/';

    // Перемещение файла
    try {
      $file->moveTo($fullPath);
    } catch (\Throwable $e) {
      return new FileUploadResult(
        file: null,
        originalName: $originalName,
        error: 'Ошибка перемещения файла: ' . $e->getMessage()
      );
    }

    return new FileUploadResult(
      file: [
        'name' => $filename,
        'path' => $relativeDir,
        'size' => $file->getSize(),
        'mime' => $file->getClientMediaType(),
      ]
    );
  }

  private static function sanitizeFilename(string $original, string $dir): string
  {
    $original = basename($original);
    $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);

    $info = pathinfo($sanitized);
    $base = $info['filename'];
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    $counter = 1;
    $candidate = $sanitized;

    while (file_exists($dir . $candidate)) {
      $candidate = "{$base}_{$counter}{$ext}";
      $counter++;
    }

    return $candidate;
  }

  private static function getUploadErrorMessage(int $errorCode): string
  {
    return match ($errorCode) {
      UPLOAD_ERR_INI_SIZE     => 'Размер файла превышает лимит сервера',
      UPLOAD_ERR_FORM_SIZE    => 'Размер файла превышает лимит формы',
      UPLOAD_ERR_PARTIAL      => 'Файл был загружен частично',
      UPLOAD_ERR_NO_FILE      => 'Файл не был загружен',
      UPLOAD_ERR_NO_TMP_DIR   => 'Отсутствует временная папка',
      UPLOAD_ERR_CANT_WRITE   => 'Ошибка записи файла на диск',
      UPLOAD_ERR_EXTENSION    => 'Загрузка остановлена расширением',
      default                 => 'Неизвестная ошибка загрузки',
    };
  }

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