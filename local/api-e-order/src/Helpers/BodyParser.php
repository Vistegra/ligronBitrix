<?php

declare(strict_types=1);

namespace OrderApi\Helpers;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Хелпер для обработки тела запроса с поддержкой BOM
 */
final class BodyParser
{
  /**
   * Парсит тело запроса в массив.
   * Удаляет BOM (Byte Order Mark) перед декодированием JSON.
   *
   * @param ServerRequestInterface $request
   * @return array
   * @throws \JsonException Если тело не пустое, но JSON невалиден (сообщение исключения содержит raw body)
   */
  public static function parse(ServerRequestInterface $request): array
  {

    $body = $request->getParsedBody();
    if (!empty($body) && is_array($body)) {
      return $body;
    }

    $rawContent = (string)$request->getBody();

    if ($rawContent === '') {
      return [];
    }

    $cleanContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

    $decoded = json_decode($cleanContent, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
      throw new \JsonException('JSON Decode Fail: raw=' . $rawContent);
    }

    return $decoded;
  }
}