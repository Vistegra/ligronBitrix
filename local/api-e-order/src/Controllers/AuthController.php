<?php
namespace OrderApi\Controllers;


use OrderApi\Services\Auth\Session\AuthSession;
use OrderApi\Services\Auth\Token\AuthCrypto;
use OrderApi\Services\Auth\Token\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends AbstractController
{
  public function __construct(private readonly AuthService $auth) {}

  // POST /auth/login
  public function login(ServerRequestInterface $request): ResponseInterface
  {
    $input = $request->getParsedBody() ?? [];

    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';
    $provider = $input['providerType'] ?? '';

    if (!$login || !$password) {
      return $this->error('Логин и пароль обязательны',400);
    }

    if (!$provider) {
      return $this->error('Не передан тип пользователя', 400);
    }

    $result = $this->auth->login($login, $password, $provider);
    return $result
      ? $this->success('Успешный вход', $result)
      : $this->error('Неверный логин или пароль', 401);
  }

  // вызывать с AuthMiddleware
  public function me(ServerRequestInterface $request): ResponseInterface
  {
    //AuthSession::clear();
    //AuthSession::load($request->getAttribute('user'));
    // вызывать с AuthMiddleware
    $data = AuthSession::publicData();
    return $this->success('Детальные данные пользователя', ['detailed' => $data]);
  }

  public function crypt(ServerRequestInterface $request): ResponseInterface
  {
    $params = $request->getParsedBody();

    if ($params['encrypt'] && $params['code']) {
      $result = AuthCrypto::encrypt($params['code']);

      return $this->success('Детальные данные пользователя', ['param' => $result]);
    }

    if ($params['decrypt'] && $params['token']) {
      $result = AuthCrypto::decrypt($params['token']);

      if (!$result) {
        return $this->error('Неверный токен ', 400);
      }

      return $this->success('Детальные данные пользователя', ['param' => $result]);
    }

    return $this->error('Не переданы все параметеры ', 400);

  }

}