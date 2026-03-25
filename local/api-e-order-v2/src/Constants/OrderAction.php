<?php

declare(strict_types=1);

namespace OrderApiV2\Constants;

final class OrderAction
{
  public const string VIEW = 'view';

  public const string CREATE = 'create';

  public const string UPDATE = 'update';

  public const string DELETE = 'delete';
  public const string CHANGE_STATUS = 'change_status';
  public const string SEND_TO_1C = 'send_to_1c';
}