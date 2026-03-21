<?php

declare(strict_types=1);

namespace OrderApiV2\Constants;

final class UserRole
{
  // Роли дилеров (из таблицы dealer_roles)
  public const string DEALER_MANAGER = 'M';
  public const string DEALER_SALON_MANAGER = 'MS';
  public const string DEALER_LIGRON_MANAGER = 'LM';

  // Роли Лигрон (из таблицы ligron_roles)
  public const string LIGRON_MANAGER = 'ML';
  public const string LIGRON_OFFICE_MANAGER = 'OML';
}
