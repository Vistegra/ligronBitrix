<?php
declare(strict_types=1);

namespace OrderApiV2\DTO\Order;

final readonly class FileUploadResult
{
  public function __construct(
    public ?array  $file = null,
    public ?string $originalName = null,
    public ?string $error = null
  ) {}

  public function isSuccess(): bool
  {
    return $this->file !== null;
  }

  public function toArray(): array
  {
    return [
        'file' => $this->file,
        'original_name' => $this->originalName,
        'error' => $this->error
      ];
  }
}