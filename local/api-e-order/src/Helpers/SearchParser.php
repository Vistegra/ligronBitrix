<?php

declare(strict_types=1);

namespace OrderApi\Helpers;

/**
 * Парсер строки поиска: key=val1;key2=val2 → ['%key' => val1, '%key2' => val2]
 */
final class SearchParser
{
  /**
   * Парсит строку поиска в массив фильтров для ORM (с оператором %)
   *
   * @param string $searchString Например: "name=Столешница;number=123" или "name=Столешница&number=123"
   * @return array
   */
  public static function parse(string $searchString): array
  {
    if (empty($searchString)) {
      return [];
    }

    $result = [];

    // Поддержка и ; и & в качестве разделителей
    $searchString = str_replace('&', ';', $searchString);

    $parts = array_filter(array_map('trim', explode(';', $searchString)));

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

      $cleanKey = ltrim($key, '=!<>%@');

      $result["%{$cleanKey}"] = $valueStr;
    }

    return $result;
  }
}