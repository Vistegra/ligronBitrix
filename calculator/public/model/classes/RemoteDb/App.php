<?php

namespace RemoteDb;

use Exception;
use RedBeanPHP\RedException;

final class App
{
  const QUERY_ALL_DEALERS = 'allDealers';

  private static ?self $instance = null;

  public \Main $main;

  public \UrlGenerator $url;

  public DbProxy $db;

  public Dealers $dealer;

  public Salons $salons;

  public Users $users;

  /**
   * @throws RedException
   */
  private function __construct(\Main $main, array $dbConfig) {
    $this->main = $main;
    $this->url  = $main->url;

    $main->db->connect();
    $this->db     = new DbProxy((new Db($main))->setConfig($dbConfig));

    $this->dealer = new Dealers($this);
    $this->salons = new Salons($this);
    $this->users  = new Users($this);
  }

  /**
   * @param \Main $main
   * @param array $dbConfig
   * @return App
   * @throws RedException
   */
  public static function getInstance(\Main $main, array $dbConfig): self {
    if (self::$instance === null) {
      self::$instance = new self($main, $dbConfig);
    }
    return self::$instance;
  }


  // -------------------------------------------------------------------------------------------------------------------
  // USERS
  // -------------------------------------------------------------------------------------------------------------------

  /**
   * @return void
   * @throws RedException
   * @throws Exception
   */
  public function login()
  {
    $login    = trim($this->url->request->get('login', ''));
    $password = trim($this->url->request->get('password', ''));
    $loginType = $this->main->url->request->get('loginType', 'dealer');

    $passed = $this->users->checkUser($login, $password, $loginType);
    if ($passed) {
      $dealerTin = $this->users->getDefaultDealer();
      $dealers   = $this->dealer->getByTin($dealerTin);

      $defDealer = array_find($dealers, function ($d) use ($dealerTin) {
        return $d['tin'] === $dealerTin;
      });

      if (empty($defDealer)) {
        die('App->login: dealers not found');
      }
      // Создание дилера, если id содержит
      if (includes($defDealer['id'], 'remote')) {
        $defDealer = $this->dealer->create($defDealer);
      }

      $target = $this->main->url->getBaseUri() . 'dealer/' . $defDealer['id'];
      header('Location: ' . $target, true, 303);
    } else {
      $this->main->reDirect("login?status=error&login=$login");
    }

    die;
  }

  public function loadCurrentUser(): array
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Пользователь из локальной БД
    if (!isset($_SESSION['loginType'])) {
      return array_merge(
        $this->main->getLogin('all'),
        ['type' => 'manager'],
      );
    }

    $type     = $_SESSION['loginType'];
    $login    = $_SESSION['login'];
    $password = $_SESSION['password'];

    // Dealer admin
//    $type     = 'dealer';
//    $login    = 'dealer_user1';
//    $password = 'hash1';
    // Dealer admin
//    $type     = 'dealer';
//    $login    = 'dealer_user2';
//    $password = 'hash2';
    // Ligron
//    $type     = 'manager';
//    $login    = 'ladmin1';
//    $password = 'pass1';

    return array_merge(
      $this->users->loadUser($type, $login, $password),
      ['type' => $type],
    );
  }

  public function loadUsers(): array
  {
    $param = [
      'dealerTin' => $this->main->url->request->get('dealerTin', ''),
      'salonCode' => $this->main->url->request->get('salonCode', ''),
    ];

    return $this->users->loadUsers($param);
  }

  // -------------------------------------------------------------------------------------------------------------------
  // DEALERS
  // -------------------------------------------------------------------------------------------------------------------

  /**
   * @throws RedException
   */
  public function loadDealers(array $params): array
  {
    $dealerTin = $params['dealerTin'] ?? trim($this->url->request->get('dealerTin', ''));
    if ($dealerTin !== '') {
      return $this->dealer->getByTin($dealerTin);
    }

    $salonCode = trim($params['salonCode'] ?? $this->url->request->get('salonCode', ''));
    return $this->dealer->getBySalon($salonCode);
  }

  // -------------------------------------------------------------------------------------------------------------------
  // SALONS
  // -------------------------------------------------------------------------------------------------------------------

  public function loadSalons(array $params): array
  {
    $dealerTin = $params['dealerTin'] ?? trim($this->url->request->get('dealerTin', ''));
    if ($dealerTin !== '') {
      return $this->salons->getByTin($dealerTin);
    }

    //$userCode = trim($params['userCode'] ?? $this->url->request->get('userCode', ''));
    //return $this->salons->getByUserCode($userCode);
    return [];
  }

  // -------------------------------------------------------------------------------------------------------------------
  // REMOTE DB CHECKER
  // -------------------------------------------------------------------------------------------------------------------

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getDealersWithSalons(): array
  {
    return $this->db->getDealersWithSalons();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getDealersWithManagers(): array
  {
    return $this->db->getDealersWithManagers();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getDealersWithUsers(): array
  {
    return $this->db->getDealersWithUsers();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getManagersWithDealers(): array
  {
    return $this->db->getManagersWithDealers();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getSalonsWithDealers(): array
  {
    return $this->db->getSalonsWithDealers();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getSalonsWithUsers(): array
  {
    return $this->db->getSalonsWithUsers();
  }

  /**
   * Проверка содержимого таблиц
   * @return array
   */
  public function getUsersWithSalons(): array
  {
    return $this->db->getUsersWithSalons();
  }

  public function exec(array $parameters = []): array
  {
    $mode = $this->url->request->get('cmsAction');

    if (in_array($mode, get_class_methods($this))) {
      return ['data' => $this->$mode($parameters)];
    } else {
      return ['error' => '\App: Methods not exist'];
    }
  }
}
