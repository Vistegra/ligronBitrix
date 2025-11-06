<?php
namespace OrderApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonResponseMiddleware implements MiddlewareInterface
{
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $response = $handler->handle($request);
    if (!$response->hasHeader('Content-Type')) {
      $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
    return $response;
  }
}