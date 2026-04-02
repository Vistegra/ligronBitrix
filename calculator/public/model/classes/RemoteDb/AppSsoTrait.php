<?php

namespace RemoteDb;

trait AppSsoTrait
{

  public function ssoLogin()
  {
    $token = $this->url->request->get('param', '');

    if (!$token) {
      $this->main->reDirect("login?status=error&sso=no_token");
      die();
    }

    $decrypted = $this->decryptSso($token);

    // Ожидаем 6 частей: LOGIN | PROVIDER | INN_DEALER | SALON_CODE | REDIRECT_SUFFIX | TIMESTAMP
    $parts = explode('|', $decrypted);

    if (count($parts) === 6) {
      $login     = $parts[0];
      $provider  = $parts[1]; // 'dealer' || 'ligron'
      $inn       = $parts[2];
      $salon     = $parts[3];
      $redirect  = $parts[4];
      $timestamp = (int)$parts[5];

      // Ссылка действительна 2 минуты
      if (time() - $timestamp < 120) {

        $loginType = ($provider === 'ligron') ? 'manager' : 'dealer';

        $passed = $this->users->loginBySso($login, $loginType, $inn, $salon);

        if ($passed) {
          //  Ищем дилера в локальной базе калькулятора по ИНН из токена
          $dealers = $this->dealer->getByTin($inn);
          $targetDealer = null;

          foreach ($dealers as $d) {
            if (isset($d['tin']) && $d['tin'] === $inn) {
              $targetDealer = $d;
              break;
            }
          }

          if ($targetDealer) {

            if (strpos((string)$targetDealer['id'], 'remote') !== false) {
              $targetDealer = $this->dealer->create($targetDealer);
            }

            $_SESSION['dealerId'] = $targetDealer['id'];

            $suffix = $redirect ? '/' . ltrim($redirect, '/') : '';
            $url = $this->main->url->getBaseUri() . 'dealer/' . $targetDealer['id'] . $suffix;

            header('Location: ' . $url, true, 303);
            die();
          }
        }
      }
    }

    $this->main->reDirect("login?status=error&sso=invalid_token");
    die();
  }

  private function decryptSso(string $param): string
  {
    $ca = 'aes-256-cbc';
    $key = 'RzFsgDG0WPtiWH9Zfr94';

    $decoded = base64_decode($param);
    if (strpos($decoded, '::') === false) return '';

    list($encryptedData, $iv) = explode('::', $decoded, 2);
    return openssl_decrypt($encryptedData, $ca, $key, 0, $iv) ?: '';
  }
}