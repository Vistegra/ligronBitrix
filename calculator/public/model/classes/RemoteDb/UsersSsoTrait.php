<?php

namespace RemoteDb;

trait UsersSsoTrait
{

  public function loginBySso(string $login, string $loginType, string $innDealer, string $salonCode): bool
  {
    $this->type = $loginType;

    $user = $this->app->db->getUserByLoginForSso($loginType, $login);

    if (!$user) {
      return false;
    }

    $user['dealerTin'] = $innDealer;
    $user['salonCode'] = $salonCode;

    if ($loginType === 'manager') {
      $user['dealerName'] = 'ИНН: ' . $innDealer; //ToDO передать имя дилера
      $user['salonName'] = 'Салон: ' . $salonCode;
    }

    $this->user = $user;
    $this->setSession();

    if (session_status() === PHP_SESSION_NONE) session_start();

    $_SESSION['dealerTin'] = $innDealer;
    $_SESSION['salonCode'] = $salonCode;
    $_SESSION['dealerId'] = null;
    $_SESSION['id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['loginType'] = $loginType;
    $_SESSION['PHPSESSID'] = $_COOKIE['PHPSESSID'] ?? session_id();

    $pass = $user['password'] ?? 'sso_default_pass';
    $_SESSION['hash'] = password_hash($_SESSION['PHPSESSID'] . $pass, PASSWORD_BCRYPT);

    $this->app->main->db->setUserHash($user['id'], $_SESSION['hash']);

    return true;

  }
}