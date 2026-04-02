<?php

namespace RemoteDb;

class Salons
{
  private App $app;

  public function __construct(App $app) {
    $this->app = $app;
  }

  /**
   * Load all salons by dealer INN
   * @param string $tin
   * @return array
   */
  public function getByTin(string $tin): array
  {
    return $this->app->db->getSalonsByInnDealer($tin);
  }
}
