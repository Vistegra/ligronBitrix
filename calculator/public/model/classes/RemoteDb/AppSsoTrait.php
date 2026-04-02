<?php

namespace RemoteDb;

trait AppSsoTrait
{
  /**
   * @throws \Exception
   */
  public function ssoLogin()
  {
    $token = $this->url->request->get('param', '');

    if (!$token) {
      $this->main->reDirect("login?status=error&sso=empty_token");
      die();
    }

    $decrypted = $this->main->decrypt($token);

    // Ожидаем 5 частей строке: LOGIN | INN_DEALER | SALON_CODE | REDIRECT_SUFFIX | TIMESTAMP
    $parts = explode('|', $decrypted);

    if (count($parts) === 5) {
      $loginFromSso = $parts[0];
      $innDealer = $parts[1];
      $salonCode = $parts[2];
      $redirect = $parts[3];
      $timestamp = (int)$parts[4];

      // Ссылка действительна 2 минуты
      if (time() - $timestamp < 120) {

        $passed = $this->users->loginBySso($loginFromSso, 'dealer', $innDealer, $salonCode);

        if ($passed) {

          // ИНН берем из переданного контекста
          $dealers = $this->dealer->getByTin($innDealer);

          $defDealer = [];
          foreach ($dealers as $d) {
            if (isset($d['tin']) && $d['tin'] === $innDealer) {
              $defDealer = $d;
              break;
            }
          }

          if (!empty($defDealer)) {
            // Если дилер новый, создаем его в локальной БД калькулятора
            if (strpos((string)$defDealer['id'], 'remote') !== false) {
              $defDealer = $this->dealer->create($defDealer);
            }

            $redirectSuffix = $redirect ? '/' . ltrim($redirect, '/') : '';
            $target = $this->main->url->getBaseUri() . 'dealer/' . $defDealer['id'] . $redirectSuffix;

            header('Location: ' . $target, true, 303);
            die();
          }
        }
      }
    }

    $this->main->reDirect("login?status=error&sso=failed");
    die();
  }
}