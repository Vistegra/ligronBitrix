<?php
namespace OrderApi\Controllers;

use OrderApi\Services\Auth\AuthService;
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

}