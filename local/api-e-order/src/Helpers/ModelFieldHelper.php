<?php declare(strict_types=1);

namespace OrderApi\Helpers;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use OrderApi\Services\LogService;

/**
 * Хелпер для модификаторов полей ORM.
 * Все методы возвращают CALLABLE, который возвращает массив модификаторов.
 */
final class ModelFieldHelper
{
  private function __construct() {}

  /** @return callable(): array<int, callable> */
  public static function toInt(): callable
  {
    return fn() => [
      function ($value) {
        if ($value === null || $value instanceof SqlExpression) {
          return $value;
        }
        return (int) $value;
      }
    ];
  }

  /** @return callable(): array<int, callable> */
  public static function toJsonEncode(): callable
  {
    return fn() => [
      function ($value) {
        return is_array($value)
          ? json_encode($value, JSON_UNESCAPED_UNICODE)
          : $value;
      }
    ];
  }

  /** @return callable(): array<int, callable> */
  public static function toJsonDecode(): callable
  {
    return fn() => [
      function ($value) {
        if ($value === null || $value === '') {
          return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
      }
    ];
  }

  /** @return callable(): array<int, callable> */
  public static function cleanString(): callable
  {
    return fn() => [
      function ($value) {
        if (!is_string($value)) {
          return $value;
        }
        return trim(preg_replace('/[\r\n\t\x0B\0]+/', '', $value));
      }
    ];
  }

  /** @return callable(): array<int, callable> */
  public static function compressJson(): callable
  {
    return fn() => [
      function ($value) {
        if ($value === null || $value === '') {
          $value = [];
        }
        if (is_array($value)) {
          $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return gzcompress($value, 9);
      }
    ];
  }

  /** @return callable(): array<int, callable> */
  public static function decompressJson(): callable
  {
    return fn() => [
      function ($value) {
        if ($value === null || $value === '') {
          return [];
        }
        $uncompressed = @gzuncompress($value);
        if ($uncompressed === false) {
          return [];
        }
        $decoded = json_decode($uncompressed, true);
        return is_array($decoded) ? $decoded : [];
      }
    ];
  }

  /** @return callable(): DateTime */
  public static function now(): callable
  {
    return fn() => new DateTime();
  }

  /** @return callable(): array<int, callable> */
  public static function toTimestamp(): callable
  {
    return fn() => [
      function ($value) {
        if ($value instanceof DateTime) {
          return $value->getTimestamp(); // секунды
        }
        return $value;
      }
    ];
  }

  /**
   * Преобразует объект Date в строку при выборке из БД.
   * По умолчанию формат 'd.m.Y' (например, 15.12.2025).
   *
   * @param string $format Формат даты (PHP date format)
   * @return callable(): array<int, callable>
   */
  public static function dateToString(string $format = 'd.m.Y'): callable
  {
    return fn() => [
      function ($value) use ($format) {
        if ($value instanceof Date) {
          return $value->format($format);
        }

        return $value;
      }
    ];
  }
}