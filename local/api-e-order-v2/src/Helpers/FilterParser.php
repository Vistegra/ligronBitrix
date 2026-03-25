<?php

declare(strict_types=1);

namespace OrderApiV2\Helpers;

use Bitrix\Main\Type\DateTime;

final class FilterParser
{
  /**
   * Поля, которые обрабатываются как даты Bitrix
   */
  private const array DATE_FIELDS = ['created_at', 'updated_at', 'ready_date'];

  /**
   * Поля, которые мы игнорируем при парсинге фильтров БД,
   * так как они обрабатываются отдельно.
   */
  private const array SPECIAL_KEYS = ['search'];

  /**
   * Основной метод парсинга
   */
  public static function parse(string $filterString): array
  {
    if (empty($filterString)) {
      return [];
    }

    $result = [];
    $pairs = self::explodeString($filterString);

    foreach ($pairs as $key => $value) {
      // Извлекаем чистый код поля и тип операции (suffix)
      [$fieldName, $operator] = self::detectOperator($key);

      if (in_array($fieldName, self::SPECIAL_KEYS, true)) {
        continue;
      }

      // Форматируем значение в зависимости от типа поля и оператора
      $formattedValue = self::prepareValue($fieldName, $value, $operator);

      // Формируем ключ для Bitrix ORM (например, ">=created_at")
      $result[$operator . $fieldName] = $formattedValue;
    }

    return $result;
  }

  /**
   * Разбивает строку на ассоциативный массив пар ключ=значение
   */
  private static function explodeString(string $filterString): array
  {
    $filterString = str_replace('&', ';', $filterString);
    $parts = array_filter(array_map('trim', explode(';', $filterString)));
    $pairs = [];

    foreach ($parts as $part) {
      if (str_contains($part, '=')) {
        [$key, $value] = explode('=', $part, 2);
        $pairs[trim($key)] = trim($value);
      }
    }

    return $pairs;
  }

  /**
   * Определяет оператор Bitrix ORM по суффиксу ключа
   * Возвращает [чистое_имя_поля, оператор_ORM]
   */
  private static function detectOperator(string $key): array
  {
    if (str_ends_with($key, '_from')) {
      return [str_replace('_from', '', $key), '>='];
    }

    if (str_ends_with($key, '_to')) {
      return [str_replace('_to', '', $key), '<='];
    }

    // По умолчанию - строгое равенство
    return [$key, '='];
  }

  /**
   * Обрабатывает значение в зависимости от бизнес-логики поля
   */
  private static function prepareValue(string $fieldName, string $value, string $operator): mixed
  {
    // Обработка дат
    if (in_array($fieldName, self::DATE_FIELDS, true)) {
      return self::parseBitrixDate($value, $operator);
    }

    // Обработка множественных значений (IN)
    if (str_contains($value, ',')) {
      return array_filter(array_map('trim', explode(',', $value)), fn($v) => $v !== '');
    }

    // Обработка специальных строковых констант
    $lowerValue = strtolower($value);

    if ($lowerValue === 'null') {
      return false; // Bitrix ORM использует false для IS NULL
    }
    if ($lowerValue === 'true') {
      return true;
    }
    if ($lowerValue === 'false') {
      return false; // Для булевых полей Bitrix
    }

    return $value;
  }

  /**
   * Создает объект DateTime Bitrix с учетом границ дня
   */
  private static function parseBitrixDate(string $value, string $operator): mixed
  {
    try {
      // Если пришла просто дата (10 символов: YYYY-MM-DD), дополняем временем
      if (strlen($value) === 10) {
        $time = ($operator === '<=') ? ' 23:59:59' : ' 00:00:00';
        return new DateTime($value . $time, 'Y-m-d H:i:s');
      }

      return new DateTime($value);
    } catch (\Throwable) {
      return $value;
    }

  }
}