<?php


declare(strict_types=1);

namespace OrderApi\Helpers;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;

/**
 * Класс-парсер для преобразования строковых фильтров из запроса в формат Bitrix ORM.
 *
 * Поддерживает:
 * - Простое равенство: "field=value" -> ["=field" => "value"]
 * - Множественные значения (IN): "field=v1,v2" -> ["=field" => ["v1", "v2"]]
 * - Диапазоны "ОТ" (_from): "created_at_from=2023-01-01" -> [">=created_at" => "2023-01-01 00:00:00"]
 * - Диапазоны "ДО" (_to): "created_at_to=2023-01-01" -> ["<=created_at" => "2023-01-01 23:59:59"]
 *
 * В качестве разделителей пар ключ=значение поддерживает ";" и "&".
 *
 * @package OrderApi\Helpers
 */
final class FilterParser
{
  /**
   * Поля, которые мы точно считаем датами.
   * Это нужно, чтобы не лепить время к обычным числам.
   */
  private const array DATE_FIELDS = ['created_at', 'updated_at', 'ready_date'];

  /**
   * @throws ObjectException
   */
  public static function parse(string $filterString): array
  {
    if (empty($filterString)) {
      return [];
    }

    $result = [];
    $filterString = str_replace('&', ';', $filterString);
    $parts = array_filter(array_map('trim', explode(';', $filterString)));

    foreach ($parts as $part) {
      if (!str_contains($part, '=')) continue;

      [$key, $valueStr] = explode('=', $part, 2);
      $key = trim($key);
      $valueStr = trim($valueStr);

      if ($key === '' || $valueStr === '') continue;

      // Определяем, является ли поле датой
      $isDateField = false;
      $cleanKey = $key;
      if (str_ends_with($key, '_from')) {
        $cleanKey = str_replace('_from', '', $key);
        $isDateField = in_array($cleanKey, self::DATE_FIELDS);
      } elseif (str_ends_with($key, '_to')) {
        $cleanKey = str_replace('_to', '', $key);
        $isDateField = in_array($cleanKey, self::DATE_FIELDS);
      }

      // Обработка логики "ОТ"
      if (str_ends_with($key, '_from')) {
        $realKey = str_replace('_from', '', $key);
        if ($isDateField) {
          // Создаем объект DateTime Bitrix (начало дня)
          $result[">={$realKey}"] = new DateTime($valueStr . ' 00:00:00', 'Y-m-d H:i:s');
        } else {
          $result[">={$realKey}"] = $valueStr;
        }
        continue;
      }

      // Обработка логики "ДО"
      if (str_ends_with($key, '_to')) {
        $realKey = str_replace('_to', '', $key);
        if ($isDateField) {
          // Создаем объект DateTime Bitrix (конец дня)
          $result["<={$realKey}"] = new DateTime($valueStr . ' 23:59:59', 'Y-m-d H:i:s');
        } else {
          $result["<={$realKey}"] = $valueStr;
        }
        continue;
      }

      // Стандартная логика
      $values = array_filter(array_map('trim', explode(',', $valueStr)), fn($v) => $v !== '');
      if (empty($values)) continue;

      $result["={$key}"] = count($values) === 1 ? $values[0] : $values;
    }

    return $result;
  }
}