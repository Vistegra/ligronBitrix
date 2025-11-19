<?php
namespace OrderApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class CorsMiddleware implements MiddlewareInterface
{
  private array $allowedOrigins = [
    'http://localhost',

    'http://localhost:5173',
    'https://localhost:5173',

    'https://local.ligron.ru:5173',
    'https://ligron.localhost:5173',

    'https://ligron.ru',
    'http://ligron.ru',
  ];

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $origin = $request->getHeaderLine('Origin');

    // Проверяем, пришёл ли Origin и разрешён ли он
    $isAllowedOrigin = $origin !== '' && in_array($origin, $this->allowedOrigins, true);


    $response = $handler->handle($request);

    if ($request->getMethod() === 'OPTIONS') {
      $response = new Response(200);
    }

    //Для локальной разработки, чтобы сессия не менялась
    if ($isAllowedOrigin && str_contains($origin, 'localhost')) {
      $sessionId = session_id();
      $response = $response->withHeader('Set-Cookie', "PHPSESSID=$sessionId; Path=/; SameSite=None; Secure=false; HttpOnly=false");
    }


    if ($isAllowedOrigin) {
      $response = $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Credentials', 'true');
    } /*else {
      $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    }*/

    return $response
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
      ->withHeader('Access-Control-Allow-Headers', 'X-Auth-Token, Content-Type, Authorization, Cookie, X-Requested-With')
      ->withHeader('Access-Control-Expose-Headers', 'Set-Cookie')
      ->withHeader('Vary', 'Origin')
      ->withHeader('Access-Control-Max-Age', '86400');
  }
}