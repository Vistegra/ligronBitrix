<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use OrderApiV2\Services\Cache\CacheManagerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CacheController extends AbstractController
{
  public function __construct(
    private readonly CacheManagerService $cacheManager
  ) {}

  /**
   * GET /tools/cache/clear?type=all
   */
  public function clear(ServerRequestInterface $request): ResponseInterface
  {
    // читаем параметры из url (?type=...)
    $query = $request->getQueryParams();
    $type = (string)($query['type'] ?? 'all'); // hierarchy, statuses, all

    try {
      match ($type) {
        'hierarchy' => $this->cacheManager->clearHierarchyCache(),
        'statuses'  => $this->cacheManager->clearStatusesCache(),
        default     => $this->cacheManager->clearAllAppCache(),
      };

      return $this->success("Кэш успешно очищен (Тип: {$type})");
    } catch (\Throwable $e) {
      return $this->handleError($e);
    }
  }

}