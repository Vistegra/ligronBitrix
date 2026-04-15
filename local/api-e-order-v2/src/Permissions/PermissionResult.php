<?php

declare(strict_types=1);

namespace OrderApiV2\Permissions;

final readonly class PermissionResult
{
  private function __construct(
    public bool $isAllowed,
    public ?string $errorMessage = null
  ) {
  }

  public static function allow(): self
  {
    return new self(true);
  }

  public static function deny(string $message): self
  {
    return new self(false, $message);
  }
}