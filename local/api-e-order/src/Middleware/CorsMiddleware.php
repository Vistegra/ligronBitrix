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

    // Обработка OPTIONS preflight запроса
    /*if ($request->getMethod() === 'OPTIONS') {
      $response = new Response(200);
    } else {
      $response = $handler->handle($request);
    }*/

    $response
      ->withHeader('Access-Control-Allow-Origin', $allowed)
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
      ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
      ->withHeader('Access-Control-Allow-Credentials', 'true');

    return $response;
  }
}