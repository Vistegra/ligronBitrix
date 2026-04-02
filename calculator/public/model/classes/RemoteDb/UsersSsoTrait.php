<?php

namespace RemoteDb;

trait UsersSsoTrait
{
  public function loginBySso(string $login, string $loginType, string $innDealer, string $salonCode): bool
  {
    $this->type = $loginType;
    $user = $this->app->db->getUserByLoginType($loginType, $login);

    if (!$user) {
      return false;
    }

    $this->user = $user;
    $this->setSession();

    // Переопределяем контекст
    $_SESSION['dealerTin'] = $innDealer;
    $_SESSION['salonCode'] = $salonCode;

    $main = $this->app->main;
    if (session_status() === PHP_SESSION_NONE) session_start();

    $_SESSION['id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['PHPSESSID'] = $_COOKIE['PHPSESSID'] ?? session_id();

    $pass = $user['password'] ?? 'sso_pass';
    $_SESSION['hash'] = password_hash($_SESSION['PHPSESSID'] . $pass, PASSWORD_BCRYPT);
    $main->db->setUserHash($user['id'], $_SESSION['hash']);

    return true;
  }
}