<?php
namespace OrderApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class CorsMiddleware implements MiddlewareInterface
{
  private array $origins = [
    'http://localhost:5173',
    'http://localhost',
    'https://ligron.ru'
  ];

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $origin = $request->getHeaderLine('Origin');
    $allowed = in_array($origin, $this->origins) ? $origin : 'http://localhost';

    $response = $handler->handle($request);

    if ($request->getMethod() === 'OPTIONS') {
      $response = new Response(200);
    }
   /* if ($request->getMethod() === 'OPTIONS') {
      return (new Response(200))
        ->withHeader('Access-Control-Allow-Origin', $allowed)
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'X-Auth-Token, Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Max-Age', '86400');
    }*/

    $response = $response
      ->withHeader('Access-Control-Allow-Origin', $allowed)
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
      ->withHeader('Access-Control-Allow-Headers', 'X-Auth-Token, Content-Type, Authorization, X-Requested-With')
      ->withHeader('Access-Control-Allow-Credentials', 'true');

    return $response;
  }
}