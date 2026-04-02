<?php

namespace RemoteDb;

use RedBeanPHP\RedException;

/**
 * @mixin Db
 *
 * @property Db $db
 * @property Db $staticDb
 */
final class DbProxy
{
  /**
   * @var Db
   */
  private $db;

  /**
   * @var Db
   */
  private static $staticDb;

  /**
   * @param Db $db
   */
  public function __construct(Db $db)
  {
    $this->db = $db;
    self::$staticDb = $db;
  }

  /**
   * @param $method
   * @param $args
   * @return mixed
   * @throws RedException
   */
  public function __call($method, $args)
  {
    $this->db->connect();

    return $this->db->$method(...$args);
  }

  /**
   * @param string $method
   * @param $args
   * @return mixed
   */
  public static function __callStatic(string $method, $args)
  {
    return forward_static_call_array([self::$staticDb, $method], $args);
  }
}
