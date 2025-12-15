<?php

declare(strict_types=1);

namespace OrderApi\Helpers;

/**
 * Простейший парсер фильтра: key=value1,value2 → ['=key' => [value1, value2]]
 */
final class FilterParser
{
  /**
   * Парсит строку фильтра в массив
   *
   * @param string $filterString Например: "status_id=1,2;dealer_prefix=ABC" или "status_id=1&dealer_prefix=ABC"
   * @return array
   */
  public static function parse(string $filterString): array
  {
    if (empty($filterString)) {
      return [];
    }

    $result = [];

    // Поддержка и ; и & в качестве разделителей
    $filterString = str_replace('&', ';', $filterString);

    $parts = array_filter(array_map('trim', explode(';', $filterString)));

    foreach ($parts as $part) {
      if (!str_contains($part, '=')) {
        continue;
      }

      [$key, $valueStr] = explode('=', $part, 2);
      $key = trim($key);
      $valueStr = trim($valueStr);

      if ($key === '' || $valueStr === '') {
        continue;
      }

      // Множественные значения
      $values = array_filter(array_map('trim', explode(',', $valueStr)));

      if (empty($values)) {
        continue;
      }

      // Всегда массив, если несколько, иначе — скаляр
      $result["={$key}"] = count($values) === 1 ? $values[0] : $values;
    }

    return $result;
  }
}