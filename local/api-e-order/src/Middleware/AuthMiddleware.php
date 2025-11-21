<?php

namespace OrderApi\Middleware;


use DI\Container;
use OrderApi\DTO\Auth\UserDTO;
use OrderApi\Services\Auth\Session\AuthSession;
use OrderApi\Services\Auth\Token\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class AuthMiddleware implements MiddlewareInterface
{
  public function __construct(
    private Container $container
  ) {}

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $user = $this->getUserFromRequest($request);

    if (!$user) {
      return $this->createUnauthorizedResponse();
    }
    // Подключаем пользователя в DI
    $this->container->set(UserDTO::class, $user);

    // Загружаем детальные данные пользователя в сессию один раз
    AuthSession::load($user);

    // Подключаем пользователя в Request
    return $handler->handle($request->withAttribute('user', $user));
  }

  private function getUserFromRequest(ServerRequestInterface $request): ?UserDTO
  {
    // Используем X-Auth-Token
    $token = $request->getHeaderLine('X-Auth-Token');

    return AuthService::getUserFromToken($token);
  }

  private function createUnauthorizedResponse(): ResponseInterface
  {
    $response = new Response(401);
    $response->getBody()->write(json_encode([
      'status' => 'error',
      'message' => 'Unauthorized: Missing or invalid token'
    ], JSON_UNESCAPED_UNICODE));

    AuthSession::clear();

    return $response->withHeader('Content-Type', 'application/json');
  }
}