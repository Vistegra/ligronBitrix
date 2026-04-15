<?php

declare(strict_types=1);

namespace OrderApiV2\Permissions;

final readonly class RolePolicy
{
  /**
   * @param string[] $allowedUpdateFields
   */
  public function __construct(
    public bool $viewAll,
    public bool $createForAny,
    public array $allowedUpdateFields,
    public bool $canChangeStatus,
    public bool $canSendTo1c,
    public bool $canUpdateSentOrder,
    public bool $canDeleteSentOrder,
  ) {
  }
}