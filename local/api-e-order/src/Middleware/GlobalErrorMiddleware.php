<?php
namespace OrderApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class GlobalErrorMiddleware implements MiddlewareInterface
{
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    try {
      return $handler->handle($request);
    } catch (\Throwable $e) {
      $logPath = $request->getAttribute('logPath');

      if ($logPath) {
        $log = new Logger('api');
        $log->pushHandler(new StreamHandler($logPath));
        $log->error($e);
      }

      $response = new Response(500);

      $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'type' => $e::class,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'log_file' => $logPath
      ], JSON_UNESCAPED_UNICODE));

      return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
  }
}