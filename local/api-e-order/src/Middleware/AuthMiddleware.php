<?php

namespace OrderApi\Middleware;


use OrderApi\DTO\Auth\UserDTO;
use OrderApi\Services\Auth\AuthService;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AuthMiddleware implements MiddlewareInterface
{

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $user = $this->getUserFromRequest($request);

    if (!$user) {
      return $this->createUnauthorizedResponse();
    }

    return $handler->handle($request->withAttribute('user', $user));
  }

  private function getUserFromRequest(ServerRequestInterface $request): ?UserDTO
  {
    $authHeader = $request->getHeaderLine('Authorization');

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      return null;
    }

    $token = $matches[1];
    return AuthService::getUserFromToken($token);
  }

  private function createUnauthorizedResponse(): ResponseInterface
  {
    $response = new Response(401);
    $response->getBody()->write(json_encode([
      'status' => 'error',
      'message' => 'Unauthorized'
    ], JSON_UNESCAPED_UNICODE));

    return $response->withHeader('Content-Type', 'application/json');
  }
}