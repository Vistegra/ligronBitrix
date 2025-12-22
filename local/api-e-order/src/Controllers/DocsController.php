<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocsController extends AbstractController
{
  private const string DOCS_DIR = __DIR__ . '/../../docs/';
  private const string LAYOUT_FILE = __DIR__ . '/../../docs/layout.php';

  // Структура меню (Sidebar)
  private array $menu = [
    'index' => [
      'title' => 'Главная',
      'category' => 'General'
    ],
    'auth' => [
      'title' => 'Авторизация',
      'category' => 'Security'
    ],
    'statuses' => [
      'title' => 'Справочник статусов',
      'category' => 'Reference'
    ],
    'webhook_1c_create' => [
      'title' => 'Webhook 1C: Создание заказа',
      'category' => 'Integration'
    ],
    'webhook_1c_update_status' => [
      'title' => 'Webhook 1C: Обновление статуса',
      'category' => 'Integration'
    ],
    'orders_list' => [
      'title' => 'Список заказов',
      'category' => 'Orders'
    ],
    'orders_get_by_id' => [
      'title' => 'Заказ по ID',
      'category' => 'Orders'
    ],
    'orders_get_by_number' => [
      'title' => 'Заказ по номеру',
      'category' => 'Orders'
    ],
    'orders_create' => [
      'title' => 'Создание заказа',
      'category' => 'Orders'
    ],
    'orders_update' => [
      'title' => 'Обновление заказа',
      'category' => 'Orders'
    ],
    'orders_upload_files' => [
      'title' => 'Загрузка файлов',
      'category' => 'Orders'
    ],
    'orders_delete_file' => [
      'title' => 'Удаление файла',
      'category' => 'Orders'
    ],
  ];

  // GET /docs
  public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    return $this->renderPage($response, 'index');
  }

  // GET /docs/{page}
  public function page(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $page = $args['page'] ?? 'index';

    if (!preg_match('/^[a-z0-9_]+$/', $page)) {
      return $this->error('Invalid page name', 400);
    }

    if (!array_key_exists($page, $this->menu)) {
      $page = '404';
    }

    return $this->renderPage($response, $page);
  }

  private function renderPage(ResponseInterface $response, string $page): ResponseInterface
  {
    $filePath = self::DOCS_DIR . $page . '.php';

    // Если .php нет, ищем .html
    if (!file_exists($filePath)) {
      $filePath = self::DOCS_DIR . $page . '.html';
    }

    if (!file_exists($filePath)) {
      return $this->error('Documentation file not found', 404);
    }

    // Данные для Layout
    $title = $this->menu[$page]['title'] ?? 'Docs';
    $currentCategory = $this->menu[$page]['category'] ?? 'General';


    ob_start();
    include $filePath;
    $content = ob_get_clean();

    // Буферизация Layout
    ob_start();

    // Переменные, доступные внутри layout.php
    $menu = $this->menu;
    $activePage = $page;
    $pageTitle = $title;
    $breadcrumbs = $this->buildBreadcrumbs($page, $title);

    require self::LAYOUT_FILE;

    $html = ob_get_clean();

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
  }

  private function buildBreadcrumbs(string $slug, string $title): array
  {
    $crumbs = [
      ['title' => 'API Docs', 'link' => '/local/api-e-order/docs/']
    ];

    if ($slug !== 'index') {
      $crumbs[] = ['title' => $title, 'link' => null]; // Активная страница без ссылки
    }

    return $crumbs;
  }
}