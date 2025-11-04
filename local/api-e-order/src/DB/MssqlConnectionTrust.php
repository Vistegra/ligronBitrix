<?php

declare(strict_types=1);

namespace OrderApi\DB;

use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\ConnectionException;
class MssqlConnectionTrust extends MssqlConnection
{
  /**
   * Establishes a connection to the database.
   * Includes php_interface/after_connect_d7.php on success.
   * Throws exception on failure.
   *
   * @return void
   * @throws ConnectionException
   */
  protected function connectInternal(): void
  {
    if ($this->isConnected)
    {
      return;
    }

    $connectionInfo = [
      "UID" => $this->login,
      "PWD" => $this->password,
      "Database" => $this->database,
      "ReturnDatesAsStrings" => true,
      /*"CharacterSet" => "utf-8",*/

      "TrustServerCertificate" => true,
      "Encrypt" => false, // Отсключаем шифрование
    ];

    if ($this->isPersistent())
    {
      $connectionInfo["ConnectionPooling"] = true;
    }
    else
    {
      $connectionInfo["ConnectionPooling"] = false;
    }

    $connection = sqlsrv_connect($this->host, $connectionInfo);

    if (!$connection)
    {
      throw new ConnectionException('MS Sql connect error', $this->getErrorMessage());
    }

    $this->resource = $connection;
    $this->isConnected = true;

    // hide cautions
    sqlsrv_configure("WarningsReturnAsErrors", 0);

    $this->afterConnected();
  }

}