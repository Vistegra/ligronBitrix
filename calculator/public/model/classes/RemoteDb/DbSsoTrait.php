<?php

namespace RemoteDb;

trait DbSsoTrait
{

  public function getUserByLoginForSso(string $loginType, string $login): ?array
  {
    $table = ($loginType === 'manager') ? 'ligron_users' : 'dealer_users';

    $sql = "SELECT TOP 1 *, username AS [login] FROM [dbo].[$table] WHERE username = ? AND active = 1";

    $rows = $this->handler->getAll($sql, [$login]);

    return !empty($rows) ? $rows[0] : null;
  }
}