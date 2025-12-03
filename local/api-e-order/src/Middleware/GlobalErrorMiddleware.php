<?php
namespace OrderApi\Middleware;

use OrderApi\Services\LogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;

final class GlobalErrorMiddleware implements MiddlewareInterface
{
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    try {
      return $handler->handle($request);
    }

    //ToDo InvalidArgumentException,
    //ToDo RuntimeException

    catch (HttpMethodNotAllowedException $e) {
      $response = new Response(405);

      $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'type' => $e::class,
      ], JSON_UNESCAPED_UNICODE));

      return $response;
    }

    catch (HttpNotFoundException $e) {
      $response = new Response(404);

      $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'type' => $e::class,
      ], JSON_UNESCAPED_UNICODE));

      return $response;
    }

    //Необработанный тип ошибки
    catch (\Throwable $e) {
      LogService::error($e);

      $response = new Response(500);

      $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'type' => $e::class,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], JSON_UNESCAPED_UNICODE));

      return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

  }
}