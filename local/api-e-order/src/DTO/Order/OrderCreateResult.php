<?php
declare(strict_types=1);

namespace OrderApi\DTO\Order;

use OrderApi\DTO\Order\FileUploadResult;

final readonly class OrderCreateResult
{
  public function __construct(
    public bool              $success,
    public ?array            $order = null,
    /** @var FileUploadResult[] */
    public array             $fileResults = [],
    public ?string           $orderError = null
  ) {}

  public function isSuccess(): bool
  {
    return $this->success;
  }

  public function hasFileErrors(): bool
  {
    return count($this->getFailedResults()) > 0;
  }

  public function allFilesFailed(): bool
  {
    return !empty($this->fileResults) && empty($this->getSuccessfulFiles());
  }

  /** @return array<int, array> Только успешные файлы для data.files */
  public function getSuccessfulFiles(): array
  {
    return array_filter(
      array_map(fn(FileUploadResult $r) => $r->file, $this->fileResults),
      fn($file) => $file !== null
    );
  }

  /** @return string[] Оригинальные имена провалившихся файлов */
  public function getFailedOriginalNames(): array
  {
    $names = [];
    foreach ($this->fileResults as $result) {
      if (!$result->isSuccess() && $result->originalName !== null) {
        $names[] = $result->originalName;
      }
    }
    return array_unique($names);
  }

  private function getFailedResults(): array
  {
    return array_filter($this->fileResults, fn($r) => !$r->isSuccess());
  }

  private function getSuccessfulResults(): array
  {
    return array_filter($this->fileResults, fn($r) => $r->isSuccess());
  }
}