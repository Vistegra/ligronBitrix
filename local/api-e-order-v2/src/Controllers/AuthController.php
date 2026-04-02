<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use OrderApiV2\Constants\UserRole;
use OrderApiV2\Services\Auth\Session\AuthSession;
use OrderApiV2\Services\Auth\Token\AuthCrypto;
use OrderApiV2\Services\Auth\Token\AuthService;
use OrderApiV2\Services\Auth\Token\SsoLinkGeneratorService;
use OrderApiV2\DTO\Auth\UserDTO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Контроллер управления аутентификацией и доступом.
 */
final class AuthController extends AbstractController
{
  public function __construct(
    private readonly AuthService $auth
  )
  {
  }

  /**
   * POST /auth/login
   * Основной вход по логину и паролю.
   */
  public function login(ServerRequestInterface $request): ResponseInterface
  {
    $input = $request->getParsedBody() ?? [];

    $login = (string)($input['login'] ?? '');
    $password = (string)($input['password'] ?? '');
    $provider = (string)($input['providerType'] ?? '');

    if (!$login || !$password || !$provider) {
      return $this->error('Логин, пароль и тип провайдера обязательны', 400);
    }

    $result = $this->auth->login($login, $password, $provider);

    return $result
      ? $this->success('Успешный вход', $result)
      : $this->error('Неверный логин или пароль', 401);
  }

  /**
   * POST /auth/login-by-token
   * Вход через временный токен (например, из писем или внешних систем).
   * @deprecated
   */
  public function loginByToken(ServerRequestInterface $request): ResponseInterface
  {
    $input = $request->getParsedBody() ?? [];
    $token = (string)($input['user_token'] ?? '');

    if (!$token) {
      return $this->error('Токен обязателен', 400);
    }

    $result = $this->auth->loginByToken($token);

    return $result
      ? $this->success('Успешный вход по ссылке', $result)
      : $this->error('Неверный или устаревший токен', 401);
  }

  /**
   * GET /auth/me
   * Получение данных о текущем пользователе из JWT и сессии.
   */
  public function me(ServerRequestInterface $request): ResponseInterface
  {
    /** @var UserDTO $user */
    $user = $request->getAttribute('user');

    // publicData содержит расширенную информацию:
    // доступные салоны, ИНН и список привязанных менеджеров Лигрон.
    $detailedData = AuthSession::publicData();

    return $this->success('Данные профиля', [
      'user' => $user->toArray(),
      'detailed' => $detailedData
    ]);
  }

  /**
   * GET /auth/sso
   * Генерация защищенной ссылки для перехода в Калькулятор.
   */
  /**
   * GET /auth/sso
   */
  public function sso(ServerRequestInterface $request): ResponseInterface
  {
    try {
      $params = $request->getQueryParams();

      $requestedInn = $params['inn_dealer'] ?? null;
      $requestedSalon = $params['salon_code'] ?? null;
      $ligronNumber = $params['ligron_number'] ?? null;

      /** @var UserDTO $user */
      $user = $request->getAttribute('user');

      $isGlobal = $this->isGlobalRole($user->role);

      if ($requestedInn && $requestedSalon) {
        if ($isGlobal) {
          // Офис-менеджер (OML) или Бог
          $user = $user->withContext((string)$requestedInn, (string)$requestedSalon);
        } else {
          // Обычный дилер/менеджер
          $availableInns = AuthSession::getAvailableInns() ?: [];
          $availableSalons = AuthSession::getAvailableSalons() ?: [];

          if (in_array($requestedInn, $availableInns, true) && in_array($requestedSalon, $availableSalons, true)) {
            $user = $user->withContext((string)$requestedInn, (string)$requestedSalon);
          } else {
            return $this->error('У вас нет прав доступа к этому подразделению.', 403);
          }
        }
      }

      $ssoService = new SsoLinkGeneratorService($user);

      $link = $ligronNumber
        ? $ssoService->generateOrderLink((string)$ligronNumber)
        : $ssoService->generateLink();

      return $this->success('Ссылка сформирована', ['url' => $link]);

    } catch (\Throwable $e) {
      return $this->error('Ошибка генерации SSO: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Вспомогательный метод проверки "глобальных" ролей
   */
  private function isGlobalRole(string $role): bool
  {
    return in_array($role, [
      UserRole::LIGRON_OFFICE_MANAGER,
      UserRole::GOD_LIGRON,
      UserRole::GOD_DEALER
    ], true);
  }

  /**
   * POST /auth/crypt
   * Вспомогательный метод для шифрования/дешифрования параметров.
   */
  public function crypt(ServerRequestInterface $request): ResponseInterface
  {
    $params = $request->getParsedBody() ?? [];

    // Шифрование
    if (!empty($params['encrypt']) && !empty($params['code'])) {
      return $this->success('Результат шифрования', [
        'param' => AuthCrypto::encrypt((string)$params['code'])
      ]);
    }

    // Дешифрование
    if (!empty($params['decrypt']) && !empty($params['token'])) {
      $result = AuthCrypto::decrypt((string)$params['token']);
      if (!$result) {
        return $this->error('Неверный токен для дешифрования', 400);
      }
      return $this->success('Результат дешифрования', ['param' => $result]);
    }

    return $this->error('Не переданы необходимые параметры (encrypt/code или decrypt/token)', 400);
  }

}