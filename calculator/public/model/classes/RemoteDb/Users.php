<?php

namespace RemoteDb;

use Exception;

class Users
{
  use UsersSsoTrait;

  private App $app;

  /**
   * User type dealer / manager
   * @var string $type
   */
  public string $type = 'dealer';

  private array $user = [];

  public function __construct(App $app) {
    $this->app = $app;
  }

  private function setSession()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user = $this->user;

    $_SESSION['customAuth'] = true;
    $_SESSION['login']      = $user['login'];
    $_SESSION['password']   = $user['password'];
    $_SESSION['loginType']  = $this->type;
  }

  private function baseLogin(array $user, string $password)
  {
    $main = $this->app->main;

    if (session_status() === PHP_SESSION_NONE) session_start();

    $_SESSION['id']       = $user['id'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['login']    = $user['login'];
    $_SESSION['password'] = $password;
    $_SESSION['PHPSESSID'] = $_COOKIE['PHPSESSID'];

    $_SESSION['hash'] = password_hash($_COOKIE['PHPSESSID'] . $password, PASSWORD_BCRYPT);
    $main->db->setUserHash($user['id'], $_SESSION['hash']);

    if ($main->isDealer() || isset($user['dealerId'])) {
      $_SESSION['dealerId'] = $user['dealerId'] ?? $main->getCmsParam('dealerId');

      $target = $main->url->getUri();
      $dealLink = "dealer/$user[dealerId]";
      if (!includes($target, $dealLink)) $target .= "dealer/$user[dealerId]";

      header('Location: ' . $target, true, 303);
      die;
    }

    else $main->reDirect('');
  }


  /**
   * @throws Exception
   */
  private function validateRequireParam(string $field, $value)
  {
    switch ($field) {
      case 'userCode':
      case 'salonCode':
      case 'dealerTin':
        if (empty($value)) {
          throw new Exception("Users field $field is empty");
        }
    }
  }

  public function loadUser(string $loginType, string $login, string $password): array
  {
    $user = $this->app->db->getUserByLoginPasswordType($loginType, $login, $password);

    /*if ($user['dealerTin'] === '' || ($user['salonCode'] ?? '') === '') {
      $user = [];
    }*/

    return $user;
  }

  public function loadUsers(array $param): array
  {
    return $this->app->db->getUsersByInnDealer('dealer', $param['dealerTin'], $param['salonCode']);
  }

  public function checkUser(string $login, string $password, string $loginType): bool
  {
    $this->type = $loginType;
    $user = $this->app->db->getUserByLoginType($loginType, $login);

    if (!$user || $password !== $user['password']) {
      // Попытка входа по локалькой БД
      if ($loginType === 'manager'
        &&
        $user = $this->app->main->db->selectDb('default')->checkPassword($login, $password))
      {
        $this->baseLogin($user, $password);
        die();
      }

      return false;
    }

    $this->user = $user;
    $this->setSession();

    return true;
  }

  /**
   * @throws Exception
   */
  public function getDefaultDealer()
  {
    // Проверка выбранного дилера из сессии
    $prevDealerTin = $_SESSION['dealerTin'] ?? null;

    if ($this->type === 'dealer') {
      // Все доступные дилеры по коду салона
      $dealers = $this->app->dealer->getBySalon($this->get('salonCode'));
    } else {
      // Все доступные дилеры по коду пользователя
      $dealers = $this->app->dealer->getByUserCode($this->get('userCode'));
    }

    if (!empty($prevDealerTin) && count($dealers) > 1) {
      $dealer = array_find($dealers, function (array $dealer) use ($prevDealerTin) {
        return $dealer['dealerTin'] === $prevDealerTin;
      });

      if ($dealer) return $prevDealerTin;
    }

    return $this->get('dealerTin');
  }

  /**
   * @throws Exception
   */
  public function get(string $field)
  {
    $r = $this->user[$field] ?? null;

    $this->validateRequireParam($field, $r);

    return $this->user[$field] ?? null;
  }
}
