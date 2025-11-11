<?php
declare(strict_types=1);

namespace OrderApi\DTO\Order;

final readonly class FileUploadResult
{
  public function __construct(
    public ?int    $fileId,
    public string  $originalName,
    public ?string $error = null
  ) {}

  public function isSuccess(): bool
  {
    return $this->fileId !== null;
  }

  public function toArray(): array
  {
    return [
      'file_id' => $this->fileId,
      'original_name' => $this->originalName,
      'error' => $this->error,
    ];
  }
}