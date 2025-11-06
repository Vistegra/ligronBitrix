<?php
namespace OrderApi\Middleware;

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
    $payload = AuthService::validateFromHeader();
    if (!$payload) {
      $res = new Response(401);
      $res->getBody()->write(json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
      ], JSON_UNESCAPED_UNICODE));
      return $res->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request->withAttribute('user', $payload));
  }
}