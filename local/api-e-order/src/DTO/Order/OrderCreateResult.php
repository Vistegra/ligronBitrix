<?php
declare(strict_types=1);

namespace OrderApi\DTO\Order;

use OrderApi\DTO\Order\FileUploadResult;

final readonly class OrderCreateResult
{
  public function __construct(
    public bool    $success,
    public ?int    $orderId = null,
    /** @var FileUploadResult[]  */
    public array   $fileResults = [],
    public ?string $orderError = null
  ) {}

  public function hasFileErrors(): bool
  {
    return count(array_filter($this->fileResults, fn($r) => !$r->isSuccess())) > 0;
  }

  public function allFilesFailed(): bool
  {
    return !empty($this->fileResults) && empty(array_filter($this->fileResults, fn($r) => $r->isSuccess()));
  }
}