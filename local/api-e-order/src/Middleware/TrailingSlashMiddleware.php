<?php
namespace OrderApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrailingSlashMiddleware implements MiddlewareInterface
{
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $uri = $request->getUri();
    $path = $uri->getPath();

    // Убираем слеш в конце, кроме корня
    if ($path !== '/' && str_ends_with($path, '/')) {
      $request = $request->withUri($uri->withPath(rtrim($path, '/')));
    }

    return $handler->handle($request);
  }
}