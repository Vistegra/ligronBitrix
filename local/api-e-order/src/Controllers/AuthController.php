<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\Services\Auth\AuthService;
use OrderApi\Services\Auth\AuthServiceInterface;

class AuthController extends AbstractController
{
  private AuthService $authService;

  public function __construct()
  {
    $this->authService = new AuthService();
  }

  public function login(): void
  {
    $input = $this->getRequestData();

    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';
    $providerType = $input['providerType'] ?? 'dealer';

    if (empty($login) || empty($password)) {
      $this->sendError('Логин и пароль обязательны', 400);
    }

    if (!in_array($providerType, ['dealer', 'ligron'])) {
      $this->sendError('Укажите тип пользователя', 400);
    }

    $result = $this->authService->login($login, $password, $providerType);

    if (!$result) {
      $this->sendError('Неверный логин или пароль', 401);
    }

    $this->sendResponse('Успешный вход', $result);
  }

  public function logout(): void
  {
    $this->authService->logout();
    $this->sendResponse('Выход выполнен успешно');
  }

}