<?php

namespace RemoteDb;

require_once ABS_SITE_PATH . 'public/const.php';

class DBQuery
{
  public function getAll(string $sql, array $bindings = []): array
  {
    return $this->executeQuery($sql, $bindings);
  }

  public function getRow(string $sql, array $bindings = []): array
  {
    $result = $this->executeQuery($sql, $bindings);
    return $result[0] ?? [];
  }

  private function executeQuery(string $sql, array $bindings = [])
  {
    $result = httpRequest(MAIN_SERVER_URL, [
      'method' => 'post',
      'contentType' => 'application/json; charset=utf-8',
    ], json_encode([
      'mode' => 'executeQuery',
      'sql'  => $sql,
      'bind' => $bindings, // Никаких параметров запросов
    ]));

    return $result['status'] ? $result['data'] : $result['data']['error'];
  }

  public function __call(string $name, array $arguments)
  {
    return $this->forwardCall($name, $arguments);
  }

  public static function __callStatic(string $name, array $arguments)
  {
    $instance = new self();
    return $instance->forwardCall($name, $arguments);
  }

  private function forwardCall(string $name, array $arguments)
  {
    return null;
  }
}
